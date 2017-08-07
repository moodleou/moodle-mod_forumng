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
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $adminid = $USER->id;
        $CFG->forumng_trackreadposts = true;
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_forumng');

        $course = $this->get_new_course();
        $etuser = $this->get_new_user('editingteacher', $course->id);
        $suser1 = $this->get_new_user('student', $course->id);
        $suser2 = $this->get_new_user('student', $course->id);

        // Set mark as read to 'manually' for discussions and posts.
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
        // Note etuser is classed as read at forum level as they made last post in discussion.
        $forumetposts = mod_forumng::get_course_forums($course, $etuser->id, mod_forumng::UNREAD_DISCUSSIONS);
        $this->assertFalse($forumetposts[$forum1->get_id()]->has_unread_discussions());
        $this->assertEquals(0, $forumetposts[$forum1->get_id()]->get_num_unread_discussions());
        // Check etuser should be classed as unread at discussion level.
        $discussionetuser = mod_forumng_discussion::get_from_id($did1[0], 0, $etuser->id);
        $this->assertEquals(8, $discussionetuser->get_num_unread_posts());

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
        // Note etuser is classed as read at forum level as they made last post in discussion.
        $forumetposts = mod_forumng::get_course_forums($course, $etuser->id, mod_forumng::UNREAD_DISCUSSIONS);
        $this->assertFalse($forumetposts[$forum1->get_id()]->has_unread_discussions());
        $this->assertEquals(0, $forumetposts[$forum1->get_id()]->get_num_unread_discussions());
        $forumsuser1posts = mod_forumng::get_course_forums($course, $suser1->id, mod_forumng::UNREAD_DISCUSSIONS);
        $this->assertTrue($forumsuser1posts[$forum1->get_id()]->has_unread_discussions());
        $this->assertEquals(1, $forumsuser1posts[$forum1->get_id()]->get_num_unread_discussions());
        $forumsuser2posts = mod_forumng::get_course_forums($course, $suser2->id, mod_forumng::UNREAD_DISCUSSIONS);
        $this->assertTrue($forumsuser2posts[$forum1->get_id()]->has_unread_discussions());
        $this->assertEquals(1, $forumsuser2posts[$forum1->get_id()]->get_num_unread_discussions());

        // Get read counts for editingteacher, include count of their own posts.
        $discussionetuser = mod_forumng_discussion::get_from_id($did1[0], 0, $etuser->id);
        $etuserposts = $discussionetuser->get_num_posts();
        $this->assertEquals(1, count($etuserposts));
        $unreadpostsetuser = $discussionetuser->get_num_unread_posts();
        $this->assertEquals(4, $unreadpostsetuser);
        $readpostsetuser = $DB->get_records('forumng_read_posts', array('userid' => $etuser->id));
        $this->assertCount(4, $readpostsetuser);

        // Get the first count for the student user 1, includes count of their own posts.
        $discussionsuser1 = mod_forumng_discussion::get_from_id($did1[0], 0, $suser1->id);
        $suser1posts = $discussionsuser1->get_num_posts();
        $this->assertEquals(1, count($suser1posts));
        $unreadpostsuser1 = $discussionsuser1->get_num_unread_posts();
        $this->assertEquals(3, $unreadpostsuser1);
        $readpostsuser1 = $DB->get_records('forumng_read_posts', array('userid' => $suser1->id));
        $this->assertCount(4, $readpostsuser1);

        // Get the first count for the student user 2, includes count of their own posts.
        $discussionsuser2 = mod_forumng_discussion::get_from_id($did1[0], 0, $suser2->id);
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
        // Note etuser is classed as read at forum level as they made last post in discussion.
        $forumetposts = mod_forumng::get_course_forums($course, $etuser->id, mod_forumng::UNREAD_DISCUSSIONS);
        $this->assertFalse($forumetposts[$forum1->get_id()]->has_unread_discussions());
        $this->assertEquals(0, $forumetposts[$forum1->get_id()]->get_num_unread_discussions());
        $forumsuser1posts = mod_forumng::get_course_forums($course, $suser1->id, mod_forumng::UNREAD_DISCUSSIONS);
        $this->assertFalse($forumsuser1posts[$forum1->get_id()]->has_unread_discussions());
        $this->assertEquals(0, $forumsuser1posts[$forum1->get_id()]->get_num_unread_discussions());
        $forumsuser2posts = mod_forumng::get_course_forums($course, $suser2->id, mod_forumng::UNREAD_DISCUSSIONS);
        $this->assertFalse($forumsuser2posts[$forum1->get_id()]->has_unread_discussions());
        $this->assertEquals(0, $forumsuser2posts[$forum1->get_id()]->get_num_unread_discussions());

        // Get second read count for editingteacher, includes count of their own posts.
        $discussionetuser = mod_forumng_discussion::get_from_id($did1[0], 0, $etuser->id);
        $etuserposts = $discussionetuser->get_num_posts();
        $this->assertEquals(11, $etuserposts);
        $unreadpostsetuser = $discussionetuser->get_num_unread_posts();
        $this->assertEquals(1, $unreadpostsetuser);
        $readpostsetuser = $DB->get_records('forumng_read_posts', array('userid' => $etuser->id));
        $this->assertCount(7, $readpostsetuser);

        // Get the second count for the student user 1, includes count of their own posts.
        $discussionsuser1 = mod_forumng_discussion::get_from_id($did1[0], 0, $suser1->id);
        $suser1posts = $discussionsuser1->get_num_posts();
        $this->assertEquals(1, count($suser1posts));
        $unreadpostsuser1 = $discussionsuser1->get_num_unread_posts();
        $this->assertEquals(0, $unreadpostsuser1);
        $readpostsuser1 = $DB->get_records('forumng_read_posts', array('userid' => $suser1->id));
        $this->assertCount(7, $readpostsuser1);

        // Get more counts for the student user 2, includes count of their own posts.
        $discussionsuser2 = mod_forumng_discussion::get_from_id($did1[0], 0, $suser2->id);
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
        // Note etuser is classed as read at forum level as they made last post in discussion.
        $forumetposts = mod_forumng::get_course_forums($course, $etuser->id, mod_forumng::UNREAD_DISCUSSIONS);
        $this->assertFalse($forumetposts[$forum1->get_id()]->has_unread_discussions());
        $this->assertEquals(0, $forumetposts[$forum1->get_id()]->get_num_unread_discussions());
        $forumsuser1posts = mod_forumng::get_course_forums($course, $suser1->id, mod_forumng::UNREAD_DISCUSSIONS);
        $this->assertTrue($forumsuser1posts[$forum1->get_id()]->has_unread_discussions());
        $this->assertEquals(1, $forumsuser1posts[$forum1->get_id()]->get_num_unread_discussions());
        $forumsuser2posts = mod_forumng::get_course_forums($course, $suser2->id, mod_forumng::UNREAD_DISCUSSIONS);
        $this->assertTrue($forumsuser2posts[$forum1->get_id()]->has_unread_discussions());
        $this->assertEquals(1, $forumsuser2posts[$forum1->get_id()]->get_num_unread_discussions());

        $discussionetuser = mod_forumng_discussion::get_from_id($did1[0], 0, $etuser->id);
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

    /**
     * Extend tests that check forumns and discussions being marked as read.
     *
     */
    public function test_discussions_mark_read() {
        global $USER, $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $adminid = $USER->id;
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_forumng');
        $course = $this->get_new_course();
        $etuser1 = $this->get_new_user('editingteacher', $course->id);
        $suser1 = $this->get_new_user('student', $course->id);
        $suser2 = $this->get_new_user('student', $course->id);

        $group1 = $this->get_new_group($course->id);
        $group2 = $this->get_new_group($course->id);
        $this->get_new_group_member($group1->id, $etuser1->id);
        $this->get_new_group_member($group1->id, $suser1->id);

        // Create 2 forums (1 group forum, 1 no groups), each with 1 discussion.
        $forum1 = $this->get_new_forumng($course->id, array('groupmode' => VISIBLEGROUPS));
        $forum2 = $this->get_new_forumng($course->id);
        $did1 = $generator->create_discussion(array('course' => $course,
                'forum' => $forum1->get_id(), 'userid' => $etuser1->id, 'groupid' => $group1->id));
        $did2 = $generator->create_discussion(array('course' => $course,
                'forum' => $forum2->get_id(), 'userid' => $etuser1->id));

        // Set the time for offset use.
        $posttime = time();
        // Ensure user prefs allow discussions/posts to be marked as read 'automatically'.
        unset_user_preference('forumng_manualmark', $etuser1);
        unset_user_preference('forumng_manualmark', $suser1);

        // Root post auto marked as read for ET the creator, of forum1 at group level.
        $etforums = mod_forumng::get_course_forums($course, $etuser1->id, mod_forumng::UNREAD_DISCUSSIONS);
        // Forum contains discussion post read by etuser1.
        $this->assertFalse($etforums[$forum1->get_id()]->has_unread_discussions());
        $this->assertEquals(0, $etforums[$forum1->get_id()]->get_num_unread_discussions());

        // Suser1 has NOT read root posts.
        $s1forums = mod_forumng::get_course_forums($course, $suser1->id, mod_forumng::UNREAD_DISCUSSIONS);
        // Forums contain discussion posts NOT read by Suser1.
        $this->assertTrue($s1forums[$forum1->get_id()]->has_unread_discussions());
        $this->assertTrue($s1forums[$forum2->get_id()]->has_unread_discussions());
        // Discussion contains posts NOT read by S1.
        $this->assertEquals(1, $s1forums[$forum1->get_id()]->get_num_unread_discussions());
        $this->assertEquals(1, $s1forums[$forum2->get_id()]->get_num_unread_discussions());

        // User Prefs allow the marking of both discussion root posts as read 'automatically'.
        $posttime = $posttime + 2;
        $did1read = mod_forumng_discussion::get_from_id($did1[0], 0);
        $did1read->mark_read($posttime, $suser1->id);
        $did2read = mod_forumng_discussion::get_from_id($did2[0], 0);
        $did2read->mark_read($posttime, $suser1->id);

        // Check the root posts now marked as read for S1.
        $s1forums = mod_forumng::get_course_forums($course, $suser1->id, mod_forumng::UNREAD_DISCUSSIONS);
        // Forums contain NO discussions not read by Suser1.
        $this->assertFalse($s1forums[$forum1->get_id()]->has_unread_discussions());
        $this->assertFalse($s1forums[$forum2->get_id()]->has_unread_discussions());
        // No forumn discussions contain root posts not read by Suser1.
        $this->assertEquals(0, $s1forums[$forum1->get_id()]->get_num_unread_discussions());
        $this->assertEquals(0, $s1forums[$forum2->get_id()]->get_num_unread_discussions());

        // Set user pref so discussions/posts marked 'manually'.
        set_user_preference('forumng_manualmark', 1, $etuser1);
        set_user_preference('forumng_manualmark', 1, $suser1);

        // Add extra posts to both discussions with time modified offset.
        $posttime = $posttime + 2;
        $did1s1p1 = $generator->create_post(array('discussionid' => $did1[0],
                'parentpostid' => $did1[1], 'userid' => $suser1->id, 'modified' => $posttime));
        $did1etp1 = $generator->create_post(array('discussionid' => $did1[0],
                'parentpostid' => $did1[1], 'userid' => $etuser1->id, 'modified' => $posttime));
        $did1s2p1 = $generator->create_post(array('discussionid' => $did1[0],
                'parentpostid' => $did1[1], 'userid' => $suser2->id, 'modified' => $posttime));
        $did2s1p2 = $generator->create_post(array('discussionid' => $did2[0],
                'parentpostid' => $did2[1], 'userid' => $suser1->id, 'modified' => $posttime));
        $did2etp2 = $generator->create_post(array('discussionid' => $did2[0],
                'parentpostid' => $did2[1], 'userid' => $etuser1->id, 'modified' => $posttime));
        $did2s2p1 = $generator->create_post(array('discussionid' => $did2[0],
                'parentpostid' => $did2[1], 'userid' => $suser2->id, 'modified' => $posttime));
        $did1s1post1 = mod_forumng_post::get_from_id($did1s1p1->id, 0);
        $did2s1post2 = mod_forumng_post::get_from_id($did2s1p2->id, 0);
        $did1etpost1 = mod_forumng_post::get_from_id($did1etp1->id, 0);
        $did2etpost2 = mod_forumng_post::get_from_id($did2etp2->id, 0);
        $did1s2post1 = mod_forumng_post::get_from_id($did1s2p1->id, 0);
        $did2s2post1 = mod_forumng_post::get_from_id($did2s2p1->id, 0);

        // Check read status of new posts.
        $etforums = mod_forumng::get_course_forums($course, $etuser1->id, mod_forumng::UNREAD_DISCUSSIONS);
        // Discussions DO have posts not read by ET.
        $this->assertTrue($etforums[$forum1->get_id()]->has_unread_discussions());
        $this->assertTrue($etforums[$forum2->get_id()]->has_unread_discussions());
        // A number of discussions contain posts NOT read by ET.
        $this->assertEquals(1, $etforums[$forum1->get_id()]->get_num_unread_discussions());
        $this->assertEquals(1, $etforums[$forum2->get_id()]->get_num_unread_discussions());

        $s1forums = mod_forumng::get_course_forums($course, $suser1->id, mod_forumng::UNREAD_DISCUSSIONS);
        // Discussions DO have posts not read by Suser1.
        $this->assertTrue($s1forums[$forum1->get_id()]->has_unread_discussions());
        $this->assertTrue($s1forums[$forum2->get_id()]->has_unread_discussions());
        // A number of discussions contain posts NOT read by Suser1.
        $this->assertEquals(1, $s1forums[$forum1->get_id()]->get_num_unread_discussions());
        $this->assertEquals(1, $s1forums[$forum2->get_id()]->get_num_unread_discussions());

        // Mark posts read individually.
        $posttime = $posttime + 2;
        $did1s1post1->mark_read($posttime, $etuser1->id);// Poster suser1, reader teacher 1.
        $did2s1post2->mark_read($posttime, $etuser1->id);// Poster suser1, reader teacher 1.
        $did1etpost1->mark_read($posttime, $suser1->id);// Poster etuser1, reader suser1.
        $did2etpost2->mark_read($posttime, $suser1->id);// Poster etuser1, reader suser1.
        $did1s2post1->mark_read($posttime, $suser1->id);
        $did2s2post1->mark_read($posttime, $suser1->id);
        $did1s2post1->mark_read($posttime, $etuser1->id);
        $did2s2post1->mark_read($posttime, $etuser1->id);

        // Check read status of newly marked read posts.
        $etforums = mod_forumng::get_course_forums($course, $etuser1->id, mod_forumng::UNREAD_DISCUSSIONS);
        // Discussions have NO posts not read by ET.
        $this->assertFalse($etforums[$forum1->get_id()]->has_unread_discussions());
        $this->assertFalse($etforums[$forum2->get_id()]->has_unread_discussions());
        // No discussions contain posts not read by ET.
        $this->assertEquals(0, $etforums[$forum1->get_id()]->get_num_unread_discussions());
        $this->assertEquals(0, $etforums[$forum2->get_id()]->get_num_unread_discussions());

        $s1forums = mod_forumng::get_course_forums($course, $suser1->id, mod_forumng::UNREAD_DISCUSSIONS);
        // Discussions have NO posts not read by Suser1.
        $this->assertFalse($s1forums[$forum1->get_id()]->has_unread_discussions());
        $this->assertFalse($s1forums[$forum2->get_id()]->has_unread_discussions());
        // No discussions contain posts not read by Suser1.
        $this->assertEquals(0, $s1forums[$forum1->get_id()]->get_num_unread_discussions());
        $this->assertEquals(0, $s1forums[$forum2->get_id()]->get_num_unread_discussions());

        // Get read counts for ET user, includes their own posts,
        // also checks both query_forums() and query_discussions().
        $discussion1etuser = mod_forumng_discussion::get_from_id($did1[0], 0, $etuser1->id);
        $etuserposts = $discussion1etuser->get_num_posts();
        $this->assertEquals(1, count($etuserposts));
        $unreadpostsetuser = $discussion1etuser->get_num_unread_posts();
        $this->assertEquals(0, $unreadpostsetuser);
        $readpostsetuser = $DB->get_records('forumng_read_posts', array('userid' => $etuser1->id));
        $this->assertCount(4, $readpostsetuser);

        $discussion2etuser = mod_forumng_discussion::get_from_id($did2[0], 0, $etuser1->id);
        $etuserposts = $discussion2etuser->get_num_posts();
        $this->assertEquals(1, count($etuserposts));
        $unreadpostsetuser = $discussion2etuser->get_num_unread_posts();
        $this->assertEquals(0, $unreadpostsetuser);
        $readpostsetuser = $DB->get_records('forumng_read_posts', array('userid' => $etuser1->id));
        $this->assertCount(4, $readpostsetuser);

        // Get read counts for the student user 1, includes count of their own posts.
        $discussion1suser1 = mod_forumng_discussion::get_from_id($did1[0], 0, $suser1->id);
        $suser1posts = $discussion1suser1->get_num_posts();
        $this->assertEquals(1, count($suser1posts));
        $unreadpostsuser1 = $discussion1suser1->get_num_unread_posts();
        $this->assertEquals(0, $unreadpostsuser1);
        $readpostsuser1 = $DB->get_records('forumng_read_posts', array('userid' => $suser1->id));
        $this->assertCount(4, $readpostsuser1);

        $discussion2suser1 = mod_forumng_discussion::get_from_id($did2[0], 0, $suser1->id);
        $suser1posts = $discussion2suser1->get_num_posts();
        $this->assertEquals(1, count($suser1posts));
        $unreadpostsuser1 = $discussion2suser1->get_num_unread_posts();
        $this->assertEquals(0, $unreadpostsuser1);
        $readpostsuser1 = $DB->get_records('forumng_read_posts', array('userid' => $suser1->id));
        $this->assertCount(4, $readpostsuser1);

        // Ensure user prefs allow discussions/posts to be marked as read 'automatically'.
        unset_user_preference('forumng_manualmark', $etuser1);
        unset_user_preference('forumng_manualmark', $suser1);

        // Mark read at forumn/discussion level 'automatically'.
        $posttime = $posttime + 2;
        // Mark forumn/discussion 1 read 'automatically'.
        $did1read = mod_forumng_discussion::get_from_id($did1[0], 0);
        $did1read->mark_read($posttime, $etuser1->id);
        $did1read->mark_read($posttime, $suser1->id);
        // Mark forumn/discussion 2 read 'automatically'.
        $did2read = mod_forumng_discussion::get_from_id($did2[0], 0);
        $did2read->mark_read($posttime, $suser1->id);
        $did2read->mark_read($posttime, $etuser1->id);

        // Re-check Forumn read status of forum/discussions/posts.
        $etforums = mod_forumng::get_course_forums($course, $etuser1->id, mod_forumng::UNREAD_DISCUSSIONS);
        // Forum discussions have NO posts not read by ET.
        $this->assertFalse($etforums[$forum1->get_id()]->has_unread_discussions('use'));
        $this->assertFalse($etforums[$forum2->get_id()]->has_unread_discussions());
        // No forum discussion contains posts not read by ET.
        $this->assertEquals(0, $etforums[$forum1->get_id()]->get_num_unread_discussions());
        $this->assertEquals(0, $etforums[$forum2->get_id()]->get_num_unread_discussions());

        $s1forums = mod_forumng::get_course_forums($course, $suser1->id, mod_forumng::UNREAD_DISCUSSIONS);
        // Forum discussions have NO posts not read by S1.
        $this->assertFalse($s1forums[$forum1->get_id()]->has_unread_discussions());
        $this->assertFalse($s1forums[$forum2->get_id()]->has_unread_discussions());
        // No forum discussion contains posts not read by S1.
        $this->assertEquals(0, $s1forums[$forum1->get_id()]->get_num_unread_discussions());
        $this->assertEquals(0, $s1forums[$forum2->get_id()]->get_num_unread_discussions());

        // Add some more posts to discussion 2 only, to remain NOT MARKED manually.
        $posttime = $posttime + 2;
        $did2s1p3 = $generator->create_post(array('discussionid' => $did2[0],
                 'parentpostid' => $did2[1], 'userid' => $suser1->id, 'modified' => $posttime));
        $did2s1post3 = mod_forumng_post::get_from_id($did2s1p3->id, 0);
        $did2etp3 = $generator->create_post(array('discussionid' => $did2[0],
                 'parentpostid' => $did2[1], 'userid' => $suser2->id, 'modified' => $posttime));
        $did2etpost3 = mod_forumng_post::get_from_id($did2etp3->id, 0);

        // Setuser pref so discussions/posts can be marked 'manually'.
        set_user_preference('forumng_manualmark', 1, $etuser1);
        set_user_preference('forumng_manualmark', 1, $suser1);

        // Check read status of forum/discussions/posts.
        $etforums = mod_forumng::get_course_forums($course, $etuser1->id, mod_forumng::UNREAD_DISCUSSIONS);
        // Forumn discussion 1 has NO post not read by ET.
        $this->assertFalse($etforums[$forum1->get_id()]->has_unread_discussions());
        $this->assertEquals(0, $etforums[$forum1->get_id()]->get_num_unread_discussions());
        // Forumn discussion 2 has post NOT read by ET.
        $this->assertTrue($etforums[$forum2->get_id()]->has_unread_discussions());
        $this->assertEquals(1, $etforums[$forum2->get_id()]->get_num_unread_discussions());

        $s1forums = mod_forumng::get_course_forums($course, $suser1->id, mod_forumng::UNREAD_DISCUSSIONS);
        // Forumn discussions 1 has NO post unread by S1.
        $this->assertFalse($s1forums[$forum1->get_id()]->has_unread_discussions());
        $this->assertEquals(0, $s1forums[$forum1->get_id()]->get_num_unread_discussions());
        // Forumn discussion 2 DOES contain posts not read by S1.
        $this->assertTrue($s1forums[$forum2->get_id()]->has_unread_discussions());
        $this->assertEquals(1, $s1forums[$forum2->get_id()]->get_num_unread_discussions());

        // Mark only the older posts read individually.
        $posttime = $posttime + 2;
        $did1s1post1->mark_read($posttime, $etuser1->id);// Poster suser1, reader teacher 1.
        $did2s1post2->mark_read($posttime, $etuser1->id);// Poster suser1, reader teacher 1.
        $did1etpost1->mark_read($posttime, $suser1->id);// Poster etuser1, reader suser1.
        $did2etpost2->mark_read($posttime, $suser1->id);// Poster etuser1, reader suser1.

        // Get read counts for ET user, includes their own posts,
        // also checks both query_forums() and query_discussions().
        $discussion1etuser = mod_forumng_discussion::get_from_id($did1[0], 0, $etuser1->id);
        $etuserposts = $discussion1etuser->get_num_posts();
        $this->assertEquals(4, $etuserposts);
        $unreadpostsetuser = $discussion1etuser->get_num_unread_posts();
        $this->assertEquals(0, $unreadpostsetuser);
        $readpostsetuser = $DB->get_records('forumng_read_posts', array('userid' => $etuser1->id));
        $this->assertCount(2, $readpostsetuser);

        $discussion2etuser = mod_forumng_discussion::get_from_id($did2[0], 0, $etuser1->id);
        $etuserposts = $discussion2etuser->get_num_posts();
        $this->assertEquals(6, $etuserposts);
        $unreadpostsetuser = $discussion2etuser->get_num_unread_posts();
        $this->assertEquals(2, $unreadpostsetuser);

        // Get read counts for the student user 1, includes count of their own posts.
        $discussion1suser1 = mod_forumng_discussion::get_from_id($did1[0], 0, $suser1->id);
        $suser1posts = $discussion1suser1->get_num_posts();
        $this->assertEquals(1, count($suser1posts));
        $unreadpostsuser1 = $discussion1suser1->get_num_unread_posts();
        $this->assertEquals(0, $unreadpostsuser1);
        $readpostsuser1 = $DB->get_records('forumng_read_posts', array('userid' => $suser1->id));
        $this->assertCount(2, $readpostsuser1);

        $discussion2suser1 = mod_forumng_discussion::get_from_id($did2[0], 0, $suser1->id);
        $suser1posts = $discussion2suser1->get_num_posts();
        $this->assertEquals(1, count($suser1posts));
        $unreadpostsuser1 = $discussion2suser1->get_num_unread_posts();
        $this->assertEquals(1, $unreadpostsuser1);
        $readpostsuser1 = $DB->get_records('forumng_read_posts', array('userid' => $suser1->id));
        $this->assertCount(2, $readpostsuser1);

        // Check read status of forum/discussions/posts.
        $etforums = mod_forumng::get_course_forums($course, $etuser1->id, mod_forumng::UNREAD_DISCUSSIONS);
        // Forumn discussion 1  has NO posts not read by ET.
        $this->assertFalse($etforums[$forum1->get_id()]->has_unread_discussions());
        $this->assertEquals(0, $etforums[$forum1->get_id()]->get_num_unread_discussions());
        // Forumn discussion 2 contains posts NOT read by ET.
        $this->assertTrue($etforums[$forum2->get_id()]->has_unread_discussions());
        $this->assertEquals(1, $etforums[$forum2->get_id()]->get_num_unread_discussions());

        $s1forums = mod_forumng::get_course_forums($course, $suser1->id, mod_forumng::UNREAD_DISCUSSIONS);
        // Forumn discussion 1 has NO posts not read by S1.
        $this->assertFalse($etforums[$forum1->get_id()]->has_unread_discussions());
        $this->assertEquals(0, $etforums[$forum1->get_id()]->get_num_unread_discussions());
        // Forumn discussion 2 contains posts NOT read by S1.
        $this->assertTrue($etforums[$forum2->get_id()]->has_unread_discussions());
        $this->assertEquals(1, $etforums[$forum2->get_id()]->get_num_unread_discussions());

        // Mark forumns read 'automatically'.
        $forums = mod_forumng::get_course_forums($course, $etuser1->id);
        $posttime = $posttime + 2;
        $forums[$forum1->get_id()]->mark_read(0, $posttime, $etuser1->id);
        $forums[$forum2->get_id()]->mark_read(0, $posttime, $etuser1->id);

        $forums = mod_forumng::get_course_forums($course, $suser1->id);
        $posttime = $posttime + 2;
        $forums[$forum2->get_id()]->mark_read(0, $posttime, $suser1->id);
        $forums[$forum1->get_id()]->mark_read(0, $posttime, $suser1->id);

        // Check Discussion read status of forum/discussions.
        $etforums = mod_forumng::get_course_forums($course, $etuser1->id, mod_forumng::UNREAD_DISCUSSIONS);
        // Forum discussions have NO posts not read by ET.
        $this->assertFalse($etforums[$forum1->get_id()]->has_unread_discussions());
        $this->assertFalse($etforums[$forum2->get_id()]->has_unread_discussions());
        // Forum discussions contain NO posts unread by ET.
        $this->assertEquals(0, $etforums[$forum1->get_id()]->get_num_unread_discussions());
        $this->assertEquals(0, $etforums[$forum2->get_id()]->get_num_unread_discussions());

        $s1forums = mod_forumng::get_course_forums($course, $suser1->id, mod_forumng::UNREAD_DISCUSSIONS);
        // Forum discussions have NO posts not read by S1.
        $this->assertFalse($s1forums[$forum1->get_id()]->has_unread_discussions());
        $this->assertFalse($s1forums[$forum2->get_id()]->has_unread_discussions());
        // No Forum 1 discussion contains posts not read by S1.
        $this->assertEquals(0, $s1forums[$forum1->get_id()]->get_num_unread_discussions());
        $this->assertEquals(0, $s1forums[$forum2->get_id()]->get_num_unread_discussions());

    }

    public function test_get_formatted_message_normal() {
        global $USER;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $adminid = $USER->id;
        $course = $this->get_new_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_forumng');
        $forum1 = $this->get_new_forumng($course->id, array('groupmode' => VISIBLEGROUPS));
        $did1 = $generator->create_discussion(array('course' => $course,
            'forum' => $forum1->get_id(), 'userid' => $adminid));
        $post1 = $generator->create_post(
            array(
                'discussionid' => $did1[0],
                'parentpostid' => $did1[1],
                'userid' => $adminid,
                'message' => '<img src="@@PLUGINFILE@@/filename.testing.jpg" alt="description">'
                    . '<img src="http://externalhost.com/images.jpg" alt="description" />'
                    . '<img src="https://www.example.com/pluginfile.php/31/mod_bookingsystem/intro/test_image.png" '
                    . 'alt="description" />'
            ));
        $postobj = mod_forumng_post::get_from_id($post1->id, 0);
        $expected = '<img src="https://www.example.com/moodle/pluginfile.php/'
            . $forum1->get_context()->id . '/mod_forumng/message/'
            . $postobj->get_id() . '/filename.testing.jpg" alt="description" />'
            . '<img src="http://externalhost.com/images.jpg" alt="description" />'
            . '<img src="https://www.example.com/pluginfile.php/31/mod_bookingsystem/intro/test_image.png" '
            . 'alt="description" />';
        $actual = $postobj->get_formatted_message();
        $this->assertEquals($expected, $actual);
    }

    public function test_get_formatted_message_email() {
        global $USER;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $adminid = $USER->id;
        $course = $this->get_new_course();
        $salt = context_course::instance($course->id)->id;
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_forumng');
        $forum1 = $this->get_new_forumng($course->id, array('groupmode' => VISIBLEGROUPS));
        $did1 = $generator->create_discussion(array('course' => $course,
            'forum' => $forum1->get_id(), 'userid' => $adminid));
        $post1 = $generator->create_post(
            array(
                'discussionid' => $did1[0],
                'parentpostid' => $did1[1],
                'userid' => $adminid,
                'message' => '<img src="@@PLUGINFILE@@/filename.testing.jpg" alt="description">'
                    . '<img src="http://externalhost.com/images.jpg" alt="description" />'
                    . '<img src="https://www.example.com/pluginfile.php/31/mod_bookingsystem/intro/test_image.png" '
                    . 'alt="description"/>'
            ));
        $postobj = mod_forumng_post::get_from_id($post1->id, 0);
        $expected = '<img src="https://www.example.com/moodle/mod/forumng/pluginfile.php/'
            . $forum1->get_context()->id . '/mod_forumng/message/'
            . $postobj->get_id() . '/filename.testing.jpg/' . sha1('filename.testing.jpg' . $salt) . '" alt="description" />'
            . '<img src="http://externalhost.com/images.jpg" alt="description" />'
            . '<img src="https://www.example.com/pluginfile.php/31/mod_bookingsystem/intro/test_image.png" alt="description" />';
        $actual = $postobj->get_formatted_message(array(
            'email' => true
        ));
        $this->assertEquals($expected, $actual);
    }
}
