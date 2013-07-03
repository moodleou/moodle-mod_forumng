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
 * Discussion feature: Lock discussion.
 * @package forumngfeature
 * @subpackage lock
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class forumngfeature_lock extends forumngfeature_discussion {
    public function get_order() {
        global $PAGE;
        if ($PAGE->pagetype == 'mod-forumng-view') {
            return 500;
        } else {
            return 200;
        }
    }

    public function display($discussion) {
        global $PAGE;
        if ($PAGE->pagetype == 'mod-forumng-discuss') {
            if (!$discussion->is_locked()) {
                $button = parent::get_button($discussion,
                        get_string('lock', 'forumngfeature_lock'), 'editpost.php', false,
                        array('lock' => 1));
            } else {
                $button = parent::get_button($discussion,
                        get_string('unlock', 'forumngfeature_lock'),
                        'feature/lock/unlock.php');
            }
        } else {
            // Main page lock button.
            $params['exclude'] = 'forumng-deleted,forumng-locked';
            $lock_get = array_merge($params, $_GET);
            $button = forumngfeature_discussion_list::get_button(
                    $discussion, get_string('lockdis', 'forumngfeature_lock'),
                    'feature/lock/lockall.php', false, $lock_get, '', 'forumng-dselectorbutton');
        }
        return $button;
    }

    public function supports_discussion_list() {
        return true;
    }

    public function should_display($discussion) {
        if (is_a($discussion, 'mod_forumng_discussion')) {
            return parent::should_display($discussion);
        }
        if (is_a($discussion, 'mod_forumng')) {
            return $discussion->can_manage_discussions(0);
        }
        return true;
    }
}
