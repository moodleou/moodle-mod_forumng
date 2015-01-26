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
 * Forum feature: flag discussions.
 *
 * @package forumngfeature_edittags
 * @copyright 2014 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../forumngfeature_discussion_list.php');

/**
 * This feature lists posts from a user. It appears at the bottom of the
 * discussion list page.
 */
class forumngfeature_edittags extends forumngfeature_discussion {

    public function should_display($discussion) {
        global $USER;

        if (is_a($discussion, 'mod_forumng_discussion')) {
            return true;
        }

        // This is a forum.
        if (!$discussion->get_tags_enabled()) {
            return false;
        } else {
            // Need to check for capabilities.
            if (has_capability('forumngfeature/edittags:managesettags', $discussion->get_context())) {
                return true;
            } else {
                $cm = $discussion->get_course_module();
                if ($discussion->get_group_mode()) {
                    $allowedgroups = groups_get_activity_allowed_groups($cm);
                    if (!empty($allowedgroups) &&
                            has_capability('forumngfeature/edittags:editsettags', $discussion->get_context())) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    public function display($discussion) {
        global $USER;
        if (is_a($discussion, 'mod_forumng')) {
            $script = 'feature/edittags/managetags.php';
            $name = get_string('settags', 'forumngfeature_edittags');
            return forumngfeature_discussion_list::get_button($discussion, $name, $script, false, array());
        } else {
            // It is a discussion so need slightly different set up.
            $script = 'feature/edittags/edittags.php';
            $name = get_string('edittags', 'forumngfeature_edittags');
            $tagout = '';
            $tags = $discussion->get_tags();
            if ($tags) {
                $tagout = html_writer::start_tag('div', array('class' => 'forum-feature-edittags'));
                $tagout .= html_writer::start_tag('strong');
                $tagout .= html_writer::tag('span', get_string('discussiontags', 'forumng') . ': ');
                $tagout .= html_writer::end_tag('strong');
                $i = 1;
                foreach ($tags as $key => $value) {
                    if (count($tags) != $i) {
                        $tagout .= $value . ', ';
                    } else {
                        $tagout .= $value . ' ';
                    }
                    $i++;
                }
                if ($this->can_edit_tags($discussion)) {
                    $tagout .= parent::get_button($discussion, $name, $script, false, array('d' => $discussion->get_id()));
                }
                $tagout .= html_writer::end_tag('div');
            } else {
                if ($this->can_edit_tags($discussion)) {
                    $tagout = html_writer::start_tag('div', array('class' => 'forum-feature-edittags'));
                    $tagout .= html_writer::start_tag('strong');
                    $tagout .= html_writer::tag('span', get_string('nodiscussionset', 'forumngfeature_edittags'));
                    $tagout .= html_writer::end_tag('strong');
                    $tagout .= parent::get_button($discussion, $name, $script, false, array('d' => $discussion->get_id()));
                    $tagout .= html_writer::end_tag('div');
                }
            }
            return $tagout;
        }
    }

    public function supports_discussion_list() {
        return true;
    }

    /**
     * Controls the order in which features are displayed. The lowest order
     * number is displayed first. If two items have the same order, the
     * tiebreak is the alphabetical order of their class names. Default
     * behaviour is to return order 500.
     * @return int Ordering index
     */
    public function get_order() {
        global $PAGE;
        if ($PAGE->pagetype == 'mod-forumng-discuss') {
            return 9999999;
        } else {
            return parent::get_order();
        }
    }

    /**
     * Checks to see whether user can tag discussions
     * @param object $discussion
     * @return boolean
     */
    public function can_edit_tags($discussion) {
        global $USER;
        // Discussion, get forum details.
        $cm = $discussion->get_course_module();
        $forum = $discussion->get_forum();

        if (!$forum->can_tag_discussion()) {
            return false;
        }
        // Check to see whether user created the discussion.
        $user = $discussion->get_poster();
        if ($USER->id == $user->id) {
            return true;
        } else {
            return parent::should_display($discussion);
        }
    }

}
