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
 * Discussion feature: mark read (when in manual mark mode).
 * @package forumngfeature
 * @subpackage markdiscussionread
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__).'/../forumngfeature_discussion_list.php');

/**
 * This feature lists posts from a user. It appears at the bottom of the
 * discussion list page.
 */
class forumngfeature_markdiscussionread  extends forumngfeature_discussion {
    public function get_order() {
        return 90;
    }

    public function should_display($discussion) {
        return !mod_forumng::mark_read_automatically() &&
                $discussion->get_forum()->can_mark_read() &&
                $discussion->get_num_unread_posts();
    }

    public function display($discussion) {
        $params = $discussion->get_link_params_array();
        return parent::get_button($discussion,
                get_string('markdiscussionread', 'forumngfeature_markdiscussionread'),
                'markread.php', true, $params, '', false, false, 'fng-mobile-on');
    }
}
