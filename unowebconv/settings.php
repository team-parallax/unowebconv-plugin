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
 * Settings for unowebconv.
 *
 * @package   fileconverter_unowebconv
 * @copyright 2020 Sven Patrick Meier <sven.patrick.meier@team-parallax.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$enableUnoWebConv = function () {
    \fileconverter_unowebconv\converter::log("Installing DB for unowebconv");
    $plugins = \core_plugin_manager::instance()->get_plugins_of_type('fileconverter');
    if (array_key_exists('unowebconv', $plugins)) {
        \fileconverter_unowebconv\converter::log("Enable unowebconv");
        $plugins['unowebconv']->set_enabled(true);
    }
    if (array_key_exists('unoconv', $plugins)) {
        $enabled_plugins = \core_plugin_manager::instance()->get_enabled_plugins('fileconverter');
        \fileconverter_unowebconv\converter::log($enabled_plugins);
        if (array_key_exists('unoconv', $enabled_plugins)) {
            \fileconverter_unowebconv\converter::log("Disable unoconv");
            $plugins['unoconv']->set_enabled(false);
        }
    }
};

// Unowebconv setting.
$unowebconv_url_setting = new admin_setting_configtext(
    'fileconverter_unowebconv/pathtounoconvws',
    get_string('pathtounoconvws', 'fileconverter_unowebconv'),
    get_string('pathtounoconvws_help', 'fileconverter_unowebconv'),
    'https://example-webservice.com'
);
$unowebconv_url_setting->set_updatedcallback($enableUnoWebConv);
$settings->add(
    $unowebconv_url_setting
);

$url = new moodle_url('/files/converter/unowebconv/testunowebconv.php');
$link = html_writer::link($url, get_string('test_unoconvws', 'fileconverter_unowebconv'));
$settings->add(new admin_setting_heading('test_unoconvws', '', $link));
