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
 * Installation script for the enrol_ibobenrol plugin.
 *
 * This script updates the configuration for enrolment plugins by adding the
 * ibobenrol enrolment method to the list of enabled plugins.
 *
 * @package    enrol_ibobenrol
 * @copyright  2025, frederic.grebot <frederic.grebot@agrosupdijon.fr>, L'Institut Agro Dijon, DSI, CNERTA-WEB
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
function xmldb_enrol_ibobenrol_install() {
    // Retrieve the current configuration value for enabled enrolment plugins.
    $enabledplugins = get_config(null, 'enrol_plugins_enabled');

    if (empty($enabledplugins)) {
        // If no configuration exists, initialize with default methods plus ibobenrol.
        $newplugins = 'ibobenrol';
    } else {
        // Split the string into an array and trim any whitespace.
        $plugins = array_map('trim', explode(',', $enabledplugins));
        // Add ibobenrol if it is not already present.
        if (!in_array('ibobenrol', $plugins)) {
            $plugins[] = 'ibobenrol';
        }
        // Reassemble the list into a comma-separated string.
        $newplugins = implode(',', $plugins);
    }

    // Update the configuration in the database.
    set_config('enrol_plugins_enabled', $newplugins);
}
