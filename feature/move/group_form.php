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

    public function definition() {

        global $CFG, $USER;
        $mform = $this->_form;
        $forum = $this->_customdata->targetforum;

        // Informational paragraph.
        $mform->addElement('static', '', '',
            get_string('move_group_info', 'forumngfeature_move', $forum->get_name()));

        // Get list of allowed groups.
        $groups = $this->_customdata->groups;
        $mform->addElement('select', 'group', get_string('group'), $groups);
        reset($groups);
        $mform->setDefault('group', key($groups));

        // Hidden fields.
        $mform->addElement('hidden', 'd', $this->_customdata->discussionid);
        $mform->setType('d', PARAM_INT);
        $mform->addElement('hidden', 'clone', $this->_customdata->cloneid);
        $mform->setType('clone', PARAM_INT);
        $mform->addElement('hidden', 'target', $forum->get_course_module_id());
        $mform->setType('target', PARAM_INT);
        $this->add_action_buttons(true, get_string('move'));

    }
}


class mod_forumng_moveall_form extends moodleform {

    public function definition() {
        global $CFG, $USER;

        $mform = $this->_form;
        $params = $this->_customdata['params'];

        // Get current forum.
        $forum = $this->_customdata['forum'];
        // Get array of forums excluding current forum.
        $forums = get_other_course_forums($forum);

        $mform->addElement('select', 'forum', get_string('movediscussionsto', 'forumngfeature_move'), $forums);

        // Hidden fields.
        foreach ($params as $param => $value) {
            $mform->addElement('hidden', $param, $value);
            $mform->setType($param, PARAM_INT);
        }

        $this->add_action_buttons(true, get_string('movediscussions', 'forumngfeature_move'));

    }
}

class mod_forumng_moveall_groups_form extends moodleform {

    public function definition() {
        global $CFG, $USER;
        $mform = $this->_form;

        $pageparams = $this->_customdata['params'];
        $target = $this->_customdata['params']['target'];
        $forumngid = $this->_customdata['params']['id'];
        $cloneid = $this->_customdata['params']['clone'];
        $multigroups = $this->_customdata['params']['multigroups'];
        $targetgroupmode = $this->_customdata['params']['targetgroupmode'];

        $targetforum = $this->_customdata['targetforum'];

        $pageparams = $this->_customdata['params'];
        foreach ($pageparams as $param => $value) {
            $mform->addElement('hidden', $param, $value);
            $mform->setType($param, PARAM_INT);
        }

        $selectedids = array();
        foreach ($pageparams as $field => $value) {
            $matches = array();
            if (!is_array($value) && (string)$value !== '0' &&
                    preg_match('~^selectd([0-9]+)$~', $field, $matches)) {
                $selectedids[] = ($matches[1]);
            }
        }

        // Get list of allowed groups.
        $options = array();
        // Check to see whether target forum uses group mode.
        if ($targetgroupmode) {
            $options = get_allowed_groups($targetforum, false);
        }
        // Informational paragraph.
        $mform->addElement('static', '', '',
                get_string('move_discussions_group_info', 'forumngfeature_move', $targetforum->get_name()));
        // Get group from user.
        $mform->addElement('select', 'chosengroup', get_string('group'), $options);
        reset($options);
        $mform->setDefault('group', key($options));
        $this->add_action_buttons(true, get_string('movediscussions', 'forumngfeature_move'));

    }

}
