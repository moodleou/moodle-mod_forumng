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

/**
 * PHPUnit main forum class testcase.
 *
 * @package mod_forumng
 * @copyright 2014 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class forumng_test extends forumng_test_lib {

    /**
     * Unit tests cover following aspects of mod_forumng:
     * Getting forum from id and cmid and checking base properties e.g. name, group, context
     * Clones
     * get_course_forums() (inc query_forums())
     * can_? functions (access to functionality)
     * read tracking (unread etc)
     * whole forum + group subscriptions
     * grades
     * completion
     * post date restrictions - get functions (inc quota)
     * // TODO: Unit tests do not cover:
     * discussion functions (in discussion tests instead)
     * reporting email functions
     * ratings functions
     * Anon posting functions
     * maxbytes etc
     */

    /**
     * Tests getting forum object from id and cmid, inc clones.
     */
    public function test_get_forum() {
        $this->resetAfterTest();
        $course = $this->get_new_course();
        // Test get_from_id using test lib.
        $forum = $this->get_new_forumng($course->id, array('name' => 'TEST', 'introduction' => 'abc123'));
        $cm = get_coursemodule_from_instance('forumng', $forum->get_id());
        // Check.
        $this->check_forum_settings($forum, $course, $cm);
        // Check get_from_cmid also works.
        $forum = mod_forumng::get_from_cmid($cm->id, mod_forumng::CLONE_DIRECT);
        $this->check_forum_settings($forum, $course, $cm);
        $this->assertEquals($cm->id, $forum->get_course_module_id());

        // Check clone.
        $forum1 = $this->get_new_forumng($course->id, array('name' => 'TEST', 'intro' => 'abc123',
                'shared' => true, 'cmidnumber' => 'SF1'));
        $this->assertEmpty($forum1->get_clone_details());
        $this->assertTrue($forum1->is_shared());
        $this->assertFalse($forum1->is_clone());

        $course2 = $this->get_new_course();
        $forum2 = $this->get_new_forumng($course2->id, array('name' => 'TEST',
                'usesharedgroup' => array('useshared' => true, 'originalcmidnumber' => 'SF1')));
        $this->assertTrue($forum2->is_shared());
        $this->assertFalse($forum2->is_clone());

        $this->assertEquals($forum1->get_course_module_id(), $forum2->get_course_module_id(true));
        $this->assertEquals($forum1->get_context()->id, $forum2->get_context(true)->id);
        $this->assertEquals($course2->id, $forum2->get_course()->id);
        $this->assertEquals($course->id, $forum2->get_course(true)->id);
        // Discrepancy between get_course_id() [returns original] and get_course()[returns clone].
        $this->assertEquals($course->id, $forum2->get_course_id());

        // Use another instance without clone set to test it knows it is a clone.
        $forum3 = mod_forumng::get_from_cmid($forum2->get_course_module_id(), mod_forumng::CLONE_DIRECT);
        $this->assertTrue($forum3->is_clone());
        $this->assertArrayHasKey($forum3->get_context()->id, $forum1->get_clone_details());
        $this->assertEquals($course2->id, $forum1->get_clone_details()[$forum3->get_context()->id]->courseid);
        $this->assertEquals($forum3->get_context()->id, $forum1->get_clone_details()[$forum3->get_context()->id]->context->id);
        $this->assertEquals($course2->id, $forum3->get_course_id());
    }

    /**
     * Checks forum object created in test_get_forum()
     * In function so same tests can be carried out on multiple forum setups
     * @param object $forum
     * @param object $course
     * @param object $cm
     */
    private function check_forum_settings(mod_forumng $forum, $course, $cm) {
        $this->assertEquals('TEST', $forum->get_name());
        $this->assertEquals('abc123', $forum->get_introduction());
        $this->assertEquals(FORMAT_MOODLE, $forum->get_introduction_format());
        $this->assertEquals($cm->instance, $forum->get_id());
        $this->assertEquals($course->id, $forum->get_course_id());
        $this->assertEquals($course->id, $forum->get_course()->id);
        $this->assertEquals($cm->id, $forum->get_course_module_id());
        $this->assertEquals(mod_forumng::NO_GROUPS, $forum->get_activity_group($cm));
        $context = context_module::instance($cm->id);
        $this->assertEquals($context->id, $forum->get_context()->id);
        $this->assertInstanceOf('forumngtype', $forum->get_type());
    }

    /**
     * Tests get_course_forums() for two users.
     * 1 course, 3 forums (1 clone), 4 discussions (2 by each user).
     */
    public function test_get_course_forums() {
        global $USER;
        $this->resetAfterTest();
        $this->setAdminUser();
        $adminid = $USER->id;
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_forumng');

        $course = $this->get_new_course();
        $course2 = $this->get_new_course();
        $course3 = $this->get_new_course();
        $user2 = $this->get_new_user('student', $course->id);

        $forum1 = $this->get_new_forumng($course->id);
        $forum2 = $this->get_new_forumng($course->id, array('shared' => true, 'cmidnumber' => 'SFCF'));
        $forum3 = $this->get_new_forumng($course2->id, array('usesharedgroup' =>
                array('useshared' => true, 'originalcmidnumber' => 'SFCF')));

        $did1 = $generator->create_discussion(array('course' => $course, 'forum' => $forum1->get_id(), 'userid' => $USER->id));
        $generator->create_post(array('discussionid' => $did1[0], 'parentpostid' => $did1[1], 'userid' => $USER->id));
        $did2 = $generator->create_discussion(array('course' => $course, 'forum' => $forum1->get_id(), 'userid' => $user2->id));
        $generator->create_post(array('discussionid' => $did2[0], 'parentpostid' => $did2[1], 'userid' => $user2->id));
        $generator->create_discussion(array('course' => $course, 'forum' => $forum2->get_id(), 'userid' => $USER->id));
        $generator->create_discussion(array('course' => $course, 'forum' => $forum2->get_id(), 'userid' => $user2->id));

        $resultuser = mod_forumng::get_course_forums($course3, $user2->id, mod_forumng::UNREAD_NONE);
        $this->assertEmpty($resultuser);
        $result1admin = mod_forumng::get_course_forums($course, 0, mod_forumng::UNREAD_NONE);
        $this->assertCount(2, $result1admin);

        $result2admin = mod_forumng::get_course_forums($course2, 0, mod_forumng::UNREAD_NONE, array(), true);
        $this->assertCount(1, $result2admin);// Should be getting master in other course instead.
        $this->assertEquals($course->id, $result2admin[$forum3->get_course_module()->instance]->get_course_id());

        $result3admin = mod_forumng::get_course_forums($course2, 0, mod_forumng::UNREAD_NONE);
        $this->assertCount(1, $result3admin);// Should not be getting master in other course.
        $this->assertEquals($course2->id, $result3admin[$forum3->get_course_module()->instance]->get_course_id());

        $result4admin = mod_forumng::get_course_forums($course, 0, mod_forumng::UNREAD_NONE,
                array($forum1->get_course_module_id()));
        $this->assertCount(1, $result4admin);

        $result5admin = mod_forumng::get_course_forums($course, 0, mod_forumng::UNREAD_DISCUSSIONS);
        $this->assertTrue($result5admin[$forum1->get_id()]->has_unread_discussions());
        $this->assertEquals(1, $result5admin[$forum1->get_id()]->get_num_unread_discussions());

        $result6admin = mod_forumng::get_course_forums($course, 0, mod_forumng::UNREAD_BINARY);
        $this->assertTrue($result6admin[$forum1->get_id()]->has_unread_discussions());
    }

    /**
     * Test access/permissions functions, the can_...() functions in mod_forumng class.
     */
    public function test_can_x() {
        global $USER, $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $adminid = $USER->id;
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_forumng');

        $course = $this->get_new_course();
        $user1 = $this->get_new_user('editingteacher', $course->id);
        $role = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $group1 = $this->get_new_group($course->id);
        $group2 = $this->get_new_group($course->id);
        $this->get_new_group_member($group1->id, $user1->id);
        $forum1 = $this->get_new_forumng($course->id, array('groupmode' => VISIBLEGROUPS));
        $forum2 = $this->get_new_forumng($course->id, array('groupmode' => SEPARATEGROUPS));

        // Test can_access_group().
        $this->assertTrue($forum1->can_access_group($group1->id, true));
        role_change_permission($role->id, $forum1->get_context(), 'moodle/site:accessallgroups', CAP_PREVENT);
        role_change_permission($role->id, $forum2->get_context(), 'moodle/site:accessallgroups', CAP_PREVENT);
        $this->assertTrue($forum1->can_access_group($group1->id, true, $user1->id));
        $this->assertTrue($forum1->can_access_group($group2->id, false, $user1->id));
        $this->assertFalse($forum1->can_access_group($group2->id, true, $user1->id));
        $this->assertFalse($forum2->can_access_group($group2->id, false, $user1->id));

        // Test can_change_subscription() - simple checks, subscriptions tested elsewhere.
        $this->assertFalse($forum1->can_change_subscription(1));// Check guest user.
        $this->assertTrue($forum1->can_change_subscription());
        $this->assertTrue($forum1->can_change_subscription($user1->id));
        $forum3 = $this->get_new_forumng($course->id, array('subscription' => mod_forumng::SUBSCRIPTION_NOT_PERMITTED));
        $this->assertFalse($forum3->can_change_subscription());
        $forum4 = $this->get_new_forumng($course->id, array('subscription' => mod_forumng::SUBSCRIPTION_FORCED));
        $this->assertTrue($forum4->can_change_subscription());
        $this->assertFalse($forum4->can_change_subscription(1));// Check guest user.

        // Test can_create_attachments().
        role_change_permission($role->id, $forum1->get_context(), 'mod/forumng:createattachment', CAP_PREVENT);
        $this->assertFalse($forum1->can_create_attachments($user1->id));
        role_change_permission($role->id, $forum1->get_context(), 'mod/forumng:createattachment', CAP_ALLOW);
        $this->assertTrue($forum1->can_create_attachments($user1->id));

        // Test can_grade().
        $this->assertFalse($forum1->can_grade());
        $forum5 = $this->get_new_forumng($course->id, array('grading' => mod_forumng::GRADING_MANUAL));
        $this->assertTrue($forum5->can_grade());

        // Test can_indicate_moderator().
        role_change_permission($role->id, $forum1->get_context(), 'mod/forumng:postasmoderator', CAP_PREVENT);
        $this->assertFalse($forum1->can_indicate_moderator($user1->id));
        role_change_permission($role->id, $forum1->get_context(), 'mod/forumng:postasmoderator', CAP_ALLOW);
        $this->assertTrue($forum1->can_indicate_moderator($user1->id));

        // Test can_mail_now().
        role_change_permission($role->id, $forum1->get_context(), 'mod/forumng:mailnow', CAP_PREVENT);
        $this->assertFalse($forum1->can_mail_now($user1->id));
        role_change_permission($role->id, $forum1->get_context(), 'mod/forumng:mailnow', CAP_ALLOW);
        $this->assertTrue($forum1->can_mail_now($user1->id));

        // Test can_manage_discussions().
        role_change_permission($role->id, $forum1->get_context(), 'mod/forumng:managediscussions', CAP_PREVENT);
        $this->assertFalse($forum1->can_manage_discussions($user1->id));
        role_change_permission($role->id, $forum1->get_context(), 'mod/forumng:managediscussions', CAP_ALLOW);
        $this->assertTrue($forum1->can_manage_discussions($user1->id));

        // Test can_manage_subscriptions().
        $this->assertFalse($forum3->can_manage_subscriptions());
        role_change_permission($role->id, $forum1->get_context(), 'mod/forumng:managesubscriptions', CAP_PREVENT);
        $this->assertFalse($forum1->can_manage_subscriptions($user1->id));
        role_change_permission($role->id, $forum1->get_context(), 'mod/forumng:managesubscriptions', CAP_ALLOW);
        $this->assertTrue($forum1->can_manage_subscriptions($user1->id));

        // Test can_mark_read().
        $this->assertFalse($forum1->can_mark_read(1));

        // Test can_post_anonymously().
        $this->assertFalse($forum1->can_post_anonymously());
        $forum6 = $this->get_new_forumng($course->id, array('canpostanon' => mod_forumng::CANPOSTANON_MODERATOR));
        role_change_permission($role->id, $forum6->get_context(), 'mod/forumng:postanon', CAP_PREVENT);
        $this->assertFalse($forum6->can_post_anonymously($user1->id));
        role_change_permission($role->id, $forum6->get_context(), 'mod/forumng:postanon', CAP_ALLOW);
        $this->assertTrue($forum6->can_post_anonymously($user1->id));
        $forum11 = $this->get_new_forumng($course->id, array('canpostanon' => mod_forumng::CANPOSTATON_NONMODERATOR));
        role_change_permission($role->id, $forum11->get_context(), 'mod/forumng:postanon', CAP_PREVENT);
        $this->assertFalse($forum11->can_post_anonymously($user1->id));
        $this->assertEquals(get_string('moderator', 'forumng'),
                $forum11->display_author_name($user1, mod_forumng::ASMODERATOR_ANON, false, $user1->id));
        $this->assertEquals(get_string('identityprotected', 'forumng'),
                $forum11->display_author_name($user1, mod_forumng::ASMODERATOR_NO, false, $user1->id));
        role_change_permission($role->id, $forum11->get_context(), 'mod/forumng:postanon', CAP_ALLOW);
        $this->assertTrue($forum11->can_post_anonymously($user1->id));
        $this->assertStringContainsString($user1->firstname,
                $forum11->display_author_name($user1, mod_forumng::ASMODERATOR_ANON, false, $user1->id));
        $this->assertStringContainsString($user1->firstname,
                $forum11->display_author_name($user1, mod_forumng::ASMODERATOR_NO, false, $user1->id));

        // Test can_rate().
        $this->assertFalse($forum1->can_rate(0));
        $basetime = time();
        $forum7 = $this->get_new_forumng($course->id, array('ratingscale' => 5,
                'ratingfrom' => $basetime - 1, 'ratinguntil' => $basetime + 1));
        $this->assertTrue($forum7->can_rate(0));
        $this->setUser($user1);
        role_change_permission($role->id, $forum7->get_context(), 'mod/forumng:rate', CAP_PREVENT);
        $this->assertFalse($forum7->can_rate(0));
        role_change_permission($role->id, $forum7->get_context(), 'mod/forumng:rate', CAP_ALLOW);
        $this->assertTrue($forum7->can_rate(0));
        $this->assertTrue($forum7->can_rate($basetime));
        $this->assertFalse($forum7->can_rate($basetime - 1));
        $this->assertFalse($forum7->can_rate($basetime + 1));
        $this->setAdminUser();

        // Test can_set_important().
        role_change_permission($role->id, $forum1->get_context(), 'mod/forumng:setimportant', CAP_PREVENT);
        $this->assertFalse($forum1->can_set_important($user1->id));
        role_change_permission($role->id, $forum1->get_context(), 'mod/forumng:setimportant', CAP_ALLOW);
        $this->assertTrue($forum1->can_set_important($user1->id));

        // Test can_start_discussion().
        // Check dates.
        $forum8 = $this->get_new_forumng($course->id, array('postingfrom' => (time() + 1000)));
        role_change_permission($role->id, $forum8->get_context(), 'mod/forumng:ignorepostlimits', CAP_PREVENT);
        $this->assertFalse($forum8->can_start_discussion(-1, $whynot, $user1->id));
        $this->assertEmpty($whynot);
        $forum9 = $this->get_new_forumng($course->id, array('postinguntil' => (time() - 1000),
                'maxpostsblock' => 1, 'maxpostsperiod' => 86400));
        role_change_permission($role->id, $forum9->get_context(), 'mod/forumng:ignorepostlimits', CAP_PREVENT);
        $this->assertFalse($forum9->can_start_discussion(-1, $whynot, $user1->id));
        $this->assertEmpty($whynot);
        // Check start discussion capability.
        $this->assertTrue($forum8->can_start_discussion(-1, $whynot));
        role_change_permission($role->id, $forum9->get_context(), 'mod/forumng:ignorepostlimits', CAP_ALLOW);
        $this->assertTrue($forum9->can_start_discussion(-1, $whynot, $user1->id));
        role_change_permission($role->id, $forum9->get_context(), 'mod/forumng:startdiscussion', CAP_PREVENT);
        $this->assertFalse($forum9->can_start_discussion(-1, $whynot, $user1->id));
        $this->assertEquals('startdiscussion_nopermission', $whynot);
        // Check throttling.
        $forum10 = $this->get_new_forumng($course->id, array('maxpostsblock' => 1, 'maxpostsperiod' => 86400));
        role_change_permission($role->id, $forum10->get_context(), 'mod/forumng:ignorepostlimits', CAP_PREVENT);
        $this->assertTrue($forum10->can_start_discussion(-1, $whynot, $user1->id));
        $generator->create_discussion(array('course' => $course, 'forum' => $forum10->get_id(), 'userid' => $user1->id));
        $this->assertFalse($forum10->can_start_discussion(-1, $whynot, $user1->id));

        // Test can_view_discussions().
        role_change_permission($role->id, $forum1->get_context(), 'mod/forumng:viewdiscussion', CAP_PREVENT);
        $this->assertFalse($forum1->can_view_discussions($user1->id));
        role_change_permission($role->id, $forum1->get_context(), 'mod/forumng:viewdiscussion', CAP_ALLOW);
        $this->assertTrue($forum1->can_view_discussions($user1->id));

        // Test can_view_hidden().
        role_change_permission($role->id, $forum1->get_context(), 'mod/forumng:viewallposts', CAP_PREVENT);
        $this->assertFalse($forum1->can_view_hidden($user1->id));
        role_change_permission($role->id, $forum1->get_context(), 'mod/forumng:viewallposts', CAP_ALLOW);
        $this->assertTrue($forum1->can_view_hidden($user1->id));

        // Test can_view_subscribers().
        $this->assertFalse($forum3->can_view_subscribers());// Forum with no subscription.
        role_change_permission($role->id, $forum1->get_context(), 'mod/forumng:viewsubscribers', CAP_PREVENT);
        $this->assertFalse($forum1->can_view_subscribers($user1->id));
        role_change_permission($role->id, $forum1->get_context(), 'mod/forumng:viewsubscribers', CAP_ALLOW);
        $this->assertTrue($forum1->can_view_subscribers($user1->id));
    }

    /**
     * Tests that discussions can be marked as read.
     * (unread count etc tested in test_get_course_forums())
     */
    public function test_mark_read() {
        global $USER, $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $adminid = $USER->id;
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_forumng');

        $course = $this->get_new_course();
        $user1 = $this->get_new_user('editingteacher', $course->id);
        $group1 = $this->get_new_group($course->id);
        $group2 = $this->get_new_group($course->id);
        $this->get_new_group_member($group1->id, $user1->id);
        $this->get_new_group_member($group2->id, $user1->id);
        $forum1 = $this->get_new_forumng($course->id, array('groupmode' => VISIBLEGROUPS,
                'shared' => true, 'cmidnumber' => 'SFGF'));
        $forum2 = $this->get_new_forumng($course->id, array('usesharedgroup' =>
                array('useshared' => true, 'originalcmidnumber' => 'SFGF')));
        $generator->create_discussion(array('course' => $course, 'forum' => $forum1->get_id(),
                'userid' => $USER->id, 'groupid' => $group1->id));
        $generator->create_discussion(array('course' => $course, 'forum' => $forum2->get_id(),
                'userid' => $USER->id, 'groupid' => $group2->id));
        $forums = mod_forumng::get_course_forums($course, $user1->id);
        $this->assertTrue($forums[$forum1->get_id()]->has_unread_discussions());
        $this->assertTrue($forums[$forum2->get_id()]->has_unread_discussions());
        $this->assertEquals(2, $forums[$forum1->get_id()]->get_num_unread_discussions());
        $this->assertEquals(2, $forums[$forum2->get_id()]->get_num_unread_discussions());
        $forums[$forum1->get_id()]->mark_read($group2->id, 0, $user1->id);
        $forums = mod_forumng::get_course_forums($course, $user1->id);
        $this->assertEquals(1, $forums[$forum1->get_id()]->get_num_unread_discussions());
        $this->assertEquals(1, $forums[$forum2->get_id()]->get_num_unread_discussions());
        $forums[$forum2->get_id()]->mark_read($group1->id, 0, $user1->id);
        $forums = mod_forumng::get_course_forums($course, $user1->id);
        $this->assertFalse($forums[$forum1->get_id()]->has_unread_discussions());
        $this->assertFalse($forums[$forum2->get_id()]->has_unread_discussions());
    }

    /**
     * Test subscription functions.
     * Also tests removing user from group and course.
     * Does not test discussion level subscription.
     */
    public function test_subscription() {
        global $USER, $DB;

        $this->assertEquals('array', gettype(mod_forumng::get_subscription_options()));

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $adminid = $USER->id;
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_forumng');

        $course = $this->get_new_course();
        $user1 = $this->get_new_user('editingteacher', $course->id);
        $user2 = $this->get_new_user('student', $course->id);
        $group1 = $this->get_new_group($course->id);
        $group2 = $this->get_new_group($course->id);
        $this->get_new_group_member($group1->id, $user1->id);
        $this->get_new_group_member($group2->id, $user1->id);
        $this->get_new_group_member($group2->id, $user2->id);
        $forum1 = $this->get_new_forumng($course->id, array('groupmode' => VISIBLEGROUPS,
                'subscription' => mod_forumng::SUBSCRIPTION_PERMITTED));
        $forum2 = $this->get_new_forumng($course->id, array('groupmode' => VISIBLEGROUPS,
                'subscription' => mod_forumng::SUBSCRIPTION_FORCED));
        $forum3 = $this->get_new_forumng($course->id, array('groupmode' => VISIBLEGROUPS,
                'subscription' => mod_forumng::SUBSCRIPTION_INITIALLY_SUBSCRIBED));
        $forum4 = $this->get_new_forumng($course->id, array('groupmode' => VISIBLEGROUPS,
                'subscription' => mod_forumng::SUBSCRIPTION_NOT_PERMITTED));

        set_config('forumng_subscription', mod_forumng::SUBSCRIPTION_FORCED);
        $this->assertEquals(mod_forumng::SUBSCRIPTION_FORCED, $forum1->get_effective_subscription_option());
        set_config('forumng_subscription', -1);
        $this->assertEquals(mod_forumng::SUBSCRIPTION_PERMITTED, $forum1->get_effective_subscription_option());
        $this->assertEquals(mod_forumng::SUBSCRIPTION_FORCED, $forum2->get_effective_subscription_option());
        $this->assertEquals(mod_forumng::SUBSCRIPTION_INITIALLY_SUBSCRIBED, $forum3->get_effective_subscription_option());
        $this->assertEquals(mod_forumng::SUBSCRIPTION_NOT_PERMITTED, $forum4->get_effective_subscription_option());

        $this->assertFalse($forum2->is_forced_to_subscribe());
        $this->assertFalse($forum3->is_initially_subscribed());
        $this->assertTrue($forum2->is_forced_to_subscribe($user1->id));
        $this->assertTrue($forum3->is_initially_subscribed($user1->id));

        $this->assertEmpty($forum1->get_auto_subscribers());
        $this->assertEmpty($forum4->get_auto_subscribers());
        $this->assertCount(2, $forum2->get_auto_subscribers());
        $this->assertCount(2, $forum3->get_auto_subscribers());
        $this->assertEmpty($forum1->get_subscribers());
        $this->assertEmpty($forum4->get_subscribers());
        $this->assertCount(1, $forum2->get_subscribers($group1->id));
        $this->assertCount(2, $forum2->get_subscribers());
        $this->assertCount(2, $forum3->get_subscribers());

        $this->assertFalse($forum1->get_subscription_info($user1->id)->wholeforum);
        $this->assertTrue($forum2->get_subscription_info($user1->id)->wholeforum);
        $this->assertTrue($forum3->get_subscription_info($user1->id)->wholeforum);
        $this->assertTrue($forum2->get_subscription_info($user2->id)->wholeforum);
        $this->assertTrue($forum3->get_subscription_info($user2->id)->wholeforum);
        $this->assertFalse($forum4->get_subscription_info($user1->id)->wholeforum);

        $forum1->subscribe($user1->id, $group1->id, true);
        $forum1->subscribe($user1->id, $group2->id);
        $this->assertArrayHasKey($group1->id, $forum1->get_subscription_info($user1->id)->groupids);
        $this->assertArrayHasKey($group2->id, $forum1->get_subscription_info($user1->id)->groupids);
        $this->assertCount(1, $forum1->get_subscribers());
        $forum1->unsubscribe($user1->id, $group1->id, true);
        $forum1->subscribe($user1->id);
        $this->assertEmpty($forum1->get_subscription_info($user1->id)->groupids);
        $this->assertTrue($forum1->get_subscription_info($user1->id)->wholeforum);
        $this->assertCount(1, $forum1->get_subscribers());
        $forum1->unsubscribe($user1->id);
        $forum1->subscribe($user1->id, $group2->id);
        groups_remove_member($group2, $user1);
        $this->assertEmpty($forum1->get_subscribers($group2->id));
        $forum1->subscribe($user1->id);
        $role = $DB->get_record('role', array('shortname' => 'editingteacher'));
        role_unassign($role->id, $user1->id, context_course::instance($course->id)->id);
        $this->assertEmpty($forum1->get_subscribers());
        // Test can subscribe (private) via can_change_subscription().
        $forum = mod_forumng::get_from_id($forum1->get_id(), mod_forumng::CLONE_DIRECT);
        $this->assertFalse($forum->can_change_subscription($user1->id));
    }

    /**
     * Tests the grading functions in mod_forumng.
     * Note manual grading is handled in feature/user posts and not tested here...
     */
    public function test_grading() {
        global $USER, $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $adminid = $USER->id;
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_forumng');

        $course = $this->get_new_course();
        $user1 = $this->get_new_user('editingteacher', $course->id);

        $forum1 = $this->get_new_forumng($course->id, array('grading' => mod_forumng::GRADING_MANUAL, 'gradingscale' => 50));
        $forum1->update_grades($USER->id);// Should do nothing and throw no excpetion...

        $forum2 = $this->get_new_forumng($course->id, array('grading' => mod_forumng::GRADING_COUNT,
                'gradingscale' => 5, 'ratingscale' => 5));
        list ($discuss, $postid) = $generator->create_discussion(array('course' => $course->id,
                'forum' => $forum2->get_id(), 'userid' => $USER->id));
        $post = mod_forumng_post::get_from_id($postid, 0);
        $post->rate(1);// Calls update_grades().
        $post = $generator->create_post(array('discussionid' => $discuss, 'userid' => $USER->id,
                'parentpostid' => $postid));
        $post = mod_forumng_post::get_from_id($post->id, 0);
        $post->rate(3);// Calls update_grades().
        $grades = grade_get_grades($course->id, 'mod', 'forumng', $forum2->get_id(), $USER->id);
        $this->assertEquals(2, abs($grades->items[0]->grades[$USER->id]->grade));
        // Try another grading type, forumng_update_instance() should update grades.
        forumng_update_instance((object) array('instance' => $forum2->get_id(),
                'grading' => mod_forumng::GRADING_MAX));
        $grades = grade_get_grades($course->id, 'mod', 'forumng', $forum2->get_id(), $USER->id);
        $this->assertEquals(3, abs($grades->items[0]->grades[$USER->id]->grade));
        // Try another grading type, forumng_update_instance() should update grades.
        forumng_update_instance((object) array('instance' => $forum2->get_id(),
                'grading' => mod_forumng::GRADING_MIN));
        $grades = grade_get_grades($course->id, 'mod', 'forumng', $forum2->get_id(), $USER->id);
        $this->assertEquals(1, abs($grades->items[0]->grades[$USER->id]->grade));
        // Try another grading type, forumng_update_instance() should update grades.
        forumng_update_instance((object) array('instance' => $forum2->get_id(),
                'grading' => mod_forumng::GRADING_SUM));
        $grades = grade_get_grades($course->id, 'mod', 'forumng', $forum2->get_id(), $USER->id);
        $this->assertEquals(4, abs($grades->items[0]->grades[$USER->id]->grade));
        // Try another grading type, forumng_update_instance() should update grades.
        forumng_update_instance((object) array('instance' => $forum2->get_id(),
                'grading' => mod_forumng::GRADING_AVERAGE));
        $grades = grade_get_grades($course->id, 'mod', 'forumng', $forum2->get_id(), $USER->id);
        $this->assertEquals(2, abs($grades->items[0]->grades[$USER->id]->grade));
        // Try another grading type, forumng_update_instance() should update grades.
        forumng_update_instance((object) array('instance' => $forum2->get_id(),
                'grading' => mod_forumng::GRADING_NONE));
        $grades = grade_get_grades($course->id, 'mod', 'forumng', $forum2->get_id(), $USER->id);
        $this->assertEmpty($grades->items[0]->grades[$USER->id]->grade);
    }

    /**
     * Tests completion.
     */
    public function test_completion() {
        global $USER, $DB, $CFG;

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $adminid = $USER->id;
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_forumng');
        $CFG->enablecompletion = true;
        $course = $this->get_new_course();
        $course->enablecompletion = true;
        $DB->update_record('course', $course);
        $user1 = $this->get_new_user('editingteacher', $course->id);
        // Test post discussions or replies.
        $forum1 = $this->get_new_forumng($course->id, array('completion' => COMPLETION_TRACKING_AUTOMATIC,
                'completionpostsenabled' => 1, 'completionposts' => 1));
        $this->assertEquals(1, $forum1->get_completion_posts());
        $this->assertFalse($forum1->get_completion_state($USER->id, COMPLETION_OR));
        list ($discuss, $postid) = $generator->create_discussion(array('course' => $course->id,
                'forum' => $forum1->get_id(), 'userid' => $USER->id));
        $this->assertTrue($forum1->get_completion_state($USER->id, COMPLETION_OR));
        // Test post discussions.
        $forum2 = $this->get_new_forumng($course->id, array('completion' => COMPLETION_TRACKING_AUTOMATIC,
                'completiondiscussionsenabled' => 1, 'completiondiscussions' => 1));
        $this->assertEquals(1, $forum2->get_completion_discussions());
        $this->assertFalse($forum2->get_completion_state($USER->id, COMPLETION_OR));
        list ($discuss, $postid) = $generator->create_discussion(array('course' => $course->id,
                'forum' => $forum2->get_id(), 'userid' => $USER->id));
        $this->assertTrue($forum2->get_completion_state($USER->id, COMPLETION_OR));
        // Test replies.
        $forum3 = $this->get_new_forumng($course->id, array('completion' => COMPLETION_TRACKING_AUTOMATIC,
                'completionrepliesenabled' => 1, 'completionreplies' => 1));
        $this->assertEquals(1, $forum3->get_completion_replies());
        $this->assertFalse($forum3->get_completion_state($USER->id, COMPLETION_OR));
        list ($discuss, $postid) = $generator->create_discussion(array('course' => $course->id,
                'forum' => $forum3->get_id(), 'userid' => $USER->id));
        $this->assertFalse($forum3->get_completion_state($USER->id, COMPLETION_OR));
        $generator->create_post(array('discussionid' => $discuss, 'userid' => $USER->id,
                'parentpostid' => $postid));
        $this->assertTrue($forum3->get_completion_state($USER->id, COMPLETION_OR));
    }

    /**
     * Tests completion of discussions with wordcount.
     */
    public function test_completion_wordcount_discussions() {
        global $USER, $DB, $CFG;

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_forumng');
        $CFG->enablecompletion = true;
        $course = $this->get_new_course();
        $course->enablecompletion = true;
        $DB->update_record('course', $course);
        $user1 = $this->get_new_user('student', $course->id);
        $user2 = $this->get_new_user('student', $course->id);
        $message6character = html_writer::tag('p', 'Message for post has 6 character');
        $message3character = html_writer::tag('p', 'Message for post');

        // Test discussions with wordcount min.
        $forum1 = $this->get_new_forumng($course->id, ['completion' => COMPLETION_TRACKING_AUTOMATIC,
                'completiondiscussionsenabled' => 1, 'completiondiscussions' => 1, 'completionwordcountminenabled' => 1,
                'completionwordcountmin' => 5]);
        $this->assertEquals(1, $forum1->get_completion_discussions());
        $this->assertFalse($forum1->get_completion_state($USER->id, COMPLETION_OR));
        // User1 meets the conditions.
        list ($discuss, $postid) = $generator->create_discussion(['course' => $course->id,
                'forum' => $forum1->get_id(), 'userid' => $user1->id, 'message' => $message6character]);
        $this->assertTrue($forum1->get_completion_state($user1->id, COMPLETION_OR));
        // User2 does not meet the conditions.
        list ($discuss, $postid) = $generator->create_discussion(['course' => $course->id,
                'forum' => $forum1->get_id(), 'userid' => $user2->id, 'message' => $message3character]);
        $this->assertFalse($forum1->get_completion_state($user2->id, COMPLETION_OR));

        // Test discussions with wordcount max.
        $forum2 = $this->get_new_forumng($course->id, ['completion' => COMPLETION_TRACKING_AUTOMATIC,
                'completiondiscussionsenabled' => 1, 'completiondiscussions' => 1, 'completionwordcountmaxenabled' => 1,
                'completionwordcountmax' => 3]);
        $this->assertEquals(1, $forum2->get_completion_discussions());
        $this->assertFalse($forum2->get_completion_state($USER->id, COMPLETION_OR));
        // User1 does not meet the conditions.
        list ($discuss, $postid) = $generator->create_discussion(['course' => $course->id,
                'forum' => $forum2->get_id(), 'userid' => $user1->id, 'message' => $message6character]);
        $this->assertFalse($forum2->get_completion_state($user1->id, COMPLETION_OR));
        // User2 meets the conditions.
        list ($discuss, $postid) = $generator->create_discussion(['course' => $course->id,
                'forum' => $forum2->get_id(), 'userid' => $user2->id, 'message' => $message3character]);
        $this->assertTrue($forum2->get_completion_state($user2->id, COMPLETION_OR));

        // Test discussions with wordcount min and max.
        $forum3 = $this->get_new_forumng($course->id, ['completion' => COMPLETION_TRACKING_AUTOMATIC,
                'completiondiscussionsenabled' => 1, 'completiondiscussions' => 1, 'completionwordcountminenabled' => 1,
                'completionwordcountmin' => 3, 'completionwordcountmaxenabled' => 1, 'completionwordcountmax' => 5]);
        $this->assertEquals(1, $forum3->get_completion_discussions());
        $this->assertFalse($forum3->get_completion_state($USER->id, COMPLETION_OR));
        // User1 does not meet the conditions.
        list ($discuss, $postid) = $generator->create_discussion(['course' => $course->id,
                'forum' => $forum3->get_id(), 'userid' => $user1->id, 'message' => $message6character]);
        $this->assertFalse($forum3->get_completion_state($user1->id, COMPLETION_OR));
        // User2 meets the conditions.
        list ($discuss, $postid) = $generator->create_discussion(['course' => $course->id,
                'forum' => $forum3->get_id(), 'userid' => $user2->id, 'message' => $message3character]);
        $this->assertTrue($forum3->get_completion_state($user2->id, COMPLETION_OR));
    }

    /**
     * Tests completion of replies with wordcount.
     */
    public function test_completion_wordcount_replies() {
        global $USER, $DB, $CFG;

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_forumng');
        $CFG->enablecompletion = true;
        $course = $this->get_new_course();
        $course->enablecompletion = true;
        $DB->update_record('course', $course);
        $user1 = $this->get_new_user('student', $course->id);
        $user2 = $this->get_new_user('student', $course->id);
        $message6character = html_writer::tag('p', 'Message for post has 6 character');
        $message3character = html_writer::tag('p', 'Message for post');

        // Test replies with wordcount min.
        $forum1 = $this->get_new_forumng($course->id, ['completion' => COMPLETION_TRACKING_AUTOMATIC,
                'completionrepliesenabled' => 1, 'completionreplies' => 1, 'completionwordcountminenabled' => 1,
                'completionwordcountmin' => 5]);
        $this->assertEquals(1, $forum1->get_completion_replies());
        $this->assertFalse($forum1->get_completion_state($USER->id, COMPLETION_OR));
        list ($discuss, $postid) = $generator->create_discussion(['course' => $course->id,
                'forum' => $forum1->get_id(), 'userid' => $user1->id]);

        // User1 meets the conditions.
        $generator->create_post(['discussionid' => $discuss, 'userid' => $user1->id,
                'parentpostid' => $postid, 'message' => $message6character]);
        $this->assertTrue($forum1->get_completion_state($user1->id, COMPLETION_OR));
        // User2 does not meet the conditions.
        $generator->create_post(['discussionid' => $discuss, 'userid' => $user2->id,
                'parentpostid' => $postid, 'message' => $message3character]);
        $this->assertFalse($forum1->get_completion_state($user2->id, COMPLETION_OR));

        // Test replies with wordcount max.
        $forum2 = $this->get_new_forumng($course->id, ['completion' => COMPLETION_TRACKING_AUTOMATIC,
                'completionrepliesenabled' => 1, 'completionreplies' => 1, 'completionwordcountmaxenabled' => 1,
                'completionwordcountmax' => 3]);
        $this->assertEquals(1, $forum2->get_completion_replies());
        $this->assertFalse($forum2->get_completion_state($USER->id, COMPLETION_OR));
        list ($discuss, $postid) = $generator->create_discussion(['course' => $course->id,
                'forum' => $forum2->get_id(), 'userid' => $user1->id]);

        // User1 does not meet the conditions.
        $generator->create_post(['discussionid' => $discuss, 'userid' => $user1->id,
                'parentpostid' => $postid, 'message' => $message6character]);
        $this->assertFalse($forum2->get_completion_state($user1->id, COMPLETION_OR));
        // User2 meets the conditions.
        $generator->create_post(['discussionid' => $discuss, 'userid' => $user2->id,
                'parentpostid' => $postid, 'message' => $message3character]);
        $this->assertTrue($forum2->get_completion_state($user2->id, COMPLETION_OR));

        // Test replies with wordcount min and max.
        $forum3 = $this->get_new_forumng($course->id, ['completion' => COMPLETION_TRACKING_AUTOMATIC,
                'completionrepliesenabled' => 1, 'completionreplies' => 1, 'completionwordcountminenabled' => 1,
                'completionwordcountmin' => 3, 'completionwordcountmaxenabled' => 1, 'completionwordcountmax' => 5]);
        $this->assertEquals(1, $forum3->get_completion_replies());
        $this->assertFalse($forum3->get_completion_state($USER->id, COMPLETION_OR));
        list ($discuss, $postid) = $generator->create_discussion(['course' => $course->id,
                'forum' => $forum3->get_id(), 'userid' => $user1->id]);

        // User1 does not meet the conditions.
        $generator->create_post(['discussionid' => $discuss, 'userid' => $user1->id,
                'parentpostid' => $postid, 'message' => $message6character]);
        $this->assertFalse($forum3->get_completion_state($user1->id, COMPLETION_OR));
        // User2 meets the conditions.
        $generator->create_post(['discussionid' => $discuss, 'userid' => $user2->id,
                'parentpostid' => $postid, 'message' => $message3character]);
        $this->assertTrue($forum3->get_completion_state($user2->id, COMPLETION_OR));
    }

    /**
     * Tests completion of posts with wordcount.
     */
    public function test_completion_wordcount_posts() {
        global $USER, $DB, $CFG;

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_forumng');
        $CFG->enablecompletion = true;
        $course = $this->get_new_course();
        $course->enablecompletion = true;
        $DB->update_record('course', $course);
        $user1 = $this->get_new_user('student', $course->id);
        $user2 = $this->get_new_user('student', $course->id);
        $message6character = html_writer::tag('p', 'Message for post has 6 character');
        $message3character = html_writer::tag('p', 'Message for post');

        // Test posts with wordcount min.
        $forum1 = $this->get_new_forumng($course->id, ['completion' => COMPLETION_TRACKING_AUTOMATIC,
                'completionpostsenabled' => 1, 'completionposts' => 1, 'completionwordcountminenabled' => 1,
                'completionwordcountmin' => 5]);
        $this->assertEquals(1, $forum1->get_completion_posts());
        $this->assertFalse($forum1->get_completion_state($USER->id, COMPLETION_OR));
        // User1 meets the conditions.
        list ($discuss, $postid) = $generator->create_discussion(['course' => $course->id,
                'forum' => $forum1->get_id(), 'userid' => $user1->id, 'message' => $message6character]);
        $this->assertTrue($forum1->get_completion_state($user1->id, COMPLETION_OR));
        // User2 does not meet the conditions.
        list ($discuss, $postid) = $generator->create_discussion(['course' => $course->id,
                'forum' => $forum1->get_id(), 'userid' => $user2->id, 'message' => $message3character]);
        $this->assertFalse($forum1->get_completion_state($user2->id, COMPLETION_OR));

        // Test posts with wordcount max.
        $forum2 = $this->get_new_forumng($course->id, ['completion' => COMPLETION_TRACKING_AUTOMATIC,
                'completionpostsenabled' => 1, 'completionposts' => 1, 'completionwordcountmaxenabled' => 1,
                'completionwordcountmax' => 3]);
        $this->assertEquals(1, $forum2->get_completion_posts());
        $this->assertFalse($forum2->get_completion_state($USER->id, COMPLETION_OR));
        // User1 does not meet the conditions.
        list ($discuss, $postid) = $generator->create_discussion(['course' => $course->id,
                'forum' => $forum2->get_id(), 'userid' => $user1->id, 'message' => $message6character]);
        $this->assertFalse($forum2->get_completion_state($user1->id, COMPLETION_OR));
        // User2 meets the conditions.
        list ($discuss, $postid) = $generator->create_discussion(['course' => $course->id,
                'forum' => $forum2->get_id(), 'userid' => $user2->id, 'message' => $message3character]);
        $this->assertTrue($forum2->get_completion_state($user2->id, COMPLETION_OR));

        // Test posts with wordcount min and max.
        $forum3 = $this->get_new_forumng($course->id, ['completion' => COMPLETION_TRACKING_AUTOMATIC,
                'completionpostsenabled' => 1, 'completionposts' => 1, 'completionwordcountminenabled' => 1,
                'completionwordcountmin' => 3, 'completionwordcountmaxenabled' => 1, 'completionwordcountmax' => 5]);
        $this->assertEquals(1, $forum3->get_completion_posts());
        $this->assertFalse($forum3->get_completion_state($USER->id, COMPLETION_OR));
        // User1 does not meet the conditions.
        list ($discuss, $postid) = $generator->create_discussion(['course' => $course->id,
                'forum' => $forum3->get_id(), 'userid' => $user1->id, 'message' => $message6character]);
        $this->assertFalse($forum3->get_completion_state($user1->id, COMPLETION_OR));
        // User2 meets the conditions.
        list ($discuss, $postid) = $generator->create_discussion(['course' => $course->id,
                'forum' => $forum3->get_id(), 'userid' => $user2->id, 'message' => $message3character]);
        $this->assertTrue($forum3->get_completion_state($user2->id, COMPLETION_OR));
    }

    /**
     * Tests completion of posts, discussions, replies with wordcount.
     */
    public function test_completion_wordcount_all_require() {
        global $USER, $DB, $CFG;

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_forumng');
        $CFG->enablecompletion = true;
        $course = $this->get_new_course();
        $course->enablecompletion = true;
        $DB->update_record('course', $course);
        $user1 = $this->get_new_user('student', $course->id);
        $user2 = $this->get_new_user('student', $course->id);
        $message6character = html_writer::tag('p', 'Message for post has 6 character');
        $message3character = html_writer::tag('p', 'Message for post');

        // Test all require with wordcount min/max.
        $forum1 = $this->get_new_forumng($course->id, ['completion' => COMPLETION_TRACKING_AUTOMATIC,
                'completionpostsenabled' => 1, 'completionposts' => 1,
                'completiondiscussionsenabled' => 1, 'completiondiscussions' => 1,
                'completionrepliesenabled' => 1, 'completionreplies' => 2,
                'completionwordcountminenabled' => 1, 'completionwordcountmin' => 3,
                'completionwordcountmaxenabled' => 1, 'completionwordcountmax' => 5]);
        $this->assertEquals(1, $forum1->get_completion_posts());
        $this->assertEquals(1, $forum1->get_completion_discussions());
        $this->assertEquals(2, $forum1->get_completion_replies());
        $this->assertFalse($forum1->get_completion_state($USER->id, COMPLETION_OR));
        list ($discuss, $postid) = $generator->create_discussion(['course' => $course->id,
                'forum' => $forum1->get_id(), 'userid' => $user1->id, 'message' => $message3character]);

        // User1 meets the conditions [disscussion:pass, replies:pass].
        $generator->create_post(['discussionid' => $discuss, 'userid' => $user1->id,
                'parentpostid' => $postid, 'message' => $message3character]);
        $generator->create_post(['discussionid' => $discuss, 'userid' => $user1->id,
                'parentpostid' => $postid, 'message' => $message3character]);
        $this->assertTrue($forum1->get_completion_state($user1->id, COMPLETION_AND));

        // User2 does not meet the conditions [discussion:pass, replies:fail].
        $generator->create_post(['discussionid' => $discuss, 'userid' => $user2->id,
                'parentpostid' => $postid, 'message' => $message3character]);
        // Fails because this step reply does not meet the conditions.
        $generator->create_post(['discussionid' => $discuss, 'userid' => $user2->id,
                'parentpostid' => $postid, 'message' => $message6character]);
        $this->assertFalse($forum1->get_completion_state($user2->id, COMPLETION_AND));
    }

    /**
     * Tests completion of discussions with tracking time.
     */
    public function test_completion_tracking_time_discussions() {
        global $USER, $DB, $CFG;

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_forumng');
        $CFG->enablecompletion = true;
        $course = $this->get_new_course();
        $course->enablecompletion = true;
        $DB->update_record('course', $course);
        $user1 = $this->get_new_user('student', $course->id);
        $user2 = $this->get_new_user('student', $course->id);
        $now = time();
        $timetrackingfrom1 = (new \DateTime())->setTimestamp($now)->modify('-2 day');
        $timetrackingto1 = (new \DateTime())->setTimestamp($now)->modify('-1 day');

        $timetrackingfrom2 = (new \DateTime())->setTimestamp($now)->modify('+1 day');
        $timetrackingto2 = (new \DateTime())->setTimestamp($now)->modify('+2 day');

        $timetrackingfrom3 = (new \DateTime())->setTimestamp($now)->modify('-1 day');
        $timetrackingto3 = (new \DateTime())->setTimestamp($now)->modify('+1 day');

        // Test discussions with time tracking from.
        $forum1 = $this->get_new_forumng($course->id, ['completion' => COMPLETION_TRACKING_AUTOMATIC,
                'completiondiscussionsenabled' => 1, 'completiondiscussions' => 1, '
                timetrackingfrom' => $timetrackingfrom1->getTimestamp()]);
        $this->assertEquals(1, $forum1->get_completion_discussions());
        $this->assertFalse($forum1->get_completion_state($USER->id, COMPLETION_OR));
        $forum2 = $this->get_new_forumng($course->id, ['completion' => COMPLETION_TRACKING_AUTOMATIC,
                'completiondiscussionsenabled' => 1, 'completiondiscussions' => 1,
                'timetrackingfrom' => $timetrackingfrom2->getTimestamp()]);
        $this->assertEquals(1, $forum2->get_completion_discussions());
        $this->assertFalse($forum2->get_completion_state($USER->id, COMPLETION_OR));

        // User1 meets the conditions.
        list ($discuss, $postid) = $generator->create_discussion(['course' => $course->id,
                'forum' => $forum1->get_id(), 'userid' => $user1->id ]);

        $this->assertTrue($forum1->get_completion_state($user1->id, COMPLETION_OR));

        // User2 does not meet the conditions.
        list ($discuss, $postid) = $generator->create_discussion(['course' => $course->id,
                'forum' => $forum2->get_id(), 'userid' => $user2->id ]);

        $this->assertFalse($forum2->get_completion_state($user2->id, COMPLETION_OR));

        // Test discussions with time tracking to.
        $forum3 = $this->get_new_forumng($course->id, ['completion' => COMPLETION_TRACKING_AUTOMATIC,
                'completiondiscussionsenabled' => 1, 'completiondiscussions' => 1,
                'timetrackingto' => $timetrackingto1->getTimestamp()]);
        $this->assertEquals(1, $forum3->get_completion_discussions());
        $this->assertFalse($forum3->get_completion_state($USER->id, COMPLETION_OR));

        $forum4 = $this->get_new_forumng($course->id, ['completion' => COMPLETION_TRACKING_AUTOMATIC,
                'completiondiscussionsenabled' => 1, 'completiondiscussions' => 1,
                'timetrackingto' => $timetrackingto2->getTimestamp()]);
        $this->assertEquals(1, $forum4->get_completion_discussions());
        $this->assertFalse($forum4->get_completion_state($USER->id, COMPLETION_OR));
        // User1 does not meet the conditions.
        list ($discuss, $postid) = $generator->create_discussion(['course' => $course->id,
                'forum' => $forum3->get_id(), 'userid' => $user1->id]);
        $this->assertFalse($forum3->get_completion_state($user1->id, COMPLETION_OR));
        // User2 meets the conditions.
        list ($discuss, $postid) = $generator->create_discussion(['course' => $course->id,
                'forum' => $forum4->get_id(), 'userid' => $user2->id]);
        $this->assertTrue($forum4->get_completion_state($user2->id, COMPLETION_OR));

        // Test discussions with time tracking from min and time tracking to.
        $forum5 = $this->get_new_forumng($course->id, ['completion' => COMPLETION_TRACKING_AUTOMATIC,
                'completiondiscussionsenabled' => 1, 'completiondiscussions' => 1,
                'timetrackingfrom' => $timetrackingfrom1->getTimestamp(), 'timetrackingto' => $timetrackingto1->getTimestamp()]);
        $this->assertEquals(1, $forum5->get_completion_discussions());
        $this->assertFalse($forum5->get_completion_state($USER->id, COMPLETION_OR));
        $forum6 = $this->get_new_forumng($course->id, ['completion' => COMPLETION_TRACKING_AUTOMATIC,
                'completiondiscussionsenabled' => 1, 'completiondiscussions' => 1,
                'timetrackingfrom' => $timetrackingfrom3->getTimestamp(), 'timetrackingto' => $timetrackingto3->getTimestamp()]);
        $this->assertEquals(1, $forum6->get_completion_discussions());
        $this->assertFalse($forum6->get_completion_state($USER->id, COMPLETION_OR));
        // User1 does not meet the conditions.
        list ($discuss, $postid) = $generator->create_discussion(['course' => $course->id,
                'forum' => $forum5->get_id(), 'userid' => $user1->id]);
        $this->assertFalse($forum5->get_completion_state($user1->id, COMPLETION_OR));
        // User2 meets the conditions.
        list ($discuss, $postid) = $generator->create_discussion(['course' => $course->id,
                'forum' => $forum6->get_id(), 'userid' => $user2->id]);
        $this->assertTrue($forum6->get_completion_state($user2->id, COMPLETION_OR));
    }

    /**
     * Tests completion of replies with tracking time.
     */
    public function test_completion_tracking_time_replies() {
        global $USER, $DB, $CFG;

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_forumng');
        $CFG->enablecompletion = true;
        $course = $this->get_new_course();
        $course->enablecompletion = true;
        $DB->update_record('course', $course);
        $user1 = $this->get_new_user('student', $course->id);
        $user2 = $this->get_new_user('student', $course->id);
        $now = time();
        $timetrackingfrom1 = (new \DateTime())->setTimestamp($now)->modify('-2 day');
        $timetrackingto1 = (new \DateTime())->setTimestamp($now)->modify('-1 day');

        $timetrackingfrom2 = (new \DateTime())->setTimestamp($now)->modify('+1 day');
        $timetrackingto2 = (new \DateTime())->setTimestamp($now)->modify('+2 day');

        $timetrackingfrom3 = (new \DateTime())->setTimestamp($now)->modify('-1 day');
        $timetrackingto3 = (new \DateTime())->setTimestamp($now)->modify('+1 day');

        // Test replies with time tracking from.
        $forum1 = $this->get_new_forumng($course->id, ['completion' => COMPLETION_TRACKING_AUTOMATIC,
                'completionrepliesenabled' => 1, 'completionreplies' => 1,
                'timetrackingfrom' => $timetrackingfrom1->getTimestamp()]);
        $this->assertEquals(1, $forum1->get_completion_replies());
        $this->assertFalse($forum1->get_completion_state($USER->id, COMPLETION_OR));
        $forum2 = $this->get_new_forumng($course->id, ['completion' => COMPLETION_TRACKING_AUTOMATIC,
                'completionrepliesenabled' => 1, 'completionreplies' => 1,
                'timetrackingfrom' => $timetrackingfrom2->getTimestamp()]);
        $this->assertEquals(1, $forum2->get_completion_replies());
        $this->assertFalse($forum2->get_completion_state($USER->id, COMPLETION_OR));
        list ($discuss1, $postid1) = $generator->create_discussion(['course' => $course->id,
                'forum' => $forum1->get_id(), 'userid' => $user1->id]);
        list ($discuss2, $postid2) = $generator->create_discussion(['course' => $course->id,
                'forum' => $forum2->get_id(), 'userid' => $user2->id]);
        // User1 meets the conditions.
        $generator->create_post(['discussionid' => $discuss1, 'userid' => $user1->id,
                'parentpostid' => $postid1]);
        $this->assertTrue($forum1->get_completion_state($user1->id, COMPLETION_OR));

        // User2 does not meet the conditions.
        $generator->create_post(['discussionid' => $discuss2, 'userid' => $user2->id,
                'parentpostid' => $postid2]);
        $this->assertFalse($forum1->get_completion_state($user2->id, COMPLETION_OR));

        // Test discussions with time tracking to.
        $forum3 = $this->get_new_forumng($course->id, ['completion' => COMPLETION_TRACKING_AUTOMATIC,
                'completionrepliesenabled' => 1, 'completionreplies' => 1, 'timetrackingto' => $timetrackingto1->getTimestamp()]);
        $this->assertEquals(1, $forum3->get_completion_replies());
        $this->assertFalse($forum3->get_completion_state($USER->id, COMPLETION_OR));

        $forum4 = $this->get_new_forumng($course->id, ['completion' => COMPLETION_TRACKING_AUTOMATIC,
                'completionrepliesenabled' => 1, 'completionreplies' => 1, 'timetrackingto' => $timetrackingto2->getTimestamp()]);
        $this->assertEquals(1, $forum4->get_completion_replies());
        $this->assertFalse($forum4->get_completion_state($USER->id, COMPLETION_OR));

        list ($discuss3, $postid3) = $generator->create_discussion(['course' => $course->id,
                'forum' => $forum3->get_id(), 'userid' => $user1->id]);
        list ($discuss4, $postid4) = $generator->create_discussion(['course' => $course->id,
                'forum' => $forum4->get_id(), 'userid' => $user2->id]);

        // User1 does not meet the conditions.
        $generator->create_post(['discussionid' => $discuss3, 'userid' => $user1->id,
                'parentpostid' => $postid3]);
        $this->assertFalse($forum3->get_completion_state($user1->id, COMPLETION_OR));
        // User2 meets the conditions.
        $generator->create_post(['discussionid' => $discuss4, 'userid' => $user2->id,
                'parentpostid' => $postid4]);
        $this->assertTrue($forum4->get_completion_state($user2->id, COMPLETION_OR));

        // Test discussions with time tracking from min and time tracking to.
        $forum5 = $this->get_new_forumng($course->id, ['completion' => COMPLETION_TRACKING_AUTOMATIC,
                'completionrepliesenabled' => 1, 'completionreplies' => 1, 'timetrackingfrom' => $timetrackingfrom1->getTimestamp(),
                'timetrackingto' => $timetrackingto1->getTimestamp()]);
        $this->assertEquals(1, $forum5->get_completion_replies());
        $this->assertFalse($forum5->get_completion_state($USER->id, COMPLETION_OR));
        $forum6 = $this->get_new_forumng($course->id, ['completion' => COMPLETION_TRACKING_AUTOMATIC,
                'completionrepliesenabled' => 1, 'completionreplies' => 1, 'timetrackingfrom' => $timetrackingfrom3->getTimestamp(),
                'timetrackingto' => $timetrackingto3->getTimestamp()]);
        $this->assertEquals(1, $forum6->get_completion_replies());
        $this->assertFalse($forum6->get_completion_state($USER->id, COMPLETION_OR));

        list ($discuss5, $postid5) = $generator->create_discussion(['course' => $course->id,
                'forum' => $forum5->get_id(), 'userid' => $user1->id]);
        list ($discuss6, $postid6) = $generator->create_discussion(['course' => $course->id,
                'forum' => $forum6->get_id(), 'userid' => $user2->id]);

        // User1 does not meet the conditions.
        $generator->create_post(['discussionid' => $discuss5, 'userid' => $user1->id,
                'parentpostid' => $postid5]);
        $this->assertFalse($forum5->get_completion_state($user1->id, COMPLETION_OR));
        // User2 meets the conditions.
        $generator->create_post(['discussionid' => $discuss6, 'userid' => $user2->id,
                'parentpostid' => $postid6]);
        $this->assertTrue($forum6->get_completion_state($user2->id, COMPLETION_OR));
    }

    /**
     * Tests completion of posts with tracking time.
     */
    public function test_completion_tracking_time_posts() {
        global $USER, $DB, $CFG;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_forumng');
        $CFG->enablecompletion = true;
        $course = $this->get_new_course();
        $course->enablecompletion = true;
        $DB->update_record('course', $course);
        $user1 = $this->get_new_user('student', $course->id);
        $user2 = $this->get_new_user('student', $course->id);
        $now = time();
        $timetrackingfrom1 = (new \DateTime())->setTimestamp($now)->modify('-2 day');
        $timetrackingto1 = (new \DateTime())->setTimestamp($now)->modify('-1 day');

        $timetrackingfrom2 = (new \DateTime())->setTimestamp($now)->modify('+1 day');
        $timetrackingto2 = (new \DateTime())->setTimestamp($now)->modify('+2 day');

        $timetrackingfrom3 = (new \DateTime())->setTimestamp($now)->modify('-1 day');
        $timetrackingto3 = (new \DateTime())->setTimestamp($now)->modify('+1 day');

        // Test replies with time tracking from.
        $forum1 = $this->get_new_forumng($course->id, ['completion' => COMPLETION_TRACKING_AUTOMATIC,
                'completionpostsenabled' => 1, 'completionposts' => 1, 'timetrackingfrom' => $timetrackingfrom1->getTimestamp()]);
        $this->assertEquals(1, $forum1->get_completion_posts());
        $this->assertFalse($forum1->get_completion_state($USER->id, COMPLETION_OR));
        $forum2 = $this->get_new_forumng($course->id, ['completion' => COMPLETION_TRACKING_AUTOMATIC,
                'completionpostsenabled' => 1, 'completionposts' => 1, 'timetrackingfrom' => $timetrackingfrom2->getTimestamp()]);
        $this->assertEquals(1, $forum2->get_completion_posts());
        $this->assertFalse($forum2->get_completion_state($USER->id, COMPLETION_OR));

        // User1 meets the conditions.
        list ($discuss, $postid) = $generator->create_discussion(['course' => $course->id,
                'forum' => $forum1->get_id(), 'userid' => $user1->id]);
        $this->assertTrue($forum1->get_completion_state($user1->id, COMPLETION_OR));
        // User2 does not meet the conditions.
        list ($discuss, $postid) = $generator->create_discussion(['course' => $course->id,
                'forum' => $forum2->get_id(), 'userid' => $user2->id]);
        $this->assertFalse($forum1->get_completion_state($user2->id, COMPLETION_OR));

        // Test discussions with time tracking to.
        $forum3 = $this->get_new_forumng($course->id, ['completion' => COMPLETION_TRACKING_AUTOMATIC,
                'completionpostsenabled' => 1, 'completionposts' => 1,
                'timetrackingto' => $timetrackingto1->getTimestamp()]);
        $this->assertEquals(1, $forum3->get_completion_posts());
        $this->assertFalse($forum3->get_completion_state($USER->id, COMPLETION_OR));

        $forum4 = $this->get_new_forumng($course->id, ['completion' => COMPLETION_TRACKING_AUTOMATIC,
                'completionpostsenabled' => 1, 'completionposts' => 1,
                'timetrackingto' => $timetrackingto2->getTimestamp()]);
        $this->assertEquals(1, $forum4->get_completion_posts());
        $this->assertFalse($forum4->get_completion_state($USER->id, COMPLETION_OR));

        list ($discuss, $postid) = $generator->create_discussion(['course' => $course->id,
                'forum' => $forum3->get_id(), 'userid' => $user1->id]);
        $this->assertFalse($forum3->get_completion_state($user1->id, COMPLETION_OR));

        list ($discuss, $postid) = $generator->create_discussion(['course' => $course->id,
                'forum' => $forum4->get_id(), 'userid' => $user2->id]);
        $this->assertTrue($forum4->get_completion_state($user2->id, COMPLETION_OR));

        // Test discussions with time tracking from min and time tracking to.
        $forum5 = $this->get_new_forumng($course->id, ['completion' => COMPLETION_TRACKING_AUTOMATIC,
                'completionpostsenabled' => 1, 'completionposts' => 1,
                'timetrackingfrom' => $timetrackingfrom1->getTimestamp(),
                'timetrackingto' => $timetrackingto1->getTimestamp()]);
        $this->assertEquals(1, $forum5->get_completion_posts());
        $this->assertFalse($forum5->get_completion_state($USER->id, COMPLETION_OR));
        $forum6 = $this->get_new_forumng($course->id, ['completion' => COMPLETION_TRACKING_AUTOMATIC,
                'completionpostsenabled' => 1, 'completionposts' => 1,
                'timetrackingfrom' => $timetrackingfrom3->getTimestamp(),
                'timetrackingto' => $timetrackingto3->getTimestamp()]);
        $this->assertEquals(1, $forum6->get_completion_posts());
        $this->assertFalse($forum6->get_completion_state($USER->id, COMPLETION_OR));

        // User1 does not meet the conditions.
        list ($discuss, $postid) = $generator->create_discussion(['course' => $course->id,
                'forum' => $forum5->get_id(), 'userid' => $user1->id]);
        $this->assertFalse($forum5->get_completion_state($user1->id, COMPLETION_OR));
        // User2 meets the conditions.
        list ($discuss, $postid) = $generator->create_discussion(['course' => $course->id,
                'forum' => $forum6->get_id(), 'userid' => $user2->id]);
        $this->assertTrue($forum6->get_completion_state($user2->id, COMPLETION_OR));
    }

    /**
     * Tests completion of posts, discussions, replies with tracking time.
     */
    public function test_completion_tracking_time_all_require() {
        global $USER, $DB, $CFG;

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_forumng');
        $CFG->enablecompletion = true;
        $course = $this->get_new_course();
        $course->enablecompletion = true;
        $DB->update_record('course', $course);
        $user1 = $this->get_new_user('student', $course->id);
        $user2 = $this->get_new_user('student', $course->id);
        $now = time();
        $timetrackingfrom1 = (new \DateTime())->setTimestamp($now)->modify('-1 day');
        $timetrackingto1 = (new \DateTime())->setTimestamp($now)->modify('+1 day');
        $timetrackingfrom2 = (new \DateTime())->setTimestamp($now)->modify('+1 day');
        $timetrackingto2 = (new \DateTime())->setTimestamp($now)->modify('+2 day');

        // Test all require with wordcount min/max.
        $forum1 = $this->get_new_forumng($course->id, ['completion' => COMPLETION_TRACKING_AUTOMATIC,
                'completionpostsenabled' => 1, 'completionposts' => 1,
                'completiondiscussionsenabled' => 1, 'completiondiscussions' => 1,
                'completionrepliesenabled' => 1, 'completionreplies' => 2,
                'timetrackingfrom' => $timetrackingfrom1->getTimestamp(), 'timetrackingto' => $timetrackingto1->getTimestamp()]);
        $this->assertEquals(1, $forum1->get_completion_posts());
        $this->assertEquals(1, $forum1->get_completion_discussions());
        $this->assertEquals(2, $forum1->get_completion_replies());
        $this->assertFalse($forum1->get_completion_state($USER->id, COMPLETION_OR));
        list ($discuss1, $postid1) = $generator->create_discussion(['course' => $course->id,
                'forum' => $forum1->get_id(), 'userid' => $user1->id]);

        $forum2 = $this->get_new_forumng($course->id, ['completion' => COMPLETION_TRACKING_AUTOMATIC,
                'completionpostsenabled' => 1, 'completionposts' => 1,
                'completiondiscussionsenabled' => 1, 'completiondiscussions' => 1,
                'completionrepliesenabled' => 1, 'completionreplies' => 2,
                'timetrackingfrom' => $timetrackingfrom2->getTimestamp(), 'timetrackingto' => $timetrackingto2->getTimestamp()]);
        $this->assertEquals(1, $forum1->get_completion_posts());
        $this->assertEquals(1, $forum1->get_completion_discussions());
        $this->assertEquals(2, $forum1->get_completion_replies());
        $this->assertFalse($forum2->get_completion_state($USER->id, COMPLETION_OR));
        list ($discuss2, $postid2) = $generator->create_discussion(['course' => $course->id,
                'forum' => $forum2->get_id(), 'userid' => $user2->id]);

        // User1 meets the conditions [disscussion:pass, replies:pass].
        $generator->create_post(['discussionid' => $discuss1, 'userid' => $user1->id,
                'parentpostid' => $postid1]);
        $generator->create_post(['discussionid' => $discuss1, 'userid' => $user1->id,
                'parentpostid' => $postid1]);
        $this->assertTrue($forum1->get_completion_state($user1->id, COMPLETION_AND));

        // User2 does not meet the conditions [discussion:pass, replies:fail].
        $generator->create_post(['discussionid' => $discuss2, 'userid' => $user2->id,
                'parentpostid' => $postid2]);
        // Fails because this step reply does not meet the conditions.
        $generator->create_post(['discussionid' => $discuss2, 'userid' => $user2->id,
                'parentpostid' => $postid2]);
        $this->assertFalse($forum1->get_completion_state($user2->id, COMPLETION_AND));
    }

    public function test_subscribers_with_oucu() {
        global $DB, $CFG;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $course = $this->get_new_course();
        $CFG->showuseridentity = 'username,profile_field_oucu,profile_field_staffid';
        $student1 = $this->getDataGenerator()->create_user(['profile_field_oucu' => 'student11111', 'profile_field_staffid' => 'A111']);
        $student2 = $this->getDataGenerator()->create_user(['profile_field_oucu' => 'student22222', 'profile_field_staffid' => 'B111']);
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->getDataGenerator()->enrol_user($student1->id, $course->id, $studentrole->id, 'manual');
        $this->getDataGenerator()->enrol_user($student2->id, $course->id, $studentrole->id, 'manual');
        $forum = $this->get_new_forumng($course->id);
        $forum->subscribe($student1->id);
        $forum->subscribe($student2->id);

        $subs = $forum->get_subscribers();
        $this->assertEquals('student11111', $subs[$student1->id]->profile_field_oucu);
        $this->assertEquals('A111', $subs[$student1->id]->profile_field_staffid);
        $this->assertEquals('student22222', $subs[$student2->id]->profile_field_oucu);
        $this->assertEquals('B111', $subs[$student2->id]->profile_field_staffid);
    }
    /**
     * Checks timed discussions read
     */
    public function test_timed_discussions_read() {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->get_new_course('TESTTIME');
        $student = $this->get_new_user('student', $course->id);
        $manager = $this->get_new_user('manager', $course->id);

        list($forum, $discussions) = $this->create_timed_discussions_forum($course->id);

        $sforum = mod_forumng::get_course_forums($course, $student->id);
        $this->assertEquals(2, $sforum[$forum->get_id()]->get_num_unread_discussions());

        // Check manager (can see timed discussions).
        $mforum = mod_forumng::get_course_forums($course, $manager->id);
        $this->assertEquals(4, $mforum[$forum->get_id()]->get_num_unread_discussions());
    }

    /**
     * Checks forum posting restrictions.
     */
    public function test_forum_posting_restrictions() {
        global $USER;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->get_new_course('TESTTIME');
        $student = $this->get_new_user('student', $course->id);

        $course = $this->get_new_course();
        $forum = $this->get_new_forumng($course->id, array('name' => 'TEST', 'intro' => 'abc123'));
        $future = $this->get_new_forumng($course->id, array('name' => 'TEST2', 'intro' => 'abc123', 'postingfrom' => 1893456000));
        $past = $this->get_new_forumng($course->id, array('name' => 'TEST3', 'intro' => 'abc123', 'postinguntil' => 1420070400));
        $limit = $this->get_new_forumng($course->id, array('name' => 'TEST4', 'intro' => 'abc123', 'maxpostsblock' => 1, 'maxpostsperiod' => 1209600));

        $this->assertFalse($forum->is_read_only());
        $this->assertFalse($future->is_read_only());
        $this->assertFalse($past->is_read_only());
        $this->assertFalse($limit->has_post_quota());
        $this->assertEquals(mod_forumng::QUOTA_DOES_NOT_APPLY, $limit->get_remaining_post_quota());

        $this->assertFalse($forum->is_read_only($student->id));
        $this->assertTrue($future->is_read_only($student->id));
        $this->assertTrue($past->is_read_only($student->id));
        $this->assertTrue($limit->has_post_quota($student->id));
        $this->assertEquals(1, $limit->get_remaining_post_quota($student->id));

        $this->get_new_discussion($limit, array('userid' => $USER->id));
        $this->get_new_discussion($limit, array('userid' => $student->id));

        $this->assertEquals(mod_forumng::QUOTA_DOES_NOT_APPLY, $limit->get_remaining_post_quota());
        $this->assertEquals(0, $limit->get_remaining_post_quota($student->id));
    }

    /**
     * Checks get prevent features list.
     */
    public function test_prevent_forumngfeature_discussion_list() {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->get_new_course();
        $forumipud = $this->get_new_forumng($course->id, array('type' => 'ipud'));
        $forum = $this->get_new_forumng($course->id);
        $preventfeature = $forumipud->get_type()->prevent_forumngfeature_discussion();
        $features = array();
        foreach ($preventfeature as $pf) {
            $features[] = forumngfeature::get_new(str_replace('forumngfeature_', '', $pf));
        }
        $this->assertNotEquals(forumngfeature::get_all($forum->get_type()),
            forumngfeature::get_all($forumipud->get_type()));
        $this->assertNotContains($features, forumngfeature::get_all($forumipud->get_type()));
    }

    /**
     * Test call to get the format message.
     *
     * @throws moodle_exception
     */
    public function test_formatmessage_success() {
        global $USER;
        $this->resetAfterTest();
        $this->setAdminUser();

        // Create course, forum, discussion and post with fake plugin file.
        $course = $this->get_new_course();
        $forum = $this->get_new_forumng($course->id, array('name' => 'ForumNG 1', 'intro' => 'Intro'));
        $discussion = $this->get_new_discussion($forum, array('userid' => $USER->id));
        $message = '@@PLUGINFILE@@/image.png';
        $postid = $discussion->get_root_post()->reply('Subject 1', $message, 1);

        $response = mod_forumng_output_fragment_formatmessage(array(
                'postid' => $postid,
                'rawmessage' => $message
        ));

        // Check that @@PLUGINFILE@@ have been replaced by real link.
        $this->assertStringContainsString('https://www.example.com/moodle/pluginfile.php', $response);
        $this->assertStringContainsString('image.png', $response);
        $this->assertStringNotContainsString('@@PLUGINFILE@@', $response);
    }

    /**
     * Check call to format message function when missing postid or rawmessage parameters.
     *
     * @throws moodle_exception
     */
    public function test_formatmessage_missingparameters() {
        global $USER;

        $this->resetAfterTest();
        $this->setAdminUser();

        $this->expectException('moodle_exception');
        $this->expectExceptionMessage(get_string('missingparam_formatmessage', 'mod_forumng'));
        mod_forumng_output_fragment_formatmessage(array(
            'postid' => 1,
        ));

        mod_forumng_output_fragment_formatmessage(array(
            'rawmessage' => '@@PLUGINFILE@@/image.png',
        ));
    }

    /**
     *  Test call to format message, expect exception if user not have view permission.
     *
     * @throws moodle_exception
     */
    public function test_formatmessage_nopermission() {
        global $USER;

        $this->resetAfterTest();
        $this->setAdminUser();

        // Create course, forum, discussion and post with fake plugin file.
        $course = $this->get_new_course('Course 1');
        $forum = $this->get_new_forumng($course->id, array(
            'name' => 'ForumNG 1',
            'intro' => 'Intro',
            'groupmode' => SEPARATEGROUPS
        ));
        $discussion = $this->get_new_discussion($forum, array('userid' => $USER->id));
        $message = '@@PLUGINFILE@@/image.png';
        $postid = $discussion->get_root_post()->reply('Subject 1', $message, 1);
        $student = $this->get_new_user('student');
        $this->setUser($student);

        // Expect exception because this student not belong to a group require for this forum.
        $this->expectException('moodle_exception');
        $this->expectExceptionMessage(get_string('error_cannotviewdiscussion', 'mod_forumng'));

        mod_forumng_output_fragment_formatmessage(array(
            'postid' => $postid,
            'rawmessage' => $message
        ));
    }

    public function test_feed_links() {
        global $CFG, $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        // Create course, forum, discussion and post with fake plugin file.
        $course = $this->get_new_course('Course 1');
        $forum = $this->get_new_forumng($course->id, array(
            'name' => 'ForumNG 1',
            'intro' => 'Intro',
            'groupmode' => SEPARATEGROUPS,
            'feedtype' => 2
        ));
        $CFG->forumng_enablerssfeeds =1;
        $CFG->enablerssfeeds = 1;
        $this->assertNotEmpty($forum->display_feed_links(0));

        // Remove permissions.
        $role = $DB->get_record('role', array('shortname' => 'student'));
        role_change_permission($role->id, $forum->get_context(), 'mod/forumng:showatom', CAP_PREVENT);
        $student = $this->get_new_user('student');
        $this->setUser($student);
        $this->assertNotEmpty($forum->display_feed_links(0));
        role_change_permission($role->id, $forum->get_context(), 'mod/forumng:showrss', CAP_PREVENT);
        $this->assertEmpty($forum->display_feed_links(0));
    }

    /**
     * Check the modinfo object is correctly populated.
     */
    public function test_forum_user_cminfo() {
        global $USER;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->get_new_course('testcourse');
        $user1 = $this->get_new_user();
        $forumrecord = $this->get_new_forumng($course->id);

        $forum = mod_forumng::get_from_id($forumrecord->get_id(), 0, true, null, $user1->id);
        $forumcm = $forum->get_course_module();
        $this->assertEquals($user1->id, $forumcm->get_modinfo()->get_user_id());

        $forum = mod_forumng::get_from_cmid($forumcm->id, 0, $user1->id);
        $forumcm = $forum->get_course_module();
        $this->assertEquals($user1->id, $forumcm->get_modinfo()->get_user_id());

        $forum = mod_forumng::get_from_id($forumrecord->get_id(), 0);
        $forumcm = $forum->get_course_module();
        $this->assertEquals($USER->id, $forumcm->get_modinfo()->get_user_id());

        $forum = mod_forumng::get_from_cmid($forumcm->id, 0);
        $forumcm = $forum->get_course_module();
        $this->assertEquals($USER->id, $forumcm->get_modinfo()->get_user_id());
    }

    public function test_forumng_get_max_bytes(): void {
        global $USER, $DB;
        $this->resetAfterTest(true);

        $coursemaxbytes = 1024000;
        $course = $this->getDataGenerator()->create_course([
            'fullname' => 'testcourse',
            'maxbytes' => $coursemaxbytes,
        ]);
        $this->assertEquals($coursemaxbytes, $course->maxbytes);

        $student = $this->get_new_user('student', $course->id);
        $manager = $this->get_new_user('manager', $course->id);
        $teacher = $this->get_new_user('editingteacher', $course->id);

        $maxbytes = 512000;
        $forumrecord = $this->get_new_forumng($course->id, [
            'attachmentmaxbytes' => $maxbytes,
        ]);

        // Get forum data.
        $forumfields = $DB->get_record('forumng', ['id' => $forumrecord->get_id()], '*', MUST_EXIST);
        $this->assertEquals($maxbytes, $forumfields->attachmentmaxbytes);

        // Admin.
        $this->setAdminUser();
        $forum = mod_forumng::get_from_id($forumrecord->get_id(), 0, true, null, $USER->id);
        $this->assertInstanceOf(mod_forumng::class, $forum);
        $this->assertTrue($forum->is_ignore_filesize_limit());
        // Admin can do anything.
        $this->assertEquals(USER_CAN_IGNORE_FILE_SIZE_LIMITS, $forum->get_max_bytes());
        $this->assertTrue($forum->can_create_attachments());

        // Student.
        $this->setUser($student);
        $forum = mod_forumng::get_from_id($forumrecord->get_id(), 0, true, null, $student->id);
        $this->assertInstanceOf(mod_forumng::class, $forum);
        $this->assertFalse($forum->is_ignore_filesize_limit());
        // Student must respect ForumNG setting.
        $this->assertEquals($forumfields->attachmentmaxbytes, $forum->get_max_bytes());
        $this->assertTrue($forum->can_create_attachments());

        // Manager.
        $this->setUser($manager);
        $forum = mod_forumng::get_from_id($forumrecord->get_id(), 0, true, null, $manager->id);
        $this->assertInstanceOf(mod_forumng::class, $forum);
        $this->assertTrue($forum->is_ignore_filesize_limit());
        // Manager ignores ForumNG setting, but still respect course setting.
        $this->assertEquals($coursemaxbytes, $forum->get_max_bytes());
        $this->assertTrue($forum->can_create_attachments());

        // Teacher.
        $this->setUser($teacher);
        $forum = mod_forumng::get_from_id($forumrecord->get_id(), 0, true, null, $teacher->id);
        $this->assertInstanceOf(mod_forumng::class, $forum);
        $this->assertTrue($forum->is_ignore_filesize_limit());
        // Teacher ignores ForumNG setting, but still respect course setting.
        $this->assertEquals($coursemaxbytes, $forum->get_max_bytes());
        $this->assertTrue($forum->can_create_attachments());
    }

    /**
     * For site upload limit, we set attachmentmaxbytes = 0.
     */
    public function test_forumng_get_max_bytes_with_site_upload_limit(): void {
        global $USER, $DB;
        $this->resetAfterTest(true);

        $coursemaxbytes = 1024000;
        $course = $this->getDataGenerator()->create_course([
            'fullname' => 'testcourse',
            'maxbytes' => $coursemaxbytes,
        ]);
        $this->assertEquals($coursemaxbytes, $course->maxbytes);

        $student = $this->get_new_user('student', $course->id);
        $manager = $this->get_new_user('manager', $course->id);
        $teacher = $this->get_new_user('editingteacher', $course->id);

        $forumrecord = $this->get_new_forumng($course->id, [
            'attachmentmaxbytes' => 0,
        ]);

        // Get forum data.
        $forumfields = $DB->get_record('forumng', ['id' => $forumrecord->get_id()], '*', MUST_EXIST);
        $this->assertEquals(0, $forumfields->attachmentmaxbytes);

        // Admin.
        $this->setAdminUser();
        $forum = mod_forumng::get_from_id($forumrecord->get_id(), 0, true, null, $USER->id);
        $this->assertInstanceOf(mod_forumng::class, $forum);
        $this->assertTrue($forum->is_ignore_filesize_limit());
        // Admin can do anything.
        $this->assertEquals(USER_CAN_IGNORE_FILE_SIZE_LIMITS, $forum->get_max_bytes());
        $this->assertTrue($forum->can_create_attachments());

        // Student.
        $this->setUser($student);
        $forum = mod_forumng::get_from_id($forumrecord->get_id(), 0, true, null, $student->id);
        $this->assertInstanceOf(mod_forumng::class, $forum);
        $this->assertFalse($forum->is_ignore_filesize_limit());
        // Student must respect ForumNG setting.
        $this->assertEquals($coursemaxbytes, $forum->get_max_bytes());
        $this->assertTrue($forum->can_create_attachments());

        // Manager.
        $this->setUser($manager);
        $forum = mod_forumng::get_from_id($forumrecord->get_id(), 0, true, null, $manager->id);
        $this->assertInstanceOf(mod_forumng::class, $forum);
        $this->assertTrue($forum->is_ignore_filesize_limit());
        // Manager still respects course setting.
        $this->assertEquals($coursemaxbytes, $forum->get_max_bytes());
        $this->assertTrue($forum->can_create_attachments());

        // Teacher.
        $this->setUser($teacher);
        $forum = mod_forumng::get_from_id($forumrecord->get_id(), 0, true, null, $teacher->id);
        $this->assertInstanceOf(mod_forumng::class, $forum);
        $this->assertTrue($forum->is_ignore_filesize_limit());
        // Teacher still respects course setting.
        $this->assertEquals($coursemaxbytes, $forum->get_max_bytes());
        $this->assertTrue($forum->can_create_attachments());
    }

    /**
     * For uploads are not allowed, we set attachmentmaxbytes = -1.
     */
    public function test_forumng_get_max_bytes_with_upload_are_not_allowed(): void {
        global $USER, $DB;
        $this->resetAfterTest(true);

        $coursemaxbytes = 1024000;
        $course = $this->getDataGenerator()->create_course([
            'fullname' => 'testcourse',
            'maxbytes' => $coursemaxbytes,
        ]);
        $this->assertEquals($coursemaxbytes, $course->maxbytes);

        $student = $this->get_new_user('student', $course->id);
        $manager = $this->get_new_user('manager', $course->id);
        $teacher = $this->get_new_user('editingteacher', $course->id);

        $forumrecord = $this->get_new_forumng($course->id, [
            'attachmentmaxbytes' => -1,
        ]);

        // Get forum data.
        $forumfields = $DB->get_record('forumng', ['id' => $forumrecord->get_id()], '*', MUST_EXIST);
        $this->assertEquals(-1, $forumfields->attachmentmaxbytes);

        // Admin.
        $this->setAdminUser();
        $forum = mod_forumng::get_from_id($forumrecord->get_id(), 0, true, null, $USER->id);
        $this->assertInstanceOf(mod_forumng::class, $forum);
        $this->assertTrue($forum->is_ignore_filesize_limit());
        // Admin can do anything.
        $this->assertEquals(USER_CAN_IGNORE_FILE_SIZE_LIMITS, $forum->get_max_bytes());
        $this->assertTrue($forum->can_create_attachments());

        // Student.
        $this->setUser($student);
        $forum = mod_forumng::get_from_id($forumrecord->get_id(), 0, true, null, $student->id);
        $this->assertInstanceOf(mod_forumng::class, $forum);
        $this->assertFalse($forum->is_ignore_filesize_limit());
        // Student must respect ForumNG setting.
        // Consider to return something else instead of -1 because it is USER_CAN_IGNORE_FILE_SIZE_LIMITS.
        $this->assertEquals(-1, $forum->get_max_bytes());
        $this->assertEquals(USER_CAN_IGNORE_FILE_SIZE_LIMITS, $forum->get_max_bytes());
        $this->assertFalse($forum->can_create_attachments());

        // Manager.
        $this->setUser($manager);
        $forum = mod_forumng::get_from_id($forumrecord->get_id(), 0, true, null, $manager->id);
        $this->assertInstanceOf(mod_forumng::class, $forum);
        $this->assertTrue($forum->is_ignore_filesize_limit());
        // Manager still respects course setting.
        $this->assertEquals($coursemaxbytes, $forum->get_max_bytes());
        $this->assertFalse($forum->can_create_attachments());

        // Teacher.
        $this->setUser($teacher);
        $forum = mod_forumng::get_from_id($forumrecord->get_id(), 0, true, null, $teacher->id);
        $this->assertInstanceOf(mod_forumng::class, $forum);
        $this->assertTrue($forum->is_ignore_filesize_limit());
        // Teacher still respects course setting.
        $this->assertEquals($coursemaxbytes, $forum->get_max_bytes());
        $this->assertFalse($forum->can_create_attachments());
    }
}
