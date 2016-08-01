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
 * Base class for forum types.
 *
 * A forum type can control display of the view and discussion pages. (It
 * cannot control display of the index page because that is shared by all
 * forums so has no type!)
 *
 * For example, this could be used to add extra text or features.
 *
 * It can also control whether users can post or reply in the forum. These
 * restrictions are in addition to any applied by the normal capability system.
 * @package mod
 * @subpackage forumng
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class forumngtype {
    /**
     * Displays the view page (usually showing a list of discussions).
     * @param mod_forumng $forum Forum
     * @param int $groupid Group ID
     */
    abstract public function print_view_page($forum, $groupid);

    /**
     * Displays the discussion page.
     * @param mod_forumng_discussion $discussion Discussion
     */
    abstract public function print_discussion_page($discussion);

    /**
     * Checks if user is allowed to post to this forum (if capabilities permit).
     * Default implementation just returns true.
     * @param mod_forumng $forum Forum
     * @param string &$whynot Output parameter - set to a language string name
     *   to give a specific reason for failure that is displayed on view
     *   screen where the button would be (otherwise will not report one)
     * @param int $userid User ID or 0 for current user
     * @return bool False to prevent user posting; true to allow it subject
     *   to normal restrictions
     */
    public function can_post($forum, &$whynot, $userid=0) {
        return true;
    }

    /**
     * Checks if user is allowed to view a discussion on this forum (if
     * capabilities/groups permit). Default implementation just returns true.
     *
     * Note that implementing this function usually also requires implementation
     * of get_unread_restriction_sql.
     *
     * @param mod_forumng_discussion $discussion Discussion
     * @param int $userid User ID or 0 for current user
     * @return bool False to prevent user viewing; true to allow it subject
     *   to normal restrictions
     */
    public function can_view_discussion($discussion, $userid=0) {
        return true;
    }

    /**
     * Forum types can change the way unread status is calculated. If this
     * is done, then extra SQL queries will be required when accessing forum
     * unread data on courses that include forums of this type. The function
     * get_unread_restriction_sql must be implemented.
     * @return bool True if this forum changes the way 'unread' status is
     *   calculated
     */
    public function has_unread_restriction() {
        return false;
    }

    /**
     * Obtains additional SQL used to restrict the list of discussions counted
     * in the 'unread' queries.
     *
     * Valid aliases: 'fd' (forumng_discussions), 'fplast' (forumng_post; most
     *   recent post in discussion), 'fpfirst' (forumng_post; first post in
     *   discussion), f (forummg), cm (course_modules), c (course).
     * @param mod_forumng Forum object
     * @param int $userid
     * @return array Array with 2 elements: SQL code and array of parameters
     */
    public function get_unread_restriction_sql($forum, $userid=0) {
        return array('', array());
    }

    /**
     * Checks if user is allowed to reply to a post on this forum (if
     * capabilities permit). Default implementation just returns true.
     * @param mod_forumng_post $inreplyto Post being replied to
     * @param int $userid User ID or 0 for current user
     * @return bool False to prevent user posting; true to allow it subject
     *   to normal restrictions
     */
    public function can_reply($inreplyto, $userid=0) {
        return true;
    }

    /**
     * Checks whether a discussion feature is allowed for this forum type.
     * Default just returns true. This could be used to veto selected features.
     * @param mod_forumng_discussion $discussion
     * @param forumngfeature_discussion $feature
     * @return bool True to allow
     */
    public function allow_forumngfeature_discussion($discussion, $feature) {
        return true;
    }


    // Type plugin basics
    /* ////////////////// */

    /**
     * Obtains the ID of this forum type. Default implementation cuts
     * '_forumngtype' off the class name and returns that.
     * @return string ID
     */
    public function get_id() {
        return str_replace('forumngtype_', '', get_class($this));
    }

    /**
     * Obtains the display name of this forum type. Default implementation
     * gets string type_(whatever) from forumng language file.
     * @return string Name
     */
    public function get_name() {
        return get_string('pluginname', 'forumngtype_' . $this->get_id());
    }

    /**
     * Creates a new object of the given named type.
     * @param $type Type name (may be null for default)
     * @return forumngtype Type
     * @throws mod_forumng_exception If the name isn't valid
     */
    public static function get_new($type) {
        // Get type name
        if (!$type) {
            $type = 'general';
        }
        if (!preg_match('~^[a-z][a-z0-9_]*$~', $type)) {
            throw new coding_exception("Invalid forum type name: $type");
        }
        $classname = 'forumngtype_' . $type;

        // Require library
        global $CFG;
        require_once(dirname(__FILE__) . "/$type/$classname.php");

        // Create and return type object
        return new $classname;
    }

    /**
     * Returns a new object of each available type.
     * @return array Array of forumngtype objects
     */
    public static function get_all() {
        global $CFG;
        // Get directory listing (excluding simpletest, CVS, etc)
        $list = core_component::get_plugin_list('forumngtype');

        $results = array();
        foreach ($list as $name => $location) {
            $results[] = self::get_new(str_replace('forumngtype_', '', $name));
        }
        return $results;
    }

    /**
     * Provided so that forum types can override certain language strings.
     * @param mod_forumng $forum Forum object
     * @param string $string Language string id (note: must be from forumng
     *   language file)
     * @param mixed $a Value or null
     * @return string Evaluated string
     */
    protected function get_string($forum, $string, $a=null) {
        return get_string($string, 'forumng', $a);
    }

    /**
     * @return bool True if the user is allowed to select this type, false
     *   if it's only used internally
     */
    public function is_user_selectable() {
        return true;
    }
}
