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
class mod_forumng_forumng_testcase extends forumng_test_lib {

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

        // Check clone.
        $forum1 = $this->get_new_forumng($course->id, array('name' => 'TEST', 'intro' => 'abc123',
                'shared' => true, 'cmidnumber' => 'SF1'));
        $this->assertEmpty($forum1->get_clone_details());
        $this->assertTrue($forum1->is_shared());

        $course2 = $this->get_new_course();
        $forum2 = $this->get_new_forumng($course2->id, array('name' => 'TEST',
                'usesharedgroup' => array('useshared' => true, 'originalcmidnumber' => 'SF1')));

        $this->assertTrue($forum2->is_shared());

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
        $forum6 = $this->get_new_forumng($course->id, array('canpostanon' => 1));
        role_change_permission($role->id, $forum6->get_context(), 'mod/forumng:postanon', CAP_PREVENT);
        $this->assertFalse($forum6->can_post_anonymously($user1->id));
        role_change_permission($role->id, $forum6->get_context(), 'mod/forumng:postanon', CAP_ALLOW);
        $this->assertTrue($forum6->can_post_anonymously($user1->id));

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
}
