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


global $CFG;
require_once($CFG->dirroot . '/mod/forumng/tests/forumng_test_lib.php');
require_once($CFG->dirroot . '/mod/forumng/mod_forumng_mail_list.php');
require_once($CFG->dirroot . '/mod/forumng/mod_forumng_cron.php');

class mod_forumng_cron_testcase extends forumng_test_lib
{
    /**
     * Unit tests to check mod_forumng_cron::email_normal() and class mod_forumng_mail_list send the correct posts
     */

    protected $generator;
    protected $discussionid;
    protected $rootpostid;
    protected $student;
    protected $adminid;


    /**
     * Set up user, course, forum, discussion for tests
     */
    protected function setUp() {
        global $USER, $CFG;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $CFG->forumng_trackreadposts = true;
        $CFG->forumng_emailafter = 120;

        // Create a course and users.
        $this->adminid = $USER->id;
        $course = $this->get_new_course();
        $student = $this->get_new_user('student', $course->id);

        // Set mark as read to 'manually' for discussions and posts.
        set_user_preference('forumng_manualmark', 1, $this->adminid);
        set_user_preference('forumng_manualmark', 1, $student);

        // Create a forumng, a discussion and a post without edit.
        $this->generator = $this->getDataGenerator()->get_plugin_generator('mod_forumng');
        $forum = $this->get_new_forumng($course->id, array('groupmode' => VISIBLEGROUPS, 'shared' => false,
            'cmidnumber' => 'IPMR'));
        $forum->subscribe($student->id);

        $discussion = $this->generator->create_discussion(array('course' => $course, 'forum' => $forum->get_id(),
            'userid' => $this->adminid));
        $this->discussionid = $discussion[0];
        $this->rootpostid = $discussion[1];
    }

    /**
     * Test post with no edits included after delay time only, expect mail send
     */
    public function test_email_normal_post_without_edit_in_delay_times() {
        global $CFG;
        $this->setUp();
        $created = time() - $CFG->forumng_emailafter - 1;

        // Create post with no edit after delay times no edit.
        $postrecord = $this->generator->create_post(array('discussionid' => $this->discussionid,
            'parentpostid' => $this->rootpostid, 'mailstate' => mod_forumng::MAILSTATE_NOT_MAILED,
            'created' => $created, 'userid' => $this->adminid));

        unset_config('noemailever');
        $sink = $this->redirectEmails();
        \mod_forumng_cron::email_normal();
        $messages = $sink->get_messages();

        // Check 1 email sent.
        $this->assertEquals(1, count($messages));
    }

    /**
     * Test post with edits within delay time , expect latest edit sent
     */
    public function test_email_normal_post_with_edit_in_delay_times() {
        global $CFG;
        $this->setUp();
        $created = time() - $CFG->forumng_emailafter - 1;

        // Create post with no edit after delay times.
        $postrecord = $this->generator->create_post(array('discussionid' => $this->discussionid,
            'parentpostid' => $this->rootpostid, 'mailstate' => mod_forumng::MAILSTATE_NOT_MAILED,
            'created' => $created, 'userid' => $this->adminid));
        $post = mod_forumng_post::get_from_id($postrecord->id, 0);

        // Edit post within delay times.
        $gotsubject = $post->edit_start("New subject");
        $post->edit_finish($post->get_raw_message(), $post->get_format(), $gotsubject);

        unset_config('noemailever');
        $sink = $this->redirectEmails();
        \mod_forumng_cron::email_normal();
        $messages = $sink->get_messages();

        // Check only 1 mail sent for latest edited post.
        $this->assertEquals(1, count($messages));
    }

    /**
     * Test post with edit(s) after mail has been sent, expect edit post sent
     */
    public function test_email_normal_post_with_edit_after_mail_sent() {
        global $CFG;
        $this->setUp();

        $createtime = time() - $CFG->forumng_emailafter - 1;

        // Create post with no edit after delay times.
        $postrecord = $this->generator->create_post(array('discussionid' => $this->discussionid,
            'parentpostid' => $this->rootpostid, 'mailstate' => mod_forumng::MAILSTATE_NOT_MAILED,
            'created' => $createtime, 'userid' => $this->adminid));
        $post = mod_forumng_post::get_from_id($postrecord->id, 0);

        unset_config('noemailever');
        $sink = $this->redirectEmails();

        // Send email.
        \mod_forumng_cron::email_normal();
        $messages = $sink->get_messages();

        // Check 1 email sent for created port.
        $this->assertEquals(1, count($messages));

        // Edit post after email sent.
        $gotsubject = $post->edit_start("New subject");
        $post->edit_finish($post->get_raw_message(), $post->get_format(), $gotsubject);

        $sink = $this->redirectEmails();
        \mod_forumng_cron::email_normal();
        $messages = $sink->get_messages();
        // Check send email again.
        $this->assertEquals(1, count($messages));
    }

    /**
     * Posts + edits already sent , expect do not get sent again
     */
    public function test_email_normal_post_with_edit_before_mail_already_sent() {
        global $CFG;
        $this->setUp();

        $createtime = time() - $CFG->forumng_emailafter - 1;

        // Create post after delay times.
        $postrecord = $this->generator->create_post(array('discussionid' => $this->discussionid,
            'parentpostid' => $this->rootpostid, 'mailstate' => mod_forumng::MAILSTATE_NOT_MAILED,
            'created' => $createtime, 'userid' => $this->adminid));
        $post = mod_forumng_post::get_from_id($postrecord->id, 0);

        // Edit post within delay times.
        $gotsubject = $post->edit_start("New subject");
        $post->edit_finish($post->get_raw_message(), $post->get_format(), $gotsubject);

        unset_config('noemailever');
        $sink = $this->redirectEmails();
        \mod_forumng_cron::email_normal();
        $messages = $sink->get_messages();

        // Check the sending email after delay time.
        $this->assertEquals(1, count($messages));

        $sink = $this->redirectEmails();
        \mod_forumng_cron::email_normal();
        $messages = $sink->get_messages();

        // Check no email again.
        $this->assertEquals(0, count($messages));
    }
}