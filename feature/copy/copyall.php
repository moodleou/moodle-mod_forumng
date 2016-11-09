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
 * Copy single or multiple discussions.
 * @package forumngfeature
 * @subpackage copy
 * @copyright 2013 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../../../config.php');
require_once($CFG->dirroot . '/mod/forumng/mod_forumng.php');
require_once(dirname(__FILE__) . '/../forumngfeature_discussion_selector.php');
require_once($CFG->dirroot . '/mod/forumng/feature/copy/forumngfeature_copy_form.php');

class copy_discussion_selector extends forumngfeature_discussion_selector {
    public function get_button_name() {
        return get_string('copy_discussion', 'forumngfeature_copy');
    }

    public function get_form($forum, $all, $selected = array()) {
        if (get_user_preferences('forumng_hidecopyhelp', 0)) {
            return null;
        } else {
            foreach ($selected as $discussionid) {
                $params['selectd' . $discussionid] = $discussionid;
            }
            $params['id'] = $forum->get_course_module_id();
            $params['clone'] = optional_param('clone', 0, PARAM_INT);
            $params['postselectform'] = true;
            return new forumngfeature_copy_form('copyall.php', $params);
        }
    }

    public function get_page_name() {
        return get_string('copyall_title', 'forumngfeature_copy');
    }

    public function apply($discussion, $all, $selected, $formdata) {
        global $SESSION;

        $forum = $discussion;
        $clone  = optional_param('clone', 0, PARAM_INT);
        $groupid = null;
        // Make sure that only non-deleted and non-locked discussions are selected
        // just in case "All discussion shown" is chose.
        $selectd = array();
        foreach ($selected as $discussionid) {
            $discussion = mod_forumng_discussion::get_from_id($discussionid, $clone);
            if (!$discussion->is_deleted() && !$discussion->is_locked()) {
                $selectd[] = $discussionid;
                $dgrpid = $discussion->get_group_id();
                if ($groupid == null) {
                    $groupid = $dgrpid == null ? mod_forumng::NO_GROUPS : $dgrpid;
                } else if ($dgrpid != $groupid) {
                    // Multiple groups - set to all participants.
                    $groupid = mod_forumng::NO_GROUPS;
                }
            }
        }
        if (!empty($selectd)) {
            // Remember in session that the discussions are being copied.
            $SESSION->forumng_copyfrom = $selectd;
            $SESSION->forumng_copyfromclone = $clone;
            $SESSION->forumng_copyfromforum = $forum->get_id();
            if ($forum->get_group_mode() != NOGROUPS) {
                $SESSION->forumng_copyfromgroup = $groupid;
            } else {
                $SESSION->forumng_copyfromgroup = mod_forumng::NO_GROUPS;
            }
        }
        if (!empty($formdata->hidelater)) {
            set_user_preference('forumng_hidecopyhelp', 1);
        }
        // Redirect back to forum view page.
        redirect('../../view.php?' . $forum->get_link_params(mod_forumng::PARAM_PLAIN));
    }

    public function only_discussion_types() {
        return array('forumng-discussion-short');
    }

    public function exclude_discussion_types() {
        return array('forumng-deleted', 'forumng-locked');
    }

}

forumngfeature_discussion_selector::go(new copy_discussion_selector());
