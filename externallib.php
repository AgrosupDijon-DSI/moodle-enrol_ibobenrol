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
 * @package     enrol_ibobenrol
 * @copyright  2024, frederic.grebot <frederic.grebot@agrosupdijon.fr>, L'Institut Agro Dijon, DSI, CNERTA-WEB
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir."/externallib.php");

/**
 * External lib for enrol_ibobenrol.
 *
 * @package    enrol_ibobenrol
 * @copyright  2024, frederic.grebot <frederic.grebot@agrosupdijon.fr>, L'Institut Agro Dijon, DSI, CNERTA-WEB
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol_ibobenrol_external extends external_api {
    /**
     * Filter the badges list.
     *
     * @param string $userinput
     * @return string
     */
    public static function filter_badges(string $userinput) {
        global $DB;
        $likefield1 = trim($DB->sql_like('name', ':name', false, false));
        $likefield2 = trim($DB->sql_like('description', ':description', false, false));
        $badgesfiltered = $DB->get_records_sql(
                "SELECT distinct(id) AS badgeid, name FROM {local_ibob_badges} WHERE {$likefield1} OR {$likefield2} ORDER BY name",
                [
                        'name' => '%'.$userinput.'%',
                        'description' => '%'.$userinput.'%',
                ]
        );
        $retunedoptions = "";
        foreach ($badgesfiltered as $obadge) {
            $retunedoptions .= html_writer::start_tag('option', ['value' => $obadge->badgeid]);
            $retunedoptions .= $obadge->name;
            $retunedoptions .= html_writer::end_tag('option');
        }
        return json_encode($retunedoptions);
    }

    /**
     * Badge detail function.
     *
     * @param string $userinput
     * @return mixed
     */
    public static function filter_badge_function(string $userinput) {
        self::validate_parameters(self::filter_badge_function_parameters(), ['userinput' => $userinput]);
        return self::filter_badges($userinput);
    }

    /**
     * Badge detail function return.
     *
     * @return external_value
     */
    public static function filter_badge_function_returns() {
        return new external_value(PARAM_RAW, 'Json returned');
    }

    /**
     * Badge detail function return.
     *
     * @return external_function_parameters
     */
    public static function filter_badge_function_parameters() {
        return new external_function_parameters(
            [
                'userinput' => new external_value(PARAM_RAW, 'filter search', VALUE_REQUIRED),
            ]
        );
    }
}
