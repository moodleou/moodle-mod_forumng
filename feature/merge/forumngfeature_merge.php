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
 * Discussion feature: merge discussion.
 * @package forumngfeature
 * @subpackage merge
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class forumngfeature_merge extends forumngfeature_discussion {
    public function get_order() {
        return 350;
    }

    public function should_display($discussion) {
        global $SESSION;
        return has_capability('mod/forumng:splitdiscussions',
            $discussion->get_forum()->get_context())
            && $discussion->can_write_to_group()
            && !$discussion->is_deleted()
            && !$discussion->is_locked();
    }

    public function display($discussion) {
        global $SESSION, $PAGE;

        $discussionid = 0;
        if (isset($SESSION->forumng_mergefrom)) {
            $discussionid = $SESSION->forumng_mergefrom->discussionid;
        }

        $PAGE->requires->js_call_amd('mod_forumng/mergediscussion', 'init', [['discussionid' => $discussionid]]);

        if (isset($SESSION->forumng_mergefrom)) {
            if ($SESSION->forumng_mergefrom->discussionid == $discussion->get_id() ||
                    $SESSION->forumng_mergefrom->forumid != $discussion->get_forum()->get_id()) {
                $html = html_writer::tag('div', get_string('cannotmergehere', 'forumngfeature_merge'),
                    array('class' => 'forumngfeature-merge-extrahtml'));
                return parent::get_button($discussion,
                        get_string('cancelmerge', 'forumngfeature_merge'),
                        'feature/merge/merge.php', true, array('stage'=>2, 'cancel'=>1),
                        ' ' . $html, true);

            } else {
                return parent::get_button($discussion,
                    get_string('mergehere', 'forumngfeature_merge'), 'feature/merge/merge.php',
                    true, array('stage'=>2),
                    '<input type="submit" name="cancel" value="' .
                        get_string('cancel') . '" />', true);
            }
        } else {
            $script = !get_user_preferences('forumng_hidemergehelp', 0) ? '' : 'feature/merge/merge.php';
            return parent::get_button($discussion,
                get_string('merge', 'forumngfeature_merge'), $script, false, [], '', false, false, 'merge-form');
        }

    }
}
