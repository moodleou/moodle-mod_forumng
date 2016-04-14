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
 * Discussion  features appear at the bottom of a discussion page.
 * @package mod
 * @subpackage forumng
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class forumngfeature_discussion extends forumngfeature {
    /**
     * Checks whether this feature should be displayed for the current user
     * in current disscussion.
     * By default, this checks the discussions's can_manage function and that
     * the discussion isn't deleted.
     * @param mod_forumng_discussion $discussion
     * @return bool True if this should display
     */
    public function should_display($discussion) {
        return $discussion->can_manage() && !$discussion->is_deleted();
    }

    /**
     * Can this discussion feature also show on discussion list page?
     * Whether it should or not is check in should_display()
     * Defaults to false.
     * @return bool true if feature supports discussion list as well
     */
    public function supports_discussion_list() {
        return false;
    }

    /**
     * @param mod_forumng_discussion $discussion
     * @return string HTML code for button
     */
    public abstract function display($discussion);

    /**
     * Convenience function for subclasses. Returns HTML code suitable to
     * use for a button in this area.
     * @param mod_forumng_discussion $discussion
     * @param string $name Text of button
     * @param string $script Name/path of .php script (relative to mod/forumng)
     * @param bool $post If true, makes the button send a POST request
     * @param array $options If included, passes these options as well as 'd'
     * @param string $extrahtml If specified, adds this HTML at end of (just
     *   inside) the form
     * @param bool $highlight If true, adds a highlight class to the form
     * @param bool $selector If true, adds a selector class to the form (indicating that the
     *   JavaScript post selector should be used)
     * @param string $extraclass Add extra css class to form
     * @return string HTML code for button
     */
    protected static function get_button($discussion, $name, $script,
        $post = false, $options = array(), $extrahtml = '', $highlight = false, $selector = false, $extraclass = '') {
        $method = $post ? 'post' : 'get';
        $optionshtml = '';
        $options['d'] = $discussion->get_id();
        if ($discussion->get_forum()->is_shared()) {
            $options['clone'] = $discussion->get_forum()->get_course_module_id();
        }
        if ($post) {
            $options['sesskey'] = sesskey();
        }
        foreach ($options as $key => $value) {
            $optionshtml .= '<input type="hidden" name="' . $key .
                '" value="' . $value . '" />';
        }

        $class = '';
        if ($highlight) {
            $class = 'forumng-highlight';
        }
        if ($selector) {
            $class .= ' forumng-selectorbutton';
        }
        $class .= $extraclass;
        if ($class !== '') {
            $class = ' class="' . trim($class) . '"';
        }
        return "<form method='$method' action='$script' $class><div>" .
            "$optionshtml<input type='submit' value='$name' />" .
            "$extrahtml</div></form>";
    }

    /**
     * Returns a new object of each available type.
     * @return array Array of forumngfeature_discussion objects
     */
    public static function get_all() {
        $all = forumngfeature::get_all();
        $results = array();
        foreach ($all as $feature) {
            if (is_a($feature, 'forumngfeature_discussion')) {
                $results[] = $feature;
            }
        }
        return $results;
    }
}
