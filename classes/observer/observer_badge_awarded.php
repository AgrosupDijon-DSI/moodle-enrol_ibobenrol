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
 * Plugin enrol_ibobenrol.
 *
 * @package    enrol_ibobenrol
 * @copyright  2023, frederic.grebot <frederic.grebot@agrosupdijon.fr>, L'Institut Agro Dijon, DSI, CNERTA-WEB
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol_ibobenrol_observer {

    /**
     * Observer function to handle the assessable_uploaded event in enrol_ibob.
     *
     * @param \core\event\badge_awarded $event
     * @return void
     */
    public static function badge_awarded(\core\event\badge_awarded $event): void {
        global $DB;
        // Check to see if this badge should result in an enrolment.
        static $enrolmentplugins;
        // Check to make sure this plugin is enabled.
        if (!enrol_is_enabled('ibobenrol')) {
            return;
        }
        $eventdata = $event->get_data();
        // It would be better if the enrol plugin used it's own tables so we could search for plugins relevant
        // to this badge, instead we populate an array to use in case multiple badges are assigned at the same time.
        if (empty($enrolmentplugins)) {
            // Get all enrolment plugins.
            $enrolmentplugins = $DB->get_records('enrol', ['enrol' => 'ibobenrol', 'customint1' => 1]);
        }
        foreach ($enrolmentplugins as $ep) {
            $badges = explode('#', $ep->customtext1);
            if (!empty($eventdata['other']['badgeissuedid'])) {
                $badgeid = $DB->get_field('badge_issued', 'badgeid', ['id' => $eventdata['other']['badgeissuedid']]);
                if (in_array($badgeid, $badges)) {
                    if (count($badges) > 1) { // If more than one badge required, check user has all.
                        foreach ($badges as $badge) {
                            if ($badge == $badgeid) {
                                continue;
                            }
                            // Check the user has this badge - if not, prevent enrolment.
                            if (!$DB->record_exists('badge_issued', ['badgeid' => $badge,
                                'userid' => $eventdata['relateduserid'], ])) {
                                return; // Stop here and don't enrol user, more badges required before enrolment can be given.
                            }
                        }
                    }
                    $plugin = enrol_get_plugin('ibobenrol');
                    $plugin->enrol_user($ep, $eventdata['relateduserid'], $ep->roleid, time());
                }
            }
        }
    }
}
