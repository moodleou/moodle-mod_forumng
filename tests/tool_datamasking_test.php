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

namespace mod_forumng;

/**
 * Tests the tool_datamasking class for this plugin.
 *
 * @package tool_datamasking
 * @copyright 2021 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_datamasking_test extends \advanced_testcase {

    /**
     * Tests actual behaviour of the masking applied in this plugin.
     */
    public function test_behaviour(): void {
        global $DB;

        $this->resetAfterTest();

        // Delete existing entry in forums table to avoid confusion.
        $DB->delete_records('forumng');

        // Set up data to be masked.
        $forumngid = $DB->insert_record('forumng',
                ['course' => 1, 'name' => 'N', 'magicnumber' => 1, 'timemodified' => 1,
                'reportingemail' => 'secret@example.org']);
        $DB->insert_record('forumng',
                ['course' => 1, 'name' => 'N', 'magicnumber' => 1, 'timemodified' => 1]);

        // Note: For the similar_text_mask, text is usually random which makes it harder to test,
        // but extremely short text strings are always masked to 'X', so we use a short string here.
        $DB->insert_record('forumng_posts',
                ['discussionid' => 1, 'userid' => 1, 'created' => 1, 'modified' => 1, 'messageformat' => 1,
                'subject' => null, 'message' => '<p>Q.</p>']);
        $DB->insert_record('forumng_posts',
                ['discussionid' => 1, 'userid' => 1, 'created' => 1, 'modified' => 1, 'messageformat' => 1,
                'subject' => 'Q!', 'message' => '']);

        $DB->insert_record('forumng_drafts',
                ['forumngid' => 1, 'userid' => 1, 'saved' => 1, 'messageformat' => 1,
                'subject' => null, 'message' => '<p>Q.</p>']);
        $DB->insert_record('forumng_drafts',
                ['forumngid' => 1, 'userid' => 1, 'saved' => 1, 'messageformat' => 1,
                'subject' => 'Q!', 'message' => '']);

        // Before checks.
        $forumngreportingemailsql = 'SELECT reportingemail FROM {forumng} ORDER BY id';
        $this->assertEquals(['secret@example.org', ''], $DB->get_fieldset_sql($forumngreportingemailsql));

        $forumngpostsmessagesql = 'SELECT message FROM {forumng_posts} ORDER BY id';
        $this->assertEquals(['<p>Q.</p>', ''], $DB->get_fieldset_sql($forumngpostsmessagesql));
        $forumngpostssubjectsql = 'SELECT subject FROM {forumng_posts} ORDER BY id';
        $this->assertEquals([null, 'Q!'], $DB->get_fieldset_sql($forumngpostssubjectsql));

        $forumngdraftsmessagesql = 'SELECT message FROM {forumng_drafts} ORDER BY id';
        $this->assertEquals(['<p>Q.</p>', ''], $DB->get_fieldset_sql($forumngdraftsmessagesql));
        $forumngdraftssubjectsql = 'SELECT subject FROM {forumng_drafts} ORDER BY id';
        $this->assertEquals([null, 'Q!'], $DB->get_fieldset_sql($forumngdraftssubjectsql));

        // Run the full masking plan including this plugin.
        \tool_datamasking\api::get_plan()->execute();

        // After checks.
        $this->assertEquals(['email' . $forumngid . '@open.ac.uk.invalid', ''],
                $DB->get_fieldset_sql($forumngreportingemailsql));

        $this->assertEquals(['<p>X.</p>', ''], $DB->get_fieldset_sql($forumngpostsmessagesql));
        $this->assertEquals([null, 'X!'], $DB->get_fieldset_sql($forumngpostssubjectsql));

        $this->assertEquals(['<p>X.</p>', ''], $DB->get_fieldset_sql($forumngdraftsmessagesql));
        $this->assertEquals([null, 'X!'], $DB->get_fieldset_sql($forumngdraftssubjectsql));
    }
}
