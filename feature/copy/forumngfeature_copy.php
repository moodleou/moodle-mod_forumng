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
 * Discussion feature: Copy.
 * @package forumngfeature
 * @subpackage copy
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class forumngfeature_copy extends forumngfeature_discussion {
    public function get_order() {
        global $PAGE;
        if ($PAGE->pagetype == 'mod-forumng-view') {
            return 320;
        } else {
            return 360;
        }
    }

    public function should_display($discussion) {
        global $SESSION;
        if (is_a($discussion, 'mod_forumng_discussion')) {
            return has_capability('mod/forumng:copydiscussion',
                $discussion->get_forum()->get_context()) && (!isset($SESSION->forumng_copyfrom) ||
                $SESSION->forumng_copyfrom != $discussion->get_id());
        } else if (is_a($discussion, 'mod_forumng')) {
            return has_capability('mod/forumng:copydiscussion',
                $discussion->get_context()) && (!isset($SESSION->forumng_copyfrom));
        }
    }

    public function display($discussion) {
        if (is_a($discussion, 'mod_forumng_discussion')) {
            return parent::get_button($discussion,
                get_string('copy_discussion', 'forumngfeature_copy'), 'feature/copy/copy.php');
        } else if (is_a($discussion, 'mod_forumng')) {
            $params['exclude'] = 'forumng-deleted,forumng-locked';
            $excludedget = array_merge($params, $_GET);
            return forumngfeature_discussion_list::get_button(
                    $discussion,
                    get_string('copy_discussion', 'forumngfeature_copy'),
                    'feature/copy/copyall.php',
                    false,
                    $excludedget,
                    '',
                    'forumng-dselectorbutton');
        }
    }

    public function supports_discussion_list() {
        return true;
    }

}
