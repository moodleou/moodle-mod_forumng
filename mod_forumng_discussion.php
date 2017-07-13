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
 * Represents a forum discussion.
 * @see mod_forumng_discussion_list
 * @see forum
 * @see mod_forumng_post
 * @package mod
 * @subpackage forumng
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_forumng_discussion {
    /** Posts are cached for 10 minutes */
    const CACHE_TIMEOUT = 600;

    /** Max number of discussions to cache in session */
    const CACHE_COUNT = 2;

    /**
     * Max size (total bytes of messages) before not caching discussion.
     * I analysed our current discussions. At time of writing, there are 50 that
     * are bigger than 200,000 bytes (ok characters but close enough) and
     * 336,168 that are smaller, so I think this should generally be OK.
     */
    const CACHE_MAX_SIZE = 200000;

    /** Used for edit_settings when not changing a value */
    const NOCHANGE = -999;

    /**
     * Used in the numreadposts field to indicate that read information is not
     * stored because a discussion is too old.
     */
    const PAST_SELL_BY = 1000000;

    // Object variables and accessors
    /*///////////////////////////////*/

    private $forum, $discussionfields, $full, $rootpost, $timeretrieved,
        $pretendtimeread, $foruserid;

    private $postscache, $groupscache, $incache;

    private $ismakingsearchchange;

    private $totalsize = 0;

    /** @return mod_forumng The forum that this discussion comes from */
    public function get_forum() {
        return $this->forum;
    }

    /** @return object Moodle course object */
    public function get_course() {
        return $this->forum->get_course();
    }

    /** @return object Moodle course-module object */
    public function get_course_module() {
        return $this->forum->get_course_module();
    }

    /** @return int ID of this discussion */
    public function get_id() {
        return $this->discussionfields->id;
    }
    /** @return int Group ID for this discussion or null if any group */
    public function get_group_id() {
        return $this->discussionfields->groupid;
    }
    /** @return int Group name for this discussion */
    public function get_group_name() {
        if (is_null($this->discussionfields->groupid)) {
            return get_string('allparticipants');
        } else {
            return $this->discussionfields->groupname;
        }
    }
    /**
     * Obtains subject. Note this results in a DB query if the discussion
     * was not fully loaded in the first place.
     * @param bool $expectingquery True if code expects there to be a query;
     *   this just avoids a debugging() call.
     * @return string Subject or null if none
     */
    public function get_subject($expectingquery = false) {
        global $DB;
        if (!isset($this->discussionfields->subject)) {
            if (!$expectingquery) {
                debugging('This get method made a DB query; if this is expected,
                    set the flag to say so', DEBUG_DEVELOPER);
            }
            $this->discussionfields->subject = $DB->get_field(
              'forumng_posts', 'subject', array('id' => $this->discussionfields->postid));
        }
        return $this->discussionfields->subject;
    }

    /**
     * For use only by mod_forumng_post when updating in-memory representation
     * after an edit.
     * @param string $subject New subject
     */
    public function hack_subject($subject) {
        $this->discussionfields->subject = $subject;
    }

    /** @return bool True if discussion is 'sticky' */
    public function is_sticky() {
        return $this->discussionfields->sticky ? true : false;
    }

    /** @return bool True if discussion is locked */
    public function is_locked() {
        return $this->discussionfields->locked ? true : false;
    }

    /** @return bool True if discussion is auto locked */
    public function is_auto_locked() {
        if ($this->discussionfields->locked == 2) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return int Time this discussion becomes visible (seconds since epoch)
     *  or null if no start time
     */
    public function get_time_start() {
        return $this->discussionfields->timestart;
    }

    /**
     * @return int Time this discussion stops being visible (seconds since
     *  epoch) or null if no end time
     */
    public function get_time_end() {
        return $this->discussionfields->timeend;
    }

    /**
     * Obtains details of user who originally posted this discussion.
     * @return object Moodle user object (selected fields)
     */
    public function get_poster() {
        $this->check_full();
        return $this->discussionfields->firstuser;
    }

    /**
     * Obtains details of user who posted the last reply to this discussion.
     * @return object Moodle user object (selected fields)
     */
    public function get_last_post_user() {
        $this->check_full();
        return $this->discussionfields->lastuser;
    }

    /**
     * Obtains ID of last post
     * @return int ID of last post
     */
    public function get_last_post_id() {
        return $this->discussionfields->lastpostid;
    }

    /**
     * If the discussion is locked, this function returns the explanatory post.
     * Will retrieve discussion posts if not already obtained.
     * @return mod_forumng_post Lock post or null if none
     */
    public function get_lock_post() {
        if ($this->is_locked() && !$this->is_auto_locked() ) {
            return $this->get_root_post()->find_child(
                $this->discussionfields->lastpostid);
        } else {
            return null;
        }

    }

    /**
     * Checks that the discussion is fully loaded. There are two load states: full
     * (includes all data retrieved when loading discussion list) and partial
     * (includes only minimal data required when creating discussion). Note that
     * full data state does not imply that the actual posts are in memory yet,
     * post storage is tracked separately.
     * @throws coding_exception If discussion is not loaded
     */
    private function check_full() {
        if (!$this->full) {
            throw new coding_exception('This function is not available unless
              the discussion has been fully loaded.');
        }
    }

    /**
     * @return string URL of this discussion for log table, relative to the
     *   module's URL
     */
    public function get_log_url() {
        return 'discuss.php?' . $this->get_link_params(mod_forumng::PARAM_PLAIN);
    }

    /**
     * @return mixed Number of unread posts as integer, possibly 0; or empty
     *   string if unread data is no longer tracked for this post
     */
    public function get_num_unread_posts() {
        if (!isset($this->discussionfields->numreadposts)) {
            throw new coding_exception('Unread post count not obtained');
        }
        if ($this->discussionfields->numreadposts == self::PAST_SELL_BY) {
            return '';
        } else {
            return $this->discussionfields->numposts
                - $this->discussionfields->numreadposts;
        }
    }

    /**
     * @return int Number of discussions
     */
    public function get_num_posts() {
        if (!isset($this->discussionfields->numposts)) {
            throw new coding_exception('Post count not obtained');
        }
        return $this->discussionfields->numposts;
    }

    /**
     * @return int Time of last post
     */
    public function get_time_modified() {
        if (!isset($this->discussionfields->timemodified)) {
            throw new coding_exception('Time modified not obtained');
        }
        return $this->discussionfields->timemodified;
    }

    /**
     * @return moodle_url URL of discussion
     */
    public function get_moodle_url() {
        return new moodle_url('/mod/forumng/discuss.php', $this->get_link_params_array());
    }

    /**
     * @return string URL of this discussion
     */
    public function get_url($type = mod_forumng::PARAM_PLAIN) {
        global $CFG;
        return $CFG->wwwroot . '/mod/forumng/discuss.php?' .
                $this->get_link_params($type);
    }

    /**
     * Obtains details of user who posted the first post to this discussion.
     * @return object Moodle user object (selected fields)
     */
    public function get_poster_anon() {
        $this->check_full();
        return is_null($this->discussionfields->firstasmoderator)
                ? mod_forumng::ASMODERATOR_NO : $this->discussionfields->firstasmoderator;
    }

    /**
     * Obtains details of user who posted the last post to this discussion.
     * @return object Moodle user object (selected fields)
     */
    public function get_last_post_anon() {
        $this->check_full();
        return is_null($this->discussionfields->lastasmoderator)
                ? mod_forumng::ASMODERATOR_NO : $this->discussionfields->lastasmoderator;
    }

    /*
     * @return int boolean 0 or 1 flagged
     */
    public function get_flagged() {
        return $this->discussionfields->flagged;
    }

    /**
     * @return bool True if can flag
     */
    public function can_flag() {
        // The guest user cannot flag.
        if (isguestuser()) {
            return false;
        }
        // Cannot flag for deleted discussion unless already flagged.
        if ($this->is_deleted() && (!$this->is_flagged())) {
            return false;
        }
        return true;
    }

    /** @return bool True if post is flagged by current user */
    public function is_flagged() {
        if (!property_exists($this->discussionfields, 'flagged')) {
            throw new coding_exception('Flagged information not available here');
        }
        return $this->discussionfields->flagged ? true : false;
    }

    /**
     * @param bool $flag True to set flag
     * @param int $userid User ID or 0 for current
     */
    public function set_flagged($flag, $userid = 0) {
        global $DB;

        $userid = mod_forumng_utils::get_real_userid($userid);
        if ($flag) {
            // Check there is not already a row.
            if (!$DB->record_exists('forumng_flags',
                    array('discussionid' => $this->get_id(), 'userid' => $userid))) {
                // Insert new row.
                $newflag = (object) array('discussionid' => $this->get_id(),
                        'userid' => $userid, 'postid' => 0, 'flagged' => time());
                $DB->insert_record('forumng_flags', $newflag);
                $this->discussionfields->flagged = 1;
            }
        } else {
            $DB->delete_records('forumng_flags',
                    array('discussionid' => $this->get_id(), 'userid' => $userid));
            $this->discussionfields->flagged = 0;
        }

    }

    // Factory method
    /*///////////////*/

    /**
     * Creates a forum discussion object, forum object, and all related data from a
     * single forum discussion ID. Intended when entering a page which uses
     * discussion ID as a parameter.
     * @param int $id ID of forum discussion
     * @param int $cloneid ID of clone (or 0 or mod_forumng::CLONE_DIRECT as relevant)
     * @param int $userid User ID; 0 = current user, -1 = do not get unread data
     * @param bool $usecache True if cache should be used (if available)
     * @param bool $storecache True if newly-retrieved discussion should be
     *   stored to cache
     * @return mod_forumng_discussion Discussion object
     */
    public static function get_from_id($id, $cloneid, $userid=0,
            $usecache=false, $storecache=false) {
        if ($usecache) {
            global $SESSION;
            self::check_cache();
            foreach ($SESSION->forumng_cache->discussions as $info) {
                if ($info->userid==mod_forumng_utils::get_real_userid($userid) &&
                        $info->id==$id && $info->cloneid==$cloneid) {
                    $info->lastused = time();
                    $result = self::create_from_cache($info);
                    if ($result) {
                        return $result;
                    }
                }
            }
        }
        return self::get_base('fd.id=?', array($id), $userid, $storecache, $cloneid);
    }

    /**
     * Creates a forum discussion object, forum object, and all related data from a
     * forum post ID (the discussion related to that post). Intended when
     * requesting a post if we want 'context' data too
     * @param int $postid ID of forum post
     * @param int $userid User ID; 0 = current user, -1 = do not get unread data
     * @param bool $usecache True if cache should be used (if available)
     * @param bool $storecache True if newly-retrieved discussion should be
     *   stored to cache
     * @return mod_forumng_discussion Discussion object
     */
    public static function get_from_post_id($postid, $cloneid, $userid=0,
            $usecache=false, $storecache=false) {
        if ($usecache) {
            global $SESSION;
            self::check_cache();
            foreach ($SESSION->forumng_cache->discussions as $info) {
                if ($info->userid!=mod_forumng_utils::get_real_userid($userid)) {
                    continue;
                }
                // Check whether this discussion contains the desired
                // post
                if (in_array($postid, $info->posts)) {
                    $info->lastused = time();
                    $result = self::create_from_cache($info);
                    if ($result) {
                        return $result;
                    }
                }
            }
        }

        return self::get_base("fd.id =
            (SELECT discussionid FROM {forumng_posts} WHERE id = ?)", array($postid),
            $userid, $storecache, $cloneid);
    }

    private static function get_base($where, $whereparams, $userid, $cache, $cloneid) {
        // If user isn't logged in, don't get unread data
        if (!isloggedin()) {
            $userid = -1;
        }
        // Get discussion data (including read status)
        $rs = self::query_discussions($where, $whereparams, $userid, 'id', 0, 1, null, true);
        $discussionfields = false;
        if (!$rs->valid()) {
            throw new dml_exception('Unable to retrieve relevant discussion');
        }
        $discussionfields = $rs->current();
        $rs->close();

        // Get forum and construct discussion
        $forum = mod_forumng::get_from_id($discussionfields->forumngid, $cloneid);
        $result = new mod_forumng_discussion($forum, $discussionfields, true,
            mod_forumng_utils::get_real_userid($userid));
        if ($cache) {
            $result->cache($userid);
        }
        return $result;
    }

    // Discussion caching
    /*///////////////////*/

    /**
     * Caches the specified discussion in session.
     * Replaces the least-recently-used, if the number exceeds the
     * limit.
     * @param mod_forumng_discussion $discussion
     */
    private function cache() {
        global $SESSION;
        self::check_cache();

        if (!$this->full) {
            // Only cache 'full' data
            return;
        }

        if ($this->totalsize > self::CACHE_MAX_SIZE) {
            // Don't cache huge discussions
            return;
        }

        // Remove any existing data for this discussion id
        $oldest = -1;
        $oldesttime = 0;
        foreach ($SESSION->forumng_cache->discussions as $key => $info) {
            if ($info->id == $this->get_id()) {
                unset($SESSION->forumng_cache->discussions[$key]);
            } else {
                if ($oldest==-1 || $info->lastused<$oldesttime) {
                    $oldest = $key;
                }
            }
        }

        // If there are too many, discard oldest
        if (count($SESSION->forumng_cache->discussions) > self::CACHE_COUNT) {
            unset($SESSION->forumng_cache->discussions[$oldest]);
        }

        // Cache this data
        $info = new stdClass;
        $info->lastused = time();
        $info->id = $this->get_id();
        $info->timemodified = $this->get_time_modified();
        $info->discussionfields = serialize($this->discussionfields);
        $info->postscache = $this->postscache;
        $info->groupscache = serialize($this->groupscache);
        $info->userid = $this->get_unread_data_user_id();
        $info->posts = array();
        $info->settingshash = $this->get_forum()->get_settings_hash();
        $info->cloneid = $this->get_forum()->get_course_module_id();

        if ($this->rootpost) {
            $this->rootpost->list_child_ids($info->posts);
        }

        $this->incache = $info;
        $SESSION->forumng_cache->discussions[] = $info;
    }

    /**
     * Removes any instances of this discussion from current user's cache.
     * Used so that current user sees changes immediately (other users will
     * still wait 10 minutes).
     */
    public function uncache() {
        global $SESSION;
        if (isset($SESSION->forumng_cache->discussions)) {
            foreach ($SESSION->forumng_cache->discussions as $key => $info) {
                if ($info->id == $this->get_id()) {
                    unset($SESSION->forumng_cache->discussions[$key]);
                }
            }
        }
    }

    /**
     * Obtains a discussion from the cache.
     * @param object $info Object from session cache
     * @return mod_forumng_discussion New discussion object or null if there is a
     *   problem and you should re-cache
     */
    private static function create_from_cache($info) {
        $discussionfields = unserialize($info->discussionfields);
        $forum = mod_forumng::get_from_id($discussionfields->forumngid, $info->cloneid);
        if ($forum->get_settings_hash() != $info->settingshash) {
            return null;
        }

        $result = new mod_forumng_discussion(
            $forum, $discussionfields, true, $info->userid);

        $result->groupscache = unserialize($info->groupscache);
        $result->postscache = $info->postscache;
        $result->incache = true;
        return $result;
    }

    /**
     * Checks whether the current discussion object is newer (contains
     * newer posts) than an equivalent discussion stored in the cache.
     * If so, removes the cached value.
     */
    public function maybe_invalidate_cache() {
        global $SESSION;
        self::check_cache();

        foreach ($SESSION->forumng_cache->discussions as $key => $info) {
            if ($info->id == $this->get_id()
                && $info->timemodified != $this->get_time_modified()) {
                unset($SESSION->forumng_cache->discussions[$key]);
            }
        }
    }

    /**
     * Updates the discussion cache, discarding old data.
     */
    public static function check_cache() {
        global $SESSION;

        // Check cache variable exists
        if (!isset($SESSION->forumng_cache)) {
            $SESSION->forumng_cache = new stdClass;
        }
        if (!isset($SESSION->forumng_cache->discussions)) {
            $SESSION->forumng_cache->discussions = array();
        }

        // Remove old cache data
        foreach ($SESSION->forumng_cache->discussions as $key => $info) {
            if (time() - $info->lastused > self::CACHE_TIMEOUT) {
                unset($SESSION->forumng_cache->discussions[$key]);
            }
        }
    }

    // Object methods
    /*///////////////*/

    /**
     * Initialises the discussion. Used internally by forum - don't call directly.
     * @param mod_forumng $forum Forum object
     * @param object $discussionfields Discussion fields from db table (plus
     *   some extra fields provided by query in forum method)
     * @param bool $full True if the parameter includes 'full' data via the
     *   various joins, false if it's only the fields from the discussions table.
     * @param int $foruserid The user ID that was used to obtain the discussion
     *   data (may be -1 for no unread data)
     */
    public function __construct($forum, $discussionfields, $full, $foruserid) {
        if ($full && !isset($discussionfields->firstuser)) {
            // Extract the user details into Moodle user-like objects
            $discussionfields->firstuser = mod_forumng_utils::extract_subobject($discussionfields,
                    'fu_');
            $discussionfields->lastuser = mod_forumng_utils::extract_subobject($discussionfields,
                    'lu_');
        }

        $this->forum = $forum;
        $this->discussionfields = $discussionfields;
        $this->full = $full;
        $this->foruserid = $foruserid;
        $this->rootpost = null;
        $this->timeretrieved = time();
        $this->postscache = null;
        $this->groupscache = null;
        $this->ismakingsearchchange = false;
    }

    /**
     * Fills discussion data (loaded from db) for given user.
     * @param int $foruserid User ID or -1 if no unread data is required
     * @param bool $usecache True to use cache if available
     * @param bool $storecache True to sstore retrieved value in cache
     */
    public function fill($foruserid=0, $usecache=false, $storecache=false) {
        if ($this->full && ($this->foruserid == $foruserid || $foruserid==-1)) {
            return;
        }
        $new = self::get_from_id($this->discussionfields->id,
                $this->get_forum()->get_course_module_id(), $foruserid, $usecache, $storecache);
        foreach (get_class_vars('mod_forumng_discussion') as $field => $dontcare) {
            $this->{$field} = $new->{$field};
        }
    }

    /**
     * Obtains the root post of the discussion. This actually requests all
     * posts from the database; the first is returned, but others are
     * accessible from methods in the first.
     * If available, cached information is used unless
     * you set $usecache to false. The cache is stored within the discussion
     * object so will not persist beyond a request unless you make the
     * discussion object persist too.
     * @param bool $usecache True to use cache if available, false to
     *    request fresh data
     * @param int $userid User ID to get user-specific data (initially, post
     *   flags) for; 0 = current
     * @return mod_forumng_post Post object
     */
    public function get_root_post($usecache=true, $userid=0) {
        global $CFG, $USER;
        require_once($CFG->dirroot . '/rating/lib.php');
        if (!$usecache || !$this->rootpost) {
            if (!$usecache || !$this->postscache) {
                $read = !mod_forumng::mark_read_automatically($userid);
                // Retrieve most posts in the discussion - even deleted
                // ones. These are necessary in case somebody deletes a post that has
                // replies. They will display as 'deleted post'. We don't retrieve
                // old versions of edited posts. Posts are retrieved in created order
                // so that the order of replies remains constant when we build the tree.
                $posts = mod_forumng_post::query_posts('fp.discussionid=? AND fp.oldversion=0',
                        array($this->discussionfields->id), 'fp.created',
                        $this->forum->has_ratings(), true, false, $userid, false, false, '', '', $read);
                // Load standard ratings.
                if ($this->get_forum()->get_enableratings() == mod_forumng::FORUMNG_STANDARD_RATING) {
                    // If grading is 'No grading' or 'Teacher grades students'.
                    if ($this->get_forum()->get_grading() == mod_forumng::GRADING_NONE ||
                                    $this->get_forum()->get_grading() == mod_forumng::GRADING_MANUAL ) {
                        // Set the aggregation method.
                        if ($this->get_forum()->get_rating_scale() > 0) {
                            $aggregate = RATING_AGGREGATE_AVERAGE;
                        } else {
                            $aggregate = RATING_AGGREGATE_COUNT;
                        }
                    } else {
                        $aggregate = $this->get_forum()->get_grading();
                    }
                    $ratingoptions = new stdClass();
                    $ratingoptions->context = $this->get_forum()->get_context();
                    $ratingoptions->component = 'mod_forumng';
                    $ratingoptions->ratingarea = 'post';
                    $ratingoptions->items = $posts;
                    $ratingoptions->aggregate = $aggregate;
                    $ratingoptions->scaleid = $this->get_forum()->get_rating_scale();
                    $ratingoptions->userid = $USER->id;
                    $ratingoptions->assesstimestart = $this->forum->get_ratingfrom();
                    $ratingoptions->assesstimefinish = $this->forum->get_ratinguntil();
                    $ratingoptions->returnurl = $this->get_moodle_url();

                    $rm = new rating_manager();
                    $posts = $rm->get_ratings($ratingoptions);
                }
                $this->postscache = serialize($posts);
            } else {
                $posts = unserialize($this->postscache);
            }

            // Add numbers to posts
            $i = 1;
            foreach ($posts as $post) {
                $post->number = $i++;
            }

            // Obtain post relationships
            $children = array();
            foreach ($posts as $id => $fields) {
                if (!array_key_exists($fields->parentpostid, $children)) {
                    $children[$fields->parentpostid] = array();
                }
                $children[$fields->parentpostid][] = $id;
            }

            // Recursively build posts
            $this->rootpost = $this->build_posts($posts, $children,
                $this->discussionfields->postid, null);

            // Update the 'next/previous' unread lists stored in posts
            if ($this->get_unread_data_user_id() != -1) {
                $linear = array();
                $this->rootpost->build_linear_children($linear);
                $nextunread = array();
                $dump = '';
                foreach ($linear as $index => $post) {
                    $nextunread[$index] = null;
                    if ($post->is_unread() &&
                            (!$post->get_deleted() || $post->can_undelete($dump))) {
                        for ($j = $index-1; $j>=0; $j--) {
                            if ($nextunread[$j]) {
                                break;
                            }
                            $nextunread[$j] = $post;
                        }
                    }
                }
                $previous = null;
                foreach ($linear as $index => $post) {
                    $post->set_unread_list($nextunread[$index], $previous);
                    if ($post->is_unread() &&
                            (!$post->get_deleted() || $post->can_undelete($dump))) {
                        $previous = $post;
                    }
                }

                // Update cached version to include this data
                if ($this->incache) {
                    $this->cache();
                }
            }
        }

        return $this->rootpost;
    }

    /**
     * Internal method. Queries for a number of discussions, including additional
     * data about unread posts etc. Returns the database result.
     * @param string $conditions WHERE clause (may refer to aliases 'd' for discussion)
     * @param array $conditionparams Parameters for conditions
     * @param int $userid User ID, 0 = current user, -1 = no unread data is needed
     * @param string $orderby ORDER BY clause
     * @param int $limitfrom Limit on results
     * @param int $limitnum Limit on results
     * @param mod_forumng $typeforum If set, this forum is used to potentially restrict
     *   the results based on forum type limits
     * @param boolean $flags set to indicate that flagged discussions are to be returned
     * @param boolean hastag set to indicate that tagged discussions are to be returned
     * @return adodb_recordset Database query results
     */
    public static function query_discussions($conditions, $conditionparams, $userid, $orderby,
        $limitfrom='', $limitnum='', $typeforum=null, $flags = false, $hastag = false) {
        global $USER, $DB;

        // For read tracking, we get a count of total number of posts in
        // discussion, and total number of read posts in the discussion (this
        // is so we can display the number of UNread posts, but the query
        // works that way around because it will return 0 if no read
        // information is stored).
        if (mod_forumng::enabled_read_tracking() && $userid!=-1) {
            if (!$userid) {
                $userid = $USER->id;
            }
            $deadline = mod_forumng::get_read_tracking_deadline();
            $readjoin1 = "";
            $readwhere1 = "";
            $readtrackingparams = array($deadline, $userid, $userid, $deadline);
            $readtrackingjoinparams = array($userid);
            if (!mod_forumng::mark_read_automatically($userid)) {
                // Ind Mark read - check individual read_posts state.
                $readjoin1 = "LEFT JOIN {forumng_read_posts} frp2 on frp2.postid = fp3.id AND frp2.userid = ?";
                $readwhere1 = "OR frp2.id IS NOT NULL";
                $readtrackingparams = array($deadline, $userid, $userid, $userid, $deadline);
            }
            // Get unread count only when last added post is newer than deadline.
            // When PAST_SELL_BY, posts modified later than last will be unread but not picked up.
            $readtracking = "
                    , (CASE WHEN fplast.modified IS NOT NULL AND fplast.modified < ? THEN " .
                                self::PAST_SELL_BY . " ELSE (SELECT COUNT(1)
                        FROM {forumng_posts} fp3
                  $readjoin1
                       WHERE fp3.discussionid = fd.id AND fp3.oldversion = 0
                         AND fp3.deleted = 0
                         AND (fp3.modified < fr.time OR fp3.edituserid = ?
                            $readwhere1
                            OR (fp3.edituserid IS NULL AND fp3.userid = ?)
                            OR fp3.modified < ?)) END) AS numreadposts,
                   fr.time AS timeread";
            // Join read info, get posts not authored by user: get latest modified post time.
            $readtrackingjoin = "LEFT JOIN {forumng_read} fr ON fd.id = fr.discussionid AND fr.userid = ?";
        } else {
            $readtracking = ", 0 AS numreadposts, NULL AS timeread";
            $readtrackingjoin = "";
            $readtrackingparams = array();
            $readtrackingjoinparams = array();
        }

        $order = ($orderby) ? 'ORDER BY ' . $orderby : '';

        // Handle forum type restriction
        $typejoin = '';
        $typeparams = array();
        $flagsjoin = '';
        $flagsquery = '';
        $flagparams = array();
        if ($typeforum && $userid != -1) {
            $type = $typeforum->get_type();
            if ($type->has_unread_restriction()) {
                list($restrictionsql, $restrictionparams) =
                        $type->get_unread_restriction_sql($typeforum, $userid);
            } else {
                $restrictionsql = false;
            }
            if ($restrictionsql) {
                $typejoin = "
    INNER JOIN {forumng} f ON f.id = fd.forumngid
    INNER JOIN {course} c ON c.id = f.course
    INNER JOIN {course_modules} cm ON cm.instance = f.id AND cm.course = f.course
    INNER JOIN {modules} m ON m.id = cm.module";
                $conditions .= " AND m.name = 'forumng' AND $restrictionsql";
                $conditionparams = array_merge($conditionparams, $restrictionparams);
            }
        }

        if ($flags && $userid != -1) {
            $flagsjoin = "LEFT JOIN {forumng_flags} ff ON ff.discussionid = fd.id AND ff.userid = ?";
            $flagsquery = ', ff.flagged';
            $flagparams = array($userid);
        }

        // Tag join sql if needed.
        $tagjoin = '';
        if ($hastag) {
            $tagjoin = "LEFT JOIN {tag_instance} ti on ti.itemid = fd.id
                            AND ti.itemtype = 'forumng_discussions'
                            AND ti.component = 'mod_forumng'";
        }

        // Main query. This retrieves:
        // * Basic discussion information.
        // * Information about the discussion that is obtained from the first and
        //   last post.
        // * Information about the users responsible for first and last post.
        $rs = $DB->get_recordset_sql("
SELECT * FROM (SELECT
    fd.*,
    fpfirst.created AS timecreated,
    fplast.modified AS timemodified,
    fpfirst.subject AS subject,
    fplast.subject AS lastsubject,
    fplast.message AS lastmessage,
    fpfirst.asmoderator AS firstasmoderator,
    fplast.asmoderator AS lastasmoderator,
    ".mod_forumng_utils::select_username_fields('fu').",
    ".mod_forumng_utils::select_username_fields('lu').",
    (SELECT COUNT(1)
        FROM {forumng_posts} fp2
        WHERE fp2.discussionid = fd.id AND fp2.deleted = 0 AND fp2.oldversion = 0)
        AS numposts,
    g.name AS groupname
    $readtracking
    $flagsquery
FROM
    {forumng_discussions} fd
    INNER JOIN {forumng_posts} fpfirst ON fd.postid = fpfirst.id
    INNER JOIN {user} fu ON fpfirst.userid = fu.id
    INNER JOIN {forumng_posts} fplast ON fd.lastpostid = fplast.id
    INNER JOIN {user} lu ON fplast.userid = lu.id
    LEFT JOIN {groups} g ON g.id = fd.groupid
    $readtrackingjoin
    $typejoin
    $flagsjoin
    $tagjoin
WHERE
    $conditions) x $order
",
                array_merge($readtrackingparams, $readtrackingjoinparams, $flagparams, $conditionparams),
                $limitfrom, $limitnum);
        return $rs;
    }

    /**
     * Constructs a post object and (recursively) all of its children from
     * information retrieved from the database.
     * @param $posts Array of post ID => fields from DB query
     * @param $children Array of post ID => array of child IDs
     * @param $id ID of post to construct
     * @param $parent Parent post or NULL if none
     * @return mod_forumng_post Newly-created post
     * @throws mod_forumng_exception If ID is invalid
     */
    private function build_posts(&$posts, &$children, $id, $parent) {
        if (!array_key_exists($id, $posts)) {
            $msg = "No such post: $id (discussion " . $this->get_id() . '); ' .
                'posts';
            foreach ($posts as $id => $junk) {
                $msg .= ' ' . $id;
            }
            $msg .= '; children';
            foreach ($children as $id => $junk) {
                $msg .= ' ' . $id;
            }
            throw new dml_exception($msg);
        }
        $post = new mod_forumng_post($this, $posts[$id], $parent);
        $this->totalsize += strlen($posts[$id]->message);
        $post->init_children();

        if (array_key_exists($id, $children)) {
            foreach ($children[$id] as $childid) {
                $post->add_child(
                    $this->build_posts($posts, $children, $childid, $post));
            }
        }
        return $post;
    }

    /**
     * Used by forum when creating a discussion. Do not call directly.
     * @param string $subject Subject
     * @param string $message Message
     * @param int $format Moodle format used for message
     * @param bool $attachments True if post contains attachments
     * @param bool $mailnow If true, sends mail ASAP
     * @param int $userid User ID (0 = current)
     * @param int $asmoderator values are ASMODERATOR_NO, ASMODERATOR_IDENTIFY or ASMODERATOR_ANON
     * @return int ID of newly-created post
     */
    public function create_root_post($subject, $message, $format,
        $attachments=false, $mailnow=false, $userid=0, $asmoderator = mod_forumng::ASMODERATOR_NO) {
        return $this->create_reply(null, $subject, $message, $format,
            $attachments, false, $mailnow, $userid, $asmoderator);
    }

    /**
     * Used by mod_forumng_post when creating a reply. Do not call directly.
     * @param mod_forumng_post $parentpost Parent post object (NULL when creating root post)
     * @param string $subject Subject
     * @param string $message Message
     * @param int $format Moodle format used for message
     * @param bool $attachments True if post contains attachments
     * @param bool $setimportant If true, highlight the post
     * @param bool $mailnow If true, sends mail ASAP
     * @param int $userid User ID (0 = current)
     * @param int $asmoderator values are ASMODERATOR_NO, ASMODERATOR_IDENTIFY or ASMODERATOR_ANON
     * @return int ID of newly-created post
     */
    public function create_reply($parentpost, $subject, $message, $format,
        $attachments=false, $setimportant=false, $mailnow=false, $userid=0, $asmoderator = mod_forumng::ASMODERATOR_NO) {
        global $DB;
        $userid = mod_forumng_utils::get_real_userid($userid);

        // Prepare post object
        $postobj = new StdClass;
        $postobj->discussionid = $this->discussionfields->id;
        $postobj->parentpostid = $parentpost ? $parentpost->get_id() : null;
        $postobj->userid = $userid;
        $postobj->u_id = $userid;
        $postobj->created = time();
        $postobj->deleted = 0;
        $postobj->mailstate = $mailnow
            ? mod_forumng::MAILSTATE_NOW_NOT_MAILED
            : mod_forumng::MAILSTATE_NOT_MAILED;
        $postobj->important = $setimportant ? 1 : 0;
        $postobj->oldversion = 0;
        $postobj->edituserid = null;
        $postobj->subject = strlen(trim($subject)) == 0 ? null : $subject;
        $postobj->message = $message;
        $postobj->messageformat = $format;
        $postobj->attachments = $attachments ? 1 : 0;
        $postobj->asmoderator = $asmoderator;
        if ($parentpost == null && $this->get_time_start() && $this->get_time_start() > time()) {
            // When $parentpost is null and get_time_start() has a value that $postobj->created is the value of get_time_start().
            $postobj->created = $this->get_time_start();
        }
        $postobj->modified = $postobj->created;

        $transaction = $DB->start_delegated_transaction();

        // Create post
        $postobj->id = $DB->insert_record('forumng_posts', $postobj);
        $post = new mod_forumng_post($this, $postobj);

        // For replies, update last post id
        if ($parentpost) {
            $discussionchange = new stdClass;
            $discussionchange->id = $parentpost->get_discussion()->get_id();
            $discussionchange->lastpostid = $postobj->id;
            $DB->update_record('forumng_discussions', $discussionchange);
        }

        // Update search index (replies only)
        if ($parentpost) {
            $post->search_update();
        }

        // Update completion state
        $post->update_completion(true);

        // Outside the catch so we don't commit transaction if something
        // fails
        $transaction->allow_commit();

        return $post->get_id();
    }

    /**
     * Used when updating search data for posts. When this function returns
     * true, updating search data will cause it to be deleted. After making
     * the change which affects search, make this function return false again.
     * @return bool True if search data is being changed and posts should
     *   delete their search data
     */
    public function is_making_search_change() {
        return $this->ismakingsearchchange;
    }

    /**
     * Edits discussion settings. These parameters may be set to the NOCHANGE
     * constant if not being altered.
     * @param int $groupid Group ID
     * @param int $timestart Seconds since epoch that this becomes visible,
     *   null/0 if always
     * @param int $timeend Seconds since epoch that this disappear, null/0 if
     *   it doesn't
     * @param bool $locked True if discussion should be locked
     * @param bool $sticky True if discussion should be sticky
     * @param array $tags
     */
    public function edit_settings($groupid, $timestart, $timeend, $locked, $sticky, array $tags = null) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/tag/lib.php');

        // Apply defaults
        if ($groupid === self::NOCHANGE) {
            $groupid = $this->discussionfields->groupid;
        }
        if ($timestart === self::NOCHANGE) {
            $timestart = $this->discussionfields->timestart;
        }
        if ($timeend === self::NOCHANGE) {
            $timeend = $this->discussionfields->timeend;
        }
        if ($locked === self::NOCHANGE) {
            $locked = $this->discussionfields->locked;
        }
        if ($sticky === self::NOCHANGE) {
            $sticky = $this->discussionfields->sticky;
        }

        // Normalise entries to match db values
        $timestart = $timestart ? $timestart : 0;
        $timeend = $timeend ? $timeend : 0;
        $sticky = $sticky ? 1 : 0;
        if ($locked === true) {
            $locked = 1;
        }
        if (!($locked == 1 || $locked == 2)) {
            $locked = 0;
        }
        $groupid = $groupid ? $groupid : null;

        // Start transaction in case there are multiple changes relating to
        // search
        $transaction = $DB->start_delegated_transaction();

        $update = new StdClass;
        if ($groupid != $this->discussionfields->groupid) {
            $update->groupid = $groupid;

            // When group changes, need to redo the search data; must remove it
            // before changing group or it won't be able to find the old
            // search documents any more (because it looks for them under the
            // new group id).
            $this->ismakingsearchchange = true;
            $root = $this->get_root_post();
            $root->search_update();
            $root->search_update_children();
            $this->ismakingsearchchange = false;
        }
        if ($timestart != $this->discussionfields->timestart) {
            $update->timestart = $timestart;
            $root = $this->get_root_post();
            // When $timestart is not the same as $this->discussionfields->timestart
            // and the discussion root post ($root) has no children.
            if (!$root->has_children()) {
                // Then the root post created and modified times are set to $timestart.
                // Note will need to do this using DB function as no method to do this in classes.
                if ($timestart == 0) {
                    $timestart = time();
                }
                $revisedroot = new stdClass();
                $revisedroot->created = $timestart;
                $revisedroot->modified = $timestart;
                $revisedroot->id = $root->get_id();
                $DB->update_record('forumng_posts', $revisedroot);
            }
        }
        if ($timeend != $this->discussionfields->timeend) {
            $update->timeend = $timeend;
        }
        if ($locked != $this->discussionfields->locked) {
            $update->locked = $locked;
        }
        if ($sticky != $this->discussionfields->sticky) {
            $update->sticky = $sticky;
        }

        // Update tags if required.
        if (!is_null($tags)) {
            // Get old item tags and compare with new item tags to detect changes.
            sort($tags);
            $taggeditems = core_tag_tag::get_item_tags_array('mod_forumng', 'forumng_discussions',
                $this->discussionfields->id);
            $taggeditems = array_values($taggeditems);
            sort($taggeditems);
            $tagupdated = !($taggeditems == $tags);
            if ($tagupdated) {
                // We need to update time modified for re-indexing discussion only when item tags are changed.
                $update->modified = time();
            }
            $context = $this->get_forum()->get_context(true);
            core_tag_tag::set_item_tags('mod_forumng', 'forumng_discussions', $this->discussionfields->id,
                    $context, $tags);
        }

        if (count((array)$update)==0) {
            // No change

            $transaction->allow_commit();
            return;
        } else {
            $update->modified = time();
        }
        $update->id = $this->discussionfields->id;
        $DB->update_record('forumng_discussions', $update);

        // Update in memory (needed for the next group bit)
        $this->uncache();
        foreach ($update as $key => $value) {
            $this->discussionfields->{$key} = $value;
        }

        // Update group if required
        if (isset($update->groupid)) {
            // When group has changed, must add items to the new group
            $root = $this->get_root_post();
            $root->search_update();
            $root->search_update_children();
        }

        // End transaction
        $transaction->allow_commit();
    }

    /**
     * Moves discussion to another forum. This will also move any attachments
     * in the filesystem. You can also use this method to change group.
     * (Note that once a discussion has been moved its data fields are no longer
     * valid and the object should be discarded.)
     * @param mod_forumng $targetforum Target forum for move
     * @param int $targetforumngid New forum ID
     * @param int $targetgroupid New group ID
     */
    public function move($targetforum, $targetgroupid) {
        global $DB;

        $update = new StdClass;
        if ($targetforum->get_id() != $this->discussionfields->forumngid) {
            $update->forumngid = $targetforum->get_id();
        }
        if ($targetgroupid != $this->discussionfields->groupid) {
            $update->groupid = $targetgroupid;
        }
        if (count((array)$update) == 0) {
            // No change
            return;
        }
        // Delete search data for this discussion before moving.
        $this->ismakingsearchchange = true;
        $root = $this->get_root_post();
        $root->search_update();
        $root->search_update_children();
        $this->ismakingsearchchange = false;

        $update->id = $this->discussionfields->id;

        $transaction = $DB->start_delegated_transaction();
        $DB->update_record('forumng_discussions', $update);

        $targetcloneid = $targetforum->is_shared() ? $targetforum->get_course_module_id() : 0;
        $newdiscussion = self::get_from_id($this->get_id(), $targetcloneid, -1);

        if ($targetforum->get_id() != $this->forum->get_id()) {
            // Moving to different forum, we need to move attachments + ratings if any...

            // Get old and new contexts
            $fs = get_file_storage();
            $filecontext = $this->get_forum()->get_context(true);
            $newfilecontext = $targetforum->get_context(true);

            // Get list of all affected post ids (includes edited, deleted)
            // that have attachments
            $postids = $DB->get_records('forumng_posts', array(
                    'discussionid' => $this->get_id()), '', 'id');

            // Loop through all posts copying attachments & deleting old one
            foreach ($postids as $postid => $junk) {
                // Move core ratings if enabled (Note move to new forum even if not enabled there).
                if ($this->get_forum()->get_enableratings() == mod_forumng::FORUMNG_STANDARD_RATING) {
                    $DB->set_field_select('rating', 'contextid', $newfilecontext->id,
                            "itemid = $postid AND contextid = {$filecontext->id} AND component
                            = 'mod_forumng' AND ratingarea = 'post'");
                }
                foreach (array('attachment', 'message') as $filearea) {
                    $oldfiles = $fs->get_area_files($filecontext->id, 'mod_forumng', $filearea,
                            $postid, 'id', false);
                    foreach ($oldfiles as $oldfile) {
                        $filerecord = new stdClass();
                        $filerecord->contextid = $newfilecontext->id;
                        $fs->create_file_from_storedfile($filerecord, $oldfile);
                        $oldfile->delete();
                    }
                }
            }
            if ($this->get_forum()->get_enableratings() == mod_forumng::FORUMNG_STANDARD_RATING) {
                $this->forum->update_grades();
            }
            if ($targetforum->get_enableratings() == mod_forumng::FORUMNG_STANDARD_RATING) {
                $targetforum->update_grades();
            }

            // Completion status may have changed in source and target forums
            // Performance optimise: only do this if completion is enabled
            if ($this->forum->is_auto_completion_enabled()) {
                $this->update_completion(false);
                $newdiscussion->update_completion(true);
            }
        }

        // Update the search data after the move.
        $newroot = $newdiscussion->get_root_post();
        $newroot->search_update();
        $newroot->search_update_children();

        // Update the tags after the move.
        if ($targetforum->get_id() != $this->forum->get_id()) {
            // Update tags for moved discussions.
            $oldcontext = $this->forum->get_context(true);
            $newcontext = $targetforum->get_context(true);
            $DB->set_field('tag_instance', 'contextid', $newcontext->id, array('itemid' => $this->get_id(), 'itemtype' => 'forumng_discussions'));
        }

        $this->uncache();
        $transaction->allow_commit();
    }
    /**
     * Copy the discussion and its posts to another forum and/or group.
     * @param mod_forumng $targetforum Forum to copy the discussion to
     * @param int $groupid If 'All participants' has been selected from the
     * separate groups dropdown box, use default value 0
     */
    public function copy($targetforum, $groupid) {
        global $SESSION, $DB;
        $oldforum = $this->get_forum();
        $oldforumngid = $oldforum->get_id();
        $oldcourseid = $oldforum->get_course_id();
        $targetforumngid = $targetforum->get_id();
        $targetcourseid = $targetforum->get_course_id();
        // Clone the old discussion.
        $discussionobj = clone($this->discussionfields);
        unset($discussionobj->id);

        // Update the forumngid and gruopid to the target forumngid and selected groupid.
        $discussionobj->forumngid = $targetforumngid;
        unset($discussionobj->groupid);
        if ($targetforum->get_group_mode() && $groupid) {
            $discussionobj->groupid = $groupid;
        }
        $transaction = $DB->start_delegated_transaction();
        $newdiscussionid =  $DB->insert_record('forumng_discussions', $discussionobj);
        $rs = $DB->get_recordset('forumng_posts', array('discussionid' => $this->get_id()));
        // $newids and $parentused are temp arrays used to
        // $newids is a array of new postids using the indices of its old postids
        // Update the parentid of the post records copied over
        // $hasattachments is a temp array for record the posts which has attachments.
        $newids = array();
        $parentsused = array();
        $hasattachments = array();
        foreach ($rs as $postrec) {
            $oldpostid = $postrec->id;
            unset($postrec->id);
            $postrec->discussionid = $newdiscussionid;
            $postrec->mailstate = mod_forumng::MAILSTATE_DIGESTED;
            $newpostid = $DB->insert_record('forumng_posts', $postrec);
            $newids[$oldpostid] = $newpostid;
            if ($postrec->parentpostid) {
                $parentsused[$postrec->parentpostid] = true;
            }
            if ($postrec->attachments ==1) {
                $hasattachments[$oldpostid] = $newpostid;
            }
        }
        $rs->close();
        // Duplicate core ratings if enabled and both forums in same course.
        if ($oldforum->get_enableratings() == mod_forumng::FORUMNG_STANDARD_RATING &&
                $oldforum->get_course(true)->id == $targetforum->get_course(true)->id) {
            list($in, $inparams) = $DB->get_in_or_equal(array_keys($newids));
            if ($ratings = $DB->get_records_sql("SELECT * FROM {rating} WHERE itemid $in AND component
                    = ? AND ratingarea = ? AND contextid = ?", array_merge($inparams,
                            array('mod_forumng', 'post', $oldforum->get_context(true)->id)))) {
                foreach ($ratings as $rating) {
                    $newrate = new stdClass();
                    $newrate->contextid = $targetforum->get_context(true)->id;
                    $newrate->component = 'mod_forumng';
                    $newrate->ratingarea = 'post';
                    $newrate->itemid = $newids[$rating->itemid];
                    $newrate->scaleid = $rating->scaleid;
                    $newrate->userid = $rating->userid;
                    $newrate->rating = $rating->rating;
                    $newrate->timecreated = $rating->timecreated;
                    $newrate->timemodified = time();
                    $DB->insert_record('rating', $newrate);
                }
                $targetforum->update_grades();
            }
        }
        // Update the postid and lastpostid in the discussion table no matter if they
        // are null or not
        $newpostid = $newids[$discussionobj->postid];
        $newlastpostid = $newids[$discussionobj->lastpostid];
        $DB->execute("UPDATE {forumng_discussions} SET postid = ?, lastpostid = ? WHERE id = ?",
                array($newpostid, $newlastpostid, $newdiscussionid));
        foreach ($parentsused as $key => $value) {
            $newparentpostid = $newids[$key];
            // Update the parentpostids which have just been copied over.
            $DB->execute("UPDATE {forumng_posts} SET parentpostid = ? " .
                    "WHERE parentpostid = ? AND discussionid = ?",
                    array($newparentpostid, $key, $newdiscussionid));
        }
        // Copy attachments
        $fs = get_file_storage();
        $oldfilecontext = $oldforum->get_context(true);
        $newfilecontext = $targetforum->get_context(true);
        foreach ($hasattachments as $oldpostid => $newpostid) {
            foreach (array('attachment', 'message') as $filearea) {
                $oldfiles = $fs->get_area_files($oldfilecontext->id, 'mod_forumng', $filearea,
                        $oldpostid, 'id', false);
                foreach ($oldfiles as $oldfile) {
                    $filerecord = new stdClass();
                    $filerecord->contextid = $newfilecontext->id;
                    $filerecord->itemid = $newpostid;
                    $fs->create_file_from_storedfile($filerecord, $oldfile);
                }
            }
        }
        // Update the search data after the copy
        $newdiscussion = self::get_from_id($newdiscussionid,
                $this->get_forum()->get_course_module_id(), -1);
        $root = $newdiscussion->get_root_post();
        $root->search_update();
        $root->search_update_children();
        $transaction->allow_commit();
        // Update any discussion tags.
        $tagslist = $this->get_tags();
        if ($tagslist) {
            $tags = array();
            foreach ($tagslist as $key => $value) {
                array_push($tags, $value);
            }
            $newdiscussion->edit_settings(self::NOCHANGE, self::NOCHANGE, self::NOCHANGE, self::NOCHANGE, self::NOCHANGE, $tags);
        }
    }

    /**
     * Clones this discussion but changes the post IDs, for internal use
     * only (in split).
     * @param int $postid First post in discussion
     * @param int $lastpostid Last post in discussion
     * @return int New discussion ID
     */
    public function clone_for_split($postid, $lastpostid) {
        global $DB;
        // Create new discussion
        $discussionobj = clone($this->discussionfields);
        unset($discussionobj->id);
        $discussionobj->postid = $postid;
        $discussionobj->lastpostid = $lastpostid;
        return $DB->insert_record('forumng_discussions', $discussionobj);
    }

    /**
     * Deletes this discussion.
     * @param bool $log True to log action
     */
    public function delete($log=true) {
        global $DB;
        if ($this->discussionfields->deleted) {
            return;
        }
        $transaction = $DB->start_delegated_transaction();
        $update = new StdClass;
        $update->id = $this->discussionfields->id;
        $update->deleted = time();
        $DB->update_record('forumng_discussions', $update);
        $this->discussionfields->deleted = $update->deleted;

        // Update all the posts to remove them from search
        $this->get_root_post()->search_update();
        $this->get_root_post()->search_update_children();

        // Update completion status in case it needs marking false for anyone
        $this->update_completion(false);

        // Log delete
        if ($log) {
            $this->log('delete discussion');
        }
        $transaction->allow_commit();

        $this->uncache();
    }

    /**
     * Undeletes this discussion.
     * @param bool $log True to log action
     */
    public function undelete($log=true) {
        global $DB;
        if (!$this->discussionfields->deleted) {
            return;
        }
        $transaction = $DB->start_delegated_transaction();
        $update = new StdClass;
        $update->id = $this->discussionfields->id;
        $update->deleted = 0;
        $DB->update_record('forumng_discussions', $update);
        $this->discussionfields->deleted = 0;

        // Update all the posts to add them back to search
        $this->get_root_post()->search_update();
        $this->get_root_post()->search_update_children();

        // Update completion status in case it needs marking true for anyone
        $this->update_completion(true);

        if ($log) {
            $this->log('undelete discussion');
        }
        $transaction->allow_commit();

        $this->uncache();
    }

    /**
     * Deletes this discussion and its relevant data permanently.
     * It can't be undeleted afterwards.
     * @param bool $log True to log action
     */
    public function permanently_delete($log=true) {
        global $DB;

        $this->delete(false);

        $transaction = $DB->start_delegated_transaction();

        // Deleting the relevant data in the forumng_subscriptions table.
        $DB->delete_records('forumng_subscriptions', array('discussionid' => $this->get_id()));

        // Deleting the relevant data in the forumng_read table.
        $DB->delete_records('forumng_read', array('discussionid' => $this->get_id()));

        // Deleting the relevant data in the forumng_ratings table.
        $query = "WHERE postid IN (
SELECT fp.id
FROM
    {forumng_posts} fp
    INNER JOIN {forumng_discussions} fd ON fp.discussionid = fd.id
WHERE
    fd.id = ?)";
        $queryparams = array($this->discussionfields->id);
        $DB->execute("DELETE FROM {forumng_ratings} $query", $queryparams);

        // Delete core ratings if enabled.
        if ($this->get_forum()->get_enableratings() == mod_forumng::FORUMNG_STANDARD_RATING) {
            $ratequery = str_replace('postid', 'itemid', $query);
            $DB->execute("DELETE FROM {rating} $ratequery AND component = ? AND ratingarea = ?
                    AND contextid = ?", array_merge($queryparams, array('mod_forumng', 'post',
                            $this->get_forum()->get_context(true)->id)));
            $this->forum->update_grades();
        }

        // Deleting the relevant data in the forumng_flags table.
        $DB->execute("DELETE FROM {forumng_flags} $query", $queryparams);

        // Deleting the relevant data in the forumng_read_posts table.
        $DB->execute("DELETE FROM {forumng_read_posts} $query", $queryparams);

        // Delete all the attachment files of this discussion.
        $fs = get_file_storage();
        $filecontext = $this->get_forum()->get_context(true);

        // Get list of all affected post ids (includes edited, deleted) that have attachments.
        $postids = $DB->get_records('forumng_posts', array(
                'discussionid' => $this->get_id(), 'attachments' => 1), '', 'id');

        // Loop through all posts and deleting the attachments for each post.
        foreach ($postids as $postid => $junk) {
            foreach (array('attachment', 'message') as $filearea) {
                $fs->delete_area_files($filecontext->id, 'mod_forumng', $filearea,
                        $postid);
            }
        }

        // Deleting the relevant posts in this discussion in the forumng_posts table.
        $DB->delete_records('forumng_posts', array('discussionid' => $this->get_id()));

        // Delete the relevant discussion in the forumng_flags table.
        $DB->delete_records('forumng_flags', array('discussionid' => $this->get_id()));

        // Delete the relevant discussion in the tag_instance table.
        core_tag_tag::remove_all_item_tags('mod_forumng', 'forumng_discussions', $this->get_id());

        // Finally deleting this discussion in the forumng_discussions table.
        $DB->delete_records('forumng_discussions', array('id' => $this->get_id()));

        // Log delete.
        if ($log) {
            $this->log('permdelete discussion');
        }
        $transaction->allow_commit();

        $this->uncache();
    }

    /**
     * Locks a discussion with a final message.
     * @param string $subject Subject
     * @param string $message Message
     * @param int $format Moodle format used for message
     * @param bool $attachments True if post contains attachments
     * @param bool $mailnow If true, sends mail ASAP
     * @param int $userid User ID (0 = current)
     * @param bool $log True to log this action
     * @param int $asmoderator values are ASMODERATOR_NO, ASMODERATOR_IDENTIFY or ASMODERATOR_ANON
     * @return int post ID
     */
    public function lock($subject, $message, $format,
        $attachments=false, $mailnow=false, $userid=0, $log=true, $asmoderator = mod_forumng::ASMODERATOR_NO) {
        global $DB;
        $transaction = $DB->start_delegated_transaction();

        // Post reply
        $postid = $this->get_root_post()->reply($subject, $message, $format,
            $attachments, false, $mailnow, $userid, false, $asmoderator);

        // Mark discussion locked
        $this->edit_settings(self::NOCHANGE,
            self::NOCHANGE, self::NOCHANGE,
            true, self::NOCHANGE);

        // Log
        if ($log) {
            $this->log('lock discussion', 'p' . $postid . ' d' . $this->get_id());
        }

        $transaction->allow_commit();
        return $postid;
    }

    /**
     * Auto locks a discussion with a final message.
     * @return int post ID
     */
    public function auto_lock() {

        if ($this->is_locked() ) {
            $locked = self::NOCHANGE;
        } else {
            $locked = 2;
            // Mark discussion locked.
            $this->edit_settings(self::NOCHANGE,
                self::NOCHANGE, self::NOCHANGE,
                $locked, self::NOCHANGE);
            // Log.
            $this->log('auto lock discussion', ' d' . $this->get_id());
        }

    }

    /**
     * Unlocks a discussion.
     * @param int $userid User ID (0 = current)
     * @param bool $log True to log this action
     */
    public function unlock($userid=0, $log=true) {
        global $DB;
        $transaction = $DB->start_delegated_transaction();

        // Get autolocked value as it changes after edit_settings is run.
        $autolocked = $this->is_auto_locked();
        // If not auto locked.
        if (!$autolocked) {
            // Delete lock post.
            $lockpost = $this->get_lock_post();
            if (!$lockpost) {
                throw new invalid_state_exception('Discussion not locked');
            }
            $lockpost->delete($userid, false);
        }

        // Mark discussion unlocked
        $this->edit_settings(self::NOCHANGE,
            self::NOCHANGE, self::NOCHANGE,
            false, self::NOCHANGE);

        // Log
        if ($log) {
            if (!$autolocked) {
                $this->log('unlock discussion', 'p' . $lockpost->get_id() . ' d' . $this->get_id());
            } else {
                $this->log('unlock auto locked discussion', ' d' . $this->get_id());
            }
        }

        $transaction->allow_commit();
    }

    /**
     * Merges the contents of this discussion into another discussion.
     * @param mod_forumng_discussion $targetdiscussion Target discussion
     * @param int $userid User ID (0 = current)
     * @param bool $log True to log this action
     */
    public function merge_into($targetdiscussion, $userid=0, $log=true) {
        global $DB;
        $transaction = $DB->start_delegated_transaction();

        // Delete search data for the source discussion
        $this->ismakingsearchchange = true;
        $root = $this->get_root_post();
        $root->search_update();
        $root->search_update_children();
        $this->ismakingsearchchange = false;

        // Update parent post id of root post
        $record = new stdClass;
        $record->id = $this->discussionfields->postid;
        $record->parentpostid = $targetdiscussion->discussionfields->postid;
        $DB->update_record('forumng_posts', $record);

        // Move all posts into new discussion
        $DB->execute("UPDATE {forumng_posts} SET discussionid = ? WHERE discussionid = ?",
                array($targetdiscussion->get_id(), $this->get_id()));

        // Update the last post id to that of the most recent time modified.
        $targetdiscussion->possible_lastpost_change();

        // Delete this discussion
        $DB->delete_records('forumng_discussions', array('id' => $this->discussionfields->id));

        // Attachments are OK because they are still in the same context, postid
        // etc (note this would NOT be the case if we allowed merging between
        // forums).
        if ($this->get_forum()->get_id() != $targetdiscussion->get_forum()->get_id()) {
            throw new coding_exception('Cannot merge into different forum');
        }

        // Merging the discussion into another might cause completion changes
        // (if there was a requirement for discussions and this is no longer
        // a discussion in its own right).
        $this->update_completion(false);

        // Update the search data for the target discussion after the merge
        $newroot = $targetdiscussion->get_root_post();
        $newroot->search_update();
        $newroot->search_update_children();

        if ($log) {
            $this->log('merge discussion', 'd' . $this->get_id() . ' into d' .
                    $targetdiscussion->get_id());
        }

        $transaction->allow_commit();
        $this->uncache();
        $targetdiscussion->uncache();
    }

    /**
     * Obtains a list of everybody who has read this discussion (only works
     * if the discussion is within the 'read' period). The list is in date order
     * (most recent first). Each returned item has ->time (time last read) and
     * ->user (Moodle user object) fields.
     * @param int $groupid Group ID or mod_forumng::ALL_GROUPS
     * @return array Array of information about readers
     * @throws coding_exception If you try to call it in a shared forum (not supported)
     */
    public function get_readers($groupid=mod_forumng::ALL_GROUPS) {
        global $DB;

        if ($this->get_forum()->is_shared()) {
            throw new coding_exception('get_readers not supported in shared forums');
        }

        list($sql, $params) = get_enrolled_sql(
                $this->get_forum()->get_context(), '', $groupid ? $groupid : 0, true);
        $now = round(time(), -2);
        $params['discussionid'] = $this->discussionfields->id;
        $result = $DB->get_records_sql($sql = "
SELECT
    fr.id,
    " . mod_forumng_utils::select_username_fields('u', false) . ",
    fr.time,
    u.idnumber AS u_idnumber
FROM
    (SELECT MAX(id) AS id, userid, discussionid, MAX(time) AS time
     FROM (
         SELECT id, userid, discussionid, time
           FROM {forumng_read} fr
      UNION ALL
         SELECT id, userid, discussionid, time
           FROM (
               SELECT frp.*, fp.discussionid
                 FROM {forumng_posts} fp
           RIGHT JOIN {forumng_read_posts} frp ON fp.id = frp.postid
                WHERE fp.deleted = 0 AND fp.oldversion = 0
           ) frp
    ) rp GROUP BY userid, discussionid) fr
    INNER JOIN {user} u ON u.id = fr.userid
WHERE
    fr.userid IN ($sql)
    AND fr.discussionid = :discussionid
ORDER BY
    fr.time DESC", $params);

        foreach ($result as $item) {
            $item->user = mod_forumng_utils::extract_subobject($item, 'u_');
        }

        return $result;
    }

    /**
     * @return bool True if read tracking is enabled for this discussion
     *   (it is not too old, and read tracking is turned on globally)
     */
    public function is_read_tracked() {
        $this->check_full();
        return mod_forumng::enabled_read_tracking() &&
            ($this->discussionfields->timemodified >=
                mod_forumng::get_read_tracking_deadline());
    }

    /**
     * Marks this discussion read.
     * @param int $time Time to mark it read at (0 = now)
     * @param int $userid User who's read the discussion (0=current)
     */
    public function mark_read($time=0, $userid=0) {
        global $DB;
        $userid = mod_forumng_utils::get_real_userid($userid);
        if (!$time) {
            $time = time();
        }
        $transaction = $DB->start_delegated_transaction();
        $existing = $DB->get_record('forumng_read', array('userid' => $userid,
            'discussionid' => $this->discussionfields->id), '*', IGNORE_MISSING);
        if ($existing) {
            $readrecord = new StdClass;
            $readrecord->id = $existing->id;
            $readrecord->time = $time;
            $DB->update_record('forumng_read', $readrecord);
        } else {
            $readrecord = new StdClass;
            $readrecord->userid = $userid;
            $readrecord->discussionid = $this->discussionfields->id;
            $readrecord->time = $time;
            $DB->insert_record('forumng_read', $readrecord);
        }
        // Delete any individual post records for discussion as now redundant.
        $DB->execute("DELETE FROM {forumng_read_posts}
                            WHERE postid IN(
                                  SELECT id FROM {forumng_posts}
                                   WHERE discussionid = :discussionid)
                              AND userid = :userid AND time <= :now",
                array('userid' => $userid, 'discussionid' => $this->discussionfields->id, 'now' => $time));
        $transaction->allow_commit();

        if ($this->incache) {
            $this->discussionfields->timeread = $time;
            $this->cache($this->incache->userid);
        }
    }

    /**
     * Marks this discussion unread.
     * @param int $userid User who's not read the discussion (0=current)
     */
    public function mark_unread($userid=0) {
        global $DB;
        $userid = mod_forumng_utils::get_real_userid($userid);
        $DB->delete_records('forumng_read',
                array('userid' => $userid, 'discussionid' => $this->discussionfields->id));
        // Delete any individual post records for discussion as well.
        $DB->execute("DELETE FROM {forumng_read_posts}
                            WHERE postid IN(
                                  SELECT id FROM {forumng_posts}
                                   WHERE discussionid = :discussionid)
                              AND userid = :userid",
                array('userid' => $userid, 'discussionid' => $this->discussionfields->id));
        if ($this->incache) {
            $this->discussionfields->timeread = null;
            $this->cache($this->incache->userid);
        }
    }

    /**
     * Called when a post is deleted or undeleted or modified, or there is a
     * larger change to the discussion
     * @param mod_forumng_post $post Post that has changed; null to always recalculate
     */
    public function possible_lastpost_change($post=null) {
        $recalculate = false;
        if (!$post) {
            $recalculate = true;
        } else {
            if ($post->get_deleted()) {
                // For deleted posts, recalculate if this was previously
                // considered the latest post
                $recalculate =
                    $this->discussionfields->lastpostid == $post->get_id();
            } else {
                // For other posts, recalculate if this is now newer than the
                // stored last post
                $recalculate =
                    $post->get_modified() > $this->discussionfields->timemodified;
            }
        }

        // If necessary, recalculate the date
        if ($recalculate) {
            global $DB;
            $change = new stdClass;
            $change->id = $this->get_id();

            $records = $DB->get_records_sql("SELECT id " .
                "FROM {forumng_posts} WHERE discussionid = ? AND deleted = 0 AND oldversion = 0 " .
                "ORDER BY modified DESC", array($this->get_id()), 0, 1);
            if (count($records) == 0) {
                throw new moodle_exception('errorfindinglastpost', 'forumng');
            }
            $rec = reset($records);
            $change->lastpostid = $rec->id;
            if ($change->lastpostid != $this->discussionfields->lastpostid) {
                $DB->update_record('forumng_discussions', $change);
            }
        }
    }

    /**
     * Records an action in the Moodle log for current user.
     * @param string $action Action name - see datalib.php for suggested verbs
     *   and this code for example usage
     * @param string $replaceinfo Optional info text to replace default (which
     *   is just the discussion id again)
     */
    public function log($action, $replaceinfo = '') {
        $info = 'd' . $this->discussionfields->id;
        if ($replaceinfo !== '') {
            $info = $replaceinfo;
        }
        $params = array(
                'context' => $this->get_forum()->get_context(),
                'objectid' => $this->get_id(),
                'other' => array('info' => $info, 'logurl' => $this->get_log_url())
        );
        switch ($action) {
            case 'add discussion':
                $classname = 'discussion_created';
                break;
            case 'delete discussion':
                $classname = 'discussion_deleted';
                break;
            case 'undelete discussion':
                $classname = 'discussion_undeleted';
                break;
            case 'permdelete discussion':
                $classname = 'discussion_permdeleted';
                break;
            case 'lock discussion':
                $classname = 'discussion_locked';
                break;
            case 'unlock discussion':
                $classname = 'discussion_unlocked';
                break;
            case 'auto lock discussion':
                $params['other']['auto'] = true;
                $classname = 'discussion_locked';
                break;
            case 'unlock auto locked discussion':
                $params['other']['auto'] = true;
                $classname = 'discussion_unlocked';
                break;
            case 'subscribe':
                $classname = 'subscription_created';
                $params['relateduserid'] = substr($info, 0, strpos($info, ' '));
                unset($params['objectid']);// Unset discuss id as event for subscriptions table.
                break;
            case 'unsubscribe':
                $classname = 'subscription_deleted';
                $params['relateduserid'] = substr($info, 0, strpos($info, ' '));
                unset($params['objectid']);// Unset discuss id as event for subscriptions table.
                break;
            case 'merge discussion':
                $params['other']['newid'] = substr($info, strpos($info, 'into d') + 6);
                $classname = 'discussion_merged';
                break;
            default:
                $classname = 'discussion_viewed';
                break;
        }
        $class = '\\mod_forumng\\event\\' . $classname;
        $event = $class::create($params);
        $event->add_record_snapshot('course_modules', $this->get_course_module());
        $event->add_record_snapshot('course', $this->get_course());
        $event->add_record_snapshot('forumng_discussions', $this->discussionfields);
        $event->trigger();
    }

    /**
     * Checks whether this discussion is currently visible to students.
     * A discussion is visible to students if it is not deleted and is not
     * restricted to a non-current time period.
     * @return bool True if it's visible
     */
    public function is_currently_visible() {
        // Deleted
        if ($this->is_deleted()) {
            return false;
        }

        return $this->is_within_time_period();
    }

    /**
     * @return bool True if deleted
     */
    public function is_deleted() {
        return $this->discussionfields->deleted ? true : false;
    }

    /**
     * @return bool True if discussion is within the given time period, or
     *   there isn't one
     */
    public function is_within_time_period() {
        // Start/end time, if set
        $now = time();
        return ($this->discussionfields->timestart <= $now &&
            ((!$this->discussionfields->timeend) ||
                ($this->discussionfields->timeend > $now)));
    }

    /**
     * @return int NOT_SUBSCRIBED:0; PARTIALLY_SUBSCRIBED:1; FULLY_SUBSCRIBED:2;
     * THIS_GROUP_SUBSCRIBED:5; THIS_GROUP_NOT_SUBSCRIBED:6;
     * @param int $userid User who's not read the discussion (0=current)
     */
    public function is_subscribed($userid=0) {
        $userid = mod_forumng_utils::get_real_userid($userid);
        $subscriptioninfo = $this->get_forum()->get_subscription_info($userid);
        if ($subscriptioninfo->wholeforum) {
            // Subscribed to the entire forum.
            return mod_forumng::FULLY_SUBSCRIBED;
        } else if (count($subscriptioninfo->discussionids) == 0) {
            if (count($subscriptioninfo->groupids) == 0) {
                // Not subscribed at all.
                return mod_forumng::NOT_SUBSCRIBED;
            } else {
                if ($this->get_forum()->get_group_mode()) {
                    // If the group mode turned on, we need to check if subscribed to the group
                    // that the current discussion belongs to.
                    foreach ($subscriptioninfo->groupids as $id) {
                        if ($this->get_group_id() == $id) {
                            return mod_forumng::THIS_GROUP_SUBSCRIBED;
                        }
                    }
                    return mod_forumng::THIS_GROUP_NOT_SUBSCRIBED;
                } else {
                    return mod_forumng::NOT_SUBSCRIBED;
                }
            }

        } else {
            // Discussionids array is not empty.
            // No needs to check the groupids here assuming all the subscripiton data in
            // the database is not messed up.
            $discussionid = $this->get_id();
            foreach ($subscriptioninfo->discussionids as $id => $groupid) {
                if ($discussionid == $id) {
                    return mod_forumng::PARTIALLY_SUBSCRIBED;
                }
            }
            return mod_forumng::NOT_SUBSCRIBED;
        }
    }

    /**
     * @return True if discussion contains data about whether the user has
     *   read it or not
     */
    public function has_unread_data() {
        return property_exists($this->discussionfields, 'timeread');
    }

    /**
     *
     * @return int User ID that unread data was requested for (-1 if none)
     */
    public function get_unread_data_user_id() {
        return empty($this->foruserid) ? -1 : $this->foruserid;
    }

    /**
     * Checks that data about whether or not the user has read this discussion
     * is available, sets to unread (null) if not.
     * @throws mod_forumng_exception If discussion does not contain unread data
     */
    private function check_unread_data() {
        if (!property_exists($this->discussionfields, 'timeread')) {
            $this->discussionfields->timeread = null;
        }
    }

    /**
     * @return bool True if this entire discussion has not been read yet
     */
    public function is_entirely_unread() {
        $this->check_unread_data();
        return is_null($this->discussionfields->timeread);
    }

    /**
     * @return int Time (seconds since epoch) that this discussion was
     *   read by user, or null if it has never been read
     */
    public function get_time_read() {
        $this->check_unread_data();
        if ($this->pretendtimeread) {
            return $this->pretendtimeread;
        }
        return $this->discussionfields->timeread;
    }

    /**
     * Pretends that the discussion was read at a particular time. Future tests
     * to mod_forumng_post->is_unread() etc will use this data rather than anything
     * from the database.
     * @param $time Time you want discussion to have been read at, or 0 to
     *   stop pretending
     */
    public function pretend_time_read($time=0) {
        $this->pretendtimeread = $time;
    }

    /**
     * Use to obtain link parameters when linking to any page that has anything
     * to do with discussions.
     */
    public function get_link_params($type) {
        if ($type == mod_forumng::PARAM_FORM) {
            $d = '<input type="hidden" name="d" value="' .
                    $this->get_id() . '" />';
        } else {
            $d = 'd=' . $this->discussionfields->id;
        }
        return $d . $this->get_forum()->get_clone_param($type);
    }

    /**
     * Use to obtain link parameters when linking to any page that has anything
     * to do with discussions.
     * @return array Array of parameters e.g. ('d'=>317)
     */
    public function get_link_params_array() {
        $result = array('d' => $this->discussionfields->id);
        $this->get_forum()->add_clone_param_array($result);
        return $result;
    }

    /**
     * Obtains group info for a user in this discussion. Group info may be
     * cached in the discussion object in order to reduce DB queries.
     * @param int $userid User ID (must be a user who has posts in this discussion)
     *   May be 0 to pre-cache the data without returning anything
     * @param bool $cacheall If true, obtains data for all users in the
     *   discussion and caches it; set false if only one user's information
     *   is likely to be required, to do a single query
     * @return array Array of group objects containing id, name, picture
     *   (empty if none). False if $userid was 0.
     * @throws mod_forumng_exception If user is not in this discussion
     */
    public function get_user_groups($userid, $cacheall=true) {
        global $DB;

        // If there is no cached data yet, and we are supposed to cache it,
        // then cache it now
        if (!$this->groupscache && $cacheall) {
            $this->groupscache = array();

            // Get list of users in discussion and initialise empty cache
            $userids = array();
            $this->get_root_post()->list_all_user_ids($userids);
            $userids = array_keys($userids);
            list($userlistsql, $userlistparams) =
                    mod_forumng_utils::get_in_array_sql('gm.userid', $userids);
            foreach ($userids as $auserid) {
                $this->groupscache[$auserid] = array();
            }

            // Basic IDs
            $courseid = $this->get_forum()->get_course_id();
            $discussionid = $this->get_id();

            // Grouping restriction
            if ($groupingid = $this->get_forum()->get_grouping()) {
                $groupingjoin = "INNER JOIN {groupings_groups} gg ON gg.groupid = g.id";
                $groupingcheck = "AND gg.groupingid = ?";
                $groupingparams = array($groupingid);
            } else {
                $groupingjoin = $groupingcheck = '';
                $groupingcheck = '';
                $groupingparams = array();
            }

            // Do query
            $rs = $DB->get_recordset_sql("
SELECT
    gm.userid, g.id, g.name, g.picture, g.hidepicture
FROM
    {groups_members} gm
    INNER JOIN {groups} g ON g.id = gm.groupid
    $groupingjoin
WHERE
    g.courseid = ?
    $groupingcheck
    AND $userlistsql", array_merge(array($courseid), $groupingparams, $userlistparams));
            foreach ($rs as $rec) {
                $auserid = $rec->userid;
                unset($rec->userid);
                $this->groupscache[$auserid][] = $rec;
            }
            $rs->close();

            // Update cached version to include this data
            if ($this->incache) {
                $this->cache($this->incache->userid);
            }
        }

        // If caller only wants to cache data, return false
        if (!$userid) {
            return false;
        }

        // If there is cached data, use it
        if ($this->groupscache && $cacheall) {
            if (!array_key_exists($userid, $this->groupscache)) {
                // This can happen in rare cases when sending out email. If there
                // is only one post from user X in a discussion, and that post is
                // deleted/moved to another discussion between when it gets the
                // list of all posts and when it tries to cache this list of groups
                // for the individual discussion.
                return $this->get_user_groups($userid, false);// Re-call to use code below.
            }
            return $this->groupscache[$userid];
        }

        // Otherwise make a query just for this user
        $groups = groups_get_all_groups($this->get_forum()->get_course_id(),
            $userid, $this->get_course_module()->groupingid);
        return $groups ? $groups : array();
    }

    // Permissions
    /*////////////*/

    /**
     * Checks if user can view this discussion, given that they can see the
     * forum as a whole.
     * @param int $userid User ID
     * @return bool True if user can view discusion
     */
    public function can_view($userid=0) {
        $userid = mod_forumng_utils::get_real_userid($userid);

        // If this is a 'all groups' post, then we only require access to the
        // 'no groups' forum view (any group can see it)
        $groupid = is_null($this->discussionfields->groupid) ?
            mod_forumng::NO_GROUPS : $this->discussionfields->groupid;

        // Check forum view permission and group access
        if (!$this->forum->can_access_group($groupid, false, $userid)) {
            return false;
        }

        // Check viewdiscussion
        if (!has_capability('mod/forumng:viewdiscussion', $this->forum->get_context(), $userid)) {
            return false;
        }

        // Let forum type check permission too
        if (!$this->forum->get_type()->can_view_discussion($this, $userid)) {
            return false;
        }

        // Check time limits / delete
        if (!$this->is_currently_visible() &&
            !has_capability('mod/forumng:viewallposts', $this->forum->get_context(), $userid)) {
            return false;
        }

        return true;
    }

    /**
     * Makes security checks for viewing this discussion. Will not return if
     * user cannot view it.
     * This function should be a complete access check. It calls the forum's
     * equivalent method.
     * @param int $userid ID of user to check for
     */
    public function require_view($userid=0) {
        $userid = mod_forumng_utils::get_real_userid($userid);

        // If this is a 'all groups' post, then we only require access to the
        // 'no groups' forum view (any group can see it)
        $groupid = is_null($this->discussionfields->groupid) ?
            mod_forumng::NO_GROUPS : $this->discussionfields->groupid;

        // Check forum view permission and group access
        $this->forum->require_view($groupid, $userid, true);

        // Check viewdiscussion
        require_capability('mod/forumng:viewdiscussion',
            $this->forum->get_context(), $userid);

        // Let forum type check permission too
        if (!$this->forum->get_type()->can_view_discussion($this, $userid)) {
            print_error('error_cannotviewdiscussion', 'forumng');
        }

        // Check time limits / delete
        if ($this->is_currently_visible()) {
            // Not deleted/no time limit, ordinary students are allowed to see
            return;
        }

        // The post is outside the permitted time limit, so you need
        // special permission to view it
        require_capability('mod/forumng:viewallposts',
            $this->forum->get_context(), $userid);
    }

    /**
     * Requires that the user can edit discussion options, otherwise prints
     * an error. (You need the managediscussions capability for this.)
     * Editing options is not affected by locks.
     */
    public function require_edit() {
        $this->require_view();
        if (!$this->can_manage()) {
            print_error('error_cannotmanagediscussion', 'forumng');
        }
    }

    /**
     * Checks whether the user can split this discussion, assuming that they
     * can view it. (The split permission also works for join.)
     * @return bool True if they are allowed to split
     */
    public function can_split(&$whynot, $userid=0) {
        $userid = mod_forumng_utils::get_real_userid($userid);

        // Check if discussion is locked
        if ($this->is_locked()) {
            $whynot = 'edit_locked';
            return false;
        }

        // Check user has capability
        if (!has_capability('mod/forumng:splitdiscussions',
                $this->forum->get_context(), $userid)) {
            $whynot = 'edit_nopermission';
            return false;
        }

        return true;
    }

    /**
     * When carrying out actions on discussion, this permission should be
     * checked to ensure that the user is allowed to write to that discussion's
     * group.
     * @param int $userid User ID, 0 = current
     * @return bool True if they're allowed
     */
    public function can_write_to_group($userid=0) {
        // Get group id
        $groupid = is_null($this->discussionfields->groupid) ?
            mod_forumng::NO_GROUPS : $this->discussionfields->groupid;

        // Check forum group access
        return $this->forum->can_access_group($groupid, true, $userid);
    }

    /**
     * Checks if you are allowed to manage settings of this discussion.
     * @param int $userid User ID, 0 = current
     * @return bool True if they're allowed
     */
    public function can_manage($userid=0) {
        return $this->can_write_to_group($userid) &&
            $this->forum->can_manage_discussions($userid);
    }
    /**
     * Checks whether the user can subscribe this discussion
     * @return bool True if this user is allowed to subscribe
     */
    public function can_subscribe($userid=0) {
        // If PARTIALLY_SUBSCRIBED:1 or FULLY_SUBSCRIBED:2 or THIS_GROUP_SUBSCRIBED:5 return false.
        if ($this->is_subscribed($userid) != mod_forumng::NOT_SUBSCRIBED &&
            $this->is_subscribed($userid) != mod_forumng::THIS_GROUP_NOT_SUBSCRIBED) {
            return false;
        }
        if (!$this->get_forum()->can_change_subscription($userid)) {
            return false;
        }
        return true;
    }

    /**
     * Checks whether the user can unsubscribe this discussion
     * @return bool True if this user is allowed to unsubscribe
     */
    public function can_unsubscribe($userid=0) {
        $issubscribed = $this->is_subscribed($userid);
        if ($issubscribed == mod_forumng::PARTIALLY_SUBSCRIBED &&
            $this->get_forum()->can_change_subscription($userid)) {
                return true;
        }
        return false;
    }

    // UI
    // //.

    /**
     * Given a list of post IDs, displays these selected posts in a manner
     * suitable for use in email. Note that this function is now used for
     * a number of other purposes in addition to email.
     * @param array $postids Array of IDs for posts to include, or false
     *   to include all posts
     * @param string $alltext Output variable; text of all posts will be
     *   appended (text format)
     * @param string $allhtml Output variable; text of all posts will be
     *   appended (HTML format)
     * @param array $extraoptions Set or override options when displaying posts
     */
    public function build_selected_posts_email($postids, &$alltext, &$allhtml,
            $extraoptions = array()) {
        global $USER;
        $list = array();
        $rootpost = $this->get_root_post();
        $rootpost->list_child_ids($list);
        foreach ($list as $postid) {
            if ($postids && !in_array($postid, $postids)) {
                continue;
            }
            $post = $rootpost->find_child($postid);
            $text = '';
            $html = '';
            $post->build_email(null, $subject, $text, $html, true,
                false, has_capability('moodle/site:viewfullnames',
                    $this->get_forum()->get_context()), current_language(),
                $USER->timezone, true, true, $extraoptions);

            // Don't put <hr> after the first post or after one which we didn't
            // actually print (deleted posts)
            if ($alltext != '' && $text !== '') {
                $alltext .= "\n" . mod_forumng_cron::EMAIL_DIVIDER . "\n";
                $allhtml .= '<hr size="1" noshade="noshade" />';
            }
            if ($text !== '') {
                $alltext .= $text;
                $allhtml .= $html;
            }
        }

        // Remove crosslinks to posts that do not exist
        $this->posthtml = $allhtml;
        $allhtml = preg_replace_callback(
            '~<a class="forumng-parentlink" href="#p([0-9]+)">([0-9]+)</a>~',
            array($this, 'internal_build_selected_posts_replacer'), $allhtml);
    }

    public function internal_build_selected_posts_replacer($matches) {
        if (strpos($this->posthtml, ' id="p' . $matches[1] . '"') === false) {
            return $matches[2];
        } else {
            return $matches[0];
        }
    }

    /**
     * Initialises the $PAGE variable for the page.
     * @param moodle_url Page URL
     * @param string $pagename Name of page
     * @return mod_forumng_renderer Renderer for use to output header etc
     */
    public function init_page($url, $pagename) {
        // No blocks on discussion pages
        global $PAGE;
        $PAGE->set_pagelayout('base');

        // Include discussion URL
        $navigation = array(
            shorten_text(htmlspecialchars($this->get_subject())) =>
            $this->get_moodle_url());

        // Standard forum init
        return $this->forum->init_page($url, $pagename, $navigation);
    }

    /**
     * Displays row of buttons that go along the bottom of a discussion.
     * @return string HTML code for all feature buttons in this discussion
     */
    public function display_forumngfeature_discussions() {
        global $PAGE;
        // Get forum type
        $type = $this->get_forum()->get_type();

        // Print discussion features
        $features = '';
        foreach (forumngfeature_discussion::get_all() as $feature) {
            if ($feature->should_display($this) &&
                $type->allow_forumngfeature_discussion($this, $feature)) {
                $features .= html_writer::start_div('forumngfeature_dis_' . $feature->get_id());
                $features .= $feature->display($this);
                $features .= html_writer::end_div();
            }
        }
        if ($features) {
            $PAGE->requires->string_for_js('tooltip_show_features', 'mod_forumng');
            $PAGE->requires->js_call_amd('mod_forumng/featurebtns', 'initMobileHide');
            return '<div id="forumng-features">' . $features . '</div>';
        } else {
            return '';
        }
    }

    /**
     * Display subscribe options for this discussion.
     * @return string HTML code for this area
     */
    public function display_subscribe_options() {
        if (!$this->can_subscribe() && !$this->can_unsubscribe()) {
            return '';
        } else {
            $out = mod_forumng_utils::get_renderer();
            return $out->render_discussion_subscribe_option($this, $this->can_subscribe());
        }
    }

    public function display_link_back_to_forum() {
        // Print link back to discussion list
        print '<div id="forumng-arrowback">' .
            link_arrow_left($this->get_forum()->get_name(),
                'view.php?' . $this->get_forum()->get_link_params(mod_forumng::PARAM_HTML)) .
                 '</div>';
    }


    /**
     * Subscribe a user to this discussion. (Assuming it permits manual subscribe/
     * unsubscribe.)
     * @param $userid User ID (default current)
     * @param $log True to log this
     */
    public function subscribe($userid=0, $log=true) {
        global $DB;
        $userid = mod_forumng_utils::get_real_userid($userid);
        $params = array($userid, $this->discussionfields->id);
        // For shared forums, we subscribe to a specific clone
        if ($this->get_forum()->is_shared()) {
            $clonecmid = $this->get_forum()->get_course_module_id();
            $clonevalue = '=?';
            $params[] = $clonecmid;
        } else {
            $clonecmid = null;
            $clonevalue = 'IS NULL';
        }
        $transaction = $DB->start_delegated_transaction();

        // Clear any previous subscriptions to this discussion from the same user if any.
        $DB->execute(
            "DELETE FROM {forumng_subscriptions} " .
            "WHERE userid = ? AND discussionid = ? AND clonecmid " . $clonevalue, $params);

        $subrecord = new StdClass;
        $subrecord->userid = $userid;
        $subrecord->forumngid = $this->get_forum()->get_id();
        $subrecord->subscribed = 1;
        $subrecord->discussionid = $this->discussionfields->id;
        $subrecord->clonecmid = $clonecmid;
        $DB->insert_record('forumng_subscriptions', $subrecord);
        $transaction->allow_commit();

        if ($log) {
            $this->log('subscribe', $userid . ' discussion ' . $this->get_id());
        }
    }

    /**
     * Unsubscribe a user from this discussion.
     * @param $userid User ID (default current)
     * @param $log True to log this
     */
    public function unsubscribe($userid=0, $log=true) {
        global $DB;
        $userid = mod_forumng_utils::get_real_userid($userid);
        $params = array($userid, $this->discussionfields->id);
        // For shared forums, we subscribe to a specific clone
        if ($this->get_forum()->is_shared()) {
            $clonecmid = $this->get_forum()->get_course_module_id();
            $clonevalue = '=?';
            $params[] = $clonecmid;
        } else {
            $clonecmid = null;
            $clonevalue = 'IS NULL';
        }
        $transaction = $DB->start_delegated_transaction();
        // Clear any subscriptions to this discussion from the same user if any.
        $DB->execute(
            "DELETE FROM {forumng_subscriptions} " .
            "WHERE userid = ? AND discussionid = ? AND clonecmid " . $clonevalue, $params);
        $transaction->allow_commit();

        if ($log) {
            $this->log('unsubscribe', $userid . ' discussion ' . $this->get_id());
        }
    }

    /**
     * @return string HTML skip link to unread posts
     */
    public function display_unread_skip_link() {
        if ($this->get_num_unread_posts() == 0 || $this->get_num_unread_posts() == ''
                || $this->get_root_post()->is_unread()) {
            return '';
        }

        $out = mod_forumng_utils::get_renderer();
        return $out->render_unread_skip_link();
    }

    /**
     * @return string HTML links for RSS/Atom feeds to this discussion (if
     *   enabled etc)
     */
    public function display_feed_links() {
        // Check they're allowed to see it
        if ($this->get_forum()->get_effective_feed_option()
            != mod_forumng::FEEDTYPE_ALL_POSTS) {
            return '';
        }

        $out = mod_forumng_utils::get_renderer();
        return $out->render_feed_links($this->get_feed_url(mod_forumng::FEEDFORMAT_ATOM),
                $this->get_feed_url(mod_forumng::FEEDFORMAT_RSS));
    }

    // Feeds
    /*//////*/

    /**
     * Gets URL for an Atom/RSS feed to this discussion.
     * @param int $feedformat FEEDFORMAT_xx constant
     * @param int $userid User ID or 0 for current
     * @return string URL for feed
     */
    public function get_feed_url($feedformat, $userid=0) {
        global $CFG;
        $userid = mod_forumng_utils::get_real_userid($userid);
        $groupid = $this->get_group_id();

        return $CFG->wwwroot . '/mod/forumng/feed.php?' .
            $this->get_link_params(mod_forumng::PARAM_PLAIN) .
            '&user=' . $userid .
            '&key=' . $this->get_forum()->get_feed_key($groupid, $userid) .
            '&format=' . ($feedformat == mod_forumng::FEEDFORMAT_RSS ? 'rss' : 'atom');
    }

    /**
     * Obtains list of posts to include in an Atom/RSS feed.
     * @param int $userid User ID
     * @return array Array of mod_forumng_post objects in date order (newest first)
     */
    public function get_feed_posts($userid) {
        return $this->forum->get_feed_posts(0, $userid, $this);
    }

    // Completion
    /*///////////*/

    /**
     * Updates completion status based on changes made to entire discussion.
     * @param bool $positive True if the changes will make things complete
     *   that were previously incomplete; false if they will make things
     *   incomplete that were previously complete
     */
    private function update_completion($positive) {
        // Get list of affected users (if any)
        $users = array();
        if ($this->forum->get_completion_replies() ||
            $this->forum->get_completion_posts()) {
            // Affected users = everyone who posted
            $rootpost = $this->get_root_post();
            $posts = array();
            $rootpost->build_linear_children($posts);
            foreach ($posts as $post) {
                $users[$post->get_user()->id] = true;
            }
        } else if ($this->forum->get_completion_discussions()) {
            // Affected users = discussion poster only
            $users[$this->get_poster()->id] = true;
        }

        foreach ($users as $userid => $junk) {
            $course = $this->get_course();
            $cm = $this->get_course_module();
            $completion = new completion_info($course);
            $completion->update_state($cm, $positive ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE,
                    $userid);
        }
    }

    // Tags.
    /*//////*/

    /**
     * Get tags for this discussion.
     * @param $htmldecode bool Set true to decode html special chars (for form field)
     * @return array of sorted tags or false if no tags are found.
     */
    public function get_tags($htmldecode = false) {
        global $CFG;
        require_once($CFG->dirroot . '/tag/lib.php');
        $tags = null;
        $forum = $this->get_forum();
        if ($forum->get_tags_enabled()) {
            $tags = core_tag_tag::get_item_tags_array('mod_forumng', 'forumng_discussions', $this->get_id());
            $tags = array_map('strtolower', $tags);
            if ($htmldecode) {
                $tags = array_map('htmlspecialchars_decode', $tags);
            }
            // Sort tags keeping id.
            asort($tags);
            return $tags;
        } else {
            return false;
        }

    }
}
