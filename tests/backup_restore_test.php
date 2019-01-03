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
 * PHPUnit ForumNG backup restore test.
 *
 * @package mod_forumng
 * @copyright 2018 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . "/phpunit/classes/restore_date_testcase.php");

/**
 * PHPUnit forum backup/restore testcase.
 *
 * @package mod_forumng
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_restore_test extends restore_date_testcase {


    public function test_copy_bug_restore_fix() {
        global $USER, $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Create the generator object for ForumNG.
        $generator = self::getDataGenerator()->get_plugin_generator('mod_forumng');

        // Create course.
        $course = self::getDataGenerator()->create_course(array('shortname' => 'Course 1'));

        // Create a student user and enrol them onto the course.
        $student = self::getDataGenerator()->create_user(array('username' => 'student'));
        $roleid = $DB->get_field('role', 'id', array('shortname' => 'student'));
        self::getDataGenerator()->enrol_user($student->id, $course->id, $roleid);

        // Create forum.
        $forum = $generator->create_instance(array('course' => $course->id));

        // Create discussion for current user.
        $record = new stdClass();
        $record->course = $course->id;
        $record->forum = $forum->id;
        $record->userid = $USER->id;
        $dis1 = $generator->create_discussion($record);
        $dis2 = $generator->create_discussion($record);

        // Hack in bad posts where id is less than parent post.
        $badpost = [
            'discussionid' => $dis2[0],
            'message' => 'test',
            'oldversion' => 1,
            'userid' => $student->id,
            'created' => time(),
            'modified' => time(),
            'messageformat' => 0,
        ];
        $post1id = $DB->insert_record('forumng_posts', $badpost);
        $badpost['oldversion'] = 0;
        $badpost['parentpostid'] = $dis2[1];
        $post2id = $DB->insert_record('forumng_posts', $badpost);
        $DB->set_field('forumng_posts', 'parentpostid', $post2id, ['id' => $post1id]);

        $this->backup_and_restore($course);
        // Check all new posts (that don't have the original discussion ids).
        list($sql, $params) = $DB->get_in_or_equal([$dis1[0], $dis2[0]], SQL_PARAMS_QM, 'param', false);
        $newposts = $DB->get_records_select('forumng_posts', "oldversion = 1 and discussionid $sql", $params);
        foreach ($newposts as $newpost) {
            // Check our new edit posts have the parentpost set.
            $this->assertNotEmpty($newpost->parentpostid);
            $this->assertGreaterThan(0, $newpost->parentpostid);
        }
    }
}
