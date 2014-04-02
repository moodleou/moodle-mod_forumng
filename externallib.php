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

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ .  '/mod_forumng.php');
require_once($CFG->libdir . '/externallib.php');

/**
 * Forum services implementation.
 *
 * @package mod_forumng
 * @copyright 2014 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_forumng_external extends external_api {
    /**
     * Gets function parameter metadata.
     *
     * @return external_function_parameters Parameter info
     */
    public static function get_forum_list_parameters() {
        return new external_function_parameters(array(
                'shortname' => new external_value(PARAM_RAW, 'Website shortname'),
                'oucu' => new external_value(PARAM_RAW, 'OU computer username')
                ));
    }

    /**
     * Returns description of method result values.
     *
     * @return external_description Description of result
     */
    public static function get_forum_list_returns() {
        return new external_multiple_structure(
                 new external_single_structure(array(
                'name' => new external_value(PARAM_RAW),
                'unread' => new external_value(PARAM_RAW)
                )));
    }

    /**
     * Get forum list for a user on a course.
     *
     * @param string $courseshortname Website shortname.
     * @param string $oucu Username.
     * @return array Forum details objects.
     */
    public static function get_forum_list($courseshortname, $oucu) {
        global $DB;

        // Validate the parameters.
        $params = self::validate_parameters(self::get_forum_list_parameters(),
                array('shortname' => $courseshortname, 'oucu' => $oucu));

        // Get course object from the shortname.
        $course = $DB->get_record('course', array('shortname' => $courseshortname));
        if ($course === false) {
            throw new moodle_exception(get_string('cannotfindcourse', 'error'));
        }

        $forumlist = array();

        // Get the user's id from the supplied username (oucu).
        $userid = $DB->get_field('user', 'id', array('username'=> $oucu));
        if ($userid === false) {
            throw new moodle_exception(get_string('cannotfinduser', 'error', $oucu));
        }

        // Get forums for the given course and user.
        $forums = mod_forumng::get_course_forums($course, $userid, mod_forumng::UNREAD_BINARY);
        $modinfo = get_fast_modinfo($course, $userid);
        // Loop through the forum instances, they are correctly ordered by sections.
        foreach ($modinfo->get_instances_of('forumng') as $cm) {
            // Only include this forum if it is visible to the user.
            if ($cm->uservisible) {
                $forum = $forums[$cm->instance];
                if ($forum->is_clone()) {
                    $forum->init_unread_from_original(mod_forumng::UNREAD_BINARY, $userid);
                }
                $forumdetails = new stdClass();
                $forumdetails->name = $forum->get_name();
                if ($forum->has_unread_discussions()) {
                    $forumdetails->unread = 'y';
                } else {
                    $forumdetails->unread = 'n';
                }

                $forumlist[] = $forumdetails;
                unset($forumdetails);
            }
        }

        return $forumlist;
    }
}
