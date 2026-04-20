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
 * Unit tests for hook_callbacks class.
 *
 * @package mod_forumng
 * @copyright 2025 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/forumng/tests/forumng_test_lib.php');
require_once($CFG->dirroot . '/mod/forumng/classes/hook_callbacks.php');
use mod_forumng\hook_callbacks as hook_callbacks;

/**
 * Test cases for hook_callbacks class.
 */
class hook_callbacks_test extends forumng_test_lib {

    /**
     * @var stdClass Course object.
     */
    private $course;

    /**
     * @var mod_forumng ForumNG instance.
     */
    private $forum;

    /**
     * @var stdClass User object.
     */
    private $user;

    /**
     * Set up test data.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();

        // Create test course.
        $this->course = $this->get_new_course();

        // Create test user.
        $this->user = $this->get_new_user('student', $this->course->id);

        // Create test forum.
        $this->forum = $this->get_new_forumng($this->course->id);
    }

    /**
     * Test activity_search_info method.
     */
    public function test_activity_search_info() {
        // Test with ForumNG pages - should enable search.
        $hook = $this->create_activity_search_hook('mod-forumng-view');
        hook_callbacks::activity_search_info($hook);
        $this->assertTrue($hook->can_search());
        $this->assertEquals(get_string('searchthisforum', 'forumng'), $hook->get_label());

        $hook = $this->create_activity_search_hook('mod-forumng-discuss');
        hook_callbacks::activity_search_info($hook);
        $this->assertTrue($hook->can_search());

        // Test with non-ForumNG page - should not enable search.
        $hook = $this->create_activity_search_hook('mod-forum-view');
        hook_callbacks::activity_search_info($hook);
        $this->assertFalse($hook->can_search());
    }

    /**
     * Test before_prompt_generation method.
     */
    public function test_before_prompt_generation() {
        // Create a discussion with posts.
        $discussion = $this->get_new_discussion($this->forum, [
            'userid' => $this->user->id,
            'subject' => 'Test Discussion',
            'message' => 'This is <b>test</b> content.'
        ]);

        $this->get_new_post($discussion, [
            'userid' => $this->user->id,
            'subject' => 'Reply',
            'message' => 'Reply with <i>italic</i>.'
        ]);

        $context = \context_module::instance($this->forum->get_course_module()->id);

        // Test with valid discussion ID - should extract and clean content.
        $prompt = 'Prompt discussionidforpromptai:' . $discussion->get_id();
        $hook = $this->create_prompt_hook($context->id, $prompt);
        hook_callbacks::before_prompt_generation($hook);
        $newprompt = $hook->get_prompt();
        $this->assertNotEquals($prompt, $newprompt);
        $this->assertStringContainsString('Test Discussion', $newprompt);
        $this->assertStringContainsString('Reply', $newprompt);
        $this->assertStringNotContainsString('<b>', $newprompt);
        $this->assertStringNotContainsString('<i>', $newprompt);

        // Test without discussion ID - should not change.
        $prompt = 'Prompt without ID';
        $hook = $this->create_prompt_hook($context->id, $prompt);
        hook_callbacks::before_prompt_generation($hook);
        $this->assertEquals($prompt, $hook->get_prompt());

        // Test with invalid discussion ID - should not change.
        $prompt = 'Prompt discussionidforpromptai:99999';
        $hook = $this->create_prompt_hook($context->id, $prompt);
        hook_callbacks::before_prompt_generation($hook);
        $this->assertEquals($prompt, $hook->get_prompt());

        // Test with non-ForumNG context - should not change.
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $this->course->id]);
        $assigncontext = \context_module::instance($assign->cmid);
        $prompt = 'Prompt discussionidforpromptai:123';
        $hook = $this->create_prompt_hook($assigncontext->id, $prompt);
        hook_callbacks::before_prompt_generation($hook);
        $this->assertEquals($prompt, $hook->get_prompt());

        // Test with invalid context - should not change.
        $hook = $this->create_prompt_hook(99999, $prompt);
        hook_callbacks::before_prompt_generation($hook);
        $this->assertEquals($prompt, $hook->get_prompt());
    }

    /**
     * Test get_discussion_id_from_prompt method.
     */
    public function test_get_discussion_id_from_prompt() {
        $reflection = new ReflectionClass('mod_forumng\hook_callbacks');
        $method = $reflection->getMethod('get_discussion_id_from_prompt');

        // Test with valid ID.
        $result = $method->invokeArgs(null, ['Text discussionidforpromptai:12345 here']);
        $this->assertEquals(12345, $result);

        // Test without ID - should return null.
        $result = $method->invokeArgs(null, ['No ID here']);
        $this->assertNull($result);

        // Test with malformed patterns - should return null.
        $this->assertNull($method->invokeArgs(null, ['discussionidforpromptai:']));
        $this->assertNull($method->invokeArgs(null, ['discussionidforpromptai:abc']));
        $this->assertNull($method->invokeArgs(null, ['discussionidforpromptai: 123']));
    }

    /**
     * Test get_discussion_by_id method.
     */
    public function test_get_discussion_by_id() {
        $reflection = new ReflectionClass('mod_forumng\hook_callbacks');
        $method = $reflection->getMethod('get_discussion_by_id');

        // Test with valid ID.
        $discussion = $this->get_new_discussion($this->forum, [
            'userid' => $this->user->id,
            'subject' => 'Test',
            'message' => 'Content'
        ]);
        $result = $method->invokeArgs(null, [$discussion->get_id()]);
        $this->assertInstanceOf('mod_forumng_discussion', $result);
        $this->assertEquals($discussion->get_id(), $result->get_id());

        // Test with invalid ID - should return null.
        $result = $method->invokeArgs(null, [99999]);
        $this->assertNull($result);
    }

    /**
     * Test extract_forum_discussion_content method.
     */
    public function test_extract_forum_discussion_content() {
        $reflection = new ReflectionClass('mod_forumng\hook_callbacks');
        $method = $reflection->getMethod('extract_forum_discussion_content');

        // Test with valid ID.
        $discussion = $this->get_new_discussion($this->forum, [
            'userid' => $this->user->id,
            'subject' => 'Subject',
            'message' => 'Message content'
        ]);
        $result = $method->invokeArgs(null, [$discussion->get_id()]);
        $this->assertIsString($result);
        $this->assertStringContainsString('Subject', $result);

        // Test with invalid ID - should return null.
        $result = $method->invokeArgs(null, [99999]);
        $this->assertNull($result);
    }

    /**
     * Test render_and_clean_discussion method.
     */
    public function test_render_and_clean_discussion() {
        $discussion = $this->get_new_discussion($this->forum, [
            'userid' => $this->user->id,
            'subject' => 'Title',
            'message' => 'Content with <b>HTML</b>'
        ]);

        $reflection = new ReflectionClass('mod_forumng\hook_callbacks');
        $method = $reflection->getMethod('render_and_clean_discussion');

        $result = $method->invokeArgs(null, [$discussion]);
        $this->assertIsString($result);
        $this->assertStringContainsString('Title', $result);
        $this->assertStringNotContainsString('<b>', $result);
    }

    /**
     * Test clean_html_content method.
     */
    public function test_clean_html_content() {
        $reflection = new ReflectionClass('mod_forumng\hook_callbacks');
        $method = $reflection->getMethod('clean_html_content');

        // Test stripping HTML tags.
        $result = $method->invokeArgs(null, ['<p>Text <b>bold</b></p>']);
        $this->assertEquals('Text bold', $result);

        // Test decoding entities.
        $result = $method->invokeArgs(null, ['Text &amp; &quot;quoted&quot;']);
        $this->assertEquals('Text & "quoted"', $result);

        // Test normalizing whitespace.
        $result = $method->invokeArgs(null, ['Multiple    spaces']);
        $this->assertEquals('Multiple spaces', $result);

        // Test trimming.
        $result = $method->invokeArgs(null, ['  Text  ']);
        $this->assertEquals('Text', $result);
    }

    /**
     * Test is_forumng_context method.
     */
    public function test_is_forumng_context() {
        $reflection = new ReflectionClass('mod_forumng\hook_callbacks');
        $method = $reflection->getMethod('is_forumng_context');

        // Test with valid ForumNG context.
        $context = \context_module::instance($this->forum->get_course_module()->id);
        $this->assertTrue($method->invokeArgs(null, [$context->id]));

        // Test with invalid context ID.
        $this->assertFalse($method->invokeArgs(null, [99999]));

        // Test with course context.
        $coursecontext = \context_course::instance($this->course->id);
        $this->assertFalse($method->invokeArgs(null, [$coursecontext->id]));

        // Test with non-ForumNG module.
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $this->course->id]);
        $assigncontext = \context_module::instance($assign->cmid);
        $this->assertFalse($method->invokeArgs(null, [$assigncontext->id]));
    }

    /**
     * Helper method to create a new post in a discussion.
     */
    private function get_new_post($discussion, $options) {
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_forumng');
        $record = [
            'discussionid' => $discussion->get_id(),
            'userid' => $options['userid'],
        ];
        if (!empty($options['subject'])) {
            $record['subject'] = $options['subject'];
        }
        if (!empty($options['message'])) {
            $record['message'] = $options['message'];
        }
        $post = $generator->create_post($record);
        return mod_forumng_post::get_from_id($post->id, mod_forumng::CLONE_DIRECT);
    }

    /**
     * Create a mock hook for activity_search_info testing.
     */
    private function create_activity_search_hook($pagetype) {
        $cm = $this->forum->get_course_module();
        return new \local_moodleglobalsearch\hook\activity_search_info($cm, $pagetype);
    }

    /**
     * Create a mock hook for before_prompt_generation testing.
     */
    private function create_prompt_hook($contextid, $prompt) {
        return new \tool_ouadmin\hook\before_prompt_generation($contextid, $prompt);
    }
}
