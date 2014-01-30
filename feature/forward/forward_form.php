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
 * Form for email forwarding.
 * @package forumngfeature
 * @subpackage forward
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->libdir.'/formslib.php');

class mod_forumng_forward_form extends moodleform {

    public function definition() {

        global $CFG, $USER;
        $mform = $this->_form;

        // Informational paragraph
        $a = (object)array(
            'email' => $USER->email,
            'fullname' => fullname($USER, true));
        $mform->addElement('static', '', '',
            get_string('forward_info_' .
                ($this->_customdata->onlyselected ? 'selected' : 'all'),
                'forumngfeature_forward', $a));

        // Email address
        $mform->addElement('text', 'email', get_string('forward_email', 'forumngfeature_forward'),
            array('size'=>48));
        $mform->setType('email', PARAM_RAW);
        $mform->addHelpButton('email', 'forward_email', 'forumngfeature_forward');
        $mform->addRule('email', get_string('required'), 'required', null,
            'client');

        // CC me
        $mform->addElement('checkbox', 'ccme',
                get_string('forward_ccme', 'forumngfeature_forward'));

        // Email subject
        $mform->addElement('text', 'subject', get_string('subject', 'forumng'),
            array('size'=>48));
        $mform->setType('subject', PARAM_TEXT);
        $mform->addRule('subject', get_string('maximumchars', '', 255),
                'maxlength', 255, 'client');
        $mform->addRule('subject', get_string('required'), 'required', null, 'client');
        $mform->setDefault('subject', $this->_customdata->subject);

        // Email message
        $mform->addElement('editor', 'message',
            get_string('forward_intro', 'forumngfeature_forward'), array('cols'=>50, 'rows'=> 15));
        $mform->setType('message', PARAM_RAW);

        // Hidden fields
        if ($this->_customdata->postids) {
            foreach ($this->_customdata->postids as $postid) {
                $mform->addElement('hidden', 'selectp' . $postid, 1);
                $mform->setType('selectp' . $postid, PARAM_INT);
            }
        } else {
            $mform->addElement('hidden', 'all', 1);
            $mform->setType('all', PARAM_INT);
        }
        $mform->addElement('hidden', 'd', $this->_customdata->discussionid);
        $mform->setType('d', PARAM_INT);
        $mform->addElement('hidden', 'clone', $this->_customdata->cloneid);
        $mform->setType('clone', PARAM_INT);
        $mform->addElement('hidden', 'postselectform', 1);
        $mform->setType('postselectform', PARAM_INT);

        $this->add_action_buttons(true, get_string('forward', 'forumngfeature_forward'));
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (isset($data['email'])) {
            $emails = preg_split('~[; ]+~', $data['email']);
            if (count($emails) < 1) {
                $errors['email'] = get_string('invalidemails', 'forumng');
            } else {
                foreach ($emails as $email) {
                    if (!validate_email($email)) {
                        $errors['email'] = get_string('invalidemails', 'forumng');
                        break;
                    }
                }
            }
        }
        return $errors;
    }
}
