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
 * Mobile web service function tests.
 *
 * @package mod_forumng
 * @copyright 2018 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tests\mod_forumng;

use \mod_forumng\output\mobile;
use \mod_forumng\local\external\more_discussions;

defined('MOODLE_INTERNAL') || die();

/**
 * Mobile web service function tests.
 */
class mobile_testcase extends \advanced_testcase {

    /**
     * Test the basic functionality of the forum view page.
     */
    public function test_mobile_forumng_view_basic() {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_user();
        $generator->enrol_user($student->id, $course->id, 'student');
        $this->setUser($student);
        $forumnggenerator = $generator->get_plugin_generator('mod_forumng');
        $forum = $forumnggenerator->create_instance(['course' => $course->id]);
        $cm = get_coursemodule_from_instance('forumng', $forum->id);

        // Request initial page.
        $args = [
            'cmid' => $cm->id,
            'courseid' => $course->id,
            'userid' => $student->id
        ];
        $result = mobile::forumng_view($args);

        // Basic forum with no discussions.
        $this->assertEquals([], $result['files']);
        $otherdata = $result['otherdata'];
        $this->assertEquals(-1, $otherdata['defaultgroup']);
        $this->assertEquals(1, count($otherdata['discussions']));
        $this->assertEquals(0, $otherdata['totaldiscussions']);
        $this->assertEquals(1, $otherdata['page']);
        $template = $result['templates'][0];
        $this->assertEquals('main', $template['id']);
        $this->assertContains('Test forum 1', $template['html']);

        // Add a discussion.
        $record = [];
        $record['course'] = $course->id;
        $record['forum'] = $forum->id;
        $record['userid'] = $student->id;
        $forumnggenerator->create_discussion($record);
        $result = mobile::forumng_view($args);
        $otherdata = $result['otherdata'];
        $this->assertEquals(-1, $otherdata['defaultgroup']);
        $this->assertEquals(1, count($otherdata['discussions']));
        $this->assertEquals(1, $otherdata['totaldiscussions']);
        $this->assertEquals(1, $otherdata['page']);
    }

    /**
     * Test the more_discussions webservice functionality.
     */
    public function test_mobile_forumng_more_discussions() {
        global $CFG;

        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_user();
        $generator->enrol_user($student->id, $course->id, 'student');
        $this->setUser($student);
        $forumnggenerator = $generator->get_plugin_generator('mod_forumng');
        $forum = $forumnggenerator->create_instance(['course' => $course->id]);
        $cm = get_coursemodule_from_instance('forumng', $forum->id);
        // Set the discussions per page to a low number as we want page 2 results.
        $CFG->forumng_discussionsperpage = 2;

        // Add discussions.
        $record = [];
        $record['course'] = $course->id;
        $record['forum'] = $forum->id;
        $record['userid'] = $student->id;
        $forumnggenerator->create_discussion($record);
        $forumnggenerator->create_discussion($record);
        $forumnggenerator->create_discussion($record);

        // Get the second page of discussions from the webservice function.
        $pageno = 2;
        $result = more_discussions::more_discussions($cm->id, \mod_forumng::NO_GROUPS, $pageno);
        // The second page only has one discussion (the oldest) so has subject no of 1.
        $this->assertCount(1, $result);
        $this->assertEquals('Subject for discussion 1', $result[0]->subject);
    }
}
