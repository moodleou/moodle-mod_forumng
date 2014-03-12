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


/**
 * PHPUnit data generator testcase.
 *
 * @package mod_forumng
 * @copyright 2014 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_forumng_generator_testcase extends advanced_testcase {
    public function test_generator() {
        global $DB, $SITE;

        $this->resetAfterTest(true);

        // Must be a non-guest user to create forums.
        $this->setAdminUser();

        // There are 0 forums initially.
        $this->assertEquals(0, $DB->count_records('forumng'));

        // Create a course.
        $course = $this->getDataGenerator()->create_course();

        // Create the generator object and do standard checks.
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_forumng');
        $this->assertInstanceOf('mod_forumng_generator', $generator);
        $this->assertEquals('forumng', $generator->get_modulename());

        // Create three forum instances in the site course.
        $generator->create_instance(array('course' => $SITE->id));
        $generator->create_instance(array('course' => $SITE->id));
        $forum = $generator->create_instance(array('course' => $SITE->id));
        $this->assertEquals(3, $DB->count_records('forumng'));

        // Check the course-module is correct.
        $cm = get_coursemodule_from_instance('forumng', $forum->id);
        $this->assertEquals($forum->id, $cm->instance);
        $this->assertEquals('forumng', $cm->modname);
        $this->assertEquals($SITE->id, $cm->course);

        // Check the context is correct.
        $context = context_module::instance($cm->id);
        $this->assertEquals($forum->cmid, $context->instanceid);
    }

    /**
     * Test create_discussion.
     */
    public function test_create_discussion() {
        global $DB;

        $this->resetAfterTest(true);

        // User that will create the forum.
        $user = self::getDataGenerator()->create_user();
        $this->setUser($user);

        // Create course to add the forum to.
        $course = self::getDataGenerator()->create_course();

        // The forum.
        $record = new stdClass();
        $record->course = $course->id;
        $forum = self::getDataGenerator()->create_module('forumng', $record);

        // Add a few discussions.
        $record = array();
        $record['course'] = $course->id;
        $record['forum'] = $forum->id;
        $record['userid'] = $user->id;
        self::getDataGenerator()->get_plugin_generator('mod_forumng')->create_discussion($record);
        self::getDataGenerator()->get_plugin_generator('mod_forumng')->create_discussion($record);
        self::getDataGenerator()->get_plugin_generator('mod_forumng')->create_discussion($record);

        // Check the discussions were correctly created.
        $this->assertEquals(3, $DB->count_records_select('forumng_discussions', 'forumngid = :forum',
            array('forum' => $forum->id)));
    }

    /**
     * Test create_post.
     */
    public function test_create_post() {
        global $DB;

        $this->resetAfterTest(true);

        // Create a bunch of users.
        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
        $user3 = self::getDataGenerator()->create_user();
        $user4 = self::getDataGenerator()->create_user();

        // Create course to add the forum.
        $course = self::getDataGenerator()->create_course();

        // The forum.
        $record = new stdClass();
        $record->course = $course->id;
        $forum = self::getDataGenerator()->create_module('forumng', $record);

        // Add a discussion.
        $record->forum = $forum->id;
        $record->userid = $user1->id;
        $this->setUser($user1);
        $discussionid = self::getDataGenerator()->get_plugin_generator('mod_forumng')->create_discussion($record);

        // Add a bunch of replies, changing the userid.
        $record = new stdClass();
        $record->discussionid = $discussionid[0];
        $record->userid = $user2->id;
        self::getDataGenerator()->get_plugin_generator('mod_forumng')->create_post($record);
        $record->userid = $user3->id;
        self::getDataGenerator()->get_plugin_generator('mod_forumng')->create_post($record);
        $record->userid = $user4->id;
        self::getDataGenerator()->get_plugin_generator('mod_forumng')->create_post($record);

        // Check the posts were correctly created, remember, when creating a discussion a post
        // is generated as well, so we should have 4 posts, not 3.
        $this->assertEquals(4, $DB->count_records_select('forumng_posts', 'discussionid = :discussionid',
            array('discussionid' => $discussionid[0])));
    }
}
