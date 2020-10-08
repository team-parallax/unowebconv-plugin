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

defined('MOODLE_INTERNAL') || die();

/**
 * Installation for unowebconv.
 *
 * @package   fileconverter_unowebconv
 * @copyright 2020 Sven Patrick Meier <sven.patrick.meier@team-parallax.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function xmldb_fileconverter_unoconv_install() {
    global $CFG;

    $unoconvpresent = !empty($CFG->pathtounoconvws);
    // Todo: implement check for webservice availability
    // $unoconvpresent = $unoconvpresent && file_exists($CFG->pathtounoconv);
    if ($unoconvpresent) {
        // Unowebconv is configured correctly, enable it.
        $plugins = \core_plugin_manager::instance()->get_plugins_of_type('fileconverter');
        $plugins['unowebconv']->set_enabled(true);
    }
}
