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
 * @package forumngfeature_flagdiscussion
 * @copyright 2014 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../forumngfeature_discussion.php');

/**
 * This feature lists posts from a user. It appears at the bottom of the
 * discussion list page.
 */
class forumngfeature_flagdiscussion extends forumngfeature_discussion {

    public function get_order() {
        return 400;
    }

    public function should_display($discussion) {
        return $discussion->can_flag();
    }

    public function display($discussion) {
        $button = false;
        $script = 'feature/flagdiscussion/flag.php';
        if ($discussion->is_flagged()) {
            $flag = 0;
            $name = get_string('removeflag', 'forumngfeature_flagdiscussion');
            $button = true;
        } else {
            if (!$discussion->is_deleted()) {
                $flag = 1;
                $name = get_string('flagdiscussion', 'forumngfeature_flagdiscussion');
                $button = true;
            }
        }

        if ($button) {
            $html = parent::get_button($discussion, $name, $script, false,
                    array('d' => $discussion->get_id(), 'flag' => $flag), '', false, false, 'fng-mobile-on');
            return html_writer::div($html, "forumng_flagdis fngflg$flag");
        }
    }

}
