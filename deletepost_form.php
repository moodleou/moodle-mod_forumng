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
 * Form for sending an email to the author of a post when deleting
 * @package mod
 * @subpackage forumng
 * @copyright 2012 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_forumng_deletepost_form extends moodleform {

    public function definition() {
        $mform =& $this->_form;

        $expand = $this->_customdata['expand'];

        // Header.
        $mform->addElement('header', 'general', get_string('deleteandemail', 'forumng'));

        // Message box.
        $mform->addElement('editor', 'message',
                get_string('emailmessage', 'forumng'), array('size'=>'64', 'id' => 'id_forumng_delete_msg'));
        $mform->setType('message', PARAM_RAW);
        $mform->addRule('message', null, 'required', null, 'client');

        // Send a copy to self.
        $mform->addElement('checkbox', 'copyself', get_string('copytoself', 'forumng'));

        // Keep the expand status.
        $mform->addElement('hidden', 'expand', $expand);

        // add some buttons
        $this->add_action_buttons(true, get_string('sendanddelete', 'forumng'));

    }

}
