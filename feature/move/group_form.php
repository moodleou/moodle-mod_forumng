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
 * Form to select a target group when moving to a group forum and it doesn't
 * already have a valid group.
 * @package forumngfeature
 * @subpackage move
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->libdir.'/formslib.php');

class mod_forumng_group_form extends moodleform {

    function definition() {

        global $CFG, $USER;
        $mform = $this->_form;
        $forum = $this->_customdata->targetforum;

        // Informational paragraph
        $mform->addElement('static', '', '',
            get_string('move_group_info', 'forumngfeature_move', $forum->get_name()));

        // Get list of allowed groups
        $groups = $this->_customdata->groups;
        $mform->addElement('select', 'group', get_string('group'), $groups);
        reset($groups);
        $mform->setDefault('group', key($groups));

        // Hidden fields
        $mform->addElement('hidden', 'd', $this->_customdata->discussionid);
        $mform->addElement('hidden', 'clone', $this->_customdata->cloneid);
        $mform->addElement('hidden', 'target', $forum->get_course_module_id());

        $this->add_action_buttons(true, get_string('move'));
    }
}
