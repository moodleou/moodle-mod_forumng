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
 * Tests for the forum implementation of the Privacy Provider API.
 *
 * @package mod_forumng
 * @copyright 2018 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once(__DIR__ . '/helper.php');
require_once($CFG->dirroot . '/rating/lib.php');
require_once($CFG->dirroot . '/mod/forumng/mod_forumng.php');
require_once($CFG->dirroot . '/mod/forumng/mod_forumng_draft.php');

use \mod_forumng\privacy\provider;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\writer;

/**
 * Tests for the forum implementation of the Privacy Provider API.
 *
 * @package mod_forumng
 * @copyright 2018 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_forumng_privacy_provider_testcase extends \core_privacy\tests\provider_testcase {

    // Include the privacy subcontext_info trait.
    // This includes the subcontext builders.
    use \mod_forumng\privacy\subcontext_info;

    // Include the privacy helper trait for the ratings API.
    use \core_rating\phpunit\privacy_helper;

    // Include the privacy helper trait for the tag API.
    use \core_tag\tests\privacy_helper;

    // Include the mod_forum test helpers.
    // This includes functions to create forums, users, discussions, and posts.
    use forumng_helper;

    protected $currentuser = '';
    protected $context = '';
    protected $forum = '';
    protected $discussion = '';
    protected $otheruser = '';
    protected $draft = '';

    /**
     * Test setUp.
     */
    public function setUp() {
        $this->resetAfterTest(true);
        // Create a course, with a forum, our user under test, another user, and a discussion + post from the other user.
        $course = $this->getDataGenerator()->create_course();
        $forumnglib = new mod_forumng_privacy_helper();
        $forumnglib->get_new_forumng($course->id, ['name' => 'TEST', 'intro' => 'abc123',
            'enableratings' => mod_forumng::FORUMNG_STANDARD_RATING, 'ratingscale' => 10]);
        $course = $this->getDataGenerator()->create_course();
        $forumnglib->get_new_forumng($course->id, ['name' => 'TEST2', 'intro' => 'abc123',
            'enableratings' => mod_forumng::FORUMNG_STANDARD_RATING, 'ratingscale' => 10]);
        $course = $this->getDataGenerator()->create_course();
        $forum = $forumnglib->get_new_forumng($course->id, ['name' => 'TEST3', 'intro' => 'Test forum 3',
            'enableratings' => mod_forumng::FORUMNG_STANDARD_RATING, 'ratingscale' => 10,
            'subscription' => mod_forumng::SUBSCRIPTION_PERMITTED]);
        list($user, $otheruser) = $this->helper_create_users($course, 2);
        $this->setUser($user);
        $dis1 = $forumnglib->get_new_discussion($forum, array('userid' => $user->id, 'timestart' => 1420070400,
                'subject' => 'Welcome/to the Developing as a Researcher seminar'));
        $lastpost = mod_forumng_post::get_from_id($dis1->get_last_post_id(), 0);
        $lastpost->set_flagged(true, $user->id);
        $lastpost->mark_read(1420070400, $user->id);
        $lastpost->rate(5, $user->id);
        $dis1->create_reply($lastpost, 'Welcome/to the Developing as a Researcher seminar', 'reply',
                FORMAT_HTML, false, false, false, $user->id);
        // Set flag for discussion.
        $dis1->set_flagged(true. $user->id);
        // Mark read for discussion.
        $dis1->mark_read(1420070400, $user->id);
        $cm = get_coursemodule_from_instance('forumng', $forum->get_id());

        // Get context.
        $context = \context_module::instance($cm->id);
        \core_tag_tag::set_item_tags('mod_forumng', 'forumng_discussions', $dis1->get_id(), $context, ['example', 'tag']);
        // Create draft.
        $draftid = mod_forumng_draft::save_new($forum, null, $lastpost->get_id(), 'draft', 'draft message',
            FORMAT_HTML, false, null, $user->id);
        $draft = mod_forumng_draft::get_from_id($draftid);
        $fs = get_file_storage();
        // Add a fake inline image to the draft.
        $fs->create_file_from_string([
            'contextid' => $context->id,
            'component' => 'mod_forumng',
            'filearea'  => 'draft',
            'itemid'    => $draftid,
            'filepath'  => '/',
            'filename'  => 'example.jpg',
        ], 'image contents (not really)');
        // Add a fake inline image to the post.
        $fs->create_file_from_string([
            'contextid' => $context->id,
            'component' => 'mod_forumng',
            'filearea'  => 'attachments',
            'itemid'    => $lastpost->get_id(),
            'filepath'  => '/',
            'filename'  => 'example.jpg',
        ], 'image contents (not really)');
        // Add a fake inline image to the post editor content.
        $fs->create_file_from_string([
            'contextid' => $context->id,
            'component' => 'mod_forumng',
            'filearea'  => 'post',
            'itemid'    => $lastpost->get_id(),
            'filepath'  => '/',
            'filename'  => 'example.jpg',
        ], 'image contents (not really)');
        $this->draft = $draft;
        $this->context = $context;
        $this->currentuser = $user;
        $this->forum = $forum;
        $this->discussion = $dis1;
        $this->otheruser = $otheruser;
        // Subscribe the user to the forum.
        $forum->subscribe($user->id);
    }

    /**
     * Test that a user who is enrolled in a course, but who has never
     * posted and has no other metadata stored will not have any link to
     * that context.
     */
    public function test_user_has_never_posted() {
        $context = $this->context;
        $otheruser = $this->otheruser;

        // Test that no contexts were retrieved.
        $contextlist = $this->get_contexts_for_userid($otheruser->id, 'mod_forumng');
        $contexts = $contextlist->get_contextids();
        $this->assertCount(0, $contexts);

        // Attempting to export data for this context should return nothing either.
        $this->export_context_data_for_user($otheruser->id, $context, 'mod_forumng');

        $writer = \core_privacy\local\request\writer::with_context($context);

        // The provider should always export data for any context explicitly asked of it, but there should be no
        // metadata, files, or discussions.
        $this->assertEmpty($writer->get_data([get_string('discussions', 'mod_forumng')]));
        $this->assertEmpty($writer->get_all_metadata([]));
        $this->assertEmpty($writer->get_files([]));
    }

    /**
     * Test that a user who is enrolled in a course, and who has never
     * posted and has subscribed to the forum will have relevant
     * information returned.
     */
    public function test_user_has_never_posted_subscribed_to_forum() {
        $forum = $this->forum;
        $context = $this->context;
        $otheruser = $this->otheruser;
        // Subscribe the user to the forum.
        $forum->subscribe($otheruser->id);
        $this->assertCount(2, $forum->get_subscribers());
        // Retrieve all contexts - only this context should be returned.
        $contextlist = $this->get_contexts_for_userid($otheruser->id, 'mod_forumng');
        $this->assertCount(1, $contextlist);
        $this->assertEquals($context, $contextlist->current());

        // Export all of the data for the context.
        $this->export_context_data_for_user($otheruser->id, $context, 'mod_forumng');
        $writer = \core_privacy\local\request\writer::with_context($context);
        $this->assertTrue($writer->has_any_data());

        $subcontext = $this->get_subcontext($forum);
        // There are forumng_subscriptions data.
        $this->assertNotEmpty($writer->get_data($subcontext, [get_string('forumngsubscriptions', 'mod_forumng')]));
        // There should be data about the forum itself.
        $this->assertNotEmpty($writer->get_data($subcontext));
    }

    /**
     * Test export data for user in forumng
     */
    public function test_export_user_post_in_discussion() {
        global $DB;
        $forum = $this->forum;
        $dis1 = $this->discussion;
        $user = $this->currentuser;
        $context = $this->context;
        $discussion = new stdClass();
        $discussion->id = $dis1->get_id();
        $discussionarea = static::get_discussion_area($discussion);

        // Check subject of discussion has been shortened and character "/" replaced by "_".
        $this->assertEquals($discussion->id.'-Welcome_to the Developing as a R', $discussionarea[1]);

        $fs = get_file_storage();

        $this->assertCount(1, $forum->get_subscribers());

        $contextids = provider::get_contexts_for_userid($user->id)->get_contextids();
        $appctx = new approved_contextlist($user, 'mod_forumng', $contextids);
        provider::export_user_data($appctx);
        $contextdata = writer::with_context($context);
        // Check forum data.
        $this->assertEquals($forum->get_name(), $contextdata->get_data([])->name);
        // Check subscription data.
        $sub = $DB->get_record('forumng_subscriptions', ['forumngid' => $forum->get_id(), 'userid' => $user->id]);
        $this->assertEquals((object)[
            'userid' => get_string('privacy_you', 'mod_forumng'),
            'subscribed' => \core_privacy\local\request\transform::yesno(1),
        ], $contextdata->get_data([get_string('forumngsubscriptions', 'mod_forumng'). '-' . $sub->id]));
        // Check draft data and files.
        $this->assertNotEmpty($fs->get_area_files($context->id,
            'mod_forumng', 'draft', $this->draft->get_id()));
        $this->assertEquals((object)[
            'userid' => get_string('privacy_you', 'mod_forumng'),
            'subject' => 'draft',
            'message' => 'draft message',
            'messageformat' => FORMAT_HTML,
            'attachments' => \core_privacy\local\request\transform::yesno(false),
            'saved' => \core_privacy\local\request\transform::datetime($this->draft->get_saved()),
        ], $contextdata->get_data([get_string('forumngdraft', 'mod_forumng') . '-' . $this->draft->get_id()]));
        // Check discussion data.
        $dis1 = mod_forumng_discussion::get_from_id($dis1->get_id(), 0);
        $this->assert_all_tags_match_on_context(
                $user->id,
                $context,
                $discussionarea,
                'mod_forumng',
                'forumng_discussions',
                $dis1->get_id()
        );
        $this->assertEquals((object)[
            'timestart' => \core_privacy\local\request\transform::datetime($dis1->get_time_start()),
            'timeend' => \core_privacy\local\request\transform::datetime($dis1->get_time_end()),
            'deleted' => \core_privacy\local\request\transform::yesno($dis1->is_deleted()),
            'locked' => \core_privacy\local\request\transform::yesno($dis1->is_locked()),
            'sticky' => \core_privacy\local\request\transform::yesno($dis1->is_sticky()),
            'modified' => \core_privacy\local\request\transform::datetime($dis1->get_time_modified()),
            'ipudloc' => $dis1->get_location()
        ], $contextdata->get_data($discussionarea));
        // Check discussion read data.
        $readarea = $discussionarea;
        $readarea[] = get_string('forumngreaddiscussion', 'mod_forumng');
        $this->assertEquals((object)[
            'userid' => get_string('privacy_you', 'mod_forumng'),
            'time' => \core_privacy\local\request\transform::datetime(1420070400)
        ], $contextdata->get_data($readarea));
        // Check discussion flag data.
        $flag = $discussionarea;
        $flag[] = get_string('forumngflagdiscussion', 'mod_forumng');
        $this->assertEquals((object)[
            'userid' => get_string('privacy_you', 'mod_forumng'),
            'flagged' => \core_privacy\local\request\transform::yesno(1)
        ], $contextdata->get_data($flag));
        // Check post data.
        $postarea = $discussionarea;
        $postarea[] = get_string('posts', 'mod_forumng');
        $rootpost = $dis1->get_root_post();
        $post = new stdClass();
        $post->created = $rootpost->get_created();
        $post->subject = $rootpost->get_subject();
        $post->id = $rootpost->get_id();
        $rootpostarea = array_merge($postarea, static::get_post_area($post));

        // Check subject of post has been shortened and character "/" replaced by "_".
        $this->assertEquals($post->created . '-Welcome_to the Developing as a R-' . $post->id, $rootpostarea[3]);

        // Check root post.
        // Check post data and files.
        $this->assertNotEmpty($fs->get_area_files($context->id,
            'mod_forumng', 'attachments', $rootpost->get_id()));
        $this->assertEquals((object)[
            'deleted' => \core_privacy\local\request\transform::yesno(0),
            'deleted_by_you' => \core_privacy\local\request\transform::yesno(0),
            'edited_by_you' => \core_privacy\local\request\transform::yesno(0),
            'important' => \core_privacy\local\request\transform::yesno(0),
            'mailstate' => '0',
            'oldversion' => 0,
            'subject' => format_string($rootpost->get_subject(), true),
            'message' => $rootpost->get_formatted_message(),
            'messageformat' => 0,
            'attachments' => \core_privacy\local\request\transform::yesno(0),
            'asmoderator' => \core_privacy\local\request\transform::yesno(0),
            'created' => \core_privacy\local\request\transform::datetime($rootpost->get_created()),
            'modified' => \core_privacy\local\request\transform::datetime($rootpost->get_modified()),
            'author_was_you' => \core_privacy\local\request\transform::yesno(1)
        ], $contextdata->get_data($rootpostarea));
        // Check flag post data.
        $flag = $DB->get_record('forumng_flags', ['userid' => $user->id, 'postid' => $rootpost->get_id()]);
        $this->assertEquals((object)[
            'userid' => get_string('privacy_you', 'mod_forumng'),
            'flagged' => core_privacy\local\request\transform::yesno(1)
        ], $contextdata->get_metadata($rootpostarea, 'flags' . $flag->id));
        // Check custom rating.
        $rating = $DB->get_record('forumng_ratings', ['postid' => $rootpost->get_id()]);
        $this->assertEquals((object)[
            'author' => get_string('privacy_you', 'mod_forumng'),
            'customrating' => '5',
            'time' => core_privacy\local\request\transform::datetime($rating->time)
        ], $contextdata->get_metadata($rootpostarea, 'customrating' . $rating->id));
        // Check reply post.
        $lastpost = mod_forumng_post::get_from_id($dis1->get_last_post_id(), 0);
        $post = new stdClass();
        $post->created = $lastpost->get_created();
        $post->subject = $lastpost->get_subject();
        $post->id = $lastpost->get_id();
        $replypostarea = array_merge($rootpostarea, static::get_post_area($post));
        $this->assertEmpty($contextdata->get_data($replypostarea));

        $replypostarea = array_merge($postarea, static::get_post_area($post));
        $this->assertEquals((object)[
            'deleted' => \core_privacy\local\request\transform::yesno(0),
            'deleted_by_you' => \core_privacy\local\request\transform::yesno(0),
            'edited_by_you' => \core_privacy\local\request\transform::yesno(0),
            'important' => \core_privacy\local\request\transform::yesno(0),
            'mailstate' => '0',
            'oldversion' => 0,
            'subject' => format_string($lastpost->get_subject(), true),
            'message' => $lastpost->get_formatted_message(),
            'messageformat' => 1,
            'attachments' => \core_privacy\local\request\transform::yesno(0),
            'asmoderator' => \core_privacy\local\request\transform::yesno(0),
            'created' => \core_privacy\local\request\transform::datetime($lastpost->get_created()),
            'modified' => \core_privacy\local\request\transform::datetime($lastpost->get_modified()),
            'author_was_you' => \core_privacy\local\request\transform::yesno(1),
            'parentpostid' => $rootpost->get_id()
        ], $contextdata->get_data($replypostarea));
    }


    /**
     * Test delete data for user.
     */
    public function test_delete_data_for_user() {
        $dis1 = $this->discussion;
        $user = $this->currentuser;
        $context = $this->context;
        $contextids = provider::get_contexts_for_userid($user->id)->get_contextids();
        $appctx = new approved_contextlist($user, 'mod_forumng', $contextids);
        $fs = get_file_storage();
        $discussion = new stdClass();
        $discussion->id = $dis1->get_id();
        $discussionarea = static::get_discussion_area($discussion);
        provider::export_user_data($appctx);
        $this->assertTrue(writer::with_context($context)->has_any_data());
        // Delete data belong to first user.
        provider::delete_data_for_user($appctx);
        writer::reset();
        provider::export_user_data($appctx);
        // Check data belong to first user is deleted.
        // Subscription is deleted.
        $this->assertTrue(empty(writer::with_context($context)->get_data([get_string('forumngsubscriptions',
            'mod_forumng')])));
        // Draft data and files is deleted.
        $this->assertTrue(empty(writer::with_context($context)->get_data([get_string('forumngdraft', 'mod_forumng')])));
        $this->assertTrue(empty($fs->get_area_files($context->id,
            'mod_forumng', 'draft', $this->draft->get_id())));
        $readarea = $discussionarea;
        // Read data for discussion is deleted.
        $readarea[] = get_string('forumngreaddiscussion', 'mod_forumng');
        $this->assertTrue(empty(writer::with_context($context)->get_data($readarea)));;
        $flag = $discussionarea;
        $flag[] = get_string('forumngflagdiscussion', 'mod_forumng');
        // Flag data for discussion is deleted.
        $this->assertTrue(empty(writer::with_context($context)->get_data($flag)));;
        $postarea = $discussionarea;
        $postarea[] = get_string('posts', 'mod_forumng');
        $rootpost = $dis1->get_root_post();
        $post = new stdClass();
        $post->created = $rootpost->get_created();
        $post->subject = $rootpost->get_subject();
        $post->id = $rootpost->get_id();
        $this->assertTrue(empty($fs->get_area_files($context->id,
            'mod_forumng', 'attachments', $rootpost->get_id())));
        $this->assertTrue(empty($fs->get_area_files($context->id,
            'mod_forumng', 'post', $rootpost->get_id())));
        $rootpostarea = array_merge($postarea, static::get_post_area($post));
        // We do not delete post in discussion,just change content to empty.
        $this->assertFalse(empty(writer::with_context($context)->get_data($rootpostarea)));
        $this->assertEmpty($rootpost->get_formatted_message());
        // Check reply post.
        $lastpost = mod_forumng_post::get_from_id($dis1->get_last_post_id(), 0);
        $post = new stdClass();
        $post->created = $lastpost->get_created();
        $post->subject = $lastpost->get_subject();
        $post->id = $lastpost->get_id();
        $replypostarea = array_merge($rootpostarea, static::get_post_area($post));
        $this->assertEmpty(writer::with_context($context)->get_data($replypostarea));;
    }

    /**
     *  Test delete data mod_forumng in context.
     */
    public function test_delete_data_for_all_users_in_context() {
        global $DB;
        $dis1 = $this->discussion;
        $user = $this->currentuser;
        $context = $this->context;
        $contextids = provider::get_contexts_for_userid($user->id)->get_contextids();
        $appctx = new approved_contextlist($user, 'mod_forumng', $contextids);
        $discussion = new stdClass();
        $discussion->id = $dis1->get_id();
        $discussionarea = static::get_discussion_area($discussion);

        provider::export_user_data($appctx);
        $this->assertTrue(writer::with_context($context)->has_any_data());
        // Delete data belong to first user.
        provider::delete_data_for_all_users_in_context($context);
        writer::reset();
        provider::export_user_data($appctx);
        // Should delete everything in discussion.
        $this->assertTrue(empty(writer::with_context($context)->get_data($discussionarea)));
        $ratings = $DB->get_records('forumng_ratings', ['userid' => $user->id]);
        $drafts = $DB->get_records('forumng_drafts', ['userid' => $user->id]);
        $rps = $DB->get_records('forumng_read_posts', ['userid' => $user->id]);
        $rds = $DB->get_records('forumng_read', ['userid' => $user->id]);
        $flags = $DB->get_records('forumng_read', ['userid' => $user->id]);
        $this->assertEquals(0, count($ratings));
        $this->assertEquals(0, count($drafts));
        $this->assertEquals(0, count($rps));
        $this->assertEquals(0, count($flags));
        $this->assertEquals(0, count($rds));
        $this->assertCount(0, \core_tag_tag::get_item_tags('mod_forumng', 'forumng_discussions', $dis1->get_id()));
    }

    /**
     * Ensure that export_user_preferences returns no data if the user has no data.
     */
    public function test_export_user_preferences_not_defined() {
        $user = $this->currentuser;
        $contextids = provider::get_contexts_for_userid($user->id)->get_contextids();
        $appctx = new approved_contextlist($user, 'mod_forumng', $contextids);
        $writer = writer::with_context(context_user::instance($user->id));
        $this->assertFalse($writer->has_any_data());
    }

    /**
     * Test export user preferences
     */
    public function test_export_user_preferences() {
        $user = $this->currentuser;
        $this->setUser($user);
        set_user_preference('forumng_simplemode', true);
        provider::export_user_preferences($user->id);
        $context = context_user::instance($user->id);
        $writer = writer::with_context($context);
        $prefs = $writer->get_user_preferences('mod_forumng');
        $this->assertCount(2, (array) $prefs);
        $this->assertEquals(0, $writer->get_user_preferences('mod_forumng')->maildigest->value);
        $this->assertEquals(get_string('emaildigestoff'),
            $writer->get_user_preferences('mod_forumng')->maildigest->description);
        $this->assertTrue($writer->has_any_data());
        // Simple mode.
        $this->assertEquals('Yes', $prefs->forumng_simplemode->value);
        $this->assertEquals(
                get_string('privacy:metadata:preference:forumng_simplemode', 'mod_forumng'),
                $prefs->forumng_simplemode->description
        );
    }
}
