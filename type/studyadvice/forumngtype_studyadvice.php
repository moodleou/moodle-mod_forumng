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

require_once($CFG->dirroot . '/mod/forumng/type/general/forumngtype_general.php');

/**
 * Forum type: Study advice (users can only see discussions they posted).
 * @package forumngtype
 * @subpackage studyadvice
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class forumngtype_studyadvice extends forumngtype_general {
    public function can_view_discussion($discussion, $userid=0) {
        $userid = mod_forumng_utils::get_real_userid($userid);

        // When loaded from cron, we need to 'fill' the discussion
        // (time-consuming but oh well) to get the userid
        $discussion->fill(-1);
        return $discussion->get_poster()->id == $userid
            || $discussion->get_forum()->can_view_hidden($userid);
    }

    public function has_unread_restriction() {
        return true;
    }

    public function get_unread_restriction_sql($forum, $userid=0) {
        $userid = mod_forumng_utils::get_real_userid($userid);
        // See if they're already allowed to view all discussions
        if ($forum->can_view_hidden($userid)) {
            return array('', array());
        }
        // Otherwise restrict it
        return array('fpfirst.userid = ?', array($userid));
    }

    protected function get_string($forum, $string, $a=null) {
        if ($string == 'nodiscussions') {
            return get_string(
                $forum->can_view_hidden() ? 'studyadvice_noquestions'
                    : 'studyadvice_noyourquestions', 'forumng');
        } else {
            return parent::get_string($string, $a);
        }
    }
}
