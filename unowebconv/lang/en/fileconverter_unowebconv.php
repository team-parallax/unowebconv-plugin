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
 * Strings for plugin 'fileconverter_unowebconv'
 *
 * @package   fileconverter_unowebconv
 * @copyright 2020 Sven Patrick Meier <sven.patrick.meier@team-parallax.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pathtounoconvws'] = 'Url to unoconv document converter webservice';
$string['pathtounoconvws_help'] = 'Url to unoconv document converter webservice. This is a webservice that is capable of converting between document formats supported by LibreOffice. This is used to support a wider range of input files for the assignment annotate PDF feature.';
$string['pluginname'] = 'Unowebconv';
$string['privacy:metadata'] = 'The Unoconv document converter plugin does not store any personal data.';
$string['test_unoconvws'] = 'Test unoconv webservice url';
$string['test_unoconvwserror'] = 'The webservice seems to have troubles responding. Review your settings.';
$string['test_unoconvwsdoesnotexist'] = 'The provided url does not point to the unoconv-webservice. Please review your path settings.';
$string['test_unoconvwsempty'] = 'The unoconv url is not set. Please review your path settings.';
$string['test_unoconvwsnotestfile'] = 'The test document to be converted to PDF is missing.';
$string['test_unoconvwsok'] = 'The unoconv webservice url appears to be properly configured.';
