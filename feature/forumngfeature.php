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

require_once(dirname(__FILE__).'/forumngfeature_discussion.php');
require_once(dirname(__FILE__).'/forumngfeature_discussion_list.php');

/**
 * Base class for 'forum features' which are facilities that appear at the bottom of
 * a discussion or discussion list page.
 * @package mod
 * @subpackage forumng
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class forumngfeature {
    /**
     * Obtains the ID of this forum type. Default implementation cuts
     * '_forumngfeature' off the class name and returns that.
     * @return string ID
     */
    public function get_id() {
        return str_replace('forumngfeature_', '', get_class($this));
    }

    /**
     * Controls the order in which features are displayed. The lowest order
     * number is displayed first. If two items have the same order, the
     * tiebreak is the alphabetical order of their class names. Default
     * behaviour is to return order 500.
     * @return int Ordering index
     */
    public function get_order() {
        return 500;
    }

    /**
     * Compare function that orders features.
     * @param forumngfeature $a One feature
     * @param forumngfeature $b Another feature
     * @return int 1, -1, or 0 as per usual compare functions
     */
    private static function compare($a, $b) {
        $ordera = $a->get_order();
        $orderb = $b->get_order();
        if ($ordera > $orderb) {
            return 1;
        }
        if ($ordera < $orderb) {
            return -1;
        }
        $classa = get_class($a);
        $classb = get_class($b);
        if ($classa > $classb) {
            return 1;
        }
        if ($classb < $classa) {
            return -1;
        }
        return 0;
    }

    /**
     * Creates a new object of the given named type.
     * @param $feature Feature name (may be null for default)
     * @return forumngfeature Feature
     * @throws mod_forumng_exception If the name isn't valid
     */
    public static function get_new($feature) {
        // Get type name
        if (!preg_match('~^[a-z][a-z0-9_]*$~', $feature)) {
            throw new coding_exception("Invalid forum feature name: $feature");
        }
        $classname = 'forumngfeature_' . $feature;

        // Require library
        global $CFG;
        require_once(dirname(__FILE__) . "/$feature/$classname.php");

        // Create and return type object
        return new $classname;
    }

    /**
     * Returns a new object of each available type.
     * @return array Array of forumngfeature objects
     */
    public static function get_all() {
        global $CFG;
        // Get directory listing (excluding simpletest, CVS, etc)
        $list = core_component::get_plugin_list('forumngfeature');

        // Create array and put one of each object in it
        $results = array();
        foreach ($list as $name => $location) {
            $results[] = self::get_new(str_replace('forumngfeature_', '', $name));
        }

        // Sort features into order and return
        usort($results, array('forumngfeature', 'compare'));
        return $results;
    }
}
