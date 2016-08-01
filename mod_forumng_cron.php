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

require_once(dirname(__FILE__).'/mod_forumng.php');
require_once(dirname(__FILE__).'/mod_forumng_mail_list.php');
require_once(dirname(__FILE__).'/mod_forumng_digest_list.php');

/**
 * Utility class handling all cron tasks.
 * @package mod
 * @subpackage forumng
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_forumng_cron {
    const DEBUG_VIEW_EMAILS = false;

    /** Divider used between sections of email */
    const EMAIL_DIVIDER =
      "---------------------------------------------------------------------\n";


    public static function delete_old_posts() {
        global $CFG, $DB;

        // Check if deletion is turned off
        if (empty($CFG->forumng_permanentdeletion)) {
            return;
        }

        mtrace('Beginning forum deleted/edit message cleanup...');

        // Work out how long ago things have to have been 'deleted' before we
        // permanently delete them
        $deletebefore = time() - $CFG->forumng_permanentdeletion;

        // Handle all posts which were deleted (that long ago) or which are in
        // discussions which were deleted (that long ago)
        $mainparams = array();
        $mainquery = "
FROM
    {forumng_posts} fp
    INNER JOIN {forumng_discussions} fd ON fd.id = fp.discussionid
    INNER JOIN {forumng} f ON fd.forumngid = f.id
    INNER JOIN {course_modules} cm ON cm.instance = f.id AND cm.module =
        (SELECT id FROM {modules} WHERE name='forumng')
    INNER JOIN {context} x on x.instanceid=cm.id AND x.contextlevel=?
WHERE
    (fp.deleted<>0 AND fp.deleted<? AND
    NOT EXISTS (SELECT id from {forumng_posts} where parentpostid = fp.id and deleted = 0))
    OR (fp.oldversion<>0 AND fp.modified<?)
    OR (fd.deleted<>0 AND fd.deleted<?)";
        $idquery = "SELECT fp.id $mainquery ";
        $mainparams[] = CONTEXT_MODULE;
        $mainparams[] = $deletebefore;
        $mainparams[] = $deletebefore;
        $mainparams[] = $deletebefore;

        $before = microtime(true);
        mtrace('Message search: ', '');
        $count = $DB->count_records_sql("SELECT COUNT(1) $mainquery", $mainparams);
        mtrace(round(microtime(true)-$before, 1) .'s');
        if ($count == 0) {
            mtrace("No old deleted / edited messages to clean up.");
        } else {
            mtrace("Permanently deleting $count old deleted / edited messages.");
        }

        if ($count) {
            $before = microtime(true);
            mtrace('Database post deletion: ', '');
            $transaction = $DB->start_delegated_transaction();

            // Delete all ratings
            $DB->execute("DELETE FROM {forumng_ratings} WHERE postid IN ($idquery)",
                    $mainparams);

            // Delete all read post records.
            $DB->execute("DELETE FROM {forumng_read_posts} WHERE postid IN ($idquery)", $mainparams);

            // Find all messages...
            $rs = $DB->get_recordset_sql("
SELECT
    fp.id AS postid, x.id AS contextid, fp.attachments
$mainquery", $mainparams);

            // Delete all attachments - this is very very slow (at least 1 query
            // per post being deleted), it could be done faster in the database
            // but I thought it might be safer to use the API
            $fs = get_file_storage();
            foreach ($rs as $attachmentrecord) {
                // Delete both attachments & message attachments
                if ($attachmentrecord->attachments) {
                    // Only if marked as existing
                    $fs->delete_area_files($attachmentrecord->contextid,
                            'mod_forumng', 'attachment', $attachmentrecord->postid);
                }
                // Always (there is no marker for this one)
                $fs->delete_area_files($attachmentrecord->contextid,
                        'mod_forumng', 'message', $attachmentrecord->postid);
            }
            $rs->close();

            // Delete all posts
            mod_forumng_utils::update_with_subquery_grrr_mysql(
                    "DELETE FROM {forumng_posts} WHERE id %'IN'%",
                    $idquery, $mainparams);

            // Now delete all discussions
            $DB->execute(
                    "DELETE FROM {forumng_discussions} WHERE deleted<>0 AND deleted<?",
                    array($deletebefore));

            $transaction->allow_commit();
            mtrace(round(microtime(true)-$before, 1) .'s');
        }
    }

    /**
     * @param string $text Text to output, or none if you only want to check
     *   the value
     * @param string $lf Set to '' if you don't want a linefeed
     * @return bool True if debug output is enabled
     */
    public static function debug($text = '', $lf = "\n") {
        static $checked = false, $debug;
        if (!$checked) {
            $debug = debugging('', DEBUG_DEVELOPER);
        }
        if (!$debug) {
            return false;
        }
        if ($text) {
            mtrace($text, $lf);
        }
        return true;
    }

    public static function email_normal() {
        global $USER, $CFG, $PERF;

        $exceptioncount = 0;

        // Obtain information about all mails that are due for sending
        mtrace('Email processing:');
        $before = microtime(true);
        if (!empty($PERF->dbqueries)) {
            $beforequeries = $PERF->dbqueries;
        }
        mtrace('Initial query: ', '');
        $list = new mod_forumng_mail_list(true);
        mtrace(round(microtime(true)-$before, 1) .'s');

        // Cumulative time spent actually sending emails
        $mailtime = 0;
        $totalemailcount = 0;

        // Forum loop
        while ($list->next_forum($forum, $cm, $context, $course)) {
            self::debug("DEBUG: Forum " . $forum->get_name() .
                    " on course {$course->shortname} " .
                    "(cmid {$cm->id} contextid {$context->id})");

            // We had problems with cron running out of memory when it sends
            // a lot of emails. On the basis that the PHP garbage collector
            // might have 'issues', perhaps it may help to call it manually.
            gc_collect_cycles();

            // Set up course details
            // Note: This code is a bit sketchy; borrowed from cron_setup_user
            $PAGE = new moodle_page();
            $PAGE->set_course($course);

            // Count posts and emails just for logging
            $postcount = 0;
            $emailcount = 0;

            // Get subscribers to forum
            try {
                $subscribers = $forum->get_subscribers();
                self::debug("DEBUG: Subscribers before filter " . count($subscribers), '');
                self::email_filter_subscribers($course, $cm, $forum, $subscribers, false);
                self::debug(", after " . count($subscribers));
                if (count($subscribers)==0) {
                    continue;
                }
            } catch (coding_exception $e) {
                // If an error occurs while getting subscribers, continue
                // to next forum
                mtrace(' Exception while getting subscribers for forum ' .
                        $forum->get_id());
                mtrace($e->__toString());
                continue;
            }

            while ($list->next_discussion($discussion)) {
                self::debug("DEBUG: Discussion " . $discussion->get_subject() .
                        ' (' . $discussion->get_id() . ')');

                // Evaluate list of users based on this discussion (which holds
                // group info). Organise list by language, timezone and email
                // type.
                $langusers = array();
                foreach ($subscribers as $subscriber) {
                    // Conditions for each subscriber to get this discussion
                    if (self::subscriber_receives_discussion(
                        $forum, $discussion, $subscriber)) {
                        $oldlang = $USER->lang;
                        $USER->lang = $subscriber->lang;
                        $lang = current_language();
                        $USER->lang = $oldlang;
                        $langusers[$lang][$subscriber->timezone]
                            [$subscriber->emailtype][$subscriber->id] =
                            $subscriber;
                    }
                }
                if (self::debug()) {
                    $debugcount = 0;
                    foreach ($langusers as $lang => $tzusers) {
                        foreach ($tzusers as $timezone => $typeusers) {
                            foreach ($typeusers as $emailtype => $users) {
                                mtrace("DEBUG: Subscribers for lang [$lang] " .
                                        "tz [$timezone] type [$emailtype]: " .
                                        count($users));
                                $debugcount += count($users);
                            }
                        }
                    }
                    mtrace("DEBUG: Total discussion subscribers: $debugcount");
                }

                while ($list->next_post($post, $inreplyto)) {
                    if (self::debug()) {
                        mtrace("DEBUG: Post " . $post->get_id(), '');
                        $debugcount = $emailcount;
                    }
                    try {
                        $from = $post->get_user();

                        // These loops are intended so that we generate identical
                        // emails once only, and can then send them in batches
                        foreach ($langusers as $lang => $tzusers) {
                            foreach ($tzusers as $timezone => $typeusers) {
                                foreach ($typeusers as $emailtype => $users) {

                                    // We get both plaintext and html versions.
                                    // The html version will be blank if set to
                                    // plain text mode.
                                    $post->build_email($inreplyto, $subject,
                                        $plaintext, $html, $emailtype & 1,
                                        $emailtype & 2, $emailtype & 4, $lang,
                                        $timezone);

                                    if ($post->get_asmoderator() == mod_forumng::ASMODERATOR_ANON) {
                                        $from->maildisplay = false;
                                        $from->firstname = get_string('moderator', 'forumng');
                                        $from->lastname = '';
                                    }

                                    $beforemail = microtime(true);
                                    if ($CFG->forumng_usebcc) {
                                        // Use BCC to send all emails at once
                                        $emailcount += self::email_send_bcc(
                                            $users, $from, $subject,
                                            $html, $plaintext,
                                            "post " . $post->get_id(),
                                            $emailtype & 1, $emailtype & 4);
                                    } else {
                                        // Loop through subscribers, sending mail to
                                        // each one
                                        foreach ($users as $mailto) {
                                            self::email_send($mailto, $from, $subject,
                                                $plaintext, $html);
                                            $emailcount++;
                                        }
                                    }
                                    $mailtime += microtime(true) - $beforemail;
                                }
                            }
                        }
                        // Reset exception count; while some posts are
                        // successful, we'll keep trying to send them out
                        $exceptioncount = 0;
                    } catch (Exception $e) {
                        mtrace(' Exception while sending post ' . $post->get_id());
                        mtrace($e->__toString());
                        $exceptioncount++;

                        if ($exceptioncount > 100) {
                            throw new moodle_exception('error_system', 'forumng', '',
                                'Too many post exceptions in a row, aborting');
                        }
                    }

                    $postcount++;
                    if (self::debug()) {
                        mtrace(", sent " . ($emailcount - $debugcount) .
                                " emails");
                    }
                }
            }

            // Trace and log information
            $counts = "$postcount posts ($emailcount emails) to " .
                count($subscribers) . " subscribers";
            mtrace("Forum ".$forum->get_name() .
                ": sent $counts");
            $params = array('other' => array('type' => 'sub', 'count' => $counts),
                'context' => $forum->get_context());
            $event = \mod_forumng\event\mail_sent::create($params);
            $event->trigger();
            $totalemailcount += $emailcount;
        }
        $queryinfo = '';
        if (!empty($PERF->dbqueries)) {
            $queryinfo = ', ' . ($PERF->dbqueries - $beforequeries) .
              ' queries';
        }
        $totalpostcount = $list->get_post_count_so_far();
        $totaltime = microtime(true)-$before;
        mtrace("Email processing ($totalpostcount new posts, $totalemailcount new emails) " .
                "complete, total: " . round($totaltime, 1) . 's (mail sending ' .
                round($mailtime, 1) . 's = ' . round(100.0 * $mailtime / $totaltime, 1) . '%)' .
                $queryinfo);
    }

    /**
     * Shared logic that works out whether a particular subscriber receives a
     * discussion or not.
     * @param mod_forumng $forum Forum
     * @param mod_forumng_discussion $discussion Discussion
     * @param object $subscriber Subscriber
     */
    private static function subscriber_receives_discussion($forum, $discussion, $subscriber) {
        // Did they subscribe specifically to this discussion?
        $explicitsubscribed = array_key_exists(
                $discussion->get_id(), $subscriber->discussionids);

        // Did they subscribe to the group this discussion belongs to
        $explicitsubscribedtogroup = in_array($discussion->get_group_id(),
                $subscriber->groupids);

        $groupid = $discussion->get_group_id();
        $visiblegroups = $forum->get_group_mode() == VISIBLEGROUPS;

        // Conditions for each subscriber to get this discussion
        $result =
                // 1. Subscribed to whole forum, or specifically to
                // this discussion, or specifically to this group
                ($subscriber->wholeforum || $explicitsubscribed ||
                    $explicitsubscribedtogroup) &&

                // 2. The discussion has no group id, or they
                // belong to the group, or they can access all groups,
                // or it's visible-groups
                (!$groupid || $subscriber->accessallgroups || $visiblegroups ||
                    array_key_exists($groupid, $subscriber->groups)) &&

                // 3. Forum type allows user to view discussion
                $forum->get_type()->can_view_discussion(
                    $discussion, $subscriber->id);

        return $result;
    }

    public static function email_digest() {
        global $CFG, $PERF;

        // Build current digest.
        mtrace("Beginning forum digest processing...");
        if (!empty($PERF->dbqueries)) {
            $beforequeries = $PERF->dbqueries;
        }

        $before = microtime(true);
        mtrace('Initial query: ', '');
        $list = new mod_forumng_digest_list(true);
        mtrace(round(microtime(true)-$before, 1) .'s');

        $userdigests = array();
        $oldcourse = null;

        // Forum loop.
        while ($list->next_forum($forum, $cm, $context, $course)) {
            self::debug("DEBUG: Forum " . $forum->get_name() .
                    " on course {$course->shortname} " .
                    "(cmid {$cm->id} contextid {$context->id})");

            if (!$oldcourse || ($course->id != $oldcourse->id)) {
                // Finish off and clear users.
                if ($oldcourse) {
                    self::digest_finish_course($oldcourse, $userdigests);
                }
                // Set up new course details.
                // Note: This code is a bit sketchy; borrowed from cron_setup_user.
                $PAGE = new moodle_page();
                $PAGE->set_course($course);
                $oldcourse = clone($course);
            }

            // Count posts just for logging.
            $postcount = 0;

            // Get subscribers to forum.
            $subscribers = $forum->get_subscribers();
            self::debug("DEBUG: Subscribers before filter " . count($subscribers), '');
            self::email_filter_subscribers($course, $cm, $forum, $subscribers, true);
            self::debug(", after " . count($subscribers));
            if (count($subscribers)==0) {
                continue;
            }

            while ($list->next_discussion($discussion)) {
                self::debug("DEBUG: Discussion " . $discussion->get_subject() .
                        ' (' . $discussion->get_id() . ')');

                // Evaluate list of users based on this discussion (which holds
                // group info). Organise list by language, timezone and email
                // type.
                $discussionusers = array();
                foreach ($subscribers as $subscriber) {
                    if (self::subscriber_receives_discussion(
                        $forum, $discussion, $subscriber)) {
                        $discussionusers[$subscriber->id] = $subscriber;
                    }
                }

                while ($list->next_post($post, $inreplyto)) {
                    // Loop through all digest users.
                    foreach ($discussionusers as $user) {
                        // Add to digest. (This will set up the user's
                        // digest if they don't already have one).
                        self::digest_add_post_for_user($user, $userdigests,
                            $post, $inreplyto, $discussion, $forum, $cm,
                            $course, $context);
                    }

                    $postcount++;
                }
            }
        }

        if ($oldcourse) {
            self::digest_finish_course($oldcourse, $userdigests);
        }

        $queryinfo = '';
        if (!empty($PERF->dbqueries)) {
            $queryinfo = ', ' . ($PERF->dbqueries - $beforequeries) .
              ' queries';
        }
        $totalpostcount = $list->get_post_count_so_far();
        mtrace("Digest processing ($totalpostcount new digest posts) complete, total: "
            . round(microtime(true)-$before, 1) . 's' . $queryinfo);
    }

    /**
     * Called at end of processing a single forum. Updates the digest records
     * to add footer information for each user who had messages from the forum.
     * @param array $userdigests Array of userid => digest info
     * @param mod_forumng $forum
     * @param object $cm
     */
    private static function digest_end_forum(&$userdigests, $forum, $cm) {
        global $CFG;

        $canunsubscribe = mod_forumng::SUBSCRIPTION_FORCED
            != $forum->get_effective_subscription_option();
        foreach ($userdigests as $digest) {
            // If there was a message from this forum...
            if ($digest->forumngid === $forum->get_id()) {
                $digest->html .= "\n<div class='forumng-email-unsubscribe'><small>";
                $digest->text .= "\n";
                if ($canunsubscribe) {
                    $digest->html .=
                        "<a href='$CFG->wwwroot/mod/forum/subscribe.php?" .
                        $forum->get_link_params(mod_forumng::PARAM_HTML) . "'>" .
                        get_string("unsubscribe", "forumng") . "</a>";
                    $digest->text .= get_string("unsubscribe", "forumng") .
                        ": $CFG->wwwroot/mod/forum/subscribe.php" .
                        $forum->get_link_params(mod_forumng::PARAM_PLAIN);
                } else {
                    $digest->html .= get_string("everyoneissubscribed", "forumng");
                    $digest->text .= get_string("everyoneissubscribed", "forumng");
                }
                $digest->html .= '</small></div>';
                $digest->text .= "\n\n";
            }
        }
    }

    /**
     * Updates the in-memory digest records to add a new post to the given
     * user's digests.
     * @param object $user User object (must include special ->emailtype, etc)
     * @param array $userdigests Array of user id => digest information object
     * @param mod_forumng_post $post Post object
     * @param mod_forumng_post $inreplyto Parent post
     * @param mod_forumng_discussion $discussion Discus
     * @param mod_forumng $forum
     * @param object $cm
     * @param object $course
     * @param object $context
     */
    private static function digest_add_post_for_user(&$user, &$userdigests,
        &$post, &$inreplyto, &$discussion, &$forum, &$cm, &$course, &$context) {
        global $CFG;

        // Set up digest for user if required
        if (!array_key_exists($user->id, $userdigests)) {
            $userdigests[$user->id] = new StdClass;
            $userdigests[$user->id]->discussionid = -1; // So we do header next
            $userdigests[$user->id]->user = $user;
            $userdigests[$user->id]->forumngid = -1;

            // Get header text
            $headerdata = new object();
            $headerdata->sitename = format_string($course->fullname, true);
            $headerdata->userprefs = $CFG->wwwroot . '/user/edit.php?id=' .
                $user->id . '&amp;course=' . $course->id;

            $userdigests[$user->id]->text = get_string('digestmailheader',
                'forumng', $headerdata) . "\n\n";

            // Get header HTML
            $html = "<body id='forumng-email'>\n";
            $headerdata->userprefs = '<a target="_blank" href="' .
                $headerdata->userprefs . '">' .
                get_string('digestmailprefs', 'forumng') . '</a>';
            $html .= '<div class="forumng-emailheader"><p>' .
                get_string('digestmailheader', 'forumng', $headerdata).
                '</p></div><hr size="1" noshade="noshade" />';
            $userdigests[$user->id]->html = $html;

            // Get email subject
            $userdigests[$user->id]->subject = get_string('digestmailsubject',
                'forumng', format_string($course->shortname, true));
        }

        // New forum?
        if ($userdigests[$user->id]->forumngid != $forum->get_id()) {
            $userdigests[$user->id]->forumngid = $forum->get_id();
        }

        // Is this a new discussion?
        if ($userdigests[$user->id]->discussionid != $discussion->get_id()) {
            $strforums = get_string('forums', 'forumng');

            // Per-discussion header (text mode)
            $text = "\n \n";
            $text .= '=====================================================================';
            $text .= "\n \n";
            $text .= "$course->shortname -> $strforums -> " .
                    format_string($forum->get_name(), true);
            if ($discussion->get_subject(false) !== $forum->get_name()) {
                $text  .= " -> " . format_string($discussion->get_subject(false), true);
            }
            $text .= "\n";

            // HTML mode
            $html = '<hr size="1" noshade="noshade" />';
            $html .= "<div class='forumng-breadcrumbs'>" .
                    "<a target='_blank' href='$CFG->wwwroot/course/view.php?" .
                    "id=$course->id'>$course->shortname</a> -> " .
                    "<a target='_blank' href='$CFG->wwwroot/mod/forumng/index.php?" .
                    "id=$course->id'>$strforums</a> -> " .
                    "<a target='_blank' href='$CFG->wwwroot/mod/forumng/view.php?" .
                    $forum->get_link_params(mod_forumng::PARAM_HTML) . "'>" .
                    format_string($forum->get_name(), true)."</a>";
            if ($discussion->get_subject(false) !== $forum->get_name()) {
                $html .= " -> <a target='_blank' href='$CFG->wwwroot/mod/forumng/discuss.php?" .
                        $discussion->get_link_params(mod_forumng::PARAM_HTML) . "'>" .
                        format_string($discussion->get_subject(false), true) . "</a>";
            }
            $html .= '</div>';

            $userdigests[$user->id]->text .= $text;
            $userdigests[$user->id]->html .= $html;
            $userdigests[$user->id]->discussionid = $discussion->get_id();
        }

        // Get both plaintext and html versions (and subject).
        // The html version will be blank if set to
        // plain text mode.
        $post->build_email($inreplyto, $subject,
            $text, $html, $user->emailtype & 1,
            $user->emailtype & 2, $user->emailtype & 4,
            $user->lang, $user->timezone, true);
        $userdigests[$user->id]->text .= $text;
        $userdigests[$user->id]->html .= $html;
    }

    /**
     * Actually sends out digest emails to all users (who got something to say)
     * because we are moving on to process the next course.
     */
    private static function digest_finish_course(&$course, &$userdigests) {
        global $CFG;

        // Loop around all digests and send them out
        foreach ($userdigests as $digest) {
            self::email_send($digest->user, $CFG->noreplyaddress,
                $digest->subject, $digest->text, $digest->html);
        }

        // Trace and log information
        $counts = count($userdigests) . ' digests';
        mtrace("Course ".$course->shortname . ": sent $counts");
        $params = array('other' => array('type' => 'digest', 'count' => $counts),
                'context' => context_course::instance($course->id));
        $event = \mod_forumng\event\mail_sent::create($params);
        $event->trigger();

        // Clear users ready for new course
        $userdigests = array();
    }

    /**
     * Filters a list of subscribers to remove those who can't receive email
     * etc., and adds extra information to each one.
     * @param object $course Course
     * @param object $cm Course-module
     * @param array $subscribers List of subscribers
     * @param bool $digest True if we want digest recipients (only) instead of
     *   non-digest recipients
     */
    private static function email_filter_subscribers($course, $cm, $forum,
            &$subscribers, $digest=false) {
        foreach ($subscribers as $subscriber) {
            // Exclude anyone who has mail disabled for various reasons,
            // or is/is not on digest.
            // Note that the mail checks here duplicate checks in
            // email_to_user; this probably makes it slightly more
            // efficient, but is really there because if bcc is enabled
            // we don't go through email_to_user at all.
            if ($subscriber->emailstop || $subscriber->deleted ||
                $subscriber->auth=='nologin' ||
                (($digest && !$subscriber->maildigest) ||
                (!$digest && $subscriber->maildigest)) ||
                over_bounce_threshold($subscriber)) {
                unset($subscribers[$subscriber->id]);
                continue;
            }

            // Add information about groups for this user.
            $subscriber->groups = groups_get_all_groups(
                $course->id, $subscriber->id, $cm->groupingid, 'g.id');
            $subscriber->groups = $subscriber->groups
                ? $subscriber->groups : array();

            // Track whether users are allowed to view full names
            $subscriber->viewfullnames = has_capability(
                'moodle/site:viewfullnames', $forum->get_context(),
                $subscriber->id);
            $subscriber->canreply = has_capability(
                'mod/forumng:replypost', $forum->get_context(),
                $subscriber->id);
            // Note I don't think the Reply capability is sufficient
            // check here, could be restricted by forum type. Maybe it
            // will do for now.

            // Categorise subscriber by the type of email. There are
            // eight types as determined by the two flags above, plus
            // HTML vs standard email.
            $subscriber->emailtype =
                ($subscriber->viewfullnames ? 4 : 0) +
                ($subscriber->canreply ? 2 : 0) +
                ($subscriber->mailformat ? 1 : 0);
        }
    }

    /**
     * Sends an email. (Wrapper around email_to_user.)
     * @param object $to User who receives email
     * @param mixed $from User or string who sent email
     * @param string $subject Subject line
     * @param string $text Text of email
     * @param string $html HTML of email or '' if plaintext only
     */
    private static function email_send($to, $from, $subject, $text, $html) {
        global $CFG;
        if (self::DEBUG_VIEW_EMAILS) {
            print "<div style='margin:4px; border:1px solid blue; padding:4px;'>";
            print "<h3>Email sent</h3>";
            print "<ul><li>From: <strong>" . (is_object($from) ? $from->email : $from) .
                "</strong></li>";
            print "<li>To: <strong>$to->email</strong></li>";
            print "<li>Subject: <strong>" . htmlspecialchars($subject) .
                "</strong></li></ul>";
            print $html;
            print "<pre style='border-top: 1px solid blue; padding-top: 4px; margin-top:4px;'>";
            print htmlspecialchars($text);
            print "</pre></div>";
            return;
        }
        email_to_user($to, $from, $subject, $text, $html, '', '',
            $CFG->forumng_replytouser);
    }

    /**
     * Sends an email to lots of people using BCC.
     * @param array $targets List of target user objects (email, name fields
     *   required)
     * @param mixed $from User or string who sent email
     * @param string $subject Subject of email
     * @param string $html HTML version of email (blank if none)
     * @param string $text Plain text version of email
     * @param string $showerrortext If set, mtraces errors and includes this
     *   extra string about where the error was.
     * @param bool $ishtml If true, email is in HTML format
     * @param bool $viewfullnames If true, these recipients have access to
     *   see the full name
     * @return int Number of emails sent
     */
    private static function email_send_bcc($targets, $from, $subject, $html, $text,
        $showerrortext, $ishtml, $viewfullnames) {
        if (self::DEBUG_VIEW_EMAILS) {
            print "<div style='border:1px solid blue; padding:4px;'>";
            print "<h3>Bulk email sent</h3>";
            print "<ul><li>To: ";
            $first = true;
            foreach ($targets as $target) {
                if ($first) {
                    $first = false;
                } else {
                    print ', ';
                }
                print "<strong>$target->email</strong>";
            }
            print "</li><li>Subject: <strong>" . htmlspecialchars($subject) .
                "</strong></li>";
            print $html;
            print "<pre style='border-top: 1px solid blue; padding-top: 4px;'>";
            print htmlspecialchars($text);
            print "</pre></div>";
            return;
        }

        global $CFG;
        $emailcount = 0;

        // Trim subject length (not sure why but
        // email_to_user does); note that I did it more
        // aggressively due to use of textlib.
        $mail->Subject = core_text::substr($subject, 0, 200);

        // Loop through in batches of specified size
        $copy = array();
        foreach ($targets as $key => $target) {
            $copy[$key] = $target;
        }
        while (count($copy)>0) {
            $batch = array_splice($copy, 0,
                $CFG->forumng_usebcc);

            // Prepare email
            $mail = get_mailer();

            // From support user
            static $supportuser;
            if (!$supportuser) {
                $supportuser = core_user::get_support_user();
            }
            $mail->Sender = $supportuser->email;

            // Set the From details similar to email_to_user
            if ($CFG->forumng_replytouser &&
                $from->maildisplay) {
                $mail->From     = $from->email;
                $mail->FromName = fullname($from, $viewfullnames);
            } else {
                $mail->From     = $CFG->noreplyaddress;
                $mail->FromName = fullname($from, $viewfullnames);
            }

            $mail->ToName = 'Test to name';

            $mail->Subject = $subject;

            if ($ishtml) {
                $mail->IsHTML(true);
                $mail->Encoding = 'quoted-printable';
                $mail->Body    =  $html;
                $mail->AltBody =  "\n$text\n";
            } else {
                $mail->IsHTML(false);
                $mail->Body =  "\n$text\n";
            }

            foreach ($batch as $user) {
                $mail->AddBCC($user->email);
            }

            $emailcount++;
            if (!$mail->Send()) {
                $users = '';
                foreach ($batch as $user) {
                    if ($users) {
                        $users.=', ';
                    }
                    $users .= $user->id;
                }
                if ($showerrortext) {
                    mtrace('Error sending email "'. $subject .
                        '": "'. $mail->ErrorInfo . '" (' . $showerrortext .
                        '). Users affected: ' . $users);
                }
            } else {
                // Mail send successful; log all users
                foreach ($batch as $user) {
                    // Note this log entry is in the same format as the
                    // main mail function
                    $params = array('other' => array('username' => $user->username, 'subject' => $subject),
                            'context' => context_system::instance(), 'relateduserid' => $user->id);
                    $event = \mod_forumng\event\mail_sent::create($params);
                    $event->trigger();
                }
            }
        }
        return $emailcount;
    }

    /**
     * Checks whether current hour is between two specified hours.
     * @param int $starthour Hour to start running at (inclusive) e.g. 23
     * @param int $stophour Hour to stop running at (exclusive) e.g. 4
     * @return boolean True if it should currently run
     */
    public static function is_between_hours($starthour, $stophour) {
        $hour = (int)date('G');
        if ($starthour < $stophour) {
            // Hours are in numerical order e.g. 0-6
            return ($hour >= $starthour && $hour < $stophour);
        } else {
            // Hours are not in numerical order e.g. 23-4
            return ($hour >= $starthour || $hour < $stophour);
        }
    }

    /**
     * Do housekeeping only runs once per day.
     */
    public static function daily_housekeeping() {
        global $CFG;

        // Really-delete old posts if that option is enabled.
        self::delete_old_posts();

        // Either move or delete old discussions.
        self::archive_old_discussions();

        self::delete_old_read();
    }

    /**
     * Delete read information that is about 2 years old
     * (Not counting leap years etc)
     */
    public static function delete_old_read() {
        global $CFG, $DB;
        $oldtime = strtotime('730 days ago');

        $DB->delete_records_select('forumng_read_posts', 'time < ?', array($oldtime));
        $DB->delete_records_select('forumng_read', 'time < ?', array($oldtime));
    }

    /**
     * Either delete or archive old discussions based on the forum setting
     */
    public static function archive_old_discussions() {
        global $CFG, $DB;
        $now = time();
        $housekeepingquery = "
FROM
    {forumng_discussions} fd
    INNER JOIN {forumng_posts} fp ON fd.lastpostid = fp.id
    INNER JOIN {forumng} f ON fd.forumngid = f.id
WHERE
    f.removeafter<>0 AND fd.sticky<>1 AND fp.modified<? - f.removeafter
";
        $housekeepingparams = array($now);
        $count = $DB->count_records_sql("SELECT COUNT(1) $housekeepingquery", $housekeepingparams);
        if ($count) {
            if (!PHPUNIT_TEST) {
                mtrace("\nBeginning processing $count discussion archiving/deleting requests");
            }
            $housekeepingrs = $DB->get_recordset_sql("
SELECT
    fd.id AS discussionid, f.id AS forumngid, f.removeafter, f.removeto
$housekeepingquery
ORDER BY f.removeto", $housekeepingparams);

            $targetforum = null;
            $targetcourseid = null;
            $cronlog = '';
            $discussionmovecount = 0;
            $discussiondeletecount = 0;
            $discussionautolocked = 0;
            foreach ($housekeepingrs as $rec) {
                $discussion = mod_forumng_discussion::get_from_id($rec->discussionid,
                        mod_forumng::CLONE_DIRECT);
                if ($rec->removeto) {
                    if ($rec->removeto == -1) {
                        // Locked record or discussion deleted.
                        if ($discussion->is_locked() || $discussion->is_auto_locked() || $discussion->is_deleted()) {
                            continue;
                        } else {
                            $end = $discussion->get_time_end();
                            $canautolock = true;
                            // Check to see whether time now is beyond the end time.
                            if ($end > $now) {
                                $canautolock = false;
                            }
                            if ($canautolock) {
                                // Lock record.
                                $discussion->auto_lock();
                                $discussionautolocked++;
                            } else {
                                continue;
                            }
                        }
                    } else {
                        // Moving to a different forum.
                        $forum = $discussion->get_forum();
                        $course = $forum->get_course();
                        $modinfo = get_fast_modinfo($course);
                        if ($forum->can_archive_forum($modinfo, $cronlog)) {
                            // Do not get the target forum and course id again
                            // if the target forum is the same.
                            if (!$targetforum || $targetforum->get_id() != $rec->removeto) {
                                $targetforum = mod_forumng::get_from_id($rec->removeto,
                                        mod_forumng::CLONE_DIRECT);
                                $targetforum = $targetforum->get_real_forum();
                            }
                            // Target discussion groupid must be the same as the original groupid.
                            $targetgroupmode = $targetforum->get_group_mode();
                            $targetgroupid = $targetgroupmode ? $discussion->get_group_id() : null;
                            $discussion->move($targetforum, $targetgroupid);
                            $discussionmovecount++;
                        }
                    }
                } else {
                    // Delete all discussions and relevant data permanently.
                    $discussion->permanently_delete();
                    $discussiondeletecount++;
                }
            }
            $housekeepingrs->close();
            if (!PHPUNIT_TEST) {
                mtrace ("\n $discussionmovecount discussions have been archived and " .
                    "$discussiondeletecount discussions have been deleted permanently and " .
                    "$discussionautolocked discussions have been automatically locked");
            }
        }
    }
}
