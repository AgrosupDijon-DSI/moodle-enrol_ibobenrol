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

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir.'/adminlib.php');

/**
 * Plugin enrol_ibobenrol.
 *
 * @package    enrol_ibobenrol
 * @copyright  2023, frederic.grebot <frederic.grebot@agrosupdijon.fr>, L'Institut Agro Dijon, DSI, CNERTA-WEB
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol_ibobenrol_plugin extends enrol_plugin {

    /**
     * Returns optional enrolment information icons.
     *
     * This is used in course list for quick overview of enrolment options.
     *
     * We are not using single instance parameter because sometimes
     * we might want to prevent icon repetition when multiple instances
     * of one type exist. One instance may also produce several icons.
     *
     * @param array $instances all enrol instances of this type in one course
     * @return array of pix_icon
     */
    public function get_info_icons(array $instances): array {
        $key = false;
        $nokey = false;
        foreach ($instances as $instance) {
            if ($this->can_self_enrol($instance, false) !== true) {
                // User can not enrol himself.
                // Note that we do not check here if user is already enrolled for performance reasons -
                // such check would execute extra queries for each course in the list of courses and
                // would hide self-enrolment icons from guests.
                continue;
            }
            if ($instance->password || $instance->customint1) {
                $key = true;
            } else {
                $nokey = true;
            }
        }
        $icons = [];
        $icons[] = new pix_icon('badge', get_string('pluginname', 'enrol_ibobenrol'), 'enrol_ibobenrol');

        return $icons;
    }

    /**
     * Allow unenrol.
     * @param stdClass $instance
     * @return bool
     */
    public function allow_unenrol(stdClass $instance): bool {
        // Users with unenrol cap may unenrol other users manually.
        return true;
    }

    /**
     * Allow mange by capability.
     * @param stdClass $instance
     * @return bool
     */
    public function allow_manage(stdClass $instance):bool {
        // Users with manage cap may tweak period and status.
        return true;
    }

    /**
     * Add course navigation.
     * @param navigation_node $instancesnode
     * @param stdClass $instance
     * @return void
     */
    public function add_course_navigation($instancesnode, stdClass $instance):void {
        if ($instance->enrol !== 'ibobenrol') {
             throw new coding_exception('Invalid enrol instance type!');
        }

        $context = context_course::instance($instance->courseid);
        if (has_capability('enrol/ibobenrol:config', $context)) {
            $managelink = new moodle_url('/enrol/ibobenrol/edit.php',
                ['courseid' => $instance->courseid, 'id' => $instance->id]);
            $instancesnode->add($this->get_instance_name($instance), $managelink, navigation_node::TYPE_SETTING);
        }
    }

    /**
     * Is it possible to hide/show enrol instance via standard UI?
     *
     * @param stdClass $instance
     * @return bool
     */
    public function can_hide_show_instance($instance):bool {
        $context = context_course::instance($instance->courseid);
        return has_capability('enrol/ibobenrol:config', $context);
    }

    /**
     * Returns link to page which may be used to add new instance of enrolment plugin in course.
     *
     * @param int $courseid
     * @return moodle_url page url
     */
    public function get_newinstance_link($courseid) {
        $context = context_course::instance($courseid, MUST_EXIST);

        if (!has_capability('moodle/course:enrolconfig', $context) || !has_capability('enrol/ibobenrol:config', $context)) {
            return null;
        }
        // Multiple instances supported - different roles with different password.
        return new moodle_url('/enrol/ibobenrol/edit.php', ['courseid' => $courseid]);
    }

    /**
     * Delete instance if capability.
     *
     * @param stdClass $instance
     * @return bool
     */
    public function can_delete_instance($instance):bool {
        $context = context_course::instance($instance->courseid);
        return has_capability('enrol/ibobenrol:config', $context);
    }

    /**
     * Hook.
     *
     * @param stdClass $instance
     * @return false|string|null
     */
    public function enrol_page_hook(stdClass $instance) {
        global $OUTPUT, $USER, $DB;

        ob_start();

        if ($DB->record_exists('user_enrolments', ['userid' => $USER->id, 'enrolid' => $instance->id])) {
            return ob_get_clean();
        }

        $context = context_system::instance();

        // Can not enrol guest.
        if (isguestuser()) {
            return null;
        }

        $configbadges = explode('#', $instance->customtext1);

        if (empty($configbadges[0])) {
            return $OUTPUT->box(get_string('nobadgesconfigured', 'enrol_ibobenrol'), 'generalbox');
        }

        $access = $this->check_required_badges($USER->id, $configbadges);

        if ($access) {
            $form = new \enrol_ibobenrol\forms\enrol_ibobenrol_form(null, $instance);
            $instanceid = optional_param('instance', 0, PARAM_INT);
            if ($instance->id == $instanceid) {
                if ($data = $form->get_data()) {
                    $this->enrol_ibobenrol($instance, $data);
                }
            }

            $form->display();
            $output = ob_get_clean();
            return $OUTPUT->box($output);
        } else {

            $out = $OUTPUT->box(get_string('enrolinfo', 'enrol_ibobenrol'), 'generalbox');
            foreach ($configbadges as $badgeid) {

                $badge = $DB->get_record('local_ibob_badges', ['id' => $badgeid], '*', MUST_EXIST);

                $imageurl = $badge->image;
                $attributes = ['src' => $imageurl, 'alt' => s($badge->name), 'class' => 'ibobenrol-activatebadge'];

                $name = html_writer::tag('span', $badge->name, ['class' => 'badge-name']);
                $image = html_writer::empty_tag('img', $attributes);
                $url = new moodle_url($badge->image, ['type' => 1]);

                $badgeout = html_writer::link($url, $image.$name, ['title' => $badge->name, 'class' => 'requiredbadge']);

                $out .= $OUTPUT->box($badgeout, 'generalbox');
            }

        }

        return $out;
    }

    /**
     * Check the required badges.
     *
     * @param int $userid
     * @param array $badges
     * @return bool
     */
    public function check_required_badges(int $userid, array $badges):bool {
        global $DB;
        $access = false;
        foreach ($badges as $badgeid) {
            if ($record = $DB->get_record('local_ibob_badge_issued', ['badgeid' => $badgeid, 'userid' => $userid])) {
                if (!$record->expirationdate || $record->expirationdate >= time()) {
                    $access = true;
                }
            }
        }
        return $access;
    }

    /**
     * Enrol user to course
     *
     * @param stdClass $instance enrolment instance
     * @param stdClass $data data needed for enrolment.
     * @return bool|array true if enroled else error code and messege
     */
    public function enrol_ibobenrol(stdClass $instance, $data = null) {
        global $USER;

        if ($this->enrol_user($instance, $USER->id, $instance->roleid, time(), 0)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Gets an array of the user enrolment actions.
     *
     * @param course_enrolment_manager $manager
     * @param stdClass $ue A user enrolment object
     * @return array An array of user_enrolment_actions
     */
    public function get_user_enrolment_actions(course_enrolment_manager $manager, $ue):array {
        $actions = [];
        $context = $manager->get_context();
        $instance = $ue->enrolmentinstance;
        $params = $manager->get_moodlepage()->url->params();
        $params['ue'] = $ue->id;
        if ($this->allow_unenrol_user($instance, $ue) && has_capability("enrol/ibobenrol:unenrol", $context)) {
            $url = new moodle_url('/enrol/unenroluser.php', $params);
            $actions[] = new user_enrolment_action(new pix_icon('t/delete', ''), get_string('unenrol', 'enrol'), $url,
                ['class' => 'unenrollink', 'rel' => $ue->id]);
        }
        return $actions;
    }

    /**
     * Get the action icons.
     *
     * @param stdClass $instance
     * @return array
     */
    public function get_action_icons(stdClass $instance):array {
        global $OUTPUT;

        if ($instance->enrol !== 'ibobenrol') {
            throw new coding_exception('invalid enrol instance!');
        }
        $context = context_course::instance($instance->courseid);

        $icons = [];

        if (has_capability('enrol/ibobenrol:manage', $context)) {
            $editlink = new moodle_url('/enrol/ibobenrol/edit.php',
                ['courseid' => $instance->courseid, 'id' => $instance->id]);
            $icons[] = $OUTPUT->action_icon($editlink, new pix_icon('t/edit', get_string('edit'), 'core',
                ['class' => 'iconsmall']));
        }

        return $icons;
    }
}
