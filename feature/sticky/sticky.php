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
 * Mark as sticky scripts. This uses the discussion selector infrastructure to
 * handle the situation when discussions are being selected for marking.
 * @package forumngfeature
 * @subpackage sticky
 * @copyright 2013 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../forumngfeature_discussion_selector.php');

class sticky_on_selector extends forumngfeature_discussion_selector {

    public function get_button_name() {
        return get_string('sticky_on', 'forumngfeature_sticky');
    }

    public function apply($discussion, $all, $selected, $formdata) {
        global $DB, $CFG;
        if ($discussion->can_manage_discussions()) {
            foreach ($selected as $postid) {
                $DB->set_field('forumng_discussions', 'sticky', 1,
                        array('id' => $postid, 'deleted' => 0));
            }
            // Redirect to the view page.
            redirect($discussion->get_url(mod_forumng::PARAM_PLAIN));
        }
    }

    public function only_discussion_types() {
        return array('forumng-discussion-short');
    }

    public function exclude_discussion_types() {
        return array('forumng-sticky', 'forumng-deleted');
    }
}

class sticky_off_selector extends forumngfeature_discussion_selector {
    public function get_button_name() {
        return get_string('sticky_off', 'forumngfeature_sticky');
    }

    public function apply($discussion, $all, $selected, $formdata) {
        global $DB, $CFG;
        if ($discussion->can_manage_discussions()) {
            foreach ($selected as $postid) {
                $DB->set_field('forumng_discussions', 'sticky', 0,
                        array('id' => $postid, 'deleted' => 0));
            }
            // Redirect to the view page.
            redirect($discussion->get_url(mod_forumng::PARAM_PLAIN));
        }
    }

    public function only_discussion_types() {
        return array('forumng-sticky');
    }

    public function exclude_discussion_types() {
        return array('forumng-deleted');
    }
}

if ($buttonselection = optional_param('sticky', '', PARAM_INT)) {
    forumngfeature_discussion_selector::go(new sticky_on_selector());
}
if ($buttonselection = optional_param('normal', '', PARAM_INT)) {
    forumngfeature_discussion_selector::go(new sticky_off_selector());
}
