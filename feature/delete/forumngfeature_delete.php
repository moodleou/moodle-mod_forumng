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
 * Discussion feature: Delete.
 * @package forumngfeature
 * @subpackage delete
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class forumngfeature_delete extends forumngfeature_discussion {
    public function get_order() {
        return 400;
    }

    public function should_display($discussion) {
        // Display even if deleted.
        // Display to those without capability if:
        // First post author, no replies + they can edit first post.
        return $discussion->can_delete($whynot);
    }

    public function display($discussion) {
        global $USER;

        $childposts = $discussion->get_root_post()->has_children();
        $creator = $discussion->get_poster();
        $deleted = $discussion->is_deleted();
        $extrahtml = '';
        if (!$deleted && ($childposts || $creator->id != $USER->id)) {
            $extrahtml = '<div class="forumng_deldiscussion"></div>';
        }
        return parent::get_button($discussion,
            $discussion->is_deleted() ? get_string('undelete', 'forumng')
                : get_string('delete'),
            'feature/delete/delete.php', false,
            array('delete' => ($discussion->is_deleted() ? 0 : 1)), $extrahtml);
    }
}
