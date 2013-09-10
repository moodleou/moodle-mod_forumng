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

    return true;
}
