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
define('CLI_SCRIPT', true);
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

$testArr = array(
    'data' =>
        array (
            'sourcefileid' => '3913',
            'targetformat' => 'pdf',
            'id' => 16,
            'status' => 1,
            'statusmessage' => NULL,
            'converter' => '\\fileconverter_unowebconv\\converter',
            'destfileid' => NULL,
            'data' => NULL,
            'timecreated' => 1602169192,
            'timemodified' => 1602169193,
            'usermodified' => '2',
        ),
    'errors' =>
        array (
        ),
    'validated' => true,
);
$conversion = new \core_files\conversion();
$conversion->set("sourcefileid", "3913");
$conversion->set('targetformat', 'pdf');
$conversion->set('id', 16);
$conversion->set('status', 1);
$conversion->set('statusmessage', NULL);
//$conversion->set('converter', new converter());
$conversion->set('destfileid', NULL);
$conversion->set('data', "973006fa-f133-4403-bbc0-c39da3415727");
$conversion->set('timecreated', 1602169192);
$conversion->set('timemodified', 1602169193);
$conversion->set('usermodified', '2');
$conversion->validate();

$converter = new converter();

//$response = $converter->start_document_conversion($conversion);
$response = $converter->poll_conversion_status($conversion);
