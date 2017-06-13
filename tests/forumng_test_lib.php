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
 * This is a lib/helper class for forumng tests, containing useful setup functions
 * Include + Extend this class in your test rather than advance_testcase
 *
 * @package    mod_forumng
 * @copyright  2014 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

abstract class forumng_test_lib extends advanced_testcase {
    /*
     These functions require us to create database entries and/or grab objects to make it possible to test the
    many permuations required for forumng.

    */

    /**
     * Creates a new user and enrols them on course with role specified (optional)
     * @param string $rolename role shortname if enrolment required
     * @param int $courseid course id to enrol on
     * @return stdClass user
     */
    public function get_new_user($rolename = null, $courseid = null) {
        global $DB;
        $user = $this->getDataGenerator()->create_user();

        // Assign role if required.
        if ($rolename && $courseid) {
            $role = $DB->get_record('role', array('shortname' => $rolename));
            $this->getDataGenerator()->enrol_user($user->id, $courseid, $role->id);
        } else if ($rolename) {
            // Assign role at system level.
            $role = $DB->get_record('role', array('shortname' => $rolename));
            $this->getDataGenerator()->role_assign($role->id, $user->id);
        }

        return $user;
    }

    public function get_new_course($shortname = null) {
        $course = new stdClass();
        $course->fullname = 'Anonymous test course';
        $course->shortname = $shortname ? $shortname : 'ANON_' . random_string(3);
        return $this->getDataGenerator()->create_course($course);
    }

    public function get_new_group($courseid) {
        $group = new stdClass();
        $group->courseid = $courseid;
        $group->name = 'test group';
        return $this->getDataGenerator()->create_group($group);
    }

    public function get_new_group_member($groupid, $userid) {
        $member = new stdClass();
        $member->groupid = $groupid;
        $member->userid = $userid;
        return $this->getDataGenerator()->create_group_member($member);
    }

    /**
     * Create a new grouping and add it into a course
     *
     * @param int $courseid
     * @return stdClass
     */
    public function get_new_grouping($courseid) {
        $grouping = new stdClass();
        $grouping->courseid = $courseid;
        return $this->getDataGenerator()->create_grouping($grouping);
    }

    /**
     * Add a group to a grouping
     *
     * @param int $groupingid
     * @param int $groupid
     * @return bool
     */
    public function get_new_grouping_group($groupingid, $groupid) {
        $groupmember = new stdClass();
        $groupmember->groupid = $groupid;
        $groupmember->groupingid = $groupingid;
        return $this->getDataGenerator()->create_grouping_group($groupmember);
    }

    /**
     * Create new forumng instance using generator, returns instance record + cm
     * @param int $courseid
     * @param array $options
     * @return mod_forumng
     */
    public function get_new_forumng($courseid, $options = null) {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/forumng/mod_forumng.php');
        if (is_null($options)) {
            $options = array();
        } else {
            $options = (array) $options;
        }
        $options['course'] = $courseid;

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_forumng');
        $forum = $generator->create_instance((object) $options);

        $this->assertNotEmpty($forum);

        $cm = get_coursemodule_from_instance('forumng', $forum->id);
        $this->assertNotEmpty($cm);

        $clone = mod_forumng::CLONE_DIRECT;
        if (!empty($options['usesharedgroup']['originalcmidnumber'])) {
            // Clone forum - swap forum id into clone and find original.
            if ($origcm = $DB->get_record('course_modules', array('idnumber' => $options['usesharedgroup']['originalcmidnumber'],
                    'module' => $DB->get_field('modules', 'id', array('name' => 'forumng'))))) {
                $clone = $cm->id;
                $forum->id = $DB->get_field('forumng', 'id', array('id' => $origcm->instance), MUST_EXIST);
            }
        }
        $forum = mod_forumng::get_from_id($forum->id, $clone, true);
        return $forum;
    }

    /**
     * Create a discussion using the generator, returns discussion object
     * @param mod_forumng $forum
     * @param array $options Must contain userid
     * @return mod_forumng_discussion
     */
    public function get_new_discussion(mod_forumng $forum, array $options) {
        $options['forum'] = $forum->get_id();
        $options['course'] = $forum->get_course_id();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_forumng');
        $dis = $generator->create_discussion($options);
        return mod_forumng_discussion::get_from_id($dis[0], mod_forumng::CLONE_DIRECT);
    }

    /**
     * Creates a forum with 6 timed discussions
     * 1 - start in past
     * 2 - start in future
     * 3 - end in past
     * 4 - end in future
     * 5 - start+end in past
     * 6 - start+end in future
     * @param int $courseid
     * @param int $userid
     * @returns array (forum, array(discussions))
     */
    public function create_timed_discussions_forum($courseid, $userid = 0) {
        $forum = $this->get_new_forumng($courseid);
        // Discussion starting in past.
        $dis1 = $this->get_new_discussion($forum, array('userid' => $userid, 'timestart' => 1420070400));
        // Discussion starting in future.
        $dis2 = $this->get_new_discussion($forum, array('userid' => $userid, 'timestart' => 2524608000));
        // Discussion ending in past.
        $dis3 = $this->get_new_discussion($forum, array('userid' => $userid, 'timeend' => 1420070400));
        // Discussion ending in future.
        $dis4 = $this->get_new_discussion($forum, array('userid' => $userid, 'timeend' => 2524608000));
        // Discussion starting & ending in past.
        $dis5 = $this->get_new_discussion($forum, array('userid' => $userid, 'timestart' => 1420070400, 'timeend' => 1420070401));
        // Discussion starting & ending in future.
        $dis6 = $this->get_new_discussion($forum, array('userid' => $userid, 'timestart' => 2524608000, 'timeend' => 2524608001));
        return array($forum, array($dis1, $dis2, $dis3, $dis4, $dis5, $dis6));
    }

}
