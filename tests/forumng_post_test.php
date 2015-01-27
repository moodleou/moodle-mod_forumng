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
 * PHPUnit ForumNG main mod_forumng class tests.
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
 * PHPUnit main forum class testcase.
 *
 * @package mod_forumng
 * @copyright 2014 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_forumng_post_test extends forumng_test_lib {

    /**
     * Unit tests cover following aspects of mod_forumng:
     * Backend functions covered:
     * get_from_id()
     * has_unread_discussions()
     * get_num_unread_discussions()
     * get_num_unread_posts()
     */

    /**
     * Tests that discussions can be marked as read.
     * (unread count etc tested in test_get_course_forums())
     */
    public function test_individual_posts_marked_read() {
        global $USER, $DB, $CFG;
        $this->resetAfterTest();
        $this->setAdminUser();
        $adminid = $USER->id;
        $CFG->forumng_trackreadposts = true;
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_forumng');

        $course = $this->get_new_course();
        $etuser = $this->get_new_user('editingteacher', $course->id);
        $suser1 = $this->get_new_user('student', $course->id);
        $suser2 = $this->get_new_user('student', $course->id);

        // Set mark discussions as read 'manually'.
        set_user_preference('forumng_manualmark', 1, $adminid);
        set_user_preference('forumng_manualmark', 1, $etuser);
        set_user_preference('forumng_manualmark', 1, $suser1);
        set_user_preference('forumng_manualmark', 1, $suser2);

        $forum1 = $this->get_new_forumng($course->id, array('groupmode' => VISIBLEGROUPS,
                'shared' => false, 'cmidnumber' => 'IPMR'));

        $did1 = $generator->create_discussion(array('course' => $course, 'forum' => $forum1->get_id(), 'userid' => $etuser->id));

        $forumetposts = mod_forumng::get_course_forums($course, $etuser->id, mod_forumng::UNREAD_DISCUSSIONS);
        // Check new discussion test state for 1st etuser.
        $this->assertFalse($forumetposts[$forum1->get_id()]->has_unread_discussions());
        $this->assertEquals(0, $forumetposts[$forum1->get_id()]->get_num_unread_discussions());

        $post1 = $generator->create_post(array('discussionid' => $did1[0], 'parentpostid' => $did1[1], 'userid' => $suser1->id));
        $post2 = $generator->create_post(array('discussionid' => $did1[0], 'parentpostid' => $did1[1], 'userid' => $suser2->id));
        $post3 = $generator->create_post(array('discussionid' => $did1[0], 'parentpostid' => $did1[1], 'userid' => $suser1->id));
        $post4 = $generator->create_post(array('discussionid' => $did1[0], 'parentpostid' => $did1[1], 'userid' => $suser2->id));
        $post5 = $generator->create_post(array('discussionid' => $did1[0], 'parentpostid' => $did1[1], 'userid' => $etuser->id));
        $post6 = $generator->create_post(array('discussionid' => $did1[0], 'parentpostid' => $did1[1], 'userid' => $suser1->id));
        $post7 = $generator->create_post(array('discussionid' => $did1[0], 'parentpostid' => $did1[1], 'userid' => $suser2->id));
        $post8 = $generator->create_post(array('discussionid' => $did1[0], 'parentpostid' => $did1[1], 'userid' => $suser1->id));
        $post9 = $generator->create_post(array('discussionid' => $did1[0], 'parentpostid' => $did1[1], 'userid' => $suser2->id));
        $post10 = $generator->create_post(array('discussionid' => $did1[0], 'parentpostid' => $did1[1], 'userid' => $etuser->id));

        $forumadminposts = mod_forumng::get_course_forums($course, $adminid, mod_forumng::UNREAD_DISCUSSIONS);
        $this->assertTrue($forumadminposts[$forum1->get_id()]->has_unread_discussions());
        $this->assertEquals(1, $forumadminposts[$forum1->get_id()]->get_num_unread_discussions());
        $forumetposts = mod_forumng::get_course_forums($course, $etuser->id, mod_forumng::UNREAD_DISCUSSIONS);
        $this->assertTrue($forumetposts[$forum1->get_id()]->has_unread_discussions());
        $this->assertEquals(1, $forumetposts[$forum1->get_id()]->get_num_unread_discussions());
        $forumsuser1posts = mod_forumng::get_course_forums($course, $suser1->id, mod_forumng::UNREAD_DISCUSSIONS);
        $this->assertTrue($forumsuser1posts[$forum1->get_id()]->has_unread_discussions());
        $this->assertEquals(1, $forumsuser1posts[$forum1->get_id()]->get_num_unread_discussions());
        $forumsuser2posts = mod_forumng::get_course_forums($course, $suser2->id, mod_forumng::UNREAD_DISCUSSIONS);
        $this->assertTrue($forumsuser2posts[$forum1->get_id()]->has_unread_discussions());
        $this->assertEquals(1, $forumsuser2posts[$forum1->get_id()]->get_num_unread_discussions());

        // Create post objects.
        $post = mod_forumng_post::get_from_id($did1[1], 0);// Etuser.
        $posta = mod_forumng_post::get_from_id($post1->id, 0);// Suser1.
        $postb = mod_forumng_post::get_from_id($post2->id, 0);// Suser2.
        $postc = mod_forumng_post::get_from_id($post3->id, 0);// Suser1.
        $postd = mod_forumng_post::get_from_id($post4->id, 0);// Suser2.
        $poste = mod_forumng_post::get_from_id($post5->id, 0);// Etuser.
        $postf = mod_forumng_post::get_from_id($post6->id, 0);// Suser1.
        $postg = mod_forumng_post::get_from_id($post7->id, 0);// Suser2.
        $posth = mod_forumng_post::get_from_id($post8->id, 0);// Suser1.
        $posti = mod_forumng_post::get_from_id($post9->id, 0);// Suser2.
        $postj = mod_forumng_post::get_from_id($post10->id, 0);// Etuser.

        // Individuals mark some posts as read.
        $post->mark_read(0, $suser1->id);// Poster Etuser, reader Student 1.
        $post->mark_read(0, $suser2->id);// Poster Etuser, reader Student 2.
        $posta->mark_read(0, $etuser->id);// Poster Suser1, reader Teacher.
        $posta->mark_read(0, $suser2->id);// Poster Suser1, reader Student 1.
        $postb->mark_read(0, $suser1->id);// Poster Suser2, reader Student 2.
        $postb->mark_read(0, $etuser->id);// Poster Suser2, reader Teacher.
        $postc->mark_read(0, $suser2->id);// Poster Suser1, reader Student 2.
        $postc->mark_read(0, $etuser->id);// Poster Suser1, reader Teacher.
        $postd->mark_read(0, $suser1->id);// Poster Suser2, reader Student 1.
        $postd->mark_read(0, $etuser->id);// Poster Suser2, reader Teacher.
        $poste->mark_read(0, $suser1->id);// Poster Etuser, reader Student 1.
        $poste->mark_read(0, $suser2->id);// Poster Etuser, reader Student 2.

        // Test forum read aggregate functions.
        $this->assertTrue(mod_forumng::enabled_read_tracking());
        $forumadminposts = mod_forumng::get_course_forums($course, $adminid, mod_forumng::UNREAD_DISCUSSIONS);
        $this->assertTrue($forumadminposts[$forum1->get_id()]->has_unread_discussions());
        $this->assertEquals(1, $forumadminposts[$forum1->get_id()]->get_num_unread_discussions());
        $forumetposts = mod_forumng::get_course_forums($course, $etuser->id, mod_forumng::UNREAD_DISCUSSIONS);
        $this->assertTrue($forumetposts[$forum1->get_id()]->has_unread_discussions());
        $this->assertEquals(1, $forumetposts[$forum1->get_id()]->get_num_unread_discussions());
        $forumsuser1posts = mod_forumng::get_course_forums($course, $suser1->id, mod_forumng::UNREAD_DISCUSSIONS);
        $this->assertTrue($forumsuser1posts[$forum1->get_id()]->has_unread_discussions());
        $this->assertEquals(1, $forumsuser1posts[$forum1->get_id()]->get_num_unread_discussions());
        $forumsuser2posts = mod_forumng::get_course_forums($course, $suser2->id, mod_forumng::UNREAD_DISCUSSIONS);
        $this->assertTrue($forumsuser2posts[$forum1->get_id()]->has_unread_discussions());
        $this->assertEquals(1, $forumsuser2posts[$forum1->get_id()]->get_num_unread_discussions());

        // Get read counts for editingteacher, include count of their own posts.
        $discussionetuser = mod_forumng_discussion::get_from_id($did1[1], 0, $etuser->id);
        $etuserposts = $discussionetuser->get_num_posts();
        $this->assertEquals(1, count($etuserposts));
        $unreadpostsetuser = $discussionetuser->get_num_unread_posts();
        $this->assertEquals(4, $unreadpostsetuser);
        $readpostsetuser = $DB->get_records('forumng_read_posts', array('userid' => $etuser->id));
        $this->assertCount(4, $readpostsetuser);

        // Get the first count for the student user 1, includes count of their own posts.
        $discussionsuser1 = mod_forumng_discussion::get_from_id($did1[1], 0, $suser1->id);
        $suser1posts = $discussionsuser1->get_num_posts();
        $this->assertEquals(1, count($suser1posts));
        $unreadpostsuser1 = $discussionsuser1->get_num_unread_posts();
        $this->assertEquals(3, $unreadpostsuser1);
        $readpostsuser1 = $DB->get_records('forumng_read_posts', array('userid' => $suser1->id));
        $this->assertCount(4, $readpostsuser1);

        // Get the first count for the student user 2, includes count of their own posts.
        $discussionsuser2 = mod_forumng_discussion::get_from_id($did1[1], 0, $suser2->id);
        $suser2posts = $discussionsuser2->get_num_posts();
        $this->assertEquals(1, count($suser2posts));
        $unreadpostsuser2 = $discussionsuser2->get_num_unread_posts();
        $this->assertEquals(3, $unreadpostsuser2);
        $readpostsuser2 = $DB->get_records('forumng_read_posts', array('userid' => $suser2->id));
        $this->assertCount(4, $readpostsuser2);

        // Individuals mark some more posts as read.
        $postf->mark_read(0, $etuser->id);// Poster Suser1, reader Teacher.
        $postf->mark_read(0, $suser2->id);// Poster Suser1, reader Student 2.
        $postg->mark_read(0, $suser2->id);// Poster Suser2, reader Teacher.
        $postg->mark_read(0, $suser1->id);// Poster Suser2, reader Student 1.
        $posth->mark_read(0, $etuser->id);// Poster Suser1, reader Teacher.
        $posth->mark_read(0, $suser2->id);// Poster Suser1, reader Student 2.
        $posti->mark_read(0, $suser1->id);// Poster Suser2, reader Student 1.
        $posti->mark_read(0, $etuser->id);// Poster Suser2, reader Teacher.
        $postj->mark_read(0, $suser1->id);// Poster Etuser, reader Student 1.
        $postj->mark_read(0, $suser2->id);// Poster Etuser, reader Student 2.

        $forumadminposts = mod_forumng::get_course_forums($course, $adminid, mod_forumng::UNREAD_DISCUSSIONS);
        $this->assertTrue($forumadminposts[$forum1->get_id()]->has_unread_discussions());
        $this->assertEquals(1, $forumadminposts[$forum1->get_id()]->get_num_unread_discussions());
        $forumetposts = mod_forumng::get_course_forums($course, $etuser->id, mod_forumng::UNREAD_DISCUSSIONS);
        $this->assertTrue($forumetposts[$forum1->get_id()]->has_unread_discussions());
        $this->assertEquals(1, $forumetposts[$forum1->get_id()]->get_num_unread_discussions());
        $forumsuser1posts = mod_forumng::get_course_forums($course, $suser1->id, mod_forumng::UNREAD_DISCUSSIONS);
        $this->assertFalse($forumsuser1posts[$forum1->get_id()]->has_unread_discussions());
        $this->assertEquals(0, $forumsuser1posts[$forum1->get_id()]->get_num_unread_discussions());
        $forumsuser2posts = mod_forumng::get_course_forums($course, $suser2->id, mod_forumng::UNREAD_DISCUSSIONS);
        $this->assertFalse($forumsuser2posts[$forum1->get_id()]->has_unread_discussions());
        $this->assertEquals(0, $forumsuser2posts[$forum1->get_id()]->get_num_unread_discussions());

        // Get second read count for editingteacher, includes count of their own posts.
        $discussionetuser = mod_forumng_discussion::get_from_id($did1[1], 0, $etuser->id);
        $etuserposts = $discussionetuser->get_num_posts();
        $this->assertEquals(11, $etuserposts);
        $unreadpostsetuser = $discussionetuser->get_num_unread_posts();
        $this->assertEquals(1, $unreadpostsetuser);
        $readpostsetuser = $DB->get_records('forumng_read_posts', array('userid' => $etuser->id));
        $this->assertCount(7, $readpostsetuser);

        // Get the second count for the student user 1, includes count of their own posts.
        $discussionsuser1 = mod_forumng_discussion::get_from_id($did1[1], 0, $suser1->id);
        $suser1posts = $discussionsuser1->get_num_posts();
        $this->assertEquals(1, count($suser1posts));
        $unreadpostsuser1 = $discussionsuser1->get_num_unread_posts();
        $this->assertEquals(0, $unreadpostsuser1);
        $readpostsuser1 = $DB->get_records('forumng_read_posts', array('userid' => $suser1->id));
        $this->assertCount(7, $readpostsuser1);

        // Get more counts for the student user 2, includes count of their own posts.
        $discussionsuser2 = mod_forumng_discussion::get_from_id($did1[1], 0, $suser2->id);
        $suser2posts = $discussionsuser2->get_num_posts();
        $this->assertEquals(1, count($suser2posts));
        $unreadpostsuser2 = $discussionsuser2->get_num_unread_posts();
        $this->assertEquals(0, $unreadpostsuser2);
        $readpostsuser2 = $DB->get_records('forumng_read_posts', array('userid' => $suser2->id));
        $this->assertCount(8, $readpostsuser2);

        // Mark ALL individual posts as unread.
        $discussionetuser->mark_unread($etuser->id);
        $discussionsuser1->mark_unread($suser1->id);
        $discussionsuser2->mark_unread($suser2->id);

        $forumadminposts = mod_forumng::get_course_forums($course, $adminid, mod_forumng::UNREAD_DISCUSSIONS);
        $this->assertTrue($forumadminposts[$forum1->get_id()]->has_unread_discussions());
        $this->assertEquals(1, $forumadminposts[$forum1->get_id()]->get_num_unread_discussions());
        $forumetposts = mod_forumng::get_course_forums($course, $etuser->id, mod_forumng::UNREAD_DISCUSSIONS);
        $this->assertTrue($forumetposts[$forum1->get_id()]->has_unread_discussions());
        $this->assertEquals(1, $forumetposts[$forum1->get_id()]->get_num_unread_discussions());
        $forumsuser1posts = mod_forumng::get_course_forums($course, $suser1->id, mod_forumng::UNREAD_DISCUSSIONS);
        $this->assertTrue($forumsuser1posts[$forum1->get_id()]->has_unread_discussions());
        $this->assertEquals(1, $forumsuser1posts[$forum1->get_id()]->get_num_unread_discussions());
        $forumsuser2posts = mod_forumng::get_course_forums($course, $suser2->id, mod_forumng::UNREAD_DISCUSSIONS);
        $this->assertTrue($forumsuser2posts[$forum1->get_id()]->has_unread_discussions());
        $this->assertEquals(1, $forumsuser2posts[$forum1->get_id()]->get_num_unread_discussions());

        $discussionetuser = mod_forumng_discussion::get_from_id($did1[1], 0, $etuser->id);
        $etuserposts = $discussionetuser->get_num_posts();
        $this->assertEquals(11, $etuserposts);
        $unreadpostsetuser = $discussionetuser->get_num_unread_posts();
        $this->assertEquals(8, $unreadpostsetuser);
        $readpostsetuser = $DB->get_records('forumng_read_posts', array('userid' => $etuser->id));
        $this->assertCount(0, $readpostsetuser);

        $unreadpostset = $discussionetuser->get_num_unread_posts();
        $unreadpostsuser1 = $discussionsuser1->get_num_unread_posts();
        $unreadpostsuser2 = $discussionsuser2->get_num_unread_posts();
        $this->assertEquals(8, $unreadpostset);
        $this->assertEquals(0, $unreadpostsuser1);
        $this->assertEquals(0, $unreadpostsuser2);
    }

}
