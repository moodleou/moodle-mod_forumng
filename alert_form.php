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

require_once($CFG->libdir.'/formslib.php');

/**
 * Form for user to report inappropriate posts.
 * @package mod
 * @subpackage forumng
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_forumng_alert_form extends moodleform {

    public function definition() {

        global $CFG, $USER;
        $mform = $this->_form;

        // Add all the check boxes
        $mform->addElement('static', 'alert_intro', '',
            get_string('alert_info', 'forumng'));

        $checkboxarray = array();

        $checkboxarray[] =& $mform->createElement('checkbox', 'alert_condition1',
            '', get_string('alert_condition1', 'forumng'));

        $checkboxarray[] =& $mform->createElement('checkbox', 'alert_condition2',
            '', get_string('alert_condition2', 'forumng'));

        $checkboxarray[] =& $mform->createElement('checkbox', 'alert_condition3',
            '', get_string('alert_condition3', 'forumng'));

        $checkboxarray[] =& $mform->createElement('checkbox', 'alert_condition4',
            '', get_string('alert_condition4', 'forumng'));

        $checkboxarray[] =& $mform->createElement('checkbox', 'alert_condition5',
            '', get_string('alert_condition5', 'forumng'));

        $checkboxarray[] =& $mform->createElement('checkbox', 'alert_condition6',
            '', get_string('alert_condition6', 'forumng'));

        $mform->addGroup($checkboxarray, get_string('alert_reasons', 'forumng'),
                get_string('alert_reasons', 'forumng'), '<br />', false);

        // Plain text field.
        $mform->addElement('textarea', 'alert_conditionmore',
                get_string('alert_conditionmore', 'forumng'), array('cols'=>50,
                    'rows'=> 15));

        $mform->setType('alert_conditionmore', PARAM_RAW);

        $mform->addElement('static', '', '',
            get_string('alert_reporterinfo', 'forumng'));

        $mform->addElement('static', '', '',
            get_string('alert_reporterdetail', 'forumng', $this->_customdata));

        // Add submit and cancel buttons.
        $this->add_action_buttons(true, get_string('alert_submit', 'forumng'));

        // Add postid as hidden field.
        $mform->addElement('hidden', 'p', $this->_customdata->postid);
        $mform->setType('p', PARAM_INT);
        $mform->addElement('hidden', 'clone', $this->_customdata->cloneid);
        $mform->setType('clone', PARAM_INT);
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        // Error if all fields are empty.
        if (empty($data['alert_condition1']) && empty($data['alert_condition2']) &&
                empty($data['alert_condition3']) && empty($data['alert_condition4']) &&
                empty($data['alert_condition5']) && empty($data['alert_condition6']) &&
                empty($data['alert_conditionmore'])) {
            $errors['alert_intro'] = get_string('invalidalert', 'forumng');
        }

        if (empty($data['alert_condition1']) && empty($data['alert_condition2']) &&
                empty($data['alert_condition3']) && empty($data['alert_condition4']) &&
                empty($data['alert_condition5']) && empty($data['alert_condition6']) &&
                !empty($data['alert_conditionmore'])) {
            $errors['alert_intro'] = get_string('invalidalertcheckbox', 'forumng');
        }

        return $errors;
    }
}
