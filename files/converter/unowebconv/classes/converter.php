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

// const UNOCONVPATH_ERROR = 'error';
    /** Webservice could not be reached with given path. */
    const UNOCONVWSPATH_NOT_FOUND_ERROR = "notfound";
    const UNOCONVWSPATH_NOT_FOUND_ERROR_MSG = "Path error: webservice not found";

    /** Any other error */
    const UNOCONVWSPATH_ERROR = 'error';

    const UNOCONVWSPATH_ERROR_MSG = "unknown error occured.";

    /**
     * @var bool $requirementsmet Whether requirements have been met.
     */
    protected static $requirementsmet = false;

    /**
     * @var array $formats The list of formats supported by unoconv.
     */
    protected static $formats;

    /**
     * Convert a document to a new format and return a conversion object relating to the conversion in progress.
     *
     * @param   conversion $conversion The file to be converted
     * @return  $this
     */
    public function start_document_conversion(\core_files\conversion $conversion) {
        global $CFG;

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

        // The temporary file to copy into.
        $newtmpfile = pathinfo($filename, PATHINFO_FILENAME) . '.' . $format;
        $newtmpfile = $uniqdir . '/' . clean_param($newtmpfile, PARAM_FILE);

        $cmd = escapeshellcmd(trim($CFG->pathtounoconv)) . ' ' .
               escapeshellarg('-f') . ' ' .
               escapeshellarg($format) . ' ' .
               escapeshellarg('-o') . ' ' .
               escapeshellarg($newtmpfile) . ' ' .
               escapeshellarg($filename);

        $output = null;
        $currentdir = getcwd();
        chdir($uniqdir);
        $result = exec($cmd, $output, $returncode);
        chdir($currentdir);
        touch($newtmpfile);

        if ($returncode != 0) {
            $conversion->set('status', conversion::STATUS_FAILED);
            error_log(
                "Unoconv conversion for '" . $filepath . "' from '" . $fromformat . "' to '" . $format . "' " .
                "was unsuccessful; returned with exit status code (" . $returncode . "). Please check the unoconv " .
                "configuration and conversion file content / format."
            );
            return $this;
        }

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

        $conversion
            ->store_destfile_from_path($newtmpfile)
            ->set('status', conversion::STATUS_COMPLETE)
            ->update();

        return $this;
    }

    /**
     * Poll an existing conversion for status update.
     *
     * @param   conversion $conversion The file to be converted
     * @return  response $response The response object containing 
     * a status and:
     *  if status is 'in queue' contains its position in queue
     *  if status is 'converted' contains the converted file
     */
    public function poll_conversion_status(conversion $conversion) {
        // Get current conversion status for given Id.
        // Todo: find out if this is how conversion "looks"
        $response = curl_handler::fetch_url_data($conversion->id);
        $status = $response["status"];
        $status = self::get_conversion_status($status);
        $conversion->set('status', $status);
        return $response;
    }

    protected static function get_conversion_status(string $status) {
        switch ($status) {
            case 'converted':
                return \core_files\conversion::STATUS_COMPLETE;
            case 'in queue':
                return \core_files\conversion::STATUS_PENDING;
            case 'processing':
                return \core_files\conversion::STATUS_IN_PROGRESS;
        }
    }

    /**
     * Generate and serve the test document.
     *
     * @return  void
     */
    public function serve_test_document() {
        // Todo: rework/refactor
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        $format = 'pdf';

        $filerecord = [
            'contextid' => \context_system::instance()->id,
            'component' => 'test',
            'filearea' => 'fileconverter_unowebconv',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'unoconv_test.docx'
        ];

        // Get the fixture doc file content and generate and stored_file object.
        $fs = get_file_storage();
        $testdocx = $fs->get_file($filerecord['contextid'], $filerecord['component'], $filerecord['filearea'],
                $filerecord['itemid'], $filerecord['filepath'], $filerecord['filename']);

        if (!$testdocx) {
            $fixturefile = dirname(__DIR__) . '/tests/fixtures/unoconv-source.docx';
            $testdocx = $fs->create_file_from_pathname($filerecord, $fixturefile);
        }

        $conversions = conversion::get_conversions_for_file($testdocx, $format);
        foreach ($conversions as $conversion) {
            if ($conversion->get('id')) {
                $conversion->delete();
            }
        }

        $conversion = new conversion(0, (object) [
                'sourcefileid' => $testdocx->get_id(),
                'targetformat' => $format,
            ]);
        $conversion->create();

        // Convert the doc file to the target format and send it direct to the browser.
        $this->start_document_conversion($conversion);
        do {
            sleep(1);
            $this->poll_conversion_status($conversion);
            $status = $conversion->get('status');
        } while ($status !== conversion::STATUS_COMPLETE && $status !== conversion::STATUS_FAILED);

        readfile_accel($conversion->get_destfile(), 'application/pdf', true);
    }

    /**
     * Whether the plugin is configured and requirements are met.
     *
     * @return  bool
     */
    public static function are_requirements_met() {
        // Todo: rework/refactor
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
        self::$formats = (new converter)->get_supported_conversions();
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
        $formats = (new converter)->get_supported_conversions();
        // To-Do: filter if conversion format is in list
        return $formats;
    }

    /**
     * Fetch the list of supported file formats.
     *
     * @return  array
     */
    public function get_supported_conversions() {
        $base_url = self::get_unoconv_webservice_url();
        $response_data = curl_handler::fetch_url_data($base_url . 'formats');
        $response = array();
        foreach ($response_data as $key => $value) {
            $response = array_merge($response, $value);
        }
        return $response;
    }

    /**
     * Get Unowebconv url from settings
     *
     * @return string
     */
    private static function get_unoconv_webservice_url() {
        return get_config('fileconverter_unowebconv', 'pathtounoconvws');
    }
}
