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

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__).'/mod_forumng.php');

/**
 * Manages a list (based on a database recordset, so not all stored in memory)
 * of posts which need to be emailed to users.
 *
 * The list only includes posts which are due to be mailed.
 * It does not include:
 * - Posts older than 48 hours (this is to avoid cron sending out a million old
 *   posts if it has never been run before), with exception of timed posts that
 *   have just become due
 * - Posts which have been deleted, or old versions of edited posts.
 * - Any posts which don't belong to a valid discussion, forum, and
 *   course-module
 * - Posts which are timed and not yet due - even if user has permission to
 *   see them (it is more useful for the timed posts to be mailed out at the
 *   'right time' even to these users, plus is easier)
 *
 * When used with shared forums, this will return multiple copies of each
 * message (one from each shared forum including the original one).
 *
 * @package mod_forumng
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_forumng_mail_list {
    /** Config flag used to prevent sending mails twice */
    const PENDING_MARK_MAILED = 'pending_mark_mailed';

    /** Special marker used to indicate there are no more forums to process */
    const FORUMID_NO_MORE_FORUMS = -1;
    /** Special marker used to indicate there is no forum restriction on this run */
    const FORUMID_NO_RESTRICTION = 0;

    /** @var int When querying forums, request at most this many */
    const MAX_FORUMS_PER_QUERY = 1000;

    private $rs;
    private $time;

    private $forum, $discussion;
    private $storedrecord;

    private $postcount;

    /** @var int Forum ID that is being processed, 0 if none */
    protected $forumid;

    /** @var float[] Array of times taken for the 3 operations (only valid at end) */
    protected $times = [];

    /** @var int[] Array cache of forum IDs that should be processed next, in order */
    protected static $nextforumcache = [];

    /**
     * Resets the static cache to ensure it will calculate the list of forums again.
     */
    public static function reset_static_cache() {
        self::$nextforumcache = [];
    }

    /**
     * Creates the mail queue and runs query to obtain list of posts that should
     * be mailed.
     *
     * This query includes only one forum, so the system needs to be used repeatedly in order
     * to process mail for all forums. If there are no forums requiring email it will return
     * true to is_finished().
     *
     * @param bool $output If true, outputs a couple of lines using mtrace to indicate progress
     */
    public function __construct($output) {
        global $DB, $CFG;
        $this->time = time();
        $this->forum = null;
        $this->discussion = null;
        $this->storedrecord = null;
        $this->postcount = 0;

        // Check if an earlier run got aborted. In that case we mark all
        // messages as mailed anyway because it's better to skip some than
        // to send out double-posts.
        if ($pending = get_config('forumng', $this->get_pending_flag_name())) {
            list ($time, $forumid) = explode(',', $pending);
            $this->mark_mailed($time, (int) $forumid);
        }

        // Get next forum id.
        if ($output) {
            mtrace('[Forum] ', '');
        }

        $this->forumid = $this->get_limited_forum_id();
        if ($this->forumid === self::FORUMID_NO_MORE_FORUMS) {
            return;
        }
        if ($this->forumid !== self::FORUMID_NO_RESTRICTION) {
            $DB->set_field('forumng', 'lastemailprocessing', $this->time, ['id' => $this->forumid]);
        }

        // Note that we are mid-run.
        set_config($this->get_pending_flag_name(), $this->time . ',' . $this->forumid, 'forumng');

        list ($wheresql, $whereparams) = $this->get_query_where($this->time, $this->forumid);
        $querychunk = $this->get_query_from() . $wheresql;
        if ($output) {
            mtrace('[Posts] ', '');
        }
        $before = microtime(true);
        $this->rs = $DB->get_recordset_sql($sql = "
SELECT
    ".mod_forumng_utils::select_mod_forumng_fields('f').",
    ".mod_forumng_utils::select_discussion_fields('fd').",
    ".mod_forumng_utils::select_post_fields('discussionpost').",
    ".mod_forumng_utils::select_post_fields('fp').",
    ".mod_forumng_utils::select_post_fields('reply').",
    ".mod_forumng_utils::select_course_module_fields('cm').",
    ".mod_forumng_utils::select_context_fields('x').",
    ".mod_forumng_utils::select_username_fields('u', true).",
    ".mod_forumng_utils::select_username_fields('eu').",
    ".mod_forumng_utils::select_username_fields('replyu').",
    ".mod_forumng_utils::select_username_fields('replyeu').",
    ".mod_forumng_utils::select_course_fields('c').",
    clonecm.id AS cloneid
$querychunk
ORDER BY
    clonecm.course, f.id, fd.id, fp.id", $whereparams);
        $this->record_time('Getting post list', $before);

        if (!empty($CFG->forumng_cronultradebug)) {
            $easyread = mod_forumng_utils::debug_query_for_reading($sql, $whereparams);
            mtrace("\n\n" . $easyread . "\n\n");
        }
    }

    /**
     * If we should limit this run-through to a single forum, then returns that ID. Otherwise
     * returns one of the FORUMID_xx constants.
     *
     * @return int FORUMID_xx constant or forum id
     * @throws dml_exception
     */
    protected function get_limited_forum_id() {
        $forumid = $this->get_next_forum_id_for_email_processing();
        if ($forumid) {
            return $forumid;
        } else {
            // No forums need processing.
            return self::FORUMID_NO_MORE_FORUMS;
        }
    }

    /**
     * Records a (cumulative) time.
     *
     * @param string $name Name for time
     * @param float $before Micro-time before this thing was done
     */
    protected function record_time($name, $before) {
        if (!array_key_exists($name, $this->times)) {
            $this->times[$name] = 0;
        }
        $this->times[$name] += microtime(true) - $before;
    }

    /**
     * Gets the next forum ID that should be processed.
     *
     * @return int ForumNG id or 0 if none requiring processing
     * @throws dml_exception
     */
    protected function get_next_forum_id_for_email_processing() {
        global $DB;

        // If no forum IDs are in the cache, then work out which forums to process next.
        if (!self::$nextforumcache) {
            $before = microtime(true);

            list($wheresql, $whereparams) = $this->get_query_where(
                    $this->time, self::FORUMID_NO_RESTRICTION);

            // Get next forums in batches of 1,000 because it can take a long time. (This query
            // takes around 600 seconds on live.)
            $nextforums = $DB->get_records_sql($sql = "
                    SELECT f.id
                      FROM {forumng} f
                      JOIN {course} c ON c.id = f.course
                      JOIN {course_modules} cm ON cm.course = f.course AND cm.instance = f.id
                           AND cm.module = (SELECT id FROM {modules} WHERE name = ?)
                      JOIN {context} x ON x.instanceid = cm.id AND x.contextlevel = ?
                     WHERE EXISTS(
                           SELECT 1
                             FROM {forumng_discussions} fd
                             JOIN {forumng_posts} fp ON fp.discussionid = fd.id
                             $wheresql
                                  AND fd.forumngid = f.id
                           )
                  ORDER BY f.lastemailprocessing ASC",
                    $params = array_merge(['forumng', CONTEXT_MODULE], $whereparams), 0,
                    self::MAX_FORUMS_PER_QUERY);

            foreach ($nextforums as $rec) {
                self::$nextforumcache[] = $rec->id;
            }

            // If we received less than 1,000 forums (so, all of them) then add a marker indicating
            // that there are no forums left (this is used so that we don't call the query a second
            // time at the end of the run).
            if (count(self::$nextforumcache) < self::MAX_FORUMS_PER_QUERY) {
                self::$nextforumcache[] = self::FORUMID_NO_MORE_FORUMS;
            }

            $this->record_time('Finding next forum', $before);
        }

        if (self::$nextforumcache) {
            if (self::$nextforumcache[0] === self::FORUMID_NO_MORE_FORUMS) {
                // There are no forums - don't use up the array and force another query.
                return 0;
            }
            return array_shift(self::$nextforumcache);
        } else {
            // There are still no forums!
            return 0;
        }
    }

    /**
     * Checks if there are any more emails to send.
     *
     * @return bool True if there are no forums currently requiring processing
     */
    public function is_finished() {
        return $this->forumid === self::FORUMID_NO_MORE_FORUMS;
    }

    /**
     * Gets times as an associative array.
     *
     * @return float[] List of times
     */
    public function get_times() {
        return $this->times;
    }

    /**
     * Obtains the next post in current forum.
     * @param mod_forumng_post &$post Output variable: Receives the post object
     * @param mod_forumng_post &$inreplyto Output variable: Receives the post this one was
     *   replying to
     * @return bool True if a post could be retrieved, false if there are
     *   no more posts in this forum (call next_forum)
     */
    public function next_post(&$post, &$inreplyto) {
        // Make sure we have a forum/discussion setup
        if ($this->forum == null || $this->discussion == null || $this->is_finished()) {
            throw new coding_exception("Cannot call next_post when not inside
                forum and discussion");
        }

        // Get record
        if ($this->storedrecord) {
            $record = $this->storedrecord;
            $this->storedrecord = null;
        } else {
            if (!$this->rs->valid()) {
                // End of the line. Mark everything as mailed
                $this->mark_mailed($this->time, $this->forumid);
                $this->rs->close();
                $this->rs = null;
                $this->discussion = null;
                return false;
            }
            $record = $this->rs->current();
            $this->rs->next();
        }

        // If record discussion is not the same as current discussion
        if ($record->fd_id != $this->discussion->get_id()
            || $record->cloneid != $this->forum->get_course_module_id()) {
            $this->storedrecord = $record;
            $this->discussion = null;
            return false;
        }

        // Get post details including the joined user info
        $postfields = mod_forumng_utils::extract_subobject($record, 'fp_');
        mod_forumng_utils::copy_subobject($postfields, $record, 'u_');
        mod_forumng_utils::copy_subobject($postfields, $record, 'eu_');
        $post = new mod_forumng_post($this->discussion, $postfields);
        if ($record->reply_id) {
            $postfields = mod_forumng_utils::extract_subobject($record, 'reply_');
            mod_forumng_utils::copy_subobject($postfields, $record, 'replyu_', 'u_');
            mod_forumng_utils::copy_subobject($postfields, $record, 'replyeu_', 'eu_');
            $inreplyto = new mod_forumng_post($this->discussion, $postfields);
        } else {
            $inreplyto = null;
        }

        $this->postcount++;
        return true;
    }

    /**
     * Obtains the next discussion in the list.
     * @param mod_forumng_discussion $discussion Discussion
     */
    public function next_discussion(&$discussion) {
        // Make sure we have a forum setup but no discussion
        if ($this->forum == null || $this->is_finished()) {
            throw new coding_exception("Cannot call next_discussion when not inside
                forum");
        }
        // Skip if required to get to new discussion
        while ($this->discussion!=null) {
            $this->next_post($post, $inreplyto);
        }

        // Get record
        if ($this->storedrecord) {
            $record = $this->storedrecord;
            $this->storedrecord = null;
        } else if (!$this->rs) {
            // Already used entire list and closed recordset
            $this->forum = null;
            return false;
        } else {
            if (!$this->rs->valid()) {
                // End of the line. Mark everything as mailed
                $this->mark_mailed($this->time, $this->forumid);
                $this->rs->close();
                $this->forum = null;
                $this->rs = null;
                return false;
            }
            $record = $this->rs->current();
            $this->rs->next();
        }

        // If record forums are not the same as current forum
        if ($record->cloneid != $this->forum->get_course_module_id()) {
            $this->storedrecord = $record;
            $this->forum = null;
            return false;
        }

        // Store record and check discussion
        $this->storedrecord = clone($record);
        $discussionfields = mod_forumng_utils::extract_subobject($record, 'fd_');
        $discussionfields->subject = $record->discussionpost_subject;
        $discussion = new mod_forumng_discussion($this->forum,
            $discussionfields, false, -1);
        $this->discussion = $discussion;
        return true;
    }

    /**
     * Obtains the next forum from the list.
     * @param mod_forumng &$forum Forum (out variable)
     * @param object &$cm Course-module object (out variable)
     * @param object &$context Context object (out variable)
     * @param object &$course Course object (out variable)
     */
    public function next_forum(&$forum, &$cm, &$context, &$course) {
        // Check not finished.
        if ($this->is_finished()) {
            throw new coding_exception('Cannot call next_forum when finished');
        }

        // Skip if required to get to new forum
        while ($this->forum!=null) {
            $this->next_discussion($discussion);
        }

        // Get record
        if ($this->storedrecord) {
            $record = $this->storedrecord;
            $this->storedrecord = null;
        } else if (!$this->rs) {
            // Already used entire list and closed recordset
            return false;
        } else {
            if (!$this->rs->valid()) {
                // End of the line. Mark everything as mailed
                $this->mark_mailed($this->time, $this->forumid);
                $this->rs->close();
                $this->rs = null;
                return false;
            }
            $record = $this->rs->current();
            $this->rs->next();
        }

        // Set data
        $this->storedrecord = clone($record);
        $cm = mod_forumng_utils::extract_subobject($record, 'cm_');
        $course = mod_forumng_utils::extract_subobject($record, 'c_');
        // Get full cm info if we can.
        $modinfo = get_fast_modinfo($course);
        if (array_key_exists($cm->id, $modinfo->get_cms())) {
            $cm = $modinfo->get_cm($cm->id);
        }
        context_helper::preload_from_record(
                mod_forumng_utils::extract_subobject($record, 'x_'));
        $context = context_module::instance($cm->id);
        $forum = new mod_forumng($course, $cm, $context,
            mod_forumng_utils::extract_subobject($record, 'f_'));
        if ($forum->is_shared()) {
            $forum->set_clone_reference($record->cloneid);
            $cm = $forum->get_course_module();
            $course = $forum->get_course();
            $context = $forum->get_context();
        }
        $this->forum = $forum;

        return true;
    }

    private function mark_mailed($time, $forumid) {
        list ($wheresql, $whereparams) = $this->get_query_where($time, $forumid);
        $querychunk = $this->get_query_from() . $wheresql;
        $before = microtime(true);

        mod_forumng_utils::update_with_subquery_grrr_mysql("
UPDATE
    {forumng_posts}
SET
    mailstate = " . $this->get_target_mail_state() . "
WHERE
    id %'IN'%", "SELECT fp.id $querychunk", $whereparams);
        $this->record_time('Marking posts processed', $before);

        unset_config($this->get_pending_flag_name(), 'forumng');
    }

    public function get_post_count_so_far() {
        return $this->postcount;
    }

    protected function get_pending_flag_name() {
        return self::PENDING_MARK_MAILED;
    }

    protected function get_target_mail_state() {
        return mod_forumng::MAILSTATE_MAILED;
    }

    /**
     * Safety net is to prevent the forum sending out very old emails if cron
     * is down for a long time, potentially causing a mail flood.
     * @param int $time Current/base time (seconds)
     * @return int Oldest time (seconds) of messages to process
     */
    protected function get_safety_net($time) {
        global $CFG;
        $hours = isset($CFG->forumng_donotmailafter)
                ? $CFG->forumng_donotmailafter : 48;
        return $time - $hours * 3600;
    }

    protected function get_query_from() {
        return "
FROM
    {forumng_posts} fp
    INNER JOIN {user} u ON fp.userid = u.id
    LEFT JOIN {user} eu ON fp.edituserid = eu.id
    LEFT JOIN {forumng_posts} reply ON fp.parentpostid = reply.id
    LEFT JOIN {user} replyu ON reply.userid = replyu.id
    LEFT JOIN {user} replyeu ON reply.edituserid = replyeu.id
    INNER JOIN {forumng_discussions} fd ON fp.discussionid = fd.id
    INNER JOIN {forumng_posts} discussionpost ON fd.postid = discussionpost.id
    INNER JOIN {forumng} f ON fd.forumngid = f.id
    INNER JOIN {course_modules} cm ON f.id = cm.instance
        AND cm.module = (SELECT id FROM {modules} WHERE name='forumng')
    INNER JOIN {context} x ON x.instanceid = cm.id
    INNER JOIN {course} c ON c.id = f.course
    INNER JOIN {forumng} clonef
        ON (clonef.originalcmid = cm.id OR (f.originalcmid IS NULL AND clonef.id = f.id))
    INNER JOIN {course_modules} clonecm ON clonef.id = clonecm.instance
        AND clonecm.module = (SELECT id FROM {modules} WHERE name='forumng')";
    }

    protected function get_query_where($time, $forumid) {
        global $CFG;

        // We usually only mail out posts after a delay of maxeditingtime.
        $mailtime = $time - $CFG->forumng_emailafter;

        // In case cron has not run for a while.
        $safetynet = $this->get_safety_net($time);

        $forumidsql = '';
        if ($forumid != self::FORUMID_NO_RESTRICTION) {
            $forumidsql = 'AND f.id = ?';
        }

        $sql = "
WHERE
    -- Skip future posts (this is more relevant when using the set state
    -- version of the query)...
    fp.created < ?

    -- Post must not have been mailed yet, also wait for editing delay if
    -- not set to mailnow
    AND ((fp.mailstate = " . mod_forumng::MAILSTATE_NOT_MAILED . "
        AND fp.created < ?)
        OR fp.mailstate = " . mod_forumng::MAILSTATE_NOW_NOT_MAILED . ")

    -- Don't mail out really old posts (unless they were previously hidden)
    AND (fp.created > ? OR fd.timestart > ?)

    -- Group mode posts on non-group forums are not shown in the UI so let's
    -- not mail them either
    AND (fd.groupid IS NULL OR (c.groupmodeforce = 0 AND cm.groupmode <> 0) OR
        (c.groupmodeforce <> 0 AND c.groupmode <> 0))

    -- Discussion must meet time requirements
    AND fd.timestart < ?
    AND (fd.timeend = 0 OR fd.timeend > ?)

    -- Post and discussion must not have been deleted and we're only looking
    -- at original posts not edited old ones
    AND fp.deleted = 0
    AND fd.deleted = 0
    AND fp.oldversion = 0

    $forumidsql

    -- Context limitation
    AND x.contextlevel = 70";
        $params = [$time, $mailtime, $safetynet, $safetynet, $time, $time];

        if ($forumid != self::FORUMID_NO_RESTRICTION) {
            $params[] = $forumid;
        }

        return array($sql, $params);
    }
}
