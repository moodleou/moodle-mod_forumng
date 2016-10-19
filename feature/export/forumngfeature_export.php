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
 * Discussion feature: forward posts by email.
 * @package forumngfeature
 * @subpackage forward
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class forumngfeature_export extends forumngfeature_discussion {
    public function get_order() {
        global $PAGE;
        if ($PAGE->pagetype == 'mod-forumng-view') {
            return 300;
        } else {
            return 1000;
        }
    }

    public function display($discussion) {
        if (is_a($discussion, 'mod_forumng_discussion')) {
            return parent::get_button($discussion,
                    get_string('export', 'forumngfeature_export'),
                    'feature/export/export.php',
                    false, array(), '', false, true);
        } else {
            return forumngfeature_discussion_list::get_button($discussion,
                    get_string('export', 'forumngfeature_export'), 'feature/export/exportall.php',
                    false, $_GET, '', 'forumng-dselectorbutton', '', '');
        }
    }

    // Always display the Export button
    public function should_display($discussion) {
        global $CFG;

        // Check are portfolios enabled.
        if (!$CFG->enableportfolios) {
            return false;
        }

        // Check at there's at least one enabled and visible portfolio plugin.
        require_once($CFG->libdir . '/portfoliolib.php');
        $instances = portfolio_instances();
        if (empty($instances)) {
            return false;
        }

        if (is_a($discussion, 'mod_forumng')) {
            return $discussion->can_view_discussions();
        }

        return true;
    }

    public function supports_discussion_list() {
        return true;
    }
}
