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
 * Unit test for feature.
 *
 * @package forumngfeature_usage
 * @copyright 2016 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/forumng/tests/forumng_test_lib.php');
require_once($CFG->dirroot . '/mod/forumng/mod_forumng.php');

/**
 * Unit test.
 */
class forumngfeature_usage_usage_testcase extends forumng_test_lib {

    /**
     * Tests that discussions can be marked as read.
     * (unread count etc tested in test_get_course_forums())
     */
    public function test_most_readers() {
        global $USER, $CFG;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_forumng');

        $course = $this->get_new_course();
        $user1 = $this->get_new_user('student', $course->id);
        $user2 = $this->get_new_user('student', $course->id);
        $user3 = $this->get_new_user('student', $course->id);
        $group1 = $this->get_new_group($course->id);
        $group2 = $this->get_new_group($course->id);

        // The users are in both the groups except user 3 is not in group 1.
        $this->get_new_group_member($group1->id, $user1->id);
        $this->get_new_group_member($group2->id, $user1->id);
        $this->get_new_group_member($group1->id, $user2->id);
        $this->get_new_group_member($group2->id, $user2->id);
        $this->get_new_group_member($group2->id, $user3->id);

        $forum1 = $this->get_new_forumng($course->id, array('groupmode' => VISIBLEGROUPS));
        list($d1, $postid) = $generator->create_discussion(array('course' => $course, 'forum' => $forum1->get_id(),
                'userid' => $USER->id, 'groupid' => $group1->id));
        list($d2, $postid) = $generator->create_discussion(array('course' => $course, 'forum' => $forum1->get_id(),
                'userid' => $USER->id, 'groupid' => $group1->id));
        list($d3, $postid) = $generator->create_discussion(array('course' => $course, 'forum' => $forum1->get_id(),
                'userid' => $USER->id, 'groupid' => $group2->id));
        list($d4, $postid) = $generator->create_discussion(array('course' => $course, 'forum' => $forum1->get_id(),
                'userid' => $USER->id, 'groupid' => $group1->id));

        $discussions = $forum1->get_discussion_list()->get_normal_discussions();

        // Discussion 1 has been read 3 times (but one is from somebody not in the group)...
        $discussions[$d1]->mark_read(0, $user1->id);
        $discussions[$d1]->mark_read(0, $user2->id);
        $discussions[$d1]->mark_read(0, $user3->id);
        // Discussion 2 has been read twice but one is not enrolled.
        $discussions[$d2]->mark_read(0, $user1->id);
        $discussions[$d2]->mark_read(0, $USER->id);
        // Discussion 3 has been read twice but once by marking a single post not whole discussion.
        $discussions[$d3]->get_root_post()->mark_read(0, $user2->id);
        $discussions[$d3]->mark_read(0, $user3->id);

        require_once($CFG->dirroot . '/mod/forumng/feature/usage/locallib.php');

        // Get results for all groups.
        $params = array('id' => $forum1->get_course_module_id());
        $result = forumngfeature_usage_show_mostreaders($params);
        $this->assertRegExp('~forumng_usage_list_tot">3<.*?discussion 1.*?' .
                'forumng_usage_list_tot">2<.*?discussion 3.*?' .
                'forumng_usage_list_tot">1<.*?discussion 2~s', $result);
        $this->assertNotContains('discussion 4', $result);

        // Try for group 1.
        $params['group'] = $group1->id;
        $result = forumngfeature_usage_show_mostreaders($params);
        $this->assertRegExp('~forumng_usage_list_tot">2<.*?discussion 1.*?' .
                'forumng_usage_list_tot">1<.*?discussion 2~s', $result);
        $this->assertNotContains('discussion 3', $result);
        $this->assertNotContains('discussion 4', $result);

        // Try for group 2.
        $params['group'] = $group2->id;
        $result = forumngfeature_usage_show_mostreaders($params);
        $this->assertRegExp('~forumng_usage_list_tot">2<.*?discussion 3~s', $result);
        $this->assertNotContains('discussion 1', $result);
        $this->assertNotContains('discussion 2', $result);
        $this->assertNotContains('discussion 4', $result);
    }

}
