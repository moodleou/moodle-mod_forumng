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
 * Forum feature: toggle manual mark read.
 * @package forumngfeature
 * @subpackage manualmark
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__).'/../forumngfeature_discussion_list.php');

/**
 * This feature lets users toggle between automatically and manually
 * marking discussions read. It appears at bottom of the
 * discussion list.
 */
class forumngfeature_manualmark extends forumngfeature_discussion_list {
    public function get_order() {
        return 200;
    }

    public function should_display($forum) {
        // So long as you can view discussions, and you are not a guest,
        // you can mark them read.
        return $forum->can_mark_read();
    }

    public function display($forum) {
        $out = mod_forumng_utils::get_renderer();

        // Work out current status
        $manualmark = !mod_forumng::mark_read_automatically();
        $current = get_string(
                $manualmark ? 'manualmark_manual' : 'manualmark_auto',
                'forumngfeature_manualmark');

        // Make a help button
        $change = get_string('manualmark_change', 'forumngfeature_manualmark');
        $helpbutton = $out->help_icon('manualmark_change', 'forumngfeature_manualmark');

        // Get the button form
        $params = $forum->get_link_params_array();
        return parent::get_button($forum, $change,
                'feature/manualmark/change.php', true, $params, $helpbutton,
                'forumng-manualmark', $current . '&nbsp;',
                'forumng-button-to-link');
    }
}
