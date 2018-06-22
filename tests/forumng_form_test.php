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
require_once($CFG->dirroot . '/mod/forumng/mod_forumng.php');
require_once($CFG->dirroot . '/mod/forumng/mod_forumng_post.php');

/**
 * Class mod_forumng_form_testcase
 */
class mod_forumng_form_testcase extends forumng_test_lib {
    /**
     * Set up for form test
     */
    protected function setUp() {
        $this->resetAfterTest(true);
        $this->setAdminUser();
    }

    /**
     * Test get post fragment field, check form only have message field and data is fully prepared
     * in edit mode.
     */
    public function test_get_post_fragment_form() {
        global $USER, $PAGE;
        $PAGE->set_url('/');

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_forumng');

        // Create new course.
        $course = $this->get_new_course();

        // Create new forum.
        $forum = $this->get_new_forumng($course->id, array('name' => 'ForumNG 1', 'introduction' => 'Sample introduction'));

        // Create new discussion.
        $discussion = $generator->create_discussion(array('course' => $course, 'forum' => $forum->get_id(), 'userid' => $USER->id));

        // Create new post for discussion.
        $post1 = $generator->create_post(array(
                'discussionid' => $discussion[0],
                'parentpostid' => $discussion[1],
                'userid' => $USER->id)
        );

        $formhtml = mod_forumng_output_fragment_postform(['replyto' => $post1->id]);

        // Check fields other than message field are hidden or not rendered.
        $this->assertContains('input id="id_subject" name="subject" type="hidden"', $formhtml);
        $this->assertContains('input name="attachments" type="hidden" value="0"', $formhtml);
        $this->assertContains('input name="asmoderator" type="hidden" value="0"', $formhtml);
        $this->assertContains('name="replyto" type="hidden" value="' . $post1->id . '"', $formhtml);
        $this->assertNotContains('setimportant', $formhtml);

        $formhtml = mod_forumng_output_fragment_postform(['edit' => $post1->id]);

        // Check post data is filled to form correctly.
        $this->assertContains('input id="id_subject" name="subject" type="hidden" value="Forum post subject 1"', $formhtml);
        $this->assertContains('input name="p" type="hidden" value="' . $post1->id . '"', $formhtml);
        $this->assertContains(
            'textarea id="id_message' . $post1->id . '" name="message[text]" class="form-control" rows="30" cols="50" spellcheck="true" >&lt;p&gt;Forum message post 1&lt;/p&gt;',
            $formhtml);
    }
}
