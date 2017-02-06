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
 * Discussion feature: move to another forum.
 * @package forumngfeature
 * @subpackage move
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class forumngfeature_move extends forumngfeature_discussion {
    public function get_order() {
        global $PAGE;
        if ($PAGE->pagetype == 'mod-forumng-view') {
            return 300;
        } else {
            return 1000;
        }
    }

    public function should_display($discussion) {
        if (is_a($discussion, 'mod_forumng_discussion')) {
            // Check they are allowed to move discussions, discussion not deleted.
            if (!has_capability('mod/forumng:movediscussions',
                $discussion->get_forum()->get_context())
                || $discussion->is_deleted()
                || !$discussion->can_write_to_group()) {
                    return false;
            }
        } else {
            // Test to see if a forum.
            if (is_a($discussion, 'mod_forumng')) {
                // Check they are allowed to move discussions.
                if (!has_capability('mod/forumng:movediscussions',
                        $discussion->get_context())) {
                    return false;
                }
            }
        }

        // Otherwise always 'display' it (may display blank if there aren't
        // any target forums, though).
        return true;
    }

    public function display($discussion) {
        require_once(dirname(__FILE__) . '/lib.php');
        if (is_a($discussion, 'mod_forumng_discussion')) {
            // Obtain list of other forums in this course where the user has the
            // 'move discussion' feature.
            $course = $discussion->get_forum()->get_course();
            $modinfo = get_fast_modinfo($course);
            $results = array();
            foreach ($modinfo->instances['forumng'] as $other) {
                // Don't let user move discussion to its current forum.
                if ($other->instance == $discussion->get_forum()->get_id() ||
                    $other->id == $discussion->get_forum()->get_course_module_id()) {
                    continue;
                }
                $othercontext = context_module::instance($other->id);
                if (has_capability('mod/forumng:movediscussions', $othercontext) && $other->uservisible) {
                    $results[$other->id] = $other->name;
                }
            }
            if (count($results) == 0) {
                return '';
            }

            // Make list alphabetical.
            uasort($results, 'sort_ignore_case');

            // Build select using the list.
            $out = mod_forumng_utils::get_renderer();
            $select = html_writer::select($results, 'target', '',
                array('' => get_string('movethisdiscussionto', 'forumngfeature_move')));
            return '<form method="post" action="feature/move/move.php"><div>' .
                $discussion->get_link_params(mod_forumng::PARAM_FORM) .
                $select . '<input class="forumng-zero-disable" ' .
                'type="submit" value="' .get_string('move') . '" /></div></form>';
        } else {
            // Display button.
            $params['exclude'] = 'forumng-deleted';
            $excludedget = array_merge($params, $_GET);

            if (is_a($discussion, 'mod_forumng')) {
                return forumngfeature_discussion_list::get_button($discussion,
                    get_string('move', 'forumngfeature_move'), 'feature/move/moveall.php',
                    false, $excludedget, '', 'forumng-dselectorbutton', '', '');
            }
        }

    }

    public function supports_discussion_list() {
        return true;
    }

}