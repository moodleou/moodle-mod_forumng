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

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/mod/forumng/tests/forumng_test_lib.php');
require_once($CFG->dirroot . '/mod/forumng/mod_forumng_utils.php');
require_once($CFG->dirroot . '/mod/forumng/mod_forumng.php');

use mod_forumng\output\mobile;

/**
 * PHPUnit to test function display author anonymously
 *
 * @package    mod_forumng
 * @copyright  2018 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class forumng_display_anon_test extends forumng_test_lib {

    /**
     * @var mod_forumng_generator generator class.
     */
    private $generator;

    /**
     * @var stdClass course record
     */
    private $course;

    /**
     * @var array Array forum to test
     */
    private $forums;

    /**
     * @var array User to test.
     */
    private $users;

    /**
     * @var array Discussion to test.
     */
    private $discussions;

    /**
     * Set up data for testing.
     *
     * @throws coding_exception
     */
    protected function setUp() {
        $this->resetAfterTest(true);

        $this->generator = $this->getDataGenerator()->get_plugin_generator('mod_forumng');

        // Create course.
        $this->course = $this->get_new_course();
        // Create forums.
        $this->forums[mod_forumng::CANPOSTAON_NORMAL] = $this->get_new_forumng($this->course->id, [
                'name' => 'ForumNG 1',
                'introduction' => 'Sample introduction',
                'canpostanon' => mod_forumng::CANPOSTAON_NORMAL
        ]);

        $this->forums[mod_forumng::CANPOSTANON_MODERATOR] = $this->get_new_forumng($this->course->id, [
                'name' => 'ForumNG 2',
                'introduction' => 'Sample introduction',
                'canpostanon' => mod_forumng::CANPOSTANON_MODERATOR
        ]);

        $this->forums[mod_forumng::CANPOSTATON_NONMODERATOR] = $this->get_new_forumng($this->course->id, [
                'name' => 'ForumNG 3',
                'introduction' => 'Sample introduction',
                'canpostanon' => mod_forumng::CANPOSTATON_NONMODERATOR
        ]);
        // Create users.
        $this->users[0] = $this->get_new_user('teacher', $this->course->id);
        $this->users[1] = $this->get_new_user('student', $this->course->id);
        $this->users[2] = $this->get_new_user('student', $this->course->id);
        // Create discussions.
        $this->discussions[mod_forumng::CANPOSTAON_NORMAL] = $this->generator->create_discussion([
                        'course' => $this->course,
                        'forum' => $this->forums[mod_forumng::CANPOSTAON_NORMAL]->get_id(),
                        'userid' => $this->users[1]->id]
        );
        $this->discussions[mod_forumng::CANPOSTANON_MODERATOR] = $this->generator->create_discussion([
                        'course' => $this->course,
                        'forum' => $this->forums[mod_forumng::CANPOSTANON_MODERATOR]->get_id(),
                        'userid' => $this->users[1]->id]
        );
        $this->discussions[mod_forumng::CANPOSTATON_NONMODERATOR] = $this->generator->create_discussion([
                        'course' => $this->course,
                        'forum' => $this->forums[mod_forumng::CANPOSTATON_NONMODERATOR]->get_id(),
                        'userid' => $this->users[1]->id]
        );
    }

    /**
     * Test function display_discussion_list_item_author_anonymously
     */
    public function test_display_discussion_list_item_author_anonymously() {
        // Post as normal.
        $this->assertFalse(mod_forumng_utils::display_discussion_list_item_author_anonymously(
                $this->forums[mod_forumng::CANPOSTAON_NORMAL], $this->users[2]->id));
        $this->assertFalse(mod_forumng_utils::display_discussion_list_item_author_anonymously(
                $this->forums[mod_forumng::CANPOSTAON_NORMAL], $this->users[0]->id));
        $this->assertFalse(mod_forumng_utils::display_discussion_list_item_author_anonymously(
                $this->forums[mod_forumng::CANPOSTAON_NORMAL], $this->users[1]->id, $this->users[1]->id));
        // Allow moderators to post anonymously.
        $this->assertFalse(mod_forumng_utils::display_discussion_list_item_author_anonymously(
                $this->forums[mod_forumng::CANPOSTANON_MODERATOR], $this->users[2]->id));
        $this->assertFalse(mod_forumng_utils::display_discussion_list_item_author_anonymously(
                $this->forums[mod_forumng::CANPOSTANON_MODERATOR], $this->users[0]->id));
        $this->assertFalse(mod_forumng_utils::display_discussion_list_item_author_anonymously(
                $this->forums[mod_forumng::CANPOSTANON_MODERATOR], $this->users[1]->id));
        // Non-moderators always post anonymously.
        $this->assertTrue(mod_forumng_utils::display_discussion_list_item_author_anonymously(
                $this->forums[mod_forumng::CANPOSTATON_NONMODERATOR], $this->users[2]->id));
        $this->assertFalse(mod_forumng_utils::display_discussion_list_item_author_anonymously(
                $this->forums[mod_forumng::CANPOSTATON_NONMODERATOR], $this->users[0]->id));
        $this->assertTrue(mod_forumng_utils::display_discussion_list_item_author_anonymously(
                $this->forums[mod_forumng::CANPOSTATON_NONMODERATOR], $this->users[1]->id));
    }

    /**
     * Test function display_discussion_author_anonymously
     *
     * @throws coding_exception
     */
    public function test_display_discussion_author_anonymously() {
        // Post as normal.
        $post1 = mod_forumng_post::get_from_id($this->discussions[mod_forumng::CANPOSTAON_NORMAL][1], 0, true);
        $this->assertFalse(mod_forumng_utils::display_discussion_author_anonymously($post1, $this->users[2]->id));
        $this->assertFalse(mod_forumng_utils::display_discussion_author_anonymously($post1, $this->users[0]->id));
        $this->assertFalse(mod_forumng_utils::display_discussion_author_anonymously($post1, $this->users[1]->id));
        // Allow moderators to post anonymously.
        $post2 = mod_forumng_post::get_from_id($this->discussions[mod_forumng::CANPOSTANON_MODERATOR][1], 0, true);
        $this->assertFalse(mod_forumng_utils::display_discussion_author_anonymously($post2, $this->users[2]->id));
        $this->assertFalse(mod_forumng_utils::display_discussion_author_anonymously($post2, $this->users[0]->id));
        $this->assertFalse(mod_forumng_utils::display_discussion_author_anonymously($post2, $this->users[1]->id));
        // Non-moderators always post anonymously.
        $post3 = mod_forumng_post::get_from_id($this->discussions[mod_forumng::CANPOSTATON_NONMODERATOR][1], 0, true);
        $this->assertTrue(mod_forumng_utils::display_discussion_author_anonymously($post3, $this->users[2]->id));
        $this->assertFalse(mod_forumng_utils::display_discussion_author_anonymously($post3, $this->users[0]->id));
        $this->assertTrue(mod_forumng_utils::display_discussion_author_anonymously($post3, $this->users[1]->id));
        $tmp = $this->generator->create_post([
                'discussionid' => $this->discussions[mod_forumng::CANPOSTATON_NONMODERATOR][0],
                'parentpostid' => $this->discussions[mod_forumng::CANPOSTATON_NONMODERATOR][1],
                'userid' => $this->users[0]->id,
                'asmoderator' => mod_forumng::ASMODERATOR_IDENTIFY]);
        $post4 = mod_forumng_post::get_from_id($tmp->id, 0, true);
        $this->assertFalse(mod_forumng_utils::display_discussion_author_anonymously($post4, $this->users[2]->id));
        $this->assertFalse(mod_forumng_utils::display_discussion_author_anonymously($post4, $this->users[0]->id));
        $tmp = $this->generator->create_post([
                'discussionid' => $this->discussions[mod_forumng::CANPOSTATON_NONMODERATOR][0],
                'parentpostid' => $this->discussions[mod_forumng::CANPOSTATON_NONMODERATOR][1],
                'userid' => $this->users[0]->id,
                'asmoderator' => mod_forumng::ASMODERATOR_ANON]);
        $post5 = mod_forumng_post::get_from_id($tmp->id, 0, true);
        $this->assertFalse(mod_forumng_utils::display_discussion_author_anonymously($post5, $this->users[2]->id));
        $this->assertFalse(mod_forumng_utils::display_discussion_author_anonymously($post5, $this->users[0]->id));
    }

    /**
     * Test function mobile_prep_discussions display author anonymously
     *
     * @throws coding_exception
     */
    public function test_mobile_prep_discussions_anon() {
        // Student login.
        $this->setUser($this->users[2]->id);
        $discussions = [];
        $tmp = $this->generator->create_discussion([
                'course' => $this->course,
                'forum' => $this->forums[mod_forumng::CANPOSTAON_NORMAL]->get_id(),
                'userid' => $this->users[0]->id,
                'asmoderator' => mod_forumng::ASMODERATOR_NO]);
        $discussionlist = [
                mod_forumng_discussion::get_from_id($tmp[0], 0, $this->users[1]->id)
        ];
        mobile::prep_discussions($this->forums[mod_forumng::CANPOSTAON_NORMAL], $discussionlist, $discussions);
        $this->assertContains(fullname($this->users[0]), $discussions[0]->startedby);
        $this->assertContains(fullname($this->users[0]), $discussions[0]->startedby);

        $discussions = [];
        $tmp1 = $this->generator->create_discussion([
                'course' => $this->course,
                'forum' => $this->forums[mod_forumng::CANPOSTANON_MODERATOR]->get_id(),
                'userid' => $this->users[0]->id,
                'asmoderator' => mod_forumng::ASMODERATOR_NO]);
        $tmp2 = $this->generator->create_discussion([
                'course' => $this->course,
                'forum' => $this->forums[mod_forumng::CANPOSTANON_MODERATOR]->get_id(),
                'userid' => $this->users[0]->id,
                'asmoderator' => mod_forumng::ASMODERATOR_IDENTIFY]);
        $discussionlist = [
                mod_forumng_discussion::get_from_id($tmp1[0], 0),
                mod_forumng_discussion::get_from_id($tmp2[0], 0)
        ];
        mobile::prep_discussions($this->forums[mod_forumng::CANPOSTANON_MODERATOR], $discussionlist, $discussions);
        $this->assertContains(fullname($this->users[0]), $discussions[0]->startedby);
        $this->assertContains(fullname($this->users[0]), $discussions[0]->lastpost);
        $this->assertContains(get_string('moderator', 'forumng'), $discussions[1]->startedby);
        $this->assertContains(get_string('moderator', 'forumng'), $discussions[1]->lastpost);

        $discussions = [];
        $tmp1 = $this->generator->create_discussion([
                'course' => $this->course,
                'forum' => $this->forums[mod_forumng::CANPOSTATON_NONMODERATOR]->get_id(),
                'userid' => $this->users[0]->id,
                'asmoderator' => mod_forumng::ASMODERATOR_NO]);

        $tmp2 = $this->generator->create_discussion([
                'course' => $this->course,
                'forum' => $this->forums[mod_forumng::CANPOSTATON_NONMODERATOR]->get_id(),
                'userid' => $this->users[0]->id,
                'asmoderator' => mod_forumng::ASMODERATOR_IDENTIFY]);

        $tmp3 = $this->generator->create_discussion([
                'course' => $this->course,
                'forum' => $this->forums[mod_forumng::CANPOSTATON_NONMODERATOR]->get_id(),
                'userid' => $this->users[0]->id,
                'asmoderator' => mod_forumng::ASMODERATOR_ANON]);

        $discussionlist = [
                mod_forumng_discussion::get_from_id($tmp1[0], 0),
                mod_forumng_discussion::get_from_id($tmp2[0], 0),
                mod_forumng_discussion::get_from_id($tmp3[0], 0)
        ];
        mobile::prep_discussions($this->forums[mod_forumng::CANPOSTANON_MODERATOR], $discussionlist, $discussions);
        $this->assertContains(get_string('identityprotected', 'forumng'), $discussions[0]->startedby);
        $this->assertContains(get_string('identityprotected', 'forumng'), $discussions[0]->lastpost);
        $this->assertContains(get_string('moderator', 'forumng'), $discussions[1]->startedby);
        $this->assertContains(get_string('moderator', 'forumng'), $discussions[1]->lastpost);
        $this->assertContains(fullname($this->users[0]), $discussions[1]->startedby);
        $this->assertContains(fullname($this->users[0]), $discussions[1]->lastpost);
        $this->assertNotContains(fullname($this->users[0]), $discussions[2]->startedby);
        $this->assertNotContains(fullname($this->users[0]), $discussions[2]->lastpost);

        // Moderator login.
        $this->setUser($this->users[0]->id);
        $discussions = [];
        mobile::prep_discussions($this->forums[mod_forumng::CANPOSTANON_MODERATOR], $discussionlist, $discussions);
        $this->assertContains(fullname($this->users[0]), $discussions[0]->startedby);
        $this->assertContains(fullname($this->users[0]), $discussions[0]->lastpost);
        $this->assertContains(fullname($this->users[0]), $discussions[1]->startedby);
        $this->assertContains(fullname($this->users[0]), $discussions[1]->lastpost);
        $this->assertContains(fullname($this->users[0]), $discussions[2]->startedby);
        $this->assertContains(fullname($this->users[0]), $discussions[2]->lastpost);
    }

    /**
     * Test function get_common_post_data_anon display author anonymously
     *
     * @throws ReflectionException
     * @throws coding_exception
     */
    public function test_mobile_get_common_post_data_anon() {
        $method = new ReflectionMethod('\mod_forumng\output\mobile', 'get_common_post_data');
        $method->setAccessible(true);
        $fullname = fullname($this->users[0]);
        $moderator = get_string('moderator', 'forumng');
        $identityprotected = get_string('identityprotected', 'forumng');
        // Login as Moderator.
        $this->setUser($this->users[2]->id);
        // Normal forum.
        $tmp = $this->generator->create_discussion([
                'course' => $this->course,
                'forum' => $this->forums[mod_forumng::CANPOSTAON_NORMAL]->get_id(),
                'userid' => $this->users[0]->id]);
        $discussion = mod_forumng_discussion::get_from_id($tmp[0], 0);
        $post = mod_forumng_post::get_from_id($tmp[1], 0, true);
        $postdata = $method->invoke(new \mod_forumng\output\mobile, $discussion, $post, 'defaultimage', $moderator);
        $this->assertEquals($fullname, $postdata['startedby']);

        // Moderator can post anonymous.
        $tmp = $this->generator->create_discussion([
                'course' => $this->course,
                'forum' => $this->forums[mod_forumng::CANPOSTANON_MODERATOR]->get_id(),
                'userid' => $this->users[0]->id]);
        $discussion = mod_forumng_discussion::get_from_id($tmp[0], 0);
        $post = mod_forumng_post::get_from_id($tmp[1], 0, true);
        $postdata = $method->invoke(new \mod_forumng\output\mobile, $discussion, $post, 'defaultimage', $moderator);
        $this->assertEquals($fullname, $postdata['startedby']);

        $tmp = $this->generator->create_discussion([
                'course' => $this->course,
                'forum' => $this->forums[mod_forumng::CANPOSTANON_MODERATOR]->get_id(),
                'userid' => $this->users[0]->id,
                'asmoderator' => mod_forumng::ASMODERATOR_IDENTIFY]);
        $discussion = mod_forumng_discussion::get_from_id($tmp[0], 0);
        $post = mod_forumng_post::get_from_id($tmp[1], 0, true);
        $postdata = $method->invoke(new \mod_forumng\output\mobile, $discussion, $post, 'defaultimage', $moderator);
        $this->assertContains($fullname, $postdata['startedby']);
        $this->assertContains($moderator, $postdata['startedby']);

        // Non-moderator always post anonymous.
        $tmp = $this->generator->create_discussion([
                'course' => $this->course,
                'forum' => $this->forums[mod_forumng::CANPOSTATON_NONMODERATOR]->get_id(),
                'userid' => $this->users[0]->id,
                'asmoderator' => mod_forumng::CANPOSTAON_NORMAL]);
        $discussion1 = mod_forumng_discussion::get_from_id($tmp[0], 0);
        $post1 = mod_forumng_post::get_from_id($tmp[1], 0, true);
        $postdata = $method->invoke(new \mod_forumng\output\mobile, $discussion1, $post1, 'defaultimage', $moderator);
        $this->assertEquals($identityprotected, $postdata['startedby']);
        $this->assertContains('defaultimage', $postdata['startedbyurl']);

        $tmp = $this->generator->create_discussion([
                'course' => $this->course,
                'forum' => $this->forums[mod_forumng::CANPOSTATON_NONMODERATOR]->get_id(),
                'userid' => $this->users[0]->id,
                'asmoderator' => mod_forumng::ASMODERATOR_IDENTIFY]);
        $discussion2 = mod_forumng_discussion::get_from_id($tmp[0], 0);
        $post2 = mod_forumng_post::get_from_id($tmp[1], 0, true);
        $postdata = $method->invoke(new \mod_forumng\output\mobile, $discussion2, $post2, 'defaultimage', $moderator);
        $this->assertContains($fullname, $postdata['startedby']);
        $this->assertContains($moderator, $postdata['startedby']);

        $tmp = $this->generator->create_discussion([
                'course' => $this->course,
                'forum' => $this->forums[mod_forumng::CANPOSTATON_NONMODERATOR]->get_id(),
                'userid' => $this->users[0]->id,
                'asmoderator' => mod_forumng::ASMODERATOR_ANON]);
        $discussion3 = mod_forumng_discussion::get_from_id($tmp[0], 0);
        $post3 = mod_forumng_post::get_from_id($tmp[1], 0, true);
        $postdata = $method->invoke(new \mod_forumng\output\mobile, $discussion3, $post3, 'defaultimage', $moderator);
        $this->assertEquals($moderator, $postdata['startedby']);
        $this->assertContains('defaultimage', $postdata['startedbyurl']);

        // Login as Moderator.
        $this->setUser($this->users[0]->id);
        $postdata = $method->invoke(new \mod_forumng\output\mobile, $discussion1, $post1, 'defaultimage', $moderator);
        $this->assertEquals($fullname, $postdata['startedby']);
        $this->assertNotContains('defaultimage', $postdata['startedbyurl']);

        $postdata = $method->invoke(new \mod_forumng\output\mobile, $discussion2, $post2, 'defaultimage', $moderator);
        $this->assertContains($fullname, $postdata['startedby']);
        $this->assertContains($moderator, $postdata['startedby']);

        $postdata = $method->invoke(new \mod_forumng\output\mobile, $discussion3, $post3, 'defaultimage', $moderator);
        $this->assertContains($moderator, $postdata['startedby']);
        $this->assertContains($fullname, $postdata['startedby']);
        $this->assertNotContains('defaultimage', $postdata['startedbyurl']);
    }
}
