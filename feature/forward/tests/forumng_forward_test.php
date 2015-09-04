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
 * PHPUnit ForumNG forward feature tests.
 *
 * @package mod_forumng
 * @copyright 2015 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/forumng/tests/forumng_test_lib.php');

class mod_forumng_forward_testcase  extends forumng_test_lib {

    protected $helper;

    public function setUp() {
        $helper = new stdClass();

        // Catch all messages.
        $helper->mailsink = $this->redirectEmails();

        $messages = $helper->mailsink->get_messages();
        $this->assertEquals(0, count($messages));

        $this->helper = $helper;
    }

    public function tearDown() {
        $this->helper->mailsink->clear();
        $this->helper->mailsink->close();
    }

    /**
     * Tests email sending.
     */
    public function test_forward() {
        global $USER;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->get_new_course();
        $forum = $this->get_new_forumng($course->id);
        $discussion = $this->get_new_discussion($forum, array('userid' => $USER->id));

        $postids = array($discussion->get_root_post()->get_id());

        $forward = new \forumngfeature_forward\post_selector();
        $forward->printout = false;

        $formdata = (object) array(
                'email' => 'example@example.com',
                'message' => array(
                        'text' => 'test',
                        'format' => FORMAT_HTML
                ),
                'subject' => 'unit test'
        );

        $forward->apply($discussion, true, $postids, $formdata);

        // Now check the results in the message sink.
        $messages = $this->helper->mailsink->get_messages();

        // There should be the expected number of messages.
        $this->assertEquals(1, count($messages));

        $formdata = (object) array(
                'email' => 'example@example.com;example2@example.com',
                'message' => array(
                        'text' => 'test',
                        'format' => FORMAT_HTML
                ),
                'ccme' => true,
                'subject' => 'unit test'
        );

        $forward->apply($discussion, true, $postids, $formdata);

        // Now check the results in the message sink.
        $messages = $this->helper->mailsink->get_messages();

        // There should be the expected number of messages.
        $this->assertEquals(4, count($messages));

    }
}
