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
 * Discussion list features appear at the bottom of a forum main page.
 * @package mod
 * @subpackage forumng
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class forumngfeature_discussion_list extends forumngfeature {
    /**
     * Checks whether this feature should be displayed for the given user
     * in current forum.
     * By default, this checks the discussions's can_manage function and that
     * the discussion isn't deleted.
     * @param mod_forumng $forum Forum object
     * @param int $groupid Group id
     * @return bool True if this should display
     */
    public function should_display($forum) {
        return $forum->can_manage_discussions();
    }

    /**
     * @param mod_forumng $forum
     * @param int $groupid
     * @return string HTML code for button
     */
    public abstract function display($forum);

    /**
     * Convenience function for subclasses. Returns HTML code suitable to
     * use for a button in this area.
     * @param mod_forumng_discussion $discussion
     * @param string $name Text of button
     * @param string $script Name/path of .php script (relative to mod/forumng)
     * @param bool $post If true, makes the button send a POST request
     * @param array $options If included, passes these options as well as 'd'
     * (Must send $_GET for discussion selector buttons)
     * @param string $afterhtml If specified, adds this HTML at end of (just
     *   inside) the form
     * @param string $class Adds a class to the form (set to 'forumng-dselectorbutton'
     *  to activate discussion selector)
     * @param string $beforehtml If specified, adds this HTML at start of (just
     *   inside) the form
     * @param string $buttonclass If set, adds additional css class to the button
     * @return string HTML code for button
     */
    public static function get_button($forum, $name, $script,
            $post=false, $options=array(), $afterhtml='', $class='',
            $beforehtml='', $buttonclass='') {
        $method = $post ? 'post' : 'get';
        $optionshtml = '';
        $options['id'] = $forum->get_course_module_id(true);
        if ($forum->is_shared()) {
            $options['clone'] = $forum->get_course_module_id();
        }

        if ($post) {
            $options['sesskey'] = sesskey();
        }
        foreach ($options as $key => $value) {
            $optionshtml .= '<input type="hidden" name="' . $key .
                '" value="' . $value . '" />';
        }
        if ($class) {
            $class = " class='$class'";
        }
        if ($buttonclass) {
            $buttonclass = " class='$buttonclass'";
        }
        return "<form $class method='$method' action='$script'><div>" .
                $beforehtml .
                "$optionshtml<input type='submit' value='$name'$buttonclass/>" .
                "$afterhtml</div></form>";
    }

    /**
     * Returns a new object of each available type.
     * @return array Array of forumngfeature_discussion objects
     */
    public static function get_all() {
        $all = forumngfeature::get_all();
        $results = array();
        foreach ($all as $feature) {
            if (is_a($feature, 'forumngfeature_discussion_list') ||
                    (is_a($feature, 'forumngfeature_discussion')
                            && $feature->supports_discussion_list())) {
                $results[] = $feature;
            }
        }
        return $results;
    }
}
