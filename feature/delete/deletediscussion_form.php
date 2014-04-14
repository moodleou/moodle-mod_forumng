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
 * Form for sending an email to the author of a discussion and contributors when deleting
 * @package mod
 * @subpackage forumng
 * @copyright 2014 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_forumng_deletediscussion_form extends moodleform {

    public function definition() {
        $mform =& $this->_form;

        // Header.
        $mform->addElement('header', 'general', get_string('deleteandemail', 'forumng'));

        // Message box.
        $mform->addElement('editor', 'message',
                get_string('emailmessage', 'forumng'), array('size' => '64', 'id' => 'id_forumng_delete_msg'));
        $mform->setType('message', PARAM_RAW);
        $mform->addRule('message', null, 'required', null, 'client');

        // Send a copy to self.
        $mform->addElement('checkbox', 'copyself', get_string('copytoself', 'forumng'));

        // Adding optional text field 'Email address of other recipients'.
        $mform->addElement('text', 'emailadd', get_string('extra_emails', 'forumng'),
                array('size' => '48'));
        $mform->addHelpButton('emailadd', 'extra_emails', 'forumng');
        $mform->setType('emailadd', PARAM_RAW);

        $contributors = $this->_customdata['contributors'];

        if ($contributors) {
            // Notify other contributors checkbox.
            $mform->addElement('checkbox', 'notifycontributors', get_string('notifycontributors', 'forumngfeature_delete'));
            $mform->addHelpButton('notifycontributors', 'notifycontributors', 'forumngfeature_delete');

            // Notify contributors message box.
            $mform->addElement('editor', 'notifymessage',
                    get_string('emailmessage', 'forumng'), array('size' => '64', 'id' => 'id_forumng_delete_notify_msg'));
            $mform->setType('notifymessage', PARAM_RAW);
        }
        // Hidden fields.
        foreach ($this->_customdata as $param => $value) {
            $mform->addElement('hidden', $param, $value);
            $mform->setType($param, PARAM_INT);
        }

        // Add some buttons.
        $this->add_action_buttons(true, get_string('sendanddelete', 'forumng'));

    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (!empty($data['emailadd'])) {
            $emails = preg_split('~[; ]+~', $data['emailadd']);
            if (count($emails) < 1) {
                $errors['emailadd'] = get_string('invalidemails', 'forumng');
            } else {
                foreach ($emails as $email) {
                    if (!validate_email($email)) {
                        $errors['emailadd'] = get_string('invalidemails', 'forumng');
                        break;
                    }
                }
            }
        }
        if (!empty($data['notifycontributors']) && empty($data['notifymessage']['text'])) {
            $errors['notifymessage'] = get_string('required');
        }
        return $errors;
    }

}
