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
 * Forum feature: toggle sticky.
 * @package forumngfeature
 * @subpackage sticky
 * @copyright 2013 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__).'/../forumngfeature_discussion_list.php');

/**
 * This feature lets users toggle between automatically and manually
 * marking discussions read. It appears at bottom of the
 * discussion list.
 */
class forumngfeature_sticky extends forumngfeature_discussion_list {
    public function get_order() {
            return 350;
    }

    public function display($forum) {
        $params = array();
        $params['sticky'] = 100;
        $params['normal'] = 0;
        $params['exclude'] = 'forumng-deleted,forumng-sticky';
        $sticky_get = array_merge($params, $_GET);
        $stickyonbutton = parent::get_button(
                $forum,
                get_string('sticky_on', 'forumngfeature_sticky'),
                'feature/sticky/sticky.php',
                false,
                $sticky_get,
                '',
                'forumng-dselectorbutton');
        unset($params);
        $params['sticky'] = 0;
        $params['normal'] = 200;
        $params['include'] = 'forumng-sticky';
        $params['exclude'] = 'forumng-deleted';
        $normal_get = array_merge($params, $_GET);
        $stickyoffbutton = parent::get_button(
                $forum,
                get_string('sticky_off', 'forumngfeature_sticky'),
                'feature/sticky/sticky.php',
                false,
                $normal_get,
                '',
                'forumng-dselectorbutton');
        return $stickyonbutton . $stickyoffbutton;
    }
}
