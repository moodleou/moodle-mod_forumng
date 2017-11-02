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

defined('MOODLE_INTERNAL') || die();

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
        core_php_time_limit::raise(0);
        // Fix issue with read table having duplicate entries.
        $select = "SELECT " . $DB->sql_concat('r.userid', "'|'", 'r.discussionid') . ", r.userid, r.discussionid
                     FROM {forumng_read} r
                 GROUP BY " . $DB->sql_concat('r.userid', "'|'", 'r.discussionid') . ", r.userid, r.discussionid
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

    if ($oldversion < 2014072800) {

        // Define field discussionid to be added to forumng_flags.
        $table = new xmldb_table('forumng_flags');
        $field = new xmldb_field('discussionid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'flagged');

        // Conditionally launch add field discussionid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define key discussionid (foreign) to be added to forumng_flags.
        $key = new xmldb_key('discussionid', XMLDB_KEY_FOREIGN, array('discussionid'), 'forumng_discussion', array('id'));

        // Launch add key discussionid.
        $dbman->add_key($table, $key);

        // Forumng savepoint reached.
        upgrade_mod_savepoint(true, 2014072800, 'forumng');
    }

    if ($oldversion < 2014102400) {

        // Define field tags to be added to forumng.
        $table = new xmldb_table('forumng');
        $field = new xmldb_field('tags', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'canpostanon');

        // Conditionally launch add field tags.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Forumng savepoint reached.
        upgrade_mod_savepoint(true, 2014102400, 'forumng');
    }

    if ($oldversion < 2014102800) {
        // Define field enableratings to be added to forumng.
        $table = new xmldb_table('forumng');
        $field = new xmldb_field('enableratings', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'canpostanon');

        // Launch add field enableratings.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Foreach existing 'Forumng ratings (obsolete)'
        // Set the enableratings field to FORUMNG_RATING_OBSOLETE=1 for everything that has a rating.
        $DB->set_field_select('forumng', 'enableratings', 1, 'ratingscale != 0');

        // ForumNG savepoint reached.
        upgrade_mod_savepoint(true, 2014102800, 'forumng');
    }

    if ($oldversion < 2015012700) {

        // Define table forumng_read_posts to be created.
        $table = new xmldb_table('forumng_read_posts');

        // Adding fields to table forumng_read_posts.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('postid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('time', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table forumng_read_posts.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('userid', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));
        $table->add_key('postid', XMLDB_KEY_FOREIGN, array('postid'), 'forumng_posts', array('id'));

        // Adding indexes to table forumng_read_posts.
        $table->add_index('userid-postid', XMLDB_INDEX_UNIQUE, array('userid', 'postid'));
        $table->add_index('time', XMLDB_INDEX_NOTUNIQUE, array('time'));

        // Conditionally launch create table for forumng_read_posts.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        core_php_time_limit::raise(0);
        $oldtime = strtotime('730 days ago');

        $DB->delete_records_select('forumng_read', 'time < ?', array($oldtime));

        // Forumng savepoint reached.
        upgrade_mod_savepoint(true, 2015012700, 'forumng');
    }

    if ($oldversion < 2015060502) {

        // Define key discussionid (foreign) to be dropped form forumng_flags.
        $table = new xmldb_table('forumng_flags');
        $key = new xmldb_key('discussionid', XMLDB_KEY_FOREIGN, array('discussionid'), 'forumng_discussion', array('id'));

        // Launch drop key discussionid.
        $dbman->drop_key($table, $key);

        // Define key discussionid (foreign) to be added to forumng_flags.
        $table = new xmldb_table('forumng_flags');
        $key = new xmldb_key('discussionid', XMLDB_KEY_FOREIGN, array('discussionid'), 'forumng_discussions', array('id'));

        // Launch add key discussionid.
        $dbman->add_key($table, $key);

        // Forumng savepoint reached.
        upgrade_mod_savepoint(true, 2015060502, 'forumng');
    }

    if ($oldversion < 2016080100) {

        // Rename field tags on table forumng to enabletags (core now uses a tags field in modules).
        $table = new xmldb_table('forumng');
        $field = new xmldb_field('tags', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'canpostanon');

        // Launch rename field enabletags.
        $dbman->rename_field($table, $field, 'enabletags');

        // Forumng savepoint reached.
        upgrade_mod_savepoint(true, 2016080100, 'forumng');
    }

    if ($oldversion < 2017042100) {

        // First, replace any nulls with default.
        // There is historic data where it is null in some of our systems.
        $DB->set_field_select('forumng', 'introformat', 0, 'introformat IS NULL');

        // Changing nullability of field introformat on table forumng to not null.
        $table = new xmldb_table('forumng');
        $field = new xmldb_field('introformat', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0', 'intro');

        // Launch change of nullability for field introformat.
        $dbman->change_field_notnull($table, $field);

        // Forumng savepoint reached.
        upgrade_mod_savepoint(true, 2017042100, 'forumng');
    }

    if ($oldversion < 2017042101) {

        // Rename field intro on table forumng to introduction.
        $table = new xmldb_table('forumng');
        $field = new xmldb_field('intro', XMLDB_TYPE_TEXT, null, null, null, null, null, 'name');

        // Launch rename field intro.
        $dbman->rename_field($table, $field, 'introduction');

        // Forumng savepoint reached.
        upgrade_mod_savepoint(true, 2017042101, 'forumng');
    }

    if ($oldversion < 2017042102) {

        // Move corresponding files.
        $DB->execute("
                UPDATE {files}
                   SET filearea = 'introduction'
                 WHERE component = 'mod_forumng'
                   AND filearea = 'intro'");

        // Forumng savepoint reached.
        upgrade_mod_savepoint(true, 2017042102, 'forumng');
    }

    if ($oldversion < 2017042103) {

        // Rename field introformat on table forumng to introductionformat.
        $table = new xmldb_table('forumng');
        $field = new xmldb_field('introformat', XMLDB_TYPE_TEXT, null, null, null, null, null, 'intro');

        // Launch rename field introformat.
        $dbman->rename_field($table, $field, 'introductionformat');

        // Forumng savepoint reached.
        upgrade_mod_savepoint(true, 2017042103, 'forumng');
    }

    if ($oldversion < 2017042104) {

        // Define field intro to be added to forumng.
        $table = new xmldb_table('forumng');
        $field = new xmldb_field('intro', XMLDB_TYPE_TEXT, null, null, null, null, null, 'name');

        // Conditionally launch add field intro.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Forumng savepoint reached.
        upgrade_mod_savepoint(true, 2017042104, 'forumng');
    }

    if ($oldversion < 2017042105) {

        // Define field introformat to be added to forumng.
        $table = new xmldb_table('forumng');
        $field = new xmldb_field('introformat', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, 1, 'intro');

        // Conditionally launch add field introformat.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Forumng savepoint reached.
        upgrade_mod_savepoint(true, 2017042105, 'forumng');
    }

    if ($oldversion < 2017042106) {

        // Set a sensible default for the introformat. Moodle standard practice is
        // to declare the un-sensible default 0 in the DB schema.
        $DB->set_field('forumng', 'introformat', FORMAT_HTML);

        // Forumng savepoint reached.
        upgrade_mod_savepoint(true, 2017042106, 'forumng');
    }

    if ($oldversion < 2017042107) {

        // Add timemodified field for applying global search to forumng activity.
        $table = new xmldb_table('forumng');
        $field = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Conditionally launch add field timemodified.
        if (!$dbman->field_exists($table, $field)) {
            // Add the field but allowing nulls.
            $dbman->add_field($table, $field);
            // Set the field to 0 for everything.
            $DB->set_field('forumng', 'timemodified', '0');
            // Changing nullability of field timemodified to not null.
            $field = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', null,
                    XMLDB_NOTNULL, null, null);
            // Launch change of nullability for field themetype.
            $dbman->change_field_notnull($table, $field);
        }

        // Forumng savepoint reached.
        upgrade_mod_savepoint(true, 2017042107, 'forumng');
    }

    if ($oldversion < 2017071900) {

        // Add timemodified field for applying global search to forumng discussion.
        $table = new xmldb_table('forumng_discussions');
        $field = new xmldb_field('modified', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Conditionally launch add field modified.
        if (!$dbman->field_exists($table, $field)) {
            // Add the field but allowing nulls.
            $dbman->add_field($table, $field);
            // Set the field to 0 for everything.
            $DB->set_field('forumng_discussions', 'modified', '0');
            // Changing nullability of field modified to not null.
            $field = new xmldb_field('modified', XMLDB_TYPE_INTEGER, '10', null,
                    XMLDB_NOTNULL, null, null);
            // Launch change of nullability for field modified.
            $dbman->change_field_notnull($table, $field);
        }

        // Forumng savepoint reached.
        upgrade_mod_savepoint(true, 2017071900, 'forumng');
    }

    if ($oldversion < 2017101200) {

        // Add indexes on 'modified' field for the discussion and post. This makes the indexing
        // query complete more quickly.

        $table = new xmldb_table('forumng_discussions');
        $index = new xmldb_index('modified', XMLDB_INDEX_NOTUNIQUE, array('modified'));
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        $table = new xmldb_table('forumng_posts');
        $index = new xmldb_index('modified', XMLDB_INDEX_NOTUNIQUE, array('modified'));
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Forumng savepoint reached.
        upgrade_mod_savepoint(true, 2017101200, 'forumng');
    }

    return true;
}
