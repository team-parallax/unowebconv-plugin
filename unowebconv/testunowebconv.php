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
 * Test that unowebconv is configured correctly
 *
 * @package   fileconverter_unowebconv
 * @copyright 2020 Sven Patrick Meier <sven.patrick.meier@team-parallax.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/filelib.php');
use fileconverter_unowebconv\converter;
$sendpdf = optional_param('sendpdf', 0, PARAM_BOOL);

$PAGE->set_url(new moodle_url('/files/converter/unowebconv/testunowebconv.php'));
$PAGE->set_context(context_system::instance());

require_login();
require_capability('moodle/site:config', context_system::instance());

$strheading = get_string('test_unoconvws', 'fileconverter_unowebconv');
$PAGE->navbar->add(get_string('administrationsite'));
$PAGE->navbar->add(get_string('plugins', 'admin'));
$PAGE->navbar->add(get_string('pluginname', 'fileconverter_unowebconv'),
        new moodle_url('/admin/settings.php', array('section' => 'fileconverterunowebconv')));
$PAGE->navbar->add($strheading);
$PAGE->set_heading($strheading);
$PAGE->set_title($strheading);

$converter = new converter();

$response = converter::test_unoconv_path();
$path = get_config('fileconverter_unowebconv', 'pathtounoconvws');
$msg = $OUTPUT->get_string('pluginname', 'fileconverter_unowebconv') . 'path: ' . $path;
if ($response->status === converter::UNOCONVWSPATH_OK) {
    $unoresponse = $OUTPUT->notification(get_string('test_unoconvwsok', 'fileconverter_unowebconv'), 'success');
}
else {
    
    $unoresponse = $OUTPUT->notification($response, 'warning');
}

$returl = new moodle_url('/admin/settings.php', array('section' => 'fileconverterunowebconv'));
$msg .= $OUTPUT->continue_button($returl);
echo $OUTPUT->header();
echo $OUTPUT->box($unoresponse, 'generalbox');
echo $OUTPUT->footer();
