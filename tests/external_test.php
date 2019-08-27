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
     * Create sample discussion.
     *
     * @return mixed
     */
    protected function generate_discussion() {
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

        $this->setUser($student);

        return $discussion;
    }

    /**
     * Tests the get_posts() function for iPud ForumNG type. This function is the meat of the web service
     * but we cannot check it as a web service so we just call the function.
     */
    public function test_get_posts_ipud() {
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
        $forum = $generator->create_instance(array('course' => $course->id, 'type' => 'ipud'));

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
     * Tests the test_expandpost() function. This function is the meat of the web service but we cannot
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

    /**
     * Tests the create_reply() function. This function is the meat of the web service but we cannot
     * check it as a web service so we just call the function.
     */
    public function test_create_reply_success() {
        global $USER;

        $discussion = $this->generate_discussion();

        // Set to prevent exception about session key when construct a form.
        $_POST['sesskey'] = $USER->sesskey;

        // Check reply for discussion.
        $response = \mod_forumng\local\external\create_reply::create_reply($discussion[1], 'Subject 1', array(
            'text' => 'Content 1',
            'format' => 1
        ));
        $this->assertEquals('Subject 1', $response->title);
        $this->assertEquals('Content 1', $response->content);
        // Get from database to check if it really created.
        $post = mod_forumng_post::get_from_id($response->postid, 0);
        $this->assertEquals('Subject 1', $post->get_subject());
        $this->assertEquals('Content 1', $post->get_raw_message());

        // Check reply to reply.
        $response = \mod_forumng\local\external\create_reply::create_reply($post->get_id(), 'Subject 2', array(
            'text' => 'Content 2',
            'format' => 1
        ));
        $this->assertEquals('Subject 2', $response->title);
        $this->assertEquals('Content 2', $response->content);
        // Get from database to check if it really created.
        $post = mod_forumng_post::get_from_id($response->postid, 0);
        $this->assertEquals('Subject 2', $post->get_subject());
        $this->assertEquals('Content 2', $post->get_raw_message());
    }

    /**
     * Test reply to post which have been deleted.
     */
    public function test_create_reply_to_deleted_post() {
        global $USER;

        $discussion = $this->generate_discussion();

        // Set to prevent exception about session key when construct a form.
        $_POST['sesskey'] = $USER->sesskey;

        $newpostid = mod_forumng_post::get_from_id($discussion[1], 0)->reply('Subject', 'Message', 1);
        $deletedpost = mod_forumng_post::get_from_id($newpostid, 0);
        $deletedpost->delete();

        // Check reply to post which have been deleted, expect error with message.
        $this->expectException('moodle_exception');
        $this->expectExceptionMessage(get_string('reply_notcurrentpost', 'mod_forumng'));
        \mod_forumng\local\external\create_reply::create_reply($deletedpost->get_id(), 'Subject', array(
            'text' => 'Content',
            'format' => 1
        ));
    }

    /**
     * Test reply to post with invalid data.
     */
    public function test_create_reply_with_invalid_data() {
        global $USER;

        $discussion = $this->generate_discussion();

        // Set to prevent exception about session key when construct a form.
        $_POST['sesskey'] = $USER->sesskey;

        // Check create with invalid data (simple form data by set the message empty and custom validation
        // by set the subject to current user's username.).
        $formerrors = array(
            'subject' => get_string('error_identityinsubject_reply', 'mod_forumng'),
            'message' => get_string('required')
        );
        $this->expectException('moodle_exception');
        $this->expectExceptionMessage(json_encode($formerrors));
        \mod_forumng\local\external\create_reply::create_reply($discussion[1], $USER->username, array(
            'text' => '',
            'format' => 1
        ));
    }

    /**
     * Tests the edit_post() function. This function is the meat of the web service but we cannot
     * check it as a web service so we just call the function.
     */
    public function test_edit_post_success() {
        global $USER;

        $discussion = $this->generate_discussion();

        // Set to prevent exception about session key when construct a form.
        $_POST['sesskey'] = $USER->sesskey;

        // Create first reply for current user.
        $rootpost = mod_forumng_post::get_from_id($discussion[1], 0);
        $newpostid = $rootpost->reply('Subject 1', 'Content 1', 1);

        // Edit new post content.
        $response = \mod_forumng\local\external\edit_post::edit_post($newpostid, 'Subject 2', array(
            'text' => 'Content 2',
            'format' => 1
        ));
        $this->assertEquals('Subject 2', $response->title);
        $this->assertEquals('Content 2', $response->content);
        // Get from database to check if it really updated.
        $post = mod_forumng_post::get_from_id($response->postid, 0);
        $this->assertEquals('Subject 2', $post->get_subject());
        $this->assertEquals('Content 2', $post->get_raw_message());

    }

    /**
     * Test edit to post which have been deleted.
     */
    public function test_edit_deleted_post() {
        global $USER;

        $discussion = $this->generate_discussion();

        // Set to prevent exception about session key when construct a form.
        $_POST['sesskey'] = $USER->sesskey;

        $newpostid = mod_forumng_post::get_from_id($discussion[1], 0)->reply('Subject', 'Message', 1);
        $deletedpost = mod_forumng_post::get_from_id($newpostid, 0);
        $deletedpost->delete();

        // Check edit the post which have been deleted, expect error.
        $this->expectException('moodle_exception');
        $this->expectExceptionMessage(get_string('edit_notcurrentpost', 'mod_forumng'));
        \mod_forumng\local\external\edit_post::edit_post($deletedpost->get_id(), 'Subject 2', array(
            'text' => 'Content 2',
            'format' => 1
        ));
    }

    /**
     * Test edit post with invalid data.
     */
    public function test_edit_post_with_invalid_data() {
        global $USER;

        $discussion = $this->generate_discussion();

        // Set to prevent exception about session key when construct a form.
        $_POST['sesskey'] = $USER->sesskey;

        $newpostid = mod_forumng_post::get_from_id($discussion[1], 0)->reply('Subject', 'Message', 1);

        // Check edit with invalid data (simple form data by set the message empty and custom validation
        // by set the subject to current user's username.).
        $formerrors = array(
            'subject' => get_string('error_identityinsubject_reply', 'mod_forumng'),
            'message' => get_string('required')
        );
        $this->expectException('moodle_exception');
        $this->expectExceptionMessage(json_encode($formerrors));
        \mod_forumng\local\external\edit_post::edit_post($newpostid, $USER->username, array(
            'text' => '',
            'format' => 1
        ));
    }

    /**
     * Tests the delete_post() function. This function is the meat of the web service but we cannot
     * check it as a web service so we just call the function.
     */
    public function test_delete_post() {
        global $USER;

        $discussion = $this->generate_discussion();

        // Create first reply for current user.
        $rootpost = mod_forumng_post::get_from_id($discussion[1], 0);
        $newpostid = $rootpost->reply('Subject 1', 'Content 1', 1);

        // Try to delete the post via web service.
        $response = \mod_forumng\local\external\delete_post::delete_post($newpostid, $newpostid);
        $this->assertTrue($response->success);

        // Check that post really deleted in DB.
        $post = mod_forumng_post::get_from_id($newpostid, 0);
        $this->assertNotEmpty($post->get_deleted());
        $this->assertEquals($USER->id, $post->get_delete_user()->id);

        // Try to delete deleted post, expect exception.
        $response = \mod_forumng\local\external\delete_post::delete_post($newpostid, $post->get_id());
        $this->assertFalse($response->success);
        $this->assertEquals(get_string('edit_notcurrentpost', 'mod_forumng'), $response->message);
    }

    /**
     * Tests the undelete_post() function. This function is the meat of the web service but we cannot
     * check it as a web service so we just call the function.
     */
    public function test_undelete_post() {
        global $USER;

        $discussion = $this->generate_discussion();

        // Switch to admin to have capability to undelete post.
        $this->setAdminUser();

        // Create post then delete the post.
        $rootpost = mod_forumng_post::get_from_id($discussion[1], 0);
        $newpostid = $rootpost->reply('Subject 1', 'Content 1', 1);
        $newpost = mod_forumng_post::get_from_id($newpostid, 0);
        $newpost->delete();

        // Try to un the post via web service.
        $response = \mod_forumng\local\external\undelete_post::undelete_post($newpostid);
        $this->assertTrue($response->success);
        $this->assertEquals($newpost->get_id(), $response->postinfo->postid);
        $this->assertEmpty($response->postinfo->deletedtime);

        // Check that post really is not deleted in DB.
        $post = mod_forumng_post::get_from_id($newpostid, 0);
        $this->assertEmpty($post->get_deleted());

        // Try to undelete normal post, expect exception.
        $response = \mod_forumng\local\external\undelete_post::undelete_post($newpostid);
        $this->assertFalse($response->success);
        $this->assertEquals(get_string('edit_notdeleted', 'mod_forumng'), $response->message);
    }
}
