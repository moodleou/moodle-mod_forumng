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
 * PHPUnit ForumNG ipud tests.
 *
 * @package forumngtype_ipud
 * @copyright 2019 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/forumng/tests/forumng_test_lib.php');
require_once($CFG->dirroot . '/mod/forumng/type/forumngtype.php');
require_once($CFG->dirroot . '/mod/forumng/type/ipud/forumngtype_ipud.php');

class forumngtype_ipud_testcase extends forumng_test_lib {

    /**
     * Test get first level posts with its replies.
     */
    public function test_get_reply_options() {
        global $USER;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Create the generator object for ForumNG.
        $generator = self::getDataGenerator()->get_plugin_generator('mod_forumng');

        // Create course.
        $course = self::getDataGenerator()->create_course(array('shortname' => 'Course 1'));

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
                'message' => 'Reply 1',
            )
        );
        $reply2 = $generator->create_post(
            array(
                'discussionid' => $discussion[0],
                'parentpostid' => $discussion[1],
                'userid' => $USER->id,
                'message' => 'Reply 2',
            )
        );
        $reply3 = $generator->create_post(
            array(
                'discussionid' => $discussion[0],
                'parentpostid' => $discussion[1],
                'userid' => $USER->id,
                'message' => 'Reply 3',
                'important' => true,
            )
        );
        // Create reply for Reply 1.
        $generator->create_post(
            array(
                'discussionid' => $discussion[0],
                'parentpostid' => $reply1->id,
                'userid' => $USER->id,
                'message' => 'Reply 1.1'
            )
        );
        // Create reply for Reply 3.
        $reply31 = $generator->create_post(
            array(
                'discussionid' => $discussion[0],
                'parentpostid' => $reply3->id,
                'userid' => $USER->id,
                'message' => 'Reply 3.1'
            )
        );
        $reply2 = mod_forumng_post::get_from_id($reply2->id, 0);
        $reply3 = mod_forumng_post::get_from_id($reply3->id, 0);
        $reply31 = mod_forumng_post::get_from_id($reply31->id, 0);

        $discussion = mod_forumng_discussion::get_from_id($discussion[0], 0);

        $ipud = new \forumngtype_ipud();

        // First check new reply.
        $options = $ipud->get_reply_options(true, false, $discussion);
        $this->assertFalse($options['markimportant']);

        // Check editing existing post.
        $options = $ipud->get_reply_options(true, false, $discussion, $reply3);
        $this->assertNotFalse($options['markimportant']);
        $options = $ipud->get_reply_options(true, false, $discussion, $reply31);
        $this->assertFalse($options['markimportant']);

        // Check when important post is deleted.
        $reply3->delete(); // Important post should still be returned.
        $discussion = mod_forumng_discussion::get_from_id($discussion->get_id(), 0);
        $options = $ipud->get_reply_options(true, false, $discussion);
        $this->assertFalse($options['markimportant']);

        // Check when important post reply is deleted.
        $reply31->delete(); // Important post not be returned as itself and all replies deleted.
        $discussion = mod_forumng_discussion::get_from_id($discussion->get_id(), 0);
        $options = $ipud->get_reply_options(true, false, $discussion);
        $this->assertNotFalse($options['markimportant']);

        // Check sub-reply.
        $options = $ipud->get_reply_options(true, false, $discussion, null, $reply2);
        $this->assertFalse($options['markimportant']);
    }

}
