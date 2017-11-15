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
 * PHPUnit ForumNG web service test.
 *
 * @package mod_forumng
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use \mod_forumng\local\external\get_posts;
use \mod_forumng\local\external\expand_post;

global $CFG;
require_once($CFG->dirroot . '/mod/forumng/externallib.php');
require_once($CFG->dirroot . '/course/lib.php');

/**
 * PHPUnit forum list api testcase.
 *
 * @package mod_forumng
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class forumng_external_test extends advanced_testcase {
    /**
     * Tests the get_posts() function. This funcion is the meat of the web service but we cannot
     * check it as a web service so we just call the function.
     */
    public function test_get_posts() {
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
        $discussion = $generator->create_discussion($record);

        // Test get posts when discussion have no reply.
        $response = get_posts::get_posts($discussion[0], 4);
        // Expect return nothing.
        $this->assertCount(0, $response['data']);
        $this->assertEquals(0, $response['totalpost']);

        // Create replies for discussion.
        $reply1 = $generator->create_post(
            array(
                'discussionid' => $discussion[0],
                'parentpostid' => $discussion[1],
                'userid' => $USER->id,
                'message' => 'Reply 1'
            )
        );
        $reply2 = $generator->create_post(
            array(
                'discussionid' => $discussion[0],
                'parentpostid' => $discussion[1],
                'userid' => $USER->id,
                'message' => 'Reply 2'
            )
        );
        $generator->create_post(
            array(
                'discussionid' => $discussion[0],
                'parentpostid' => $discussion[1],
                'userid' => $USER->id,
                'message' => 'Reply 3'
            )
        );
        $reply4 = $generator->create_post(
            array(
                'discussionid' => $discussion[0],
                'parentpostid' => $discussion[1],
                'userid' => $USER->id,
                'message' => 'Reply 4'
            )
        );

        // Create reply for Reply 2.
        $generator->create_post(
            array(
                'discussionid' => $discussion[0],
                'parentpostid' => $reply2->id,
                'userid' => $USER->id,
                'message' => 'Reply 2.1'
            )
        );
        $generator->create_post(
            array(
                'discussionid' => $discussion[0],
                'parentpostid' => $reply2->id,
                'userid' => $USER->id,
                'message' => 'Reply 2.2'
            )
        );

        // Test get single reply.
        $response = get_posts::get_posts($discussion[0], 1);
        $this->assertEquals(6, $response['totalpost']);
        $this->assertEquals('Reply 4', $response['data'][0]->content);
        $this->assertEquals(0, $response['data'][0]->numberofreply);
        $this->assertEquals(0, count($response['data'][0]->replies));
        $this->assertEmpty($response['data'][0]->replies);

        // Test get more reply than we have, this should show all post (4).
        $response = get_posts::get_posts($discussion[0], 5);
        $this->assertEquals(6, $response['totalpost']);
        $this->assertCount(4, $response['data']);
        $this->assertEquals('Reply 4', $response['data'][0]->content);
        $this->assertEquals('Reply 3', $response['data'][1]->content);
        $this->assertEquals('Reply 2', $response['data'][2]->content);
        $this->assertEquals('Reply 1', $response['data'][3]->content);
        $this->assertEquals(0, $response['data'][0]->numberofreply);
        $this->assertEquals(0, $response['data'][1]->numberofreply);
        $this->assertEquals(2, $response['data'][2]->numberofreply);
        $this->assertEquals(0, $response['data'][3]->numberofreply);
        $this->assertEquals(0, count($response['data'][0]->replies));
        $this->assertEquals(0, count($response['data'][1]->replies));
        $this->assertEquals(0, count($response['data'][2]->replies));
        $this->assertEquals(0, count($response['data'][3]->replies));
        $this->assertEmpty($response['data'][0]->replies);
        $this->assertEmpty($response['data'][1]->replies); // Empty because we do not return replies.
        $this->assertEmpty($response['data'][2]->replies);
        $this->assertEmpty($response['data'][3]->replies);

        // Test get all post including replies.
        $response = get_posts::get_posts($discussion[0], 0);
        $this->assertEquals(6, $response['totalpost']);
        $this->assertEquals('Reply 1', $response['data'][0]->content);
        $this->assertEquals('Reply 2', $response['data'][1]->content);
        $this->assertEquals('Reply 3', $response['data'][2]->content);
        $this->assertEquals('Reply 4', $response['data'][3]->content);
        $this->assertEquals(0, $response['data'][0]->numberofreply);
        $this->assertEquals(2, $response['data'][1]->numberofreply);
        $this->assertEquals(0, $response['data'][2]->numberofreply);
        $this->assertEquals(0, $response['data'][3]->numberofreply);
        $this->assertEquals(0, count($response['data'][0]->replies));
        $this->assertEquals(2, count($response['data'][1]->replies));
        $this->assertEquals(0, count($response['data'][2]->replies));
        $this->assertEquals(0, count($response['data'][3]->replies));
        $this->assertEmpty($response['data'][0]->replies);
        $this->assertNotEmpty($response['data'][1]->replies);
        $this->assertEmpty($response['data'][2]->replies);
        $this->assertEmpty($response['data'][3]->replies);
        $this->assertEquals('Reply 2.1', $response['data'][1]->replies[0]->content);
        $this->assertEquals('Reply 2.2', $response['data'][1]->replies[1]->content);

        // Test view data on deleted post, delete Reply 1.
        $post1 = mod_forumng_post::get_from_id($reply4->id, 0);
        $post1->delete();

        // Test that owner can see content of deleted post.
        $response = get_posts::get_posts($discussion[0], 1);
        $this->assertEquals(5, $response['totalpost']);
        $this->assertEquals(1, count($response['totalpost']));
        $this->assertEquals('Reply 4', $response['data'][0]->content);
        $this->assertEquals(1, $response['data'][0]->canundelete);
        $this->assertEquals(1, $response['data'][0]->canviewdeleted);
        $this->assertNotEquals(0, $response['data'][0]->deletedtime);

        // Login as student.
        $this->setUser($student);

        // Check get latest post, since student don't have permission to view deleted post,
        // server will return not deleted post instead.
        $response = get_posts::get_posts($discussion[0], 1);
        $this->assertEquals(5, $response['totalpost']);
        $this->assertEquals('Reply 3', $response['data'][0]->content);
        $this->assertEquals('Forum post subject 3', $response['data'][0]->title);
        $this->assertEquals('Admin User', $response['data'][0]->authorname);
        $this->assertEquals(0, $response['data'][0]->deletedtime);
    }

    /**
     * Tests the test_expandpost() function. This funcion is the meat of the web service but we cannot
     * check it as a web service so we just call the function.
     */
    public function test_expand_post() {
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
        $discussion = $generator->create_discussion($record);

        // Create replies for discussion.
        $reply1 = $generator->create_post(
            array(
                'discussionid' => $discussion[0],
                'parentpostid' => $discussion[1],
                'userid' => $USER->id,
                'message' => 'Reply 1'
            )
        );
        $reply2 = $generator->create_post(
            array(
                'discussionid' => $discussion[0],
                'parentpostid' => $discussion[1],
                'userid' => $USER->id,
                'message' => 'Reply 2'
            )
        );
        // Create reply for Reply 2.
        $generator->create_post(
            array(
                'discussionid' => $discussion[0],
                'parentpostid' => $reply2->id,
                'userid' => $USER->id,
                'message' => 'Reply 2.1'
            )
        );
        $generator->create_post(
            array(
                'discussionid' => $discussion[0],
                'parentpostid' => $reply2->id,
                'userid' => $USER->id,
                'message' => 'Reply 2.2'
            )
        );

        // Check expand post 1 this post don't have any replies.
        $response = expand_post::expand_post($discussion[0], $reply1->id);
        $this->assertEquals('Reply 1', $response->content);
        $this->assertEquals(0, $response->numberofreply);
        $this->assertEquals(0, count($response->replies));
        $this->assertEmpty($response->replies);

        // Check expand post 2 this post don't have any replies.
        $response = expand_post::expand_post($discussion[0], $reply2->id);
        $this->assertEquals('Reply 2', $response->content);
        $this->assertEquals(2, $response->numberofreply);
        $this->assertEquals(2, count($response->replies));
        $this->assertEquals('Reply 2.1', $response->replies[0]->content);
        $this->assertEquals('Reply 2.2', $response->replies[1]->content);
    }
}
