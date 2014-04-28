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
 * Forum database upgrade script.
 * @package mod
 * @subpackage forumng
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function xmldb_forumng_upgrade($oldversion=0) {
    global $CFG, $THEME, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2012070900) {
        // Changed format of modinfo cache, so need to rebuild all courses.
        rebuild_course_cache(0, true);
        upgrade_mod_savepoint(true, 2012070900, 'forumng');
    }

    if ($oldversion < 2012102601) {
        // Define field gradingscale to be added to forumng.
        $table = new xmldb_table('forumng');
        $field = new xmldb_field('gradingscale', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'grading');

        // Launch add field gradingscale.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Changed format of modinfo cache, so need to rebuild all courses.
        rebuild_course_cache(0, true);

        // ForumNG savepoint reached.
        upgrade_mod_savepoint(true, 2012102601, 'forumng');
    }

    if ($oldversion < 2013082000) {
        // Fix posts that have been orphaned after incorrect clean up in cron.
        // This is processed in a recordset with update per row as 1 big update is too slow.

        // Find affected posts info, put into recordset.
        $sql = 'SELECT p.id, d.postid
                FROM {forumng_posts} p
                JOIN {forumng_discussions} d on d.id = p.discussionid
                WHERE p.parentpostid IS NOT NULL
                AND NOT EXISTS (SELECT id FROM {forumng_posts} WHERE id = p.parentpostid)';
        $rs = $DB->get_records_sql($sql);
        if ($rs) {
            $pbar = new progress_bar('mod_forumng_fixposts', 500, true);
            $cur = 1;
            $total = count($rs);
            // Update each row, making parent post id the discussion root post.
            foreach ($rs as $record) {
                $update = new stdClass();
                $update->id = $record->id;
                $update->parentpostid = $record->postid;
                $DB->update_record('forumng_posts', $update);
                $pbar->update($cur, $total, 'Repair ForumNG orphaned posts');
                $cur++;
            }
        }
        // ForumNG savepoint reached.
        upgrade_mod_savepoint(true, 2013082000, 'forumng');
    }

    if ($oldversion < 2013100801) {
        // Define field canpostanon to be added to forumng.
        $table = new xmldb_table('forumng');
        $field = new xmldb_field('canpostanon', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'gradingscale');

        // Launch add field canpostanon.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field asmoderator to be added to forumng_posts.
        $table = new xmldb_table('forumng_posts');
        $field = new xmldb_field('asmoderator', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'attachments');

        // Launch add field asmoderator.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Changed format of modinfo cache, so need to rebuild all courses.
        rebuild_course_cache(0, true);

        // ForumNG savepoint reached.
        upgrade_mod_savepoint(true, 2013100801, 'forumng');
    }

    if ($oldversion < 2014031200) {
        global $DB;
        set_time_limit(0);
        // Fix issue with read table having duplicate entries.
        $select = "SELECT r.userid, r.discussionid
                     FROM {forumng_read} r
                 GROUP BY r.userid, r.discussionid
                   HAVING COUNT(1) > 1";
        $duplicates = $DB->get_records_sql($select);
        if ($duplicates) {
            $pbar = new progress_bar('mod_forumng_fixread', 500, true);
            $cur = 1;
            $total = count($duplicates);
            foreach ($duplicates as $duplicate) {
                // Find other records with user and discussion - keep latest time or lowest id.
                $select = "id IN(
                        SELECT DISTINCT r1.id FROM {forumng_read} r1
                          JOIN {forumng_read} r2 ON r2.discussionid = r1.discussionid
                           AND r2.userid = r1.userid AND r2.id != r1.id
                           AND (r2.time > r1.time OR (r2.time = r1.time AND r2.id > r1.id))
                         WHERE r1.userid = ? AND r1.discussionid = ?
                        )";
                $result = $DB->delete_records_select('forumng_read', $select,
                        array($duplicate->userid, $duplicate->discussionid));
                $pbar->update($cur, $total, 'Remove duplicate ForumNG read rows');
                $cur++;
            }
        }

        // Drop then add index as don't seem to be able to update...

        // Define index userid-discussionid (not unique) to be dropped form forumng_read.
        $table = new xmldb_table('forumng_read');
        $index = new xmldb_index('userid-discussionid', XMLDB_INDEX_NOTUNIQUE, array('userid', 'discussionid'));

        // Conditionally launch drop index userid-discussionid.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Define index userid-discussionid (unique) to be added to forumng_read.
        $table = new xmldb_table('forumng_read');
        $index = new xmldb_index('userid-discussionid', XMLDB_INDEX_UNIQUE, array('userid', 'discussionid'));

        // Conditionally launch add index userid-discussionid.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Forumng savepoint reached.
        upgrade_mod_savepoint(true, 2014031200, 'forumng');
    }

    return true;
}
