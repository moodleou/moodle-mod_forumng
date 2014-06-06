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
 * PHPUnit ForumNG discussion tests.
 *
 * @package mod_forumng
 * @copyright 2014 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class mod_forumng_discussion_testcase  extends advanced_testcase {

    /**
     * ForumNG generator reference
     * @var testing_module_generator
     */
    public $generator = null;

    /**
     * Create temporary test tables and entries in the database for these tests.
     * These tests have to work on a brand new site.
     */

    /*
        Backend functions covered:
        get_root_post()
        get_from_id()
        edit_settings()
        create_discussion()
        get_time_start()
    */

    public function test_showfrom () {
        global $DB, $USER, $SITE;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Create the generator object and do standard checks.
        $generator = self::getDataGenerator()->get_plugin_generator('mod_forumng');

        // Create course.
        $record = new stdClass();
        $record->shortname = 'testcourse';
        $course = self::getDataGenerator()->create_course($record);

        // Create forum.
        $forum = $generator->create_instance(array('course' => $SITE->id));

        // Get UNIX time + 1 day.
        $utime = time();
        // UNIX time + 1 day.
        $futuretime = $utime + (24 * 60 * 60);

        // Start a discussion in forum.
        $record = new stdClass();
        $record->course = $course->id;
        $record->forum = $forum->id;
        $record->userid = $USER->id;

        $record->timestart = $futuretime;
        $ids = $generator->create_discussion($record);

        // Test to see that the created discussion and root post records contains a matching timestart to futuretime.
        $discussionid = $ids[0];
        $rootpostid = $ids[1];
        $discussion = mod_forumng_discussion::get_from_id($discussionid , 0);
        $starttime = $discussion->get_time_start();
        $this->assertEquals($futuretime, $starttime);
        $root = $discussion->get_root_post();
        $this->assertEquals($futuretime, $root->get_created());
        $this->assertEquals($futuretime, $root->get_modified());

        // Test to see we can not create a post with an earlier start time than today.
        $record = new stdClass();
        $record->course = $course->id;
        $record->forum = $forum->id;
        $record->userid = $USER->id;

        $pasttime = $utime - (24 * 60 * 60);
        $record->timestart = $pasttime;
        $ids = $generator->create_discussion($record);
        // Test to see that the created discussion and root post records contains a matching time for now (utime).
        $discussionid = $ids[0];
        $rootpostid = $ids[1];
        $discussion = mod_forumng_discussion::get_from_id($discussionid , 0);
        $starttime = $discussion->get_time_start();
        $root = $discussion->get_root_post();
        $this->assertNotEquals($pasttime, $root->get_created());
        $this->assertNotEquals($pasttime, $root->get_modified());

        // Test to see that we can change root post times to a revised future time.
        // Future time is set to an extra day ahead (i.e. 2 days from now).
        $futuretime = $futuretime + (24 * 60 * 60);

        // Edit root post settings.
        $discussion->edit_settings($discussion::NOCHANGE, $futuretime, $discussion::NOCHANGE, $discussion::NOCHANGE,
                $discussion::NOCHANGE);

        $starttime = $discussion->get_time_start();
        $this->assertEquals($futuretime, $starttime);
        // Set cache to false to stop it picking up old (cached) created and modified times.
        $roota = $discussion->get_root_post(false);
        $this->assertEquals($futuretime, $roota->get_created());
        $this->assertEquals($futuretime, $roota->get_modified());

    }
}
