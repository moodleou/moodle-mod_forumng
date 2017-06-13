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
 * Represents a single forum post.
 * @see mod_forumng_discussion
 * @see forum
 * @package mod
 * @subpackage forumng
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_forumng_post {
    const PARENT_NOT_LOADED = 'not_loaded';

    const PARENTPOST_DEPTH_PER_QUERY = 8;

    // For option definitions, see forumngtype.php display_post function
    const OPTION_EMAIL = 'email';
    const OPTION_DIGEST = 'digest';
    const OPTION_COMMAND_REPLY = 'command_reply';
    const OPTION_COMMAND_EDIT = 'command_edit';
    const OPTION_COMMAND_DELETE = 'command_delete';
    const OPTION_COMMAND_UNDELETE = 'command_undelete';
    const OPTION_COMMAND_SPLIT = 'command_split';
    const OPTION_COMMAND_HISTORY = 'command_history';
    const OPTION_COMMAND_REPORT = 'command_report';
    const OPTION_COMMAND_DIRECTLINK = 'command_directlink';
    const OPTION_VIEW_FULL_NAMES = 'view_full_names';
    const OPTION_TIME_ZONE = 'time_zone';
    const OPTION_SUMMARY = 'summary';
    const OPTION_NO_COMMANDS = 'no_commands';
    const OPTION_RATINGS_VIEW = 'ratings_view';
    const OPTION_RATINGS_EDIT = 'ratings_edit';
    const OPTION_VIEW_DELETED_INFO = 'deleted_info';
    const OPTION_EXPANDED = 'short';
    const OPTION_FLAG_CONTROL = 'flag_control';
    const OPTION_READ_TIME = 'read_time';
    const OPTION_CHILDREN_EXPANDED = 'children_expanded';
    const OPTION_CHILDREN_COLLAPSED = 'children_collapsed';
    const OPTION_INCLUDE_LOCK = 'include_lock';
    const OPTION_EXPORT = 'export';
    const OPTION_FULL_ADDRESSES = 'full_addresses';
    const OPTION_DISCUSSION_SUBJECT = 'discussion_subject';
    const OPTION_SELECTABLE = 'selectable';
    const OPTION_VISIBLE_POST_NUMBERS = 'visible_post_numbers';
    const OPTION_USER_IMAGE = 'user_image';
    const OPTION_PRINTABLE_VERSION = 'printable_version';
    const OPTION_JUMP_NEXT = 'jump_next';
    const OPTION_JUMP_PREVIOUS = 'jump_previous';
    const OPTION_JUMP_PARENT = 'jump_parent';
    const OPTION_FIRST_UNREAD = 'first_unread';
    const OPTION_UNREAD_NOT_HIGHLIGHTED = 'unread_not_highlighted';
    const OPTION_SINGLE_POST = 'single_post';
    const OPTION_PARTICIPATION = 'in_participation_screen';

    /** Constant indicating that post is not rated by user */
    const NO_RATING = 999;

    const OPTION_INDICATE_MODERATOR = 'indicate_moderator';
    const OPTION_IS_ANON = 'is_anon';
    const OPTION_VIEW_ANON_INFO = 'view_anon';

    // Object variables and accessors
    // Comment.

    private $discussion, $parentpost, $postfields, $full, $children,
        $forceexpand, $nextunread, $previousunread;

    /** @return mod_forumng The forum that this post is in */
    public function get_forum() {
        return $this->discussion->get_forum();
    }

    /** @return mod_forumng_post Parent post*/
    public function get_parent() {
        if ($this->parentpost==self::PARENT_NOT_LOADED) {
            throw new coding_exception('Parent post not loaded');
        }
        return $this->parentpost;
    }

    /** @return mod_forumng_discussion The discussion that this post is in  */
    public function get_discussion() {
        return $this->discussion;
    }

    /** @return int ID of this post */
    public function get_id() {
        return $this->postfields->id;
    }

    /** @return string Subject or null if no change in subject */
    public function get_subject() {
        return $this->postfields->subject;
    }

    /** @return int Post number [within discussion] */
    public function get_number() {
        if (!property_exists($this->postfields, 'number')) {
            throw new coding_exception('Post number not available here');
        }
        return $this->postfields->number;
    }

    /**
     * Use to obtain link parameters when linking to any page that has anything
     * to do with posts.
     */
    public function get_link_params($type, $currentuser = false) {
        global $USER;
        $params = 'p=' . $this->postfields->id .
                $this->get_forum()->get_clone_param($type);

        if ($currentuser) {
            $author = $this->get_user();

            if ($author->id == $USER->id) {
                $params .= '&currentuser=1';
            }
        }

        return $params;
    }

    /**
     * @return bool True if can flag
     */
    public function can_flag() {
        // Cannot flag for deleted post
        if ($this->get_deleted() || $this->discussion->is_deleted()) {
            return false;
        }
        // The guest user cannot flag
        if (isguestuser()) {
            return false;
        }
        return true;
    }

    /** @return bool True if post is flagged by current user */
    public function is_flagged() {
        if (!property_exists($this->postfields, 'flagged')) {
            throw new coding_exception('Flagged information not available here');
        }
        return $this->postfields->flagged ? true : false;
    }

    /**
     * @param bool $flag True to set flag
     * @param int $userid User ID or 0 for current
     */
    public function set_flagged($flag, $userid=0) {
        global $DB;
        $userid = mod_forumng_utils::get_real_userid($userid);
        if ($flag) {
            $transaction = $DB->start_delegated_transaction();

            // Check there is not already a row
            if (!$DB->record_exists('forumng_flags',
                    array('postid' => $this->get_id(), 'userid' => $userid))) {
                // Insert new row
                $newflag = (object)array('postid' => $this->get_id(),
                    'userid' => $userid, 'flagged' => time());
                $DB->insert_record('forumng_flags', $newflag);
            }

            // Note: Under rare circumstances this could result in two rows
            // for the same post and user, resulting in duplicates being
            // returned. This is dealt with in mod_forumng::get_flagged_posts.
            $transaction->allow_commit();
        } else {
            $DB->delete_records('forumng_flags',
                    array('postid' => $this->get_id(), 'userid' => $userid));
        }
    }

    /**
     * Obtains the subject to use for this post where a subject is required
     * (should not be blank), such as in email. May be of the form Re:
     * <parent subject>. This function call makes a database query if the full
     * discussion was not loaded into memory.
     * @param bool $expectingquery Set to true if you think this might make
     *     a db query (to prevent the warning)
     * @return string Subject
     */
    public function get_effective_subject($expectingquery = false) {
        if (property_exists($this->postfields, 'effectivesubject')) {
            return $this->postfields->effectivesubject;
        }

        // If subject is set in this post, return it
        if (!is_null($this->postfields->subject)) {
            $this->postfields->effectivesubject = $this->postfields->subject;
            return $this->postfields->effectivesubject;
        }

        // See if we already have other posts loaded
        if ($this->parentpost == self::PARENT_NOT_LOADED) {
            // Posts are not loaded, do a database query
            if (!$expectingquery) {
                debugging('This get method made a DB query; if this is expected,
                    set the flag to say so', DEBUG_DEVELOPER);
            }

            $this->postfields->effectivesubject =
                    self::inner_get_recursive_subject($this->postfields->parentpostid);
            return $this->postfields->effectivesubject;
        } else {
            // Posts are loaded, loop through them to find subject
            for ($parent = $this->parentpost; $parent!=null;
                $parent = $parent->parentpost) {
                if ($parent->postfields->subject!==null) {
                    return get_string('re', 'forumng',
                        $parent->postfields->subject);
                }
            }
            return '[subject error]'; // shouldn't get here
        }
    }

    /**
     * Given a post id - or the id of some ancestor of a post - this query
     * obtains the next (up to) 8 ancestors and returns a 'Re:' subject line
     * corresponding to the first ancestor which has a subject. If none of
     * the 8 have a subject, it makes another query to retrieve the next 8,
     * and so on.
     * @param int $parentid ID of a child post that we are trying to find
     *   the subject from a parent of
     * @return string Subject of post ('Re: something')
     */
    private static function inner_get_recursive_subject($parentid) {
        global $DB;

        // Although the query looks scary because it has so many left joins,
        // in testing it worked quickly. The db just does eight primary-key
        // lookups. Analysis of existing posts in our database showed that
        // doing 8 levels is currently sufficient for about 98.7% of posts.
        $select = '';
        $join = '';
        $maxdepth = self::PARENTPOST_DEPTH_PER_QUERY;
        for ($depth = 1; $depth <= $maxdepth; $depth++) {
            $select .= "p$depth.subject AS s$depth, p$depth.deleted AS d$depth, ";
            if ($depth >= 2) {
                $prev = $depth - 1;
                $join .= "LEFT JOIN {forumng_posts} p$depth
                    ON p$depth.id = p$prev.parentpostid ";
            }
        }

        do {
            $rec = $DB->get_record_sql("
SELECT
    $select
    p$maxdepth.parentpostid AS nextparent
FROM
    {forumng_posts} p1
    $join
WHERE
    p1.id = ?
", array($parentid), MUST_EXIST);
            for ($depth = 1; $depth <= $maxdepth; $depth++) {
                $var = "s$depth";
                $var2 = "d$depth";
                if (isset($rec->{$var}) && $rec->{$var2}==0) {
                    return get_string('re', 'forumng', $rec->{$var});
                }
            }

            $parentid = isset($rec->nextparent) ? $rec->nextparent : null;

        } while ($parentid);

        // If the database and memory representations are correct, we shouldn't
        // really get here because the top-level post always has a subject
        return '';
    }

    /** @return object User who created this post */
    public function get_user() {
        if (!property_exists($this->postfields, 'user')) {
            throw new coding_exception('User is not available at this point.');
        }
        return $this->postfields->user;
    }

    /** @return object User who last edited this post or null if no edits */
    public function get_edit_user() {
        if (!property_exists($this->postfields, 'edituser')) {
            throw new coding_exception('Edit user is not available at this point.');
        }
        return is_null($this->postfields->edituserid)
            ? null : $this->postfields->edituser;
    }

    /** @return int Time post was originally created */
    public function get_created() {
        return $this->postfields->created;
    }

    /** @return int Time post was most recently modified */
    public function get_modified() {
        return $this->postfields->modified;
    }

    /** @return int 0 if post is not deleted, otherwise time of deletion */
    public function get_deleted() {
        return $this->postfields->deleted;
    }

    /** @return object User object (basic fields) of deleter */
    public function get_delete_user() {
        return $this->postfields->deleteuser;
    }

    /** @return bool True if this is an old version of a post */
    public function is_old_version() {
        return $this->postfields->oldversion ? true : false;
    }

    /** @return bool True if the post is important */
    public function is_important() {
        return $this->postfields->important ? true : false;
    }

    /** @return string Message data from database (May be in arbitrary format) */
    public function get_raw_message() {
        return $this->postfields->message;
    }

    /** @return int Format of message (Moodle FORMAT_xx constant) */
    public function get_format() {
        return $this->postfields->messageformat;
    }

    /**
     * @param array $options.
     * @return string Message after format_text and replacing file URLs
     */
    public function get_formatted_message($options = null) {
        global $CFG;
        require_once($CFG->dirroot . '/lib/filelib.php');
        $foremail = false;
        if (!empty($options) && array_key_exists(self::OPTION_EMAIL, $options)) {
            $foremail = $options[self::OPTION_EMAIL];
        }
        $text = $this->postfields->message;
        $forum = $this->get_forum();
        // Add clone param to end of pluginfile requests
        if ($forum->is_shared()) {
            // "@@PLUGINFILE@@/cheese.gif?download=1"
            $text = preg_replace('~([\'"]@@PLUGINFILE@@[^\'"?]+)\?~',
                    '$1?clone=' . $forum->get_course_module_id() . '&amp;', $text);
            // "@@PLUGINFILE@@/cheese.gif"
            $text = preg_replace('~([\'"]@@PLUGINFILE@@[^\'"?]+)([\'"])~',
                    '$1?clone=' . $forum->get_course_module_id() . '$2', $text);
        }
        $id = $this->get_id();
        if ($this->is_old_version()) {
            // If old version get id of parent post as images stored against this.
            $id = $this->get_parent()->get_id();
        }
        $context = $forum->get_context(true);
        if ($foremail) {
            $fileurlbase = 'mod/forumng/pluginfile.php';
        } else {
            $fileurlbase = 'pluginfile.php';
        }
        $text = file_rewrite_pluginfile_urls($text, $fileurlbase,
            $context->id, 'mod_forumng', 'message', $id);
        if ($foremail && (!isset($CFG->slasharguments) || $CFG->slasharguments != 0)) {
            // Append hash if this post render for email.
            $text = $this->add_hash_to_image($text);
        }
        $textoptions = new stdClass();
        // Don't put a <p> tag round post
        $textoptions->para = false;
        // Does not indicate that we trust the text, only that the
        // TRUSTTEXT marker would be supported. At present though it isn't (hm)
        $textoptions->trusttext = false;
        if (trusttext_active() && $this->get_user() && has_capability('moodle/site:trustcontent',
                $context, $this->get_user())) {
            // Support trusted text when initial author is safe (post editors are not checked!).
            $textoptions->trusted = true;
        }
        $textoptions->context = $context;
        return format_text($text, $this->postfields->messageformat, $textoptions);
    }

    /**
     * @return string Message after format_text_email and replacing file URLs
     */
    public function get_email_message() {
        global $CFG;
        require_once($CFG->dirroot . '/lib/filelib.php');
        $text = file_rewrite_pluginfile_urls($this->postfields->message, 'pluginfile.php',
            $this->get_forum()->get_context(true)->id, 'mod_forumng', 'message',
            $this->postfields->id);
        return format_text_email($text, $this->postfields->messageformat);
    }

    /** @return bool True if this message has one or more attachments */
    public function has_attachments() {
        return $this->postfields->attachments ? true : false;
    }

    /**
     * Gets the names of all attachments (if any)
     * @return array Array of attachment names (may be empty). Names only,
     *   not including path to attachment folder
     */
    public function get_attachment_names() {
        $result = array();
        if (!$this->has_attachments()) {
            return $result;
        }
        $filecontext = $this->get_forum()->get_context(true);
        $fs = get_file_storage();
        foreach ($fs->get_area_files($filecontext->id, 'mod_forumng', 'attachment',
                $this->get_id(), 'filename', false) as $file) {
            $result[] = $file->get_filename();
        }
        return $result;
    }

    /**
     * @param string $attachment Attachment name (will not be checked for existence)
     * @return moodle_url URL to attachment
     */
    public function get_attachment_url($attachment) {
        $filecontext = $this->get_forum()->get_context(true);
        $params = array();
        if ($this->get_forum()->is_shared()) {
            $params['clone'] = $this->get_forum()->get_course_module_id();
        }
        return new moodle_url('/pluginfile.php/' . $filecontext->id . '/mod_forumng/attachment/' .
                $this->get_id() . '/' . urlencode($attachment), $params);
    }

    /**
     * @return string URL of this discussion
     */
    public function get_url() {
        return $this->get_discussion()->get_url() . '#p' . $this->get_id();
    }

    /**
     * Marks this post read.
     * @param int $time Time to mark it read at (0 = now)
     * @param int $userid User who's read the post (0 = current)
     */
    public function mark_read($time = 0, $userid = 0) {
        global $DB;
        $userid = mod_forumng_utils::get_real_userid($userid);
        if (!$time) {
            $time = time();
        }
        $transaction = $DB->start_delegated_transaction();
        // Check for existing record - should never have one, but do this in case.
        $existing = $DB->get_record('forumng_read_posts', array('userid' => $userid,
                'postid' => $this->get_id()), '*', IGNORE_MISSING);
        if ($existing) {
            $readrecord = new stdClass();
            $readrecord->id = $existing->id;
            $readrecord->time = $time;
            $DB->update_record('forumng_read_posts', $readrecord);
        } else {
            $readrecord = new stdClass();
            $readrecord->userid = $userid;
            $readrecord->postid = $this->get_id();
            $readrecord->time = $time;
            $DB->insert_record('forumng_read_posts', $readrecord);
        }
        $transaction->allow_commit();
    }

    /**
     * Checks unread status (only available when requested as part of whole
     * discussion).
     * @return bool True if this post is unread
     * @throws mod_forumng_exception If unread data is not available
     */
    public function is_unread() {
        // Your own posts are always read (note: technically you can request
        // unread data for another user - so we use the id for whom data was
        // requested, not $USER->id directly).
        $userid = $this->discussion->get_unread_data_user_id();
        if (($this->postfields->edituserid == $userid) ||
            (!$this->postfields->edituserid
                && $this->postfields->userid==$userid)) {
            return false;
        }

        // Posts past sell-by are always read
        $deadline = mod_forumng::get_read_tracking_deadline();
        if ($this->postfields->modified < $deadline) {
            return false;
        }

        if ($this->get_deleted()) {
            return false;
        }

        $timeread = $this->discussion->get_time_read();

        // If later manual mark post as read record then use that as read time.
        if (!empty($this->postfields->uread) && $this->postfields->uread > $timeread) {
            $timeread = $this->postfields->uread;
        }

        // Compare date to discussion read data.
        return $this->postfields->modified > $timeread;
    }

    /**
     * Checks unread status of child posts (only available when requested as
     * part of whole discussion). Not a recursive method - checks only one
     * level of children.
     * @return bool True if any of the children of this post are unread
     */
    public function has_unread_child() {
        $this->require_children();
        foreach ($this->children as $child) {
            if ($child->is_unread()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Checks if this post has any children (replies).
     * @return bool True if post has one or more replies
     */
    public function has_children() {
        $this->require_children();
        return count($this->children) > 0;
    }

    /**
     * Marks this post as being expanded from the start.
     */
    public function force_expand() {
        $this->forceexpand = true;
    }

    /** @return bool True if this is the first post of a discussion */
    public function is_root_post() {
        return $this->postfields->parentpostid ? false : true;
    }

    /**
     * @throws mod_forumng_exception If rating information wasn't queried
     */
    private function check_ratings() {
        if (!property_exists($this->postfields, 'averagerating')) {
            throw new coding_exception('Rating information not retrieved');
        }
    }

    /**
     * @param bool $astext If true, returns a string rather than a number
     * @return mixed Average rating as float, or a string description if
     *   $astext is true
     * @throws mod_forumng_exception If rating information wasn't queried
     */
    public function get_average_rating($astext = false) {
        $this->check_ratings();
        if ($astext) {
            $options = $this->get_forum()->get_rating_options();
            $value = (int)round($this->postfields->averagerating);
            if (array_key_exists($value, $options)) {
                return $options[$value];
            } else {
                return '?'; // Can occur if rating scale is changed
            }
        } else {
            return $this->postfields->averagerating;
        }
    }

    /**
     * @return int Number of ratings of this post (may be 0)
     */
    public function get_num_ratings() {
        $this->check_ratings();
        return $this->postfields->numratings;
    }

    /**
     * @return int Current user's rating of this post or null if none
     * @throws mod_forumng_exception If rating information wasn't queried
     */
    public function get_own_rating() {
        $this->check_ratings();
        return $this->postfields->ownrating;
    }

    /**
     * Obtains search document representing this post.
     * @return local_ousearch_document Document object
     */
    public function search_get_document() {
        $doc = new local_ousearch_document();
        $doc->init_module_instance('forumng',
                $this->get_forum()->get_course_module(true));
        if ($groupid = $this->discussion->get_group_id()) {
            $doc->set_group_id($groupid);
        }
        $doc->set_int_refs($this->get_id());
        return $doc;
    }

    /**
     * @param array $out Array that receives list of this post and all
     *   children (including nested children) in order
     */
    public function build_linear_children(&$out) {
        $this->require_children();
        $out[count($out)] = $this;
        foreach ($this->children as $child) {
            $child->build_linear_children($out);
        }
    }

    /**
     * Finds a child post (or this one) with the specified ID.
     * @param int $id Post ID
     * @param bool $toplevel True for initial request (makes it throw
     *   exception if not found)
     * @return mod_forumng_post Child post
     */
    public function find_child($id, $toplevel=true) {
        if ($this->postfields->id == $id) {
            return $this;
        }
        $this->require_children();
        foreach ($this->children as $child) {
            $result = $child->find_child($id, false);
            if ($result) {
                return $result;
            }
        }

        if ($toplevel) {
            throw new coding_exception("Child id $id not found");
        }
        return null;
    }

    /**
     * Finds which child post (or this) has the most recent modified date.
     * @param mod_forumng_post &$newest Newest post (must be null when calling)
     */
    public function find_newest_child(&$newest) {
        if (!$newest || $newest->get_modified() < $this->get_modified()) {
            $newest = $this;
        }
        $this->require_children();
        foreach ($this->children as $child) {
            $child->find_newest_child($newest);
        }
    }

    /**
     * Adds the ID of all children (and this post itself) to a list.
     * @param array &$list List of IDs
     */
    public function list_child_ids(&$list) {
        $list[] = $this->get_id();
        $this->require_children();
        foreach ($this->children as $child) {
            $child->list_child_ids($list);
        }
    }

    /**
     * @return mod_forumng_post Next unread post or null if there are no more
     */
    public function get_next_unread() {
        $this->require_children();
        return $this->nextunread;
    }

    /**
     * @return mod_forumng_post Previous unread post or null if there are no more
     */
    public function get_previous_unread() {
        $this->require_children();
        return $this->previousunread;
    }

    /**
     * Used by discussion to set up the unread posts.
     * @param mod_forumng_post $nextunread
     * @param mod_forumng_post $previousunread
     */
    public function set_unread_list($nextunread, $previousunread) {
        $this->nextunread = $nextunread;
        $this->previousunread = $previousunread;
    }

    /** @return int forum ratings enabled */
    public function get_ratings() {
        return isset($this->postfields->rating) ? $this->postfields->rating : null;
    }

    // Factory method
    /*///////////////*/

    /**
     * Creates a forum post object, forum object, and all related data from a
     * single forum post ID. Intended when entering a page which uses post ID
     * as a parameter.
     * @param int $id ID of forum post
     * @param int $cloneid If this is in a shared forum, must be the id of the
     *   clone forum currently in use, or CLONE_DIRECT; otherwise must be 0
     * @param bool $wholediscussion If true, retrieves entire discussion
     *   instead of just this single post
     * @param bool $usecache True to use cache when retrieving the discussion
     * @param int $userid User ID to get post on behalf of (controls flag data
     *   retrieved)
     * @param int $allowmissing Allow missing post or not, if not throw exception
     * @return mod_forumng_post|boolean Post object
     */
    public static function get_from_id($id, $cloneid,
            $wholediscussion=false, $usecache=false, $userid=0, $allowmissing = false) {
        global $CFG, $USER;
        require_once($CFG->dirroot . '/rating/lib.php');
        if ($wholediscussion) {
            $discussion = mod_forumng_discussion::get_from_post_id($id, $cloneid,
                    $usecache, $usecache);
            $root = $discussion->get_root_post();
            return $root->find_child($id);
        } else {
            // Get post data (including extra data such as ratings and flags)
            $records = self::query_posts('fp.id = ?', array($id), 'fp.id', true,
                    true, false, $userid);
            if (count($records)!=1) {
                if ($allowmissing === true) {
                    return false;
                }
                throw new coding_exception("Invalid post ID $id");
            }
            $postfields = reset($records);

            $discussion = mod_forumng_discussion::get_from_id($postfields->discussionid, $cloneid);
            // Load standard ratings.
            $forum = $discussion->get_forum();
            if ($forum->get_enableratings() == mod_forumng::FORUMNG_STANDARD_RATING) {
                // If grading is 'No grading' or 'Teacher grades students'.
                if ($forum->get_grading() == mod_forumng::GRADING_NONE ||
                    $forum->get_grading() == mod_forumng::GRADING_MANUAL) {
                    // Set the aggregation method.
                    if ($forum->get_rating_scale() > 0) {
                        $aggregate = RATING_AGGREGATE_AVERAGE;
                    } else {
                        $aggregate = RATING_AGGREGATE_COUNT;
                    }
                } else {
                    $aggregate = $forum->get_grading();
                }
                $ratingoptions = new stdClass();
                $ratingoptions->context = $forum->get_context(true);
                $ratingoptions->component = 'mod_forumng';
                $ratingoptions->ratingarea = 'post';
                $ratingoptions->items = array('post' => $postfields);
                $ratingoptions->aggregate = $aggregate;
                $ratingoptions->scaleid = $forum->get_rating_scale();
                $ratingoptions->userid = $USER->id;
                $ratingoptions->id = $id;
                $ratingoptions->assesstimestart = $forum->get_ratingfrom();
                $ratingoptions->assesstimefinish = $forum->get_ratinguntil();
                $ratingoptions->returnurl = $discussion->get_moodle_url();

                $rm = new rating_manager();
                $postwithratings = $rm->get_ratings($ratingoptions);
                $postfields = $postwithratings['post'];// Update 'post' object.
            }
            $newpost = new mod_forumng_post($discussion, $postfields);
            return $newpost;
        }
    }

    /**
     * Obtains a search document given the ousearch parameters.
     * @param object $document Object containing fields from the ousearch documents table
     * @return mixed False if object can't be found, otherwise object containing the following
     *   fields: ->content, ->title, ->url, ->activityname, ->activityurl,
     *   and optionally ->extrastrings array, ->data, ->hide
     */
    public static function search_get_page($document) {
        global $DB, $CFG, $USER;

        // Implemented directly in SQL for performance, rather than using the
        // objects themselves
        $result = $DB->get_record_sql("
SELECT
    fp.message AS content, fp.subject, firstpost.subject AS firstpostsubject,
    firstpost.id AS firstpostid, fd.id AS discussionid,
    f.name AS activityname, cm.id AS cmid, fd.timestart, fd.timeend,
    f.shared AS shared, f.type AS forumtype
FROM
    {forumng_posts} fp
    INNER JOIN {forumng_discussions} fd ON fd.id = fp.discussionid
    INNER JOIN {forumng_posts} firstpost ON fd.postid = firstpost.id
    INNER JOIN {forumng} f ON fd.forumngid = f.id
    INNER JOIN {course_modules} cm ON cm.instance = f.id AND cm.course = f.course
    INNER JOIN {modules} m ON cm.module = m.id
WHERE
    fp.id = ? AND m.name = 'forumng'", array($document->intref1), IGNORE_MISSING);
        if (!$result) {
            return false;
        }

        // Title is either the post subject or Re: plus the discussion subject
        // if the post subject is blank
        $result->title = $result->subject;

        if (is_null($result->title)) {
            $result->title = get_string('re', 'forumng', $result->firstpostsubject);
        }

         // Link is to value in url if present, otherwise to original forum
        $cloneparam = $result->cmid;
        if ($result->shared) {
            global $FORUMNG_CLONE_MAP;
            if (!empty($FORUMNG_CLONE_MAP)) {
                $cloneparam = $FORUMNG_CLONE_MAP[$result->cmid]->id;
                $clonebit = '&amp;clone=' . $cloneparam;
            } else {
                $clonebit = '&amp;clone=' .
                    ($cloneparam = optional_param('clone', $result->cmid, PARAM_INT));
            }
        } else {
            $clonebit = '';
        }

        // Work out URL to post
        $result->url = $CFG->wwwroot . '/mod/forumng/discuss.php?d=' .
            $result->discussionid . $clonebit . '#p' . $document->intref1;

        // Activity URL
        $result->activityurl = $CFG->wwwroot . '/mod/forumng/view.php?id=' .
            $result->cmid . $clonebit;

        // Hide results outside their time range (unless current user can see)
        $now = time();
        if ($now < $result->timestart || ($result->timeend && $now>=$result->timeend) &&
            !has_capability('mod/forumng:viewallposts',
                context_module::instance($result->cmid))) {
            $result->hide = true;
        }

        // Handle annoying forum types that hide discussions
        $type = forumngtype::get_new($result->forumtype);
        if ($type->has_unread_restriction()) {
            // TODO The name of the _unread_restriction should be _discussion_restriction.
            // This is going to be slow, we need to load the discussion
            $discussion = mod_forumng_discussion::get_from_id($result->discussionid, $cloneparam);
            if (!$type->can_view_discussion($discussion, $USER->id)) {
                $result->hide = true;
            }
        }

        return $result;
    }

    // Object methods
    /*///////////////*/

    /**
     * @param mod_forumng_discussion $discussion Discussion object
     * @param object $postfields Post fields from DB table (may also include
     *   some extra fields provided by mod_forumng_post::query_posts)
     * @param mod_forumng_post $parentpost Parent post or null if this is root post,
     *   or PARENT_NOT_LOADED if not available
     */
    public function __construct($discussion, $postfields, $parentpost=self::PARENT_NOT_LOADED) {
        $this->discussion = $discussion;
        $this->postfields = $postfields;

        // Extract the user details into Moodle user-like objects
        if (property_exists($postfields, 'u_id')) {
            $postfields->user = mod_forumng_utils::extract_subobject($postfields, 'u_');
            $postfields->edituser = mod_forumng_utils::extract_subobject($postfields, 'eu_');
            $postfields->deleteuser = mod_forumng_utils::extract_subobject($postfields, 'du_');
        }

        $this->parentpost = $parentpost;
        $this->children = false;
    }

    /**
     * Used to inform the post that all its children will be supplied.
     * Call before calling add_child(), or even if there are no children.
     */
    public function init_children() {
        $this->children = array();
    }

    /**
     * For internal use only. Adds a child to this post while constructing
     * the tree of posts
     * @param mod_forumng_post $child Child post
     */
    public function add_child($child) {
        $this->require_children();
        $this->children[] = $child;
    }

    /**
     * Checks that children are available.
     * @throws mod_forumng_exception If children have not been loaded
     */
    public function require_children() {
        if (!is_array($this->children)) {
            throw new coding_exception('Requires child post data');
        }
    }

    /**
     * Internal function. Queries for posts.
     * @param string $where Where clause (fp is alias for post table)
     * @param array $whereparams Parameters (values for ? parameters) in where clause
     * @param string $order Sort order; the default is fp.id - note this is preferable
     *   to fp.timecreated because it works correctly if there are two posts in
     *   the same second
     * @param bool $ratings True if ratings should be included in the query
     * @param bool $flags True if flags should be included in the query
     * @param bool $effectivesubjects True if the query should include the
     *   (complicated!) logic to obtain the 'effective subject'. This may result
     *   in additional queries afterward for posts which are very deeply nested.
     * @param int $userid 0 = current user (at present this is only used for
     *   flags)
     * @param bool $read True if read post record (time) is sought
     * @return array Resulting posts as array of Moodle records, empty array
     *   if none
     */
    public static function query_posts($where, $whereparams, $order='fp.id', $ratings=true,
        $flags=false, $effectivesubjects=false,
        $userid=0, $joindiscussion=false, $discussionsubject=false, $limitfrom='',
        $limitnum='', $read = false) {
        global $DB, $USER;
        $userid = mod_forumng_utils::get_real_userid($userid);
        $queryparams = array();

        // We include ratings if these are enabled, otherwise save the database
        // some effort and don't bother
        if ($ratings) {
            $ratingsquery = ",
(SELECT AVG(rating) FROM {forumng_ratings}
    WHERE postid = fp.id) AS averagerating,
(SELECT COUNT(1) FROM {forumng_ratings}
    WHERE postid = fp.id) AS numratings,
(SELECT rating FROM {forumng_ratings}
    WHERE postid = fp.id AND userid = ?) AS ownrating";
            // Add parameter to start of params list
            $queryparams[] = $USER->id;
        } else {
            $ratingsquery = '';
        }

        if ($flags) {
            $flagsjoin = "
    LEFT JOIN {forumng_flags} ff ON ff.postid = fp.id AND ff.userid = ?";
            $flagsquery = ", ff.flagged";
            $queryparams[] = $userid;
        } else {
            $flagsjoin = '';
            $flagsquery = '';
        }

        if ($joindiscussion) {
            $discussionjoin = "
    INNER JOIN {forumng_discussions} fd ON fp.discussionid = fd.id";
            $discussionquery = ',' . mod_forumng_utils::select_discussion_fields('fd');
            if ($discussionsubject) {
                $discussionjoin .= "
    INNER JOIN {forumng_posts} fdfp ON fd.postid = fdfp.id";
                $discussionquery .= ', fdfp.subject AS fd_subject';
            }
        } else {
            $discussionjoin = '';
            $discussionquery = '';
        }

        if ($effectivesubjects) {
            $maxdepth = self::PARENTPOST_DEPTH_PER_QUERY;
            $subjectsjoin = '';
            $subjectsquery = ", p$maxdepth.parentpostid AS nextparent ";

            for ($depth = 2; $depth <= $maxdepth; $depth++) {
                $subjectsquery .= ", p$depth.subject AS s$depth, p$depth.deleted AS d$depth";
                $prev = 'p'. ($depth - 1);
                if ($prev == 'p1') {
                    $prev = 'fp';
                }
                $subjectsjoin .= "LEFT JOIN {forumng_posts} p$depth
                    ON p$depth.id = $prev.parentpostid ";
            }
        } else {
            $subjectsjoin = '';
            $subjectsquery = '';
        }

        if ($read) {
            $readquery = ', fr.time AS uread';
            $readjoin = "LEFT JOIN {forumng_read_posts} fr ON fr.postid = fp.id AND fr.userid = ?";
            $queryparams[] = $userid;
        } else {
            $readquery = '';
            $readjoin = '';
        }

        // Retrieve posts from discussion with incorporated user information
        // and ratings info if specified
        $results = $DB->get_records_sql("
SELECT
    fp.*,
    ".mod_forumng_utils::select_username_fields('u', true).",
    ".mod_forumng_utils::select_username_fields('eu').",
    ".mod_forumng_utils::select_username_fields('du')."
    $ratingsquery
    $flagsquery
    $subjectsquery
    $discussionquery
    $readquery
FROM
    {forumng_posts} fp
    INNER JOIN {user} u ON fp.userid = u.id
    LEFT JOIN {user} eu ON fp.edituserid = eu.id
    LEFT JOIN {user} du ON fp.deleteuserid = du.id
    $discussionjoin
    $flagsjoin
    $subjectsjoin
    $readjoin
WHERE
    $where
ORDER BY
    $order
", array_merge($queryparams, $whereparams), $limitfrom, $limitnum);
        if ($effectivesubjects) {
            // Figure out the effective subject for each result
            foreach ($results as $result) {
                $got = false;
                if ($result->subject !== null) {
                    $result->effectivesubject = $result->subject;
                    $got = true;
                    continue;
                }
                for ($depth = 2; $depth <= $maxdepth; $depth++) {
                    $var = "s$depth";
                    $var2 = "d$depth";
                    if (!$got && $result->{$var} !== null && $result->{$var2}==0) {
                        $result->effectivesubject = get_string('re', 'forumng', $result->{$var});
                        $got = true;
                    }
                    unset($result->{$var});
                    unset($result->{$var2});
                }
                if (!$got) {
                    // Do extra queries to pick up subjects for posts where it
                    // was unknown within the default depth. We can use the
                    // 'nextparent' to get the ID of the parent post of the last
                    // one that we checked already
                    $result->effectivesubject = self::inner_get_recursive_subject(
                        $result->nextparent);
                }
            }
        }
        return $results;
    }

    /**
     * Replies to the post
     * @param string $subject Subject
     * @param string $message Message
     * @param int $messageformat Moodle format used for message
     * @param bool $attachments True if post contains attachments
     * @param bool $setimportant If true, highlight the post
     * @param bool $mailnow If true, sends mail ASAP
     * @param int $userid User ID (0 = current)
     * @param bool $log True to log this reply
     * @param int $asmoderator values are ASMODERATOR_NO, ASMODERATOR_IDENTIFY or ASMODERATOR_ANON
     * @return int ID of newly-created post
     */
    public function reply($subject, $message, $messageformat,
        $attachments=false, $setimportant=false, $mailnow=false, $userid=0, $log=true,
        $asmoderator = mod_forumng::ASMODERATOR_NO) {
        global $DB;
        $transaction = $DB->start_delegated_transaction();
        $id = $this->discussion->create_reply($this, $subject, $message, $messageformat,
                $attachments, $setimportant, $mailnow, $userid, $asmoderator);
        if ($log) {
            $this->log('add reply', $id);
        }
        $transaction->allow_commit();
        $this->get_discussion()->uncache();
        return $id;
    }

    /**
     * Updates the message field of a post entry. This is necessary in some cases where
     * the user includes images etc. in the message; these are initially included using
     * a draft URL which has to be changed to a special relative path on convert, and we
     * can't do that until the post ID is known. Additionally, we don't have a post object
     * at that point, hence use of static function.
     * @param int $postid ID of post to update
     * @param string $newtext Updated message text
     */
    public static function update_message_for_files($postid, $newtext) {
        global $DB;
        $DB->set_field('forumng_posts', 'message', $newtext, array('id'=>$postid));
    }

    /**
     * Obtains a list of previous versions of this post (if any), in descending
     * order of modification date.
     * @return array Array of mod_forumng_post objects (empty if none)
     */
    public function get_old_versions() {
        $postdata = self::query_posts(
            'fp.oldversion = 1 AND fp.parentpostid = ?', array($this->postfields->id),
            'fp.modified DESC', false, false);
        $posts = array();
        foreach ($postdata as $postfields) {
            $newpost = new mod_forumng_post($this->discussion, $postfields, $this);
            $posts[] = $newpost;
        }
        return $posts;
    }

    /**
     * Recursive function obtains all users IDs that made this post and all
     * child posts.
     * @param array &$userids Associative array from id=>true that receives
     *   all user IDs
     * @param $ignoredeleted Set true to discount posts that are deleted
     */
    public function list_all_user_ids(&$userids, $ignoredeleted = false) {
        $this->require_children();
        // Add current ID.
        if (!$ignoredeleted || !$this->get_deleted()) {
            $userid = $this->get_user()->id;
            if (!array_key_exists($userid, $userids)) {
                $userids[$userid] = true;
            }
        }

        foreach ($this->children as $post) {
            $post->list_all_user_ids($userids, $ignoredeleted);
        }
    }

    /**
     * NOTE: This method is the second stage of editing and must be called
     * after edit_start and after files are being updated. This is because
     * it depends on the result of file_save_draft_area_files.
     * @param string $message Message
     * @param int $messageformat Moodle format ID
     * @param bool $gotsubject True if message subject changed
     */
    public function edit_finish($message, $messageformat, $gotsubject) {
        global $DB;

        $transaction = $DB->start_delegated_transaction();
        $update = new StdClass;
        if ($message!==$this->postfields->message) {
            $update->message = $message;
        }
        if ($messageformat != $this->postfields->messageformat) {
            $update->messageformat = $messageformat;
        }
        if (count((array)$update)>0) {
            $update->id = $this->postfields->id;
            $DB->update_record('forumng_posts', $update);
        }

        // Update in-memory representation
        foreach ((array)$update as $name => $value) {
            $this->postfields->{$name} = $value;
        }

        // Uncache before updating search (want to make sure that the recursive
        // update gets latest data)
        $this->get_discussion()->uncache();

        // Update search index
        if ((isset($update->message) || $gotsubject)) {
            // Update for this post
            $this->search_update();

            // If changing the subject of a root post, update all posts in the
            // discussion (ugh)
            if ($this->is_root_post() && $gotsubject) {
                $this->search_update_children();
            }
        }

        $transaction->allow_commit();
    }

    /**
     * Edits an existing message. The previous version of the message is
     * retained for admins to view if needed.
     *
     * NOTE: This method is the first stage of editing and must be called
     * BEFORE files are updated. Ensure that there is a DB transaction around
     * the calls to these two methods.
     * @param string $subject Subject
     * @param bool $attachments True if post now contains attachments
     * @param bool $setimportant If true, highlight the post
     * @param bool $mailnow New value of mailnow flag (ignored if message was already mailed)
     * @param int $userid Userid doing the editing (0 = current)
     * @param int $asmoderator values are ASMODERATOR_NO, ASMODERATOR_IDENTIFY or ASMODERATOR_ANON
     * @return bool True if subject changed (this is weird, but edit_finish
     *   needs it)
     */
    public function edit_start($subject, $attachments=false, $setimportant=false,
            $mailnow=false, $userid=0, $log=true, $asmoderator = mod_forumng::ASMODERATOR_NO) {
        global $DB;
        $now = time();

        // Create copy of existing entry ('old version')
        $copy = clone($this->postfields);

        // Copy has oldversion set to 1 and parentpost set to id of real post
        $copy->oldversion = 1;
        $copy->parentpostid = $copy->id;
        unset($copy->id);

        // OK, add copy
        $transaction = $DB->start_delegated_transaction();
        $copyid = $DB->insert_record('forumng_posts', $copy);

        // Move old attachments to copy (note: we will save new attachments from filemanager draft
        // area later)
        if ($this->has_attachments()) {
            $fs = get_file_storage();
            $filecontext = $this->get_forum()->get_context(true);
            foreach (array('attachment', 'message') as $filearea) {
                $oldfiles = $fs->get_area_files($filecontext->id, 'mod_forumng', $filearea,
                        $this->get_id(), 'id', false);
                foreach ($oldfiles as $oldfile) {
                    $filerecord = new stdClass();
                    $filerecord->itemid = $copyid;
                    $fs->create_file_from_storedfile($filerecord, $oldfile);
                }
                $fs->delete_area_files($filecontext->id, 'mod_forumng',
                        $filearea, $this->get_id());
            }
        }

        // Update existing entry with new data where it changed
        $update = new StdClass;
        $gotsubject = false;
        if ($subject!==$this->postfields->subject) {
            $update->subject = strlen(trim($subject)) == 0 ? null : $subject;
            $gotsubject = true;
        }
        if (!$attachments && $this->postfields->attachments) {
            $update->attachments = 0;
        } else if ($attachments && !$this->postfields->attachments) {
            $update->attachments = 1;
        }
        if ($setimportant) {
            $update->important = 1;
        } else {
            $update->important = 0;
        }
        $update->mailstate = mod_forumng::MAILSTATE_NOT_MAILED;
        $update->modified = $now;
        $update->edituserid = mod_forumng_utils::get_real_userid($userid);

        $update->id = $this->postfields->id;
        $update->asmoderator = $asmoderator;
        $DB->update_record('forumng_posts', $update);

        if ($log) {
            $this->log('edit post');
        }

        // Update in-memory representation
        foreach ((array)$update as $name => $value) {
            $this->postfields->{$name} = $value;
        }
        // If this is the root post, then changing its subject affects
        // the discussion subhject
        if ($this->is_root_post() && $gotsubject) {
            $this->discussion->hack_subject($this->postfields->subject);
        }

        $transaction->allow_commit();

        return $gotsubject;
    }

    /**
     * Updates search data for this post.
     * @param bool $expectingquery True if it might need to make a query to
     *   get the subject
     */
    public function search_update($expectingquery = false) {
        if (!mod_forumng::search_installed()) {
            return;
        }
        global $DB;

        $searchdoc = $this->search_get_document();

        $transaction = $DB->start_delegated_transaction();
        if ($this->get_deleted() || $this->get_discussion()->is_deleted() ||
            $this->get_discussion()->is_making_search_change()) {
            if ($searchdoc->find()) {
                $searchdoc->delete();
            }
        } else {
            // $title here is not the title appearing in the search result
            // but the text which decides the search score
            $title = $this->get_subject();
            $searchdoc->update($title, $this->get_formatted_message());
        }
        $transaction->allow_commit();
    }

    /**
     * Calls search_update on each child of the current post, and recurses.
     * Used when the subject's discussion is changed.
     */
    public function search_update_children() {
        if (!mod_forumng::search_installed()) {
            return;
        }
        // If the in-memory post object isn't already part of a full
        // discussion...
        if (!is_array($this->children)) {
            // ...then get one
            $discussion = mod_forumng_discussion::get_from_id(
                $this->discussion->get_id(),
                $this->get_forum()->get_course_module_id());
            $post = $discussion->get_root_post()->find_child($this->get_id());
            // Do this update on the new discussion
            $post->search_update_children();
            return;
        }

        // Loop through all children
        foreach ($this->children as $child) {
            // Update its search fields
            $child->search_update();

            // Recurse
            $child->search_update_children();
        }
    }

    /**
     * Marks a post as deleted.
     * @param int $userid User ID to mark as having deleted the post
     * @param bool $log If true, adds entry to Moodle log
     */
    public function delete($userid=0, $log=true) {
        global $DB;

        if ($this->postfields->deleted) {
            return;
        }
        if (!$this->postfields->parentpostid) {
            throw new coding_exception('Cannot delete discussion root post');
        }
        $transaction = $DB->start_delegated_transaction();

        // Mark this post as deleted
        $update = new StdClass;
        $update->id = $this->postfields->id;
        $update->deleted = time();
        $update->deleteuserid = mod_forumng_utils::get_real_userid($userid);
        $DB->update_record('forumng_posts', $update);
        $this->postfields->deleted = $update->deleted;
        $this->postfields->deleteuserid = $update->deleteuserid;

        // In case this post is the last one, update the discussion field
        $this->get_discussion()->possible_lastpost_change($this);

        // May result in user becoming incomplete
        $this->update_completion(false);

        if ($log) {
            $this->log('delete post');
        }

        $this->search_update();

        $transaction->allow_commit();
        $this->get_discussion()->uncache();
    }

    /**
     * Marks a post as undeleted.
     * @param bool $log If true, adds entry to Moodle log
     */
    public function undelete($log=true) {
        global $DB;
        if (!$this->postfields->deleted) {
            return;
        }
        $transaction = $DB->start_delegated_transaction();

        // Undelete this post
        $update = new StdClass;
        $update->id = $this->postfields->id;
        $update->deleted = 0;
        $update->deleteuserid = 0;
        $DB->update_record('forumng_posts', $update);
        $this->postfields->deleted = 0;
        $this->postfields->deleteuserid = 0;

        // In case this post is the last one, update the discussion field
        $this->get_discussion()->possible_lastpost_change($this);

        // May result in user becoming complete
        $this->update_completion(true);

        if ($log) {
            $this->log('undelete post');
        }

        $this->search_update();

        $transaction->allow_commit();
        $this->get_discussion()->uncache();
    }

    /**
     * Splits this post to become a new discussion
     * @param $newsubject
     * @param bool $log True to log action
     * @return int ID of new discussion
     */
    public function split($newsubject, $log=true) {
        global $DB;
        $this->require_children();

        // Begin a transaction
        $transaction = $DB->start_delegated_transaction();

        $olddiscussion = $this->get_discussion();

        // Create new discussion
        $newest = null;
        $this->find_newest_child($newest);
        $newdiscussionid = $olddiscussion->clone_for_split(
            $this->get_id(), $newest->get_id());

        // Update all child posts
        $list = array();
        $this->list_child_ids($list);
        unset($list[0]); // Don't include this post itself
        if (count($list) > 0) {
            list($listsql, $listparams) = mod_forumng_utils::get_in_array_sql('id', $list);
            $DB->execute("
UPDATE
    {forumng_posts}
SET
    discussionid = ?
WHERE
    $listsql", array_merge(array($newdiscussionid), $listparams));
        }

        // Update any edited posts in this discussion. Edited posts are
        // not included in the child id list above because they are not
        // loaded as children, but they are conceptually stored as children
        // of one of the posts being moved.
        $parentlist = $list;
        $parentlist[] = $this->get_id();
        list($parentlistsql, $parentlistparams) = mod_forumng_utils::get_in_array_sql(
                'parentpostid', $parentlist);
        $DB->execute("
UPDATE
    {forumng_posts}
SET
    discussionid = ?
WHERE
    oldversion = 1 AND $parentlistsql", array_merge(array($newdiscussionid), $parentlistparams));

        // Update this post
        $changes = new stdClass;
        $changes->id = $this->get_id();
        $changes->subject = $newsubject;
        $changes->parentpostid = null;
        // When split the post, reset the important to 0 so that it is not highlighted.
        $changes->important = 0;
        // Note don't update modified time, or it makes this post unread,
        // which isn't very helpful
        $changes->discussionid = $newdiscussionid;
        $DB->update_record('forumng_posts', $changes);

        // Update read data if relevant
        if (mod_forumng::enabled_read_tracking() &&
            ($newest->get_modified() >= mod_forumng::get_read_tracking_deadline())) {
            $rs = $DB->get_recordset_sql("
SELECT
    userid, time
FROM
    {forumng_read}
WHERE
    discussionid = ? AND time >= ?", array($olddiscussion->get_id(), $this->get_created()));
            foreach ($rs as $rec) {
                $rec->discussionid = $newdiscussionid;
                $DB->insert_record('forumng_read', $rec);
            }
            $rs->close();
        }

        $olddiscussion->possible_lastpost_change();

        if ($log) {
            $this->log('split post');
        }

        $transaction->allow_commit();
        $this->get_discussion()->uncache();

        // If discussion-based completion is turned on, this may enable someone
        // to complete
        if ($this->get_forum()->get_completion_discussions()) {
            $this->update_completion(true);
        }

        return $newdiscussionid;
    }

    /**
     * Rates this post or updates an existing forum rating.
     * @param $rating Rating (value depends on scale used) or NO_RATING
     * @param $userid User ID or 0 for current user
     */
    public function rate($rating, $userid=0) {
        global $DB;
        $userid = mod_forumng_utils::get_real_userid($userid);
        $transaction = $DB->start_delegated_transaction();

        // Delete any existing rating
        $DB->delete_records('forumng_ratings',
            array('postid' => $this->postfields->id, 'userid' => $userid));

        // Add new rating
        if ($rating != self::NO_RATING) {
            $ratingobj = new StdClass;
            $ratingobj->userid = $userid;
            $ratingobj->postid = $this->postfields->id;
            $ratingobj->time = time();
            $ratingobj->rating = $rating;
            $DB->insert_record('forumng_ratings', $ratingobj);
        }

        // Tell grade to update
        if ($this->get_forum()->get_grading()) {
            $this->get_forum()->update_grades($this->get_user()->id);
        }

        $transaction->allow_commit();
        $this->get_discussion()->uncache();
    }

    /**
     * Records an action in the Moodle log for current user.
     * @param string $action Action name - see datalib.php for suggested verbs
     *   and this code for example usage
     * @param int $replyid Specify only when adding a reply; when specified,
     *   this is the reply ID (used because the reply entry is logged under
     *   the new post, not the old one)
     */
    public function log($action, $replyid=0) {
        if ($replyid) {
            $postid = $replyid;
        } else {
            $postid = $this->postfields->id;
        }
        $params = array(
                'context' => $this->get_forum()->get_context(),
                'objectid' => $postid,
                'other' => array('logurl' => $this->discussion->get_log_url() . '#p' . $postid,
                    'discussid' => $this->get_discussion()->get_id())
        );
        if ($action == 'add reply') {
            $classname = 'post_created';
            $params['other']['parent'] = $this->get_id();
        } else if ($action == 'edit post') {
            $classname = 'post_updated';
        } else if ($action == 'delete post') {
            $classname = 'post_deleted';
        } else if ($action == 'undelete post') {
            $classname = 'post_undeleted';
        } else if ($action == 'split post') {
            $classname = 'post_split';
        } else if ($action == 'report post') {
            $classname = 'post_reported';
        } else {
            throw new coding_exception('Unknown forumng post log event.');
        }
        $class = '\\mod_forumng\\event\\' . $classname;
        $event = $class::create($params);
        $event->add_record_snapshot('course_modules', $this->get_forum()->get_course_module());
        $event->add_record_snapshot('course', $this->get_forum()->get_course());
        if (empty($replyid)) {
            // Only add snapshot when talking about this post, not new reply.
            $event->add_record_snapshot('forumng_posts', $this->postfields);
        }
        $event->trigger();
    }

    // Permissions
    /*////////////*/

    /**
     * Makes security checks for viewing this post. Will not return if
     * user cannot view it.
     * This function should be a complete access check. It calls the
     * discussion's equivalent method.
     * Note that this function only works for the current user when used in
     * interactive mode (ordinary web page view). It cannot be called in cron,
     * web services, etc.
     */
    public function require_view() {
        global $USER;
        // Check forum and discussion view permission, group access, etc.
        $this->discussion->require_view();

        // Other than being able to view the discussion, no additional
        // requirements to view a normal post
        if (!$this->get_deleted() && !$this->is_old_version()) {
            return true;
        }

        // User can see own deleted posts.
        if (isset($USER) && $this->get_deleted() && $this->get_user()->id === $USER->id) {
            return true;
        }

        // Deleted posts and old versions of edited posts require viewallposts
        require_capability('mod/forumng:viewallposts',
            $this->get_forum()->get_context());
    }

    /**
     * Checks whether the user can add a new reply to this post, assuming that
     * they can view the discussion.
     * @param string &$whynot
     * @param int $userid
     * @return unknown_type
     */
    public function can_reply(&$whynot, $userid=0) {
        $userid = mod_forumng_utils::get_real_userid($userid);
        $context = $this->get_forum()->get_context();

        // Check if post is a special case
        if ($this->get_deleted() || $this->is_old_version()
            || $this->get_discussion()->is_deleted()) {
            $whynot = 'reply_notcurrentpost';
            return false;
        }

        // Check if discussion is different group
        if (!$this->get_discussion()->can_write_to_group()) {
            $whynot = 'reply_wronggroup';
            return false;
        }

        // Check if discussion is locked
        if ($this->get_discussion()->is_locked()) {
            $whynot = 'edit_locked';
            return false;
        }

        // Check read-only dates
        if ($this->get_forum()->is_read_only($userid)) {
            $whynot = 'reply_readonly';
            return false;
        }

        // Check permission
        if (!has_capability('mod/forumng:replypost', $context, $userid)) {
            $whynot = 'reply_nopermission';
            return false;
        }

        // Let forum type veto reply if required
        if (!$this->get_forum()->get_type()->can_reply($this, $userid)) {
            $whynot = 'reply_typelimit';
            return false;
        }

        // Throttling
        if ($this->get_forum()->get_remaining_post_quota($userid) == 0) {
            $whynot = 'reply_postquota';
            return false;
        }

        return true;
    }

    /**
     * @param int $userid User ID or 0 for current
     * @return bool True if user can rate this post
     */
    public function can_rate($userid=0) {
        $userid = mod_forumng_utils::get_real_userid($userid);
        return
            !$this->get_deleted() && !$this->is_old_version()
            && !$this->get_discussion()->is_deleted()
            && !$this->get_discussion()->is_locked()
            && $this->get_discussion()->can_write_to_group()
            && $this->get_forum()->can_rate($this->get_created()) &&
            $this->get_user()->id != $userid;
    }


    /**
     * @param int $userid User ID or 0 for current
     * @return bool True if user can view ratings for this post
     */
    public function can_view_ratings($userid=0) {
        $userid = mod_forumng_utils::get_real_userid($userid);
        return !$this->get_deleted() && !$this->is_old_version()
            && $this->get_forum()->has_ratings() &&
            has_capability($this->get_user()->id == $userid
            ? 'mod/forumng:viewrating'
            : 'mod/forumng:viewanyrating', $this->get_forum()->get_context());
    }

    public function can_split(&$whynot, $userid=0) {
        // Check if this is a special case
        if ($this->get_deleted() || $this->is_old_version()
            || $this->get_discussion()->is_deleted()) {
            $whynot = 'edit_notcurrentpost';
            return false;
        }

        // Check if discussion is different group
        if (!$this->get_discussion()->can_write_to_group()) {
            $whynot = 'edit_wronggroup';
            return false;
        }

        // Can't split root post
        if ($this->is_root_post()) {
            $whynot = 'edit_rootpost';
            return false;
        }

        // Check permission
        if (!$this->get_discussion()->can_split($whynot, $userid)) {
            return false;
        }

        return true;
    }

    /**
     * @param string &$whynot
     * @return bool True if user can alert this post
     */
    public function can_alert(&$whynot) {
        // Check if the post has been deleted
        if ($this->get_deleted() || $this->discussion->is_deleted()) {
            $whynot = 'alert_notcurrentpost';
            return false;
        }

        // If not site level or forum level reporting email has been set
        if (!$this->get_forum()->has_reporting_email()) {
            $whynot = 'alert_turnedoff';
            return false;
        }
        return true;
    }

    /**
     * @param string &$whynot
     * @return bool True if can display the direct link
     */
    public function can_showdirectlink() {
        // Check if the post has been deleted
        if ($this->get_deleted() || $this->discussion->is_deleted()) {
            return false;
        }
        return true;
    }

    /**
     * Checks whether the user can delete the post, assuming that they can
     * view the discussion.
     * @param string &$whynot If returning false, set to the language string defining
     *   reason for not being able to edit
     * @param int $userid User ID or 0 if current
     * @return bool True if user can edit this post
     */
    public function can_undelete(&$whynot, $userid=0) {
        $userid = mod_forumng_utils::get_real_userid($userid);
        $context = $this->get_forum()->get_context();

        // Check if post is a special case
        if ($this->is_old_version() || $this->get_discussion()->is_deleted()) {
            $whynot = 'edit_notcurrentpost';
            return false;
        }

        // Check if discussion is different group
        if (!$this->get_discussion()->can_write_to_group()) {
            $whynot = 'edit_wronggroup';
            return false;
        }

        // Check if discussion is locked
        if ($this->get_discussion()->is_locked()) {
            $whynot = 'edit_locked';
            return false;
        }

        if (!$this->get_deleted()) {
            $whynot = 'edit_notdeleted';
            return false;
        }

        // Check the 'edit any' capability (always required to undelete)
        if (!has_capability('mod/forumng:editanypost', $context, $userid)) {
            $whynot = 'edit_nopermission';
            return false;
        }

        // Check read-only dates
        if ($this->get_forum()->is_read_only($userid)) {
            $whynot = 'edit_readonly';
            return false;
        }

        // OK! They're allowed to undelete (whew)
        $whynot = '';
        return true;
    }

    /**
     * Checks whether the user can delete the post, assuming that they can
     * view the discussion.
     * @param string &$whynot If returning false, set to the language string defining
     *   reason for not being able to edit
     * @param int $userid User ID or 0 if current
     * @return bool True if user can edit this post
     */
    public function can_delete(&$whynot, $userid=0) {
        // At present the logic for this is identical to the edit logic
        // except that you can't delete the root post
        return !$this->is_root_post() && $this->can_edit($whynot, $userid);
    }

    /**
     * Checks whether the user can view deleted post info.
     * @param string $whynot If returning false, set to the language string
     *   defining reason for not being able to view edits
     * @param int $userid User ID or 0 for current
     * @return bool True if user can view deleted posts
     */
    public function can_view_deleted(&$whynot, $userid=0) {
        $userid = mod_forumng_utils::get_real_userid($userid);

        // Check the 'edit any' capability (this is checked for deleting/undeleting).
        if (!has_capability('mod/forumng:editanypost',
                $this->get_forum()->get_context(), $userid)) {
            // Check if post belongs to specified user.
            if ($this->get_user()->id != $userid) {
                $whynot = 'edit_nopermission';
                return false;
            }
        }

        return true;
    }

    /**
     * Checks whether the user can view edits to posts.
     * @param string $whynot If returning false, set to the language string
     *   defining reason for not being able to view edits
     * @param int $userid User ID or 0 for current
     * @return bool True if user can view edits
     */
    public function can_view_history(&$whynot, $userid=0) {
        $userid = mod_forumng_utils::get_real_userid($userid);
        // Check the 'edit any' capability
        if (!has_capability('mod/forumng:editanypost',
                $this->get_forum()->get_context(), $userid)) {
            $whynot = 'edit_nopermission';
            return false;
        }

        return true;
    }

    /**
     * Checks whether the user can edit the post, assuming that they can
     * view the discussion.
     * @param string &$whynot If returning false, set to the language string defining
     *   reason for not being able to edit
     * @param int $userid User ID or 0 if current
     * @return bool True if user can edit this post
     */
    public function can_edit(&$whynot, $userid=0) {
        $userid = mod_forumng_utils::get_real_userid($userid);
        $context = $this->get_forum()->get_context();

        // Check if post is a special case
        if ($this->get_deleted() || $this->is_old_version()
            || $this->get_discussion()->is_deleted()) {
            $whynot = 'edit_notcurrentpost';
            return false;
        }

        // Check if discussion is different group
        if (!$this->get_discussion()->can_write_to_group()) {
            $whynot = 'edit_wronggroup';
            return false;
        }

        // Check if discussion is locked
        if ($this->get_discussion()->is_locked()) {
            $whynot = 'edit_locked';
            return false;
        }

        // Check the 'edit any' capability
        $editanypost = has_capability('mod/forumng:editanypost',
            $context, $userid);
        if (!$editanypost) {
            // If they don't have edit any, they must have either the
            // 'start discussion' or 'reply post' capability (the same
            // one they needed to create the post in the first place)
            if (($this->is_root_post() &&
                !has_capability('mod/forumng:startdiscussion', $context, $userid))
                && (!$this->is_root_post() &&
                !has_capability('mod/forumng:replypost', $context, $userid))) {
                $whynot = 'edit_nopermission';
                return false;
            }
        }

        // Check post belongs to specified user
        if (($this->get_user()->id != $userid) && !$editanypost) {
            $whynot = 'edit_notyours';
            return false;
        }

        // Check editing timeout
        if ((time() > $this->get_edit_time_limit()) && !$editanypost) {
            $whynot = 'edit_timeout';
            return false;
        }

        // Check read-only dates
        if ($this->get_forum()->is_read_only($userid)) {
            $whynot = 'edit_readonly';
            return false;
        }

        // OK! They're allowed to edit (whew)
        $whynot = '';
        return true;
    }

    /**
     * @param int $userid User ID or 0 for current
     * @return True if user can ignore the post editing time limit
     */
    public function can_ignore_edit_time_limit($userid=0) {
        $userid = mod_forumng_utils::get_real_userid($userid);
        $context = $this->get_forum()->get_context();
        return has_capability('mod/forumng:editanypost',
            $context, $userid);
    }

    /**
     * @return int Time limit after which users who don't have the edit-all
     *   permission are not allowed to edit this post (as epoch value)
     */
    public function get_edit_time_limit() {
        global $CFG;
        return $this->get_created() + $CFG->maxeditingtime;
    }

    /**
     * Checks that the user can edit this post - requiring all higher-level
     * access too.
     */
    public function require_edit() {
        // Check forum and discussion view permission, group access, etc.
        $this->discussion->require_view();

        // Check post edit
        $whynot = '';
        if (!$this->can_edit($whynot)) {
            print_error($whynot, 'forumng', 'discuss.php?' .
                    $this->discussion->get_link_params(mod_forumng::PARAM_HTML));
        }
    }

    /**
     * Checks that the user can reply to this post - requiring all higher-level
     * access too.
     */
    public function require_reply() {
        // Check forum and discussion view permission, group access, etc.
        $this->discussion->require_view();

        // Check post reply
        $whynot = '';
        if (!$this->can_reply($whynot)) {
            print_error($whynot, 'forumng', 'discuss.php?' .
                    $this->discussion->get_link_params(mod_forumng::PARAM_HTML));
        }
    }

    /**
     * @return bool asmoderator, null returned as ASMODERATOR_NO
     */
    public function get_asmoderator() {
        return is_null($this->postfields->asmoderator)
                ? mod_forumng::ASMODERATOR_NO : $this->postfields->asmoderator;
    }

    // Email
    // ////.

    /**
     * Obtains a version of this post as an email.
     * @param mod_forumng_post $inreplyto Message this one's replying to, or null
     *   if none
     * @param string &$subject Output: Message subject
     * @param string $text Output: Message plain text
     * @param string $html Output: Message HTML (or blank if not in HTML mode)
     * @param bool $ishtml True if in HTML mode
     * @param bool $canreply True if user can reply
     * @param bool $viewfullnames True if user gets to see full names even when
     *   these are normally hidden
     * @param string $lang Language of receiving user
     * @param number $timezone Time zone of receiving user
     * @param bool $digest True if in digest mode (does not include parent
     *   message or surrounding links).
     * @param bool $discussionemail True if digest is of a single disussion;
     *   includes 'post 1' information
     * @param array $extraoptions Set values here to add or override post
     *   display options
     */
    public function build_email($inreplyto, &$subject, &$text, &$html,
            $ishtml, $canreply, $viewfullnames, $lang, $timezone, $digest=false,
            $discussionemail=false, $extraoptions = array()) {
        global $CFG, $USER;

        $oldlang = $USER->lang;
        $USER->lang = $lang;

        $forum = $this->get_forum();
        $cmid = $forum->get_course_module_id();
        $course = $forum->get_course();
        $discussion = $this->get_discussion();

        // Get subject (may make DB query, unfortunately)
        $subject = $course->shortname . ': ' . format_string($forum->get_name(), true) . ': ' .
            format_string($this->get_effective_subject(true), true);

        $canunsubscribe = mod_forumng::SUBSCRIPTION_FORCED !=
            $forum->get_effective_subscription_option();

        // Header
        $text = '';
        $html = '';
        if (!$discussionemail && !$digest) {
            $html .= "\n<body id='forumng-email'>\n\n";
        }

        // Navigation bar (breadcrumbs)
        if (!$digest) {
            $text .= $forum->get_course()->shortname . ' -> ';
            $html .= "<div class='forumng-email-navbar'><a target='_blank' " .
              "href='$CFG->wwwroot/course/view.php?id=$course->id'>" .
              "$course->shortname</a> &raquo; ";

            $text .= format_string($forum->get_name(), true);
            $html .= "<a target='_blank' " .
                "href='$CFG->wwwroot/mod/forumng/view.php?" .
                $forum->get_link_params(mod_forumng::PARAM_HTML) . "'>" .
                format_string($forum->get_name(), true) . '</a>';

            // Makes a query :(
            if ($discussionsubject = $discussion->get_subject(true)) {
                $text .= ' -> ' . format_string($discussionsubject, true);
                $html .= " &raquo; <a target='_blank' " .
                    "href='$CFG->wwwroot/mod/forumng/discuss.php?" .
                    $discussion->get_link_params(mod_forumng::PARAM_HTML) . "'>" .
                    format_string($discussionsubject, true).'</a>';
            }

            $html .= '</div>';
        }

        // Main part of email
        $options = array(
            self::OPTION_EMAIL => true,
            self::OPTION_DIGEST => $digest ? true : false,
            self::OPTION_COMMAND_REPLY => ($canreply && !$digest),
            self::OPTION_VIEW_FULL_NAMES => $viewfullnames ? true : false,
            self::OPTION_TIME_ZONE => $timezone,
            self::OPTION_VISIBLE_POST_NUMBERS => $discussionemail,
            self::OPTION_USER_IMAGE => true);
        foreach ($extraoptions as $key => $value) {
            $options[$key] = $value;
        }
        $html .= $this->display(true, $options);
        $displaytext = $this->display(false, $options);
        // In digest, don't display mail divider if mail is blank (== deleted).t
        if ($displaytext !== '' || !$digest) {
            $text .= "\n" . mod_forumng_cron::EMAIL_DIVIDER;
        }
        $text .= $displaytext;

        // Now we need to display the parent post (if any, and if not in digest)
        if ($this->postfields->parentpostid && !$digest) {
            // Print the 'In reply to' heading
            $html .= '<h2>' . get_string('inreplyto', 'forumng') . '</h2>';

            $text .= "\n" . mod_forumng_cron::EMAIL_DIVIDER;
            $text .= get_string('inreplyto', 'forumng'). ":\n\n";

            // Get parent post (unfortunately this requires extra queries)
            $parent = self::get_from_id(
                $this->postfields->parentpostid,
                $this->get_forum()->get_course_module_id(), false);

            $options = array(
                self::OPTION_EMAIL => true,
                self::OPTION_NO_COMMANDS => true,
                self::OPTION_TIME_ZONE => $timezone);
            foreach ($extraoptions as $key => $value) {
                $options[$key] = $value;
            }
            $html .= $parent->display(true, $options);
            $text .= $parent->display(false, $options);
        }

        if (!$digest && $canunsubscribe) {
            $text .= "\n" . mod_forumng_cron::EMAIL_DIVIDER;
            $text .= get_string("unsubscribe", "forum");
            $text .= ": $CFG->wwwroot/mod/forumng/subscribe.php?" .
                $this->get_forum()->get_link_params(mod_forumng::PARAM_PLAIN) . "\n";

            $html .= "<hr size='1' noshade='noshade' />" .
                "<div class='forumng-email-unsubscribe'>" .
                "<a href='$CFG->wwwroot/mod/forumng/subscribe.php?" .
                $this->get_forum()->get_link_params(mod_forumng::PARAM_HTML) . "'>" .
                get_string('unsubscribe', 'forumng'). '</a></div>';
        }

        if (!$digest && !$discussionemail) {
            $html .= '</body>';
        }

        $USER->lang = $oldlang;

        // If not in HTML mode, chuck away the HTML version
        if (!$ishtml) {
            $html = '';
        }
    }

    // UI
    // //.

    /**
     * Displays this post.
     * @param array $html True for HTML format, false for plain text
     * @param array $options See forumngtype::display_post for details
     * @return string HTML or text of post
     */
    public function display($html, $options=null) {
        global $USER;

        // Initialise options array
        if (!is_array($options)) {
            $options = array();
        }
        // Default for other options
        if (!array_key_exists(self::OPTION_EMAIL, $options)) {
            $options[self::OPTION_EMAIL] = false;
        }
        if (!array_key_exists(self::OPTION_EXPORT, $options)) {
            $options[self::OPTION_EXPORT] = false;
        }
        if (!array_key_exists(self::OPTION_DIGEST, $options)) {
            $options[self::OPTION_DIGEST] = false;
        }
        if (!array_key_exists(self::OPTION_SINGLE_POST, $options)) {
            $options[self::OPTION_SINGLE_POST] = false;
        }
        if (!array_key_exists(self::OPTION_NO_COMMANDS, $options)) {
            $options[self::OPTION_NO_COMMANDS] = $options[self::OPTION_EXPORT];
        }
        if (!array_key_exists(self::OPTION_COMMAND_REPLY, $options)) {
            $options[self::OPTION_COMMAND_REPLY] =
                !$options[self::OPTION_NO_COMMANDS] && $this->can_reply($junk);
        }
        if (!array_key_exists(self::OPTION_COMMAND_EDIT, $options)) {
            $options[self::OPTION_COMMAND_EDIT] =
                !$options[self::OPTION_NO_COMMANDS] &&
                !$options[self::OPTION_EMAIL] && $this->can_edit($junk);
        }
        if (!array_key_exists(self::OPTION_COMMAND_DELETE, $options)) {
            $options[self::OPTION_COMMAND_DELETE] =
                !$options[self::OPTION_NO_COMMANDS] &&
                !$options[self::OPTION_EMAIL] && $this->can_delete($junk);
        }
        if (!array_key_exists(self::OPTION_COMMAND_REPORT, $options)) {
            $options[self::OPTION_COMMAND_REPORT] =
                !$options[self::OPTION_NO_COMMANDS] &&
                !$options[self::OPTION_EMAIL] && $this->can_alert($junk);
        }
        if (!array_key_exists(self::OPTION_COMMAND_DIRECTLINK, $options)) {
            $options[self::OPTION_COMMAND_DIRECTLINK] =
                !$options[self::OPTION_NO_COMMANDS] && !$options[self::OPTION_EMAIL] &&
                        $this->can_showdirectlink();
        }
        if (!array_key_exists(self::OPTION_COMMAND_UNDELETE, $options)) {
            $options[self::OPTION_COMMAND_UNDELETE] =
                !$options[self::OPTION_NO_COMMANDS] &&
                !$options[self::OPTION_EMAIL] && $this->can_undelete($junk);
        }
        if (!array_key_exists(self::OPTION_COMMAND_SPLIT, $options)) {
            $options[self::OPTION_COMMAND_SPLIT] =
                !$options[self::OPTION_NO_COMMANDS] &&
                !$options[self::OPTION_EMAIL] && $this->can_split($junk);
        }
        if (!array_key_exists(self::OPTION_COMMAND_HISTORY, $options)) {
            $options[self::OPTION_COMMAND_HISTORY] =
                !$options[self::OPTION_NO_COMMANDS] &&
                !$options[self::OPTION_EMAIL] && $this->can_view_history($junk);
        }
        if (!array_key_exists(self::OPTION_READ_TIME, $options)) {
            $options[self::OPTION_READ_TIME] = time();
        }
        if (!array_key_exists(self::OPTION_VIEW_FULL_NAMES, $options)) {
            // Default to whether current user has the permission in context
            $options[self::OPTION_VIEW_FULL_NAMES] = has_capability(
                'moodle/site:viewfullnames', $this->get_forum()->get_context());
        }
        if (!array_key_exists(self::OPTION_TIME_ZONE, $options)) {
            // Default to current user timezone
            $options[self::OPTION_TIME_ZONE] = $USER->timezone;
        }
        if (!array_key_exists(self::OPTION_RATINGS_EDIT, $options)) {
            $options[self::OPTION_RATINGS_EDIT] =
                !$options[self::OPTION_NO_COMMANDS] &&
                !$options[self::OPTION_EMAIL] && $this->can_rate();
        }
        if (!array_key_exists(self::OPTION_EXPANDED, $options)) {
            $options[self::OPTION_EXPANDED] = true;
        }
        if (!array_key_exists(self::OPTION_FLAG_CONTROL, $options)) {
            $options[self::OPTION_FLAG_CONTROL] =
                !$options[self::OPTION_NO_COMMANDS] &&
                !$options[self::OPTION_EMAIL] && $this->can_flag() &&
                $options[self::OPTION_EXPANDED];
        }
        if (!array_key_exists(self::OPTION_VIEW_DELETED_INFO, $options)) {
            $options[self::OPTION_VIEW_DELETED_INFO] =
                $this->can_view_deleted($junk) && !$options[self::OPTION_EXPORT];
        }
        if (!array_key_exists(self::OPTION_FULL_ADDRESSES, $options)) {
            $options[self::OPTION_FULL_ADDRESSES] =
                $options[self::OPTION_EXPORT] || $options[self::OPTION_EMAIL];
        }
        if (!array_key_exists(self::OPTION_DISCUSSION_SUBJECT, $options)) {
            $options[self::OPTION_DISCUSSION_SUBJECT] = false;
        }
        if (!array_key_exists(self::OPTION_SELECTABLE, $options)) {
            $options[self::OPTION_SELECTABLE] = false;
        }
        if (!array_key_exists(self::OPTION_VISIBLE_POST_NUMBERS, $options)) {
            $options[self::OPTION_VISIBLE_POST_NUMBERS] = false;
        }
        if (!array_key_exists(self::OPTION_USER_IMAGE, $options)) {
            $options[self::OPTION_USER_IMAGE] = true;
        }
        if (!array_key_exists(self::OPTION_PRINTABLE_VERSION, $options)) {
            $options[self::OPTION_PRINTABLE_VERSION] = false;
        }
        if (!array_key_exists(self::OPTION_RATINGS_VIEW, $options)) {
            $options[self::OPTION_RATINGS_VIEW] =
                ((!$options[self::OPTION_NO_COMMANDS] && !$options[self::OPTION_EMAIL]) ||
                    $options[self::OPTION_PRINTABLE_VERSION]) &&
                    $this->can_view_ratings();
        }
        $dojumps = !$options[self::OPTION_NO_COMMANDS] && !$options[self::OPTION_EMAIL] &&
                !$options[self::OPTION_SINGLE_POST];
        if (!array_key_exists(self::OPTION_JUMP_NEXT, $options)) {
            $options[self::OPTION_JUMP_NEXT] =
                    ($dojumps && $this->is_unread() && ($next=$this->get_next_unread()))
                    ? $next->get_id() : null;
        }
        if (!array_key_exists(self::OPTION_JUMP_PREVIOUS, $options)) {
            $options[self::OPTION_JUMP_PREVIOUS] =
                    ($dojumps && $this->is_unread() && $this->get_previous_unread())
                    ? $this->get_previous_unread()->get_id() : null;
        }
        if (!array_key_exists(self::OPTION_JUMP_PARENT, $options)) {
            $options[self::OPTION_JUMP_PARENT] =
                    ($dojumps && !$this->is_root_post()) ? $this->get_parent()->get_id() : null;
        }
        if (!array_key_exists(self::OPTION_FIRST_UNREAD, $options)) {
            $options[self::OPTION_FIRST_UNREAD] = !$options[self::OPTION_EMAIL] &&
                    !$options[self::OPTION_SINGLE_POST] && $this->is_unread() &&
                    !$this->get_previous_unread();
        }
        if (!array_key_exists(self::OPTION_UNREAD_NOT_HIGHLIGHTED, $options)) {
            $options[self::OPTION_UNREAD_NOT_HIGHLIGHTED] = false;
        }
        if (!array_key_exists(self::OPTION_IS_ANON, $options)) {
            if (self::get_asmoderator() == mod_forumng::ASMODERATOR_ANON &&
                    $this->get_forum()->get_can_post_anon()) {
                $options[self::OPTION_IS_ANON] = true;
                $options[self::OPTION_INDICATE_MODERATOR] = true;
            } else {
                $options[self::OPTION_IS_ANON] = false;
                $options[self::OPTION_INDICATE_MODERATOR] = false;
            }
        }
        if (self::get_asmoderator() == mod_forumng::ASMODERATOR_IDENTIFY) {
            $options[self::OPTION_INDICATE_MODERATOR] = true;
        } else {
            $options[self::OPTION_INDICATE_MODERATOR] = false;
        }
        if ($options[self::OPTION_IS_ANON] == true ||
                $options[self::OPTION_INDICATE_MODERATOR] == true) {
            if (!array_key_exists(self::OPTION_VIEW_ANON_INFO, $options)) {
                if ($this->get_forum()->can_post_anonymously()) {
                    $options[self::OPTION_VIEW_ANON_INFO] = true;
                } else {
                    $options[self::OPTION_VIEW_ANON_INFO] = false;
                }
            }
        }
        if (!array_key_exists(self::OPTION_PARTICIPATION, $options)) {
            $options[self::OPTION_PARTICIPATION] = false;
        }

        // Get forum type to do actual display
        $out = mod_forumng_utils::get_renderer();
        return $out->render_post($this, $html, $options);
    }

    public function display_with_children($options = null, $recursing = false) {
        global $USER;
        $this->require_children();

        if (!$recursing) {
            // Initialise options array
            if (!is_array($options)) {
                $options = array();
            }
            if (!array_key_exists(self::OPTION_EXPORT, $options)) {
                $options[self::OPTION_EXPORT] = false;
            }
            if (!array_key_exists(self::OPTION_CHILDREN_EXPANDED, $options)) {
                $options[self::OPTION_CHILDREN_EXPANDED] =
                    $options[self::OPTION_EXPORT];
            }
            if (!array_key_exists(self::OPTION_CHILDREN_COLLAPSED, $options)) {
                $options[self::OPTION_CHILDREN_COLLAPSED] = false;
            }
            if (!array_key_exists(self::OPTION_INCLUDE_LOCK, $options)) {
                $options[self::OPTION_INCLUDE_LOCK] = false;
            }
        }

        $export = $options[self::OPTION_EXPORT];

        // Decide ID of locked post to hide (if any)
        if ($this->discussion->is_locked() &&
            !$options[self::OPTION_INCLUDE_LOCK]) {
            $lockpostid = $this->discussion->get_last_post_id();
        } else {
            $lockpostid = 0;
        }

        // Display this post. It should be 'short' unless it is unread, parent
        // of unread post, top post, or flagged
        $options[self::OPTION_EXPANDED] = !$recursing ||
            ( !$options[self::OPTION_CHILDREN_COLLAPSED] &&
                ($this->is_unread()
                || $this->is_flagged()
                || $this->has_unread_child() || $this->forceexpand || !$recursing
                || $options[self::OPTION_CHILDREN_EXPANDED]));

        $output = $this->display(true, $options);

        // Are there any children?
        if (count($this->children) > 0 && !($lockpostid
            && count($this->children)==1
            && reset($this->children)->get_id()==$lockpostid)) {
            $output .= $export ? '<blockquote>' : '<div class="forumng-replies">';
            foreach ($this->children as $child) {
                if ($child->get_id()!=$lockpostid) {
                    $output .= $child->display_with_children($options, true);
                }
            }
            $output .= $export ? '</blockquote>' : '</div>';
        }

        if (!$recursing) {
            $out = mod_forumng_utils::get_renderer();
            $output = $out->render_post_group($this->get_discussion(), $output);
        }

        return $output;
    }

    /** @return string User picture HTML (for post author) */
    public function display_user_picture() {
        $out = mod_forumng_utils::get_renderer();
        return $out->user_picture($this->get_user(),
                array('courseid'=>$this->get_forum()->get_course_id()));
    }

    /**
     * Displays group pictures. This may make a (single) DB query if group
     * data has not yet been retrieved for this discussion.
     * @return string Group pictures HTML (empty string if none) for groups
     * that post author belongs to
     */
    public function display_group_pictures() {
        $groups = $this->discussion->get_user_groups($this->get_user()->id);
        if (count($groups) == 0) {
            return '';
        }
        return print_group_picture($groups, $this->get_forum()->get_course_id(),
            false, true);
    }

    /**
     * Displays this draft as an item on the list.
     * @param bool $last True if this is last in list
     * @return string HTML code for the item
     */
    public function display_flagged_list_item($last) {
        return $this->get_forum()->get_type()->display_flagged_list_item(
            $this, $last);
    }

    /**
     * Describes the post fields in JSON format. This is used for the AJAX
     * edit code.
     * @return string JSON structure listing key post fields.
     */
    public function prepare_edit_json() {
        global $USER;
        $forum = $this->get_forum();
        $filecontext = $forum->get_context(true);
        $fileoptions = array('subdirs'=>false, 'maxbytes'=>$forum->get_max_bytes());

        // Prepare draft area for attachments
        $draftitemid = 0;
        file_prepare_draft_area($draftitemid, $filecontext->id, 'mod_forumng', 'attachment',
                $this->get_id(), $fileoptions);

        // Prepare draft area for message files
        $messagedraftitemid = 0;
        $message = $this->get_raw_message();
        $message = file_prepare_draft_area($messagedraftitemid, $filecontext->id, 'mod_forumng',
                'message', $this->get_id(), $fileoptions, $message);

        // Get list of files for main attachment area
        $options = file_get_drafarea_files($draftitemid, '/');
        $usercontext = context_user::instance($USER->id);
        $fs = get_file_storage();
        $files = $fs->get_area_files($usercontext->id, 'user', 'draft',
                $options->itemid, 'id', false);
        $options->filecount = count($files);

        // Get list of files for message area
        $messageoptions = file_get_drafarea_files($messagedraftitemid, '/');
        $files = $fs->get_area_files($usercontext->id, 'user', 'draft',
                $messageoptions->itemid, 'id', false);
        $messageoptions->filecount = count($files);

        // Put everything together with basic data
        $basicvalues = (object)array('subject'=>$this->get_subject(),
            'message'=>$message, 'format'=>$this->get_format(),
            'setimportant'=>$this->is_important() ? 1 : 0);
        $basicvalues->options = $options;
        $basicvalues->messageoptions = $messageoptions;

        // Add time limit info
        $timelimit = $this->can_ignore_edit_time_limit()
            ? 0 : $this->get_edit_time_limit();
        if ($timelimit) {
            $basicvalues->editlimit = $timelimit-time();
            $basicvalues->editlimitmsg = get_string('editlimited', 'forumng',
                    userdate($timelimit-30, get_string('strftimetime', 'langconfig')));
        } else {
            $basicvalues->editlimit = 0;
        }

        // JSON encoding
        return json_encode($basicvalues);
    }

    /**
     * Prints AJAX version of the post to output, and exits.
     * @param mixed $postorid Post object or ID of post
     * @param int $cloneid If $postorid is an id, a clone id may be necessary
     *   to construct the post
     * @param array $options Post options if any
     * @param int $postid ID of post
     */
    public static function print_for_ajax_and_exit($postorid, $cloneid=null,
        $options=array()) {
        header('Content-Type: text/plain');
        echo self::get_post_html($postorid, $cloneid, $options);
        exit;
    }

    /**
     * Internal function that obtains HTML of a single post.
     * @param mixed $postorid Post object or ID of post
     * @param int $cloneid If $postorid is an id, a clone id may be necessary
     *   to construct the post
     * @param array $options Post options if any
     * @return string HTML of post
     */
    private static function get_post_html($postorid, $cloneid=null,
            $options=array()) {
        if ($postorid === 0) {
            // No post to show.
            return '<div></div>';
        }
        if (is_object($postorid)) {
            $post = $postorid;
        } else {
            $post = self::get_from_id($postorid, $cloneid, true);
        }
        return trim($post->display(true, $options));
    }

    /**
     * Prints version of the post suitable for being read out of the iframe
     * to output inside a hidden div with a script tag to inform the parent,
     * then exits.
     * @param mixed $postorid Post object or ID of post
     * @param int $cloneid If $postorid is an id, a clone id may be necessary
     *   to construct the post
     * @param array $options Post options if any
     * @param int $postid ID of post
     */
    public static function print_for_iframe_and_exit($postorid, $cloneid=null,
            $options=array()) {
        $posthtml = self::get_post_html($postorid, $cloneid, $options);
        $script = html_writer::tag('script', 'window.parent.iframe_success(window);',
                array('type' => 'text/javascript'));

        echo '<!DOCTYPE html>';
        echo html_writer::tag('html',
                html_writer::tag('head', html_writer::tag('title', 'Result') .
                html_writer::tag('body',
                    html_writer::tag('div', $posthtml, array('style' => 'display:none')) .
                    $script)));
        exit;
    }

    // Completion
    /*///////////*/

    public function update_completion($positive) {
        // Do nothing if completion isn't enabled
        if (!$this->get_forum()->is_auto_completion_enabled(true)) {
            return;
        }
        $course = $this->get_forum()->get_course();
        $cm = $this->get_forum()->get_course_module();
        $completion = new completion_info($course);
        $completion->update_state($cm, $positive ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE,
                $this->postfields->userid);
    }

    /**
     * Append hash after image link found in the document.
     *
     * @param string $html HTML string.
     * @return string mixed HTML string.
     */
    protected function add_hash_to_image($html) {
        global $CFG;
        $salt = context_course::instance($this->get_forum()->get_course()->id)->id;

        $doc = new DOMDocument();
        $previousinternalerrors = libxml_use_internal_errors(true);
        $doc->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($previousinternalerrors);

        $tags = $doc->getElementsByTagName('img');

        // Image source will be replace.
        $srcbefore = array();
        // Image source will replace.
        $srcafter = array();
        $internaldomain = $this->get_domain_from_url($CFG->wwwroot);
        foreach ($tags as $tag) {
            $src = $tag->getAttribute('src');
            $urldomain = $this->get_domain_from_url($src);
            // Only add the hash to internal image link.
            if ($internaldomain === $urldomain) {
                $srcarr = explode('/', $src);
                $arrlength = count($srcarr);
                // Get from last to first in case the domain has more slash.
                $activity = $srcarr[$arrlength - 4];
                $area = $srcarr[$arrlength - 3];
                // This image must belong to message area of forumng.
                if ($activity == 'mod_forumng' && $area == 'message') {
                    $imagename = urldecode($srcarr[$arrlength - 1]);
                    $srcbefore[] = $src;
                    $srcafter[] = $src . '/' . sha1($imagename . $salt);
                }
            }
        }

        return str_replace($srcbefore, $srcafter, $html);
    }

    /**
     * @param string $url
     * @return string mixed
     */
    protected function get_domain_from_url($url) {
        $host = parse_url($url, PHP_URL_HOST);
        if (!$host) {
            $host = explode('/', $host);
            $host = $host[0];
        }
        $regex = '/^www.(.*)/';
        return preg_replace($regex, '$1', $host);
    }
}
