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
 * Moodle API functions.
 * @package mod
 * @subpackage forumng
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function forumng_add_instance($forumng) {
    global $DB;

    // Avoid including forum libraries in large areas of Moodle code that
    // require this lib.php; only include when functions are called
    require_once(dirname(__FILE__) . '/mod_forumng.php');

    $useshared = !empty($forumng->usesharedgroup['useshared']);
    if ($useshared) {
        $idnumber = $forumng->usesharedgroup['originalcmidnumber'];
        $originalcm = $DB->get_record('course_modules', array('idnumber' => $idnumber,
                'module' => $DB->get_field('modules', 'id', array('name' => 'forumng'))), '*',
                MUST_EXIST);
        $originalforumng = $DB->get_record('forumng', array('id' => $originalcm->instance), '*',
                MUST_EXIST);

        // Create appropriate data for forumng table
        $forumng = (object)array(
            'name' => $originalforumng->name,
            'course' => $forumng->course,
            'type' => 'clone',
            'originalcmid' => $originalcm->id);
    }

    // Pick a random magic number
    $part1 = mt_rand(0, 99999999);
    $part2 = mt_rand(0, 99999999);
    while (strlen($part2)<8) {
        $part2 = '0' . $part2;
    }
    $forumng->magicnumber = $part1.$part2;

    $id = $DB->insert_record('forumng', $forumng);

    // Handle post-creation actions (but only if a new forum actually was
    // created, and not just a new reference to a shared one!)
    if (!$useshared) {
        $forum = mod_forumng::get_from_id($id, mod_forumng::CLONE_DIRECT, false);
        $forum->created($forumng->cmidnumber);
    }

    return $id;
}

function forumng_update_instance($forumng) {
    global $DB;
    require_once(dirname(__FILE__).'/mod_forumng.php');

    $forumng->id = $forumng->instance;
    $previous = $DB->get_record('forumng', array('id' => $forumng->id), '*', MUST_EXIST);
    $DB->update_record('forumng', $forumng);

    $forum = mod_forumng::get_from_id($forumng->id, mod_forumng::CLONE_DIRECT);
    $forum->updated($previous);

    return true;
}

function forumng_delete_instance($id) {
    global $DB;
    require_once(dirname(__FILE__).'/mod_forumng.php');

    $cm = get_coursemodule_from_instance('forumng', $id);
    $forum = mod_forumng::get_from_id($id, mod_forumng::CLONE_DIRECT, true, $cm);
    $forum->delete_all_data();
    if (mod_forumng::search_installed()) {
        $cm = $forum->get_course_module();
        local_ousearch_document::delete_module_instance_data($cm);
    }

    if ($forum->is_shared()) {
        // Find all the clone instances.
        $clones = $forum->get_clone_details();
        $transaction = $DB->start_delegated_transaction();
        foreach ($clones as $clone) {
            if (!forumng_delete_instance($clone->cloneforumngid)) {
                notify("Could not delete the Clone forumng (id) $clone->cloneforumngid ");
                return false;
            }
            if (!delete_course_module($clone->context->instanceid)) {
                notify("Could not delete the Clone
                        forumng (coursemoduleid) $clone->context->instanceid ");
                return false;
            }
            if (!delete_mod_from_section($clone->context->instanceid, $clone->sectionid)) {
                notify("Could not delete the sectionid $clone->sectionid from that section");
                return false;
            }
            rebuild_course_cache($clone->courseid, true);
        }
        $transaction->allow_commit();
    }
    return $DB->delete_records('forumng', array('id' => $id));
}

function forumng_cron() {
    require_once(dirname(__FILE__).'/mod_forumng_cron.php');

    try {
        mod_forumng_cron::cron();
    } catch (moodle_exception $e) {
        mtrace("A forum exception occurred and forum cron was aborted: " .
            $e->getMessage() . "\n\n" .
            $e->debuginfo . "\n\n" .
            $e->getTraceAsString()."\n\n");
    }
}


/**
 * Obtains a search document given the ousearch parameters.
 * @param object $document Object containing fields from the ousearch documents table
 * @return mixed False if object can't be found, otherwise object containing the following
 *   fields: ->content, ->title, ->url, ->activityname, ->activityurl,
 *   and optionally ->extrastrings array and ->data
 */
function forumng_ousearch_get_document($document) {
    require_once(dirname(__FILE__).'/mod_forumng.php');
    return mod_forumng_post::search_get_page($document);
}

/**
 * Update all documents for ousearch.
 * @param bool $feedback If true, prints feedback as HTML list items
 * @param int $courseid If specified, restricts to particular courseid
 */
function forumng_ousearch_update_all($feedback=false, $courseid=0) {
    require_once(dirname(__FILE__).'/mod_forumng.php');
    mod_forumng::search_update_all($feedback, $courseid);
}

/**
 * Return the correct cm for clone forum.
 * @param object $cm course module
 * @param object $course course object
 */
function forumng_ousearch_add_visible_module($cm, $course) {
    global $CFG, $FORUMNG_CLONE_MAP;
    if (empty($FORUMNG_CLONE_MAP)) {
        $FORUMNG_CLONE_MAP = array();
        require_once($CFG->dirroot . '/mod/forumng/mod_forumng.php');
        $forums = mod_forumng::get_course_forums($course, 0,
                mod_forumng::UNREAD_NONE, array(), true);

        foreach ($forums as $id => $forum) {
            if ($forum->is_shared()) {
                $originalcmid = $forum->get_course_module_id(true);
                $FORUMNG_CLONE_MAP[$originalcmid] = $forum->get_course_module();
            }
        }
    }
    if (array_key_exists($cm->id, $FORUMNG_CLONE_MAP)) {
        return $FORUMNG_CLONE_MAP[$cm->id];
    } else {
        return $cm;
    }
}

/**
 * Returns all other caps used in module
 */
function forumng_get_extra_capabilities() {
    return array('moodle/site:accessallgroups', 'moodle/site:viewfullnames',
            'moodle/site:trustcontent');
}

/**
 * Given a course_module object, this function returns any
 * "extra" information that may be needed when printing
 * this activity in a course listing.
 * See get_array_of_activities() in course/lib.php
 */
function forumng_get_coursemodule_info($coursemodule) {
    global $DB;
    if ($forumng = $DB->get_record('forumng',
            array('id' => $coursemodule->instance), 'id, name, type')) {
        $info = new cached_cm_info();
        $info->customdata = (object)array('type' => $forumng->type);
        return $info;
    }
}

/**
 * File browsing support for forumng module.
 * @param object $browser
 * @param object $areas
 * @param object $course
 * @param object $cm
 * @param object $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @return file_info instance Representing an actual file or folder (null if not found
 * or cannot access)
 */
function forumng_get_file_info($browser, $areas, $course, $cm, $context, $filearea,
        $itemid, $filepath, $filename) {
    global $CFG;
    require_once($CFG->dirroot . '/mod/forumng/mod_forumng.php');
    if ($context->contextlevel != CONTEXT_MODULE) {
        return null;
    }
    $fileareas = array('attachment', 'post');
    if (!in_array($filearea, $fileareas)) {
        return null;
    }
    try {
        // This will not work for users who can only access the clone forum but cannot access
        // the origin forumng. The ideal way is to pass in the real cloneid instead of using
        // CLONE_DIRECT which means always get the origin forum.
        // But we cannot get the cloneid in here without doing expensive querys such as get all
        // the clone forums and check them one by one.
        $post = mod_forumng_post::get_from_id($itemid, mod_forumng::CLONE_DIRECT);

    } catch (mod_forumng_exception $e) {
        return null;
    }

    $discussion = $post->get_discussion();
    if (!$discussion->can_view()) {
        return null;
    }

    $fs = get_file_storage();
    $filepath = is_null($filepath) ? '/' : $filepath;
    $filename = is_null($filename) ? '.' : $filename;
    if (!($storedfile = $fs->get_file($context->id, 'mod_forumng', $filearea, $itemid,
            $filepath, $filename))) {
        return null;
    }

    $urlbase = $CFG->wwwroot . '/pluginfile.php';
    return new file_info_stored($browser, $context, $storedfile, $urlbase, $filearea,
            $itemid, true, true, false);
}

/**
 * Create html fragment for display on myMoodle page, forums changed since
 * user last visited
 *
 * @param $courses list of courses to output information from
 * @param $htmlarray returned results appended html to display
 */
function forumng_print_overview($courses, &$htmlarray) {
    global $USER, $CFG;
    require_once($CFG->dirroot . '/mod/forumng/mod_forumng.php');

    if (empty($courses) || !is_array($courses) || count($courses) == 0) {
        return array();
    }

    $strnumunread = get_string('discussionsunread', 'forumng');
    $strforum = get_string('modulename', 'forumng');

    foreach ($courses as $course) {
        $str = "";
        $forums = mod_forumng::get_course_forums($course,
                $USER->id, mod_forumng::UNREAD_DISCUSSIONS);
        if (!empty($forums)) {
            foreach ($forums as $forum) {
                // note like all mymoodle, there's no check current user can see each forum
                // ok for openlearn & vital but might need addressing if VLE ever use it

                // only listing unread, not new & unread for performance
                if ($forum->has_unread_discussions()) {
                    $str .= '<div class="overview forumng"><div class="name">' .
                        $strforum . ':' . ' <a title="' . $strforum . '" href="' .
                        $forum->get_url(mod_forumng::PARAM_HTML).'">' .
                        $forum->get_name() . '</a></div>';
                    $str .= '<div class="info">'.$forum->get_num_unread_discussions(). ' '.
                            $strnumunread.'</div></div>';
                }

            }
        }

        if (!empty($str)) {
            if (!array_key_exists($course->id, $htmlarray)) {
                $htmlarray[$course->id] = array();
            }
            if (!array_key_exists('forumng', $htmlarray[$course->id])) {
                $htmlarray[$course->id]['forumng'] = ''; // initialize, avoid warnings
            }
            $htmlarray[$course->id]['forumng'] .= $str;
        }
    }
}

/**
 * Indicates API features that the forum supports.
 *
 * @param string $feature
 * @return mixed True if yes (some features may use other values)
 */
function forumng_supports($feature) {
    switch ($feature) {
        case FEATURE_GROUPS:                  return true;
        case FEATURE_IDNUMBER:                return true;
        case FEATURE_GROUPINGS:               return true;
        case FEATURE_GROUPMEMBERSONLY:        return true;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_COMPLETION_HAS_RULES:    return true;
        case FEATURE_GRADE_HAS_GRADE:         return true;
            // case FEATURE_GRADE_OUTCOMES:          return true;
            // case FEATURE_RATE:                    return true;
        case FEATURE_BACKUP_MOODLE2:          return true;
        default: return false;
    }
}

/**
 * Obtains the automatic completion state for this forum based on any conditions
 * in forum settings.
 *
 * @param object $course Course
 * @param object $cm Course-module
 * @param int $userid User ID
 * @param bool $type Type of comparison (or/and; can be used as return value if no conditions)
 * @return bool True if completed, false if not. (If no conditions, then return
 *   value depends on comparison type)
 */
function forumng_get_completion_state($course, $cm, $userid, $type) {
    // Use forum object to handle this request
    $forum = mod_forumng::get_from_cmid($cm->id, mod_forumng::CLONE_DIRECT);
    return $forum->get_completion_state($userid, $type);
}

/**
 * Used by course/user.php to display this module's user activity outline.
 * @param object $course as this is a standard function this is required but not used here
 * @param object $user Moodle user ob
 * @param object $mod not used here
 * @param object $forum Moodle forumng object
 * @return object A standard object with 2 variables: info (number of posts for this user) and
 * time (last modified)
 */
function forumng_user_outline($course, $user, $mod, $forum) {
    require_once(dirname(__FILE__).'/mod_forumng.php');
    if ($posts = mod_forumng::get_user_activityreport($forum->id, $user->id)) {
        $result = new object();
        $result->info = get_string("numposts", "forumng", $posts->postcount);
        $result->time = $posts->lastpost;
        return $result;
    } else {
        return null;
    }
}

function mod_forumng_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload) {
    global $CFG, $USER;
    require_once($CFG->dirroot . '/mod/forumng/mod_forumng.php');

    // Check remaining slash arguments
    if (count($args) != 2) {
        send_file_not_found();
    }
    list ($itemid, $filename) = $args;

    if ($filearea == 'attachment' || $filearea == 'message') {
        // Get post object and check permissions
        $cloneid = optional_param('clone', 0, PARAM_INT);
        $post = mod_forumng_post::get_from_id($itemid, $cloneid);
        $post->require_view();
        if ($cloneid) {
            // File is actually in other context
            $context = $post->get_forum()->get_context(true);
        }
    } else if ($filearea == 'draft' || $filearea == 'draftmessage') {
        // Get draft object and check it's yours (note: I'm not sure whether it is possible to
        // ever access draft attachments in this manner, as while editing, this access is not
        // used; maybe from the X view, but I don't think it works there, however perhaps in
        // future).
        $draft = mod_forumng_draft::get_from_id($itemid);
        if ($draft->get_user_id() !== $USER->id) {
            send_file_not_found();
        }
    } else {
        send_file_not_found();
    }

    // Get file object and send it
    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'mod_forumng', $filearea, $itemid, '/', $filename);
    if (!($file) || $file->is_directory()) {
        send_file_not_found();
    }
    $lifetime = isset($CFG->filelifetime) ? $CFG->filelifetime : 86400;
    send_stored_file($file, $lifetime, 0);
}

function mod_forumng_cm_info_view(cm_info $cm) {
    global $CFG;
    require_once($CFG->dirroot . '/mod/forumng/mod_forumng.php');
    static $forums = null;
    if ($forums === null) {
        // Read tracking is for real users only
        if (mod_forumng::enabled_read_tracking() && !isguestuser() && isloggedin()) {
            $forums = mod_forumng::get_course_forums(
                    $cm->get_course(), 0, mod_forumng::UNREAD_BINARY);
        } else {
            $forums = array();
        }
    }

    // If current forum is listed, check whether it's unread or not
    if (array_key_exists($cm->instance, $forums)) {
        if ($forums[$cm->instance]->has_unread_discussions()) {
            $cm->set_after_link('<span class="unread">' .
                    get_string('hasunreadposts', 'forumng') . '</span>');
        }
    }
}

/**
 * Sets the module uservisible to false if the user has not got the view capability
 * @param cm_info $cm
 */
function mod_forumng_cm_info_dynamic(cm_info $cm) {
    if (!has_capability('mod/forumng:view',
            context_module::instance($cm->id))) {
        $cm->uservisible = false;
        $cm->set_available(false);
    }
}