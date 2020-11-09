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

$string['pathtounoconvws'] = 'Url für den Unoconv-Webservice';
$string['pathtounoconvws_help'] = 'Url für den Unoconv-Webservice. Dieser Webservice kann Dokumente zwischen Dokumentformaten, die von LibreOffice unterstützt werden, konvertieren. Dadurch können mehrere verschiedene Formate für das Annotate PDF Feature akzeptiert werden.';
$string['pluginname'] = 'Unowebconv';
$string['privacy:metadata'] = 'Das Unowebconv Dokumentkonvertierungsplugin speichert keine persönlichen Daten.';
$string['test_unoconvws'] = 'Den unoconv-webservice testen';
$string['test_unoconvwserror'] = 'Der Webservice scheint Probleme zu haben die Anfrage zu verarbeiten. Überprüfen Sie ihre Einstellungen.';
$string['test_unoconvwsdoesnotexist'] = 'Die angegebene URL scheint nicht auf den unoconv-webservice zu verweisen. Überprüfen Sie ihre URL zum Webservice.';
$string['test_unoconvwsempty'] = 'Es wurde keine URL für den unoconv-webservice angegeben. Überprüfen Sie ihre Einstellungen.';
$string['test_unoconvwsnotestfile'] = 'Es wurde kein Testdokument zum konvertieren bereitgestellt.';
$string['test_unoconvwsok'] = 'Die URL für den unoconv-webservice scheint korrekt angegeben zu sein.';
