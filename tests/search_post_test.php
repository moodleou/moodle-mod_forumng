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
 * Unit tests for search API code.
 *
 * @package mod_forumng
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/forumng/tests/forumng_test_lib.php');
require_once($CFG->dirroot . '/search/tests/fixtures/testable_core_search.php');

use \mod_forumng\search\post as search_post;

/**
 * Test case for generic functions in classes/searcg/post.php where covered.
 */
class mod_forumng_search_post_testcase extends forumng_test_lib {

    /**
     * Tests get_recordset_by_timestamp function (obtains modified document pages) and get_document
     * function (converts them into the format the search system wants).
     */
    public function test_post_search_index() {
        global $CFG;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Enable global search for this test.
        set_config('enableglobalsearch', true);
        $search = testable_core_search::instance();

        // First check there are no results with empty database.
        $page = new search_post();
        $rs = $page->get_recordset_by_timestamp();
        $this->assertCount(0, self::recordset_to_array($rs));

        $CFG->forumng_trackreadposts = true;
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_forumng');

        $course = $this->get_new_course();
        $course2 = $this->get_new_course();
        $etuser = $this->get_new_user('editingteacher', $course->id);
        $suser1 = $this->get_new_user('student', $course->id);
        $suser2 = $this->get_new_user('student', $course->id);
        $group1 = $this->get_new_group($course->id);
        $group2 = $this->get_new_group($course->id);
        $this->get_new_group_member($group1->id, $suser1->id);
        $this->get_new_group_member($group2->id, $suser2->id);
        $grouping = $this->get_new_grouping($course->id);
        $this->get_new_grouping_group($grouping->id, $group1->id);
        $this->get_new_grouping_group($grouping->id, $group2->id);

        $forum1 = $this->get_new_forumng($course->id, array('groupmode' => SEPARATEGROUPS,
            'shared' => false, 'cmidnumber' => 'IPMR'));
        $forum2 = $this->get_new_forumng($course2->id, array('groupmode' => NOGROUPS,
                'shared' => false, 'cmidnumber' => 'IPMRII'));

        $did1 = $generator->create_discussion(array(
                'course' => $course,
                'forum' => $forum1->get_id(),
                'userid' => $etuser->id,
                'subject' => 'Discussion 1',
                'groupid' => $group1->id));
        $post1 = $generator->create_post(array(
                'discussionid' => $did1[0],
                'parentpostid' => $did1[1],
                'userid' => $suser1->id,
                'subject' => 'Post 1',
                'message' => 'Message 1'));
        $post2 = $generator->create_post(array(
                'discussionid' => $did1[0],
                'parentpostid' => $did1[1],
                'userid' => $suser2->id));

        $cm = get_coursemodule_from_instance('forumng', $forum1->get_id(), $forum1->get_course_id());
        $context = \context_module::instance($cm->id);

        // Create discussion tags.
        $generator->create_tag_instance($did1[0], $context, array('distag1'));

        // Now check we get results.
        $results = self::recordset_to_array($page->get_recordset_by_timestamp());
        $this->assertCount(3, $results);

        // Check first one in detail using the get_document function.
        $out = $page->get_document($results[1], array('lastindexedtime' => 0));
        $this->assertEquals('Post 1', $out->get('title'));
        $this->assertEquals('Message 1', $out->get('content'));
        $this->assertEquals('distag1', $out->get('description1'));
        $this->assertEquals($context->id, $out->get('contextid'));
        $this->assertEquals(\core_search\manager::TYPE_TEXT, $out->get('type'));
        $this->assertEquals($course->id, $out->get('courseid'));
        $this->assertEquals($post1->id, $out->get('itemid'));
        $this->assertEquals(\core_search\manager::NO_OWNER_ID, $out->get('owneruserid'));
        $this->assertTrue($out->get_is_new());

        // Check access.
        $did2 = $generator->create_discussion(array(
            'course' => $course,
            'forum' => $forum1->get_id(),
            'userid' => $etuser->id,
            'subject' => 'Discussion 1',
            'groupid' => $group2->id));
        $post1did2 = $generator->create_post(array(
            'discussionid' => $did2[0],
            'parentpostid' => $did2[1],
            'userid' => $suser1->id,
            'subject' => 'Post 3',
            'message' => 'Message 3'));

        // For students in group1, they can access posts in group1 only.
        $this->setUser($suser1);
        $this->assertEquals(\core_search\manager::ACCESS_GRANTED, $page->check_access($post1->id));
        $this->assertEquals(\core_search\manager::ACCESS_DENIED, $page->check_access($post1did2->id));

        // For editing teachers, they can access both posts in group1 and posts in group2.
        $this->setUser($etuser);
        $this->assertEquals(\core_search\manager::ACCESS_GRANTED, $page->check_access($post1->id));
        $this->assertEquals(\core_search\manager::ACCESS_GRANTED, $page->check_access($post1did2->id));

        // Check search result url.
        $discussionurl = $page->get_doc_url($out)->out(false);
        $this->assertEquals($CFG->wwwroot . '/mod/forumng/discuss.php?d=' . $did1[0] . '#p' . $post1->id,
                $discussionurl);

        // Check post attachment.
        $fs = get_file_storage();
        $filerecord = array(
            'contextid' => $context->id,
            'component' => 'mod_forumng',
            'filearea'  => search_post::FILEAREA['ATTACHMENT'],
            'itemid'    => $post1->id,
            'filepath'  => '/',
            'filename'  => 'file1.txt'
        );
        $file1 = $fs->create_file_from_string($filerecord, 'File 1 content');

        $filerecord = array(
            'contextid' => $context->id,
            'component' => 'mod_forumng',
            'filearea'  => search_post::FILEAREA['MESSAGE'],
            'itemid'    => $post1->id,
            'filepath'  => '/',
            'filename'  => 'file2.txt'
        );
        $file2 = $fs->create_file_from_string($filerecord, 'File 2 content');

        $forumngpostareaid = \core_search\manager::generate_areaid('mod_forumng', 'post');
        $searcharea = \core_search\manager::get_search_area($forumngpostareaid);

        $this->assertCount(0, $out->get_files());
        $searcharea->attach_files($out);
        $files = $out->get_files();
        $this->assertCount(2, $files);
        foreach ($files as $file) {
            if ($file->is_directory()) {
                continue;
            }

            switch ($file->get_filearea()) {

                case search_post::FILEAREA['ATTACHMENT']:
                    $this->assertEquals('file1.txt', $file->get_filename());
                    $this->assertEquals('File 1 content', $file->get_content());
                    break;

                case search_post::FILEAREA['MESSAGE']:
                    $this->assertEquals('file2.txt', $file->get_filename());
                    $this->assertEquals('File 2 content', $file->get_content());
                    break;

                default:
                    break;
            }
        }

        // Now check context using get_document_recordset.
        $did21 = $generator->create_discussion(array(
                'course' => $course2,
                'forum' => $forum2->get_id(),
                'userid' => $etuser->id,
                'subject' => 'Discussion 2.1'));
        $post21 = $generator->create_post(array(
                'discussionid' => $did21[0],
                'parentpostid' => $did21[1],
                'userid' => $suser1->id,
                'subject' => 'Post 2.1',
                'message' => 'Message 1'));
        $results2 = self::recordset_to_array($page->get_document_recordset());
        $this->assertCount(7, $results2);
        $modulecontext = \context_module::instance($cm->id);
        $results3 = self::recordset_to_array($page->get_document_recordset(0, $modulecontext));
        $this->assertCount(5, $results3);
    }

    /**
     * Converts recordset to array, indexed numberically (0, 1, 2).
     *
     * @param moodle_recordset $rs Record set to convert
     * @return stdClass[] Array of converted records
     */
    protected static function recordset_to_array(moodle_recordset $rs) {
        $result = array();
        foreach ($rs as $rec) {
            $result[] = $rec;
        }
        $rs->close();
        return $result;
    }

    /**
     *  Tests group support for forum posts.
     */
    public function test_posts_for_group_support() {
        global $CFG;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Enable global search for this test.
        set_config('enableglobalsearch', true);
        $search = testable_core_search::instance();

        // Get the search area and test generators.
        $forumngpostareaid = \core_search\manager::generate_areaid('mod_forumng', 'post');
        $searcharea = \core_search\manager::get_search_area($forumngpostareaid);

        $generator = $this->getDataGenerator();
        $forumgenerator = $generator->get_plugin_generator('mod_forumng');
        // Create a course, a user, and two groups.
        $course = $generator->create_course();
        $user = $generator->create_user();
        $generator->enrol_user($user->id, $course->id, 'teacher');
        $group1 = $generator->create_group(['courseid' => $course->id]);
        $group2 = $generator->create_group(['courseid' => $course->id]);
        // Separate groups forum.
        $forum = self::getDataGenerator()->create_module('forumng', ['course' => $course->id,
                'groupmode' => SEPARATEGROUPS]);
        // Create discussion with each group and one for all groups. One has a post in.
        $discussion1 = $forumgenerator->create_discussion(['course' => $course->id,
                'userid' => $user->id, 'forum' => $forum->id, 'message' => 'd1',
                'groupid' => $group1->id]);
        $forumgenerator->create_discussion(['course' => $course->id,
                'userid' => $user->id, 'forum' => $forum->id, 'message' => 'd2',
                'groupid' => $group2->id]);
        $forumgenerator->create_discussion(['course' => $course->id,
                'userid' => $user->id, 'forum' => $forum->id, 'message' => 'd3']);
        // Create a reply in discussion1.
        $forumgenerator->create_post(['discussionid' => $discussion1[0], 'parentpostid' => $discussion1[1],
                'userid' => $user->id, 'message' => 'p1']);
        // Do the indexing of all 4 posts.
        $rs = $searcharea->get_recordset_by_timestamp(0);
        $results = [];
        foreach ($rs as $rec) {
            $results[$rec->message] = $rec;
        }
        $rs->close();
        $this->assertCount(4, $results);
        // Check each document has the correct groupid.
        $doc = $searcharea->get_document($results['d1']);
        $this->assertTrue($doc->is_set('groupid'));
        $this->assertEquals($group1->id, $doc->get('groupid'));
        $doc = $searcharea->get_document($results['d2']);
        $this->assertTrue($doc->is_set('groupid'));
        $this->assertEquals($group2->id, $doc->get('groupid'));
        $doc = $searcharea->get_document($results['d3']);
        $this->assertFalse($doc->is_set('groupid'));
        $doc = $searcharea->get_document($results['p1']);
        $this->assertTrue($doc->is_set('groupid'));
        $this->assertEquals($group1->id, $doc->get('groupid'));

        // While we're here, also test that the search area requests restriction by group.
        $modinfo = get_fast_modinfo($course);
        $this->assertTrue($searcharea->restrict_cm_access_by_group($modinfo->get_cm($forum->cmid)));

        // In visible groups mode, it won't request restriction by group.
        set_coursemodule_groupmode($forum->cmid, VISIBLEGROUPS);
        $modinfo = get_fast_modinfo($course);
        $this->assertFalse($searcharea->restrict_cm_access_by_group($modinfo->get_cm($forum->cmid)));
    }

    /**
     * Tests that reindexing works in order starting from the forum with most recent discussion.
     */
    public function test_posts_for_get_contexts_to_reindex() {
        global $DB, $CFG;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Enable global search for this test.
        set_config('enableglobalsearch', true);

        $forumngpostareaid = \core_search\manager::generate_areaid('mod_forumng', 'post');
        $search = testable_core_search::instance();
        $generator = $this->getDataGenerator();
        $adminuser = get_admin();
        $course1 = $generator->create_course();
        $course2 = $generator->create_course();

        $time = time() - 1000;

        // Create 3 forums (two in course 1, one in course 2 - doesn't make a difference).
        $forum1 = $generator->create_module('forumng', ['course' => $course1->id]);
        $forum2 = $generator->create_module('forumng', ['course' => $course1->id]);
        $forum3 = $generator->create_module('forumng', ['course' => $course2->id]);
        $forum4 = $generator->create_module('forumng', ['course' => $course2->id]);

        // Hack added time for the course_modules entries. These should not be used (they would
        // be used by the base class implementation). We are setting this so that the order would
        // be 4, 3, 2, 1 if this ordering were used (newest first).
        $DB->set_field('course_modules', 'added', $time + 100, ['id' => $forum1->cmid]);
        $DB->set_field('course_modules', 'added', $time + 110, ['id' => $forum2->cmid]);
        $DB->set_field('course_modules', 'added', $time + 120, ['id' => $forum3->cmid]);
        $DB->set_field('course_modules', 'added', $time + 130, ['id' => $forum4->cmid]);

        $forumgenerator = $generator->get_plugin_generator('mod_forumng');

        // Create one discussion in forums 1 and 3, three in forum 2, and none in forum 4.

        $forumgenerator->create_discussion(['course' => $course1->id,
                'forum' => $forum1->id, 'userid' => $adminuser->id, 'modified' => $time + 20]);
        $forumgenerator->create_discussion(['course' => $course1->id,
                'forum' => $forum2->id, 'userid' => $adminuser->id, 'modified' => $time + 10]);
        $forumgenerator->create_discussion(['course' => $course1->id,
                'forum' => $forum2->id, 'userid' => $adminuser->id, 'modified' => $time + 30]);
        $forumgenerator->create_discussion(['course' => $course1->id,
                'forum' => $forum2->id, 'userid' => $adminuser->id, 'modified' => $time + 11]);

        $forumgenerator->create_discussion(['course' => $course2->id,
                'forum' => $forum3->id, 'userid' => $adminuser->id, 'modified' => $time + 25]);

        // Get the contexts in reindex order.
        $area = \core_search\manager::get_search_area($forumngpostareaid);
        $contexts = iterator_to_array($area->get_contexts_to_reindex(), false);

        // We expect them in order of newest discussion. Forum 4 is not included at all (which is
        // correct because it has no content).
        $expected = [
                \context_module::instance($forum2->cmid),
                \context_module::instance($forum3->cmid),
                \context_module::instance($forum1->cmid)
        ];
        $this->assertEquals($expected, $contexts);
    }
}
