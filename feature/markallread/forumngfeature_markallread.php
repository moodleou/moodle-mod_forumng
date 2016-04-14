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
 * Forum feature: mark all discussions read.
 * @package forumngfeature
 * @subpackage markallread
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__).'/../forumngfeature_discussion_list.php');

/**
 * This feature lists posts from a user. It appears at the bottom of the
 * discussion list page.
 */
class forumngfeature_markallread  extends forumngfeature_discussion_list {
    public function get_order() {
        return 100;
    }

    public function should_display($forum) {
        return $forum->can_mark_read();
    }

    public function display($forum) {
        $params = $forum->get_link_params_array();
        if ($forum->get_group_mode()) {
            $params['group'] = mod_forumng::get_activity_group(
                    $forum->get_course_module());
        }
        return parent::get_button($forum, get_string('markallread', 'forumngfeature_markallread'),
                'markread.php', true, $params, '', 'fng-mobile-on');
    }
}
