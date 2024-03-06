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

namespace enrol_ibobenrol\forms;

/**
 * Plugin enrol_ibobenrol.
 *
 * @package    enrol_ibobenrol
 *
 * @copyright  2023, frederic.grebot <frederic.grebot@agrosupdijon.fr>, L'Institut Agro Dijon, DSI, CNERTA-WEB
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol_ibobenrol_form extends \moodleform {

    /**
     * @var
     */
    protected $instance;

    /**
     * Overriding this function to get unique form id for multiple self enrolments.
     *
     * @return string form identifier
     */
    protected function get_form_identifier(): string {
        $formid = $this->_customdata->id . '_' . get_class($this);
        return $formid;
    }

    /**
     * Form definition.
     *
     * @return void
     */
    public function definition(): void {
        global $USER, $OUTPUT, $CFG;

        $mform = $this->_form;
        $instance = $this->_customdata;
        $this->instance = $instance;
        $plugin = enrol_get_plugin('ibobenrol');

        $heading = $plugin->get_instance_name($instance);
        $mform->addElement('header', 'ibobenrol_header', $heading);

        $mform->addElement('static', 'access', '', get_string('accessgranted', 'enrol_ibobenrol'));

        $this->add_action_buttons(false, get_string('enrolme', 'enrol_ibobenrol'));

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $instance->courseid);

        $mform->addElement('hidden', 'instance');
        $mform->setType('instance', PARAM_INT);
        $mform->setDefault('instance', $instance->id);
    }

    /**
     * Perform minimal validation on the settings form.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files): array {

        $errors = parent::validation($data, $files);
        $instance = $this->instance;

        return $errors;
    }
}
