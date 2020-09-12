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
 * Forum feature: forumngfeature_viewsubscribers
 * @package forumngfeature
 * @subpackage viewsubscribers
 * @copyright 2020 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__).'/../forumngfeature_discussion_list.php');

/**
 * This feature lets users toggle between automatically and manually
 * marking discussions read. It appears at bottom of the
 * discussion list.
 */
class forumngfeature_viewsubscribers extends forumngfeature_discussion_list {
    public function get_order() {
        return 1300;
    }

    public function display($forum) {
        if ($forum->can_view_subscribers()) {
            return parent::get_button($forum,
                get_string('viewsubscribers', 'forumngfeature_viewsubscribers'),
                'subscribers.php', false, $forum->get_link_params_array());
        }
        return '';
    }

    public function should_display($forum) {
        return $forum->can_view_subscribers();
    }
}
