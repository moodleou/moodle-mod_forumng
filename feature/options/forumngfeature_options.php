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
 * Discussion feature: Show options form. (Note: Actual form is just a variant
 * of forum core editpost.php.)
 * @package forumngfeature
 * @subpackage options
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class forumngfeature_options extends forumngfeature_discussion {
    public function get_order() {
        return 100;
    }

    public function display($discussion) {
        return parent::get_button($discussion,
            get_string('discussionoptions', 'forumng'), 'editpost.php', false, array(), '', false,
                false, 'fng-mobile-on');
    }
}
