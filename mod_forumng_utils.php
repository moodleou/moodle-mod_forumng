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
 * Class that holds utility functions used by forum.
 * @package mod
 * @subpackage forumng
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_forumng_utils {

    // DB wrapper function
    /*////////////////////*/

    /**
     * Similar to core $DB->get_in_or_equal, but permits empty array and requires the value to
     * be passed to this function. The returned SQL fragment is a complete expression that returns
     * boolean TRUE or FALSE.
     *
     * As an example, if you want to find out if the SQL expression
     * f.id is contained within the array of ids $items:
     *
     * list($sql, $params) = get_in_array_sql('f.id', $items);
     * $DB->get_records_sql("SELECT * FROM whatever WHERE $sql", $params);
     * @param string $value SQL expression for value to be compared with array items
     * @param array $items Array of IDs (or similar) that the value will be compared with
     * @param int $type bound param type SQL_PARAMS_QM or SQL_PARAMS_NAMED
     * @param string $start named param placeholder start
     * @return array - $sql and $params
     */
    public static function get_in_array_sql($value, $items,
            $type=SQL_PARAMS_QM, $start='param0000') {
        global $DB;
        if (is_array($items) && empty($items)) {
            // Empty array returns false. The keyword FALSE is not supported on all databases
            // but 1=0 probably should be.
            return array('(1=0)', array());
        }
        list ($sql, $params) = $DB->get_in_or_equal($items, $type, $start);
        return array($value . ' ' . $sql, $params);
    }

    // Exception handling
    /*///////////////////*/

    /**
     * Adds exception information to Moodle log.
     * @param Exception $e Exception
     */
    public static function log_exception(Exception $e) {
        global $DB;

        $info = '';

        // Default courseid (may override later on)
        global $COURSE;
        $courseid = isset($COURSE->id) ? $COURSE->id  : 0;
        $cmid = 0;

        // These are numeric params so OK to include; I made this list from
        // all the params in editpost.php where this is initially implemented
        foreach (array('clone', 'id', 'd', 'p', 'ajax', 'draft', 'group', 'replyto',
                'lock') as $param) {
            if ($val = optional_param($param, 0, PARAM_INT)) {
                $info .= $param . '=' . $val . ',';

                // Guess courseid from param
                if (!$cmid) {
                    if ($param === 'clone' || $param === 'id') {
                        $cmid = $val;
                    } else if ($param === 'd') {
                        $cmid = $DB->get_field_sql(
                                'SELECT cm.id FROM {forumng_discussions} fd ' .
                                'JOIN {forumng} f ON f.id = fd.forumngid ' .
                                'JOIN {modules} m ON m.name = ? ' .
                                'JOIN {course_modules} cm ON cm.instance = f.id ' .
                                    'AND cm.module = m.id ' .
                                'WHERE fd.id = ?',
                                array('forumng', $val), IGNORE_MISSING);
                    } else if ($param === 'p' || $param === 'replyto') {
                        $cmid = $DB->get_field_sql(
                                'SELECT cm.id FROM {forumng_posts} fp ' .
                                'JOIN {forumng_discussions} fd ON fd.id = fp.discussionid ' .
                                'JOIN {forumng} f ON f.id = fd.forumngid ' .
                                'JOIN {modules} m ON m.name = ? ' .
                                'JOIN {course_modules} cm ON cm.instance = f.id ' .
                                    'AND cm.module = m.id ' .
                                'WHERE fp.id = ?',
                                array('forumng', $val), IGNORE_MISSING);
                    }
                }
            }
        }

        if ($cmid) {
            $courseid = $DB->get_field('course_modules', 'course', array('id' => $cmid),
                IGNORE_MISSING);
        }

        // Remove final ,
        $info = preg_replace('~\,$~', '', $info);

        // Trace begins with | sign
        $info .= '|';
        global $CFG;

        // Annoyingly the trace array does not include the 'first' location
        $firsttrace = array('file' => $e->getFile(), 'line' => $e->getLine());
        $trace = array_merge(array($firsttrace), $e->getTrace());

        $file = '';
        foreach ($trace as $line) {
            // To reduce the number of required characters, remove the location
            // prefix and .php, and remove mod/forumng/ too.
            $file = str_replace($CFG->dirroot . '/', '', $line['file']);
            $file = str_replace('mod/forumng/', '', $file);
            $file = str_replace('.php', '', $file);
            $info .= $file . ':' . $line['line'] . ',';
        }

        // Remove final ,
        $info = preg_replace('~\,$~', '', $info);

        // Finally let's add the exception message
        $info .= '|' . $e->getMessage();

        // Cut off (using textlib in case message contains UTF-8)
        if (core_text::strlen($info) > 255) {
            // Use first part + ellipsis
            $info = core_text::substr($info, 0, 254) . html_entity_decode('&#x2026;', ENT_QUOTES, 'UTF-8');
        }

        // Add entry to Moodle log (using root file in action)
        $cmid = $cmid ? $cmid : 0;
        $courseid = $courseid ? $courseid : 0;
        $params = array(
                'context' => context_module::instance($cmid),
                'other' => array('info' => $info, 'url' => $_SERVER['REQUEST_URI'])
        );
        switch ($file) {
            case 'editpost':
                $event = \mod_forumng\event\post_updated_failed::create($params);
                break;
        }
        $event->trigger();
    }

    // Renderer
    /*/////////*/

    /**
     * Obtains the forum renderer. This is necessary because the forum renderer has functions that
     * are not included in the default $OUTPUT renderer.
     * @return mod_forumng_renderer Singleton renderer
     */
    public static function get_renderer() {
        // It probably doesn't take very long to construct one, but let's cache it anyhow
        static $out;
        if (!$out) {
            global $PAGE;
            $out = $PAGE->get_renderer('mod_forumng');
        }
        return $out;
    }

    // Exception-safe IO
    /*//////////////////*/

    // TODO Are these still needed now attachments were dumped to core?

    /**
     * Deletes a file.
     * @param string $file File to delete
     * @throws mod_forumng_exception If the delete fails
     */
    public static function unlink($file) {
        if (!unlink($file)) {
            require_once(dirname(__FILE__) . '/mod_forumng_file_exception.php');
            throw new mod_forumng_file_exception("Failed to delete $file");
        }
    }

    /**
     * Renames a file, without needing to check the return value.
     * @param $oldfile Old name
     * @param $newfile New name
     * @throws mod_forumng_exception If the rename fails
     */
    public static function rename($oldfile, $newfile) {
        if (!rename($oldfile, $newfile)) {
            require_once(dirname(__FILE__) . '/mod_forumng_file_exception.php');
            throw new mod_forumng_file_exception("Failed to rename $oldfile to $newfile");
        }
    }

    /**
     * Deletes a folder, without needing to check the return value. (Note:
     * This is not a recursive delete. You need to delete files first.)
     * @param string $folder Path of folder
     * @throws mod_forumng_exception If the delete fails
     */
    public static function rmdir($folder) {
        if (!rmdir($folder)) {
            require_once(dirname(__FILE__) . '/mod_forumng_file_exception.php');
            throw new mod_forumng_file_exception("Failed to delete folder $folder");
        }
    }

    /**
     * Creates a folder, without needing to check the return value. (Note:
     * This is not a recursive create. You need to create the parent first.)
     * @param string $folder Path of folder
     * @throws mod_forumng_exception If the create fails
     */
    public static function mkdir($folder) {
        if (!mkdir($folder)) {
            require_once(dirname(__FILE__) . '/mod_forumng_file_exception.php');
            throw new mod_forumng_file_exception("Failed to make folder $folder");
        }
    }

    /**
     * Copies a file, without needing to check the return value.
     * @param $oldfile Old name
     * @param $newfile New name
     * @throws mod_forumng_exception If the copy fails
     */
    public static function copy($oldfile, $newfile) {
        if (!copy($oldfile, $newfile)) {
            require_once(dirname(__FILE__) . '/mod_forumng_file_exception.php');
            throw new mod_forumng_file_exception("Failed to copy $oldfile to $newfile");
        }
    }

    /**
     * Opens a directory handle. The directory must exist or this function
     * will throw an exception.
     * @param string $folder Folder to open
     * @return int Handle
     * @throws mod_forumng_exception If the open fails
     */
    public static function opendir($folder) {
        $handle = @opendir($folder);
        if (!$handle) {
            require_once(dirname(__FILE__) . '/mod_forumng_file_exception.php');
            throw new mod_forumng_file_exception(
              "Failed to open folder: $folder");
        }
        return $handle;
    }

    // SQL field selections
    /*/////////////////////*/

    /**
     * Makes a list of fields with alias in front.
     * @param $fields Field
     * @param $alias Table alias (also used as field prefix) - leave blank for
     *   none
     * @return SQL SELECT list
     */
    private static function select_fields($fields, $alias = '') {
        $result = '';
        if ($alias === '') {
            $fieldprefix = '';
            $nameprefix = '';
        } else {
            $fieldprefix = $alias . '.';
            $nameprefix = $alias . '_';
        }
        foreach ($fields as $field) {
            if ($result) {
                $result .= ',';
            }
            $result .= $fieldprefix . $field . ' as ' . $nameprefix . $field;
        }
        return $result;
    }

    /**
     * @param bool $includemailfields If true, includes email fields (loads)
     * @return array List of all field names in mdl_user to include
     */
    public static function get_username_fields($includemailfields=false) {
        // Get core user name fields, for use with fullname etc.
        $namefields = get_all_user_name_fields();
        return $includemailfields
            ?  array('id', 'username', 'picture', 'url',
                'imagealt', 'email', 'maildisplay', 'mailformat', 'maildigest',
                'emailstop', 'deleted', 'auth', 'timezone', 'lang', 'idnumber') + $namefields
            :   array('id', 'username', 'picture', 'url',
                'imagealt', 'idnumber', 'email') + $namefields;
    }

    /**
     * Used when selecting users inside other SQL statements.
     * Returns list of fields suitable to go within the SQL SELECT block. For
     * example, if the alias is 'fu', one field will be fu.username AS fu_username.
     * Note, does not end in a comma.
     * @param string $alias Alias of table to extract
     * @param bool $includemailfields If true, includes additional fields
     *   needed for sending emails
     * @return string SQL select fields (no comma at start or end)
     */
    public static function select_username_fields($alias, $includemailfields = false) {
        return self::select_fields(
            self::get_username_fields($includemailfields), $alias);
    }

    public static function select_course_module_fields($alias) {
        $fields = array('id', 'course', 'module', 'instance', 'section',
            'added', 'score', 'indent', 'visible', 'visibleold', 'groupmode',
            'groupingid', 'idnumber', 'completion',
              'completiongradeitemnumber', 'completionview',
              'completionexpected');

        return self::select_fields($fields, $alias);
    }

    public static function select_course_fields($alias) {
        return self::select_fields(array('id', 'shortname', 'fullname', 'format'),
            $alias);
    }

    public static function select_context_fields($alias) {
        return self::select_fields(array('id', 'contextlevel', 'instanceid',
            'path', 'depth'), $alias);
    }

    /**
     * Used when selecting forums inside other SQL statements.
     * @param string $alias Alias of table to extract
     * @return string SQL select fields (no comma at start or end)
     */
    public static function select_mod_forumng_fields($alias) {
        return self::select_fields(array('id', 'course', 'name', 'type',
            'intro', 'introformat', 'introduction', 'introductionformat',
            'ratingscale', 'ratingfrom', 'ratinguntil', 'grading',
            'attachmentmaxbytes', 'reportingemail', 'subscription', 'feedtype', 'feeditems',
            'maxpostsperiod', 'maxpostsblock', 'postingfrom', 'postinguntil',
            'typedata', 'magicnumber', 'originalcmid', 'shared', 'canpostanon', 'enabletags', 'enableratings'), $alias);
    }

    /**
     * Used when selecting discussions inside other SQL statements.
     * @param string $alias Alias of table to extract
     * @return string SQL select fields (no comma at start or end)
     */
    public static function select_discussion_fields($alias) {
        return self::select_fields(array('id', 'forumngid', 'groupid', 'postid',
            'lastpostid', 'timestart', 'timeend', 'deleted', 'locked',
            'sticky'), $alias);
    }

    /**
     * Used when selecting posts inside other SQL statements.
     * @param string $alias Alias of table to extract
     * @return string SQL select fields (no comma at start or end)
     */
    public static function select_post_fields($alias) {
        return self::select_fields(array('id', 'discussionid', 'parentpostid',
            'userid', 'created', 'modified', 'deleted', 'important', 'mailstate',
            'oldversion', 'edituserid', 'subject', 'message', 'messageformat',
            'attachments', 'asmoderator'), $alias);
    }

    // SQL generic helpers
    /*////////////////////*/

    /**
     * Safe version of explode function. Always returns an array. Ignores blank
     * elements. So the result of calling this on '/3//4/5' will be array(3, 4, 5).
     * @param string $separator Separator eg. ","
     * @param string $string String to split
     * @return array String split into parts
     */
    public static function safe_explode($separator, $string) {
        $results = explode($separator, $string);
        $answer = array();
        if ($results) {
            foreach ($results as $thing) {
                if ($thing!=='') {
                    $answer[] = $thing;
                }
            }
        }
        return $answer;
    }

    // SQL object extraction
    /*//////////////////////*/

    /**
     * Loops through all the fields of an object, removing those which begin
     * with a given prefix, and setting them as fields of a new object.
     * @param &$object object Object
     * @param $prefix string Prefix e.g. 'prefix_'
     * @return object Object containing all the prefixed fields (without prefix)
     */
    public static function extract_subobject(&$object, $prefix) {
        $result = array();
        foreach ((array)$object as $key => $value) {
            if (strpos($key, $prefix)===0) {
                $result[substr($key, strlen($prefix))] = $value;
                unset($object->{$key});
            }
        }
        return (object)$result;
    }

    /**
     * Copies fields beginning with the specified prefix from one object to
     * another, optionally changing the prefix.
     * @param $target Target object
     * @param $source Source object
     * @param $prefix Prefix for fields to copy
     * @param $newprefix New prefix (null = same prefix)
     */
    public static function copy_subobject(&$target, $source, $prefix, $newprefix=null) {
        if ($newprefix === null) {
            $newprefix = $prefix;
        }
        foreach ($source as $key => $value) {
            if (strpos($key, $prefix)===0) {
                $newkey = $newprefix . substr($key, strlen($prefix));
                $target->{$newkey} = $value;
            }
        }
    }

    // Moodle generic helpers
    /*///////////////////////*/

    /**
     * @param int $userid User ID or 0 for default
     * @return Genuine (non-zero) user ID
     */
    public static function get_real_userid($userid=0) {
        global $USER;
        $userid = $userid==0 ? $USER->id : $userid;
        if (!$userid) {
            // This can happen in cases where we are about to check whether the user is logged in.
            // In that case, let us return user 0.
            return 0;
        }
        return $userid;
    }

    /**
     * @param int $userid User ID or 0 for default
     * @return User object
     */
    public static function get_user($userid=0) {
        global $USER, $DB;
        if ($userid && (empty($USER->id) || $USER->id != $userid)) {
            $user = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);
        } else {
            $user = $USER;
        }
        return $user;
    }

    static private $scales = array();

    /**
     * Wrapper for Moodle function that caches result, so can be called
     * without worry of a performance impact.
     * @param int $gradingtype Grading type value
     * @return array Array from value=>name
     */
    public static function make_grades_menu($gradingtype) {
        if (!array_key_exists($gradingtype, self::$scales)) {
            self::$scales[$gradingtype] = make_grades_menu($gradingtype);
        }
        return self::$scales[$gradingtype];
    }

    // UI
    // //.

    /**
     * Wraps nice way to display reasonable date format in Moodle for use
     * in all forum locations.
     * @param int $date Date (seconds since epoch)
     * @return string Date as string
     */
    public static function display_date($date) {
        // Use OU custom 'nice date' function if available
        if (function_exists('specially_shrunken_date')) {
            return specially_shrunken_date($date, false, true);
        } else {
            return userdate($date,
                get_string('strftimedatetimeshort', 'langconfig'));
        }
    }

    /**
     * Obtains a list of forums on the given course which can be converted.
     * The requirements for this are that they must have a supported forum
     * type and there must not be an existing ForumNG with the same name.
     * @param object $course
     * @return array Array of id=>name of convertable forums
     */
    public static function get_convertible_forums($course) {
        global $DB;
        return $DB->get_records_sql("
SELECT cm.id, f.name
FROM
    {forum} f
    INNER JOIN {course_modules} cm ON cm.instance = f.id
      AND cm.module = (SELECT id FROM {modules} WHERE name = 'forum')
    LEFT JOIN {forumng} fng ON fng.name = f.name AND fng.course = f.course
WHERE
    cm.course = ? AND f.course = ?
    AND f.type = 'general'
    AND fng.id IS NULL", array($course->id, $course->id));
    }

    /**
     * Executes a database update in such a way that it will work in MySQL,
     * when the update uses a subquery that refers to the table being updated.
     * @param string $update Update query with the special string %'IN'% at the
     *   point where the IN clause should go, i.e. replacing 'IN (SELECT id ...)'
     * @param string $inids Query that selects a column (which must be named
     *   id), i.e. 'SELECT id ...'
     * @param array $inparams Parameters for the $inids query
     */
    public static function update_with_subquery_grrr_mysql($update, $inids, $inparams) {
        global $DB;
        if ($DB->get_dbfamily() === 'mysql') {
            // MySQL is a PoS so the update can't directly run (you can't update
            // a table based on a subquery that refers to the table). Instead,
            // we do the same thing but with a separate update using an IN clause.
            // This might theoretically run into problems if you had a really huge
            // set of forums with frequent posts (so that the IN size exceeds
            // MySQL query limit) however the limits appear to be generous enough
            // that this is unlikely.
            $ids = array();
            $rs = $DB->get_recordset_sql($inids, $inparams);
            foreach ($rs as $rec) {
                $ids[] = $rec->id;
            }
            $rs->close();
            if (count($ids) > 0) {
                list($listsql, $listparams) = $DB->get_in_or_equal($ids);
                $update = str_replace("%'IN'%", $listsql, $update);
                $DB->execute($update, $listparams);
            }
        } else {
            // With a decent database we can do the update and query in one,
            // avoiding the need to transfer an ID list around.
            $DB->execute(
                str_replace("%'IN'%", "IN ($inids)", $update), $inparams);
        }
    }

    /**
     * Adds all the admin user ids to a list of users in format id=>$user,
     * where $user has only the ->id field.
     *
     * This is needed because the doanything parameter was removed from
     * get_users_by_capability.
     * @param array $users Existing list of users
     * @return void
     */
    public static function add_admin_users(&$users) {
        global $CFG;
        // There is no doanything parameter any more, so need to manually
        // add admin users
        foreach (explode(',', $CFG->siteadmins) as $adminid) {
            $users[$adminid] = (object)array('id'=>$adminid);
        }
    }

    /**
     * Only used while debugging. Converts a query so it can easily be
     * read (copied into pgadmin, etc).
     * @param string $sql SQL
     * @param array $whereparams Parameters
     */
    public static function debug_query_for_reading($sql, $whereparams) {
        $easyread = str_replace('}', '', str_replace('{', 'mdl_', $sql));
        foreach ($whereparams as $param) {
            if (is_int($param)) {
                $easyread = preg_replace('~\?~', $param, $easyread, 1);
            } else {
                $easyread = preg_replace('~\?~', "'" . $param . "'", $easyread, 1);
            }
        }
        return $easyread;
    }
}
