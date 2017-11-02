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

require_once(dirname(__FILE__).'/mod_forumng_utils.php');
require_once(dirname(__FILE__).'/mod_forumng_discussion.php');
require_once(dirname(__FILE__).'/mod_forumng_discussion_list.php');
require_once(dirname(__FILE__).'/mod_forumng_post.php');
require_once(dirname(__FILE__).'/mod_forumng_draft.php');
require_once(dirname(__FILE__).'/type/forumngtype.php');
require_once(dirname(__FILE__).'/feature/forumngfeature.php');

/**
 * Represents a forum. This class contains:
 * 1. A constructor and methods for handling information about a specific forum,
 *    such as obtaining a list of discussions.
 * 2. Static methods related to multiple forums across the course or site, or
 *    to forums in general.
 * @see mod_forumng_discussion_list
 * @see mod_forumng_discussion
 * @see mod_forumng_post
 * @package mod
 * @subpackage forumng
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_forumng {

    // Constants
    /*//////////*/

    /** Subscription: Nobody is allowed to subscribe to the forum. */
    const SUBSCRIPTION_NOT_PERMITTED = 0;
    /** Subscription: Anyone who can see the forum can choose to subscribe to it. */
    const SUBSCRIPTION_PERMITTED = 1;
    /** Subscription: Anybody who can see the forum can choose to subscribe to it,
        and users with certain roles are automatically subscribed (but can
        unsubscribe). */
    const SUBSCRIPTION_INITIALLY_SUBSCRIBED = 2;
    /** Subscription: Anyone who can see the forum can choose to subscribe to it.
        and users with certain roles are forced to be subscribed (and cannot
        unsubsribe). */
    const SUBSCRIPTION_FORCED = 3;

    /** NOT_SUBSCRIBED, PARTIALLY_SUBSCRIBED and FULLY_SUBSCRIBED are only used in
        a none group mode or all group mode
        FULLY_SUBSCRIBED_GROUPMODE (view a group page when fully subscribed),
        THIS_GROUP_PARTIALLY_SUBSCRIBED(subscribed some discussions in this group),
        THIS_GROUP_SUBSCRIBED, THIS_GROUP_NOT_SUBSCRIBED are only used in individual group mode.*/
    const NOT_SUBSCRIBED = 0;
    const PARTIALLY_SUBSCRIBED = 1;
    const FULLY_SUBSCRIBED = 2;
    const FULLY_SUBSCRIBED_GROUPMODE = 3;
    const THIS_GROUP_PARTIALLY_SUBSCRIBED = 4;
    const THIS_GROUP_SUBSCRIBED = 5;
    const THIS_GROUP_NOT_SUBSCRIBED = 6;

    /** Grading: No grade for this activity. */
    const GRADING_NONE = 0;
    /** Grading: Average of ratings. */
    const GRADING_AVERAGE = 1;
    /** Grading: Count of ratings. */
    const GRADING_COUNT = 2;
    /** Grading: Max rating. */
    const GRADING_MAX = 3;
    /** Grading: Min rating. */
    const GRADING_MIN = 4;
    /** Grading: Sum of ratings. */
    const GRADING_SUM = 5;
    /** Grading: Teacher grades students */
    const GRADING_MANUAL = 6;

    /** Feed type: No feeds provided. */
    const FEEDTYPE_NONE = 0;
    /** Feed type: Feed contains only the posts that start discussions. */
    const FEEDTYPE_DISCUSSIONS = 1;
    /** Feed type: Feed contains all forum posts. */
    const FEEDTYPE_ALL_POSTS = 2;

    /** Feed format: Atom */
    const FEEDFORMAT_ATOM = 1;
    /** Feed format: RSS */
    const FEEDFORMAT_RSS = 2;

    /** Mail state: Post not mailed yet. */
    const MAILSTATE_NOT_MAILED = 0;
    /** Mail state: Post not mailed (and is set to mail now). */
    const MAILSTATE_NOW_NOT_MAILED = 4;
    /** Mail state: Post already mailed. */
    const MAILSTATE_MAILED = 1;
    /** Mail state: Post sent in digests. */
    const MAILSTATE_DIGESTED = 2;

    /** Constant referring to posts from all groups. */
    const ALL_GROUPS = null;

    /**
     * Special constant indicating that groups are not used (does not apply
     * to posts).
     */
    const NO_GROUPS = -1;

    /** Discussion sort: by date. */
    const SORT_DATE = 0;
    /** Discussion sort: by subject. */
    const SORT_SUBJECT = 1;
    /** Discussion sort: by author. */
    const SORT_AUTHOR = 2;
    /** Discussion sort: by replies. */
    const SORT_POSTS = 3;
    /** Discussion sort: by unread replies. */
    const SORT_UNREAD = 4;
    /** Discussion sort: by group. */
    const SORT_GROUP = 5;

    /** Obtain no unread info */
    const UNREAD_NONE = 0;
    /** Obtain binary (yes there are unread messages) unread info */
    const UNREAD_BINARY = 1;
    /** Obtain the count of unread discussions */
    const UNREAD_DISCUSSIONS = 2;

    /** Constant used if there is no post quota in effect */
    const QUOTA_DOES_NOT_APPLY = -1;

    /** Link constant: HTML link (&amp;) */
    const PARAM_HTML = 1;
    /** Link constant: standard link (&) */
    const PARAM_PLAIN = 2;
    /** Link constant: HTML form input fields */
    const PARAM_FORM = 3;
    /** Link bitfield: HTML link (&amp;) with 'guess' for clone */
    const PARAM_UNKNOWNCLONE = 16;

    /**
     * Special parameter used when requesting a forum 'directly' from a course
     * (so that we know it will either have no clone id, or the clone id will
     * be the same as the cmid).
     */
    const CLONE_DIRECT = -1;
    /**
     * Special parameter used when requesting a forum in a situation where we
     * do not know what is the appropriate clone to use. In that case the
     * system will 'guess' based on the user's access permissions
     */
    const CLONE_GUESS = -2;

    /** Discussion moderator post identity: standard post.*/
    const ASMODERATOR_NO = 0;
    /** Discussion moderator post identity: self as moderator.*/
    const ASMODERATOR_IDENTIFY = 1;
    /** Discussion moderator post identity: anonymously as moderator.*/
    const ASMODERATOR_ANON = 2;

    // Constants defining grading options.
    const FORUMNG_NO_RATING = 0;// No grade (default).
    const FORUMNG_RATING_OBSOLETE = 1;// Forumng ratings (obsolete).
    const FORUMNG_STANDARD_RATING = 2;// Ratings (standard).

    // Static methods
    /*///////////////*/

    /**
     * Obtains list of available per-forum subscription type options.
     * @return array Array from subscription constant (integer) => description
     *   in current language
     */
    public static function get_subscription_options() {
        return array(
            self::SUBSCRIPTION_PERMITTED =>get_string('subscription_permitted', 'forumng'),
            self::SUBSCRIPTION_FORCED =>get_string('subscription_forced', 'forumng'),
            self::SUBSCRIPTION_INITIALLY_SUBSCRIBED =>
                get_string('subscription_initially_subscribed', 'forumng'),
            self::SUBSCRIPTION_NOT_PERMITTED =>
                get_string('subscription_not_permitted', 'forumng'));
    }

    /**
     * Obtains list of available per-forum feed type options.
     * @return array Array from feedtype constant (integer) => description
     *   in current language
     */
    public static function get_feedtype_options() {
        return array(
            self::FEEDTYPE_NONE=>get_string('feedtype_none', 'forumng'),
            self::FEEDTYPE_DISCUSSIONS=>get_string('feedtype_discussions', 'forumng'),
            self::FEEDTYPE_ALL_POSTS=>get_string('feedtype_all_posts', 'forumng')
        );
    }

    /**
     * Obtains list of available per-forum feed item count options.
     * @return array Array from feed item value (integer) => description
     *   in current language (probably just the same integer)
     */
    public static function get_feeditems_options() {
        return array(
            1=>1,
            2=>2,
            3=>3,
            4=>4,
            5=>5,
            10=>10,
            15=>15,
            20=>20,
            25=>25,
            30=>30,
            40=>40,
            50=>50);
    }

    /**
     * @param bool $midsentence True if the result is being used in the middle
     *   of a sentence (then we use 'day' rather than '1 day')
     * @return array Array of available post-period options (keys) to the text
     *   versions of those options (values).
     */
    public static function get_max_posts_period_options($midsentence = false) {
        $options = array();
        $options[60*60*24] = ($midsentence ? '' : '1 ') . get_string('day');
        $options[60*60*24*2] = '2 '.get_string('days');
        $options[60*60*24*7] = '7 '.get_string('days');
        $options[60*60*24*14] = '14 '.get_string('days');
        return $options;
    }

    /**
     * @param bool $text True if we want in text format not number
     * @param bool $midsentence True if the result is being used in the middle
     *   of a sentence (then we use 'day' rather than '1 day')
     * @return mixed The number (seconds) or text description of the max-posts
     *   period of the current foru (only valid if there is one)
     */
    public function get_max_posts_period($text = false, $midsentence = false) {
        if ($text) {
            $options = self::get_max_posts_period_options($midsentence);
            return $options[$this->forumfields->maxpostsperiod];
        } else {
            return $this->forumfields->maxpostsperiod;
        }
    }

    /**
     * @return array Array of grading option => description
     */
    public static function get_grading_options() {
        return array (
            self::GRADING_NONE => get_string('grading_none', 'forumng'),
            self::GRADING_MANUAL => get_string('teacher_grades_students', 'forumng'),
            self::GRADING_AVERAGE => get_string('grading_average', 'forumng'),
            self::GRADING_COUNT => get_string('grading_count', 'forumng'),
            self::GRADING_MAX => get_string('grading_max', 'forumng'),
            self::GRADING_MIN => get_string('grading_min', 'forumng'),
            self::GRADING_SUM => get_string('grading_sum', 'forumng'));
    }

    /** @return bool True if read-tracking is enabled */
    public static function enabled_read_tracking() {
        global $CFG;
        return $CFG->forumng_trackreadposts ? true : false;
    }

    /** @return int Number of days that read-tracking data is kept for */
    public static function get_read_tracking_days() {
        global $CFG;
        return $CFG->forumng_readafterdays;
    }

    /** @return int The oldest time (seconds since epoch) for which
     *     read-tracking data should be kept */
    public static function get_read_tracking_deadline() {
        return time()-self::get_read_tracking_days()*24*3600;
    }

    /**
     * @return bool True if the current user has the option selected to
     *   automatically mark discussions as read
     */
    public static function mark_read_automatically($userid = 0) {
        $userid = mod_forumng_utils::get_real_userid($userid);
        return !get_user_preferences('forumng_manualmark', '0', $userid);
    }

    /**
     * @param int $sort SORT_xx constant
     * @return string 'Sort by xxx' text in current language
     */
    public static function get_sort_title($sort) {
        return get_string('sortby', 'forumng', self::get_sort_field($sort));
    }

    /**
     * @param int $sort SORT_xx constant
     * @return string Title (in lower-case) of the field in current language
     */
    public static function get_sort_field($sort) {
        switch ($sort) {
            case self::SORT_DATE:
                return get_string('lastpost', 'forumng');
            case self::SORT_SUBJECT:
                return get_string('discussion', 'forumng');
            case self::SORT_AUTHOR:
                return get_string('startedby', 'forumng');
            case self::SORT_POSTS:
                return get_string('posts', 'forumng');
            case self::SORT_UNREAD:
                return get_string('unread', 'forumng');
            case self::SORT_GROUP:
                return get_string('group', 'forumng');
            default:
                throw new coding_exception("Unknown sort constant: $sort");
        }
    }

    /**
     * @param int $sort SORT_xx constant
     * @return string Letter used to identify this sort type
     */
    public static function get_sort_letter($sort) {
        switch ($sort) {
            case self::SORT_DATE: return 'd';
            case self::SORT_SUBJECT: return 's';
            case self::SORT_AUTHOR: return 'a';
            case self::SORT_POSTS: return 'p';
            case self::SORT_UNREAD: return 'u';
            case self::SORT_GROUP: return 'g';
            default:
                throw new coding_exception("Unknown sort constant: $sort");
        }
    }

    /**
     * @param string $letter Letter used to identify sort type
     * @return int SORT_xx constant
     */
    public static function get_sort_code($letter) {
        switch ($letter) {
            case 'd' : return self::SORT_DATE;
            case 's' : return self::SORT_SUBJECT;
            case 'a' : return self::SORT_AUTHOR;
            case 'p' : return self::SORT_POSTS;
            case 'u' : return self::SORT_UNREAD;
            case 'g' : return self::SORT_GROUP;
            default:
                throw new coding_exception("Unknown sort letter: $letter");
        }
    }

    /**
     * Obtains currently selected group for an activity, in the format that
     * forum methods want. (Which is slightly different to standard Moodle.)
     * @param object $cm Course-module
     * @param bool $update If true, updates group based on URL parameter
     * @return int Group ID; ALL_GROUPS if all groups; NO_GROUPS if no groups used
     */
    public static function get_activity_group($cm, $update=false) {
        $result = groups_get_activity_group($cm, $update);
        if ($result === false) {
            return self::NO_GROUPS;
        } else if ($result === 0) {
            return self::ALL_GROUPS;
        } else {
            return $result;
        }
    }

    /**
     * Obtains the forum type based on its 'info' object in modinfo (e.g. from
     * $modinfo->instances['forumng'][1234]). Usually this comes from the
     * custom data in the cm_info object.
     *
     * @param object $info Info object (either cm_info or something else)
     * @return string Forum type
     */
    private static function get_type_from_modinfo_info(cm_info $info) {
        if (isset($info->forumtype)) {
            // Only set when using get_modinfo_special for shared activity modules.
            return $info->forumtype;
        }
        return $info->customdata->type;
    }

    /** @return bool True if anonymous moderator posts enabled */
    public function get_can_post_anon() {
        return $this->forumfields->canpostanon;
    }

    // Object variables and accessors
    /*///////////////////////////////*/

    private $course, $cm, $context, $clonecourse, $clonecm, $clonecontext,
            $forumfields, $type, $cache;

    /** @return bool True if ratings are enabled */
    public function has_ratings() {
        return $this->forumfields->ratingscale!=0;
    }

    /**
     * @param int $created Date that post was created; use 0 to obtain
     *   a 'general' value supposing that posts are in range
     * @return bool True if current user can rate a post in this forum
     */
    public function can_rate($created=0) {
        return $this->has_ratings()
            && ($created == 0 || $created > $this->forumfields->ratingfrom)
            && ($created == 0 || $this->forumfields->ratinguntil==0
                || $created<$this->forumfields->ratinguntil)
            && has_capability('mod/forumng:rate', $this->get_context());
    }

    /**
     * @return bool True if current user can grade a user
     */
    public function can_grade() {
        return $this->get_grading() == self::GRADING_MANUAL
            && has_capability('mod/forumng:grade', $this->get_context());
    }

    /** @return int ID of course that contains this forum */
    public function get_course_id() {
        return $this->forumfields->course;
    }

    /**
     * Obtains course object. For non-shared forums this is
     * straightforward. For shared forums this usually returns the course
     * of the *clone* forum that is currently relevant, not directly of the
     * original forum.
     * @param bool $forcereal If set, always returns the course of the
     *   original forum and not of any clone
     * @return object Course object
     */
    public function get_course($forcereal = false) {
        global $DB;
        if ($this->is_shared() && !$forcereal) {
            if (!$this->clonecourse) {
                $cm = $this->get_course_module();
                $this->clonecourse = $DB->get_record('course', array('id' => $cm->course));
                if (!$this->clonecourse) {
                    throw new coding_exception('Cannot find clone course ' .
                            $cm->course);
                }
            }
            return $this->clonecourse;
        }
        return $this->course;
    }

    /**
     * Obtains course-module id. For non-shared forums this is
     * straightforward. For shared forums this usually returns the id
     * of the *clone* forum that is currently relevant, not directly of the
     * original forum.
     * @param bool $forcereal If set, always returns the id of the
     *   original forum and not of any clone
     * @return int ID of course-module instance
     */
    public function get_course_module_id($forcereal = false) {
        return $this->get_course_module($forcereal)->id;
    }

    /**
     * Obtains course-module instance. For non-shared forums this is
     * straightforward. For shared forums this usually returns the course-module
     * of the *clone* forum that is currently relevant, not directly of the
     * original forum.
     * @param bool $forcereal If set, always returns the course-module of the
     *   original forum and not of any clone
     * @return cm_info Course-module instance
     */
    public function get_course_module($forcereal = false) {
        global $CFG, $SESSION;
        if (empty($this->cm)) {
            throw new coding_exception('Course-module not set for this forum');
        }
        if ($this->is_shared() && !$forcereal) {
            if (!$this->clonecm) {
                throw new coding_exception('Clone reference not defined');
            }
            return $this->clonecm;
        }
        return $this->cm;
    }

    /**
     * Retrieves basic details for all the clones of this forum. (If any.)
     * @return array Array of objects (each one has ->context, ->courseid,
     *   ->courseshortname, ->forumname, and ->sectionid) for clones of this
     *   forum
     */
    public function get_clone_details() {
        global $DB;
        $recs = $DB->get_records_sql("
SELECT
    x.*, c.id AS courseid, c.shortname AS courseshortname, f.name AS forumname,
    f.id AS cloneforumngid, cm.section AS sectionid
FROM
    {forumng} f
    INNER JOIN {course_modules} cm ON f.id = cm.instance
    INNER JOIN {course} c ON cm.course = c.id
    INNER JOIN {modules} m ON cm.module = m.id
    INNER JOIN {context} x ON x.instanceid = cm.id
WHERE
    f.originalcmid = ?
    AND m.name = 'forumng'
    AND x.contextlevel = 70
ORDER BY
    c.shortname, f.name", array($this->cm->id));
        $contexts = array();
        foreach ($recs as $id => $rec) {
            $context = (object)array('courseid' => $rec->courseid,
                    'courseshortname' => $rec->courseshortname, 'forumname' => $rec->forumname,
                    'cloneforumngid' => $rec->cloneforumngid, 'sectionid' => $rec->sectionid);
            $context->context = mod_forumng_context_access::create_instance_from_record_public($rec);
            $contexts[$id] = $context;
        }
        return $contexts;
    }

    /**
     * Sets up the clone reference. The clone reference is used for shared
     * forums only. If a forum is a shared forum, you can access it from several
     * different course-module instances. The id of these instances is known as
     * the 'clone id'. We store the clone course-module in the forum object
     * so that when displaying links etc., these can retain the clone
     * information.
     * @param int $cloneid Clone id
     * @param object $clonecourse Optional clone course object (improves
     *   performance in cases where it needs to get the cm entry)
     */
    public function set_clone_reference($cloneid, $clonecourse=null) {
        global $SESSION, $DB;
        if ($cloneid == $this->cm->id || $cloneid == self::CLONE_DIRECT) {
            $this->clonecm = $this->cm;
            return;
        }
        if ($cloneid == self::CLONE_GUESS) {
            // We had better cache guesses in session because this is
            // time-consuming
            if (!isset($SESSION->forumng_cache)) {
                $SESSION->forumng_cache = new stdClass;
            }
            if (!isset($SESSION->forumng_cache->guesses)) {
                $SESSION->forumng_cache->guesses = array();
            }
            if (isset($SESSION->forumng_cache->guesses[$this->get_id()])) {
                return $SESSION->forumng_cache->guesses[$this->get_id()];
            }
            // Okay, no cached guess. First let's see if they can write to the
            // original forum because if so let's just use that
            if (has_capability('mod/forumng:replypost', $this->get_context(true))) {
                $this->clonecm = $this->cm;
                return;
            }

            // See if they can write to any context
            $clones = $this->get_clone_details();
            foreach ($clones as $clone) {
                if (has_capability('mod/forumng:replypost', $clone->context)) {
                    $this->clonecm = self::get_modinfo_cm(
                            $clone->context->instanceid);
                    break;
                }
            }

            // No? Well see if they can read to one
            if (!$this->clonecm) {
                foreach ($clones as $clone) {
                    if (has_capability('moodle/course:view', $clone->context)) {
                        $this->clonecm = self::get_modinfo_cm($clone->context->instanceid);
                        break;
                    }
                }
            }

            // Default, just use original
            if (!$this->clonecm) {
                $this->clonecm = $this->cm;
            }

            // Cache guess
            $SESSION->forumng_cache->guesses[$this->get_id()] = $this->clonecm;
            return;
        } else {
            // Get course-module record
            $this->clonecm = self::get_modinfo_cm($cloneid);
            // Security check that specifed cm is indeed a clone of this forum
            if ($DB->get_field('forumng', 'originalcmid', array('id' =>
                    $this->clonecm->instance)) != $this->cm->id) {
                throw new coding_exception("Not a clone of this forum: $cloneid");
            }
        }
    }

    /**
     * Gets a course-module object using get_fast_modinfo (so that it includes
     * additional data not in the actual table).
     * @param int $cmid ID of course-module
     * @param object $course Optional $course object to improve performance
     * @return cm_info Course-module object
     * @throws mod_forumng_exception If the cm isn't found or not in that course
     */
    private static function get_modinfo_cm($cmid, $course=null) {
        global $DB;
        if (!$course) {
            $course = $DB->get_record_sql("
SELECT
    c.*
FROM
    {course_modules} cm
    INNER JOIN {course} c ON c.id = cm.course
WHERE
    cm.id = ?", array($cmid), MUST_EXIST);
        }
        $modinfo = get_fast_modinfo($course);
        return $modinfo->get_cm($cmid);
    }

    /**
     * Obtains context object. For non-shared forums this is
     * straightforward. For shared forums this usually returns the context
     * of the *clone* forum that is currently relevant, not directly of the
     * original forum.
     * @param bool $forcereal If set, always returns the context of the
     *   original forum and not of any clone
     * @return context Context object
     */
    public function get_context($forcereal = false) {
        if ($this->is_shared() && !$forcereal) {
            if (!$this->clonecontext) {
                $this->clonecontext = context_module::instance($this->get_course_module_id());
            }
            return $this->clonecontext;
        }
        return $this->context;
    }

    /** @return int ID of this forum */
    public function get_id() {
        return $this->forumfields->id;
    }

    /** @return Name of forum */
    public function get_name() {
        return $this->forumfields->name;
    }

    /** @return reporting email of form */
    public function get_reportingemail() {
        return $this->forumfields->reportingemail;
    }

    /** @return array of reporting emails of forum */
    public function get_reportingemails() {
        global $CFG;
        $recipients = $this->get_reportingemail();
        if (!empty($recipients)) {
            $recipients = explode(';', $recipients);
        } else {
            $recipients = array();
        }
        if (!empty($CFG->forumng_reportunacceptable)) {
            // Check to see whether global forum report e-mail is already in recipients.
            if (!in_array($recipients, $CFG->forumng_reportunacceptable)) {
                // Add global recipient address to recipents array.
                $recipients[] = $CFG->forumng_reportunacceptable;
            }
        }
        return $recipients;
    }

    /** @return posting from of form */
    public function get_postingfrom() {
        return $this->forumfields->postingfrom;
    }
    /** @return posting until of form */
    public function get_postinguntil() {
        return $this->forumfields->postinguntil;
    }

    /**
     * @param $abbreviated If true, cuts down the length
     * @return string Introduction text
     */
    public function get_introduction() {
        return $this->forumfields->introduction;
    }

    /**
     * @return int FORMAT_xx constant for introduction format
     */
    public function get_introduction_format() {
        return $this->forumfields->introductionformat;
    }

    /** @return int GRADING_xx constant */
    public function get_grading() {
        return $this->forumfields->grading;
    }

    /**
     * @return int Scale used for ratings; 0 = disable,
     *   positive integer = 0..N scale, negative integer = defined scale
     */
    public function get_grading_scale() {
        return $this->forumfields->gradingscale;
    }

    /**
     * @return int Scale used for ratings; 0 = disable,
     *   positive integer = 0..N scale, negative integer = defined scale
     */
    public function get_rating_scale() {
        return $this->forumfields->ratingscale;
    }

    /**
     * @return array Array (in choose_from_menu format) of available rating
     *   options as value=>text
     */
    public function get_rating_options() {
        return mod_forumng_utils::make_grades_menu($this->forumfields->ratingscale);
    }

    /**
     * @return int Number of ratings a post must have in order to 'count'
     */
    public function get_rating_threshold() {
        return $this->forumfields->ratingthreshold;
    }

    /**
     * @return bool True if this forum is shared (has the 'allow sharing' flag
     *   set)
     */
    public function is_shared() {
        return $this->forumfields->shared ? true : false;
    }

    /**
     * @return bool True if this forum is a clone (has the 'original cmid'
     *   value set)
     */
    public function is_clone() {
        return $this->forumfields->originalcmid != null;
    }

    /**
     * If this forum is a clone, obtains the real one; otherwise just returns
     * this again.
     * @return mod_forumng Forum object (same or different)
     */
    public function get_real_forum() {
        if ($this->is_clone()) {
            return self::get_from_cmid($this->forumfields->originalcmid, $this->cm->id);
        } else {
            return $this;
        }
    }
    /**
     * @return int Number of discussions containing unread posts
     */
    public function get_num_unread_discussions() {
        if (!isset($this->forumfields->numunreaddiscussions)) {
            throw new coding_exception('Unread discussion count not obtained');
        }
        return $this->forumfields->numunreaddiscussions;
    }

    /**
     * @return int Number of discussions
     */
    public function get_num_discussions() {
        if (!isset($this->forumfields->numdiscussions)) {
            throw new coding_exception('Discussion count not obtained');
        }
        return $this->forumfields->numdiscussions;
    }

    /**
     * @return bool True if any discussions have unread posts
     */
    public function has_unread_discussions() {
        if (isset($this->forumfields->numunreaddiscussions)) {
            return $this->forumfields->numunreaddiscussions > 0;
        } else if (isset($this->forumfields->hasunreaddiscussions)) {
            return $this->forumfields->hasunreaddiscussions > 0;
        } else {
            throw new coding_exception('Unread discussion flag not obtained');
        }
    }

    /**
     * @return int Max bytes for attachments or -1 if upload is prevented
     */
    public function get_max_bytes() {
        if ($this->forumfields->attachmentmaxbytes) {
            if ($this->forumfields->attachmentmaxbytes == -1) {
                return -1;
            } else {
                return get_user_max_upload_file_size($this->get_context(),
                        $this->forumfields->attachmentmaxbytes);
            }
        } else {
            return get_user_max_upload_file_size($this->get_context(),
                    $this->get_course()->maxbytes);
        }
    }

    /**
     * @return int Activity group mode; may be VISIBLEGROUPS, SEPARATEGROUPS, or 0
     */
    public function get_group_mode() {
        if ($this->forumfields->shared) {
            // Performance up: shared forums never have groups
            return 0;
        }
        return groups_get_activity_groupmode($this->get_course_module(),
            $this->get_course());
    }

    /**
     * @return int Grouping in use for this activity; 0 for 'all groupings'
     */
    public function get_grouping() {
        return $this->get_course_module()->groupingid;
    }

    /** @return bool True if either site level or forum level reporting email is not null */
    public function has_reporting_email() {
        global $CFG;
        return $this->forumfields->reportingemail!= null ||
                (!empty($CFG->forumng_reportunacceptable) &&
                validate_email($CFG->forumng_reportunacceptable));
    }

    /**
     * Use to obtain link parameters when linking to any page that has anything
     * to do with forums.
     * @return string e.g. 'id=1234'
     */
    public function get_link_params($type) {
        if ($type == self::PARAM_FORM) {
            $id = '<input type="hidden" name="id" value="' . $this->cm->id . '" />';
        } else {
            $id = 'id=' . $this->cm->id;
        }
        return $id . $this->get_clone_param($type);
    }

    /**
     * Use to obtain link parameters as an array instead of as a string.
     * @return array e.g. ('id'=>123)
     */
    public function get_link_params_array() {
        $result = array('id' => $this->cm->id);
        $this->add_clone_param_array($result);
        return $result;
    }

    /**
     * Adds the clone parameter to an array of parameters, if it is necessary.
     * @param array $result Array that may have key 'clone' set
     */
    public function add_clone_param_array(&$result) {
        if ($this->is_shared()) {
            $result['clone'] = $this->get_course_module_id();
        }
    }

    /**
     * @param int $type PARAMS_xx constant
     * @return string Full URL to this forum
     */
    public function get_url($type) {
        global $CFG;
        return $CFG->wwwroot . '/mod/forumng/view.php?' .
                $this->get_link_params($type);
    }

    /**
     * @param int $type Parameter type (whether you want it escaped or not)
     * @return Either empty string or some variant of '&clone=N'
     */
    public function get_clone_param($type) {
        if (!$this->is_shared()) {
            return '';
        }
        if ($type & self::PARAM_UNKNOWNCLONE) {
            $cloneid = -2; // Special 'guess' vale
        } else {
            $cloneid = $this->get_course_module_id();
        }

        if ($type == self::PARAM_FORM) {
            return '<input type="hidden" name="clone" value="' .
                    $cloneid . '" />';
        }
        if (($type & 0xf) == self::PARAM_HTML) {
            $params = '&amp;';
        } else {
            $params = '&';
        }
        return $params . 'clone=' . $cloneid;
    }

    /** @return int forum ratings enabled */
    public function get_enableratings() {
        return $this->forumfields->enableratings;
    }

    /** @return int forum ratings from */
    public function get_ratingfrom() {
        return $this->forumfields->ratingfrom;
    }

    /** @return int forum ratings until */
    public function get_ratinguntil() {
        return $this->forumfields->ratinguntil;
    }

    // Factory methods
    /*////////////////*/

    /**
     * Creates a forum object and all related data from a single forum ID.
     * Note this is a forum ID and not a course-module ID.
     * @param int $id ID of forum
     * @param int $cloneid Clone identifier (0 if not a shared forum) or
     *   CLONE_DIRECT constant
     * @param bool $requirecm True if we require that the forum object
     *   has a valid course-module and context; false if the forum has only
     *   just been created so it doesn't have one yet
     * @param object $passcm Optional $cm object. Can be used in cases where
     *   get_fast_modinfo will fail (during course deletion).
     * @return mod_forumng Forum object
     */
    public static function get_from_id($id, $cloneid, $requirecm=true, $passcm=null) {
        global $COURSE, $DB;

        // Note that I experimented with code that retrieved this information
        // in a single query with some joins. It turned out to be fractionally
        // slower when working on a single machine, and only fractionally faster
        // when the database was on a separate machine, so we decided it wasn't
        // worth the maintenance effort over single queries.

        // Get forum data
        $forumfields = $DB->get_record('forumng', array('id' => $id), '*', MUST_EXIST);

        // Get course
        $courseid = $forumfields->course;
        if (!empty($COURSE->id) && $COURSE->id === $courseid) {
            $course = $COURSE;
        } else {
            $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
        }

        // NOTE: We obtain $cm via get_fast_modinfo. Reasons to do it this way:
        // * Modinfo has already been loaded since it comes from course table.
        // * The PHP loop search could be slow if there are many activities,
        //   but there would have to be quite a lot to make it slower than
        //   2 additional database queries (note: I did not performance-test
        //   this assumption).
        // * Other parts of the page might require the full $cm info that is
        //   only provided by get_fast_modinfo, so may as well call it now.
        $cm = null;
        if ($passcm) {
            // Modinfo not available, use supplied object instead
            $cm = $passcm;
        } else if ($requirecm) {
            $modinfo = get_fast_modinfo($course);
            foreach ($modinfo->cms as $possiblecm) {
                if ($possiblecm->instance==$id && $possiblecm->modname==='forumng') {
                    $cm = $possiblecm;
                    break;
                }
            }
        }
        if (!$cm && $requirecm) {
            // Just in case this is because the forum has only just been
            // created
            $cm = get_coursemodule_from_instance('forumng', $id, $course->id);
            if (!$cm) {
                throw new coding_exception(
                    "Couldn't find matching course-module entry for forum $id");
            }
        }

        // Get context
        $context = null;
        if ($cm) {
            $context = context_module::instance($cm->id);
        }

        // Construct forum
        $result = new mod_forumng($course, $cm, $context, $forumfields);
        if ($result->is_shared()) {
            if (!$cloneid) {
                throw new coding_exception(
                    "Shared forum {$cm->id} requires a clone id");
            }
            // This is not available when forum was only just created, so
            // don't call it
            if ($cm) {
                $result->set_clone_reference($cloneid);
            }
        }
        return $result;
    }

    /**
     * Creates a forum object and all related data from a single course-module
     * ID. Intended to be used from pages that refer to a particular forum.
     * @param int $cmid Course-module ID of forum
     * @param int $cloneid Clone identifier (0 if not a shared forum) or
     *   CLONE_DIRECT constant
     * @return mod_forumng Forum object
     */
    public static function get_from_cmid($cmid, $cloneid) {
        global $COURSE, $DB;

        // Get modinfo for current course, because we usually already have it
        $modinfo = get_fast_modinfo($COURSE);
        if (array_key_exists($cmid, $modinfo->cms)) {
            // It's in the current course, no need for another query
            $courseid = $COURSE->id;
        } else {
            // Get courseid
            $courseid = $DB->get_field('course_modules', 'course', array('id' => $cmid));
        }

        // Get course
        if (!empty($COURSE->id) && $COURSE->id === $courseid) {
            $course = $COURSE;
        } else {
            $course = $DB->get_record('course', array('id' => $courseid));
        }

        // Get course-module
        $modinfo = get_fast_modinfo($course);
        if (!array_key_exists($cmid, $modinfo->cms)) {
            throw new coding_exception(
                "Couldn't find forum with course-module ID $cmid");
        }
        $cm = $modinfo->cms[$cmid];

        if ($cm->modname != 'forumng') {
            throw new coding_exception(
                "Course-module ID $cmid is not a forum");
        }

        // Get forum data
        $forumfields = $DB->get_record('forumng', array('id' => $cm->instance));

        // Get context
        $context = context_module::instance($cm->id);

        // Construct forum
        $result = new mod_forumng($course, $cm, $context, $forumfields);
        if ($result->is_shared()) {
            if (!$cloneid) {
                throw new coding_exception(
                    "Shared forum $cmid requires a clone id");
            }
            $result->set_clone_reference($cloneid);
        }
        return $result;
    }

    // Object methods
    /*///////////////*/

    /**
     * Construct the forum's in-memory representation.
     * @param object $course Moodle course object. Optionally, can include only
     *   the 'id' field. (Otherwise should include all fields.)
     * @param object $cm Moodle course-module object. TODO Document required fields
     * @param object $forumfields Moodle forumng table record. Should include all fields.
     */
    public function __construct($course, $cm, $context, $forumfields) {
        $this->course = $course;
        $this->cm = $cm;
        $this->context = $context;
        $this->forumfields = $forumfields;
        $this->cache = new StdClass;
    }

    /**
     * Called by add_instance when the forum has just been created.
     * Note that $cm and $context are unavailable.
     * @param string $idnumber ID-number from create form
     */
    public function created($idnumber) {
        // Set up grade item if required
        $this->update_grades(0, $idnumber);

        // TODO Perform any initialisation required by forum type (single
        // discussion = create discussion)
    }

    /**
     * Called by update_instance when the forum has been updated.
     * @param $previousfields Previous copy of forum record
     */
    public function updated($previousfields) {
        global $DB;
        // If rating scale or grading on/off changes, we need to update
        // the grade information
        $gradechanged = false;
        if ($previousfields->grading != $this->forumfields->grading ||
            $previousfields->ratingscale != $this->forumfields->ratingscale ||
            $previousfields->gradingscale != $this->forumfields->gradingscale ||
            $previousfields->name != $this->forumfields->name) {
            $this->update_grades();
        }

        // TODO Call forum type for additional handling

        // If name changes and this is a shared forum, we need to go change
        // all the clones
        if ($previousfields->name !== $this->forumfields->name &&
            $this->is_shared()) {
            // Get clones
            $clones = $DB->get_records(
                    'forumng', array('originalcmid' => $this->get_course_module_id()));
            foreach ($clones as $clone) {
                $DB->set_field('forumng', 'name', $this->forumfields->name,
                        array('id' => $clone->id));
                rebuild_course_cache($clone->course, true);
            }
        }
        if (!$previousfields->shared && $this->is_shared()) {
            // Start sharing.
            $DB->set_field('forumng_subscriptions', 'clonecmid', $this->get_course_module_id(),
                    array('forumngid' => $previousfields->id));
        }
        if ($previousfields->shared && !$this->is_shared()) {
            // Stop sharing.
            $DB->set_field('forumng_subscriptions', 'clonecmid', null,
                    array('forumngid' => $previousfields->id));
        }
    }

    /**
     * Called by delete_instance. Deletes all the forum's data (but
     * not the actual forum record, delete_instance handles that).
     */
    public function delete_all_data() {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/tag/lib.php');

        // Delete per-post data
        $postquery = "
SELECT
    fp.id
FROM
    {forumng_discussions} fd
    INNER JOIN {forumng_posts} fp on fp.discussionid = fd.id
WHERE
    fd.forumngid = ?";
        $postparams = array($this->forumfields->id);
        $DB->execute("DELETE FROM {forumng_ratings}
            WHERE postid IN ($postquery)", $postparams);
        $DB->execute("DELETE FROM {forumng_read_posts}
                WHERE postid IN ($postquery)", $postparams);

        // Delete per-discussion data
        $discussionquery = "SELECT id FROM {forumng_discussions}
            WHERE forumngid = ?";
        $discussionparams = array($this->forumfields->id);
        $DB->execute("DELETE FROM {forumng_read}
            WHERE discussionid IN ($discussionquery)", $discussionparams);
        $DB->execute("DELETE FROM {forumng_posts}
            WHERE discussionid IN ($discussionquery)", $discussionparams);

        // Delete standard rating data.
        if ($this->get_enableratings() == mod_forumng::FORUMNG_STANDARD_RATING &&
                !$this->is_clone()) {
            require_once($CFG->dirroot . '/rating/lib.php');
            $delopt = new stdClass();
            $delopt->contextid = $this->get_context(true)->id;
            $delopt->component = 'mod_forumng';
            $delopt->ratingarea = 'post';

            $rm = new rating_manager();
            $rm->delete_ratings($delopt);
        }

        // Delete per-forum data
        if ($this->is_clone()) {
            $DB->delete_records('forumng_subscriptions',
                    array('clonecmid' => $this->get_course_module_id()));
        } else {
            $DB->delete_records('forumng_subscriptions',
                    array('forumngid' => $this->forumfields->id));
        }
        $DB->delete_records('forumng_discussions', array('forumngid' => $this->forumfields->id));

        // Delete tag instances.
        core_tag_tag::delete_instances('mod_forumng', 'forumng', $this->context->id);
        core_tag_tag::delete_instances('mod_forumng', 'groups', $this->context->id);
    }

    /**
     * Records an action in the Moodle log for current user.
     * @param string $action Action name - see datalib.php for suggested verbs
     *   and this code for example usage
     * @param string $replaceinfo Optional info text to replace default (which
     *   is just the forum id again)
     */
    public function log($action, $replaceinfo = '') {
        $info = $this->forumfields->id;
        if ($replaceinfo !== '') {
            $info = $replaceinfo;
        }
        $params = array(
            'context' => $this->get_context(),
            'objectid' => $this->forumfields->id,
            'other' => array('info' => $info, 'logurl' => $this->get_log_url())
        );
        switch ($action) {
            case 'subscribe':
                $classname = 'subscription_created';
                $params['relateduserid'] = substr($info, 0, strpos($info, ' '));
                unset($params['objectid']);// Unset forum id as event for subscriptions table.
                break;
            case 'unsubscribe':
                $classname = 'subscription_deleted';
                $params['relateduserid'] = substr($info, 0, strpos($info, ' '));
                unset($params['objectid']);// Unset forum id as event for subscriptions table.
                break;
            default:
                $classname = 'course_module_viewed';
                break;
        }
        $class = '\\mod_forumng\\event\\' . $classname;
        $event = $class::create($params);
        $event->add_record_snapshot('course_modules', $this->get_course_module());
        $event->add_record_snapshot('course', $this->get_course());
        $event->add_record_snapshot('forumng', $this->forumfields);
        $event->trigger();
    }

    /**
     * @return string URL of this discussion for log table, relative to the
     *   module's URL
     */
    public function get_log_url() {
        return 'view.php?' . $this->get_link_params(self::PARAM_PLAIN);
    }

    /**
     * Retrieves a list of discussions.
     * @param int $groupid Group ID or ALL_GROUPS
     * @param bool $viewhidden True if user can view hidden discussions
     * @param int $page Page to retrieve (1 = first page)
     * @param int $sort Sort order (SORT_xx constant)
     * @param bool $sortreverse Reverses the chosen sort
     * @param int $userid User ID, 0 = default, -1 if unread count not required
     * @param bool $ignoreinvalidpage true = default
     * @param int $tagid tag id of tag to get discussions that contain that tag.
     * @return mod_forumng_discussion_list
     */
    public function get_discussion_list(
        $groupid=self::ALL_GROUPS, $viewhidden=false,
        $page=1, $sort=self::SORT_DATE, $sortreverse=false, $userid=0, $ignoreinvalidpage=true, $tagid = null) {
        global $CFG, $DB, $USER;
        $userid = mod_forumng_utils::get_real_userid($userid);

        // Build list of SQL conditions
        /*/////////////////////////////*/

        // Correct forum.
        $conditionparams = array();
        $conditions = "fd.forumngid = ?";
        $conditionparams[] = $this->forumfields->id;

        // Group restriction
        if ($groupid) {
            $conditions .= " AND (fd.groupid = ? OR fd.groupid IS NULL)";
            $conditionparams[] = $groupid;
        }

        // View hidden posts
        if (!$viewhidden) {
            $now = time();
            $conditions .= " AND fd.deleted = 0";
            $conditions .= " AND (fd.timestart = 0 OR fd.timestart <= ?)".
              " AND (fd.timeend = 0 OR fd.timeend > ?)";
            $conditionparams[] = $now;
            $conditionparams[] = $now;
        }

        // Tag join sql if needed.
        $tagjoin = '';
        $hastag = false;
        if (!empty($tagid)) {
            $hastag = true;
            $tagjoin = "LEFT JOIN {tag_instance} ti ON ti.itemid = fd.id AND ti.itemtype = 'forumng_discussions'
                    AND ti.component = 'mod_forumng'";
            $conditions .= "AND ti.tagid = ?";
            $conditionparams[] = $tagid;
        }

        // Count all discussions
        /*//////////////////////*/

        if ($this->get_type()->has_unread_restriction()) {
            list($restrictionsql, $restrictionparams) =
                    $this->get_type()->get_unread_restriction_sql($this, $USER->id);
        } else {
            $restrictionsql = false;
        }
        if ($restrictionsql) {
            $querypart =
            $count = $DB->count_records_sql($sql = "
SELECT
    COUNT(1)
FROM
    {forumng_discussions} fd
    INNER JOIN {forumng_posts} fpfirst ON fpfirst.id = fd.postid
    INNER JOIN {forumng_posts} fplast ON fplast.id = fd.lastpostid
    INNER JOIN {forumng} f ON f.id = fd.forumngid
    INNER JOIN {course} c ON c.id = f.course
    INNER JOIN {course_modules} cm ON cm.instance = f.id AND cm.course = f.course
    INNER JOIN {modules} m ON m.id = cm.module
    $tagjoin
WHERE $conditions AND m.name = 'forumng' AND $restrictionsql",
                array_merge($conditionparams, $restrictionparams));
        } else {
            $count = $DB->count_records_sql(
                    "SELECT COUNT(1) FROM {forumng_discussions} fd $tagjoin WHERE " . $conditions,
                    $conditionparams);
        }

        // Check page index makes sense
        $pagecount = ceil($count / $CFG->forumng_discussionsperpage);
        if ($pagecount < 1) {
            $pagecount = 1;
        }
        if (($page > $pagecount || $page < 1) ) {
            if ($ignoreinvalidpage) {
                $page = 1;
            } else {
                throw new coding_exception("Invalid page $page, expecting 1..$pagecount");
            }
        }

        // Special case for no results
        if ($count == 0) {
            return new mod_forumng_discussion_list($page, $pagecount, $count);
        }

        // Retrieve selected discussions
        /*//////////////////////////////*/

        // Ordering
        $orderby = 'sticky DESC';
        switch ($sort) {
            case self::SORT_DATE:
                $orderby .= ', timemodified DESC';
                break;
            case self::SORT_SUBJECT:
                $orderby .= ', subject ASC';
                break;
            case self::SORT_AUTHOR:
                // This logic is based on code in fullname().
                $override = has_capability('moodle/site:viewfullnames',
                    $this->get_context(), $userid);
                $fullnamedisplay = $CFG->fullnamedisplay;
                if ($CFG->fullnamedisplay == 'language') {
                    // Controlled by lang pack - try and work out what it is doing.
                    $fakeuser = new stdClass();
                    $fakeuser->firstname = 'firstname';
                    $fakeuser->lastname = 'lastname';
                    $fullnamedisplay = get_string('fullnamedisplay', '', $fakeuser);
                }
                if ($fullnamedisplay == 'firstname lastname' ||
                    ($override && $fullnamedisplay == 'firstname')) {
                    $orderby .= ', fu_firstname ASC, fu_lastname ASC';
                } else if ($fullnamedisplay == 'lastname firstname') {
                    $orderby .= ', fu_lastname ASC, fu_firstname ASC';
                } else if ($fullnamedisplay == 'firstname') {
                    $orderby .= ', fu_firstname ASC';
                }
                if (!$override) {
                    if (!empty($CFG->forcefirstname)) {
                        $orderby = preg_replace('~, fu_firstname(ASC)?~', '', $orderby);
                    }
                    if (!empty($CFG->forcelastname)) {
                        $orderby = preg_replace('~, fu_lastname(ASC)?~', '', $orderby);
                    }
                }
                break;
            case self::SORT_POSTS:
                $orderby .= ', numposts DESC';
                break;
            case self::SORT_UNREAD:
                $orderby .= ', numposts-numreadposts DESC';
                break;
            case self::SORT_GROUP:
                $orderby .= ', groupname ASC';
                break;
            default:
                throw new coding_exception("Unknown SORT_xx constant $sort");
        }

        // swap ASC/DESC according to $sortreverse
        if ($sortreverse) {
            $orderby = str_replace('DESC', 'ASX', $orderby);
            $orderby = str_replace('ASC', 'DESC', $orderby);
            $orderby = str_replace('ASX', 'ASC', $orderby);
            $orderby = str_replace('sticky ASC', 'sticky DESC', $orderby);
        }

        // Ensure consistency by adding id ordering
        $orderby .= ', id DESC';

        // Limits
        $limitfrom = ($page-1) * $CFG->forumng_discussionsperpage;
        $limitnum = $CFG->forumng_discussionsperpage;

        // Do query
        $rs = mod_forumng_discussion::query_discussions($conditions, $conditionparams, $userid,
            $orderby, $limitfrom, $limitnum, $this, false, $hastag);

        $result = new mod_forumng_discussion_list($page, $pagecount, $count);
        foreach ($rs as $rec) {
            // Create a new discussion from the database details
            $discussion = new mod_forumng_discussion($this, $rec, true,
                mod_forumng_utils::get_real_userid($userid));

            // Give the discussion a chance to invalidate discussion
            // cache. This is so that if the user looks at a discussion
            // list, and it shows a newer post, then they click into the
            // discussion, they don't end up not seeing it!
            $discussion->maybe_invalidate_cache();

            // Add to results
            $result->add_discussion($discussion);
        }
        $rs->close();
        return $result;
    }

    /**
     * Creates a new discussion in this forum.
     * @param int $groupid Group ID for the discussion or null if it should show
     *   to all groups
     * @param string $subject Subject of message
     * @param string $message Message content
     * @param int $format Format of message content
     * @param bool $attachments True if discussion contains attachments
     * @param bool $mailnow True to mail ASAP, else false
     * @param int $timestart Visibility time of discussion (seconds since epoch) or null
     * @param int $timeend Time at which discussion disappears (seconds since epoch) or null
     * @param bool $locked True if discussion should be locked
     * @param bool $sticky True if discussion should be sticky
     * @param int $userid User ID or 0 for current user
     * @param bool $log True to log this
     * @param int $asmoderator values are ASMODERATOR_NO, ASMODERATOR_IDENTIFY or ASMODERATOR_ANON
     * @param array $tags array of tags to add to a discussion
     * @return array Array with 2 elements ($discussionid, $postid)
     */
    public function create_discussion($groupid,
            $subject, $message, $format, $attachments=false, $mailnow=false,
            $timestart=0, $timeend=0, $locked=false, $sticky=false,
            $userid=0, $log=true, $asmoderator = self::ASMODERATOR_NO, $tags = null) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/tag/lib.php');

        $userid = mod_forumng_utils::get_real_userid($userid);

        // Prepare discussion object
        $discussionobj = new StdClass;
        $discussionobj->forumngid = $this->forumfields->id;
        $discussionobj->groupid =
            ($groupid == self::ALL_GROUPS || $groupid==self::NO_GROUPS)
            ? null : $groupid;
        $discussionobj->postid = null; // Temporary until we create that first post
        $discussionobj->lastpostid = null;
        $discussionobj->timestart = $timestart;
        $discussionobj->timeend = $timeend;
        $discussionobj->deleted = 0;
        $discussionobj->locked = $locked ? 1 : 0;
        $discussionobj->sticky = $sticky ? 1 : 0;
        $discussionobj->modified = time();

        // Create discussion
        $transaction = $DB->start_delegated_transaction();
        $discussionobj->id = $DB->insert_record('forumng_discussions', $discussionobj);
        $newdiscussion = new mod_forumng_discussion($this, $discussionobj, false, -1);

        // Create initial post.
        $postid = $newdiscussion->create_root_post(
            $subject, $message, $format, $attachments, $mailnow, $userid, $asmoderator);

        // Update discussion so that it contains the post id
        $changes = new StdClass;
        $changes->id = $discussionobj->id;
        $changes->postid = $postid;
        $changes->lastpostid = $postid;
        $DB->update_record('forumng_discussions', $changes);

        $newdiscussion->log('add discussion');

        if (self::search_installed()) {
            mod_forumng_post::get_from_id($postid,
                    $this->get_course_module_id())->search_update();
        }

        // If tags add to tag_instance records.
        if ($tags != null) {
            core_tag_tag::set_item_tags('mod_forumng', 'forumng_discussions', $discussionobj->id, $this->context, $tags);
        }

        $transaction->allow_commit();
        return array($newdiscussion->get_id(), $postid);
    }

    /**
     * @return string Hash of the settings of this forum which could possibly
     *   affect cached discussion objects
     */
    public function get_settings_hash() {
        return md5(
            $this->forumfields->ratingscale .
            $this->forumfields->ratingfrom .
            $this->forumfields->ratinguntil .
            $this->forumfields->ratingthreshold .
            $this->forumfields->grading .
            $this->forumfields->ratingthreshold .
            $this->forumfields->typedata);
    }

    // Unread data
    /*////////////*/

    /**
     * Marks all discussions in this forum as read.
     * @param int $groupid Group user is looking at (will mark all discussions
     *   in this group, plus all in the 'all/no groups' section; ALL_GROUPS
     *   marks regardless of group; NO_GROUPS marks those without group)
     * @param int $time Time to mark it read at (0 = now)
     * @param int $userid User who's read the discussion (0=current)
     */
    public function mark_read($groupid, $time=0, $userid=0) {
        global $DB;
        if (!$userid) {
            global $USER;
            $userid = $USER->id;
        }
        if (!$time) {
            $time = time();
        }
        $transaction = $DB->start_delegated_transaction();

        $conditionparams = array();
        $conditions = "fd.forumngid = ?";
        $conditionparams[] = $this->forumfields->id;

        // Group restriction
        if ($groupid) {
            if ($groupid == self::NO_GROUPS) {
                $conditions .= " AND fd.groupid IS NULL";
            } else {
                // Separate group
                $conditions .= " AND (fd.groupid = ? OR fd.groupid IS NULL)";
                $conditionparams[] = $groupid;
            }
        }

        $conditions .= " AND fplast.modified >= ?";
        $conditionparams[] = self::get_read_tracking_deadline();
        $rs = mod_forumng_discussion::query_discussions($conditions, $conditionparams, -1,
                '', '', '', $this);
        $discussions = array();
        $discussionids = array();
        $forumtype = $this->get_type();
        foreach ($rs as $rec) {
            // Create a new discussion from the database details
            $discussion = new mod_forumng_discussion($this, $rec, true, -1);
            // Let forum type check permission too
            if ($forumtype->can_view_discussion($discussion, $userid)) {
                // Check time limits / delete
                if ($discussion->is_currently_visible() ||
                        has_capability('mod/forumng:viewallposts',
                        $this->get_context(), $userid)) {
                    $discussions[$discussion->get_id()] = $discussion;
                    $discussionids[] = $discussion->get_id();
                }
            }
        }
        $rs->close();

        if (count($discussions) > 0) {
            // Delete any existing records for those discussions
            list($inorequals, $inparams) =
                    mod_forumng_utils::get_in_array_sql('discussionid', $discussionids);
            $DB->execute("DELETE FROM {forumng_read} WHERE userid = ? AND $inorequals",
                    array_merge(array($userid), $inparams));

            // Add new record for each discussion
            foreach ($discussions as $discussion) {
                $readrecord = new StdClass;
                $readrecord->userid = $userid;
                $readrecord->discussionid = $discussion->get_id();
                $readrecord->time = $time;
                $DB->insert_record('forumng_read', $readrecord);
            }

            // Delete any individual post records for discussions as now redundant.
            $DB->execute("DELETE FROM {forumng_read_posts}
                            WHERE postid IN(
                                  SELECT id FROM {forumng_posts}
                                   WHERE $inorequals)
                              AND userid = ? AND time <= ?",
                    array_merge($inparams, array($userid, $time)));
        }

        $transaction->allow_commit();
    }

    // Subscriptions
    /*//////////////*/

    /**
     * Subscribes a user to this forum. (Assuming it permits manual subscribe/
     * unsubscribe.)
     * @param $userid User ID (default current)
     * @param $groupid Group ID to unsubscribe to (default null = whole forum)
     * @param $log True to log this
     */
    public function subscribe($userid=0, $groupid=null, $log=true) {
        global $DB;
        $userid = mod_forumng_utils::get_real_userid($userid);
        // For shared forums, we subscribe to a specific clone
        if ($this->is_shared()) {
            $clonecmid = $this->get_course_module_id();
            $clonevalue = '=?';
            $cloneparams = array($clonecmid);
        } else {
            $clonecmid = null;
            $clonevalue = 'IS NULL';
            $cloneparams = array();
        }
        $transaction = $DB->start_delegated_transaction();
        // delete all the subscriptions to the discussions in the entire forum or
        // the discussions in the specified group if any
        if (!$groupid) {
            // Delete all the subscriptions to the discussions/groups in the entire forum.
            $DB->execute(
                    "DELETE FROM {forumng_subscriptions} " .
                    "WHERE userid = ? AND forumngid = ? AND clonecmid $clonevalue " .
                    "AND subscribed = 1 AND (discussionid IS NOT NULL OR groupid IS NOT NULL)",
                    array_merge(array($userid, $this->forumfields->id), $cloneparams));
            $existing = $DB->get_record('forumng_subscriptions',
                    array('userid' => $userid, 'forumngid' => $this->forumfields->id,
                    'clonecmid' => $clonecmid), '*', IGNORE_MISSING);
            if (!$existing) {
                $subrecord = new StdClass;
                $subrecord->userid = $userid;
                $subrecord->forumngid = $this->forumfields->id;
                $subrecord->subscribed = 1;
                $subrecord->clonecmid = $clonecmid;
                $DB->insert_record('forumng_subscriptions', $subrecord);
            } else if (!$existing->subscribed) {
                // See if this is initial-subscription and we are subscribed by
                // default, if so just remove the record
                if ($this->is_initially_subscribed($userid, true)) {
                    $DB->delete_records('forumng_subscriptions', array('id' => $existing->id));
                } else {
                    $subchange = new StdClass;
                    $subchange->id = $existing->id;
                    $subchange->subscribed = 1;
                    $DB->update_record('forumng_subscriptions', $subchange);
                }
            }
        } else {
            // Delete all the subscriptions to the discussions in the the specified group if any.
            $discussionquery = "SELECT id FROM {forumng_discussions} " .
                    "WHERE forumngid = ? AND groupid = ?";
            $discussionparams = array($this->forumfields->id, $groupid);
            // Share forum doesn't support group mode so we don't check clonecmid.
            $DB->execute(
                    "DELETE FROM {forumng_subscriptions} " .
                    "WHERE userid = ? AND forumngid = ? AND subscribed = 1 " .
                    "AND discussionid IS NOT NULL AND discussionid IN ($discussionquery)",
                    array_merge(array($userid, $this->forumfields->id), $discussionparams));
            // Do some housekeeping in case some invalid data deleting any group subscription
            // if any (shouldn't have any records to be deleted ideally)
            $DB->delete_records('forumng_subscriptions', array('userid' => $userid,
                    'forumngid' => $this->forumfields->id, 'groupid' => $groupid));
            $subrecord = new StdClass;
            $subrecord->userid = $userid;
            $subrecord->forumngid = $this->forumfields->id;
            $subrecord->subscribed = 1;
            $subrecord->groupid = $groupid;

            $DB->insert_record('forumng_subscriptions', $subrecord);
        }
        $transaction->allow_commit();
        if ($log) {
            $this->log('subscribe', $userid . ' ' .
                    ($groupid ? 'group ' . $groupid : 'all'));
        }
    }

    /**
     * Unsubscribes a user from this forum.
     * @param $userid User ID (default current)
     * @param $groupid Group ID to unsubscribe from (default null = whole forum)
     * @param $log True to log this
     */
    public function unsubscribe($userid=0, $groupid=null, $log=true) {
        global $DB;
        $userid = mod_forumng_utils::get_real_userid($userid);
        // For shared forums, we subscribe to a specific clone
        if ($this->is_shared()) {
            $clonecmid = $this->get_course_module_id();
            $clonevalue = '=?';
            $cloneparams = array($clonecmid);
        } else {
            $clonecmid = null;
            $clonevalue = 'IS NULL';
            $cloneparams = array();
        }
        if (!$groupid) {
            // Unsubscribe from the whole forum; deleting all the discussion/group subscriptions.
            $DB->execute("DELETE FROM {forumng_subscriptions} " .
                    "WHERE userid = ? AND forumngid = ? AND clonecmid $clonevalue AND " .
                    "subscribed = 1 " . "AND (discussionid IS NOT NULL OR groupid IS NOT NULL)",
                    array_merge(array($userid, $this->forumfields->id), $cloneparams));
            if ($this->is_initially_subscribed($userid, true)) {
                $existing = $DB->get_record('forumng_subscriptions',
                    array('userid' => $userid, 'forumngid' => $this->forumfields->id,
                    'clonecmid' => $clonecmid));
                if (!$existing) {
                    $subrecord = new StdClass;
                    $subrecord->userid = $userid;
                    $subrecord->forumngid = $this->forumfields->id;
                    $subrecord->subscribed = 0;
                    $subrecord->clonecmid = $clonecmid;

                    $DB->insert_record('forumng_subscriptions', $subrecord);
                } else if ($existing->subscribed) {
                    $subchange = new StdClass;
                    $subchange->id = $existing->id;
                    $subchange->subscribed = 0;

                    $DB->update_record('forumng_subscriptions', $subchange);
                }
            } else {
                $DB->delete_records('forumng_subscriptions', array('userid' => $userid,
                    'forumngid' => $this->forumfields->id, 'clonecmid' => $clonecmid));
            }
        } else {
            // Unsubscribe from the specified group; remove all the subscritions
            // to the discussions which belongs to the group if any.
            $discussionquery = "SELECT id FROM {forumng_discussions}
                WHERE forumngid = ? AND groupid = ?";
            $discussionparams = array($this->forumfields->id, $groupid);
            $DB->execute(
                "DELETE FROM {forumng_subscriptions} " .
                "WHERE userid = ? AND forumngid = ? AND subscribed = 1 " .
                "AND discussionid IS NOT NULL AND discussionid IN ($discussionquery)",
                array_merge(array($userid, $this->forumfields->id), $discussionparams));
            $DB->delete_records('forumng_subscriptions', array('userid' => $userid,
                    'forumngid' => $this->forumfields->id, 'groupid' => $groupid));
        }
        if ($log) {
            $this->log('unsubscribe', $userid . ' ' .
                    ($groupid ? 'group ' . $groupid : 'all'));
        }
    }

    /**
     * Determines whether a user can subscribe/unsubscribe to a forum.
     * @param int $userid User ID, 0 for default
     * @return bool True if user is allowed to change their subscription
     */
    public function can_change_subscription($userid=0) {
        switch ($this->get_effective_subscription_option()) {
            case self::SUBSCRIPTION_NOT_PERMITTED:
                return false;

            case self::SUBSCRIPTION_FORCED:
                if ($this->is_forced_to_subscribe($userid)) {
                    return false;
                }

                // Fall through
            default:
                return $this->can_be_subscribed($userid);
        }
    }

    /**
     * Checks whether a user can be subscribed to the forum, regardless of
     * subscription option. Includes a variety of other checks. [These are
     * supposed to be the same as checks done when building the list of people
     * for email.]
     * @param int $userid User ID or 0 for current
     * @return bool True if user can be subscribed
     */
    private function can_be_subscribed($userid=0) {
        global $USER, $CFG;
        $userid = mod_forumng_utils::get_real_userid($userid);
        $cm = $this->get_course_module();
        $course = $this->get_course();
        $context = $this->get_context();

        // Guests cannot subscribe
        if (!isloggedin() || isguestuser($userid)) {
            return false;
        }

        // Get from cache if possible
        if (!isset($this->cache->can_be_subscribed)) {
            $this->cache->can_be_subscribed = array();
        }
        if (array_key_exists($userid, $this->cache->can_be_subscribed)) {
            return $this->cache->can_be_subscribed[$userid];
        }

        // This is not a loop, just so I can use break
        do {
            // Check user can see forum
            if (!has_capability('mod/forumng:viewdiscussion', $context, $userid)) {
                $result = false;
                break;
            }
            // For current user, can take shortcut
            if ($userid == $USER->id) {
                if (empty($cm->uservisible)) {
                    $uservisible = false;
                } else {
                    $uservisible = true;
                }
                if (!$uservisible) {
                    $result = false;
                    break;
                }
            } else {
                $visible = $cm->visible;
                $info = new \core_availability\info_module($cm);
                $visible = $visible &&
                    $info->is_available($crap, false, $userid);
                if (!$visible && !has_capability(
                    'moodle/site:viewhiddenactivities', $context, $userid)) {
                    $result = false;
                    break;
                }
            }
            if ($this->get_group_mode() == SEPARATEGROUPS &&
                    !has_capability('moodle/site:accessallgroups', $context, $userid)) {
                // Limit it to people within groups in the grouping, if one is selected.
                $groupobjs = groups_get_all_groups($course->id, $userid, $cm->groupingid, 'g.id');
                if (!$groupobjs || count($groupobjs) == 0) {
                    $result = false;
                    break;
                }
            }
            $result = true;
            break;
        } while (false);

        $this->cache->can_be_subscribed[$userid] = $result;
        return $result;
    }

    /**
     * Determines whether a user is forced to subscribe.
     * @param int $userid User ID or 0 for current
     * @param bool $expectingquery True if expecting query (note this
     *   value is ignored if you specify a non-current userid, then it will
     *   always make queries)
     * @return bool True if forced to subscribe
     */
    public function is_forced_to_subscribe($userid=0, $expectingquery=false) {

        // Only for forced-subscription forums, duh
        $subscriptionoption = $this->get_effective_subscription_option();
        if ($subscriptionoption != self::SUBSCRIPTION_FORCED) {
            return false;
        }

        return $this->is_in_auto_subscribe_list($userid, $expectingquery);
    }

    /**
     * Determines whether a user is initially subscribed.
     * @param int $userid User ID or 0 for current
     * @param bool $expectingquery True if expecting query (note this
     *   value is ignored if you specify a non-current userid, then it will
     *   always make queries)
     * @return bool True if initially subscribe
     */
    public function is_initially_subscribed($userid=0, $expectingquery=false) {

        // Only for initial-subscription forums, duh
        $subscriptionoption = $this->get_effective_subscription_option();
        if ($subscriptionoption != self::SUBSCRIPTION_INITIALLY_SUBSCRIBED) {
            return false;
        }

        return $this->is_in_auto_subscribe_list($userid, $expectingquery);
    }

    /**
     * Determines whether a user is in the auto-subscribe list for this forum
     * (applies in initial/forced subscription forums).
     * @param int $userid User ID or 0 for current
     * @param bool $expectingquery True if expecting query (note this
     *   value is ignored if you specify a non-current userid, then it will
     *   always make queries; also it always makes a query on the first request
     *   in session)
     * @return bool True if forced to subscribe
     */
    public function is_in_auto_subscribe_list($userid=0, $expectingquery=false) {
        global $DB, $USER;
        $userid = mod_forumng_utils::get_real_userid($userid);

        // Check standard subscription allowed.
        if (!$this->can_be_subscribed($userid)) {
            return false;
        }

        // Check user has role in subscribe roles.
        if (!isset($USER->forumng_enrolcourses[$userid])) {
            if (!isset($USER->forumng_enrolcourses)) {
                $USER->forumng_enrolcourses = array();
            }
            if (isset($USER) && $userid == $USER->id) {
                // Note: This always makes a query the first time in the session :(
                $USER->forumng_enrolcourses[$userid] = enrol_get_my_courses('id');
            } else {
                $USER->forumng_enrolcourses[$userid] = enrol_get_users_courses($userid, true, 'id');
            }
        }
        return array_key_exists($this->get_course_id(), $USER->forumng_enrolcourses[$userid]);
    }

    /**
     * Return the subscription info of the user.
     * @param int $userid User ID or 0 for current
     * @param bool $expectingquery True if expecting query (note this
     *   value is ignored if you specify a non-current userid, then it will
     *   always make queries)
     * @return object with three fields, $wholeforum, $discussionids (associated array
     * with discussion id as the key and its group id as value and $groupids
     * If $wholeforum = true and both $discussionids and $groupids is empty,
     * subscribed to the whole forum;
     * If $wholeforum = false and $discussionids isn't empty while the groupids is empty,
     * subscribed to a list of discussions
     * If $wholeforum = false and $discussionids is empty while the groupids is not empty,
     * subscribed to a list of groups
     * If $wholeforum = false and both $discussionids and groupids is not empty,
     * subscribed to both a list of discussions and a list of groups
     */
    public function get_subscription_info($userid=0, $expectingquery=false) {
        global $DB, $FORUMNG_CACHE;
        $userid = mod_forumng_utils::get_real_userid($userid);

        if (!isset($FORUMNG_CACHE)) {
            $FORUMNG_CACHE = new stdClass;
        }
        if (!isset($FORUMNG_CACHE->subscriptioninfo)) {
            $FORUMNG_CACHE->subscriptioninfo = array();
        }
        $key = $userid . ':' . $this->get_id();
        if (array_key_exists($key, $FORUMNG_CACHE->subscriptioninfo)) {
            return $FORUMNG_CACHE->subscriptioninfo[$key];
        }

        $user = (object)(array('wholeforum'=>false, 'discussionids'=>array(),
                'groupids'=>array()));

        // If subscription's banned, you ain't subscribed
        $subscriptionoption = $this->get_effective_subscription_option();
        if ($subscriptionoption == self::SUBSCRIPTION_NOT_PERMITTED) {
            $FORUMNG_CACHE->subscriptioninfo[$userid] = $user;
            return $user;
        }

        // Make extra checks that subscription is allowed
        $userid = mod_forumng_utils::get_real_userid($userid);
        if (!$this->can_be_subscribed($userid)) {
            $FORUMNG_CACHE->subscriptioninfo[$userid] = $user;
            return $user;
        }

        // Forced subscription
        if ($this->is_forced_to_subscribe($userid, $expectingquery)) {
            $user->wholeforum = true;
            $FORUMNG_CACHE->subscriptioninfo[$userid] = $user;
            return $user;
        }

        if ($this->is_initially_subscribed($userid, $expectingquery)) {
            $user->wholeforum = true;
        }

        // For shared forums, we subscribe to a specific clone
        if ($this->is_shared()) {
            $clonevalue = '=?';
            $cloneparams = array($this->get_course_module_id());
        } else {
            $clonevalue = 'IS NULL';
            $cloneparams = array();
        }
        $rs = $DB->get_recordset_sql($sql = "
SELECT s.subscribed, s.discussionid, s.groupid, fd.groupid AS discussiongroupid,
discussiongm.id AS discussiongroupmember, subscriptiongm.id AS subscriptiongroupmember
FROM
    {forumng_subscriptions} s
    LEFT JOIN {forumng_discussions} fd ON fd.id = s.discussionid
    LEFT JOIN {groups_members} discussiongm ON fd.groupid = discussiongm.groupid
        AND s.userid = discussiongm.userid
    LEFT JOIN {groups_members} subscriptiongm ON s.groupid = subscriptiongm.groupid
        AND s.userid = subscriptiongm.userid
WHERE
    s.forumngid = ?
    AND s.userid = ?
    AND (fd.forumngid = ? OR s.discussionid IS NULL)
    AND s.clonecmid $clonevalue",
                array_merge(array($this->forumfields->id, $userid, $this->forumfields->id),
                $cloneparams));

        $context = $this->get_context();
        $canviewdiscussion = has_capability('mod/forumng:viewdiscussion', $context, $userid);
        $canaccessallgroups = has_capability('moodle/site:accessallgroups', $context, $userid);
        foreach ($rs as $rec) {

            if ($rec->subscribed) {
                // Rewrite the whole block.
                if ($rec->groupid) {
                    // Subscrbied to a list of groups only
                    // Only allow this row to count if the user has access to subscribe to group
                    // 1. User must have mod/forumng:viewdiscussion
                    // 2. One of the following must be true:
                    //    a. Forum is set to visible groups (if forum is set for no groups,
                    //       we will ignore this group subscription
                    //    b. User belongs to the group (check the field)
                    //    c. User has accessallgroups
                    $groupok = $this->get_group_mode() == VISIBLEGROUPS ||
                            $rec->subscriptiongroupmember || $canaccessallgroups;
                    if ($canviewdiscussion && $groupok ) {
                        $user->groupids[$rec->groupid] = $rec->groupid;
                    }
                } else if ($rec->discussionid) {
                    // $groupok if disucssion belong to all groups or the user in the same group
                    // as the discussion belongs to orthe forum is set to be visible groups
                    $groupok = !$rec->discussiongroupid || $rec->discussiongroupmember ||
                        $this->get_group_mode() == VISIBLEGROUPS || $canaccessallgroups;
                    if ($canviewdiscussion && $groupok) {
                        $user->discussionids[$rec->discussionid] = $rec->discussiongroupid;
                    }
                } else {
                    // Subscribed to the whole forum, quit the loop as no more records should
                    // match if the database data isn't messed up.
                    // Only allow this row to count if the user has access to subscribe to
                    // whole forum
                    //     1. User must have mod/forumng:viewdiscussion
                    //     2. One of the following must be true:
                    //        a. Forum is set to no groups, or to visible groups
                    //        b. User has accessallgroups
                    $groupok = $this->get_group_mode() == VISIBLEGROUPS ||
                        $this->get_group_mode() == NOGROUPS || $canaccessallgroups;
                    if ($canviewdiscussion && $groupok) {
                        $user->wholeforum = true;
                        break;
                    }
                }
            } else if ($subscriptionoption == self::SUBSCRIPTION_INITIALLY_SUBSCRIBED) {
                // This is an 'unsubscribe' request. These are only allowed
                // for initial-subscription, otherwise ignored
                $user->wholeforum = false;
            }
        }
        $rs->close();

        // clear the discussions array if wholeforum is true
        if ($user->wholeforum) {
            $user->discussionids = array ();
            $user->groupids = array ();
        }

        $FORUMNG_CACHE->subscriptioninfo[$userid] = $user;
        return $user;
    }

    /**
     * Obtains current forum subscription option, taking into account global
     * setting as well as this forum.
     * @return int SUBSCRIPTION_xx constant
     */
    public function get_effective_subscription_option() {
        global $CFG;

        // Global 'force' option overrides local option if set
        $result = $CFG->forumng_subscription;
        if ($result == -1) {
            $result = $this->forumfields->subscription;
        }
        return $result;
    }

    /**
     * Obtains current forum feed type option, taking into account global
     * setting as well as this forum.
     * @return int FEEDTYPE_xx constant
     */
    public function get_effective_feed_option() {
        global $CFG;

        // Global 'force' used if set
        $result = $CFG->forumng_feedtype;

        // Feeds can be disabled globally or for whole module
        if (!($CFG->forumng_enablerssfeeds && $CFG->enablerssfeeds)) {
            $result = self::FEEDTYPE_NONE;
        }

        // If none of the above applied, use the module's setting
        if ($result == -1) {
            $result = $this->forumfields->feedtype;
        }

        return $result;
    }

    /**
     * Obtains the list of people who are forced to subscribe to the forum
     * (if forced) or are by default subscribed (if initial).
     *
     * On a shared forum, this returns only the relevant list for the current
     * clone of the forum.
     * @param int $groupid If specified, restricts list to this group id
     * @return array Array of partial user objects (with enough info to send
     *   email and display them)
     */
    public function get_auto_subscribers($groupid = self::ALL_GROUPS) {
        global $DB;
        switch ($this->get_effective_subscription_option()) {
            case self::SUBSCRIPTION_FORCED :
            case self::SUBSCRIPTION_INITIALLY_SUBSCRIBED :
                break;
            default:
                return array();
        }

        // Gets all users who are enrolled on the course and have viewdiscussions capability,
        // limited to a group if specified.
        list($enrolsql, $enrolparams) = get_enrolled_sql($this->get_context(),
                'mod/forumng:viewdiscussion', $groupid >= 0 ? $groupid : 0, true);
        $users =  $DB->get_records_sql("SELECT " .
                mod_forumng_utils::select_username_fields('', true) .
                " FROM {user} u WHERE u.id IN ($enrolsql)", $enrolparams);
        $avail = new \core_availability\info_module($this->get_course_module());
        $users = $avail->filter_user_list($users);
        if ($groupid == self::ALL_GROUPS && $groups = $this->get_permitted_groups()) {
            // Separate groups grouping enabled forum (+ group not specified) - must be in a group.
            raise_memory_limit(MEMORY_EXTRA);
            $groupmembers = get_users_by_capability($this->get_context(),
                        'mod/forumng:viewdiscussion', 'u.id', '', '', '',
                        $groups, '', 0, 0, true);
            $newusers = array();
            foreach ($users as $id => $ob) {
                if (array_key_exists($id, $groupmembers)) {
                    $newusers[$id] = $ob;
                }
            }
            return $newusers;
        }
        return $users;
    }

    /**
     * Obtains a list of group IDs that are permitted to use this forum.
     * This is not the same as restriction.
     * Group id's will be sent when a separate groups forum with grouping applied.
     * @return mixed Either an array of IDs, or '' if all groups permitted
     */
    private function get_permitted_groups() {
        $groups = '';
        $groupmode = $this->get_group_mode();
        $grouping = $this->get_grouping();
        if ($groupmode == SEPARATEGROUPS) {
            $groupobjs = groups_get_all_groups($this->get_course()->id, 0, $grouping, 'g.id');
            $groups = array();
            foreach ($groupobjs as $groupobj) {
                $groups[] = $groupobj->id;
            }
        }
        return $groups;
    }

    /**
     * Obtains list of forum subscribers.
     * @param int $groupid If specified, restricts list to this group id
     * @return array Array of partial user objects (with enough info to send
     *   email and display them); additionally, if the forum is in group mode,
     *   this includes an ->accessallgroups boolean
     */
    public function get_subscribers($groupid= self::ALL_GROUPS) {
        global $DB;

        // Array that will contain result
        $users = array();

        // Get permitted groups
        $groups = $this->get_permitted_groups();

        $subscriptionoption = $this->get_effective_subscription_option();
        switch ($subscriptionoption) {
            case self::SUBSCRIPTION_NOT_PERMITTED:
                return array();

            case self::SUBSCRIPTION_FORCED:
            case self::SUBSCRIPTION_INITIALLY_SUBSCRIBED:
                $users = $this->get_auto_subscribers($groupid);
                // Add $wholeforum = 1 and an empty array() for discussionid
                // for people who initially subscribed.
                foreach ($users as $user) {
                    $user->wholeforum = true;
                    $user->discussionids = array ();
                    $user->groupids = array ();
                }
                break;

            default:
                // The other two cases (initial subscribe, and manual subscribe)
                // fall through to the standard code below.
        }

        $context = $this->get_context();

        // For shared forums, we only return the subscribers for the current
        // clone
        $clonecheck = "";
        if ($this->is_shared()) {
            $clonecheck = 'AND s.clonecmid = ' . $this->get_course_module_id();
        }

        // Obtain the list of users who have access all groups on the forum,
        // unless it's in no-groups mode
        $groupmode = $this->get_group_mode();
        if ($groupmode) {
            // Get a list of user who can access all groups.
            $aagusers = get_users_by_capability($context, 'moodle/site:accessallgroups', 'u.id');
            mod_forumng_utils::add_admin_users($aagusers);
        }
        // Get the list of subscribed users.
        if ($groupid == self::ALL_GROUPS || $groupid == self::NO_GROUPS) {
            $groupcheck = '';
            $groupparams = array();
        } else {
            $groupcheck = "INNER JOIN {groups_members} gm ON gm.userid = u.id AND gm.groupid = ?";
            $groupparams = array($groupid);
        }

        $rs = $DB->get_recordset_sql($sql = "
SELECT
    ".mod_forumng_utils::select_username_fields('u', true).",
    s.subscribed, s.discussionid, s.groupid, fd.groupid AS discussiongroupid,
    discussiongm.id AS discussiongroupmember, subscriptiongm.id AS subscriptiongroupmember
FROM
    {forumng_subscriptions} s
    INNER JOIN {user} u ON u.id = s.userid
    $groupcheck
    LEFT JOIN {forumng_discussions} fd ON fd.id = s.discussionid
    LEFT JOIN {groups_members} discussiongm ON fd.groupid = discussiongm.groupid
        AND s.userid = discussiongm.userid
    LEFT JOIN {groups_members} subscriptiongm ON s.groupid = subscriptiongm.groupid
        AND s.userid = subscriptiongm.userid
WHERE
    s.forumngid = ?
    AND (fd.forumngid = ? OR s.discussionid IS NULL)
    $clonecheck", array_merge($groupparams,
            array($this->forumfields->id, $this->forumfields->id)));

        // Filter the result against the list of allowed users
        $allowedusers = null;
        foreach ($rs as $rec) {
            // Subscribed to the whole forum when subscribed == 1 and disucssionid =='';
            // *** Put the allowedusers checks in same part of code so not duplicated
            if ($rec->subscribed) {
                // This is a 'subscribe' request
                if (!$allowedusers) {
                    // Obtain the list of users who are allowed to see the forum.
                    // As get_users_by_capability can be expensive, we only do this
                    // once we know there actually are subscribers (and force rasing memory).
                    // We also limit the number to a max that will not cause a memory issue.
                    raise_memory_limit(MEMORY_EXTRA);
                    $allowedusers = get_users_by_capability($context,
                        'mod/forumng:viewdiscussion', 'u.id', 'u.lastaccess DESC', '', 200000,
                        $groups, '', 0, 0, true);
                    // Filter possible users by activity availability.
                    $avail = new \core_availability\info_module($this->get_course_module());
                    $allowedusers = $avail->filter_user_list($allowedusers);
                    mod_forumng_utils::add_admin_users($allowedusers);
                }
                $alloweduser = false;
                if (array_key_exists($rec->u_id, $allowedusers)) {
                    // User has access to forum + discussion.
                    $alloweduser = true;
                } else {
                    // User does not have access, maybe they were outside limit so get them again.
                    if (has_capability('mod/forumng:viewdiscussion', $context, $rec->u_id)) {
                        // Check forum availability.
                        $isallowed = $avail->filter_user_list(array($rec->u_id => new \stdClass()));
                        if ($alloweduser = array_key_exists($rec->u_id, $isallowed)) {
                            // Add user to allowed list in case they have multiple subscriptions.
                            $allowedusers[$rec->u_id] = new \stdClass();
                        }
                    }
                }

                // Get reference to current user, or make new object if required
                if (!array_key_exists($rec->u_id, $users)) {
                    $user = mod_forumng_utils::extract_subobject($rec, 'u_');
                    $user->wholeforum = false;
                    $user->discussionids = array();
                    $user->groupids = array();
                    $newuser = true;
                } else {
                    $user = $users[$rec->u_id];
                    $newuser = false;
                }
                $ok = false;
                // Subscribed to a discussion.
                if ($rec->discussionid) {
                    $groupok = !$rec->discussiongroupid || $rec->discussiongroupmember ||
                        $groupmode==VISIBLEGROUPS || array_key_exists($user->id, $aagusers);
                    if ($alloweduser && $groupok) {
                        $ok = true;
                        $user->discussionids[$rec->discussionid] = $rec->discussiongroupid;
                    }
                    // Subscribed to a group.
                } else if ($rec->groupid) {
                    $groupok = $groupmode == VISIBLEGROUPS ||
                        ($groupmode == SEPARATEGROUPS &&
                        ($rec->subscriptiongroupmember || array_key_exists($user->id, $aagusers)));
                    if ($alloweduser && $groupok) {
                        $user->groupids[$rec->groupid] = $rec->groupid;
                        $ok = true;
                    }
                    // Subscribed to the whole forum.
                } else {
                    // extra conditions for forum not separate groups or accessallgroups
                    $groupok = $groupmode != SEPARATEGROUPS ||
                            array_key_exists($user->id, $aagusers);
                    if ($alloweduser && $groupok) {
                        $user->wholeforum = true;
                        $ok = true;
                    }
                }
                // If this is a new user object, add it to the array provided the row was valid
                if ($newuser && $ok) {
                    $users[$user->id] = $user;
                }
            } else {
                // This is an 'unsubscribe' request. These are only allowed
                // for initial-subscription, otherwise ignored
                if ($subscriptionoption == self::SUBSCRIPTION_INITIALLY_SUBSCRIBED
                    && array_key_exists($user->id, $users)) {
                    // set wholeforum = false for user (if they are in the array)
                    $users[$rec->u_id]->unsubscribe = true;
                    $users[$rec->u_id]->wholeforum = false;
                }
            }
        }
        $rs->close();
        $allowedusers = null;

        // 1. loop through array and clear the discussions/groupids array if wholeforum is true.
        // 2. Find any user unsubscribed from initial subscribed forum. If the user has been
        //    subscribed to discussions/groups, remove the $user->unsubscribe flag;
        //    Otherwise remove the user from the list.
        foreach ($users as $key => $user) {
            if ($user->wholeforum) {
                $user->discussionids = array ();
                $user->groupids = array ();
            }
            // Remove discussionids for discussions that are already covered by group subscriptions
            // TODO
            if (count($user->discussionids) != 0 && count($user->groupids) != 0) {
                foreach ($user->discussionids as $id => $dgroupid) {
                    if (!$dgroupid || array_key_exists($dgroupid, $user->groupids)) {
                        unset($user->discussionids[$id]);
                    }
                }
            }
            // If the user has unsubscribed from an initial subscription, then remove the entry
            // from the results array unless there are s subscriptions to discussions or groups
            if (!empty($user->unsubscribe)) {
                // Remove the unsubscribe as the user is likely to
                // subscribed to discussions or groups
                unset($user->unsubscribe);
                if (count($user->discussionids) == 0 && count($user->groupids) == 0) {
                    unset($users[$key]);
                }
            }
        }

        // Add access-all-groups information if applicable
        if ($groupmode) {
            foreach ($users as $key => $user) {
                $user->accessallgroups = array_key_exists($user->id, $aagusers);
            }
        }

        return $users;
    }

    // Permissions
    /*////////////*/

    /**
     * Makes security checks for viewing this forum. Will not return if user
     * cannot view it.
     * This function calls Moodle require_login, so should be a complete
     * access check. It should be placed near the top of a page.
     * Note that this function only works for the current user when used in
     * interactive mode (ordinary web page view). It cannot be called in cron,
     * web services, etc.
     *
     * @param int $groupid Group ID user is attempting to view (may also be
     *   ALL_GROUPS or NO_GROUPS or null)
     * @param int $userid User ID or 0 for current; only specify user ID when
     *   there is no current user and normal login process is not required -
     *   do NOT set this to the current user id, always user 0
     * @param int $autologinasguest whether to get the require_login call to
     *   automatically log user in as guest
     */
    public function require_view($groupid, $userid=0, $autologinasguest=false) {
        global $CFG, $USER, $PAGE;

        $cm = $this->get_course_module();
        $course = $this->get_course();
        $context = $this->get_context();
        if (!$userid || (!empty($USER->id) && $userid == $USER->id)) {
            // User must be logged in and able to access the activity. (This
            // call sets up the global course and checks various other access
            // restrictions that apply at course-module level, such as visibility.)
            if (count((array)$course) == 1) {
                require_login($course->id, $autologinasguest, $cm);
            } else {
                require_login($course, $autologinasguest, $cm);
            }
        } else {
            // Since require_login is not being called, we need to set up $PAGE->context
            // or it gives an annoying warning
            $PAGE->set_context($context);

            // This is a check for another user who is not logged in.  We need to check
            // basic course enrolment and a couple of the 'hidden' flags
            if (!is_enrolled($context, $userid, '', true)) {
                require_capability('moodle/course:view', $context, $userid);
            }

            if (!$course->visible) {
                require_capability('moodle/course:viewhiddencourses', $context, $userid);
            }
            if (!$cm->uservisible) {
                require_capability('moodle/course:viewhiddenactivities', $context, $userid);
            }
        }

        // Check they have the forumng view capability (this is there largely
        // so that we can override it to prevent prisoners from accessing)
        if (!$userid) {
            global $USER;
            $userid = $USER->id;
        }
        require_capability('mod/forumng:view', $context, $userid);

        // Note: There is no other capability just to view the forum front page,
        // so just check group access
        if ($groupid!==self::NO_GROUPS && !$this->can_access_group($groupid, false, $userid)) {
            // We already know they don't have this capability, but it's
            // a logical one to use to give an error message.
            require_capability('moodle/site:accessallgroups', $context, $userid);
        }
    }

    /**
     * Makes security checks for starting a discussion. Will not return if user
     * is not allowed to.
     * @param int $groupid Group ID (or ALL_GROUPS) where discussion is
     *   to be started
     */
    public function require_start_discussion($groupid) {
        // Require forum view
        $this->require_view($groupid);

        // Check if they are allowed to start discussion
        $whynot = '';
        if (!$this->can_start_discussion($groupid, $whynot)) {
            print_error($whynot, 'forumng',
                    $this->get_url(self::PARAM_HTML));
        }
    }

    /**
     * Checks whether user can access the given group.
     * @param $groupid Group ID
     * @param $write True if write access is required (this makes a difference
     *   if group mode is visible, when you can see other groups, but not write
     *   to them).
     * @param $userid User ID (0 = current user)
     * @return bool True if user can access group
     */
    public function can_access_group($groupid, $write=false, $userid=0) {
        global $USER;
        $userid = mod_forumng_utils::get_real_userid($userid);

        // Check groupmode.
        $groupmode = groups_get_activity_groupmode($this->get_course_module());
        if (!$groupmode) {
            // No groups - you can only view 'all groups' mode
            return $groupid === self::NO_GROUPS;
        }

        // In visible groups, everyone can see everything (but not write to it)
        if ($groupmode==VISIBLEGROUPS && !$write) {
            return true;
        }

        // If you have access all groups, you can see it
        if (has_capability('moodle/site:accessallgroups', $this->get_context(), $userid)) {
            return true;
        }

        // Check if you're trying to view 'all groups'
        if ($groupid == self::ALL_GROUPS) {
            return false;
        }

        // Trying to view a specific group, must be a member
        if (isset($USER->groupmember) && (!$userid || $USER->id==$userid)
            && array_key_exists($this->get_course()->id, $USER->groupmember)) {
            // Current user, use cached value
            return array_key_exists($groupid, $USER->groupmember[$this->get_course()->id]);
        } else {
            // Not current user, test in database
            return groups_is_member($groupid, $userid);
        }
    }

    /**
     * @param $userid
     * @return bool True if user can view discussions in this forum
     */
    public function can_view_discussions($userid=0) {
        $userid = mod_forumng_utils::get_real_userid($userid);
        return has_capability('mod/forumng:viewdiscussion', $this->get_context(),
            $userid);
    }

    /**
     * @param $userid
     * @return bool True if user can view a list of subscribers in this forum
     */
    public function can_view_subscribers($userid=0) {
        if ($this->get_effective_subscription_option() ==
            self::SUBSCRIPTION_NOT_PERMITTED) {
                return false;
        }
        $userid = mod_forumng_utils::get_real_userid($userid);
        return has_capability('mod/forumng:viewsubscribers', $this->get_context(),
            $userid);
    }

    /**
     * @return bool True if user should see unread data in this forum
     */
    public function can_mark_read($userid=0) {
        global $CFG, $USER;
        $user = mod_forumng_utils::get_user($userid);
        return $this->can_view_discussions($userid)
                && $CFG->forumng_trackreadposts && !isguestuser($user);
    }

    /**
     * @return bool True if user can view hidden discussions in this forum
     */
    public function can_view_hidden($userid=0) {
        $userid = mod_forumng_utils::get_real_userid($userid);
        return has_capability('mod/forumng:viewallposts', $this->get_context(),
            $userid);
    }

    /**
     * @param int $userid User ID to check for (0 = current)
     * @return bool True if the forum is outside its 'posting from/until'
     *   times and the current user does not have permission to bypass that
     */
    public function is_read_only($userid=0) {
        $userid = mod_forumng_utils::get_real_userid($userid);
        $now = time();
        return (($this->forumfields->postingfrom > $now) ||
            ($this->forumfields->postinguntil &&
                $this->forumfields->postinguntil <= $now)) &&
            !has_capability('mod/forumng:ignorepostlimits', $this->get_context(), $userid);
    }

    /**
     * Checks whether this forum has a post quota which applies to a specific
     * user.
     * @param int $userid User ID to check for (0 = current)
     * @return bool True if post limit quota is enabled for this forum and user
     */
    public function has_post_quota($userid = 0) {
        $userid = mod_forumng_utils::get_real_userid($userid);
        return ($this->forumfields->maxpostsblock &&
            !has_capability('mod/forumng:ignorepostlimits', $this->get_context(), $userid))
            ? true : false;
    }

    /**
     * Counts number of remaining permitted posts in current time period.
     * @param int $userid User ID to check for (0 = current)
     * @return int How many more posts you can make; QUOTA_DOES_NOT_APPLY if
     *   no limit
     */
    public function get_remaining_post_quota($userid = 0) {
        // Check quota is turned on and applies to current user.
        if (!$this->has_post_quota($userid)) {
            return self::QUOTA_DOES_NOT_APPLY;
        }

        // Cache data for current user during request only
        global $USER, $FORUMNG_POSTQUOTA, $DB;

        $userid = mod_forumng_utils::get_real_userid($userid);
        $usecache = $userid == $USER->id;
        if ($usecache && $FORUMNG_POSTQUOTA &&
            array_key_exists($this->forumfields->id, $FORUMNG_POSTQUOTA)) {
            return $FORUMNG_POSTQUOTA[$this->forumfields->id];
        }

        // OK, quota applies. Need to check how many posts they made, to this
        // forum, within the given timescale, which have not been deleted
        $threshold = time() - $this->forumfields->maxpostsperiod;
        $count = $DB->count_records_sql("
SELECT
    COUNT(1)
FROM
    {forumng_posts} fp
    INNER JOIN {forumng_discussions} fd ON fp.discussionid = fd.id
WHERE
    fd.forumngid = ?
    AND fp.userid = ?
    AND fp.created > ?
    AND fp.deleted = 0
    AND fp.oldversion = 0", array($this->forumfields->id, $userid, $threshold));
        $result = $this->forumfields->maxpostsblock - $count;
        if ($result < 0) {
            $result = 0;
        }

        if ($usecache) {
            // Cache result
            if (!$FORUMNG_POSTQUOTA) {
                $FORUMNG_POSTQUOTA = array();
            }
            $FORUMNG_POSTQUOTA[$this->forumfields->id] = $result;
        }

        // Return result
        return $result;
    }

    /**
     * Checks if user is permitted to post new discussions to this forum.
     * @param int $groupid Group ID user wants to post to
     * @param string &$whynot Why user cannot post; will be set to '' or else
     *   to a language string name
     * @param int $userid User ID or 0 for current
     * @return bool True if user can post
     */
    public function can_start_discussion($groupid, &$whynot, $userid=0) {
        $userid = mod_forumng_utils::get_real_userid($userid);
        $whynot = '';

        // Dates
        if ($this->is_read_only($userid)) {
            return false;
        }

        // Capability
        if (!has_capability('mod/forumng:startdiscussion',
            $this->get_context(), $userid)) {
            $whynot = 'startdiscussion_nopermission';
            return false;
        }

        // Forum type
        $type = $this->get_type();
        if (!$type->can_post($this, $whynot)) {
            return false;
        }

        // Group access
        if (!$this->can_access_group($groupid, true, $userid)) {
            $whynot = 'startdiscussion_groupaccess';
            return false;
        }

        // Throttling
        if ($this->get_remaining_post_quota($userid) == 0) {
            $whynot = 'startdiscussion_postquota';
            return false;
        }

        return true;
    }

    /**
     * @param int $userid User ID or 0 for default
     * @return bool True if user is allowed to set 'mail now' option
     */
    public function can_mail_now($userid=0) {
        $userid = mod_forumng_utils::get_real_userid($userid);
        return has_capability('mod/forumng:mailnow', $this->get_context(), $userid);
    }

    /**
     * @param int $userid User ID or 0 for current
     * @return True if user can set posts as important
     */
    public function can_set_important($userid=0) {
        $userid = mod_forumng_utils::get_real_userid($userid);
        return has_capability('mod/forumng:setimportant', $this->get_context(), $userid);
    }

    /**
     * @param int $userid User ID or 0 for default
     * @return bool True if user is allowed to set discussion options
     */
    public function can_manage_discussions($userid=0) {
        $userid = mod_forumng_utils::get_real_userid($userid);
        return has_capability('mod/forumng:managediscussions',
            $this->get_context(), $userid);
    }

    /**
     * @param int $userid User ID, 0 for default
     * @return bool True if user has capability
     */
    public function can_manage_subscriptions($userid=0) {
        if ($this->get_effective_subscription_option() ==
            self::SUBSCRIPTION_NOT_PERMITTED) {
                return false;
        }
        $userid = mod_forumng_utils::get_real_userid($userid);
        return has_capability('mod/forumng:managesubscriptions', $this->get_context(),
            $userid);
    }

    /**
     * @param int $userid User ID, 0 for default
     * @return bool True if user has capability
     */
    public function can_create_attachments($userid=0) {
        $userid = mod_forumng_utils::get_real_userid($userid);
        return has_capability('mod/forumng:createattachment', $this->get_context(),
            $userid);
    }

    /**
     * @param int $userid User ID or 0 for current
     * @return True if user can set posts as moderator
     */
    public function can_indicate_moderator($userid = 0) {
        $userid = mod_forumng_utils::get_real_userid($userid);
        return has_capability('mod/forumng:postasmoderator', $this->get_context(), $userid);
    }

    /**
     * @param int $userid User ID or 0 for current
     * @return True if user can set posts as anonymous
     */
    public function can_post_anonymously($userid = 0) {
        $userid = mod_forumng_utils::get_real_userid($userid);
        if ($this->get_can_post_anon() && has_capability('mod/forumng:postanon',
                $this->get_context(), $userid)) {
            return true;
        } else {
            return false;
        }

    }

    // Forum type
    /*///////////*/

    /**
     * Obtains a forum type object suitable for handling this forum.
     * @return forumngtype Type object
     */
    public function get_type() {
        if (!$this->type) {
            $this->type = forumngtype::get_new($this->forumfields->type);
        }

        return $this->type;
    }

    // Grades
    /*///////*/

    /**
     * Updates the current forum grade(s), creating grade items if required,
     * or recalculating grades or deleting them.
     * (Should be based on mod_forumng_update_grades.)
     * @param int $userid User whose grades need updating, or 0 for all users
     * @param string $idnumber May be specified during forum creation when
     *   there isn't a course-module yet; otherwise leave blank to get from
     *   course-module
     */
    public function update_grades($userid = 0, $idnumber=null) {
        global $CFG, $DB;
        require_once($CFG->libdir . '/gradelib.php');

        $transaction = $DB->start_delegated_transaction();

        // Calculate grades for requested user(s)
        if ($this->get_grading() == self::GRADING_NONE ||
                $this->get_grading() == self::GRADING_MANUAL) {
            // Except don't bother if grading is not enabled or manual.
            $grades = array();
        } else {
            $grades = $this->get_user_grades($userid);

            // For specific user, add in 'null' item when updating grade - this
            // allows it to 'clear' the grade if you are 'un-rating' a post
            if (count($grades) == 0 && $userid) {
                $grade = new object();
                $grade->userid = $userid;
                $grade->rawgrade = null;
                $grades[$userid] = $grade;
            }
        }

        // Update grade item and grades
        $this->grade_item_update($grades, $idnumber);

        $transaction->allow_commit();
    }

    /**
     * Gets grades in this forum for all users or a specified user.
     * @param int $userid Specific user or 0 = all
     * @return array Grade objects as specified
     */
    private function get_user_grades($userid = 0) {
        global $DB;

        // Part of query that is common to all aggregation types
        $forumngid = $this->get_id();
        $baseselect = "SELECT fp.userid AS userid";

        if (self::get_enableratings() == mod_forumng::FORUMNG_STANDARD_RATING) {
            // Moodle standard rating.
            $basemain = "
                FROM {forumng_discussions} fd
                INNER JOIN {forumng_posts} fp ON fp.discussionid = fd.id
                INNER JOIN {rating} fr ON fr.itemid = fp.id
                WHERE fd.forumngid = ?";
        } else {
            // ForumNg rating.
            $basemain = "
                FROM {forumng_discussions} fd
                INNER JOIN {forumng_posts} fp ON fp.discussionid = fd.id
                INNER JOIN {forumng_ratings} fr ON fr.postid = fp.id
                WHERE fd.forumngid = ?";
        }

        $baseparams = array($forumngid);
        if ($userid) {
            $basemain .= " AND fp.userid = ?";
            $baseparams[] = $userid;
        }
        $basemain .= " GROUP BY fp.userid";

        $aggtype = $this->get_grading();
        switch ($aggtype) {
            case self::GRADING_COUNT :
                $customselect = ", COUNT(fr.rating) AS rawgrade";
                break;
            case self::GRADING_MAX :
                $customselect = ", MAX(fr.rating) AS rawgrade";
                break;
            case self::GRADING_MIN :
                $customselect = ", MIN(fr.rating) AS rawgrade";
                break;
            case self::GRADING_SUM :
                $customselect = ", SUM(fr.rating) AS rawgrade";
                break;
            default : // Avg.
                $customselect = ", AVG(fr.rating) AS rawgrade";
                break;
        }

        // Work out the max grade
        $scale = $this->get_rating_scale();
        if ($scale >= 0) {
            // Numeric.
            $max = $scale;
        } else {
            // Scale.
            $scale = $DB->get_record('scale', array('id' => -$scale), '*', MUST_EXIST);
            $scale = explode(',', $scale->scale);
            $max = count($scale);
        }

        $sql = $baseselect . $customselect . $basemain;
        $rs = $DB->get_recordset_sql($sql, $baseparams);
        $results = array();
        foreach ($rs as $result) {
            // it could throw off the grading if count and sum returned a
            // rawgrade higher than scale so to prevent it we review the
            // results and ensure that rawgrade does not exceed the scale,
            // if it does we set rawgrade = scale (i.e. full credit)
            if ($result->rawgrade > $max) {
                $result->rawgrade = $max;
            }
            $results[$result->userid] = $result;
        }

        return $results;
    }

    /**
     * Updates the grade item and (if given) the associated grades.
     * @param array $grades Array of grade objects which will be updated.
     *   (Alternatively may be the string 'reset' to reset grades - this is
     *   not currently used.)
     * @param string $idnumber May be specified during forum creation when
     *   there isn't a course-module yet; otherwise leave blank to get from
     *   course-module
     * @throws coding_exception
     */
    private function grade_item_update($grades = array(), $idnumber=null) {
        global $DB;
        if (is_null($idnumber)) {
            $cm = $this->get_course_module();
            // When $cm has been retrieved via get_fast_modinfo, it doesn't include
            // the idnumber field :(
            if (!property_exists($cm, 'idnumber')) {
                $idnumber = $DB->get_field('course_modules',
                        'idnumber', array('id' => $cm->id));
            } else {
                $idnumber = $cm->idnumber;
            }
        }
        $params = array(
            'itemname' => $this->get_name(),
            'idnumber' => $idnumber);

        if ($this->get_grading() == self::GRADING_MANUAL) {
            $scale = $this->get_grading_scale();
            $grades = null;
        } else {
            $scale = $this->get_rating_scale();
        }
        if (!$this->get_grading()) {
            $params['gradetype'] = GRADE_TYPE_NONE;
        } else if ($scale > 0) {
            $params['gradetype'] = GRADE_TYPE_VALUE;
            $params['grademax'] = $scale;
            $params['grademin'] = 0;
        } else if ($scale < 0) {
            $params['gradetype'] = GRADE_TYPE_SCALE;
            $params['scaleid'] = -$scale;
        }

        if ($grades  === 'reset') {
            $params['reset'] = true;
            $grades = null;
        }

        $ok = grade_update('mod/forumng', $this->forumfields->course,
            'mod', 'forumng', $this->forumfields->id, 0, $grades, $params);
        if ($ok != GRADE_UPDATE_OK) {
            throw new coding_exception("Grade update failed");
        }
    }

    // Bulk forum requests
    /*////////////////////*/

    /**
     * Queries for all forums on a course, including additional data about unread
     * posts etc.
     * NOTE: If shared forums are in use, this will usually return the CLONE
     * forum object, which doesn't hold any data about the actual forum;
     * the exception is that unread data will be obtained from the real forum.
     * If you would like to obtain the real forum instead, please make sure
     * $realforums is set to true. This has a performance cost.
     * @param object $course Moodle course object
     * @param int $userid User ID, 0 = current user, -1 = no unread data is needed
     * @param bool $unreadasbinary If true, unread data MAY BE binary (1/0)
     *   instead of containing the full number; this improves performance but
     *   only works on some databases
     * @param array $specificids If array has no entries, returns all forums
     *   on the course; if it has at least one entry, returns only those forums
     *   with course-module ID listed in the array
     * @param bool $realforums Set this to true to obtain real forums
     *   if any are clones; has a performance cost if shared forums are used
     * @return array Array of forum objects (keys are forum IDs; in the case of
     *   shared forums, the id is of the clone not the forum, even if
     *   $realforums is set)
     */
    public static function get_course_forums($course, $userid = 0,
            $unread = self::UNREAD_DISCUSSIONS, $specificids = array(),
            $realforums = false) {
        global $USER, $DB;

        $userid = mod_forumng_utils::get_real_userid($userid);
        $result = array();
        // Added $userid parameter to obtain modinfo for specific user rather than current user.
        $modinfo = get_fast_modinfo($course, $userid);

        // Obtains extra information needed only when acquiring unread data
        $aagforums = array();
        $viewhiddenforums = array();
        $groups = array();
        $contexts = array();
        if ($unread != self::UNREAD_NONE) {
            foreach ($modinfo->cms as $cmid => $cm) {
                if (count($specificids) && !in_array($cmid, $specificids)) {
                    continue;
                }
                if ($cm->modname == 'forumng') {
                    $context = context_module::instance($cmid);
                    $contexts[$cmid] = $context;
                    if (has_capability(
                            'moodle/site:accessallgroups', $context, $userid)) {
                        $aagforums[] = $cm->instance;
                    }
                    if (has_capability(
                            'mod/forumng:viewallposts', $context, $userid)) {
                        $viewhiddenforums[] = $cm->instance;
                    }
                }
            }
            if ($userid == $USER->id && isset($USER->groupmember)) {
                if (array_key_exists($course->id, $USER->groupmember)) {
                    $groups = $USER->groupmember[$course->id];
                } // Else do nothing - groups list should be empty
            } else {
                $rs = $DB->get_recordset_sql("
SELECT
    g.id
FROM
    {groups} g
    INNER JOIN {groups_members} gm ON g.id = gm.groupid
WHERE
    g.courseid = ?
    AND gm.userid = ?", array($course->id, $userid));
                foreach ($rs as $rec) {
                    $groups[] = $rec->id;
                }
                $rs->close();
            }
        }

        $rows = self::query_forums($specificids, $course, $userid,
            $unread, $groups, $aagforums, $viewhiddenforums);
        foreach ($rows as $rec) {
            // Check course-module exists
            if (!array_key_exists($rec->cm_id, $modinfo->cms)) {
                continue;
            }
            $cm = $modinfo->cms[$rec->cm_id];
            if ($cm->modname != 'forumng') {
                continue;
            }

            // Mess about with binary setting to ensure result is same, whatever
            // the database
            if ($unread == self::UNREAD_BINARY) {
                // Set binary to 0/1 even if database returns 't'/'f'
                if ($rec->f_hasunreaddiscussions === 'f') {
                    $rec->f_hasunreaddiscussions = 0;
                } else if ($rec->f_hasunreaddiscussions) {
                    $rec->f_hasunreaddiscussions = 1;
                } else {
                    $rec->f_hasunreaddiscussions = 0;
                }
            }

            // Get context if we didn't already get it.
            if (!empty($contexts[$rec->cm_id])) {
                $context = $contexts[$rec->cm_id];
            } else {
                $context = context_module::instance($rec->cm_id);
            }

            // Create a new forum object from the database details
            $forumfields = mod_forumng_utils::extract_subobject($rec, 'f_');
            $forum = new mod_forumng($course, $cm, $context, $forumfields);
            $result[$forumfields->id] = $forum;
            if ($forum->is_shared()) {
                $forum->set_clone_reference(self::CLONE_DIRECT);
            }

            // For clone forums (only pointers to genuine shared forums)
            if ($forum->is_clone()) {
                // If we are retrieving the real forum, get it individually
                if ($realforums) {
                    $othercourse = $DB->get_record_sql("
SELECT
    c.*
FROM
    {course_modules} cm
    INNER JOIN {course} c ON c.id = cm.course
WHERE
    cm.id = ?", array($forumfields->originalcmid), '*', MUST_EXIST);
                    $extra = self::get_course_forums($othercourse, $userid,
                        $unread, array($forumfields->originalcmid));
                    if (count($extra) != 1) {
                        throw new coding_exception(
                            'Unable to find shared forum ' . $forumfields->originalcmid);
                    }
                    foreach ($extra as $extraforum) {
                        $extraforum->set_clone_reference($cm->id);
                        $result[$forumfields->id] = $extraforum;
                    }
                } else if ($unread != self::UNREAD_NONE) {
                    // Even if not retrieving the real forum, we still use
                    // its undead data when unread data is on
                    $forum->init_unread_from_original($unread);
                }
            }
        }
        return $result;
    }

    private static function sort_mod_forumng_result($a, $b) {
        return strcasecmp($a->f_name, $b->f_name);
    }

    /**
     * Internal method. Queries for a number of forums, including additional
     * data about unread posts etc. Returns the database result.
     * @param array $cmids If specified, array of course-module IDs of desired
     *   forums
     * @param object $course If specified, course object
     * @param int $userid User ID, 0 = current user
     * @param int $unread Type of unread data to obtain (UNREAD_xx constant).
     * @param array $groups Array of group IDs to which the given user belongs
     *   (may be null if unread data not required)
     * @param array $aagforums Array of forums in which the user has
     *   'access all groups' (may be null if unread data not required)
     * @param array $viewhiddenforums Array of forums in which the user has
     *   'view hidden discussions' (may be null if unread data not required)
     * @return array Array of row objects
     */
    private static function query_forums($cmids=array(), $course=null,
            $userid, $unread, $groups, $aagforums, $viewhiddenforums) {
        global $DB, $CFG, $USER;
        if ((!count($cmids) && !$course)) {
            throw new coding_exception("mod_forumng::query_forums requires course id or cmids");
        }
        if (count($cmids)) {
            list($in, $conditionsparams) = mod_forumng_utils::get_in_array_sql('cm.id', $cmids);
            $conditions = $in;
        } else {
            $conditions = "f.course = ?";
            $conditionsparams = array($course->id);
        }

        $singleforum = count($cmids) == 1 ? reset($cmids) : false;
        list($inviewhiddenforums, $inviewhiddenforumsparams) = mod_forumng_utils::get_in_array_sql(
            'fd.forumngid', $viewhiddenforums);
        list($cfdinviewhiddenforums, $inviewhiddenforumsparams) =
                mod_forumng_utils::get_in_array_sql('cfd.forumngid', $viewhiddenforums);
        list($cfdingroups, $ingroupsparams) =
                mod_forumng_utils::get_in_array_sql('cfd.groupid', $groups);
        list($cfdinaagforums, $inaagforumsparams) =
                mod_forumng_utils::get_in_array_sql('cfd.forumngid', $aagforums);

        // This array of additional results is used later if combining
        // standard results with single-forum calls.
        $plusresult = array();

        // For read tracking, we get a count of total number of posts in
        // forum, and total number of read posts in the forum (this
        // is so we can display the number of UNread posts, but the query
        // works that way around because it will return 0 if no read
        // information is stored).
        if ($unread!=self::UNREAD_NONE && self::enabled_read_tracking()) {
            // Work out when unread status ends
            $endtime = time() - $CFG->forumng_readafterdays*24*3600;
            if (!$userid) {
                $userid = $USER->id;
            }

            list($ingroups, $ingroupsparams) =
                    mod_forumng_utils::get_in_array_sql('fd.groupid', $groups);
            list($inaagforums, $inaagforumsparams) =
                    mod_forumng_utils::get_in_array_sql('fd.forumngid', $aagforums);

            $restrictionsql = '';
            $restrictionparams = array();
            if ($singleforum) {
                // If it is for a single forum, get the restriction from the
                // forum type
                $forum = self::get_from_cmid($singleforum, self::CLONE_DIRECT);
                $type = $forum->get_type();
                if ($type->has_unread_restriction()) {
                    list($value, $restrictionparams) = $type->get_unread_restriction_sql($forum);
                    if ($value) {
                        $restrictionsql = 'AND ' . $value;
                    }
                }
            } else {
                // When it is not for a single forum, we can only group together
                // results for types that do not place restrictions on the
                // unread count.
                $modinfo = get_fast_modinfo($course);
                $okayids = array();
                if (array_key_exists('forumng', $modinfo->instances)) {
                    foreach ($modinfo->instances['forumng'] as $info) {
                        if (count($cmids) && !in_array($info->id, $cmids)) {
                            continue;
                        }
                        $type = self::get_type_from_modinfo_info($info);
                        if (forumngtype::get_new($type)->has_unread_restriction()) {
                            // This one's a problem! Do it individually
                            $problemresults = self::query_forums(
                                array($info->id), null, $userid, $unread,
                                $groups, $aagforums, $viewhiddenforums);
                            foreach ($problemresults as $problemresult) {
                                $plusresult[$problemresult->f_id] = $problemresult;
                            }
                        } else {
                            $okayids[] = $info->id;
                        }
                    }
                }

                if (count($okayids) == 0) {
                    // There are no 'normal' forums, so return result so far
                    // after sorting it
                    uasort($plusresult, 'mod_forumng::sort_mod_forumng_result');
                    return $plusresult;
                } else {
                    // Fall through to normal calculation, but change conditions
                    // to include only the 'normal' forums
                    list($in, $inparams) = mod_forumng_utils::get_in_array_sql('cm.id', $okayids);
                    $conditions .= " AND " . $in;
                    $conditionsparams = array_merge($conditionsparams, $inparams);
                }
            }
            $indreadpart = '';
            $indreadparms = array();
            $indreadwhere = '';
            // Get individual posts unread if manual read marking (on unread discussions only).
            if (!mod_forumng::mark_read_automatically($userid)) {
                $indreadpart = "INNER JOIN {forumng_posts} fp ON fp.discussionid = discussions.id
                                 LEFT JOIN {forumng_read_posts} frp ON frp.postid = fp.id AND frp.userid = ?";
                $indreadwhere = "AND frp.id IS NULL
                                 AND ((fp.edituserid IS NOT NULL AND fp.edituserid <> ?)
                                       OR (fp.edituserid IS NULL AND fp.userid <> ?))
                                 AND fp.deleted = ?
                                 AND fp.oldversion = ?
                                 AND fp.modified > ?
                                 AND (discussions.time IS NULL OR fp.modified > discussions.time)";
                $indreadparms = array($userid, $userid, $userid, 0, 0, $endtime);
            }

            // NOTE fpfirst is used only by forum types, not here
            $now = time();
            $sharedquerypart = "
        FROM
     (SELECT fd.id, fr.time, fd.forumngid
        FROM {forumng_discussions} fd
  INNER JOIN {forumng_posts} fplast ON fd.lastpostid = fplast.id
  INNER JOIN {forumng_posts} fpfirst ON fd.postid = fpfirst.id
   LEFT JOIN {forumng_read} fr ON fd.id = fr.discussionid AND fr.userid = ?
  INNER JOIN {forumng} f1 ON f1.id = fd.forumngid
  INNER JOIN {course_modules} cm2 ON cm2.instance = fd.forumngid
             AND cm2.module = (SELECT id FROM {modules} WHERE name = 'forumng')
       WHERE fplast.modified > ?
         AND (f1.type != ? OR fpfirst.userid = ? OR ($inviewhiddenforums))
         AND (
             (fd.groupid IS NULL)
             OR ($ingroups)
             OR cm2.groupmode = " . VISIBLEGROUPS . "
             OR ($inaagforums)
         )
         AND fd.deleted = 0
         AND (
             ((fd.timestart = 0 OR fd.timestart <= ?)
             AND (fd.timeend = 0 OR fd.timeend > ? OR ($inviewhiddenforums)))
         )
         AND ((fplast.edituserid IS NOT NULL AND fplast.edituserid <> ?)
          OR fplast.userid <> ?)
         AND (fr.time IS NULL OR fplast.modified > fr.time)
    $restrictionsql
    ) discussions
    $indreadpart
       WHERE discussions.forumngid = f.id
    $indreadwhere";
            $sharedqueryparams = array_merge(array($userid, $endtime, 'studyadvice', $userid),
                    $inviewhiddenforumsparams, $ingroupsparams, $inaagforumsparams, array($now, $now), $inviewhiddenforumsparams,
                    array($userid, $userid), $restrictionparams, $indreadparms);

            // Note: There is an unusual case in which this number can
            // be inaccurate. It is to do with ignoring messages the user
            // posted. We consider a discussion as 'not unread' if the last
            // message is by current user. In actual fact, a discussion could
            // contain unread messages if messages were posted by other users
            // after this user viewed the forum last, but before they posted
            // their reply. Since this should be an infrequent occurrence I
            // believe this behaviour is acceptable.
            if ($unread==self::UNREAD_BINARY) {
                // Query to get 0/1 unread discussions count
                $readtracking = self::select_exists("SELECT 1 $sharedquerypart") .
                        "AS f_hasunreaddiscussions";
                $readtrackingparams = $sharedqueryparams;
            } else {
                // Query to get full unread discussions count
                $readtracking = "
(SELECT
    COUNT(DISTINCT discussions.id)
$sharedquerypart
) AS f_numunreaddiscussions";
                $readtrackingparams = $sharedqueryparams;
            }
        } else {
            $readtracking = "NULL AS numreadposts, NULL AS timeread";
            $readtrackingparams = array();
        }
        $now = time();
        $orderby = "LOWER(f.name)";

        // Main query. This retrieves:
        // - Full forum fields
        // - Basic course-module and course data (not whole tables)
        // - Discussion count
        // - Unread data, if enabled
        // - User subscription data
        $result = $DB->get_records_sql($sql = "
SELECT
    " . mod_forumng_utils::select_mod_forumng_fields('f') . ",
    " . mod_forumng_utils::select_course_module_fields('cm') . ",
    " . mod_forumng_utils::select_course_fields('c') . ",
    (SELECT COUNT(1)
        FROM {forumng_discussions} cfd
        JOIN {forumng_posts} cfp ON cfd.postid = cfp.id
        WHERE cfd.forumngid = f.id AND cfd.deleted = 0
        AND (
            ((cfd.timestart = 0 OR cfd.timestart <= ?)
            AND (cfd.timeend = 0 OR cfd.timeend > ?))
            OR ($cfdinviewhiddenforums)
        )
        AND (f.type != ? OR cfp.userid = ? OR ($cfdinviewhiddenforums))
        AND (
             (cfd.groupid IS NULL)
             OR ($cfdingroups)
             OR cm.groupmode = " . VISIBLEGROUPS . "
             OR ($cfdinaagforums)
        )) AS f_numdiscussions,
    $readtracking
FROM
    {forumng} f
    INNER JOIN {course_modules} cm ON cm.instance = f.id
        AND cm.module = (SELECT id from {modules} WHERE name = 'forumng')
    INNER JOIN {course} c ON c.id = f.course
WHERE
    $conditions
ORDER BY
    $orderby", array_merge(array($now, $now), $inviewhiddenforumsparams,
                array('studyadvice', $userid), $inviewhiddenforumsparams,
                $ingroupsparams, $inaagforumsparams, $readtrackingparams,
                $conditionsparams));
        if (count($plusresult) > 0) {
            foreach ($plusresult as $key => $value) {
                $result[$key] = $value;
            }
            uasort($result, 'mod_forumng::sort_mod_forumng_result');
        }
        return $result;
    }

    // Search
    /*///////*/

    /** @return True if the OU search extension is available */
    public static function search_installed() {
        return @include_once(dirname(__FILE__) .
            '/../../local/ousearch/searchlib.php');
    }

    /**
     * Returns the SQL code for an 'exists' to be used in the select list,
     * surrounded in brackets. This function is used because 'EXISTS' works
     * differently in MS SQL.
     * @param string $sql Subquery to be called within exists, e.g. 'SELECT 1'
     * @return string Exists check SQL e.g. '(EXISTS (SELECT 1))'
     */
    public static function select_exists($sql) {
        global $DB;
        if ($DB->get_dbfamily() === 'mssql') {
            return '(SELECT 1 WHERE EXISTS(' . $sql . '))';
        } else {
            return '(EXISTS (' . $sql . '))';
        }
    }

    /**
     * Update all documents for ousearch.
     *
     * If specified, the progress object should be ready to receive indeterminate
     * progress calls.
     *
     * @param bool $feedback If true, prints feedback as HTML list items
     * @param int $courseid If specified, restricts to particular courseid
     * @param int $cmid If specified, restricts to particular cmid
     * @param \core\progress\base $progress Set to a progress object or null
     */
    public static function search_update_all($feedback=false, $courseid=0, $cmid=0,
            \core\progress\base $progress = null) {
        global $DB;
        raise_memory_limit(MEMORY_EXTRA);
        // If cmid is specified, only retrieve that one
        if ($cmid) {
            $cmrestrict = "cm.id = ? AND";
            $cmrestrictparams = array($cmid);
        } else {
            $cmrestrict = '';
            $cmrestrictparams = array();
        }
        // Get module-instances that need updating
        $cms = $DB->get_records_sql("
SELECT
    cm.id, cm.course, cm.instance, f.name
FROM
    {forumng} f
    INNER JOIN {course_modules} cm ON cm.instance = f.id
WHERE
    $cmrestrict
    cm.module = (SELECT id FROM {modules} m WHERE name = 'forumng')".
                ($courseid ? " AND f.course = ?" : ''), array_merge($cmrestrictparams,
                $courseid ? array($courseid) : array()));

        // Print count
        if ($feedback && !$cmid) {
            print '<li>' . get_string('search_update_count', 'forumng',
                '<strong>'.count($cms).'</strong>') . '</li>';
        }

        // This can take a while, so let's be sure to have a long time limit.
        $timelimitbefore = 300;

        // Loop around updating
        foreach ($cms as $cm) {
            $transaction = $DB->start_delegated_transaction();

            // Wipe existing search data, if any
            local_ousearch_document::delete_module_instance_data($cm);

            // Get all discussions for this forum
            $discussions = $DB->get_records('forumng_discussions',
                array('forumngid' => $cm->instance), '', 'id, postid');
            if ($feedback) {
                print '<li><strong>' . $cm->name . '</strong> (' . count($discussions) . '):';
            }

            // Process each discussion
            foreach ($discussions as $discussionrec) {
                // Ignore discussion with no postid
                // (This should not happen, where ther is a $discussionrec->id
                // it also shopuld have a $discussionrec->postid. This if-statement
                // fixes bug 10497 and would not have any side-effect.)
                if (!$discussionrec->postid) {
                    continue;
                }
                core_php_time_limit::raise($timelimitbefore);
                $discussion = mod_forumng_discussion::get_from_id($discussionrec->id,
                    self::CLONE_DIRECT, -1);
                $root = $discussion->get_root_post();
                $root->search_update();
                $root->search_update_children();
                $root = null;
                if ($feedback) {
                    echo '. ';
                    flush();
                }
                if ($progress) {
                    $progress->progress(\core\progress\base::INDETERMINATE);
                }
            }

            $transaction->allow_commit();

            if ($feedback) {
                print '</li>';
            }
        }
    }

    // UI
    /*///*/

    /**
     * Returns HTML for search form, or blank if there is no search facility
     * in this forum.
     * @param string $querytext Text of query (not escaped)
     * @return string HTML code for search form
     */
    public function display_search_form($querytext='') {
        if (!self::search_installed()) {
            return '';
        }
        $linkfields = $this->get_link_params(self::PARAM_FORM);
        $out = mod_forumng_utils::get_renderer();
        $help = $out->help_icon('searchthisforum', 'forumng');
        return $out->render_search_form($querytext, $linkfields, $help, $this);
    }

    /**
     * Displays the post button, if user is permitted to post.
     * @param int $groupid Group ID being shown
     * @return string HTML code for post button or empty string if none
     */
    public function display_post_button($groupid) {
        $out = mod_forumng_utils::get_renderer();
        if ($this->can_start_discussion($groupid, $whynot)) {
            return $out->render_post_button($this, $groupid);
        } else {
            if ($whynot) {
                return $out->cannot_start_discussion_error($whynot);
            } else {
                return '';
            }
        }
    }

    /**
     * Displays discussion list features for this forum. Features are the
     * plugins in the 'feature' subfolder - basically a row of buttons along
     * the bottom.
     * Will call JS AMD module for these buttons.
     *
     * @param int $groupid Group ID
     * @return string HTML code for discussion list features
     */
    public function display_forumngfeature_discussion_lists($groupid) {
        global $PAGE;
        // Print discussion list feature buttons (userposts button)
        $features = '';
        foreach (forumngfeature_discussion_list::get_all() as $feature) {
            if ($feature->should_display($this, $groupid)) {
                $features .= html_writer::start_div('forumngfeature_' . $feature->get_id());
                $features .= $feature->display($this, $groupid);
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
     * Displays subscribe options for this forum.
     * @param bool $expectquery True if we expect this to make a DB query
     * @return string HTML code for subscribe information section
     */
    public function display_subscribe_options($expectquery = false) {
        $out = mod_forumng_utils::get_renderer();

        // Is user subscribed to this forum?
        $text = '';
        $subscribed = self::NOT_SUBSCRIBED;
        $canchange = false;
        $canview = false;
        $type = $this->get_effective_subscription_option();
        $cm = $this->get_course_module();
        if ($type == self::SUBSCRIPTION_NOT_PERMITTED) {
            // Subscription not allowed
            $text = get_string('subscribestate_not_permitted', 'forumng');
        } else if (!$this->can_be_subscribed()) {
            // Current user not allowed to subscribe
            $text = get_string('subscribestate_no_access', 'forumng');
        } else {
            global $USER;
            $subscriptioninfo = $this->get_subscription_info(0, $expectquery);
            if (!$this->get_group_mode()) {
                if ($subscriptioninfo->wholeforum) {
                    // Subscribed to the entire forum.
                    $subscribed = self::FULLY_SUBSCRIBED;
                    $text = get_string('subscribestate_subscribed', 'forumng',
                        '<strong>' . $USER->email . '</strong>');
                } else if (count($subscriptioninfo->discussionids) == 0) {
                    // Not subscribed at all.
                    $text = get_string('subscribestate_unsubscribed', 'forumng');
                } else {
                    // Subscribed to one or more discussions.
                    $subscribed = self::PARTIALLY_SUBSCRIBED;
                    $text = get_string('subscribestate_partiallysubscribed', 'forumng',
                        '<strong>' . $USER->email . '</strong>');
                }
            } else {
                $currentgroupid = $this->get_activity_group($cm, true);
                if ($subscriptioninfo->wholeforum) {
                    // Subscribed to the entire forum.
                    if ($currentgroupid == self::ALL_GROUPS) {
                        $text = get_string('subscribestate_subscribed', 'forumng',
                        '<strong>' . $USER->email . '</strong>');
                        $subscribed = self::FULLY_SUBSCRIBED;
                    } else {
                        $text = get_string('subscribestate_subscribed', 'forumng',
                                '<strong>' . $USER->email . '</strong>') . ' ' .
                                ($canchange ? get_string(
                                    'subscribestate_subscribed_notinallgroup',
                                    'forumng') : '');
                        $subscribed = self::FULLY_SUBSCRIBED_GROUPMODE;
                    }
                } else if (count($subscriptioninfo->groupids) == 0) {
                    if (count($subscriptioninfo->discussionids) == 0) {
                        // Not subscribed at all.
                        if ($currentgroupid == self::ALL_GROUPS) {
                            // Return the default value NOT_SUBSCRIBED
                            $text = get_string('subscribestate_unsubscribed', 'forumng');
                        } else {
                            $text = get_string('subscribestate_unsubscribed_thisgroup', 'forumng');
                            $subscribed = self::THIS_GROUP_NOT_SUBSCRIBED;
                        }
                    } else {
                        // Only subscribed to discussions;
                        if ($currentgroupid == self::ALL_GROUPS) {
                            $subscribed = self::PARTIALLY_SUBSCRIBED;
                            $text = get_string('subscribestate_partiallysubscribed', 'forumng',
                                '<strong>' . $USER->email . '</strong>');
                        } else {
                            // Set default that the discussions do not belong to the current group.
                            $text = get_string('subscribestate_unsubscribed_thisgroup', 'forumng');
                            $subscribed = self::THIS_GROUP_NOT_SUBSCRIBED;
                            // Check if any of the discussions belongs to the current group.
                            foreach ($subscriptioninfo->discussionids as
                                    $discussionid => $groupid) {
                                if ($groupid == $currentgroupid) {
                                    $text = get_string(
                                            'subscribestate_partiallysubscribed_thisgroup',
                                            'forumng', '<strong>' . $USER->email . '</strong>');
                                    $subscribed = self::THIS_GROUP_PARTIALLY_SUBSCRIBED;
                                    break;
                                }
                            }
                        }
                    }

                } else {
                    // Subscribed to one or more groups as the groupids array are not empty.
                    if ($currentgroupid == self::ALL_GROUPS) {
                        $text = get_string('subscribestate_groups_partiallysubscribed', 'forumng',
                            '<strong>' . $USER->email . '</strong>');
                        // Treat this scenario the same as discussions partically subscribed since
                        // they all give the same options which is
                        // subscribe to the whole forum or unsubscribe from the whole forum.
                        $subscribed = self::PARTIALLY_SUBSCRIBED;
                    } else {
                        // Check if have subscribed to the current group.
                        $currentgroupsubscriptionstatus = false;
                        // Check if any of the discussions belong to the current group.
                        foreach ($subscriptioninfo->groupids as $id) {
                            if ($id == $currentgroupid) {
                                $text = get_string('subscribestate_subscribed_thisgroup',
                                        'forumng', '<strong>' . $USER->email . '</strong>');
                                $subscribed = self::THIS_GROUP_SUBSCRIBED;
                                $currentgroupsubscriptionstatus = true;
                                break;
                            }
                        }
                        if (!$currentgroupsubscriptionstatus) {
                            // Not subscribed to the current group.
                            if (count($subscriptioninfo->discussionids) == 0) {
                                $text = get_string('subscribestate_unsubscribed_thisgroup',
                                        'forumng');
                                $subscribed = self::THIS_GROUP_NOT_SUBSCRIBED;
                            } else {
                                // Check if any discussions subscribed belong to this group
                                // Set default that the discussions do not belong
                                // to the current group
                                $text = get_string('subscribestate_unsubscribed_thisgroup',
                                        'forumng');
                                $subscribed = self::THIS_GROUP_NOT_SUBSCRIBED;
                                // Check if any of the discussions belong to the current group.
                                foreach ($subscriptioninfo->discussionids as
                                        $discussionid => $groupid) {
                                    if ($groupid == $currentgroupid) {
                                        $text = get_string(
                                                'subscribestate_partiallysubscribed_thisgroup',
                                                'forumng',
                                                '<strong>' . $USER->email . '</strong>');
                                        $subscribed = self::THIS_GROUP_PARTIALLY_SUBSCRIBED;
                                        break;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // Display extra information if they are forced to subscribe
            if ($this->is_forced_to_subscribe()) {
                $text .= ' ' . get_string('subscribestate_forced', 'forumng');
            } else {
                $canchange = true;
            }
        }

        return $out->render_subscribe_options($this, $text,
            $subscribed, $canchange, $this->can_view_subscribers());
    }

    /**
     * @param object $user User object
     * @return string HTML that contains a link to the user's profile, with
     *   their name as text
     */
    public function display_user_name($user) {
        return fullname($user, has_capability(
            'moodle/site:viewfullnames', $this->get_context()));
    }

    /**
     * @param object $user User object
     * @return string HTML that contains a link to the user's profile, with
     *   their name as text
     */
    public function display_user_link($user) {
        global $CFG;
        if ($this->is_shared()) {
            $coursepart = '';
        } else {
            $coursepart = '&amp;course=' . $this->get_course()->id;
        }
        return "<a href='{$CFG->wwwroot}/user/view.php?id={$user->id}" .
            "$coursepart'>" . $this->display_user_name($user) . "</a>";
    }

    /**
     * @param int $groupid Group ID
     * @return string HTML links for RSS/Atom feeds to this discussion (if
     *   enabled etc)
     */
    public function display_feed_links($groupid) {
        global $CFG;

        // Check they're allowed to see it
        if ($this->get_effective_feed_option() == self::FEEDTYPE_NONE) {
            return '';
        }

        // Icon (decoration only) and Atom link
        $out = mod_forumng_utils::get_renderer();
        return $out->render_feed_links($this->get_feed_url(self::FEEDFORMAT_ATOM, $groupid),
                $this->get_feed_url(self::FEEDFORMAT_RSS, $groupid));
    }

    /**
     * Displays warnings for the invalid forum archive setting.
     * @return string HTML code for the warning message
     */
    public function display_archive_warning() {
        $course = $this->get_course();
        if (has_capability('moodle/course:manageactivities', $this->get_context())) {
            if (!$this->forumfields->removeto == -1) {
                if ($this->forumfields->removeafter && $this->forumfields->removeto) {
                    $modinfo = get_fast_modinfo($course);
                    $warningtext = '';
                    if (!($this->can_archive_forum($modinfo, $warningtext))) {
                        return '<div class="forumng-archivewarning">' . $warningtext . '</div>';
                    }
                }
            }
        }
        return '';
    }

    public function display_sharing_info() {
        global $CFG, $DB;
        // If it's not shared, nothing to show
        if (!$this->is_shared()) {
            return '';
        }
        // Only show this to people who can edit and stuff
        if (!has_capability('moodle/course:manageactivities', $this->get_context())) {
            return '';
        }
        // OK, let's show!
        $out = '<div class="forumng-shareinfo">';
        if ($this->get_course_module_id() != $this->get_course_module_id(true)) {
            // We are looking at a clone. Show link to original, if user can
            // see it, otherwise text.
            $a = (object)array(
                'url' => $CFG->wwwroot . '/mod/forumng/view.php?id=' .
                        $this->get_course_module_id(true),
                'shortname' => s($this->get_course(true)->shortname)
            );
            $out .= get_string('sharedviewinfoclone', 'forumng', $a);
        } else {
            // We are looking at an original.
            // I want to display the idnumber here - unfortuantely this requires
            // an extra query because it is not included in get_fast_modinfo.
            $idnumber = $DB->get_field('course_modules', 'idnumber', array('id' =>
                $this->get_course_module_id(true)));
            $out .= get_string('sharedviewinfooriginal', 'forumng', $idnumber);
            $out .= ' ';

            // Show links to each clone, if you
            // can see them.
            $clones = $this->get_clone_details();
            if (count($clones) == 0) {
                $out .= get_string('sharedviewinfonone', 'forumng');
            } else {
                $list = '';
                foreach ($clones as $clone) {
                    if ($list) {
                        $list .= ', ';
                    }

                    // Make it a link if you have access
                    if ($link = has_capability('moodle/course:view', $clone->context)) {
                        $list .= '<a href="' . $CFG->wwwroot .
                                '/mod/forumng/view.php?id=' .
                                $clone->context->instanceid . '">';
                    }
                    $list .= s($clone->courseshortname);
                    if ($link) {
                        $list .= '</a>';
                    }
                }
                $out .= get_string('sharedviewinfolist', 'forumng', $list);
            }
        }
        $out .= '</div>';
        return $out;
    }

    /**
     * Sets up $PAGE object then prints the header for a page 'within' a forum. Returns the
     * renderer object.
     * @param moodle_url $url URL of page
     * @param string $pagename Name of page (will be added to breadcrumbs)
     * @param array $navigation If specified, adds extra elements before the
     *   page name
     * @return mod_forumng_renderer Renderer that can be used e.g. to print footer, other things
     */
    public function init_page(moodle_url $url, $pagename='', $navigation=array()) {
        global $PAGE;
        $PAGE->set_url($url);
        $PAGE->set_context($this->get_context());
        $course = $this->get_course();
        $PAGE->set_heading($course->fullname);
        $PAGE->set_title($course->shortname . ': ' . format_string($this->get_name()));
        $PAGE->set_cm($this->get_course_module(), $course);
        foreach ($navigation as $text => $url) {
            $PAGE->navbar->add($text, $url);
        }
        if ($pagename) {
            $PAGE->navbar->add($pagename);
        }
        $buttontext = $this->display_search_form();
        $PAGE->set_button($buttontext);

        return mod_forumng_utils::get_renderer();
    }

    /**
     * Prints form JavaScript (much smaller than normal JS).
     */
    public function print_form_js() {
        global $CFG, $PAGE;
        $simple = get_user_preferences('forumng_simplemode', '');
        if ($PAGE->devicetypeinuse == 'legacy' || $simple) {
            return;
        }
        $module = array(
            'name'      => 'mod_forumng_form',
            'fullpath'  => '/mod/forumng/form.js',
            'requires'  => array('base', 'node'),
            'strings'   => array(array('edit_timeout', 'forumng'))
        );
        $PAGE->requires->js_init_call('M.mod_forumng_form.init',
                array(), false, $module);
    }

    /**
     * Prints out (immediately; must be after header) script tags and JS code
     * for the forum's JavaScript library, and required YUI libraries.
     * @param int $cmid If specified, passes this through to JS
     */
    public function print_js($cmid=0) {
        global $CFG, $PAGE;
        $simple = get_user_preferences('forumng_simplemode', '');
        if ($PAGE->devicetypeinuse == 'legacy' || $simple) {
            return;
        }

        // Prepare strings
        $mainstrings = array(
            'rate' => null,
            'expand' => '#',
            'jserr_load' => null,
            'jserr_save' => null,
            'jserr_alter' => null,
            'jserr_attachments' => null,
            'confirmdelete' => null,
            'confirmundelete' => null,
             'confirmdeletediscuss' => null,
            'deleteemailpostbutton' => null,
            'deletepostbutton' => null,
            'undeletepostbutton' => null,
            'js_nratings' => null,
            'js_nratings1' => null,
            'js_nopublicrating' => null,
            'js_publicrating' => null,
            'js_nouserrating' => null,
            'js_userrating' => null,
            'js_outof' => null,
            'js_clicktosetrating' => null,
            'js_clicktosetrating1' => null,
            'js_clicktoclearrating' => null,
            'selectlabel' => null,
            'selectintro' => null,
            'confirmselection' => null,
            'selectedposts' => null,
            'discussion' => null,
            'selectorall' => null,
            'selectoralldisc' => null,
            'selectorselecteddisc' => null,
            'selectordiscall' => null,
            'selectdiscintro' => null,
            'flagon' => null,
            'flagoff' => null,
            'clearflag' => null,
            'setflag' => null,
            'flagpost' => null);
        if ($this->has_post_quota()) {
            $mainstrings['quotaleft_plural'] = (object)array(
                'posts'=>'#', 'period' => $this->get_max_posts_period(true, true));
            $mainstrings['quotaleft_singular'] = (object)array(
                'posts'=>'#', 'period' => $this->get_max_posts_period(true, true));
        }
        $stringlist = array();
        foreach ($mainstrings as $string => $value) {
            $stringlist[] = array($string, 'forumng', $value);
        }
        foreach (array('cancel', 'delete', 'add', 'selectall', 'deselectall') as $string) {
            $stringlist[] = array($string, 'moodle');
        }

        // Use star ratings where the scale is between 2 and 5 (3 and 6 stars)
        $out = mod_forumng_utils::get_renderer();
        $scale = $this->get_rating_scale();
        if ($scale > 1 && $scale < 6) {
            $ratingstars = $scale;
        } else {
            $ratingstars = 0;
        }
        $starurls = array();
        foreach (array('circle', 'star') as $base) {
            foreach (array('y', 'n') as $user) {
                foreach (array('y', 'n') as $public) {
                    $key = "$base-$user-$public";
                    $starurls[$key] = $out->image_url($key, 'forumng')->out(false);
                }
            }
        }

        $module = array(
            'name'      => 'mod_forumng',
            'fullpath'  => '/mod/forumng/module.js',
            'requires'  => array('base', 'node', 'node-event-simulate', 'dom', 'event', 'io',
                'anim', 'json-parse'),
            'strings'   => $stringlist
        );
        $PAGE->requires->js_init_call('M.mod_forumng.init',
                array($cmid ? $cmid : 0,
                    $this->is_shared() ? $this->get_course_module_id() : 0,
                    $ratingstars, $this->get_remaining_post_quota(),
                    $out->image_url('i/ajaxloader')->out(false), $starurls),
                false, $module);
    }

    // Feeds
    // ////.

    /**
     * Key that allows access to this forum's Atom/RSS feeds
     * @param int $groupid Group ID/constant
     * @param int $userid User ID or 0 for current
     * @return Value of required authentication key
     */
    public function get_feed_key($groupid, $userid=0) {
        $userid = mod_forumng_utils::get_real_userid($userid);
        switch ($groupid) {
            case self::ALL_GROUPS:
                if ($this->get_group_mode()) {
                    $group = 'all';
                    break;
                }
                // Otherwise not in group mode, so actually fall through
            case self::NO_GROUPS:
                $group = 'none';
                break;
            default:
                $group = $groupid;
                break;
        }
        $text = $this->forumfields->magicnumber . $group . '_' . $userid;
        return sha1($text);
    }

    /**
     * @return int Number of items that should be included in Atom/RSS feeds
     *   for this forum
     */
    public function get_effective_feed_items() {
        global $CFG;

        // Global 'force' used if set
        $result = $CFG->forumng_feeditems;
        if ($result == -1) {
            // Otherwise use module setting
            $result = $this->forumfields->feeditems;
        }

        return $result;
    }

    /**
     * Gets URL for an Atom/RSS feed.
     * @param int $feedformat FEEDFORMAT_xx constant
     * @param int $groupid Group ID
     * @param int $userid User ID or 0 for current
     * @return string URL for feed
     */
    public function get_feed_url($feedformat, $groupid, $userid=0) {
        global $CFG;
        $userid = mod_forumng_utils::get_real_userid($userid);

        return $CFG->wwwroot . '/mod/forumng/feed.php?' .
            $this->get_link_params(self::PARAM_PLAIN) .
            '&user=' . $userid . ($groupid == self::ALL_GROUPS
                || $groupid == self::NO_GROUPS ? '' : '&group=' . $groupid) .
            '&key=' . $this->get_feed_key($groupid, $userid) . '&format=' .
            ($feedformat == self::FEEDFORMAT_RSS ? 'rss' : 'atom');
    }

    /**
     * Obtains list of discussions to include in an Atom/RSS feed (the kind
     * that lists discussions only and not full posts).
     * @param int $groupid Group ID (may be ALL_GROUPS)
     * @param int $userid User ID
     * @return array Array of mod_forumng_discussion objects
     */
    public function get_feed_discussions($groupid, $userid=0) {
        // Number of items to output
        $items = $this->get_effective_feed_items();

        // Get most recent N discussions from db
        $rs = mod_forumng_discussion::query_discussions(
            'fd.forumngid = ? AND fd.deleted = 0', array($this->get_id()), -1,
            'timemodified DESC', 0, $items);
        $result = array();
        foreach ($rs as $rec) {
            // Create a new discussion from the database details
            $discussion = new mod_forumng_discussion($this, $rec, true, -1);
            if ($this->get_type()->can_view_discussion($discussion, $userid)) {
                $result[$discussion->get_id()] = $discussion;
            }
        }
        $rs->close();
        return $result;
    }

    /**
     * Obtains list of posts to include in an Atom/RSS feed.
     * @param int $groupid Group ID (may be ALL_GROUPS)
     * @param int $userid User ID
     * @param mod_forumng_discussion $discussion Discussion object (intended only
     *   for calls via the mod_forumng_discussion method)
     * @return array Array of mod_forumng_post objects
     */
    public function get_feed_posts($groupid, $userid, $discussion=null) {
        // Don't let user view any posts in a discussion feed they can't see
        // (I don't think they should be given a key in this case, but just
        // to be sure).
        if ($discussion &&
            !$this->get_type()->can_view_discussion($discussion, $userid)) {
            return array();
        }

        // Number of items to output
        $items = $this->get_effective_feed_items();

        // Get most recent N posts from db
        $whereparams = array();
        if ($discussion) {
            $where = 'fd.id = ?';
            $whereparams[] = $discussion->get_id();
        } else {
            $where = 'fd.forumngid = ?';
            $whereparams[] = $this->get_id();
            if ($this->get_group_mode() && $groupid!=self::ALL_GROUPS) {
                $where .= ' AND fd.groupid = ?';
                $whereparams[] = $groupid;
            }
        }

        // Don't include deleted or old-version posts
        $where .= ' AND fp.oldversion = 0 AND fp.deleted = 0 AND fd.deleted = 0';
        // Or ones out of time
        $now = time();
        $where .= " AND (fd.timestart < ?)" .
                " AND (fd.timeend = 0 OR fd.timeend > ?)";
        $whereparams[] = $now;
        $whereparams[] = $now;

        $postrecs = mod_forumng_post::query_posts($where, $whereparams,
            'GREATEST(fp.created, fd.timestart) DESC',
            false, false, false, $userid, true, false, 0, $items);
        if (count($postrecs) == 0) {
            // No posts!
            return array();
        }

        $result = array();
        if ($discussion) {
            foreach ($postrecs as $rec) {
                $post = new mod_forumng_post($discussion, $rec, null);
                $result[$rec->id] = $post;
            }
        } else {
            // Based on these posts, get all mentioned discussions
            $discussionids = array();
            $discussionposts = array();
            foreach ($postrecs as $rec) {
                $discussionids[] = $rec->discussionid;
                $discussionposts[$rec->discussionid][] = $rec->id;
            }

            list($discussionpart, $discussionpartparams) =
                    mod_forumng_utils::get_in_array_sql('fd.id', $discussionids);
            $rs = mod_forumng_discussion::query_discussions(
                $discussionpart, $discussionpartparams, -1, 'id');

            // Build the discussion and post objects
            $posts = array();
            foreach ($rs as $rec) {
                $discussion = new mod_forumng_discussion($this, $rec, true, -1);
                if ($discussion->can_view($userid)) {
                    foreach ($discussionposts[$discussion->get_id()] as $postid) {
                        $post = new mod_forumng_post($discussion,
                            $postrecs[$postid], null);
                        $posts[$postid] = $post;
                    }
                }
            }
            $rs->close();

            // Put them back in order of the post records, and return
            foreach ($postrecs as $rec) {
                // Records might be excluded if user can't view discussion
                if (array_key_exists($rec->id, $posts)) {
                    $result[$rec->id] = $posts[$rec->id];
                }
            }
        }
        return $result;
    }

    /**
     * Obtains all draft posts in this forum by the given or current user,
     * in reverse date order.
     * @param int $userid User whose drafts will be retrieved. If zero,
     *   retrieves draft for current user
     * @return array Array of mod_forumng_draft objects
     */
    public function get_drafts($userid=0) {
        $userid = mod_forumng_utils::get_real_userid($userid);
        return mod_forumng_draft::query_drafts("fdr.forumngid = ? AND fdr.userid = ?",
                array($this->get_id(), $userid));
    }

    /**
     * Obtains all flagged post in this forum by the given or current user,
     * in reverse data order (of when they were flagged).
     * @param int $userid User whose flags will be retrieved; 0 = current
     * @return array Array of mod_forumng_post objects
     */
    public function get_flagged_posts($userid=0) {
        // Get all flagged posts. Note that we request the discussion row as
        // well, this is necessary (a) so we can include its forumngid field in
        // the query, and (b) because we will use that data to construct
        // basic discussion objects (without having to do another query).
        $records = mod_forumng_post::query_posts(
                'fd.forumngid = ? AND ff.flagged IS NOT NULL AND fp.deleted = 0',
                array($this->get_id()), 'ff.flagged DESC', false, true, false,
                $userid, true, true);

        // Construct post object for each one
        $result = array();
        foreach ($records as $record) {
            // Get discussion details from record
            $discussionfields = mod_forumng_utils::extract_subobject($record, 'fd_');
            $discussion = new mod_forumng_discussion($this, $discussionfields, false, -1);

            // Create post object
            $post = new mod_forumng_post($discussion, $record);
            $result[$record->id] = $post;
        }

        return $result;
    }

    /**
     * Obtain all flagged discussions in the forum by the given or current user,
     * The results should be ordered by the last post modified date (fplast.modified DESC)
     * @param int $userid User whose flags will be retrieved; 0 = current
     * @return array Array of mod_forumng_discussion objects
     */
    public function get_flagged_discussions($userid = 0) {
        global $USER;

        if ($userid != -1) {
            if ($userid == 0) {
                $userid = $USER->id;
            }
            $records = mod_forumng_discussion::query_discussions(
                'fd.forumngid = ? AND ff.flagged IS NOT NULL AND ff.discussionid = fd.id AND ff.userid = ?',
                 array($this->get_id(), $userid), $userid, 'x.flagged DESC', '', '', $this, true);
        }

        $result = array();
        foreach ($records as $record) {
            // Get discussion details from record and create discussionfields.
            $discussion = new mod_forumng_discussion($this, $record, true, $userid);
            $result[$record->id] = $discussion;
        }
        return $result;
    }

    /**
     * @param bool $mustusecounter True if this function should return false
     *   unless one or more of the three types of post counters are in use
     * @return bool True if automatic completion is enabled for this forum
     */
    public function is_auto_completion_enabled($mustusecounter=false) {
        // If this check is really checking that one of the actual counters
        // is on, then do those first as they're simple field checks
        if ($mustusecounter && !$this->forumfields->completionposts
            && !$this->forumfields->completionreplies
            && !$this->forumfields->completiondiscussions) {
            return false;
        }

        $completion = new completion_info($this->get_course());
        return ($completion->is_enabled($this->get_course_module()) ==
                COMPLETION_TRACKING_AUTOMATIC);
    }

    /**
     * @return int Number of posts required for this forum to be marked
     *   complete, or 0 if posts are not required for completion/completion
     *   is turned off.
     */
    public function get_completion_posts() {
        return $this->is_auto_completion_enabled()
            ? $this->forumfields->completionposts : 0;
    }

    /**
     * @return int Number of posts required for this forum to be marked
     *   complete, or 0 if posts are not required for completion/completion
     *   is turned off.
     */
    public function get_completion_discussions() {
        return $this->is_auto_completion_enabled()
            ? $this->forumfields->completiondiscussions: 0;
    }

    /**
     * @return int Number of posts required for this forum to be marked
     *   complete, or 0 if posts are not required for completion/completion
     *   is turned off.
     */
    public function get_completion_replies() {
        return $this->is_auto_completion_enabled()
            ? $this->forumfields->completionreplies : 0;
    }

    /**
     * Used by lib.php forumng_get_completion_state.
     * @param int $userid User ID
     * @param bool $type Type of comparison (or/and; can be used as return
     *   value if no conditions)
     * @return bool True if completed, false if not (if no conditions, then
     *   return value is $type)
     */
    public function get_completion_state($userid, $type) {
        global $DB;
        $result = $type; // Default return value

        $forumngid = $this->get_id();
        $postcountsql = "
SELECT
    COUNT(1)
FROM
    {forumng_posts} fp
    INNER JOIN {forumng_discussions} fd ON fp.discussionid = fd.id
WHERE
    fp.userid = ? AND fd.forumngid = ? AND fp.deleted = 0 AND fd.deleted = 0";
        $postcountparams = array($userid, $forumngid);

        if ($this->forumfields->completiondiscussions) {
            $value = $this->forumfields->completiondiscussions <=
                    $DB->get_field_sql( $postcountsql . ' AND fp.parentpostid IS NULL',
                    $postcountparams);
            if ($type==COMPLETION_AND) {
                $result = $result && $value;
            } else {
                $result = $result || $value;
            }
        }
        if ($this->forumfields->completionreplies) {
            $value = $this->forumfields->completionreplies <=
                    $DB->get_field_sql( $postcountsql . ' AND fp.parentpostid IS NOT NULL',
                            $postcountparams);
            if ($type==COMPLETION_AND) {
                $result = $result && $value;
            } else {
                $result = $result || $value;
            }
        }
        if ($this->forumfields->completionposts) {
            $value = $this->forumfields->completionposts <=
                    $DB->get_field_sql($postcountsql, $postcountparams);
            if ($type==COMPLETION_AND) {
                $result = $result && $value;
            } else {
                $result = $result || $value;
            }
        }

        return $result;
    }

    // Conversion
    /*///////////*/

    /**
     * Creates a new ForumNG by copying data (including all messages etc) from
     * an old forum. The old forum will be hidden.
     *
     * Behaviour is undefined if the old forum wasn't eligible for conversion
     * (mod_forumng_utils::get_convertible_forums).
     * @param object $course Moodle course object
     * @param int $forumcmid Old forum to convert
     * @param bool $progress If true, print progress to output
     * @param bool $hide If true, newly-created forum is also hidden
     * @param bool $nodata If true, no user data (posts, subscriptions, etc)
     *   is copied; you only get a forum with same configuration
     * @param bool $insection If true, remeber to create the new forumNG in the same section.
     * @throws mod_forumng_exception If any error occurs
     */
    public static function create_from_old_forum($course, $forumcmid, $progress, $hide,
            $nodata, $insection=true) {
        global $CFG, $DB, $OUTPUT;

        // Start the clock and a database transaction
        $starttime = microtime(true);
        $transaction = $DB->start_delegated_transaction();

        // Note we do not use get_fast_modinfo because it doesn't contain the
        // complete $cm object.
        $cm = $DB->get_record('course_modules', array('id' => $forumcmid), '*', MUST_EXIST);
        $forum = $DB->get_record('forum', array('id' => $cm->instance), '*', MUST_EXIST);
        if ($progress) {
            echo $OUTPUT->heading(s($forum->name), 3);
            print '<ul><li>' . get_string('convert_process_init', 'forumng');
            flush();
        }

        // Hide forum
        $DB->update_record('course_modules', (object)array(
            'id' => $cm->id, 'visible'=>0));

        // Table for changed subscription constants
        $subscriptiontranslate = array(0=>1, 1=>3, 2=>2, 3=>0);

        // Get, convert, and create forum table data
        $forumng = (object)array(
            'course' => $course->id,
            'name' => $forum->name,
            'type' => 'general',
            'intro' => $forum->intro,
            'introformat' => $forum->introformat,
            'introduction' => $forum->introduction,
            'introductionformat' => $forum->introductionformat,
            'ratingscale' => $forum->scale,
            'ratingfrom' => $forum->assesstimestart,
            'ratinguntil' => $forum->assesstimefinish,
            'ratingthreshold' => 1,
            'grading' => $forum->assessed,
            'attachmentmaxbytes' => $forum->maxbytes,
            'subscription' => $subscriptiontranslate[$forum->forcesubscribe],
            'feedtype' => $forum->rsstype,
            'feeditems' => $forum->rssarticles,
            'maxpostsperiod' => $forum->blockperiod,
            'maxpostsblock' => $forum->blockafter,
            'postingfrom' => 0,
            'postinguntil' => 0,
            'typedata' => null);
        require_once($CFG->dirroot . '/mod/forumng/lib.php');

        // Note: The idnumber is required. We cannot copy it because then there
        // would be a duplicate idnumber. Let's just leave blank, people will
        // have to configure this manually.
        $forumng->cmidnumber = '';
        if (!($newforumngid = forumng_add_instance($forumng))) {
            throw new coding_exception("Failed to add forumng instance");
        }
        $forumng->id = $newforumngid;

        // Create and add course-modules entry
        $newcm = new stdClass;
        $newcm->course = $course->id;
        $newcm->module = $DB->get_field('modules', 'id', array('name' => 'forumng'));
        if (!$newcm->module) {
            throw new coding_exception("Cannot find forumng module id");
        }
        $newcm->instance = $newforumngid;
        $newcm->section = $cm->section;
        $newcm->added = time();
        $newcm->score = $cm->score;
        $newcm->indent = $cm->indent;
        $newcm->visible = 0; // Forums are always hidden until finished
        $newcm->groupmode = $cm->groupmode;
        $newcm->groupingid = $cm->groupingid;
        $newcm->idnumber = $cm->idnumber;
        $newcm->completion = $cm->completion;
        $newcm->completiongradeitemnumber = $cm->completiongradeitemnumber;
        $newcm->completionview = $cm->completionview;
        $newcm->availability = $cm->availability;

        // Add
        $newcm->id = $DB->insert_record('course_modules', $newcm);

        $oldcontext = context_module::instance($cm->id);
        $newcontext = context_module::instance($newcm->id);

        // Update section.
        if ($insection) {
            $section = $DB->get_record('course_sections', array('id' => $newcm->section),
                    '*', MUST_EXIST);
            $updatesection = (object)array(
                    'id' => $section->id,
                    'sequence' => str_replace(
                        $cm->id, $cm->id . ',' . $newcm->id, $section->sequence));
            if ($updatesection->sequence == $section->sequence) {
                throw new coding_exception("Unable to update sequence");
            }
            $DB->update_record('course_sections', $updatesection);
        }
        // Construct forum object for new forum
        $newforum = self::get_from_id($forumng->id, self::CLONE_DIRECT);

        if ($progress) {
            print ' ' . get_string('convert_process_state_done', 'forumng') . '</li>';
        }

        if (!$nodata) {
            // Convert subscriptions
            switch ($newforum->get_effective_subscription_option()) {
                case self::SUBSCRIPTION_PERMITTED:
                    if ($progress) {
                        print '<li>' . get_string(
                            'convert_process_subscriptions_normal', 'forumng');
                        flush();
                    }
                    // Standard subscription - just copy subscriptions.
                    $rs = $DB->get_recordset('forum_subscriptions',
                            array('forum' => $forum->id));
                    foreach ($rs as $rec) {
                        $DB->insert_record('forumng_subscriptions', (object)array(
                            'forumngid' => $forumng->id,
                            'userid' => $rec->userid,
                            'subscribed' => 1));
                    }
                    $rs->close();
                    if ($progress) {
                        print ' ' . get_string(
                            'convert_process_state_done', 'forumng') . '</li>';
                    }
                    break;

                case self::SUBSCRIPTION_INITIALLY_SUBSCRIBED:
                    // Initial subscription is handled differently; the old forum
                    // stores all the subscriptions in the database, while in this
                    // forum we only store people who chose to unsubscribe
                    if ($progress) {
                        print '<li>' . get_string(
                            'convert_process_subscriptions_initial', 'forumng');
                        flush();
                    }

                    // Get list of those subscribed on old forum.
                    $rs = $DB->get_recordset('forum_subscriptions',
                            array('forum' => $forum->id));
                    $subscribedbefore = array();
                    foreach ($rs as $rec) {
                        $subscribedbefore[$rec->userid] = true;
                    }
                    $rs->close();

                    // Get list of those subscribed on new forum
                    $new = $newforum->get_subscribers();

                    // For anyone in the new list but not the old list, add an
                    // unsubscribe
                    foreach ($new as $user) {
                        if (!array_key_exists($user->id , $subscribedbefore)) {
                            $DB->insert_record('forumng_subscriptions', (object)array(
                                'forumngid' => $forumng->id,
                                'userid' => $user->id,
                                'subscribed' => 0));
                        }
                    }

                    if ($progress) {
                        print ' ' . get_string(
                            'convert_process_state_done', 'forumng') . '</li>';
                    }
                    break;
            }

            // Convert discussions
            if ($progress) {
                print '<li>' . get_string(
                    'convert_process_discussions', 'forumng');
                flush();
            }
            $rsd = $DB->get_recordset(
                'forum_discussions', array('forum' => $forum->id));
            $count = 0;
            foreach ($rsd as $recd) {
                // Convert discussion options
                $newd = (object)array(
                    'forumngid' => $forumng->id,
                    'timestart' => $recd->timestart,
                    'timeend' => $recd->timeend,
                    'deleted' => 0,
                    'locked' => 0,
                    'sticky' => 0
                );
                if ($recd->groupid == -1 || !$newcm->groupmode) {
                    $newd->groupid = null;
                } else {
                    $newd->groupid = $recd->groupid;
                }

                // Save discussion
                $newd->id = $DB->insert_record('forumng_discussions', $newd);

                // Convert posts
                $lastposttime = -1;
                $discussionupdate = (object)array('id' => $newd->id);
                $postids = array(); // From old post id to new post id.
                $parentposts = array(); // From new post id to old parent id.
                $subjects = array(); // From new id to subject text (no slashes).
                $rsp = $DB->get_recordset('forum_posts', array('discussion' => $recd->id));
                foreach ($rsp as $recp) {
                    // Convert post
                    $newp = (object)array(
                        'discussionid' => $newd->id,
                        'userid' => $recp->userid,
                        'created' => $recp->created,
                        'modified' => $recp->modified,
                        'deleted' => 0,
                        'deleteuserid' => null,
                        'mailstate' => self::MAILSTATE_DIGESTED,
                        'oldversion' => 0,
                        'edituserid' => null,
                        'subject' => $recp->subject,
                        'message' => $recp->message,
                        'messageformat' => $recp->messageformat,
                        'important' => 0);

                    // Are there any attachments?
                    $attachments = array();
                    // Standard forum uses attachment field for filename
                    if ($recp->attachment) {
                        $attachments[] = $recp->attachment;
                    }
                    $newp->attachments = count($attachments) ? 1 : 0;

                    // Add record
                    $newp->id = $DB->insert_record('forumng_posts', $newp);

                    // Remember details for later parent update
                    $postids[$recp->id] = $newp->id;
                    if ($recp->parent) {
                        $parentposts[$newp->id] = $recp->parent;
                    } else {
                        $discussionupdate->postid = $newp->id;
                    }
                    if ($newp->created > $lastposttime) {
                        $discussionupdate->lastpostid = $newp->id;
                    }
                    $subjects[$newp->id] = $recp->subject;

                    // Copy attachments.
                    $fo = get_file_storage();
                    $filesok = 0;
                    $filesfailed = 0;
                    $oldattachs = $fo->get_area_files($oldcontext->id, 'mod_forum', 'attachment', $recp->id, 'itemid', false);
                    foreach ($oldattachs as $attachment) {
                        $filerecord = array(
                                'component' => 'mod_forumng',
                                'itemid' => $newp->id,
                                'contextid' => $newcontext->id
                        );
                        $fo->create_file_from_storedfile($filerecord, $attachment);
                    }
                    $oldimgs = $fo->get_area_files($oldcontext->id, 'mod_forum', 'message', $recp->id, 'itemid', false);
                    foreach ($oldimgs as $attachment) {
                        $filerecord = array(
                                'component' => 'mod_forumng',
                                'itemid' => $newp->id,
                                'contextid' => $newcontext->id
                        );
                        $fo->create_file_from_storedfile($filerecord, $attachment);
                    }

                    // Convert ratings.
                    /*if ($forumng->ratingscale) {
                        // TODO: Support grades -> ratings.
                        $rsr = $DB->get_recordset('forum_ratings',
                                array('post' => $recp->id));
                        foreach ($rsr as $recr) {
                            $DB->insert_record('forumng_ratings', (object)array(
                                'postid' =>  $newp->id,
                                'userid' => $recr->userid,
                                'time' => $recr->time,
                                'rating' => $recr->rating));
                        }
                        $rsr->close();
                    }*/
                }
                $rsp->close();

                // Update parent numbers
                $newparentids = array();
                foreach ($parentposts as $newid => $oldparentid) {
                    if (!array_key_exists($oldparentid, $postids)) {
                        throw new coding_exception(
                            "Unknown parent post $oldparentid");
                    }
                    $newparentid = $postids[$oldparentid];
                    $DB->update_record('forumng_posts', (object)array(
                        'id' => $newid,
                        'parentpostid' => $newparentid));
                    $newparentids[$newid] = $newparentid;
                }

                // Update subjects
                $removesubjects = array(); // Array of ints to cancel subjects
                foreach ($newparentids as $newid => $newparentid) {
                    $subject = $subjects[$newid];
                    $parentsubject = $subjects[$newparentid];
                    if ($subject &&
                        ($subject == get_string('re', 'forum') . ' ' . $parentsubject
                        || $subject == $parentsubject)) {
                        $removesubjects[] = $newid;
                    }
                }
                if (count($removesubjects)) {
                    list($in, $inparams) = mod_forumng_utils::get_in_array_sql('id',
                            $removesubjects);
                    $DB->execute(
                        "UPDATE {forumng_posts} SET subject = NULL WHERE $in", $inparams);
                }

                // Update first/last post numbers
                $DB->update_record('forumng_discussions', $discussionupdate);

                // Convert read data
                $rsr = $DB->get_recordset_sql("
SELECT
    userid, MAX(lastread) AS lastread
FROM
    {forum_read}
WHERE
    discussionid = ?
GROUP BY
    userid", array($recd->id));
                foreach ($rsr as $recr) {
                    $DB->insert_record('forumng_read', (object)array(
                        'discussionid' => $newd->id,
                        'userid' => $recr->userid,
                        'time' => $recr->lastread));
                }
                $rsr->close();

                // Display dot for each discussion
                if ($progress) {
                    print '.';
                    $count++;
                    if ($count % 10 == 0) {
                        print $count;
                    }
                    flush();
                }
            }
            $rsd->close();
            if ($progress) {
                print ' ' . get_string(
                    'convert_process_state_done', 'forumng') . '</li>';
            }
        }

        // Show forum
        if (!$hide && $cm->visible) {
            if ($progress) {
                print '<li>' . get_string('convert_process_show', 'forumng');
                flush();
            }
            $updatecm = (object)array(
                'id' => $newcm->id,
                'visible' => 1);
            $DB->update_record('course_modules', $updatecm);
            if ($progress) {
                print ' ' . get_string('convert_process_state_done', 'forumng') . '</li>';
            }
        }

        // Transfer role assignments
        $roles = $DB->get_records('role_assignments', array('contextid' => $oldcontext->id));
        if ($roles) {
            if ($progress) {
                print '<li>' . get_string('convert_process_assignments', 'forumng');
                flush();
            }
            foreach ($roles as $role) {
                $newrole = $role;
                $newrole->contextid = $newcontext->id;
                $newrole->enrol = $newrole->enrol;
                $DB->insert_record('role_assignments', $newrole);
            }
            if ($progress) {
                print ' ' . get_string('convert_process_state_done', 'forumng') . '</li>';
            }
        }
        // Transfer capabilities
        $capabilities = array(
            'moodle/course:viewhiddenactivities' => 'moodle/course:viewhiddenactivities',
            'moodle/site:accessallgroups' => 'moodle/site:accessallgroups',
            'moodle/site:trustcontent' => 'moodle/site:trustcontent',
            'moodle/site:viewfullnames' => 'moodle/site:viewfullnames',

            'mod/forum:viewdiscussion' => 'mod/forumng:viewdiscussion',
            'mod/forum:startdiscussion' => 'mod/forumng:startdiscussion',
            'mod/forum:replypost' => 'mod/forumng:replypost',
            'mod/forum:viewrating' => 'mod/forumng:viewrating',
            'mod/forum:viewanyrating' => 'mod/forumng:viewanyrating',
            'mod/forum:rate' => 'mod/forumng:rate',
            'mod/forum:createattachment' => 'mod/forumng:createattachment',
            'mod/forum:deleteanypost' => 'mod/forumng:deleteanypost',
            'mod/forum:splitdiscussions' => 'mod/forumng:splitdiscussions',
            'mod/forum:movediscussions' => 'mod/forumng:movediscussions',
            'mod/forum:editanypost' => 'mod/forumng:editanypost',
            'mod/forum:viewsubscribers' => 'mod/forumng:viewsubscribers',
            'mod/forum:managesubscriptions' => 'mod/forumng:managesubscriptions',
            'mod/forum:viewhiddentimedposts' => 'mod/forumng:viewallposts'
        );
        $caps = $DB->get_records('role_capabilities', array('contextid' => $oldcontext->id));
        if ($caps) {
            if ($progress) {
                print '<li>' . get_string('convert_process_overrides', 'forumng');
                flush();
            }
            foreach ($caps as $cap) {
                foreach ($capabilities as $key => $capability) {
                    if ($cap->capability != $key) {
                        continue;
                    }
                    $newcap = $cap;
                    $newcap->contextid = $newcontext->id;
                    $newcap->capability = $capability;
                    $newcap->capability = $newcap->capability;
                    $DB->insert_record('role_capabilities', $newcap);
                }
            }
            if ($progress) {
                print ' ' . get_string('convert_process_state_done', 'forumng') . '</li>';
            }
        }

        // Do course cache
        rebuild_course_cache($course->id, true);

        // Update search data
        if (self::search_installed()) {
            if ($progress) {
                print '<li>' . get_string('convert_process_search', 'forumng') . '</li>';
                flush();
            }
            self::search_update_all($progress, $course->id, $newcm->id);
        }

        if ($progress) {
            print '<li>' . get_string('convert_process_update_subscriptions', 'forumng');
            flush();
        }
        self::group_subscription_update(false, $newcm->id);
        if ($progress) {
            print ' ' . get_string('convert_process_state_done', 'forumng') . '</li>';
        }
        $transaction->allow_commit();

        if ($progress) {
            $a = (object)array(
                'seconds' => round(microtime(true) - $starttime, 1),
                'link' => '<a href="view.php?id=' . $newcm->id . '">' .
                    get_string('convert_newforum', 'forumng') . '</a>');
            print '</ul><p>' . get_string('convert_process_complete', 'forumng',
                $a) . '</p>';
        }
    }

    /**
     * Returns user activity report information.
     * @param int $forumngid forumng id
     * @param int $userid Moodle user id
     * @return object or false
     */
    public static function get_user_activityreport($forumngid, $userid) {
        global $DB;
        return $DB->get_record_sql('
SELECT
    COUNT(p.id) AS postcount, MAX(p.modified) AS lastpost
FROM
    {forumng_discussions} d
    INNER JOIN {forumng_posts} p ON p.discussionid = d.id
WHERE
    d.forumngid = ?
    AND p.userid = ?
    AND d.deleted = 0 AND p.deleted = 0 AND p.oldversion = 0', array($forumngid, $userid));
    }

    /**
     * Gets all users within this forum who are supposed to be 'monitored'
     * (that means users who are enrolled on the course).
     * @param int $groupid Group ID or ALL_GROUPS/NO_GROUPS to get all users
     */
    public function get_monitored_users($groupid) {
        return get_enrolled_users($this->get_context(), '', $groupid > 0 ? $groupid : 0,
            "u.id, u.lastname, u.firstname, u.username");
    }

    /**
     * Returns all posts in this forum by the given user within the given group.
     * @param int $userid
     * @param int $groupid
     * @param string $order Sort order; the default is fp.id - note this is preferable
     *   to fp.timecreated because it works correctly if there are two posts in
     *   the same second
     * @param bool $hasrating if true only returns posts which ahve been rated
     * @return array Array of mod_forumng_post objects
     */
    public function get_all_posts_by_user($userid, $groupid, $order = 'fp.id', $start = null, $end = null, $hasrating = false) {
        global $CFG, $USER;
        $where = 'fd.forumngid = ? AND fp.userid = ? AND fp.oldversion = 0 AND fp.deleted = 0';
        $whereparams = array($this->get_id(), $userid);
        if ($groupid != self::NO_GROUPS && $groupid != self::ALL_GROUPS) {
            $where .= ' AND (fd.groupid = ? OR fd.groupid IS NULL)';
            $whereparams[] = $groupid;
        }
        if (!empty($start)) {
            $where .= ' AND fp.created >= ?';
            $whereparams[] = $start;
        }

        if (!empty($end)) {
            $where .= ' AND fp.created <= ?';
            $whereparams[] = $end;
        }
        if ($hasrating) {
            $where .= ' AND '.self::select_exists("SELECT r.itemid FROM {rating} r WHERE r.itemid = fp.id AND r.ratingarea = 'post'
                    AND r.contextid = ? AND r.userid <> ?");
            $whereparams[] = $this->get_context(true)->id;
            $whereparams[] = $userid;
        }
        $result = array();
        $posts = mod_forumng_post::query_posts($where, $whereparams, $order, false, false, true,
                0, true, true);
        // Add standard ratings if enabled.
        if ($this->get_enableratings() == mod_forumng::FORUMNG_STANDARD_RATING) {
            require_once($CFG->dirroot . '/rating/lib.php');
            // If grading is 'No grading' or 'Teacher grades students'.
            if ($this->get_grading() == mod_forumng::GRADING_NONE ||
                $this->get_grading() == mod_forumng::GRADING_MANUAL) {
                // Set the aggregation method.
                if ($this->get_rating_scale() > 0) {
                    $aggregate = RATING_AGGREGATE_AVERAGE;
                } else {
                    $aggregate = RATING_AGGREGATE_COUNT;
                }
            } else {
                $aggregate = $this->get_grading();
            }
            $ratingoptions = new stdClass();
            $ratingoptions->context = $this->get_context(true);
            $ratingoptions->component = 'mod_forumng';
            $ratingoptions->ratingarea = 'post';
            $ratingoptions->items = $posts;
            $ratingoptions->aggregate = $aggregate;
            $ratingoptions->scaleid = $this->get_rating_scale();
            $ratingoptions->userid = $USER->id;
            $ratingoptions->assesstimestart = $this->get_ratingfrom();
            $ratingoptions->assesstimefinish = $this->get_ratinguntil();

            $rm = new rating_manager();
            $posts = $rm->get_ratings($ratingoptions);
        }
        $discussions = array();
        foreach ($posts as $fields) {
            $discussionfields = mod_forumng_utils::extract_subobject($fields, 'fd_');
            if (!isset($discussions[$discussionfields->id])) {
                $discussions[$discussionfields->id] = new mod_forumng_discussion($this, $discussionfields, false, -1);
            }

            $result[$fields->id] = new mod_forumng_post($discussions[$discussionfields->id], $fields);
        }
        return $result;
    }

    /**
     * Returns all posts in this forum by the given user within the given group.
     * @param object $forum
     * @param int $userid
     * @param int $groupid
     * @param int $ratedstart
     * @param int $ratedend
     * @param string $order Sort order; the default is fp.id - note this is preferable
     *   to fp.timecreated because it works correctly if there are two posts in
     *   the same second
     * @param bool $hasrating if true only returns posts which ahve been rated
     * @return array Array of mod_forumng_post objects
     */
    public function get_rated_posts_by_user(
            $forum, $userid, $groupid, $order = 'fp.id', $ratedstart = null, $ratedend = null, $start = null, $end = null) {
        global $CFG, $USER;
        if ($forum->get_enableratings() != mod_forumng::FORUMNG_STANDARD_RATING) {
            return array();
        }
        $where = 'fd.forumngid = ? AND fp.userid <> ? AND fp.oldversion = 0 AND fp.deleted = 0';
        $whereparams = array($this->get_id(), $userid);
        if ($groupid != self::NO_GROUPS && $groupid != self::ALL_GROUPS) {
            $where .= ' AND (fd.groupid = ? OR fd.groupid IS NULL)';
            $whereparams[] = $groupid;
        }
        if (!empty($start)) {
            $where .= ' AND fp.created >= ?';
            $whereparams[] = $start;
        }

        if (!empty($end)) {
            $where .= ' AND fp.created <= ?';
            $whereparams[] = $end;
        }
        $sqlselectstring = 'SELECT r.itemid FROM {rating} r WHERE r.itemid = fp.id AND r.ratingarea = \'post\'
                AND r.contextid = ? AND r.userid = ?';
        $extraparams = array();
        if (!empty($ratedstart)) {
            $sqlselectstring .= ' AND r.timemodified >= ?';
            $extraparams[] = $ratedstart;
        }
        if (!empty($ratedend)) {
            $sqlselectstring .= ' AND r.timemodified <= ?';
            $extraparams[] = $ratedend;
        }
        $where .= ' AND '.self::select_exists($sqlselectstring);
        $whereparams[] = $this->get_context(true)->id;
        $whereparams[] = $userid;
        $whereparams = array_merge($whereparams, $extraparams);

        $result = array();
        $posts = mod_forumng_post::query_posts($where, $whereparams, $order, false, false, true,
                0, true, true);
        // Add standard ratings if enabled.
        if ($this->get_enableratings() == mod_forumng::FORUMNG_STANDARD_RATING) {
            require_once($CFG->dirroot . '/rating/lib.php');
            // If grading is 'No grading' or 'Teacher grades students'.
            if ($this->get_grading() == mod_forumng::GRADING_NONE ||
            $this->get_grading() == mod_forumng::GRADING_MANUAL) {
                // Set the aggregation method.
                if ($this->get_rating_scale() > 0) {
                    $aggregate = RATING_AGGREGATE_AVERAGE;
                } else {
                    $aggregate = RATING_AGGREGATE_COUNT;
                }
            } else {
                $aggregate = $this->get_grading();
            }
            $ratingoptions = new stdClass();
            $ratingoptions->context = $this->get_context(true);
            $ratingoptions->component = 'mod_forumng';
            $ratingoptions->ratingarea = 'post';
            $ratingoptions->items = $posts;
            $ratingoptions->aggregate = $aggregate;
            $ratingoptions->scaleid = $this->get_rating_scale();
            $ratingoptions->userid = $USER->id;
            $ratingoptions->assesstimestart = $this->get_ratingfrom();
            $ratingoptions->assesstimefinish = $this->get_ratinguntil();

            $rm = new rating_manager();
            $posts = $rm->get_ratings($ratingoptions);
        }
        $discussions = array();
        foreach ($posts as $fields) {
            $discussionfields = mod_forumng_utils::extract_subobject($fields, 'fd_');
            if (!isset($discussions[$discussionfields->id])) {
                $discussions[$discussionfields->id] = new mod_forumng_discussion($this, $discussionfields, false, -1);
            }
            $result[$fields->id] = new mod_forumng_post($discussions[$discussionfields->id], $fields);
        }
        return $result;
    }

    /**
     * Gets all user post counts.
     * @param int $groupid Group ID or NO_GROUPS/ALL_GROUPS
     * @param bool $ignoreanon Ignore posts marked as anonymous
     * @return array An associative array of $userid => (info object)
     *   where info object has ->discussions and ->replies values
     */
    public function get_all_user_post_counts($groupid, $ignoreanon = false, $start = null, $end = null) {
        global $DB;

        if ($groupid != self::NO_GROUPS && $groupid != self::ALL_GROUPS) {
            $groupwhere = 'AND (fd.groupid = ? OR fd.groupid IS NULL)';
            $groupparams = array($groupid);
        } else {
            $groupwhere = '';
            $groupparams = array();
        }

        $anonparams = array();
        $anonwhere = '';
        if ($ignoreanon) {
            $anonwhere = 'AND fp.asmoderator != ?';
            $anonparams[] = self::ASMODERATOR_ANON;
        }

        $timewhere = '';
        $timeparams = array();

        if (!empty($start)) {
            $timewhere = 'AND fp.created >= ?';
            $timeparams[] = $start;
        }

        if (!empty($end)) {
            $timewhere .= 'AND fp.created <= ?';
            $timeparams[] = $end;
        }

        $results = array();
        foreach (array('discussions', 'replies') as $task) {
            // This join will either extract posts which start a discussion, or those which don't
            $joinon = $task === 'discussions' ? 'fd.postid = fp.id'
                    : 'fd.id = fp.discussionid AND fd.postid <> fp.id';

            // Get counts of items in that category
            $rs = $DB->get_recordset_sql("
    SELECT
        fp.userid, COUNT(1) AS numposts
    FROM
        {forumng_posts} fp
        INNER JOIN {forumng_discussions} fd ON $joinon
    WHERE
        fd.forumngid = ?
        $groupwhere
        $anonwhere
        $timewhere
        AND fd.deleted = 0
        AND fp.deleted = 0
        AND fp.oldversion = 0
    GROUP BY
        fp.userid", array_merge(array($this->get_id()), $groupparams, $anonparams, $timeparams));

            // Store in results
            foreach ($rs as $rec) {
                if (!isset($results[$rec->userid])) {
                    $results[$rec->userid] = (object)array('discussions'=>0, 'replies'=>0);
                }
                $results[$rec->userid]->{$task} = $rec->numposts;
            }
            $rs->close();
        }

        return $results;
    }

    /**
     * Returns true if OK to archive the old discussions to the target forum.
     * @param object $modinfo Moodle get_fast_modinfo data
     * @param string $message Throwing warning if the forum cannot be archived
     * @return bool True if settings are OK
     */
    public function can_archive_forum($modinfo, &$message) {
        global $CFG;
        $forumngid = $this->get_id();
        $groupmode = $this->get_group_mode();
        $groupingid = $this->get_grouping();
        $targetforumngid = $this->forumfields->removeto;
        if (isset($modinfo->instances['forumng'][$targetforumngid])) {
            $targetcm = $modinfo->instances['forumng'][$targetforumngid];
            $targetgroupmode = groups_get_activity_groupmode($targetcm, $this->get_course());
            $targetgroupingid = $targetcm->groupingid;
            if (!$targetgroupmode) {
                return true;
            } else {
                if (($groupingid == $targetgroupingid) && $groupmode) {
                    return true;
                }
                $message = get_string('archive_errorgrouping', 'forumng');
                return false;
            }
        } else {
            $message = get_string('archive_errortargetforum', 'forumng');
            return false;
        }
    }

    // Shared/clone forums
    /*////////////////////*/

    /**
     * Redirects to the original forum that this is a clone of, setting
     * session to indicate that user came from this forum. Does not return.
     * @throws mod_forumng_exception If this is not a clone forum I
     */
    public function redirect_to_original() {
        global $CFG, $SESSION;
        $cmid = $this->forumfields->originalcmid;
        if (!$cmid) {
            throw new coding_exception('This forum is not a clone');
        }
        if (!isset($SESSION->forumng_sharedforumcm)) {
            $SESSION->forumng_sharedforumcm = array();
        }
        $SESSION->forumng_sharedforumcm[$cmid] = $this->get_course_module();
        redirect($CFG->wwwroot . '/mod/forumng/view.php?id=' . $cmid .
                '&clone=' . $this->get_course_module()->id);
    }

    /**
     * Gets unread data from original forum.
     * @param int $unread UNREAD_xx constant
     * @param int $userid User id.
     * @throws mod_forumng_exception If this is not a clone forum
     */
    public function init_unread_from_original($unread, $userid = 0) {
        global $DB;
        $cmid = $this->forumfields->originalcmid;
        if (!$cmid) {
            throw new coding_exception('This forum is not a clone');
        }
        $viewhiddenforums = array();
        if (has_capability('mod/forumng:viewallposts', context_module::instance($cmid), $userid)) {
            $viewhiddenforums[] = $DB->get_field(
                    'course_modules', 'instance', array('id' => $cmid));
        }
        $rows = self::query_forums(array($cmid), null, $userid, $unread,
                array(), array(), $viewhiddenforums);
        if (count($rows) != 1) {
            throw new coding_exception('Unexpected data extracting base forum');
        }
        $row = reset($rows);
        switch ($unread) {
            case self::UNREAD_BINARY:
                if (isset($row->f_hasunreaddiscussions)) {
                    // Set binary to 0/1 even if database returns 't'/'f'.
                    if ($row->f_hasunreaddiscussions === 'f') {
                        $this->forumfields->hasunreaddiscussions = 0;
                    } else if ($row->f_hasunreaddiscussions) {
                        $this->forumfields->hasunreaddiscussions = 1;
                    } else {
                        $this->forumfields->hasunreaddiscussions = 0;
                    }
                } else {
                    $this->forumfields->hasunreaddiscussions = $row->f_numunreaddiscussions ? 1 : 0;
                }
                break;
            case self::UNREAD_DISCUSSIONS:
                $this->forumfields->numunreaddiscussions = $row->f_numunreaddiscussions;
                break;
        }
    }

    /**
     * Obtains the course-module for a shared forum, or false if there isn't
     * one, based on the idnumber.
     * @param string $idnumber ID number (text, no slashes)
     * @return object Course-module object (raw from database) or false if not
     *   found / not a forum / etc
     */
    public static function get_shared_cm_from_idnumber($idnumber) {
        global $DB;
        return $DB->get_record_sql("
SELECT
    cm.*
FROM
    {course_modules} cm
    INNER JOIN {modules} m ON m.id = cm.module
    INNER JOIN {forumng} f ON f.id = cm.instance
WHERE
    cm.idnumber = ?
    AND m.name = 'forumng'
    AND f.shared = 1", array($idnumber));
    }

    /**
     * Update the forumng_subscription table to incorporate the group subscription feature.
     * @param bool $moodleupdate If this is true, the function is running as part of the
     *   moodle upgrade.php for Sep 2010 release. In this case, the database queries must
     *   not be changed and other code must work the same way (avoid calls to functions
     *   except Moodle standard ones)
     */
    public function group_subscription_update($moodleupdate=false, $cmid=0) {
        global $DB;
        $transaction = $DB->start_delegated_transaction();

        if ($cmid) {
            // Only update one forum.
            $optionalquery = "AND cm.id = ?";
            $optionalqueryparams = array($cmid);
        } else {
            $optionalquery = '';
            $optionalqueryparams = array();
        }
        // Query get the distinct forums
        $sqlcount = "
SELECT
    COUNT(DISTINCT cm.id) AS totalnumberforum
FROM
    {forumng_subscriptions} fs
    INNER JOIN {course_modules} cm on fs.forumngid = cm.instance
    INNER JOIN {modules} m on cm.module = m.id
    INNER JOIN {course} c on c.id = cm.course
WHERE
    discussionid IS NULL AND m.name = 'forumng' $optionalquery
    AND (CASE WHEN c.groupmodeforce=1 THEN c.groupmode ELSE cm.groupmode END ) = 1";

        // Query lists all subscriptions to forums that have separate groups.
        $sqlsub = "
SELECT
    cm.id AS cmid, fs.id AS subid, fs.userid, fs.forumngid, c.id AS courseid, cm.groupingid
FROM
    {forumng_subscriptions} fs
    INNER JOIN {course_modules} cm on fs.forumngid = cm.instance
    INNER JOIN {modules} m on cm.module = m.id
    INNER JOIN {course} c on c.id = cm.course
WHERE
    discussionid IS NULL and m.name = 'forumng' $optionalquery
    AND (CASE WHEN c.groupmodeforce = 1 THEN c.groupmode ELSE cm.groupmode END ) = 1
ORDER BY cm.id, fs.id";

        // Query lists all groups that the user belongs to from the above query.
        $sqlgroup = "
SELECT
    subs.subid, g.id AS groupid
FROM
    ($sqlsub) subs
    INNER JOIN {groups_members} gm ON gm.userid = subs.userid
    INNER JOIN {groups} g ON gm.groupid = g.id AND g.courseid = subs.courseid
    LEFT JOIN {groupings_groups} gg ON gg.groupid = g.id AND subs.groupingid = gg.groupingid
WHERE
    (subs.groupingid = 0 or gg.id IS NOT NULL)
ORDER BY
    subs.cmid, subs.subid";
        $rs = $DB->get_recordset_sql($sqlgroup, $optionalqueryparams);
        $results = array();
        foreach ($rs as $rec) {
            if (!array_key_exists($rec->subid, $results)) {
                $results[$rec->subid] = array();
            }
            $results[$rec->subid][] = $rec->groupid;
        }
        $rs->close();
        $rs = $DB->get_recordset_sql($sqlsub, $optionalqueryparams);
        $lastcmid = 0;
        $forumcount = 1;
        $totalforumcount = 0;
        $totalforumcount = $DB->count_records_sql($sqlcount, $optionalqueryparams);

        foreach ($rs as $rec) {
            if ($lastcmid != $rec->cmid) {
                if ($moodleupdate) {
                    print "Updating the subscriptions $forumcount/$totalforumcount
                            (current cmid:$rec->cmid) <br />";
                }
                $context = context_module::instance($rec->cmid);
                $aagusers = get_users_by_capability($context,
                    'moodle/site:accessallgroups', 'u.id');
                $aagusers = $aagusers ? $aagusers : array();
                $lastcmid = $rec->cmid;
                $forumcount++;
            }
            if (!array_key_exists($rec->userid, $aagusers)) {
                // Delete the whole forum subscription.
                $DB->delete_records('forumng_subscriptions', array('id' => $rec->subid));
                // Check if the subid exists in the results array.
                if (array_key_exists($rec->subid, $results)) {
                    foreach ($results[$rec->subid] as $groupid) {
                        $subrecord = new StdClass;
                        $subrecord->userid = $rec->userid;
                        $subrecord->forumngid = $rec->forumngid;
                        $subrecord->subscribed = 1;
                        $subrecord->groupid = $groupid;
                        $DB->insert_record('forumng_subscriptions', $subrecord);
                    }
                }
            }
        }
        $transaction->allow_commit();
    }

    /*
    * Call to check if search plugin exists.  If so, includes
    * the library suppport, otherwise return false.
    *
    * @return bool True if OU search extension is installed.
    */
    public function oualerts_enabled() {
        global $CFG;

        if (file_exists($CFG->dirroot.'/report/oualerts/locallib.php')) {
            @include_once($CFG->dirroot.'/report/oualerts/locallib.php');
            return oualerts_enabled();
        }
        return false;
    }

    /**
     * Call to check that system and forumng have tags enabled.
     *
     * @return bool True if system use tags set and forum tags field is set.
     */
    public function get_tags_enabled() {
        global $CFG;

        if ($CFG->usetags && core_tag_tag::is_enabled('mod_forumng', 'forumng')) {
            return $this->forumfields->enabletags;
        } else {
            return false;
        }
        return false;
    }

    /**
     * Call to check that user has the capability to tag discussions
     * and tagging has been set up for system and forumng.
     *
     * @return bool if user can tag discussions.
     */
    public function can_tag_discussion() {

        if ($this->get_tags_enabled()) {
            return has_capability('mod/forumng:addtag', $this->get_context());
        } else {
            return false;
        }
        return false;
    }

    /**
     * Call to get tags used within a forum.
     *
     * @param int $groupid
     * @param Bool $showemptyset show any set tags for this forum
     * @return boolean|multitype:
     */
    public function get_tags_used($groupid = self::ALL_GROUPS, $showemptyset = false) {
        global $DB, $USER, $CFG;
        require_once($CFG->dirroot . '/tag/lib.php');

        if (!$this->get_tags_enabled()) {
            return array();
        } else {
            // Build list of SQL conditions.
            /*/////////////////////////////*/

            // Correct forum.
            $conditionparams = array();
            $conditions = "fd.forumngid = ?";
            $conditionparams[] = $this->forumfields->id;

            // Correct tags.
            $conditions .= " AND ti.component = 'mod_forumng'";
            $conditions .= " AND ti.itemtype = 'forumng_discussions'";
            $conditions .= " AND ti.contextid = ?";
            $conditionparams[] = $this->get_context()->id;

            // Group restriction.
            if ($groupid) {
                $conditions .= " AND (fd.groupid = ? OR fd.groupid IS NULL)";
                $conditionparams[] = $groupid;
            }

            // View hidden posts.
            if (!$this->can_view_hidden()) {
                $now = time();
                $conditions .= " AND fd.deleted = 0";
                $conditions .= " AND (fd.timestart = 0 OR fd.timestart <= ?)" .
                        " AND (fd.timeend = 0 OR fd.timeend > ?)";
                $conditionparams[] = $now;
                $conditionparams[] = $now;
            }

            // Forumng type read restrictions.
            $typejoin = '';
            if ($this->get_type()->has_unread_restriction()) {
                list($restrictionsql, $restrictionparams) =
                    $this->get_type()->get_unread_restriction_sql($this, $USER->id);
            } else {
                $restrictionsql = false;
            }
            if ($restrictionsql) {
                $typejoin = "
                    INNER JOIN {forumng_posts} fpfirst ON fpfirst.id = fd.postid
                    INNER JOIN {forumng_posts} fplast ON fplast.id = fd.lastpostid
                    INNER JOIN {forumng} f ON f.id = fd.forumngid
                    INNER JOIN {course} c ON c.id = f.course
                    INNER JOIN {course_modules} cm ON cm.instance = f.id AND cm.course = f.course
                    INNER JOIN {modules} m ON m.id = cm.module";
                        $conditions .= " AND m.name = 'forumng' AND $restrictionsql";
                        $conditionparams = array_merge($conditionparams, $restrictionparams);
            }

            $rs = $DB->get_records_sql("
                    SELECT t.*, count(t.id) AS count
                      FROM {tag} t
                INNER JOIN {tag_instance} ti ON t.id = ti.tagid
                INNER JOIN {forumng_discussions} fd ON fd.id = ti.itemid
                           $typejoin
                     WHERE $conditions
                  GROUP BY t.name, t.id
                  ORDER BY t.name", $conditionparams);

            $settags = self::get_set_tags($this->forumfields->id, $groupid);

            foreach ($rs as $tag) {
                $tag->displayname = strtolower(core_tag_tag::make_display_name($tag));
                $tag->rawname = strtolower($tag->rawname);
                if (array_key_exists($tag->id, $settags)) {
                    $tag->label = get_string('settag_label', 'forumng');
                }
            }

            if ($showemptyset) {
                $needsort = false;
                // Need to check to see whether any 'set' tags are in tag result set.
                foreach ($settags as $key => $value) {
                    if (!array_key_exists($key, $rs)) {
                        // Create a standard class object.
                        $obj = new stdClass();
                        $obj->id = $key;
                        $obj->label = get_string('settag_label', 'forumng');
                        $obj->name = strtolower($value);
                        $obj->rawname = $value;
                        $obj->tagtype = 'default';
                        $obj->count = 0;
                        $obj->displayname = $value;
                        $rs[$key] = $obj;
                        $needsort = true;
                    }
                }

                // Sort on displayname.
                if ($needsort) {
                    usort($rs, function($a, $b){
                        return strcmp($a->displayname, $b->displayname);
                    });
                }
            }

            return $rs;
        }
    }

    /**
     * Call to get forum wide 'set' tags.
     *
     * @param int $forumid used to get context id
     * @param int $groupid used to specify group that we require set tags for
     * @param boolean $grouponly used to specify whether we are returning set tags for a single specified group
     * @return array set tags for that forum
     */
    public static function get_set_tags($forumid, $groupid = self::ALL_GROUPS, $grouponly = false) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/tag/lib.php');

        $forum = self::get_from_id($forumid, self::CLONE_DIRECT);
        $forumid = $forum->get_id();
        $context = $forum->get_context(true);

        if (($groupid == self::ALL_GROUPS) || ($groupid == self::NO_GROUPS)) {
            $groupid = 0;
        }

        $conditionparams = array();
        $conditions = '';

        if (($grouponly) && ($groupid)) {
            $conditions .= " (ti.component = ? AND ti.itemtype = ? AND ti.contextid = ? AND ti.itemid = ?)";
            $conditionparams[] = 'mod_forumng';
            $conditionparams[] = 'groups';
            $conditionparams[] = $context->id;
            $conditionparams[] = $groupid;
        } else {
            // Check to see whether tags have been set at forumng level.
            $conditions = "(ti.component = ? AND ti.itemtype = ? AND ti.contextid = ? AND ti.itemid = ?)";
            $conditionparams[] = 'mod_forumng';
            $conditionparams[] = 'forumng';
            $conditionparams[] = $context->id;
            $conditionparams[] = $forumid;
            if ($groupid) {
                $conditions .= " OR (ti.component = ? AND ti.itemtype = ? AND ti.contextid = ? AND ti.itemid = ?)";
                $conditionparams[] = 'mod_forumng';
                $conditionparams[] = 'groups';
                $conditionparams[] = $context->id;
                $conditionparams[] = $groupid;
            }
        }

        $rs = $DB->get_records_sql("
            SELECT DISTINCT t.*
              FROM {tag} t
        INNER JOIN {tag_instance} ti
                ON t.id = ti.tagid
             WHERE $conditions
          ORDER BY t.name", $conditionparams);

        // Create tags associative array with tagid as key and tag name as value.
        $tags = array();
        foreach ($rs as $tag) {
            $tags[$tag->id] = strtolower(core_tag_tag::make_display_name($tag, false));
        }

        return $tags;

    }

    /**
     * Sets 'Set' tags for groups for the forum.
     * Necessary to use this rather than core tag lib as that does not deal with context
     * and as group item ids can be the same that is an issue
     * Also can only have 1 unique group/tag/user record
     * @param int $forumid forum table id
     * @param int $groupid groups table id
     * @param array $tags array of tag rawnames e.g. Fish, frog
     */
    public static function set_group_tags($forumid, $groupid, $tags) {
        global $DB, $CFG, $USER;

        $forum = self::get_from_id($forumid, self::CLONE_DIRECT);
        $context = $forum->get_context(true);

        $tagcollid = core_tag_area::get_collection('mod_forumng', 'groups');

        $transaction = $DB->start_delegated_transaction();
        // Get existing tags used.
        $settags = array();
        $taginstances = $DB->get_records_sql("
                SELECT DISTINCT t.*, ti.id as instanceid
                  FROM {tag} t
            INNER JOIN {tag_instance} ti
               ON t.id = ti.tagid
                 WHERE ti.component = ? AND ti.itemtype = ? AND ti.contextid = ? AND ti.itemid = ?",
                array('mod_forumng', 'groups', $context->id, $groupid));
        // Delete instances any not in new tags (note tag records not deleted as cleaned in cron).
        $tistodelete = array();
        foreach ($taginstances as $tinstance) {
            if (!in_array($tinstance->rawname, $tags)) {
                $tistodelete[] = $tinstance->instanceid;
            } else {
                // Store existing tag instance used.
                $settags[$tinstance->instanceid] = $tinstance->rawname;
            }
        }
        if ($tistodelete) {
            list($delsql, $delparams) = $DB->get_in_or_equal($tistodelete);
            $DB->delete_records_select('tag_instance', "id $delsql", $delparams);
        }
        // Add/get new tag records.
        $existingtags = core_tag_tag::get_by_name_bulk($tagcollid, $tags, '*');
        // Normalize tags passed so can match to existing tags array.
        $normaltags = core_tag_tag::normalize($tags);
        // Add tag instances (where needed).
        $ordering = 0;
        foreach ($normaltags as $rawname => $name) {
            if (in_array($rawname, $settags)) {
                // Pre-existing instance, skip.
                $ordering++;
                continue;
            }
            if (!array_key_exists($name, $existingtags) || empty($existingtags[$name])) {
                // Need to add tag, use core method.
                core_tag_tag::add_item_tag('mod_forumng', 'groups', $groupid, $context, $rawname);
            } else {
                // Need to add tag instance only, cannot use core for this.
                $tagid = $existingtags[$name]->id;
                $tag_instance_object = new stdClass();
                $tag_instance_object->tagid = $tagid;
                $tag_instance_object->component = 'mod_forumng';
                $tag_instance_object->itemid = $groupid;
                $tag_instance_object->itemtype = 'groups';
                $tag_instance_object->contextid = $context->id;
                $tag_instance_object->ordering = $ordering;
                $tag_instance_object->timecreated = time();
                $tag_instance_object->timemodified = $tag_instance_object->timecreated;
                $tag_instance_object->tiuserid = self::get_group_taginstance_userid($groupid, $tagid);

                $DB->insert_record('tag_instance', $tag_instance_object);
            }
            $ordering++;
        }
        $DB->commit_delegated_transaction($transaction);
    }

    /**
     * Check user can save a new group tag instance as these have a unique key
     * If not, will use - admin user (0), guest user (1), other admin user (2+)
     * @param int $groupid
     * @param int $tagid
     * @param int $start default -1 for current user.
     * @return int user id
     * @throws moodle_exception If out of users
     */
    private static function get_group_taginstance_userid($groupid, $tagid, $start = -1) {
        global $DB, $USER;
        $userid = $USER->id;
        $nexttry = 0;
        if ($start == 0) {
            $userid = get_admin()->id;
            $nexttry = 1;
        } else if ($start == 1) {
            $userid = guest_user()->id;
            $nexttry = 2;
        } else if ($start >= 2) {
            $admins = get_admins();
            if (count($admins) < $start) {
                throw new moodle_exception('Cannot add tag instance - duplicate value, max possible reached.');
            }
            $keys = array_keys($admins);// Get keys (user id's).
            $userid = $keys[($start - 1)];
            $nexttry = $start;
            $nexttry++;
        }

        if (!$DB->record_exists('tag_instance', array('itemtype' => 'groups', 'itemid' => $groupid,
                'tiuserid' => $userid, 'tagid' => $tagid))) {
            // No existing record, safe to proceed.
            return $userid;
        }
        // Try and find another userid.
        return self::get_group_taginstance_userid($groupid, $tagid, $nexttry);
    }

}

/**
 * This is a sketchy stub for $PAGE to stop it doing the init call, while allowing it to do
 * require_js. Used in print_js.
 *
 * This approach is fragile and totally evil, but it is better to be fragile and evil than to
 * duplicate the code (i.e. if this breaks there will most likely be a PHP warning, whereas
 * duplicated code might go wrong silently as the PHP and JS become out of sync).
 */
class mod_forumng_filemanager_evilhack {
    /**
     * @var moodle_page
     */
    public $realpage;
    public $requires;
    public $extraarguments, $extraarguments2;
    public $context, $course, $theme;

    public function __construct() {
        global $PAGE;
        $this->realpage = $PAGE;
        $this->context = $PAGE->context;
        $this->course = $PAGE->course;
        $this->theme = $PAGE->theme;
        $PAGE = $this;
        $this->requires = new mod_forumng_filemanager_evilhack_requires($this);
    }

    public function return_to_goodness() {
        global $PAGE;
        $PAGE = $this->realpage;
    }
}

/**
 * This is a sketchy stub for ->requires. Used in print_js.
 */
class mod_forumng_filemanager_evilhack_requires {
    private $evilhack;

    public function __construct($evilhack) {
        $this->evilhack = $evilhack;
    }

    public function js_init_call($function, array $extraarguments = null,
            $ondomready = false, array $module = null) {
        if (!$module) {
            if (!preg_match('~^M\.editor_tinymce\.~', $function)) {
                throw new coding_exception('This needs changing, unsupported function');
            }
            $module = array('name'=>'editor_tinymce', 'fullpath'=>'/lib/editor/tinymce/module.js',
                    'requires'=>array());
        }
        $this->js_module($module);
        if ($this->evilhack->extraarguments) {
            // Allows to store arguments for up to 2 function calls
            $this->evilhack->extraarguments2 = $extraarguments;
        } else {
            $this->evilhack->extraarguments = $extraarguments;
        }
    }

    public function find_module($component) {
        return $this->evilhack->realpage->requires->find_module($component);
    }

    public function get_loaded_modules() {
        return $this->evilhack->realpage->requires->get_loaded_modules();
    }

    public function js_module($module) {
        $this->evilhack->realpage->requires->js_module($module);
    }

    public function js($url, $inhead=false) {
        $this->evilhack->realpage->requires->js($url, $inhead);
    }
}

/**
 * Override of context just so that we can access the protected
 * construct function (wtf).
 */
abstract class mod_forumng_context_access extends context {
    /**
     * Calls parent create_instance_from_record function.
     * @param object $record DB record
     * @return context Context object
     */
    public static function create_instance_from_record_public($record) {
        return self::create_instance_from_record($record);
    }
}
