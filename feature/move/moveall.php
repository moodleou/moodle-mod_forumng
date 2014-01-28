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
 * Move single or multiple discussions to target forum, or display group form if needed.
 * @package forumngfeature
 * @subpackage export
 * @copyright 2013 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../../../config.php');
require_once($CFG->dirroot . '/mod/forumng/mod_forumng.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/../forumngfeature_discussion_selector.php');

class move_discussion_selector extends forumngfeature_discussion_selector {
    public function get_button_name() {
        return get_string('move', 'forumngfeature_move');
    }

    public function get_form($forum, $all, $selected = array()) {
        global $CFG, $PAGE;

        foreach ($selected as $discussionid) {
            $params['selectd'.$discussionid] = $discussionid;
        }
        $params['id'] = $forum->get_course_module_id();
        $params['clone'] = optional_param('clone', 0, PARAM_INT);

        $group = mod_forumng::get_activity_group($forum->get_course_module());
        if (empty($group)) {
            $group = 0;
        }

        $params['group'] = $group;
        $params['multigroups'] = optional_param('multigroups', 0, PARAM_INT);

        $customdata = array('params' => $params, 'forum' => $forum);

        return new mod_forumng_moveall_form('movealltogroups.php', $customdata);
    }

    public function get_page_name() {
        return get_string('movealltitle', 'forumngfeature_move');
    }

    public function apply($discussion, $all, $selected, $formdata) {

    }

    public function only_discussion_types() {
        return array('forumng-discussion-short');
    }

    public function exclude_discussion_types() {
        return array('forumng-deleted');
    }

}

forumngfeature_discussion_selector::go(new move_discussion_selector());
