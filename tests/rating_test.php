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
require_once($CFG->dirroot . '/rating/lib.php');

/**
 * PHPUnit main forum core ratings implementation.
 *
 * @package mod_forumng
 * @copyright 2014 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_forumng_rating_testcase extends forumng_test_lib {

    /**
     * Unit tests cover following aspects of rating:
     * Lib hooks
     * Clones
     * Rating posts
     * Deleting discussion
     * Moving discussion
     * Copying discussion
     * Grading
     * Deleting forum
     */

    /**
     * Tests getting forum object from id and cmid, inc clones.
     */
    public function test_lib_hooks() {
        global $USER, $DB;
        $this->resetAfterTest();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_forumng');

        $course = $this->get_new_course();
        $suser = $this->get_new_user('student', $course->id);

        $forum = $this->get_new_forumng($course->id, array('name' => 'TEST', 'intro' => 'abc123',
                'enableratings' => mod_forumng::FORUMNG_STANDARD_RATING, 'ratingscale' => 10));
        $cm = get_coursemodule_from_instance('forumng', $forum->get_id());

        $result = forumng_rating_permissions($forum->get_context()->id, 'mod_forumng', 'post');
        $this->assertTrue(is_array($result));
        foreach ($result as $cap) {
            $this->assertFalse($cap);
        }
        $this->setAdminUser();
        $result = forumng_rating_permissions($forum->get_context()->id, 'mod_forumng', 'post');
        $this->assertTrue(is_array($result));
        foreach ($result as $cap) {
            $this->assertTrue($cap);
        }

        $did1 = $generator->create_discussion(array('course' => $course, 'forum' => $forum->get_id(), 'userid' => $USER->id));
        $post = $generator->create_post(array('discussionid' => $did1[0], 'parentpostid' => $did1[1], 'userid' => $suser->id));

        $rm = new rating_manager();
        $params = array();
        $params['context'] = $forum->get_context();
        $params['component'] = 'mod_forumng';
        $params['ratingarea'] = 'post';
        $params['itemid'] = $did1[1];
        $params['scaleid'] = $forum->get_rating_scale();
        $params['rateduserid'] = $USER->id;
        $params['rating'] = 21;

        // Check rating valid, should fail with same user + rating too high.
        $this->setExpectedException('rating_exception', get_string('nopermissiontorate', 'error'));
        $rm->check_rating_is_valid($params);
        $params['itemid'] = $post->id;
        $params['rateduserid'] = $suser->id;
        $this->setExpectedException('rating_exception', get_string('invalidnum', 'error'));
        $rm->check_rating_is_valid($params);
        $params['rating'] = 10;
        $result = $rm->check_rating_is_valid($params);
        $this->assertTrue($result);

        // Call to update grades (should do nothing as grading not set, no assertions).
        forumng_update_grades($DB->get_record('forumng', array('id' => $forum->get_id())));

        // Check clones.
        $forum1 = $this->get_new_forumng($course->id, array('name' => 'TEST', 'intro' => 'abc123',
                'shared' => true, 'cmidnumber' => 'SF1',
                'enableratings' => mod_forumng::FORUMNG_STANDARD_RATING, 'ratingscale' => 10));

        $forum2 = $this->get_new_forumng($course->id, array('name' => 'TEST',
                'usesharedgroup' => array('useshared' => true, 'originalcmidnumber' => 'SF1')));

        $did1 = $generator->create_discussion(array('course' => $course, 'forum' => $forum1->get_id(), 'userid' => $USER->id));
        $post = $generator->create_post(array('discussionid' => $did1[0], 'parentpostid' => $did1[1], 'userid' => $suser->id));

        $params['itemid'] = $post->id;
        $params['context'] = $forum2->get_context(true);
        $result = $rm->check_rating_is_valid($params);
        $this->assertTrue($result);

    }

    public function test_rating() {
        global $USER, $DB;
        $this->resetAfterTest();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_forumng');

        $course = $this->get_new_course();
        $course2 = $this->get_new_course();
        $suser = $this->get_new_user('student', $course->id);
        $this->setAdminUser();

        $forum = $this->get_new_forumng($course->id, array('name' => 'TEST', 'intro' => 'abc123',
                'enableratings' => mod_forumng::FORUMNG_STANDARD_RATING, 'ratingscale' => 10));
        $forum2 = $this->get_new_forumng($course->id, array('name' => 'TEST2', 'intro' => 'abc123',
                'enableratings' => mod_forumng::FORUMNG_NO_RATING));
        $forum3 = $this->get_new_forumng($course2->id, array('name' => 'TEST', 'intro' => 'abc123',
                'enableratings' => mod_forumng::FORUMNG_STANDARD_RATING, 'ratingscale' => 10));

        $did1 = $generator->create_discussion(array('course' => $course, 'forum' => $forum->get_id(), 'userid' => $suser->id));
        $did2 = $generator->create_discussion(array('course' => $course, 'forum' => $forum->get_id(), 'userid' => $suser->id));
        $did3 = $generator->create_discussion(array('course' => $course, 'forum' => $forum->get_id(), 'userid' => $suser->id));
        $did4 = $generator->create_discussion(array('course' => $course, 'forum' => $forum->get_id(), 'userid' => $suser->id));
        // Add rating to all 3 discussions.
        $rm = new rating_manager();
        $params = new stdClass();
        $params->context = $forum->get_context();
        $params->component = 'mod_forumng';
        $params->ratingarea = 'post';
        $params->scaleid = $forum->get_rating_scale();
        $params->userid = $USER->id;
        $params->itemid = $did1[1];
        $rating = new rating($params);
        $rating->update_rating(5);
        $params->itemid = $did2[1];
        $rating = new rating($params);
        $rating->update_rating(5);
        $params->itemid = $did3[1];
        $rating = new rating($params);
        $rating->update_rating(5);

        // Check rating object gets added where expected.
        $post = mod_forumng_post::get_from_id($did1[1], mod_forumng::CLONE_DIRECT, false, false);
        $ratings = $post->get_ratings();
        $this->assertNotNull($ratings);
        $this->assertEquals($did1[1], $ratings->itemid);
        $post = mod_forumng_post::get_from_id($did1[1], mod_forumng::CLONE_DIRECT, true, false);
        $ratings = $post->get_ratings();
        $this->assertNotNull($ratings);
        $this->assertEquals($did1[1], $ratings->itemid);
        $post = mod_forumng_post::get_from_id($did1[1], mod_forumng::CLONE_DIRECT, true, true);
        $ratings = $post->get_ratings();
        $this->assertNotNull($ratings);
        $this->assertEquals($did1[1], $ratings->itemid);

        $ratedposts = $forum->get_all_posts_by_user($suser->id, null, 'fp.id', null, null, true);
        $this->assertCount(3, $ratedposts);
        $allposts = $forum->get_all_posts_by_user($suser->id, null);
        $this->assertCount(4, $allposts);
        $this->assertNotNull($allposts[$did1[1]]->get_ratings());

        // Update grades (does nothing).
        $forum->update_grades();
        // Enable rating grading, forumng_update_instance() should update grades.
        forumng_update_instance((object) array('instance' => $forum->get_id(),
            'grading' => mod_forumng::GRADING_SUM));
        $grades = grade_get_grades($course->id, 'mod', 'forumng', $forum->get_id(), $suser->id);
        // Note sum is set to 10 not 15 as max grade is 10.
        $this->assertEquals(10, abs($grades->items[0]->grades[$suser->id]->grade));

        // Enable rating grading, forumng_update_instance() should update grades.
        forumng_update_instance((object) array('instance' => $forum->get_id(),
            'grading' => mod_forumng::GRADING_COUNT));
        $grades = grade_get_grades($course->id, 'mod', 'forumng', $forum->get_id(), $suser->id);
        $this->assertEquals(3, abs($grades->items[0]->grades[$suser->id]->grade));

        // Check get_rated_posts_by_user.
        $extrapost = $generator->create_post(array('discussionid' => $did1[0],
                 'parentpostid' => $did1[1], 'userid' => $suser->id, 'created' => 1388589745));
        $extraposts = $forum->get_rated_posts_by_user($forum, $suser->id, -1, 'fp.id', null, null);
        $this->assertCount(0, $extraposts);
        $extraposts = $forum->get_rated_posts_by_user($forum, $USER->id, -1, 'fp.id', null, null);
        $this->assertCount(3, $extraposts);
        $params->itemid = $extrapost->id;
        $rating = new rating($params);
        $rating->update_rating(10);
        $extraposts = $forum->get_rated_posts_by_user($forum, $USER->id, -1, 'fp.id', null, null);
        $this->assertCount(4, $extraposts);
        // Now filter out the 'old' extrapost.
        $extraposts = $forum->get_rated_posts_by_user($forum, $USER->id, -1, 'fp.id', null, null, 1388600000);
        $this->assertCount(3, $extraposts);

        // Check discussion delete.
        $discuss = mod_forumng_discussion::get_from_id($did1[0], mod_forumng::CLONE_DIRECT);
        $discuss->permanently_delete();
        $rating = $DB->get_record('rating', array('itemid' => $did1[1]));
        $this->assertFalse($rating);
        $grades = grade_get_grades($course->id, 'mod', 'forumng', $forum->get_id(), $suser->id);
        $this->assertEquals(2, abs($grades->items[0]->grades[$suser->id]->grade));

        // Check discussion move.
        $discuss = mod_forumng_discussion::get_from_id($did2[0], mod_forumng::CLONE_DIRECT);
        $discuss->move($forum2, 0);
        $grades = grade_get_grades($course->id, 'mod', 'forumng', $forum->get_id(), $suser->id);
        $this->assertEquals(1, abs($grades->items[0]->grades[$suser->id]->grade));
        forumng_update_instance((object) array('instance' => $forum2->get_id(),
            'grading' => mod_forumng::GRADING_COUNT,
            'enableratings' => mod_forumng::FORUMNG_STANDARD_RATING, 'ratingscale' => 10));
        $grades = grade_get_grades($course->id, 'mod', 'forumng', $forum2->get_id(), $suser->id);
        $this->assertEquals(1, abs($grades->items[0]->grades[$suser->id]->grade));
        $rating = $DB->get_record('rating', array('itemid' => $did2[1]));
        $this->assertNotEmpty($rating);
        $this->assertEquals($forum2->get_context(true)->id, $rating->contextid);

        // Check discussion copy.
        $discuss = mod_forumng_discussion::get_from_id($did3[0], mod_forumng::CLONE_DIRECT);
        $discuss->copy($forum3, 0);
        $grades = grade_get_grades($course->id, 'mod', 'forumng', $forum->get_id(), $suser->id);
        $this->assertEquals(1, abs($grades->items[0]->grades[$suser->id]->grade));
        // Check rating didn't copy as forum in another course.
        $ratingtotal = $DB->get_records('rating');
        $this->assertCount(2, $ratingtotal);
        // Check rating does copy to foum in same course.
        $discuss->copy($forum2, 0);
        $ratingtotal = $DB->get_records('rating');
        $this->assertCount(3, $ratingtotal);

        // Check forum deleting.
        course_delete_module($forum->get_course_module_id());
        $ratingtotal = $DB->get_records('rating');
        $this->assertCount(2, $ratingtotal);
        $grades = grade_get_grades($course->id, 'mod', 'forumng', $forum->get_id(), $suser->id);
        $this->assertEmpty($grades->items);
    }
}
