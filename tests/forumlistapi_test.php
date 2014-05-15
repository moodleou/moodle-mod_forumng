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
 * PHPUnit ForumNG generator tests.
 *
 * @package mod_forumng
 * @copyright 2014 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/forumng/externallib.php');
require_once($CFG->dirroot . '/course/lib.php');

/**
 * PHPUnit forum list api testcase.
 *
 * @package mod_forumng
 * @copyright 2014 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_forumng_forumlist_testcase extends advanced_testcase {
    /**
     * Tests the get_forum_list() function. This funcion is the meat of the web service but we cannot
     * check it as a web service so we just call the function.
     */
    public function test_forumlistapi() {
        global $DB;

        $this->resetAfterTest(true);

        // Create course.
        $record = new stdClass();
        $record->shortname = 'testcourse';
        $course = self::getDataGenerator()->create_course($record);

        // Create a teacher user and enrol them onto the course.
        $record = array();
        $record['username'] = 'teacher';
        $teacher = self::getDataGenerator()->create_user($record);
        $roleid = $DB->get_field('role', 'id', array('shortname' => 'teacher'));
        self::getDataGenerator()->enrol_user($teacher->id, $course->id, $roleid);

        // Create a student user and enrol them onto the course.
        $record['username'] = 'student';
        $student = self::getDataGenerator()->create_user($record);
        $roleid = $DB->get_field('role', 'id', array('shortname' => 'student'));
        self::getDataGenerator()->enrol_user($student->id, $course->id, $roleid);

        // Create forum A.
        $record = new stdClass();
        $record->course = $course->id;
        $record->name = 'Forum A';
        $foruma = self::getDataGenerator()->create_module('forumng', $record);

        // Needed later when moving forums to specific sections.
        course_create_sections_if_missing($course->id, array(1));
        $modinfo = get_fast_modinfo($course);
        $mod = $modinfo->get_cm($foruma->cmid);
        $sectionzero = $modinfo->get_section_info(0, MUST_EXIST);
        $sectionone = $modinfo->get_section_info(1, MUST_EXIST);

         // Create forum B.
        $record = new stdClass();
        $record->course = $course->id;
        $record->name = 'Forum B';
        $forumb = self::getDataGenerator()->create_module('forumng', $record);

         // Create forum C.
        $record = new stdClass();
        $record->course = $course->id;
        $record->name = 'Forum C';
        $forumc = self::getDataGenerator()->create_module('forumng', $record);
        // Make forum C hidden.
        set_coursemodule_visible($forumc->cmid, 0);

        $modinfo = get_fast_modinfo($course);
        // Move forum A to section 1.
        $mod = $modinfo->get_cm($foruma->cmid);
        moveto_module($mod, $sectionone);
        // Move forum B to section 0.
        $mod = $modinfo->get_cm($forumb->cmid);
        moveto_module($mod, $sectionzero);
        // Move forum C to section 1.
        $mod = $modinfo->get_cm($forumc->cmid);
        moveto_module($mod, $sectionone);

        // Student starts discussion in forum A.
        $record = new stdClass();
        $record->course = $course->id;
        $record->forum = $foruma->id;
        $record->userid = $student->id;
        $this->setUser($student);
        $discussionid = self::getDataGenerator()->get_plugin_generator('mod_forumng')->create_discussion($record);

        // Teacher starts discussion in forum B.
        $record = new stdClass();
        $record->course = $course->id;
        $record->forum = $forumb->id;
        $record->userid = $teacher->id;
        $this->setUser($teacher);
        $discussionid = self::getDataGenerator()->get_plugin_generator('mod_forumng')->create_discussion($record);

        // Call the web service function to get a list of forums for the teacher.
        $response = mod_forumng_external::get_forum_list($course->shortname, $teacher->username);
        // There should be 3 forums in the array.
        $this->assertEquals(3, count($response));
        // Check each forum's data.
        $this->assertEquals('Forum B', $response[0]->name);
        $this->assertEquals('n', $response[0]->unread);
        $this->assertEquals('Forum A', $response[1]->name);
        $this->assertEquals('y', $response[1]->unread);
        $this->assertEquals('Forum C', $response[2]->name);
        $this->assertEquals('n', $response[2]->unread);

        // Call the web service function to get a list of forums for the student.
        $response = mod_forumng_external::get_forum_list($course->shortname, $student->username);
        // There should be 2 forums in the array.
        $this->assertEquals(2, count($response));
        // Check each forum's data.
        $this->assertEquals('Forum B', $response[0]->name);
        $this->assertEquals('y', $response[0]->unread);
        $this->assertEquals('Forum A', $response[1]->name);
        $this->assertEquals('n', $response[1]->unread);

        // Call the webservice function with invalid username.
        $wronguser = 'doesnotexist';
        try {
            // If this does not throw an exception then the test fails.
            $response = mod_forumng_external::get_forum_list($course->shortname, $wronguser);
            $this->fail();
        } catch (moodle_exception $e) {
            $this->assertEquals(get_string('cannotfinduser', 'error', $wronguser), $e->errorcode);
        }

        // Call the webservice function with invalid course.
        $wrongcourse = 'doesnotexist';
        try {
            // If this does not throw an exception then the test fails.
            $response = mod_forumng_external::get_forum_list($wrongcourse, $student->username);
            $this->fail();
        } catch (Exception $e) {
            $this->assertEquals(get_string('cannotfindcourse', 'error'), $e->errorcode);
        }
    }
}
