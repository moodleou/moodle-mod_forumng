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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/forumng/tests/forumng_test_lib.php');


class mod_forumng_discussion_testcase  extends forumng_test_lib {

    /**
     * ForumNG generator reference
     * @var testing_module_generator
     */
    public $generator = null;

    /**
     * Create temporary test tables and entries in the database for these tests.
     * These tests have to work on a brand new site.
     */

    /*
        Backend functions covered:
        get_root_post()
        get_from_id()
        edit_settings()
        create_discussion()
        get_time_start()
        can_flag()
        set_flagged()
        get_flagged_discussions()
        is_flagged()
        get_tags()
        get_set_tags()
        forumng_update_instance()
        permanently_delete()
    */

    public function test_showfrom () {
        global $DB, $USER, $SITE;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Create the generator object and do standard checks.
        $generator = self::getDataGenerator()->get_plugin_generator('mod_forumng');

        // Create course.
        $record = new stdClass();
        $record->shortname = 'testcourse';
        $course = self::getDataGenerator()->create_course($record);

        // Create forum.
        $forum = $generator->create_instance(array('course' => $SITE->id));

        // Get UNIX time + 1 day.
        $utime = time();
        // UNIX time + 1 day.
        $futuretime = $utime + (24 * 60 * 60);

        // Start a discussion in forum.
        $record = new stdClass();
        $record->course = $course->id;
        $record->forum = $forum->id;
        $record->userid = $USER->id;

        $record->timestart = $futuretime;
        $ids = $generator->create_discussion($record);

        // Test to see that the created discussion and root post records contains a matching timestart to futuretime.
        $discussionid = $ids[0];
        $rootpostid = $ids[1];
        $discussion = mod_forumng_discussion::get_from_id($discussionid , 0);
        $starttime = $discussion->get_time_start();
        $this->assertEquals($futuretime, $starttime);
        $root = $discussion->get_root_post();
        $this->assertEquals($futuretime, $root->get_created());
        $this->assertEquals($futuretime, $root->get_modified());

        // Test to see we can not create a post with an earlier start time than today.
        $record = new stdClass();
        $record->course = $course->id;
        $record->forum = $forum->id;
        $record->userid = $USER->id;

        $pasttime = $utime - (24 * 60 * 60);
        $record->timestart = $pasttime;
        $ids = $generator->create_discussion($record);
        // Test to see that the created discussion and root post records contains a matching time for now (utime).
        $discussionid = $ids[0];
        $rootpostid = $ids[1];
        $discussion = mod_forumng_discussion::get_from_id($discussionid , 0);
        $starttime = $discussion->get_time_start();
        $root = $discussion->get_root_post();
        $this->assertNotEquals($pasttime, $root->get_created());
        $this->assertNotEquals($pasttime, $root->get_modified());

        // Test to see that we can change root post times to a revised future time.
        // Future time is set to an extra day ahead (i.e. 2 days from now).
        $futuretime = $futuretime + (24 * 60 * 60);

        // Edit root post settings.
        $discussion->edit_settings($discussion::NOCHANGE, $futuretime, $discussion::NOCHANGE, $discussion::NOCHANGE,
                $discussion::NOCHANGE);

        $starttime = $discussion->get_time_start();
        $this->assertEquals($futuretime, $starttime);
        // Set cache to false to stop it picking up old (cached) created and modified times.
        $roota = $discussion->get_root_post(false);
        $this->assertEquals($futuretime, $roota->get_created());
        $this->assertEquals($futuretime, $roota->get_modified());

    }

    public function test_flag_discussion() {
        global $DB, $USER, $SITE;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Create the generator object and do standard checks.
        $generator = self::getDataGenerator()->get_plugin_generator('mod_forumng');

        // Create forum.
        $forumrecord = $generator->create_instance(array('course' => $SITE->id));

        // Start a discussion in forum.
        $record = new stdClass();
        $record->course = $SITE->id;
        $record->forum = $forumrecord->id;
        $record->userid = $USER->id;

        $record->timestart = time();
        $ids = $generator->create_discussion($record);

        $discussionid = $ids[0];
        $rootpostid = $ids[1];
        $discussion = mod_forumng_discussion::get_from_id($discussionid, 0);
        $this->assertFalse($discussion->is_flagged());
        // Set flagged discussion.
        $this->assertTrue($discussion->can_flag());
        $discussion->set_flagged(true, $USER->id);

        $forum = mod_forumng::get_from_id($forumrecord->id, mod_forumng::CLONE_DIRECT, false);

        $flagged = $forum->get_flagged_discussions($USER->id);
        $this->assertCount(1, $flagged);
        foreach ($flagged as $flagdiscussion) {
            $this->assertEquals($discussion->get_id(), $flagdiscussion->get_id());
        }
        $this->assertTrue($discussion->is_flagged());

        $discussion->delete(false);
        $this->assertTrue($discussion->can_flag());// Can flag if deleted and flagged already.

        // Remove flag from discussion.
        $discussion->set_flagged(false);
        $flagged = $forum->get_flagged_discussions($USER->id);
        $this->assertCount(0, $flagged);
        $this->assertFalse($discussion->is_flagged());

        $this->assertFalse($discussion->can_flag());
        $discussion->undelete(false);

        // Test can flag as guest user.
        $this->setGuestUser();
        $this->assertFalse($discussion->can_flag());
    }

    public function test_tag_discussion() {
        global $DB, $USER, $SITE, $CFG;

        require_once($CFG->dirroot . '/tag/lib.php');

        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Create the generator object and do standard checks.
        $generator = self::getDataGenerator()->get_plugin_generator('mod_forumng');

        // Create course.
        $record = new stdClass();
        $record->shortname = 'testcourse';
        $course = self::getDataGenerator()->create_course($record);

        // Create groups.
        $group1 = $this->get_new_group($course->id);
        $group2 = $this->get_new_group($course->id);

        // Create forum.
        $forumrecord = $generator->create_instance(array('course' => $course->id, 'tags' => true,
                'groupmode' => VISIBLEGROUPS));
        $forum = mod_forumng::get_from_id($forumrecord->id, mod_forumng::CLONE_DIRECT, true);

        // Set use tag.
        $CFG->usetags = true;

        // Set forum to tag enabling.
        $this->assertTrue($CFG->usetags);
        $this->assertEquals(true, $forum->get_tags_enabled());

        // Start a discussion in forum (group1).
        $record = new stdClass();
        $record->course = $course->id;
        $record->forum = $forum->get_id();
        $record->groupid = $group1->id;
        $record->userid = $USER->id;
        $record->timestart = time();
        $ids1 = $generator->create_discussion($record);

        // Start a second discussion in forum (group1).
        $record = new stdClass();
        $record->course = $course->id;
        $record->forum = $forum->get_id();
        $record->groupid = $group1->id;
        $record->userid = $USER->id;
        $record->timestart = time();
        $ids2 = $generator->create_discussion($record);

        // Start a third discussion in forum (group2).
        $record = new stdClass();
        $record->course = $course->id;
        $record->forum = $forum->get_id();
        $record->groupid = $group2->id;
        $record->userid = $USER->id;
        $record->timestart = time();
        $ids3 = $generator->create_discussion($record);

        // Set up tags in each discussion.
        $discussionid = $ids1[0];
        $discussion = mod_forumng_discussion::get_from_id($discussionid , 0);
        $this->assertEmpty($discussion->get_tags());
        // Edit discussion settings.
        $discussion->edit_settings($discussion::NOCHANGE, $discussion::NOCHANGE, $discussion::NOCHANGE, $discussion::NOCHANGE,
                $discussion::NOCHANGE, array('tag1', 'tag2', 'tag3'));
        $tags1 = $discussion->get_tags();
        $this->assertCount(3, $tags1);
        $discussion1 = $discussion;

        $discussionid = $ids2[0];
        $discussion = mod_forumng_discussion::get_from_id($discussionid , 0);
        $this->assertEmpty($discussion->get_tags());
        // Edit discussion settings.
        $discussion->edit_settings(mod_forumng_discussion::NOCHANGE, mod_forumng_discussion::NOCHANGE,
                mod_forumng_discussion::NOCHANGE, mod_forumng_discussion::NOCHANGE, mod_forumng_discussion::NOCHANGE,
                array('tag1', 'tag2'));
        $tags2 = $discussion->get_tags();
        $this->assertCount(2, $tags2);
        $discussion2 = $discussion;

        $discussionid = $ids3[0];
        $discussion = mod_forumng_discussion::get_from_id($discussionid , 0);
        $this->assertEmpty($discussion->get_tags());
        // Edit discussion settings.
        $discussion->edit_settings(mod_forumng_discussion::NOCHANGE, mod_forumng_discussion::NOCHANGE,
                mod_forumng_discussion::NOCHANGE, mod_forumng_discussion::NOCHANGE,
                mod_forumng_discussion::NOCHANGE, array('tag1'));
        $tags3 = $discussion->get_tags();
        $this->assertCount(1, $tags3);
        $discussion3 = $discussion;

        // Create a discussion with no tags for later use.
        $record = new stdClass();
        $record->course = $course->id;
        $record->forum = $forum->get_id();
        $record->groupid = $group2->id;
        $record->userid = $USER->id;
        $record->timestart = time();
        $ids4 = $generator->create_discussion($record);

        // Get id of 'tag1'.
        $tagid = array_search('tag1', $tags3);

        // Get all tags and check on numbers for each tag.
        $tagsused = $forum->get_tags_used();
        $this->assertCount(3, $tagsused);

        $tagsused1 = $forum->get_tags_used($group1->id);
        $this->assertCount(3, $tagsused1);

        $tagsused2 = $forum->get_tags_used($group2->id);
        $this->assertCount(1, $tagsused2);
        $this->assertArrayHasKey($tagid, $tagsused2);
        $this->assertObjectHasAttribute('displayname', $tagsused2[$tagid]);
        $this->assertObjectHasAttribute('count', $tagsused2[$tagid]);
        $this->assertEquals('tag1', $tagsused2[$tagid]->displayname);
        $this->assertEquals(1, $tagsused2[$tagid]->count);

        // Get all discussions featuring 'tag1'.
        $list = $forum->get_discussion_list(mod_forumng::ALL_GROUPS, $forum->can_view_hidden(),
                1, mod_forumng::SORT_DATE, false, 0, true,  $tagid);
        $taggedlist = $list->get_normal_discussions();
        $this->assertCount(3, $taggedlist);

        // Get all discussions featuring 'tag1'.for group1.
        $list1 = $forum->get_discussion_list($group1->id, $forum->can_view_hidden(),
                1, mod_forumng::SORT_DATE, false, 0, true,  $tagid);
        $taggedlist1 = $list1->get_normal_discussions();
        $this->assertCount(2, $taggedlist1);

        // Get all discussions featuring 'tag1'.for group2.
        $list2 = $forum->get_discussion_list($group2->id, $forum->can_view_hidden(),
                1, mod_forumng::SORT_DATE, false, 0, true,  $tagid);
        $taggedlist2 = $list2->get_normal_discussions();
        $this->assertCount(1, $taggedlist2);

        // Get all discussions featuring 'tag3'.for group2 - which should be none.
        $tagid = array_search('tag3', $tags1);
        $list = $forum->get_discussion_list($group2->id, $forum->can_view_hidden(),
                1, mod_forumng::SORT_DATE, false, 0, true,  $tagid);
        $taggedlist = $list->get_normal_discussions();
        $this->assertCount(0, $taggedlist);

        // Set tags specifically for group 1.
        $forumng = new stdClass();
        $forumng->settags = array('g1 vamp', 'g1 zomb', 'g1 mumm', 'g1 damm');
        $forumng->id = $forum->get_id();
        $forumng->instance = $forum->get_id();
        $forumng->tags = 1;
        forumng_update_instance($forumng);
        $context = $forum->get_context();
        mod_forumng::set_group_tags($forum->get_id(), $group1->id, $forumng->settags);
        $tagsused1 = $forum::get_set_tags($forumng->id, $group1->id);
        $this->assertCount(4, $tagsused1);

        // Set tags for group 2 (should return main set tags also).
        $g2tags = array('g2 ghost', 'g2 ghoul', 'g2 googl', 'g2 welf', 'g2 gobb');
        mod_forumng::set_group_tags($forum->get_id(), $group2->id, $g2tags);
        $tagsused2 = $forum::get_set_tags($forumng->id, $group2->id);
        $this->assertCount(9, $tagsused2);

        $tagsused3 = $forum::get_set_tags($forumng->id);
        $this->assertCount(4, $tagsused3);

        // Test that group only tags can be returned.
        $tagsused1 = $forum::get_set_tags($forumng->id, $group1->id, true);
        $this->assertCount(4, $tagsused1);
        $tagsused2 = $forum::get_set_tags($forumng->id, $group2->id, true);
        $this->assertCount(5, $tagsused2);

        // Need to test permanently delete.
        $discussionid = $ids4[0];
        $discussion = mod_forumng_discussion::get_from_id($discussionid , 0);
        $this->assertEmpty($discussion->get_tags());
        // Edit discussion settings.
        $discussion->edit_settings(mod_forumng_discussion::NOCHANGE, mod_forumng_discussion::NOCHANGE,
                mod_forumng_discussion::NOCHANGE, mod_forumng_discussion::NOCHANGE,
                mod_forumng_discussion::NOCHANGE, array('t1', 't2', 't3'));
        $tags4 = $discussion->get_tags();
        $this->assertCount(3, $tags4);
        $discussion4 = $discussion;
        // Delete discussion.
        $discussion4->permanently_delete(false);
        $this->assertFalse($DB->get_record('forumng_discussions', array('id' => $discussion->get_id())));
        $this->assertEmpty($DB->get_records('tag_instance',
                array('itemid' => $discussion->get_id(), 'itemtype' => 'forumng discussions')));

        // Test group tag setting.
        $user2 = $this->get_new_user();
        $this->setUser($user2);
        mod_forumng::set_group_tags($forumng->id, $group1->id, array('t1', 'tnew'));
        $tagsused = $forum::get_set_tags($forumng->id, $group1->id, true);
        $this->assertCount(2, $tagsused);
        mod_forumng::set_group_tags($forumng->id, $group1->id, array('t1'));
        $tagsused = $forum::get_set_tags($forumng->id, $group1->id, true);
        $this->assertCount(1, $tagsused);
        // Create a new forum and add same group tags.
        $forumrecord2 = $generator->create_instance(array('course' => $course->id, 'tags' => true,
                'groupmode' => VISIBLEGROUPS));
        $forum2 = mod_forumng::get_from_id($forumrecord2->id, mod_forumng::CLONE_DIRECT, true);
        mod_forumng::set_group_tags($forumrecord2->id, $group1->id, array('t1'));
        $tagsused = $forum::get_set_tags($forumrecord2->id, $group1->id, true);
        $this->assertCount(1, $tagsused);
        // Create a new forum and add same group tags (should use another user).
        $forumrecord2 = $generator->create_instance(array('course' => $course->id, 'tags' => true,
                'groupmode' => VISIBLEGROUPS));
        $forum2 = mod_forumng::get_from_id($forumrecord2->id, mod_forumng::CLONE_DIRECT, true);
        mod_forumng::set_group_tags($forumrecord2->id, $group1->id, array('t1'));
        $tagsused = $forum::get_set_tags($forumrecord2->id, $group1->id, true);
        $this->assertCount(1, $tagsused);
        // Create a new forum and add same group tags (should fail as ou of users).
        $forumrecord2 = $generator->create_instance(array('course' => $course->id, 'tags' => true,
                'groupmode' => VISIBLEGROUPS));
        $forum2 = mod_forumng::get_from_id($forumrecord2->id, mod_forumng::CLONE_DIRECT, true);
        $this->setExpectedException('moodle_exception');
        mod_forumng::set_group_tags($forumrecord2->id, $group1->id, array('t1'));
        // Do not add any tests after this point as exception called above.
    }

    /**
     * Tests deleting discussion and permanent delete
     * Checks completion
     */
    public function test_delete() {
        global $DB, $USER, $SITE, $CFG;

        require_once($CFG->dirroot . '/mod/forumng/mod_forumng_cron.php');
        $CFG->enablecompletion = true;

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $course = $this->get_new_course();
        $course->enablecompletion = 1;
        update_course($course);

        $forum = $this->get_new_forumng($course->id, array('removeafter' => 1, 'removeto' => 0,
                'completion' => 2, 'completiondiscussions' => 1));
        $completion = new completion_info($forum->get_course());

        $discussion = $this->get_new_discussion($forum, array('userid' => $USER->id));
        $root1 = $discussion->get_root_post();
        // Get completion status.
        $complete = $completion->get_data($forum->get_course_module());
        $this->assertEquals(COMPLETION_COMPLETE, $complete->completionstate);
        $discussion2 = $this->get_new_discussion($forum, array('userid' => $USER->id));
        // Make post old.
        $root2 = $discussion2->get_root_post();
        $dataobject = new stdClass();
        $dataobject->id = $root2->get_id();
        $dataobject->modified = $root2->get_modified() - 100;
        $DB->update_record('forumng_posts', $dataobject);
        // Check perm delete by manual call.
        $discussion->permanently_delete(false);
        $this->assertFalse($DB->get_record('forumng_discussions', array('id' => $discussion->get_id())));
        $this->assertFalse($DB->get_record('forumng_posts', array('id' => $root1->get_id())));
        // Check cron cleanup (Does permanently_delete() on discussion2).
        mod_forumng_cron::archive_old_discussions();
        $this->assertFalse($DB->get_record('forumng_discussions', array('id' => $discussion2->get_id())));
        $this->assertFalse($DB->get_record('forumng_posts', array('id' => $root2->get_id())));

        $complete = $completion->get_data($forum->get_course_module());
        $this->assertEquals(COMPLETION_INCOMPLETE, $complete->completionstate);
        if (mod_forumng::search_installed()) {
            $searchdoc = $root2->search_get_document();
            $this->assertFalse($searchdoc->find());
            $query = new local_ousearch_search('Message for discussion');
            $query->set_coursemodule($forum->get_course_module(true));
            $results = $query->query();
            $this->assertEmpty($results->results);
        }
    }
}
