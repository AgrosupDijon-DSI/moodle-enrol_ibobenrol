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

require_once('../../config.php');
require_once('edit_form.php');
require_once($CFG->dirroot.'/lib/enrollib.php');

$courseid   = required_param('courseid', PARAM_INT);
$instanceid = optional_param('id', 0, PARAM_INT);

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($course->id);

require_login($course);
require_capability('enrol/ibobenrol:config', $context);

$PAGE->set_url('/enrol/ibobenrol/edit.php', ['courseid' => $course->id, 'id' => $instanceid]);
$PAGE->set_pagelayout('admin');

$return = new moodle_url('/enrol/instances.php', ['id' => $course->id]);
if (!enrol_is_enabled('ibobenrol')) {
    redirect($return);
}

$plugin = enrol_get_plugin('ibobenrol');

if ($instanceid) {
    $instance = $DB->get_record('enrol',
        ['courseid' => $course->id, 'enrol' => 'ibobenrol', 'id' => $instanceid], '*', MUST_EXIST);
} else {
    require_capability('moodle/course:enrolconfig', $context);

    // No instance yet, we have to add a new instance.
    navigation_node::override_active_url(new moodle_url('/enrol/instances.php', ['id' => $course->id]));
    $instance = new stdClass();
    $instance->id = null;
    $instance->courseid = $course->id;
    $instance->status = ENROL_INSTANCE_ENABLED;
}

$mform = new enrol_ibobenrol_edit_form(null, [$instance, $plugin, $context]);

if ($mform->is_cancelled()) {
    redirect($return);
} else if ($data = $mform->get_data()) {
    if ($instance->id) {
        if (isset($data->name)) {
            if (!empty($data->name)) {
                $instance->name = $data->name;
            } else {
                $data->name = '';
            }
        } else {
            $data->name = '';
        }
        $instance->courseid = $data->courseid;
        $instance->roleid = $data->roleid;

        if (!empty($data->customint1)) {
            $instance->customint1 = $data->customint1;
        } else {
            $instance->customint1 = 0;
        }

        if (!empty($data->badges)) {
            $instance->customtext1 = implode('#', $data->badges);
        } else {
            $instance->customtext1 = null;
        }
        $plugin->update_instance($instance, $data);

    } else {
        if (!empty($data->badges)) {
            $badges = implode('#', $data->badges);
        } else {
            $badges = null;
        }

        if (!isset($data->name)) {
            $data->name = '';
        }
        $fields = ['name' => $data->name, 'courseid' => $data->courseid, 'roleid' => $data->roleid, 'customtext1' => $badges];

        if (!empty($data->customint1)) {
            $fields['customint1'] = $data->customint1;
        }
        $plugin->add_instance($course, $fields);

    }

    redirect($return);
}

$PAGE->set_heading($course->fullname);
$PAGE->set_title(get_string('pluginname', 'enrol_ibobenrol'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'enrol_ibobenrol'));
$mform->display();
echo $OUTPUT->footer();
