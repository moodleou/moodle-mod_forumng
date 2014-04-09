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
 * Forum feature: forum usage stats.
 * @package forumngfeature_usage
 * @copyright 2013 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__).'/../forumngfeature_discussion_list.php');

class forumngfeature_usage extends forumngfeature_discussion_list {
    public function get_order() {
        return 400;
    }

    public function should_display($forum) {
        if (has_capability('forumngfeature/usage:view', $forum->get_context())) {
            return true;
        } else {
            return false;
        }
    }

    public function display($forum) {
        $name = get_string('button', 'forumngfeature_usage');
        $script = 'feature/usage/usage.php';
        return parent::get_button($forum, $name, $script);
    }
}
