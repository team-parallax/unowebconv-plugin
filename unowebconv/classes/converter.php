<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Class for converting files between different file formats using unoconv.
 *
 * @package    fileconverter_unowebconv
 * @copyright  2020 Sven Patrick Meier <sven.patrick.meier@team-parallax.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace fileconverter_unowebconv;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir . '/filelib.php');

use cache;
use stored_file;
use \core_files\conversion;
use \fileconverter_unowebconv\curl_handler as curl_handler;

class converter implements \core_files\converter_interface {

    /** No errors */
    const UNOCONVWSPATH_OK = 'ok';

    /** Path to webservice is not set */
    const UNOCONVWSPATH_EMPTY = 'emptypath';
    const UNOCONVWSPATH_EMPTY_MSG = "Path to webservice is empty";

    /** Test file missing */
    const UNOCONVWSPATH_NOTESTFILE = 'notestfile';

    /** Webservice could not be reached with given path. */
    const UNOCONVWSPATH_NOT_FOUND_ERROR = "notfound";
    const UNOCONVWSPATH_NOT_FOUND_ERROR_MSG = "Path error: webservice not found";

    /** Any other error */
    const UNOCONVWSPATH_ERROR = 'error';

    const UNOCONVWSPATH_ERROR_MSG = "unknown error occured.";

    /**
     * @var bool $requirementsmet Whether requirements have been met.
     */
    protected static $requirementsmet = null;

    /**
     * Convert a document to a new format and return a conversion object relating to the conversion in progress.
     *
     * @param   conversion $conversion The file to be converted
     * @return  $this
     */
    public function start_document_conversion(\core_files\conversion $conversion) {
        self::log("start_document_conversion: ");

        if (!self::are_requirements_met()) {
            $conversion->set('status', conversion::STATUS_FAILED);
            error_log(
                "Unoconv conversion failed to verify the configuraton meets the minimum requirements. " .
                "Please check the unoconv installation configuration."
            );
            return $this;
        }

        $file = $conversion->get_sourcefile();
        $filepath = $file->get_filepath();

        // Sanity check that the conversion is supported.
        $fromformat = pathinfo($file->get_filename(), PATHINFO_EXTENSION);
        if (!self::is_format_supported($fromformat)) {
            $conversion->set('status', conversion::STATUS_FAILED);
            error_log(
                "Unoconv conversion for '" . $filepath . "' found input '" . $fromformat . "' " .
                "file extension to convert from is not supported."
            );
            return $this;
        }

        $format = $conversion->get('targetformat');
        if (!self::is_format_supported($format)) {
            $conversion->set('status', conversion::STATUS_FAILED);
            error_log(
                "Unoconv conversion for '" . $filepath . "' found output '" . $format . "' " .
                "file extension to convert to is not supported."
            );
            return $this;
        }

        // Copy the file to the tmp dir.
        $uniqdir = make_unique_writable_directory(make_temp_directory('core_file/conversions'));
        \core_shutdown_manager::register_function('remove_dir', array($uniqdir));
        $localfilename = $file->get_id() . '.' . $fromformat;

        $filename = $uniqdir . '/' . $localfilename;
        try {
            // This function can either return false, or throw an exception so we need to handle both.
            if ($file->copy_content_to($filename) === false) {
                throw new \file_exception('storedfileproblem', 'Could not copy file contents to temp file.');
            }
        } catch (\file_exception $fe) {
            error_log(
                "Unoconv conversion for '" . $filepath . "' encountered disk permission error when copying " .
                "submitted file contents to unique temp file: '" . $filename . "'."
            );
            throw $fe;
        }

        $byte_array = array_values(unpack('C*', $file->get_content()));

        $unoconvwspath = self::get_unoconv_webservice_url();
        $arr = [
            "file" => [
                "type" => "Buffer",
                "data" => $byte_array
            ],
            "filename" => $file->get_filename(),
            "originalFormat" => $fromformat,
            "targetFormat" => $format
        ];
        $response = curl_handler::post_conversion($unoconvwspath . "conversion", $arr);
        if(isset($response->status) && isset($response->name) && isset($response->message) && $response->status !== 200) {
            error_log( $response->name . ": " .  $response->message);
            return $this;
        }
        if(empty($response->conversionId)) {
            error_log( "Unknown Error");
            return $this;
        }
        $conversionId = $response->conversionId;
        self::log('$conversionId: ' . $conversionId);
        $conversion
            ->set('status', conversion::STATUS_IN_PROGRESS)
            /* @TODO */
            ->set('statusmessage', "In Progress")
            ->set('data', $conversionId)
            ->update();
        return $this;
    }

    /**
     * Poll an existing conversion for status update.
     *
     * @param   conversion $conversion The file to be converted
     * @return  $this
     */
    public function poll_conversion_status(conversion $conversion) {
        self::log("poll_conversion_status");
        // Get current conversion status for given Id.
        $conversionId = $conversion->get("data");
        $base_url = self::get_unoconv_webservice_url();
        $response = curl_handler::fetch_url_data($base_url . "conversion/" . $conversionId);
        self::log($response->status);
        $status = self::get_conversion_status($response->status);
        if($status === conversion::STATUS_COMPLETE) {
            // Create unique directory
            $uniqdir = make_unique_writable_directory(make_temp_directory('core_file/conversions'));
            \core_shutdown_manager::register_function('remove_dir', array($uniqdir));

            // The temporary file to copy into.
            $file = $conversion->get_sourcefile();
            $format = $conversion->get('targetformat');
            $localfilename = $file->get_id() . '.' . $format;
            $newtmpfile = $uniqdir . '/' . $localfilename;

            // Copy
            $buffer = $response->resultFile->data;
            $str = pack('C*', ...$buffer);
            file_put_contents($newtmpfile, $str);

            if (!file_exists($newtmpfile)) {
                $conversion->set('status', conversion::STATUS_FAILED);
                error_log(
                    "Unoconv conversion for '" . $filepath . "' from '" . $fromformat . "' to '" . $format . "' " .
                    "was unsuccessful; the output file was not found in '" . $newtmpfile . "'. Please check the disk " .
                    "permissions."
                );
                return $this;
            }

            if (filesize($newtmpfile) === 0) {
                $conversion->set('status', conversion::STATUS_FAILED);
                error_log(
                    "Unoconv conversion for '" . $filepath . "' from '" . $fromformat . "' to '" . $format . "' " .
                    "was unsuccessful; the output file size has 0 bytes in '" . $newtmpfile . "'. Please check the " .
                    "conversion file content / format with the command: [ " . $cmd . " ]"
                );
                return $this;
            }

            self::log($newtmpfile);

            $conversion
                ->store_destfile_from_path($newtmpfile)
                ->set('status', $status)
                ->update();
        }
        else if($status === conversion::STATUS_FAILED) {
            /* TODO try again? */
            $conversion
                ->set('status', $status);
        }
        else if($status === conversion::STATUS_IN_PROGRESS) {
            $conversion
                ->set('status', $status)
                ->update();
        }
        return $this;
    }

    protected static function get_conversion_status(string $status) {
        switch ($status) {
            case 'converted':
                return \core_files\conversion::STATUS_COMPLETE;
            case 'in queue':
                return \core_files\conversion::STATUS_PENDING;
            case 'processing':
                return \core_files\conversion::STATUS_IN_PROGRESS;
            default:
                return \core_files\conversion::STATUS_FAILED;
        }
    }


    /**
     * Whether the plugin is configured and requirements are met.
     *
     * @return  bool
     */
    public static function are_requirements_met() {
        self::log("are_requirements_met");
        if (self::$requirementsmet === null) {
            $requirementsmet = self::test_unoconv_path()->status === self::UNOCONVWSPATH_OK;
            self::$requirementsmet = $requirementsmet;
        }
        return self::$requirementsmet;
    }

    /**
     * Whether the plugin is fully configured.
     *
     * @return  \stdClass
     */
    public static function test_unoconv_path() {
        self::log("test_unoconv_path");
        $unoconvwspath = self::get_unoconv_webservice_url();
        $ret = new \stdClass();
        $ret->status = self::UNOCONVWSPATH_OK;
        $ret->message = null;
        if (empty($unoconvwspath)) {
            $ret->status = self::UNOCONVWSPATH_EMPTY;
            $ret->message = self::UNOCONVWSPATH_EMPTY_MSG;
            return $ret;
        }
        $ping_response = curl_handler::get_http_response_code($unoconvwspath . "formats/");
        if ($ping_response !== 200) {
            if ($ping_response === 400 || $ping_response === 404) {
                $ret->status = self::UNOCONVWSPATH_NOT_FOUND_ERROR;
                $ret->message = self::UNOCONVWSPATH_NOT_FOUND_ERROR_MSG;
                return $ret;
            }
            // $ret->status = self::UNOCONVWSPATH_ERROR;
            // $ret->message = self::UNOCONVWSPATH_ERROR_MSG;
        }
        return $ret;
    }

    /**
     * Whether a file conversion can be completed using this converter.
     *
     * @param   string $from The source type
     * @param   string $to The destination type
     * @return  bool
     */
    public static function supports($from, $to) {
        return self::is_format_supported($from) && self::is_format_supported($to);
    }

    /**
     * Whether the specified file format is supported.
     *
     * @param   string $format Whether conversions between this format and another are supported
     * @return  bool
     */
    protected static function is_format_supported($format) {
        $formatObjects = (new converter)->fetch_unoconv_supported_conversions();
        foreach ($formatObjects as $key => $value) {
            $extension = $value->extension;
            if ($extension === $format) {
                return true;
            }
        }
        return false;
    }

    /**
     * Fetch the list of supported file formats.
     * Only for displaying in admin panel
     *
     * @return  string
     */
    public function get_supported_conversions() {
        $formatObjects = $this->fetch_unoconv_supported_conversions();
        $extensions = [];
        foreach ($formatObjects as $key => $value) {
            $extensions []= $value->extension;
        }
        return implode(', ', $extensions);
    }

    private function fetch_unoconv_supported_conversions() {
        $base_url = self::get_unoconv_webservice_url();
        self::log($base_url);
        $cache = cache::make('fileconverter_unowebconv', 'formats');
        if(!$cache->get('formats')) {
            $response_object = curl_handler::fetch_url_data($base_url . 'formats');
            self::log($response_object);
            $cache->set('formats', $response_object->document);
        }
        return $cache->get('formats');
    }

    /**
     * Get Unowebconv url from settings
     *
     * @return string
     */
    private static function get_unoconv_webservice_url() {
        $unowebconv_url = get_config('fileconverter_unowebconv', 'pathtounoconvws');
        if (substr($unowebconv_url, -1) !== '/') {
            $unowebconv_url .= '/';
        }
        return $unowebconv_url;
    }

    private static function log($msg) {
        $log = gmdate('H:i:s', time()) . ":  " . var_export($msg,true);
        file_put_contents("/tmp/log.txt", $log.PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}
