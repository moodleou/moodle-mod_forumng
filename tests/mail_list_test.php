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

namespace mod_forumng;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/forumng/mod_forumng_mail_list.php');

class mail_list_testcase extends \advanced_testcase
{
    public function test_mail_list() {
        global $DB, $CFG;

        $this->resetAfterTest();
        $this->preventResetByRollback();
        \mod_forumng_mail_list::reset_static_cache();

        // Create three forums.
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $forum1 = $generator->create_module('forumng', ['course' => $course->id]);
        $forum2 = $generator->create_module('forumng', ['course' => $course->id]);
        $forum3 = $generator->create_module('forumng', ['course' => $course->id]);

        // Bodge the forum mail processing dates so that it does them in order.
        $DB->set_field('forumng', 'lastemailprocessing', 1, ['id' => $forum1->id]);
        $DB->set_field('forumng', 'lastemailprocessing', 2, ['id' => $forum2->id]);
        $DB->set_field('forumng', 'lastemailprocessing', 3, ['id' => $forum3->id]);

        $admin = get_admin();

        // Forum1: One discussion with one reply.
        $forumgenerator = $generator->get_plugin_generator('mod_forumng');
        list ($discussion1id, $post1id) = $forumgenerator->create_discussion(
                ['forum' => $forum1->id, 'userid' => $admin->id]);
        $reply1 = $forumgenerator->create_post(['discussionid' => $discussion1id,
                'userid' => $admin->id, 'parentpostid' => $post1id]);

        // Forum2: One discussion, no replies.
        list ($discussion2id, $post2id) = $forumgenerator->create_discussion(
                ['forum' => $forum2->id, 'userid' => $admin->id]);

        // Forum3 has no discussions yet.

        // Update all the posts and set them into the past.
        $past = time() - $CFG->forumng_emailafter - 100;
        $DB->set_field('forumng_posts', 'created', $past);

        // Call the mail_list and check which forum it does.
        $list = new \mod_forumng_mail_list(false);
        $this->assertFalse($list->is_finished());
        $this->assertTrue($list->next_forum($forum, $cm, $context, $course));
        $this->assertEquals($forum1->id, $forum->get_id());
        $this->assertTrue($list->next_discussion($discussion));
        $this->assertEquals($discussion1id, $discussion->get_id());
        $this->assertTrue($list->next_post($post, $inreplyto));
        $this->assertEquals($post1id, $post->get_id());
        $this->assertTrue($list->next_post($post, $inreplyto));
        $this->assertEquals($reply1->id, $post->get_id());
        $this->assertFalse($list->next_post($post, $inreplyto));
        $this->assertFalse($list->next_discussion($discussion));
        $this->assertFalse($list->next_forum($forum, $cm, $context, $course));

        // Now call it again and check it does the other forum.
        $list = new \mod_forumng_mail_list(false);
        $this->assertFalse($list->is_finished());
        $this->assertTrue($list->next_forum($forum, $cm, $context, $course));
        $this->assertEquals($forum2->id, $forum->get_id());
        $this->assertTrue($list->next_discussion($discussion));
        $this->assertEquals($discussion2id, $discussion->get_id());
        $this->assertTrue($list->next_post($post, $inreplyto));
        $this->assertEquals($post2id, $post->get_id());
        $this->assertFalse($list->next_post($post, $inreplyto));
        $this->assertFalse($list->next_discussion($discussion));
        $this->assertFalse($list->next_forum($forum, $cm, $context, $course));

        // This time it should be finished as all forums were processed.
        $list = new \mod_forumng_mail_list(false);
        $this->assertTrue($list->is_finished());

        \mod_forumng_mail_list::reset_static_cache();

        // Create a new discussion in forum3 and a new post in forum2.
        list ($discussion3id, $post3id) = $forumgenerator->create_discussion(
                ['forum' => $forum3->id, 'userid' => $admin->id]);
        $reply2 = $forumgenerator->create_post(['discussionid' => $discussion2id,
                'userid' => $admin->id, 'parentpostid' => $post2id]);

        // Update the dates for each.
        $DB->set_field('forumng_posts', 'created', $past + 1, ['id' => $post3id]);
        $DB->set_field('forumng_posts', 'created', $past + 1, ['id' => $reply2->id]);

        // It should process forum 3 next (because not updated yet).
        $list = new \mod_forumng_mail_list(false);
        $this->assertFalse($list->is_finished());
        $this->assertTrue($list->next_forum($forum, $cm, $context, $course));
        $this->assertEquals($forum3->id, $forum->get_id());
        $this->assertTrue($list->next_discussion($discussion));
        $this->assertEquals($discussion3id, $discussion->get_id());
        $this->assertTrue($list->next_post($post, $inreplyto));
        $this->assertEquals($post3id, $post->get_id());
        $this->assertFalse($list->next_post($post, $inreplyto));
        $this->assertFalse($list->next_discussion($discussion));
        $this->assertFalse($list->next_forum($forum, $cm, $context, $course));

        // And now forum 2 again.
        $list = new \mod_forumng_mail_list(false);
        $this->assertFalse($list->is_finished());
        $this->assertTrue($list->next_forum($forum, $cm, $context, $course));
        $this->assertEquals($forum2->id, $forum->get_id());
        $this->assertTrue($list->next_discussion($discussion));
        $this->assertEquals($discussion2id, $discussion->get_id());
        $this->assertTrue($list->next_post($post, $inreplyto));
        $this->assertEquals($reply2->id, $post->get_id());
        $this->assertFalse($list->next_post($post, $inreplyto));
        $this->assertFalse($list->next_discussion($discussion));
        $this->assertFalse($list->next_forum($forum, $cm, $context, $course));

        // Now it should be finished again as all forums were processed once more.
        $list = new \mod_forumng_mail_list(false);
        $this->assertTrue($list->is_finished());
    }

}
