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
 * Mobile web service function tests.
 *
 * @package mod_forumng
 * @copyright 2018 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tests\mod_forumng;

use forumng_test_lib;
use mod_forumng;
use mod_forumng\local\external\delete_discussion;
use \mod_forumng\output\mobile;
use \mod_forumng\local\external\more_discussions;
use \mod_forumng\local\external\add_discussion;
use \mod_forumng\local\external\reply;
use \mod_forumng\local\external\mark_read;
use \mod_forumng\local\external\mark_all_post_read;
use \mod_forumng\local\external\lock_discussion;
use \mod_forumng\local\external\delete_post;
use \mod_forumng\local\external\undelete_post;
use \mod_forumng\local\external\add_draft;
use \mod_forumng\local\external\delete_draft;
use \mod_forumng\local\external\get_discussion;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/forumng/tests/forumng_test_lib.php');

/**
 * Mobile web service function tests.
 */
class mobile_test extends forumng_test_lib {

    /**
     * Prepare a forum and its clone forum.
     *
     * @param \stdClass $course
     * @return mod_forumng[]
     */
    private function prepare_clone_forum($course): array {
        $baseforum = $this->get_new_forumng($course->id, [
                'name' => 'Base Forum',
                'intro' => 'abc123',
                'shared' => true,
                'cmidnumber' => 'SF1',
        ]);

        $createcloneforum = $this->get_new_forumng($course->id, [
                'name' => 'Clone Forum',
                'usesharedgroup' => [
                        'useshared' => true,
                        'originalcmidnumber' => 'SF1',
                ],
        ]);

        $cloneforum = mod_forumng::get_from_cmid($createcloneforum->get_course_module_id(), mod_forumng::CLONE_DIRECT);

        return [$baseforum, $cloneforum];
    }

    /**
     * Test the basic functionality of the forum view page.
     */
    public function test_mobile_forumng_view_basic() {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_user();
        $generator->enrol_user($student->id, $course->id, 'student');
        $this->setUser($student);
        $forumnggenerator = $generator->get_plugin_generator('mod_forumng');
        $forum = $forumnggenerator->create_instance(['course' => $course->id]);
        $cm = get_coursemodule_from_instance('forumng', $forum->id);

        // Request initial page.
        $args = [
            'cmid' => $cm->id,
            'courseid' => $course->id,
            'userid' => $student->id
        ];
        $result = mobile::forumng_view($args);

        // Basic forum with no discussions.
        $this->assertEquals([], $result['files']);
        $otherdata = $result['otherdata'];
        $this->assertEquals(-1, $otherdata['defaultgroup']);
        $this->assertEquals(0, count(json_decode($otherdata['discussions'])));
        $this->assertEquals(0, $otherdata['totaldiscussions']);
        $this->assertEquals(1, $otherdata['page']);
        $template = $result['templates'][0];
        $this->assertEquals('main', $template['id']);
        $this->assertStringContainsString('Test forum 1', $template['html']);

        // Add a discussion.
        $record = [];
        $record['course'] = $course->id;
        $record['forum'] = $forum->id;
        $record['userid'] = $student->id;
        $forumnggenerator->create_discussion($record);
        $result = mobile::forumng_view($args);
        $otherdata = $result['otherdata'];
        $this->assertEquals(-1, $otherdata['defaultgroup']);
        $this->assertEquals(1, count(json_decode($otherdata['discussions'])));
        $this->assertEquals(1, $otherdata['totaldiscussions']);
        $this->assertEquals(1, $otherdata['page']);
    }

    /**
     * Test the more_discussions webservice functionality.
     */
    public function test_mobile_forumng_more_discussions() {
        global $CFG;

        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_user();
        $generator->enrol_user($student->id, $course->id, 'student');
        $this->setUser($student);
        $forumnggenerator = $generator->get_plugin_generator('mod_forumng');
        $forum = $forumnggenerator->create_instance(['course' => $course->id]);
        $cm = get_coursemodule_from_instance('forumng', $forum->id);
        // Set the discussions per page to a low number as we want page 2 results.
        $CFG->forumng_discussionsperpage = 2;

        // Add discussions.
        $record = [];
        $record['course'] = $course->id;
        $record['forum'] = $forum->id;
        $record['userid'] = $student->id;
        $forumnggenerator->create_discussion($record);
        $forumnggenerator->create_discussion($record);
        $forumnggenerator->create_discussion($record);

        // Get the second page of discussions from the webservice function.
        $pageno = 2;
        $result = more_discussions::more_discussions($cm->id, \mod_forumng::NO_GROUPS, $pageno);
        // The second page only has one discussion (the oldest) so has subject no of 1.
        $this->assertCount(1, $result);
        $this->assertEquals('Subject for discussion 1', $result[0]->subject);
    }

    /**
     * Test the add_discussion webservice functionality.
     */
    public function test_mobile_forumng_add_discussion() {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_user();
        $generator->enrol_user($student->id, $course->id, 'student');
        $this->setUser($student);
        $forumnggenerator = $generator->get_plugin_generator('mod_forumng');
        $forum = $forumnggenerator->create_instance(['course' => $course->id]);
        $discussion = 0;
        $group = -1;
        $subject = 'Test subject';
        $message = 'Test message';
        $filerecord = ['filename' => 'basepic.jpg'];
        $file = self::create_draft_file($filerecord);
        $draftarea = $file->get_itemid();

        // Add a new discussion via the WS.
        $result = add_discussion::add_discussion($forum->id, $discussion, $group,
                $subject, $message, $draftarea, 0, 0, 0, mod_forumng::CLONE_DIRECT);
        $this->assertTrue($result['success']);
        $this->assertEmpty($result['errormsg']);
        $discussionid = $result['discussion'];
        // Check the new discussion exists.
        $discussion = \mod_forumng_discussion::get_from_id($discussionid, 0);
        $this->assertEquals($subject, $discussion->get_subject());
        // Check the attachment.
        $attachmentnames = $discussion->get_root_post()->get_attachment_names();
        $this->assertEquals('basepic.jpg', $attachmentnames[0]);
        $showfrom = time();
        $result = add_discussion::add_discussion($forum->id, 0, $group,
                $subject, $message, 0, 1, $showfrom, 1, mod_forumng::CLONE_DIRECT);
        $this->assertTrue($result['success']);
        $this->assertEmpty($result['errormsg']);
        $discussionid = $result['discussion'];
        $discussion = \mod_forumng_discussion::get_from_id($discussionid, 0);
        $this->assertEquals($subject, $discussion->get_subject());
        $this->assertEquals($showfrom, $discussion->get_time_start());
        $this->assertEquals($showfrom, $discussion->is_sticky());
        $rootpost = $discussion->get_root_post();
        $this->assertEquals(0, $rootpost->get_asmoderator());
    }

    /**
     * Using API to create discussion through clone forum.
     *
     * @depends test_mobile_forumng_add_discussion
     * @return void
     */
    public function test_mobile_forumng_add_discussion_clone_forum(): void {

        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_user();
        $generator->enrol_user($student->id, $course->id, 'student');
        $this->setUser($student);

        [, $forum] = $this->prepare_clone_forum($course);

        $discussion = 0;
        $group = -1;
        $subject = 'Test subject';
        $message = 'Test message';
        $filerecord = ['filename' => 'basepic.jpg'];
        $file = self::create_draft_file($filerecord);
        $draftarea = $file->get_itemid();
        $cloneid = $forum->get_course_module_id();

        // Add a new discussion via the WS.
        $result = add_discussion::add_discussion($forum->get_real_forum()->get_id(), $discussion, $group,
                $subject, $message, $draftarea, 0, 0, 0, $cloneid);
        $this->assertTrue($result['success']);
        $this->assertEmpty($result['errormsg']);
        $discussionid = $result['discussion'];
        // Check the new discussion exists.
        $discussion = \mod_forumng_discussion::get_from_id($discussionid, $cloneid);
        $this->assertEquals($subject, $discussion->get_subject());
        // Check the attachment.
        $attachmentnames = $discussion->get_root_post()->get_attachment_names();
        $this->assertEquals('basepic.jpg', $attachmentnames[0]);
        $showfrom = time();
        $result = add_discussion::add_discussion($forum->get_real_forum()->get_id(), 0, $group,
                $subject, $message, 0, 1, $showfrom, 1, $cloneid);
        $this->assertTrue($result['success']);
        $this->assertEmpty($result['errormsg']);
        $discussionid = $result['discussion'];
        $discussion = \mod_forumng_discussion::get_from_id($discussionid, $cloneid);
        $this->assertEquals($subject, $discussion->get_subject());
        $this->assertEquals($showfrom, $discussion->get_time_start());
        $this->assertEquals($showfrom, $discussion->is_sticky());
        $rootpost = $discussion->get_root_post();
        $this->assertEquals(0, $rootpost->get_asmoderator());
    }

    /**
     * Test the reply webservice functionality.
     */
    public function test_mobile_forumng_reply() {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_user();
        $teacher = $generator->create_user();
        $generator->enrol_user($student->id, $course->id, 'student');
        $generator->enrol_user($teacher->id, $course->id, 'teacher');

        $this->setUser($student);
        $forumnggenerator = $generator->get_plugin_generator('mod_forumng');
        $forum = $forumnggenerator->create_instance(['course' => $course->id]);
        $record = [];
        $record['course'] = $course->id;
        $record['forum'] = $forum->id;
        $record['userid'] = $student->id;
        list($discussionid, $replyto) = $forumnggenerator->create_discussion($record);

        $subject = ''; // A change of subject is not required for a reply.
        $message = 'Test reply message';
        $filerecord = ['filename' => 'basepic.jpg'];
        $file = self::create_draft_file($filerecord);
        $draftarea = $file->get_itemid();
        $editing = false;

        // Add a reply via the WS.
        $result = reply::reply($replyto, $subject, $message, $draftarea, $editing, 0, 0, 0, 0, 0, mod_forumng::CLONE_DIRECT);
        $this->assertTrue($result['success']);
        $this->assertEmpty($result['errormsg']);
        $newpostid = $result['post'];
        // Check the new post exists.
        $post = \mod_forumng_post::get_from_id($newpostid, 0);
        $this->assertEquals($subject, $post->get_subject());
        $this->assertEquals($message, $post->get_formatted_message());
        $this->assertEquals(0, $post->is_important());
        $this->assertEquals(0, $post->get_asmoderator());
        // Check the attachment.
        $attachmentnames = $post->get_attachment_names();
        $this->assertEquals('basepic.jpg', $attachmentnames[0]);

        // Add a reply via the WS.
        $subject = 'Test subject 2'; // A change of subject is not required for a reply.
        $message = 'Test reply message 2';
        $filerecord = ['filename' => 'basepic2.jpg'];
        $file = self::create_draft_file($filerecord);
        $draftarea = $file->get_itemid();
        $editing = false;
        $result = reply::reply($replyto, $subject, $message, $draftarea, $editing, 1, 1, 0, 0, 0, mod_forumng::CLONE_DIRECT);
        $this->assertTrue($result['success']);
        $this->assertEmpty($result['errormsg']);
        $newpostid = $result['post'];
        // Check the new post exists.
        $post = \mod_forumng_post::get_from_id($newpostid, 0);
        $this->assertEquals($subject, $post->get_subject());
        $this->assertEquals($message, $post->get_formatted_message());
        $this->assertEquals(0, $post->is_important());
        $this->assertEquals(0, $post->get_asmoderator());
        $attachmentnames = $post->get_attachment_names();
        $this->assertEquals('basepic2.jpg', $attachmentnames[0]);

        // Teacher
        $this->setUser($teacher);
        $filerecord = ['filename' => 'basepic2.jpg'];
        $file = self::create_draft_file($filerecord);
        $draftarea = $file->get_itemid();
        $result = reply::reply($replyto, $subject, $message, $draftarea, $editing, 1,
                1, 0, 0, 0, mod_forumng::CLONE_DIRECT);
        $this->assertTrue($result['success']);
        $this->assertEmpty($result['errormsg']);
        $newpostid = $result['post'];
        // Check the new post exists.
        $post = \mod_forumng_post::get_from_id($newpostid, 0);
        $this->assertEquals($subject, $post->get_subject());
        $this->assertEquals($message, $post->get_formatted_message());
        $this->assertEquals(1, $post->is_important());
        $this->assertEquals(1, $post->get_asmoderator());
        $attachmentnames = $post->get_attachment_names();
        $this->assertEquals('basepic2.jpg', $attachmentnames[0]);
        $editedsubject = 'Edited subject';
        $editedmsg = 'Edited message';
        $result = reply::reply($newpostid, $editedsubject, $editedmsg, $draftarea, 1, 1,
                0, 0, 0, 0, mod_forumng::CLONE_DIRECT);
        $post = \mod_forumng_post::get_from_id($newpostid, 0);
        $this->assertEquals($editedsubject, $post->get_subject());
        $this->assertEquals($editedmsg, $post->get_formatted_message());
        $this->assertEquals(false, $post->is_important());
        $this->assertEquals(1, $post->get_asmoderator());

        $discussion = $post->get_discussion();
        $rootpost = $discussion->get_root_post();
        $time = time();
        $result = reply::reply($rootpost->get_id(), 'Rootpost subject',
                'Rootpost message', 0, 1, 0, 0, 1, 1, $time, mod_forumng::CLONE_DIRECT);
        $post = \mod_forumng_post::get_from_id($rootpost->get_id(), 0);
        $this->assertEquals('Rootpost subject', $post->get_effective_subject());
        $this->assertEquals('Rootpost message', $post->get_formatted_message());
        $this->assertEquals(false, $post->is_important());
        $this->assertEquals(0, $post->get_asmoderator());
        $this->assertEquals($time, $post->get_discussion()->get_time_start());
        $this->assertEquals(1, $post->get_discussion()->is_sticky());

    }

    /**
     * Test the reply webservice functionality on clone forum.
     *
     * @depends test_mobile_forumng_reply
     * @return void
     */
    public function test_mobile_forumng_reply_clone_forum(): void {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_user();
        $teacher = $generator->create_user();
        $generator->enrol_user($student->id, $course->id, 'student');
        $generator->enrol_user($teacher->id, $course->id, 'teacher');

        $this->setUser($student);
        $forumnggenerator = $generator->get_plugin_generator('mod_forumng');

        [$baseforum, $forum] = $this->prepare_clone_forum($course);
        $cloneid = $forum->get_course_module_id();

        $record = [];
        $record['course'] = $course->id;
        $record['forum'] = $baseforum->get_id();
        $record['userid'] = $student->id;
        [, $replyto] = $forumnggenerator->create_discussion($record);

        $subject = ''; // A change of subject is not required for a reply.
        $message = 'Test reply message';
        $filerecord = ['filename' => 'basepic.jpg'];
        $file = self::create_draft_file($filerecord);
        $draftarea = $file->get_itemid();
        $editing = false;

        // Add a reply via the WS.
        $result = reply::reply($replyto, $subject, $message, $draftarea, $editing, 0, 0, 0, 0, 0, $cloneid);
        $this->assertTrue($result['success']);
        $this->assertEmpty($result['errormsg']);
        $newpostid = $result['post'];
        // Check the new post exists.
        $post = \mod_forumng_post::get_from_id($newpostid, $cloneid);
        $this->assertEquals($subject, $post->get_subject());
        $this->assertEquals($message, $post->get_formatted_message());
        $this->assertEquals(0, $post->is_important());
        $this->assertEquals(0, $post->get_asmoderator());
        // Check the attachment.
        $attachmentnames = $post->get_attachment_names();
        $this->assertEquals('basepic.jpg', $attachmentnames[0]);

        // Add a reply via the WS.
        $subject = 'Test subject 2'; // A change of subject is not required for a reply.
        $message = 'Test reply message 2';
        $filerecord = ['filename' => 'basepic2.jpg'];
        $file = self::create_draft_file($filerecord);
        $draftarea = $file->get_itemid();
        $editing = false;
        $result = reply::reply($replyto, $subject, $message, $draftarea, $editing, 1, 1, 0, 0, 0, $cloneid);
        $this->assertTrue($result['success']);
        $this->assertEmpty($result['errormsg']);
        $newpostid = $result['post'];
        // Check the new post exists.
        $post = \mod_forumng_post::get_from_id($newpostid, $cloneid);
        $this->assertEquals($subject, $post->get_subject());
        $this->assertEquals($message, $post->get_formatted_message());
        $this->assertEquals(0, $post->is_important());
        $this->assertEquals(0, $post->get_asmoderator());
        $attachmentnames = $post->get_attachment_names();
        $this->assertEquals('basepic2.jpg', $attachmentnames[0]);

        // Teacher
        $this->setUser($teacher);
        $filerecord = ['filename' => 'basepic2.jpg'];
        $file = self::create_draft_file($filerecord);
        $draftarea = $file->get_itemid();
        $result = reply::reply($replyto, $subject, $message, $draftarea, $editing, 1,
                1, 0, 0, 0, $cloneid);
        $this->assertTrue($result['success']);
        $this->assertEmpty($result['errormsg']);
        $newpostid = $result['post'];
        // Check the new post exists.
        $post = \mod_forumng_post::get_from_id($newpostid, $cloneid);
        $this->assertEquals($subject, $post->get_subject());
        $this->assertEquals($message, $post->get_formatted_message());
        $this->assertEquals(1, $post->is_important());
        $this->assertEquals(1, $post->get_asmoderator());
        $attachmentnames = $post->get_attachment_names();
        $this->assertEquals('basepic2.jpg', $attachmentnames[0]);
        $editedsubject = 'Edited subject';
        $editedmsg = 'Edited message';
        reply::reply($newpostid, $editedsubject, $editedmsg, $draftarea, 1, 1,
                0, 0, 0, 0, $cloneid);
        $post = \mod_forumng_post::get_from_id($newpostid, $cloneid);
        $this->assertEquals($editedsubject, $post->get_subject());
        $this->assertEquals($editedmsg, $post->get_formatted_message());
        $this->assertEquals(false, $post->is_important());
        $this->assertEquals(1, $post->get_asmoderator());

        $discussion = $post->get_discussion();
        $rootpost = $discussion->get_root_post();
        $time = time();
        reply::reply($rootpost->get_id(), 'Rootpost subject',
                'Rootpost message', 0, 1, 0, 0, 1, 1, $time, $cloneid);
        $post = \mod_forumng_post::get_from_id($rootpost->get_id(), $cloneid);
        $this->assertEquals('Rootpost subject', $post->get_effective_subject());
        $this->assertEquals('Rootpost message', $post->get_formatted_message());
        $this->assertEquals(false, $post->is_important());
        $this->assertEquals(0, $post->get_asmoderator());
        $this->assertEquals($time, $post->get_discussion()->get_time_start());
        $this->assertEquals(1, $post->get_discussion()->is_sticky());
    }

    /**
     * Test the lock  webservice functionality.
     */
    public function test_mobile_forumng_lock_discussion() {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_user();
        $teacher = $generator->create_user();
        $generator->enrol_user($student->id, $course->id, 'student');
        $generator->enrol_user($teacher->id, $course->id, 'teacher');
        $this->setUser($teacher);
        $forumnggenerator = $generator->get_plugin_generator('mod_forumng');
        $forum = $forumnggenerator->create_instance(['course' => $course->id]);
        $record = [];
        $record['course'] = $course->id;
        $record['forum'] = $forum->id;
        $record['userid'] = $teacher->id;
        list($discussionid) = $forumnggenerator->create_discussion($record);

        $cloneid = 0;
        $subject = 'The discussion is now closed'; // A change of subject is not required for a reply.
        $message = 'Test lock';
        $filerecord = ['filename' => 'basepic.jpg'];
        $file = self::create_draft_file($filerecord);
        $draftarea = $file->get_itemid();
        $postas = 0;

        $result = lock_discussion::lock_discussion($discussionid, $cloneid, $subject, $message, $draftarea, $postas);
        $this->assertTrue($result['success']);
        $this->assertEmpty($result['errormsg']);
        //Check discussion is locked.
        $discussion = \mod_forumng_discussion::get_from_id($discussionid, $cloneid);
        $bool = $discussion->is_locked();
        $this->assertEquals(true, $bool);
        // Check the attachment.
        $newpostid = $result['post'];
        $post = \mod_forumng_post::get_from_id($newpostid, 0);
        $attachmentnames = $post->get_attachment_names();
        $this->assertEquals('basepic.jpg', $attachmentnames[0]);

        // Check permission
        $this->setUser($student);
        $record = [];
        $record['course'] = $course->id;
        $record['forum'] = $forum->id;
        $record['userid'] = $student->id;
        list($discussionid) = $forumnggenerator->create_discussion($record);
        $result = lock_discussion::lock_discussion($discussionid, $cloneid, $subject, $message, $draftarea, $postas);
        $this->assertFalse($result['success']);
        $this->assertEquals('You do not have permission to manage this discussion.', $result['errormsg']);
    }

    /**
     * Test the lock  webservice functionality for clone forum.
     *
     * @depends test_mobile_forumng_lock_discussion
     */
    public function test_mobile_forumng_lock_discussion_clone_forum(): void {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_user();
        $teacher = $generator->create_user();
        $generator->enrol_user($student->id, $course->id, 'student');
        $generator->enrol_user($teacher->id, $course->id, 'teacher');
        $this->setUser($teacher);
        $forumnggenerator = $generator->get_plugin_generator('mod_forumng');
        [, $forum] = $this->prepare_clone_forum($course);
        $record = [];
        $record['course'] = $course->id;
        $record['forum'] = $forum->get_id();
        $record['userid'] = $teacher->id;
        [$discussionid] = $forumnggenerator->create_discussion($record);

        $cloneid = $forum->get_course_module_id();
        $subject = 'The discussion is now closed'; // A change of subject is not required for a reply.
        $message = 'Test lock';
        $filerecord = ['filename' => 'basepic.jpg'];
        $file = self::create_draft_file($filerecord);
        $draftarea = $file->get_itemid();
        $postas = 0;

        $result = lock_discussion::lock_discussion($discussionid, $cloneid, $subject, $message, $draftarea, $postas);
        $this->assertTrue($result['success']);
        $this->assertEmpty($result['errormsg']);
        //Check discussion is locked.
        $discussion = \mod_forumng_discussion::get_from_id($discussionid, $cloneid);
        $this->assertTrue($discussion->is_locked());
        // Check base discussion is locked.
        $basediscussion = \mod_forumng_discussion::get_from_id($discussionid, mod_forumng::CLONE_DIRECT);
        $this->assertTrue($basediscussion->is_locked());
        // Check the attachment.
        $newpostid = $result['post'];
        $post = \mod_forumng_post::get_from_id($newpostid, $cloneid);
        $attachmentnames = $post->get_attachment_names();
        $this->assertEquals('basepic.jpg', $attachmentnames[0]);

        // Check permission
        $this->setUser($student);
        $record = [];
        $record['course'] = $course->id;
        $record['forum'] = $forum->get_id();
        $record['userid'] = $student->id;
        [$discussionid] = $forumnggenerator->create_discussion($record);
        $result = lock_discussion::lock_discussion($discussionid, $cloneid, $subject, $message, $draftarea, $postas);
        $this->assertFalse($result['success']);
        $this->assertEquals('You do not have permission to manage this discussion.', $result['errormsg']);
    }

    /**
     * Test delete and undelete ws.
     */
    public function test_mobile_forumng_delete_undelete() {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_user();
        $teacher = $generator->create_user();
        $generator->enrol_user($student->id, $course->id, 'student');
        $generator->enrol_user($teacher->id, $course->id, 'teacher');

        $this->setUser($student);
        $forumnggenerator = $generator->get_plugin_generator('mod_forumng');
        $forum = $forumnggenerator->create_instance(['course' => $course->id]);
        $record = [];
        $record['course'] = $course->id;
        $record['forum'] = $forum->id;
        $record['userid'] = $student->id;
        list($discussionid, $replyto) = $forumnggenerator->create_discussion($record);

        $subject = ''; // A change of subject is not required for a reply.
        $message = 'Test reply message';
        $filerecord = ['filename' => 'basepic.jpg'];
        $file = self::create_draft_file($filerecord);
        $draftarea = $file->get_itemid();
        $editing = false;

        // Add a reply via the WS.
        $result = reply::reply($replyto, $subject, $message, $draftarea, $editing, 0,
                0, 0, 0, 0, mod_forumng::CLONE_DIRECT);
        $this->assertTrue($result['success']);
        $this->assertEmpty($result['errormsg']);
        $newpostid = $result['post'];
        // Check the new post exists.
        $result = delete_post::delete_post($newpostid, mod_forumng::CLONE_DIRECT);
        $post = \mod_forumng_post::get_from_id($newpostid, 0);
        $this->assertTrue($result->success);
        $this->assertEmpty($result->message);
        $this->assertNotEmpty($result->postinfo->deletedtime);
        $this->assertEquals($result->postinfo->deletedtime, userdate($post->get_deleted()));

        $result = undelete_post::undelete_post($newpostid, mod_forumng::CLONE_DIRECT);
        $this->assertFalse($result->success);
        $this->assertEquals("You don't have permission to edit this kind of post.", $result->message);
        $this->setUser($teacher);
        $result = undelete_post::undelete_post($newpostid, mod_forumng::CLONE_DIRECT);
        $this->assertTrue($result->success);
        $this->assertEmpty($result->message);
        $this->assertEquals(0, $result->postinfo->deletedtime);
    }

    /**
     * Test delete and undelete ws for clone forum.
     *
     * @depends test_mobile_forumng_delete_undelete
     */
    public function test_mobile_forumng_delete_undelete_clone_forum(): void {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_user();
        $teacher = $generator->create_user();
        $generator->enrol_user($student->id, $course->id, 'student');
        $generator->enrol_user($teacher->id, $course->id, 'teacher');

        $this->setUser($student);
        $forumnggenerator = $generator->get_plugin_generator('mod_forumng');
        [$forum, $cloneforum] = $this->prepare_clone_forum($course);
        $cloneid = $forum->get_course_module_id();
        $record = [];
        $record['course'] = $course->id;
        $record['forum'] = $forum->get_id();
        $record['userid'] = $student->id;
        [, $replyto] = $forumnggenerator->create_discussion($record);

        $subject = ''; // A change of subject is not required for a reply.
        $message = 'Test reply message';
        $filerecord = ['filename' => 'basepic.jpg'];
        $file = self::create_draft_file($filerecord);
        $draftarea = $file->get_itemid();
        $editing = false;

        // Add a reply via the WS clone forum.
        $result = reply::reply($replyto, $subject, $message, $draftarea, $editing, 0,
                0, 0, 0, 0, $cloneid);
        $this->assertTrue($result['success']);
        $this->assertEmpty($result['errormsg']);
        $newpostid = $result['post'];
        // Check the new post exists.
        $result = delete_post::delete_post($newpostid, $cloneid);
        $post = \mod_forumng_post::get_from_id($newpostid, $cloneid);
        $this->assertTrue($result->success);
        $this->assertEmpty($result->message);
        $this->assertNotEmpty($result->postinfo->deletedtime);
        $this->assertEquals($result->postinfo->deletedtime, userdate($post->get_deleted()));
        // Check this post on clone forum.
        $postfromclone = \mod_forumng_post::get_from_id($newpostid, $cloneforum->get_course_module_id());
        $this->assertEquals($result->postinfo->deletedtime, userdate($postfromclone->get_deleted()));

        $result = undelete_post::undelete_post($newpostid, $cloneid);
        $this->assertFalse($result->success);
        $this->assertEquals("You don't have permission to edit this kind of post.", $result->message);
        $this->setUser($teacher);
        $result = undelete_post::undelete_post($newpostid, $cloneid);
        $this->assertTrue($result->success);
        $this->assertEmpty($result->message);
        $this->assertEquals(0, $result->postinfo->deletedtime);
    }

    /**
     * Test the mark_read web service.
     */
    public function test_mobile_forumng_mark_read() {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student1 = $generator->create_user();
        $student2 = $generator->create_user();
        $generator->enrol_user($student1->id, $course->id, 'student');
        $generator->enrol_user($student2->id, $course->id, 'student');
        $this->setUser($student1);
        $forumnggenerator = $generator->get_plugin_generator('mod_forumng');
        $forum = $forumnggenerator->create_instance(['course' => $course->id]);
        list($discussionid, $postid) = $forumnggenerator->create_discussion(['forum' => $forum->id, 'userid' => $student1->id]);
        // Note student1 cannot mark own posts as read, so switch to student2 who has not yet seen this forum.
        $this->setUser($student2);
        // Make student2 manual mark as read.
        set_user_preference('forumng_manualmark', 1, $student2->id);
        // Check there is unread post.
        $post = \mod_forumng_post::get_from_id($postid, 0);
        $this->assertTrue($post->is_unread());

        // Check the mark_read WS.
        $result = mark_read::mark_read(0, $postid);
        $this->assertTrue($result['success']);
        $this->assertEmpty($result['errormsg']);
        // Note cannot just use $post->is_unread() here as it is not updated, even though the database is.
        // So re-fetch the discussion object to check the WS result.
        $discussion = \mod_forumng_discussion::get_from_id($discussionid, 0);
        $this->assertEquals(1, $discussion->get_num_posts());
        $this->assertEquals(0, $discussion->get_num_unread_posts());
    }

    /**
     * Test mark all posts read webservice.
     */
    public function test_mobile_mark_all_posts_read() {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student1 = $generator->create_user();
        $student2 = $generator->create_user();
        $generator->enrol_user($student1->id, $course->id, 'student');
        $generator->enrol_user($student2->id, $course->id, 'student');
        $forumnggenerator = $generator->get_plugin_generator('mod_forumng');
        $forum = $forumnggenerator->create_instance(['course' => $course->id]);
        list($discussion1id, $post1id) = $forumnggenerator->create_discussion(['forum' => $forum->id, 'userid' => $student1->id]);
        list($discussion3id, $post3id) = $forumnggenerator->create_discussion(['forum' => $forum->id, 'userid' => $student1->id]);
        list($discussion2id, $post2id) = $forumnggenerator->create_discussion(['forum' => $forum->id, 'userid' => $student2->id]);
        [$discussion4id, $post4id] = $forumnggenerator->create_discussion(['forum' => $forum->id, 'userid' => $student1->id]);

        set_user_preference('forumng_manualmark', 1, $student1->id);
        set_user_preference('forumng_manualmark', 1, $student2->id);
        $this->setUser($student1);
        // Check there is unread post.
        $this->assertTrue(\mod_forumng_post::get_from_id($post2id, mod_forumng::CLONE_DIRECT)->is_unread());

        $this->setUser($student2);
        // Check there is unread post.
        $this->assertTrue(\mod_forumng_post::get_from_id($post1id, mod_forumng::CLONE_DIRECT)->is_unread());
        $this->assertTrue(\mod_forumng_post::get_from_id($post3id, mod_forumng::CLONE_DIRECT)->is_unread());
        $this->assertTrue(\mod_forumng_post::get_from_id($post4id, mod_forumng::CLONE_DIRECT)->is_unread());

        // Mark unread discussion id.
        mark_all_post_read::mark_all_post_read($forum->cmid, mod_forumng::CLONE_DIRECT, -1, $discussion4id);
        $this->assertTrue(\mod_forumng_post::get_from_id($post1id, mod_forumng::CLONE_DIRECT)->is_unread());
        $this->assertTrue(\mod_forumng_post::get_from_id($post3id, mod_forumng::CLONE_DIRECT)->is_unread());
        $this->assertFalse(\mod_forumng_post::get_from_id($post4id, mod_forumng::CLONE_DIRECT)->is_unread());

        // Mark all post read for User 2.
        mark_all_post_read::mark_all_post_read($forum->cmid, mod_forumng::CLONE_DIRECT, -1);
        // Check read post.
        $this->assertFalse(\mod_forumng_post::get_from_id($post1id, mod_forumng::CLONE_DIRECT)->is_unread());
        $this->assertFalse(\mod_forumng_post::get_from_id($post2id, mod_forumng::CLONE_DIRECT)->is_unread());
        $this->assertFalse(\mod_forumng_post::get_from_id($post3id, mod_forumng::CLONE_DIRECT)->is_unread());
    }

    /**
     * Test mark all posts read webservice for clone forum.
     *
     * @depends test_mobile_mark_all_posts_read
     */
    public function test_mobile_mark_all_posts_read_clone_forum(): void {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student1 = $generator->create_user();
        $student2 = $generator->create_user();
        $generator->enrol_user($student1->id, $course->id, 'student');
        $generator->enrol_user($student2->id, $course->id, 'student');
        $forumnggenerator = $generator->get_plugin_generator('mod_forumng');
        [$forum, $cloneforum] = $this->prepare_clone_forum($course);
        $cloneid = $cloneforum->get_course_module_id();
        [, $post1id] = $forumnggenerator->create_discussion(['forum' => $forum->get_id(), 'userid' => $student1->id]);
        [, $post3id] = $forumnggenerator->create_discussion(['forum' => $forum->get_id(), 'userid' => $student1->id]);
        [, $post2id] = $forumnggenerator->create_discussion(['forum' => $forum->get_id(), 'userid' => $student2->id]);
        [$discussion4id, $post4id] = $forumnggenerator->create_discussion(['forum' => $forum->get_id(), 'userid' => $student1->id]);

        set_user_preference('forumng_manualmark', 1, $student1->id);
        set_user_preference('forumng_manualmark', 1, $student2->id);
        $this->setUser($student1);
        // Check there is unread post.
        $this->assertTrue(\mod_forumng_post::get_from_id($post2id, $cloneid)->is_unread());

        $this->setUser($student2);
        // Check there is unread post.
        $this->assertTrue(\mod_forumng_post::get_from_id($post1id, $cloneid)->is_unread());
        $this->assertTrue(\mod_forumng_post::get_from_id($post3id, $cloneid)->is_unread());
        $this->assertTrue(\mod_forumng_post::get_from_id($post4id, $cloneid)->is_unread());

        // Mark unread discussion id.
        mark_all_post_read::mark_all_post_read($cloneid, $cloneid, -1, $discussion4id);
        $this->assertTrue(\mod_forumng_post::get_from_id($post1id, $cloneid)->is_unread());
        $this->assertTrue(\mod_forumng_post::get_from_id($post3id, $cloneid)->is_unread());
        $this->assertFalse(\mod_forumng_post::get_from_id($post4id, $cloneid)->is_unread());

        // Mark all post read for User 2.
        // Note: on mobile we won't change cmid to real forum.
        mark_all_post_read::mark_all_post_read($cloneid, $cloneid, -1);
        // Check read post.
        $this->assertFalse(\mod_forumng_post::get_from_id($post1id, $cloneid)->is_unread());
        $this->assertFalse(\mod_forumng_post::get_from_id($post2id, $cloneid)->is_unread());
        $this->assertFalse(\mod_forumng_post::get_from_id($post3id, $cloneid)->is_unread());
    }

    /**
     * Test the draft webservice functionality.
     */
    public function test_mobile_forumng_add_draft() {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_user();
        $generator->enrol_user($student->id, $course->id, 'student');
        $this->setUser($student);
        $forumnggenerator = $generator->get_plugin_generator('mod_forumng');
        $forum = $forumnggenerator->create_instance(['course' => $course->id]);
        $group = -1;
        $subject = 'Test subject';
        $message = 'Test message';
        $filerecord = ['filename' => 'basepic.jpg'];
        $file = self::create_draft_file($filerecord);
        $draftarea = $file->get_itemid();

        // Add a new draft via the WS.
        $result = add_draft::add_draft($forum->id, 0, $group, 0,
            $subject, $message, $draftarea, 0, 0, 0, false,false, mod_forumng::CLONE_DIRECT);
        $this->assertTrue($result['success']);
        $this->assertEmpty($result['errormsg']);
        $this->assertStringContainsString(\mod_forumng_utils::display_date(time()), $result['successmsg']);
        $drafid = $result['draft'];
        // Check the new draft exists.
        $draft = \mod_forumng_draft::get_from_id($drafid, 0);
        $this->assertEquals($subject, $draft->get_subject());
        // Edit draft vis the WS.
        $newsubject = 'Test new subject';
        $newresult = add_draft::add_draft($forum->id, $drafid, $group, 0,
            $newsubject, $message, $draftarea, 0, true, 0, false,false, mod_forumng::CLONE_DIRECT);
        $draft = \mod_forumng_draft::get_from_id($newresult['draft'], 0);
        $this->assertEquals($newsubject, $draft->get_subject());
        $this->assertEquals($newresult['draft'], $result['draft']);
        $this->assertEquals(true, $draft->get_options()->sticky);
         //Check the attachment.
        $forumng = \mod_forumng::get_from_id($draft->get_forumng_id(), 0);
        $filecontext = $forumng->get_context(true);
        $fs = get_file_storage();
        foreach ($fs->get_area_files($filecontext->id, 'mod_forumng', 'draft',
            $draft->get_id(), 'filename', false) as $file) {

            $attachments[] = $file->get_filename();
        }
        $this->assertEquals('basepic.jpg', $attachments[0]);
        // Delete draft via the WS
        $deletedraft = delete_draft::delete_draft($newresult['draft'], mod_forumng::CLONE_DIRECT);
        $this->assertTrue($deletedraft['success']);
        $this->assertEmpty($deletedraft['errormsg']);
        // Add draft reply via the WS
        $subject = 'Test subject reply';
        $message = 'Tes message reply';
        $discussion = $forumnggenerator->create_discussion(['course' => $course, 'forum' => $forum->id, 'userid' => $student->id]);
        $post = $forumnggenerator->create_post(array('discussionid' => $discussion[0], 'parentpostid' => $discussion[1], 'userid' => $student->id));
        $result = add_draft::add_draft($forum->id, 0, $group, $post->parentpostid,
            $subject, $message, $draftarea, 0, true, 0, false,false, mod_forumng::CLONE_DIRECT);
        $this->assertTrue($result['success']);
        $this->assertEmpty($result['errormsg']);
        $this->assertStringContainsString(\mod_forumng_utils::display_date(time()), $result['successmsg']);
        // Edit draft reply vis the WS.
        $drafid = $result['draft'];
        $newsubject = 'Test new subject reply';
        $newresult = add_draft::add_draft($forum->id, $drafid, $group, $post->parentpostid,
            $newsubject, $message, $draftarea, 0, 0, 0, false,true, mod_forumng::CLONE_DIRECT);
        $draft = \mod_forumng_draft::get_from_id($newresult['draft'], 0);
        $this->assertEquals($newsubject, $draft->get_subject());
        $this->assertEquals($newresult['draft'], $result['draft']);
        $this->assertEquals(true, $draft->get_options()->setimportant);
        // Delete draft via the WS
        $deletedraft = delete_draft::delete_draft($newresult['draft'], mod_forumng::CLONE_DIRECT);
        $this->assertTrue($deletedraft['success']);
        $this->assertEmpty($deletedraft['errormsg']);
    }

    /**
     * Test the draft webservice functionality clone forum.
     *
     * @depends test_mobile_forumng_add_draft
     */
    public function test_mobile_forumng_add_draft_clone_forum(): void {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_user();
        $generator->enrol_user($student->id, $course->id, 'student');
        $this->setUser($student);
        $forumnggenerator = $generator->get_plugin_generator('mod_forumng');
        [$forum, $cloneforum] = $this->prepare_clone_forum($course);
        $cloneid = $cloneforum->get_course_module_id();
        $group = -1;
        $subject = 'Test subject';
        $message = 'Test message';
        $filerecord = ['filename' => 'basepic.jpg'];
        $file = self::create_draft_file($filerecord);
        $draftarea = $file->get_itemid();

        // Add a new draft via the WS.
        $result = add_draft::add_draft($forum->get_id(), 0, $group, 0,
                $subject, $message, $draftarea, 0, 0, 0, false,false, $cloneid);
        $this->assertTrue($result['success']);
        $this->assertEmpty($result['errormsg']);
        $this->assertStringContainsString(\mod_forumng_utils::display_date(time()), $result['successmsg']);
        $drafid = $result['draft'];
        // Check the new draft exists.
        $draft = \mod_forumng_draft::get_from_id($drafid, 0);
        $this->assertEquals($subject, $draft->get_subject());
        // Edit draft vis the WS.
        $newsubject = 'Test new subject';
        $newresult = add_draft::add_draft($forum->get_id(), $drafid, $group, 0,
                $newsubject, $message, $draftarea, 0, true, 0, false,false, $cloneid);
        $draft = \mod_forumng_draft::get_from_id($newresult['draft']);
        $this->assertEquals($newsubject, $draft->get_subject());
        $this->assertEquals($newresult['draft'], $result['draft']);
        $this->assertEquals(true, $draft->get_options()->sticky);
        //Check the attachment.
        $forumng = \mod_forumng::get_from_id($draft->get_forumng_id(), $cloneid);
        $filecontext = $forumng->get_context(true);
        $fs = get_file_storage();
        foreach ($fs->get_area_files($filecontext->id, 'mod_forumng', 'draft',
                $draft->get_id(), 'filename', false) as $file) {

            $attachments[] = $file->get_filename();
        }
        $this->assertEquals('basepic.jpg', $attachments[0]);
        // Delete draft via the WS
        $deletedraft = delete_draft::delete_draft($newresult['draft'], $cloneid);
        $this->assertTrue($deletedraft['success']);
        $this->assertEmpty($deletedraft['errormsg']);
        // Add draft reply via the WS
        $subject = 'Test subject reply';
        $message = 'Tes message reply';
        $discussion = $forumnggenerator->create_discussion(['course' => $course, 'forum' => $forum->get_id(), 'userid' => $student->id]);
        $post = $forumnggenerator->create_post([
                'discussionid' => $discussion[0],
                'parentpostid' => $discussion[1],
                'userid' => $student->id,
        ]);
        $result = add_draft::add_draft($forum->get_id(), 0, $group, $post->parentpostid,
                $subject, $message, $draftarea, 0, true, 0, false,false, $cloneid);
        $this->assertTrue($result['success']);
        $this->assertEmpty($result['errormsg']);
        $this->assertStringContainsString(\mod_forumng_utils::display_date(time()), $result['successmsg']);
        // Edit draft reply vis the WS.
        $drafid = $result['draft'];
        $newsubject = 'Test new subject reply';
        $newresult = add_draft::add_draft($forum->get_id(), $drafid, $group, $post->parentpostid,
                $newsubject, $message, $draftarea, 0, 0, 0, false,true, $cloneid);
        $draft = \mod_forumng_draft::get_from_id($newresult['draft']);
        $this->assertEquals($newsubject, $draft->get_subject());
        $this->assertEquals($newresult['draft'], $result['draft']);
        $this->assertEquals(true, $draft->get_options()->setimportant);
        // Delete draft via the WS
        $deletedraft = delete_draft::delete_draft($newresult['draft'], $cloneid);
        $this->assertTrue($deletedraft['success']);
        $this->assertEmpty($deletedraft['errormsg']);
    }

    /**
     * Test the get_discussion webservice functionality.
     */
    public function test_mobile_forumng_get_discussion() {
        global $CFG;

        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_user();
        $generator->enrol_user($student->id, $course->id, 'student');
        $this->setUser($student);
        $forumnggenerator = $generator->get_plugin_generator('mod_forumng');
        $forum = $forumnggenerator->create_instance(['course' => $course->id]);
        $cm = get_coursemodule_from_instance('forumng', $forum->id);

        // Add discussions.
        $record = [];
        $record['course'] = $course->id;
        $record['forum'] = $forum->id;
        $record['userid'] = $student->id;
        $record['subject'] = 'Test subject';
        list($discussionid) = $forumnggenerator->create_discussion($record);
        $result = get_discussion::get_discussion($discussionid);
        $this->assertEquals($result['subject'], 'Test subject');
    }

    /**
     * Helper function to create draft files
     *
     * @param  array  $filedata data for the file record (to not use defaults)
     * @return stored_file the stored file instance
     */
    public static function create_draft_file($filedata = array()) {
        global $USER;

        $fs = get_file_storage();

        $filerecord = array(
                'component' => 'user',
                'filearea'  => 'draft',
                'itemid'    => isset($filedata['itemid']) ? $filedata['itemid'] : file_get_unused_draft_itemid(),
                'author'    => isset($filedata['author']) ? $filedata['author'] : fullname($USER),
                'filepath'  => isset($filedata['filepath']) ? $filedata['filepath'] : '/',
                'filename'  => isset($filedata['filename']) ? $filedata['filename'] : 'file.txt',
        );

        if (isset($filedata['contextid'])) {
            $filerecord['contextid'] = $filedata['contextid'];
        } else {
            $usercontext = \context_user::instance($USER->id);
            $filerecord['contextid'] = $usercontext->id;
        }
        $source = isset($filedata['source']) ? $filedata['source'] : serialize((object)array('source' => 'From string'));
        $content = isset($filedata['content']) ? $filedata['content'] : 'some content here';

        $file = $fs->create_file_from_string($filerecord, $content);
        $file->set_source($source);

        return $file;
    }

    /**
     * Test delete discussion mobile service.
     */
    public function test_mobile_delete_discussion() {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student1 = $generator->create_user();
        $student2 = $generator->create_user();
        $generator->enrol_user($student1->id, $course->id, 'student');
        $generator->enrol_user($student2->id, $course->id, 'student');
        $forumnggenerator = $generator->get_plugin_generator('mod_forumng');
        $forum = $forumnggenerator->create_instance(['course' => $course->id]);
        list($discussion1id, $post1id) = $forumnggenerator->create_discussion(['forum' => $forum->id, 'userid' => $student1->id]);
        $discussion1 = \mod_forumng_discussion::get_from_id($discussion1id, 0);
        $this->setUser($student1);

        $result = delete_discussion::delete_discussion($discussion1id, 0, false);
        $this->assertTrue($result->result);
        $this->assertEmpty($result->errormsg);

        // Student can't undelete.
        $result2 = delete_discussion::delete_discussion($discussion1id, 0, true);
        $this->assertFalse($result2->result);
        $this->assertNotEmpty($result2->errormsg);

        $this->setUser($student2);
        $result = delete_discussion::delete_discussion($discussion1id, 0, $discussion1->is_deleted());
        $this->assertFalse($result->result);
        $this->assertNotEmpty($result->errormsg);
    }

    /**
     * Test delete discussion mobile service.
     *
     * @depends test_mobile_delete_discussion
     */
    public function test_mobile_delete_discussion_clone_forum(): void {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student1 = $generator->create_user();
        $student2 = $generator->create_user();
        $generator->enrol_user($student1->id, $course->id, 'student');
        $generator->enrol_user($student2->id, $course->id, 'student');
        $forumnggenerator = $generator->get_plugin_generator('mod_forumng');
        [$forum, $cloneforum] = $this->prepare_clone_forum($course);
        $cloneid = $cloneforum->get_course_module_id();
        [$discussion1id,] = $forumnggenerator->create_discussion(['forum' => $forum->get_id(), 'userid' => $student1->id]);
        $discussion1 = \mod_forumng_discussion::get_from_id($discussion1id, $cloneid);
        $this->setUser($student1);

        $result = delete_discussion::delete_discussion($discussion1id, $cloneid, false);
        $this->assertTrue($result->result);
        $this->assertEmpty($result->errormsg);

        // Student can't undelete.
        $result2 = delete_discussion::delete_discussion($discussion1id, $cloneid, true);
        $this->assertFalse($result2->result);
        $this->assertNotEmpty($result2->errormsg);

        $this->setUser($student2);
        $result = delete_discussion::delete_discussion($discussion1id, $cloneid, $discussion1->is_deleted());
        $this->assertFalse($result->result);
        $this->assertNotEmpty($result->errormsg);
    }
}
