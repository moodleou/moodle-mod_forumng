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

defined('MOODLE_INTERNAL') || die();

/**
 * Add a forumng instance.
 * @param stdClass $data the data to use to create the new forum.
 * @param mod_forumng_mod_form $mform if this call came from a forum submit.
 * @return int new forumng instance id
 */
function forumng_add_instance($forumng, $mform = null) {
    global $DB;

    // Avoid including forum libraries in large areas of Moodle code that
    // require this lib.php; only include when functions are called.
    require_once(dirname(__FILE__) . '/mod_forumng.php');

    $useshared = !empty($forumng->usesharedgroup['useshared']);
    if ($useshared) {
        $idnumber = $forumng->usesharedgroup['originalcmidnumber'];
        $originalcm = $DB->get_record('course_modules', array('idnumber' => $idnumber,
                'module' => $DB->get_field('modules', 'id', array('name' => 'forumng'))), '*',
                MUST_EXIST);
        $originalforumng = $DB->get_record('forumng', array('id' => $originalcm->instance), '*',
                MUST_EXIST);

        // Create appropriate data for forumng table.
        $forumng = (object)array(
            'name' => $originalforumng->name,
            'course' => $forumng->course,
            'type' => 'clone',
            'originalcmid' => $originalcm->id,
            'coursemodule' => $forumng->coursemodule,
            'introductioneditor' => array('text' => '', 'format' => FORMAT_HTML),
        );
    }

    $forumng->timemodified = time();

    // Pick a random magic number.
    $part1 = mt_rand(0, 99999999);
    $part2 = mt_rand(0, 99999999);
    while (strlen($part2) < 8) {
        $part2 = '0' . $part2;
    }
    $forumng->magicnumber = $part1.$part2;

    if ($mform) {
        $forumng->introduction       = $forumng->introductioneditor['text'];
        $forumng->introductionformat = $forumng->introductioneditor['format'];
    }

    $id = $DB->insert_record('forumng', $forumng);

    // Handle post-creation actions (but only if a new forum actually was
    // created, and not just a new reference to a shared one!).
    if (!$useshared) {
        $forum = mod_forumng::get_from_id($id, mod_forumng::CLONE_DIRECT, false);
        $forum->created($forumng->cmidnumber);
    }

    // Handle any content in the introduction editor, including files.
    // Some places that programmatically create forums will not have
    // set this, which is fine, because in that case nothing need be done.
    $context = context_module::instance($forumng->coursemodule);
    if ($mform and !empty($forumng->introductioneditor['itemid'])) {
        $draftitemid = $forumng->introductioneditor['itemid'];
        $introduction = file_save_draft_area_files($draftitemid, $context->id,
                'mod_forumng', 'introduction', 0, array('subdirs' => 1), $forumng->introduction);
        if ($introduction != $forumng->introduction) {
            $DB->set_field('forumng', 'introduction', $introduction, array('id' => $id));
            $forumng->introduction = $introduction;
        }
    }

    return $id;
}

function forumng_update_instance($forumng) {
    global $DB, $CFG;
    require_once(dirname(__FILE__).'/mod_forumng.php');
    // Get the tag lib.php.
    require_once($CFG->dirroot . '/tag/lib.php');

    $forumng->timemodified = time();

    // Handle any content in the introduction editor, including files.
    // Some places that programmatically update forums will not have
    // set this, which is fine, because in that case nothing need be done.
    if (!empty($forumng->introductioneditor)) {
        $context = context_module::instance($forumng->coursemodule);
        $forumng->introduction = $forumng->introductioneditor['text'];
        $forumng->introductionformat = $forumng->introductioneditor['format'];
        $draftitemid = $forumng->introductioneditor['itemid'];
        if ($draftitemid) {
            $forumng->introduction = file_save_draft_area_files($draftitemid, $context->id, 'mod_forumng', 'introduction',
                    0, array('subdirs' => 1), $forumng->introduction);
        }
    }

    $forumng->id = $forumng->instance;
    $previous = $DB->get_record('forumng', array('id' => $forumng->id), '*', MUST_EXIST);
    $DB->update_record('forumng', $forumng);

    $forum = mod_forumng::get_from_id($forumng->id, mod_forumng::CLONE_DIRECT);
    $forum->updated($previous);
    if (isset($forumng->settags)) {
        if (!isset($context)) {
            $context = $forum->get_context(true);
        }
        core_tag_tag::set_item_tags('mod_forumng', 'forumng', $forumng->id, $context, $forumng->settags);
    }

    return true;
}

function forumng_delete_instance($id) {
    global $DB, $OUTPUT;
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
            try {
                course_delete_module($clone->context->instanceid);
            } catch (moodle_exception $e) {
                $OUTPUT->notification("Could not delete the Clone
                        forumng (coursemoduleid) $clone->context->instanceid ", 'error');
                return false;
            }
            rebuild_course_cache($clone->courseid, true);
        }
        $transaction->allow_commit();
    }
    return $DB->delete_records('forumng', array('id' => $id));
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
            'moodle/site:trustcontent', 'report/oualerts:managealerts',
            'report/restrictuser:view', 'report/restrictuser:restrict',
            'report/restrictuser:removerestrict');
}

/**
 * Given a course_module object, this function returns any
 * "extra" information that may be needed when printing
 * this activity in a course listing.
 * See get_array_of_activities() in course/lib.php
 */
function forumng_get_coursemodule_info($coursemodule) {
    global $DB;

    $forumng = $DB->get_record('forumng',
            array('id' => $coursemodule->instance), 'id, name, type, intro, introformat');
    if (!$forumng) {
        return null;
    }

    $info = new cached_cm_info();
    $info->customdata = (object)array('type' => $forumng->type);

    if ($coursemodule->showdescription) {
        // Convert intro to html. Do not filter cached version, filters run at display time.
        $info->content = format_module_intro('forumng', $forumng, $coursemodule->id, false);
    }

    return $info;
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

    } catch (coding_exception $e) {
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
        $forums = array();
        // Read tracking is for real users only.
        if (mod_forumng::enabled_read_tracking() && !isguestuser() && isloggedin()) {
            $forums = mod_forumng::get_course_forums($course,
                    $USER->id, mod_forumng::UNREAD_DISCUSSIONS);
            $modinfo = get_fast_modinfo($course);
        }
        if (!empty($forums)) {
            foreach ($forums as $forum) {
                // Only listing unread, not new & unread for performance.
                if ($modinfo->get_cm($forum->get_course_module_id(true))->uservisible && $forum->has_unread_discussions()) {
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
                $htmlarray[$course->id]['forumng'] = ''; // Initialize, avoid warnings.
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
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_SHOW_DESCRIPTION:        return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_COMPLETION_HAS_RULES:    return true;
        case FEATURE_GRADE_HAS_GRADE:         return true;
        case FEATURE_BACKUP_MOODLE2:          return true;
        default: return null;
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
    // Use forum object to handle this request.
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

/**
 * List of view style log actions
 * @return array
 */
function forumng_get_view_actions() {
    return array('view', 'view all', 'view discussion');
}

/**
 * List of update style log actions
 * @return array
 */
function forumng_get_post_actions() {
    return array('update', 'add', 'add discussion', 'add reply', 'delete post', 'edit post',
            'delete discussion', 'undelete post', 'undelete discussion', 'lock discussion');
}


function mod_forumng_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload) {
    global $CFG, $USER;
    require_once($CFG->dirroot . '/mod/forumng/mod_forumng.php');

    if ($filearea == 'introduction') {
        $filename = array_pop($args);
        $filepath = $args ? '/'.implode('/', $args).'/' : '/';
        $fs = get_file_storage();
        $file = $fs->get_file($context->id, 'mod_forumng', $filearea, 0, $filepath, $filename);
        if (!($file) || $file->is_directory()) {
            send_file_not_found();
        }
        $lifetime = isset($CFG->filelifetime) ? $CFG->filelifetime : 86400;
        send_stored_file($file, $lifetime, 0);
    }

    // Check remaining slash arguments, might have hash for image so the args may be 2 or 3.
    if (count($args) != 2 && count($args) != 3) {
        send_file_not_found();
    }
    list ($itemid, $filename) = $args;
    $filename = urldecode($filename);

    if ($filearea == 'attachment' || $filearea == 'message') {
        // Get post object and check permissions.
        $cloneid = optional_param('clone', 0, PARAM_INT);
        $post = mod_forumng_post::get_from_id($itemid, $cloneid);
        $requirelogin = true;
        if (array_key_exists(2, $args) && $filearea == 'message') {
            // Check server hash and receive hash.
            $hash = $args[2];
            $salt = context_course::instance($course->id)->id;
            $serverhash = sha1($filename . $salt);
            if ($serverhash == $hash) {
                $requirelogin = false;
            }
        }
        if ($requirelogin) {
            $post->require_view();
        }
        if ($cloneid) {
            // File is actually in other context.
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

    // Get file object and send it.
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
        // Read tracking is for real users only.
        if (mod_forumng::enabled_read_tracking() && !isguestuser() && isloggedin()) {
            $forums = mod_forumng::get_course_forums(
                    $cm->get_course(), 0, mod_forumng::UNREAD_BINARY);
        } else {
            $forums = array();
        }
    }

    // If current forum is listed, check whether it's unread or not.
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
        $cm->set_user_visible(false);
        $cm->set_available(false);
    }
}

/**
 * Return forums on course that have unread posts for current user
 *
 * @param stdClass $course
 * @return array
 */
function forumng_get_ourecent_activity($course) {
    global $CFG;
    require_once($CFG->dirroot . '/mod/forumng/mod_forumng.php');
    $forums = mod_forumng::get_course_forums($course, 0, mod_forumng::UNREAD_BINARY);
    $return = array();
    foreach ($forums as $forum) {
        if ($forum->has_unread_discussions()) {
            $data = new stdClass();
            $data->cm = $forum->get_course_module();
            $data->icon = '%%unread%%';
            $return[$data->cm->id] = $data;
        }
    }
    return $return;
}

/**
 * Returns an array of recipients for OU alerts
 * @param char $type
 * @param int $id
 * @returns array
 */
function forumng_oualerts_additional_recipients($type, $id) {
    global $CFG;
    require_once($CFG->dirroot . '/mod/forumng/mod_forumng.php');
    require_once($CFG->dirroot . '/mod/forumng/mod_forumng_discussion.php');

    $recipents = array();
    if ($type == 'post') {
        $discussion = mod_forumng_discussion::get_from_post_id($id, mod_forumng::CLONE_DIRECT);
        $forum = $discussion->get_forum();
        $recipients = $forum->get_reportingemails();
    }
    return($recipients);
}

/**
 * Return post subject or current discussion title
 * @param char $item
 * @param int $id
 * @returns string
 */
function forumng_oualerts_custom_info($item, $id) {
    global $CFG;
    require_once($CFG->dirroot . '/mod/forumng/mod_forumng.php');
    require_once($CFG->dirroot . '/mod/forumng/mod_forumng_post.php');

    $title = '';
    if ($item == 'post') {
        $post = mod_forumng_post::get_from_id($id, mod_forumng::CLONE_DIRECT);
    }

    if ($post) {
        $title = $post->get_subject();
        if ($title == null) {
            // We need to get the last previous post that has a subject field.
            $title = $post->get_effective_subject(true);
        }
    }
    return $title;
}

/**
 * Provides a link for managing OU alerts reports
 * @param settings_navigation $settings
 * @param navigation_node $node
 */
function forumng_extend_settings_navigation(settings_navigation $settings, navigation_node $node) {
    global $PAGE, $CFG, $COURSE;
    require_once($CFG->dirroot . '/mod/forumng/mod_forumng.php');

    $forum = mod_forumng::get_from_cmid($PAGE->cm->id, mod_forumng::CLONE_DIRECT);
    $context = $forum->get_context();
    if ($forum->oualerts_enabled() && has_capability('report/oualerts:managealerts', $PAGE->cm->context)
            && ((count($forum->get_reportingemails()) > 0)) ) {
        $managelevelnode = $node->add(get_string('managepostalerts', 'forumng'),
            new moodle_url( '/report/oualerts/manage.php',
            array('coursename' => $COURSE->id, 'contextcourseid' => $COURSE->id, 'cmid' => $PAGE->cm->id)));
    }
}

/**
 * Return rating related permissions
 *
 * @param string $contextid the context id
 * @param string $component the component to get rating permissions for
 * @param string $ratingarea the rating area to get permissions for
 * @return array an associative array of the user's rating permissions
 */
function forumng_rating_permissions($contextid, $component, $ratingarea) {
    $context = context::instance_by_id($contextid, MUST_EXIST);
    if ($component != 'mod_forumng' || $ratingarea != 'post') {
        // We don't know about this component/ratingarea so just return null to get the
        // default restrictive permissions.
        return null;
    }
    return array(
        'view' => has_capability('mod/forumng:viewrating', $context),
        'viewany' => has_capability('mod/forumng:viewanyrating', $context),
        'viewall' => has_capability('mod/forumng:viewallratings', $context),
        'rate' => has_capability('mod/forumng:rate', $context)
    );
}

/**
 * Validates a submitted rating
 * @param array $params submitted data
 *            context => object the context in which the rated items exists [required]
 *            component => The component for this module - should always be mod_forum [required]
 *            ratingarea => object the context in which the rated items exists [required]
 *            itemid => int the ID of the [FORUMNG-POST] object being rated [required]
 *            scaleid => int the scale from which the user can select a rating. Used for bounds checking. [required]
 *            rating => int the submitted rating
 *            rateduserid => int the id of the user whose items have been rated. NOT the user who submitted the ratings.
 *                           0 to update all. [required]
 *            aggregation => int the aggregation method to apply when calculating grades ie RATING_AGGREGATE_AVERAGE [optional]
 * @return boolean true if the rating is valid. Will throw rating_exception if not
 */
function forumng_rating_validate($params) {
    global $DB, $USER;
    // Check the component is mod_forum.
    if ($params['component'] != 'mod_forumng') {
        throw new rating_exception('invalidcomponent');
    }

    // Check the ratingarea is post (the only rating area in forumng).
    if ($params['ratingarea'] != 'post') {
        throw new rating_exception('invalidratingarea');
    }

    // Check the rateduserid is not the current user .. you can't rate your own posts.
    if ($params['rateduserid'] == $USER->id) {
        throw new rating_exception('nopermissiontorate');
    }

    $forumngsql = "SELECT fp.id, f.id as forumngid, f.ratingscale , f.course, fp.created,
                          f.enableratings, f.ratingfrom, f.ratinguntil
                     FROM {forumng} f
                     JOIN {forumng_discussions} fd ON fd.forumngid = f.id
                     JOIN {forumng_posts} fp ON fp.discussionid = fd.id
                    WHERE fp.id = :itemid";
    $forumngsqlparams = array('itemid' => $params['itemid']);
    $info = $DB->get_record_sql($forumngsql, $forumngsqlparams);
    if (!$info) {
        // Item doesn't exist.
        throw new rating_exception('invaliditemid');
    }
    if ($info->ratingscale != $params['scaleid']) {
        // The scale being submitted doesnt match the one in the database.
        throw new rating_exception('invalidscaleid');
    }

    // Check that the submitted rating is valid for the scale.

    // Lower limit.
    if ($params['rating'] < 0  && $params['rating'] != RATING_UNSET_RATING) {
        throw new rating_exception('invalidnum');
    }

    // Upper limit.
    if ($info->ratingscale < 0) {
        // Its a custom scale.
        $scalerecord = $DB->get_record('scale', array('id' => -$info->ratingscale));
        if ($scalerecord) {
            $scalearray = explode(',', $scalerecord->scale);
            if ($params['rating'] > count($scalearray)) {
                throw new rating_exception('invalidnum');
            }
        } else {
            throw new rating_exception('invalidscaleid');
        }
    } else if ($params['rating'] > $info->ratingscale) {
        // If its numeric and submitted rating is above maximum.
        throw new rating_exception('invalidnum');
    }

    if (!$info->enableratings) {
        // Item isnt approved.
        throw new rating_exception('nopermissiontorate');
    }

    // Check the item we're rating was created in the assessable time window.
    if (!empty($info->ratingfrom) && !empty($info->ratinguntil)) {
        if ($info->created < $info->ratingfrom || $info->created > $info->ratinguntil) {
            throw new rating_exception('notavailable');
        }
    }

    $cm = get_coursemodule_from_instance('forumng', $info->forumngid, $info->course, false, MUST_EXIST);
    $context = context_module::instance($cm->id, MUST_EXIST);

    // If the supplied context doesnt match the item's context.
    if ($context->id != $params['context']->id) {
        throw new rating_exception('invalidcontext');
    }

    return true;
}

/**
 * Can the current user see ratings for a given itemid?
 *
 * @param array $params submitted data
 *            contextid => int contextid [required]
 *            component => The component for this module - should always be mod_forumng [required]
 *            ratingarea => object the context in which the rated items exists [required]
 *            itemid => int the ID of the object being rated [required]
 *            scaleid => int scale id [optional]
 * @return bool
 * @throws coding_exception
 * @throws rating_exception
 */
function mod_forumng_rating_can_see_item_ratings($params) {
    require_once(dirname(__FILE__) . '/mod_forumng.php');
    require_once(dirname(__FILE__) . '/mod_forumng_post.php');

    // Check the component is mod_forum.
    if (!isset($params['component']) || $params['component'] != 'mod_forumng') {
        throw new rating_exception('invalidcomponent');
    }

    // Check the ratingarea is post (the only rating area in forum).
    if (!isset($params['ratingarea']) || $params['ratingarea'] != 'post') {
        throw new rating_exception('invalidratingarea');
    }

    if (!isset($params['itemid'])) {
        throw new rating_exception('invaliditemid');
    }

    $post = mod_forumng_post::get_from_id($params['itemid'], mod_forumng::CLONE_DIRECT);
    if (!$post->get_discussion()->can_view()) {
        return false;
    }
    $context = context::instance_by_id($params['contextid']);
    if (!has_capability('mod/forumng:viewallratings', $context)) {
        return false;
    }
    return true;
}

/**
 * Update activity grades.
 *
 * @param stdClass $forumng database record
 * @param int $userid specific user only, 0 means all
 * @param bool $nullifnone - not used
 */
function forumng_update_grades($forumng, $userid = 0, $nullifnone = true) {
    require_once(dirname(__FILE__) . '/mod_forumng.php');
    $cm = get_coursemodule_from_instance('forumng', $forumng->id);
    $forum = mod_forumng::get_from_id($forumng->id, mod_forumng::CLONE_DIRECT, true, $cm);
    $forum->update_grades($userid, null);
}
