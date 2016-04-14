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
 * Forum feature: show all posts organised by user.
 * @package forumngfeature
 * @subpackage userposts
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__).'/../forumngfeature_discussion_list.php');

/**
 * This feature lists posts from a user. It appears at the bottom of the
 * discussion list page.
 */
class forumngfeature_userposts extends forumngfeature_discussion_list {
    public function get_order() {
        return 100;
    }

    public function should_display($forum) {
        global $USER;
        if (!($forum->is_shared() || $forum->is_clone() || isguestuser($USER->id)) &&
            (has_capability('mod/forumng:startdiscussion', $forum->get_context())
            || has_capability('mod/forumng:replypost', $forum->get_context())
            || has_capability('forumngfeature/userposts:view', $forum->get_context()))) {
            if ($forum->get_group_mode() == VISIBLEGROUPS &&
                    !has_capability('forumngfeature/userposts:view', $forum->get_context())) {
                // In visible group mode, check student is in group - if not they can't see button.
                $groupid = $forum->get_activity_group($forum->get_course_module());
                if ($groupid != null && !$forum->can_access_group($groupid, true)) {
                    return false;
                }
            }
            return true;
        } else {
            return false;
        }
    }

    public function display($forum) {
        if (has_capability('forumngfeature/userposts:view', $forum->get_context())) {
            $name = get_string('viewpostsbyuser', 'forumngfeature_userposts');
            $script = 'feature/userposts/list.php';
            return parent::get_button($forum, $name, $script, false, array(), '', 'fng-mobile-on');
        } else {
            $name = get_string('viewownposts', 'forumngfeature_userposts');
            $script = 'feature/userposts/user.php';
            return parent::get_button($forum, $name, $script, false,
                    array('user' => mod_forumng_utils::get_real_userid(), 'fng-mobile-on'));
        }
    }
}
