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
 * @package mod_forumng
 * @copyright 2021 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_datamasking_test extends \advanced_testcase {
    protected function tearDown(): void {
        global $CFG;
        if (file_exists("{$CFG->dirroot}/admin/tool/datamasking/version.php")) {
            \tool_datamasking\mapping_tables::reset();
            \tool_datamasking\files_mask::clear_statics();
            \tool_datamasking\masked_glossaries::clear_statics();
        }
        parent::tearDown();
    }

    /**
     * Tests actual behaviour of the masking applied in this plugin.
     */
    public function test_behaviour(): void {
        global $DB, $CFG;

        if (!file_exists("{$CFG->dirroot}/admin/tool/datamasking/version.php")) {
            $this->markTestSkipped('This test uses tool_datamasking, which is not installed. Skipping.');
        }

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
        $itemid = $DB->insert_record('forumng_posts',
                ['discussionid' => 1, 'userid' => 1, 'created' => 1, 'modified' => 1, 'messageformat' => 1,
                        'subject' => 'Q!', 'message' => '<img src="@@PLUGINFILE@@/f%201.txt" alt="X"/>']);

        $DB->insert_record('forumng_drafts',
                ['forumngid' => 1, 'userid' => 1, 'saved' => 1, 'messageformat' => 1,
                'subject' => null, 'message' => '<p>Q.</p>']);
        $DB->insert_record('forumng_drafts',
                ['forumngid' => 1, 'userid' => 1, 'saved' => 1, 'messageformat' => 1,
                'subject' => 'Q!', 'message' => '']);

        // Add some files.
        $fileids = [];
        $fileids[] = \tool_datamasking\testing_utils::add_file('mod_forumng', 'attachment', 'a.txt', 'a');
        $fileids[] = \tool_datamasking\testing_utils::add_file('mod_forumng', 'draft', 'b.txt', 'bb');
        $fileids[] = \tool_datamasking\testing_utils::add_file('mod_forumng', 'draftmessage', 'c.txt', 'ccc');
        $fileids[] = \tool_datamasking\testing_utils::add_file('mod_forumng', 'message', 'd.txt', 'dddd');
        $fileids[] = \tool_datamasking\testing_utils::add_file('mod_forumng', 'intro', 'e.txt', 'eeeee');
        $fileids[] = \tool_datamasking\testing_utils::add_file('mod_forumng', 'message', 'f 1.txt', 'ffffff', $itemid);

        // Before checks.
        $forumngreportingemailsql = 'SELECT reportingemail FROM {forumng} ORDER BY id';
        $this->assertEquals(['secret@example.org', ''], $DB->get_fieldset_sql($forumngreportingemailsql));

        $forumngpostsmessagesql = 'SELECT message FROM {forumng_posts} ORDER BY id';
        $this->assertEquals(['<p>Q.</p>', '', '<img src="@@PLUGINFILE@@/f%201.txt" alt="X"/>'], $DB->get_fieldset_sql($forumngpostsmessagesql));
        $forumngpostssubjectsql = 'SELECT subject FROM {forumng_posts} ORDER BY id';
        $this->assertEquals([null, 'Q!', 'Q!'], $DB->get_fieldset_sql($forumngpostssubjectsql));

        $forumngdraftsmessagesql = 'SELECT message FROM {forumng_drafts} ORDER BY id';
        $this->assertEquals(['<p>Q.</p>', ''], $DB->get_fieldset_sql($forumngdraftsmessagesql));
        $forumngdraftssubjectsql = 'SELECT subject FROM {forumng_drafts} ORDER BY id';
        $this->assertEquals([null, 'Q!'], $DB->get_fieldset_sql($forumngdraftssubjectsql));

        \tool_datamasking\testing_utils::check_file($this, $fileids[0], 'a.txt', 1);
        \tool_datamasking\testing_utils::check_file($this, $fileids[1], 'b.txt', 2);
        \tool_datamasking\testing_utils::check_file($this, $fileids[2], 'c.txt', 3);
        \tool_datamasking\testing_utils::check_file($this, $fileids[3], 'd.txt', 4);
        \tool_datamasking\testing_utils::check_file($this, $fileids[4], 'e.txt', 5);
        \tool_datamasking\testing_utils::check_file($this, $fileids[5], 'f 1.txt', 6);

        // Run the full masking plan including this plugin, but without requiring mapping tables.
        \tool_datamasking\api::get_plan()->execute([], [\tool_datamasking\tool_datamasking::TAG_SKIP_ID_MAPPING]);

        // After checks.
        $this->assertEquals(['email' . $forumngid . '@open.ac.uk.invalid', ''],
                $DB->get_fieldset_sql($forumngreportingemailsql));

        $posts = $DB->get_fieldset_sql($forumngpostsmessagesql);

        $this->assertEquals(['<p>X.</p>', '', '<img src="@@PLUGINFILE@@/masked.txt" alt="X"/>'], $DB->get_fieldset_sql($forumngpostsmessagesql));
        $this->assertEquals([null, 'X!', 'X!'], $DB->get_fieldset_sql($forumngpostssubjectsql));

        $this->assertEquals(['<p>X.</p>', ''], $DB->get_fieldset_sql($forumngdraftsmessagesql));
        $this->assertEquals([null, 'X!'], $DB->get_fieldset_sql($forumngdraftssubjectsql));

        \tool_datamasking\testing_utils::check_file($this, $fileids[0], 'masked.txt', 224);
        \tool_datamasking\testing_utils::check_file($this, $fileids[1], 'masked.txt', 224);
        \tool_datamasking\testing_utils::check_file($this, $fileids[2], 'masked.txt', 224);
        \tool_datamasking\testing_utils::check_file($this, $fileids[3], 'masked.txt', 224);
        \tool_datamasking\testing_utils::check_file($this, $fileids[4], 'e.txt', 5);
        \tool_datamasking\testing_utils::check_file($this, $fileids[5], 'masked.txt', 224);
    }
}
