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
 * This page (list.php) lists the users with in alphabetical order of their
 * last-name with number of discussions and replies. If there are any
 * discussions and/or replies a link is printed for displaying all the posts
 * from that a given user depending on chosen group
 * @package forumngfeature
 * @subpackage userposts
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../../../config.php');
require_once($CFG->dirroot . '/mod/forumng/mod_forumng.php');
require_once($CFG->dirroot. '/mod/forumng/feature/userposts/locallib.php');
require_once($CFG->dirroot . '/lib/gradelib.php');
require_once($CFG->libdir . '/tablelib.php');

global $OUTPUT;

$cmid = required_param('id', PARAM_INT);
$cloneid = optional_param('clone', 0, PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);
$download   = optional_param('download', '', PARAM_TEXT);
$pageparams = array('id' => $cmid, 'page' => $page);
$perpage = 100;
if ($cloneid) {
    $pageparams['clone'] = $cloneid;
}

$forum = mod_forumng::get_from_cmid($cmid, $cloneid);
$course = $forum->get_course();
$cm = $forum->get_course_module();
$context = $forum->get_context();
if ($forum->is_shared() || $forum->is_clone()) {
    throw new moodle_exception('notforsharedforum', 'forumngfeature_userposts');
}

// Check forum access (using forum group, if required).
$groupid = mod_forumng::get_activity_group($cm, true);
if ($groupid != mod_forumng::NO_GROUPS && $groupid != mod_forumng::ALL_GROUPS) {
    $pageparams['group'] = $groupid;
}

// Check access.
$forum->require_view($groupid);
require_capability('forumngfeature/userposts:view', $forum->get_context());
$viewgrade = has_capability('mod/forumng:grade', $context) && $forum->get_grading() != mod_forumng::GRADING_NONE;
$cangrade = $forum->can_grade();

// Print page header.
$thisurl = new moodle_url('/mod/forumng/feature/userposts/list.php', $pageparams);
$out = $forum->init_page($thisurl, get_string('userposts', 'forumngfeature_userposts'));

// Setup the table layout.
$userstr = get_string('user');
$discussions = get_string('discussions', 'forumng');
$replies = get_string('replies', 'forumng');
$action = get_string('action', 'forumngfeature_userposts');
if (empty($download)) {
    $action = "<span class='accesshide'>$action</span>";
}
$grade = '';
if ($viewgrade) {
    $grade = get_string('grade', 'forumng');
}

$timeparts = getdate($course->startdate);
// Create time filter options form.
$customdata = array(
        'options' => array(),
        'cmid' => $cmid,
        'group' => $groupid,
        'download' => $download,
        'startyear' => $timeparts['year'],
        'params' => array()
);
$timefilter = new forumng_participation_table_form(null, $customdata);

$start = $end = 0;
// If data has been received from this form.
if ($submitted = $timefilter->get_data()) {
    if ($submitted->start) {
        $start = strtotime('00:00:00', $submitted->start);
    }
    if ($submitted->end) {
        $end = strtotime('23:59:59', $submitted->end);
    }
} else if (!$timefilter->is_submitted()) {
    // Recieved via post back.
    if ($start = optional_param('start', null, PARAM_INT)) {
        $start = strtotime('00:00:00', $start);
    }
    if ($end = optional_param('end', null, PARAM_INT)) {
        $end = strtotime('23:59:59', $end);
    }
}

// Add collected start and end UNIX formated dates to moodle url.
$thisurl->param('start', $start);
$thisurl->param('end', $end);

$sort = '';
$ptable = new flexible_table('mod-forumng-participation');
$ptable->set_attribute('class', 'flexible generaltable');
$ptable->set_attribute('width', '100%');
$c5value = 'c5';
$newheader = '';
if ((has_capability('mod/forumng:viewanyrating', $context)) &&
        ($forum->get_enableratings() == mod_forumng::FORUMNG_STANDARD_RATING)) {
    if ($forum->get_grading() == mod_forumng::GRADING_MANUAL) {
        if ($forum->get_rating_scale() > 0) {
            $newheader = get_string('avgrating', 'forumngfeature_userposts');
        } else if ($forum->get_rating_scale() < 0) {
            $newheader = get_string('totrating', 'forumngfeature_userposts');;
        }
    } else if ($forum->get_grading() == mod_forumng::GRADING_NONE) {
        if ($forum->get_rating_scale() > 0) {
            $grade = get_string('avgrating', 'forumngfeature_userposts');
            $c5value = 'ratingcol';
        } else if ($forum->get_rating_scale() < 0) {
            $grade = get_string('totrating', 'forumngfeature_userposts');
            $c5value = 'ratingcol';
        }
    }
}
if ($newheader == '') {
    $columnsarray = array('c1', 'numdiscussions', 'numposts', 'c4', $c5value);
    $headersarray = array($userstr, $discussions, $replies, $action, $grade);
} else {
    $columnsarray = array('c1', 'numdiscussions', 'numposts', 'c4', $c5value, 'ratingcol');
    $headersarray = array($userstr, $discussions, $replies, $action, $grade, $newheader);
}
$ptable->define_columns($columnsarray);
$ptable->define_headers($headersarray);
$ptable->define_baseurl($thisurl);
$ptable->sortable(true);
$ptable->maxsortkeys = 1;
$ptable->no_sorting('c4');
$ptable->no_sorting('c5');
$ptable->setup();
$sort = flexible_table::get_sort_for_table('mod-forumng-participation');

if (empty($sort)) {
    $sort = 'c1 ASC';
}

// Need to check whether sort refers to column 'c1' and if it does get correct sql sort statement.
$orderbyuser = stristr($sort, 'c1');

$filename = "$course->shortname-".format_string($forum->get_name(), true);
if ($groupid != -1 && !empty($download)) {
    $groupname = $DB->get_field('groups', 'name', array('id' => $groupid));
    $filename .= '-'.format_string($groupname, true);
}
$ptable->is_downloading($download, $filename, get_string('userposts', 'forumngfeature_userposts'));

if (!$ptable->is_downloading()) {
    $offset = $page * $perpage;
} else {
    // Always export all users.
    $offset = 0;
    $perpage = 0;
}

// Get users posts parameters.
$params["fd_forumngid1"] = $forum->get_id();
list($userwhere1, $userparams1) = get_all_user_post_counts_sql($groupid, false, $start, $end, '1');
$params = array_merge($params, $userparams1);

$params["fd_forumngid2"] = $forum->get_id();
list($userwhere2, $userparams2) = get_all_user_post_counts_sql($groupid, false, $start, $end, '2');
$params = array_merge($params, $userparams2);

$params["fd_forumngid3"] = $forum->get_id();
list($userwhere3, $userparams3) = get_all_user_post_counts_sql($groupid, false, $start, $end, '3');
$params = array_merge($params, $userparams3);

list($esql, $esqlparams) = get_enrolled_sql($context, '', $groupid > 0 ? $groupid : 0, false);
$params = array_merge($params, $esqlparams);

$userfields = user_picture::fields('u', array('username'));
if ($newheader == 'Average rating' || $grade == 'Average rating' ) {
    $sqlfunction = 'AVG';
} else {
    $sqlfunction = 'COUNT';
}

$sql = "SELECT $userfields, COALESCE(ta.numposts, 0) AS numposts, COALESCE(td.numdiscussions, 0) AS numdiscussions,
        COALESCE(tr.ratingval, 0) AS ratingcol
          FROM {user} u
     LEFT JOIN (
          SELECT fp.userid, COUNT(fp.userid) AS numposts
            FROM {forumng_posts} fp
      INNER JOIN {forumng_discussions} fd ON
                 fd.id = fp.discussionid
                 AND fd.postid <> fp.id
           WHERE
                 $userwhere1
        GROUP BY (fp.userid)) ta
            ON u.id = ta.userid

     LEFT JOIN (
          SELECT fp.userid, COUNT(fp.userid) AS numdiscussions
            FROM {forumng_posts} fp
      INNER JOIN {forumng_discussions} fd ON
                 fd.postid = fp.id
           WHERE
                 $userwhere2
        GROUP BY (fp.userid)) td
            ON u.id = td.userid

      LEFT JOIN (
          SELECT fp.userid, $sqlfunction(ra.rating) AS ratingval
            FROM {rating} ra
       INNER JOIN {forumng_posts} fp ON
                  fp.id = ra.itemid
        LEFT JOIN {forumng_discussions} fd ON
                  fd.id = fp.discussionid
            WHERE  $userwhere3 AND ra.component = 'mod_forumng' AND ra.ratingarea = 'post' AND contextid = {$context->id}
         GROUP BY (fp.userid)) tr
             ON u.id = tr.userid

          WHERE u.id IN ($esql)";

if (!$orderbyuser) {
    $sql = "$sql ORDER BY $sort";
} else {
    list($usort, $sortparams) = users_order_by_sql('u');
    // Need to check on sort order.
    $sortorder = stristr($sort, 'ASC');
    if (!$sortorder) {
        // Sort order defaults to ASC.
        $sortorder = 'DESC';
    }
    // Split usort into it relevant parts and add in sortorder.
    $usersort = str_replace(',', ' '.$sortorder.',', $usort);
    $usersort .= ' ' . $sortorder;
    $sql = "$sql ORDER BY $usersort";
}

// Need to do the sql.
global $DB;
if ($viewgrade) {
    // For grades we need to use get_records - this may cause memory issues on download.
    $users = $DB->get_records_sql($sql, $params, $offset, $perpage);
} else {
    $users = $DB->get_recordset_sql($sql, $params, $offset, $perpage);
}

if (!$ptable->is_downloading()) {
    // We may have more users as limited to $perpage, so work out how many.
    list($esql, $params) = get_enrolled_sql($context, '', $groupid > 0 ? $groupid : 0);
    $sql = "SELECT count(1) as count
              FROM {user} u
              JOIN ($esql) je ON je.id = u.id
             WHERE u.deleted = 0";
    $total = $DB->count_records_sql($sql, $params);
    $ptable->pagesize($perpage, $total);
}

if (empty($download)) {
    print $out->header();

    // Display group selector if required.
    groups_print_activity_menu($cm, $CFG->wwwroot . '/mod/forumng/feature/userposts/list.php?' .
            $forum->get_link_params(mod_forumng::PARAM_PLAIN));

    print '<div class="clearer"></div>';

} else {
    // May need more memory as download is all records.
    raise_memory_limit(MEMORY_EXTRA);
}

// Is grading enabled and available for the current user?
$grades = array();
if ($viewgrade) {
    $grades = grade_get_grades($course->id, 'mod', 'forumng',
            $forum->get_id(), array_keys($users));
    $maxgrade = $forum->get_grading() == mod_forumng::GRADING_MANUAL ?
            $forum->get_grading_scale() : $forum->get_rating_scale();
    $scaleopts = make_grades_menu($maxgrade);
}

$data = array();
foreach ($users as $u) {
    $id = $u->id;
    $row = array();
    $picture = $OUTPUT->user_picture($u, array('courseid' => $course->id));
    $username = fullname($u);
    $userurl = new moodle_url('/user/view.php?',
            array('id' => $id, 'course' => $course->id));
    $userdetails = html_writer::link($userurl, $username);

    $username .= $CFG->forumng_showusername ? ' (' . $u->username . ')' : '';
    $showallpostsby = null;
    // Number of discussions.
    if (!isset($u->numdiscussions)) {
        $u->numdiscussions = 0;
    }
    // Number of replies/posts.
    if (!isset($u->numposts)) {
        $u->numposts = 0;
    }

    $span = '';
    $postspan = '';
    if (($u->numdiscussions || $u->numposts) && empty($download)) {
        $showallpostsby = get_string('showallpostsby', 'forumngfeature_userposts', $username);

        // Build url and the params.
        $url = $CFG->wwwroot .
                "/mod/forumng/feature/userposts/user.php?" .
                $forum->get_link_params(mod_forumng::PARAM_HTML) .
                '&amp;user=' . $id;
        if ($start) {
            $url .= '&amp;start=' . $start;
        }
        if ($end) {
            $url .= '&amp;end=' . $end;
        }
        if ($groupid != mod_forumng::NO_GROUPS) {
            $url .= '&amp;group=' . (int)$groupid;
        }
        $showallpostsby = "<a href='$url'>$showallpostsby</a>";
        // Has posts and in web view, so make row 'active'.
        $span = html_writer::start_tag('div', array('class' => 'active'));
        $postspan = html_writer::end_tag('div');
    }

    $rows[0] = '';
    if (empty($download)) {
        $row[0] = $span . $picture . '&nbsp;' . $userdetails . $postspan;
    } else {
        $row[0] = $span . $username . $postspan;
    }
    $row[1] = $span . $u->numdiscussions . $postspan;
    $row[2] = $span . $u->numposts . $postspan;
    $row[3] = $showallpostsby;

    // Grades for all users regardless of whether they have participated or not.
    $gradeitem = '';

    if ($grades) {
        $user = new stdClass();
        $attributes = array();
        if (!isset($grades->items[0]->grades[$id]->grade)) {
            $user->grade = -1;
        } else {
            $user->grade = $grades->items[0]->grades[$id]->grade;
            $user->grade = abs($user->grade);
        }
        if (empty($download) && $cangrade) {
            $menu = html_writer::select($scaleopts,
                    'menu[' . $id . ']', $user->grade,
                    array(-1 => get_string('nograde')), $attributes);
            $gradeitem  = html_writer::tag('div', $menu, array('id' => 'gradeuser' . $id . ''));
        } else {
            if ($user->grade != -1 && isset($scaleopts[$user->grade])) {
                $gradeitem = $scaleopts[$user->grade];
                // Only whole numbers in scaleopts so check if dec point and manually add info.
                if (floor($user->grade) != $user->grade &&
                        !empty($grades->items[0]->grades[$id]->str_long_grade)) {
                    $gradeitem = $grades->items[0]->grades[$id]->str_long_grade;
                }
                if (!empty($download)) {
                    $gradeitem = str_replace('/', '//', $gradeitem);// Fix in excel.
                }
            } else {
                $gradeitem = get_string('nograde');
            }
        }
    }

    $row[4] = $span . $gradeitem . $postspan;

    if (($forum->get_enableratings() != mod_forumng::FORUMNG_NO_RATING) || (($forum->get_grading() != mod_forumng::GRADING_NONE) &&
            ($forum->get_grading() != mod_forumng::GRADING_MANUAL))) {
        $numberparamvalue = 1;
        if ($newheader == get_string('totrating', 'forumngfeature_userposts') ||
                $grade == get_string('totrating', 'forumngfeature_userposts')) {
            $numberparamvalue = 0;
        }
        if ($newheader == '') {
            if ($u->numdiscussions > 0 || $u->numposts > 0) {
                $row[4] = $span . number_format($u->ratingcol, $numberparamvalue) . $postspan;
            } else {
                $row[4] = $span . ' ' . $postspan;
            }
        } else {
            if ($u->numdiscussions > 0 || $u->numposts > 0) {
                $row[5] = $span . number_format($u->ratingcol, $numberparamvalue) . $postspan;
            } else {
                $row[5] = $span . ' ' . $postspan;
            }
        }
    }
    $data[] = $row;
}
if (!$viewgrade) {
    // We used recordset, close it to conserve memory.
    $users->close();
}

if (empty($download)) {

    if (empty($data)) {
        // No users, print info and stop.
        print_string('nothingtodisplay', 'forumng');

        // Display link to the discussion.
        print link_arrow_left($forum->get_name(), '../../view.php?id=' . $cmid);

        // Display footer.
        print $out->footer();
        exit;
    }
    // Display heading.
    print $out->heading(get_string('userposts', 'forumngfeature_userposts'));

    if ($start || $end) {
        $timefilter->set_data(array('start' => $start, 'end' => $end));
    }
    // Display time filter options form.
    $timefilter->display();
    echo $ptable->download_buttons();
    // Print out participation form.
    if ($cangrade) {
        print forumngfeature_userposts_grade_form_header($cmid, $groupid);
    }
}
$ptable->downloadable = false;

foreach ($data as $record) {
    $ptable->add_data($record);
}
$ptable->finish_output();

// Print out grade form footer.
if ($cangrade && empty($download)) {
    print forumngfeature_userposts_grade_form_footer();
}
if (empty($download)) {
    // Display link to the discussion.
    $url = '../../view.php?id=' . $cmid;
    print link_arrow_left($forum->get_name(), $url);

    // Display footer.
    print $out->footer();

    $params = array(
            'context' => $forum->get_context(),
            'objectid' => $forum->get_id(),
            'other' => array('url' => 'list.php?' . $thisurl->get_query_string(false))
    );

    $event = \forumngfeature_userposts\event\participation_viewed::create($params);
    $event->add_record_snapshot('course_modules', $forum->get_course_module());
    $event->add_record_snapshot('course', $forum->get_course());
    $event->trigger();
}
/**
 * Creates save grades form tag and relevant hidden id tags.
 * @param int $cmid course module id.
 * @param int $groupid group id.
 * @return string
 */
function forumngfeature_userposts_grade_form_header($cmid, $groupid) {
    global $USER;
    $formattrs = array();
    $formattrs['action'] = new moodle_url('/mod/forumng/feature/userposts/savegrades.php');
    $formattrs['id'] = 'savegrades';
    $formattrs['method'] = 'post';
    $formattrs['class'] = 'savegradeform';
    $output = html_writer::start_tag('form', $formattrs);
    $output .= html_writer::start_tag('div');
    $output .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'id',
            'value' => $cmid));
    $output .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'group',
            'value' => $groupid));
    $output .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sesskey',
            'value' => $USER->sesskey));
    $output .= html_writer::end_tag('div');
    return $output;
}

/**
 * Creates save grades form end tag and submit button.
 * @return string
 */
function forumngfeature_userposts_grade_form_footer() {
    $savegrades = html_writer::empty_tag('input', array('type' => 'submit',
            'name' => 'savegrades', 'value' => get_string('savegrades', 'forumngfeature_userposts')));
    $output = html_writer::tag('div', $savegrades, array('class' => 'savegradesbutton'));
    $output .= html_writer::end_tag('form');
    return $output;
}


function get_all_user_post_counts_sql($groupid, $ignoreanon = false, $start = null, $end = null, $suffix = '') {
    // Get users posts parameters.
    $getusersparams = array();

    $getuserswhere = 'fd.forumngid = :fd_forumngid'.$suffix.'
    AND fd.deleted = 0
    AND fp.deleted = 0
    AND fp.oldversion = 0';

    if ($groupid != mod_forumng::NO_GROUPS && $groupid != mod_forumng::ALL_GROUPS) {
        $getuserswhere .= ' AND (fd.groupid = :fd_groupid'.$suffix.'OR fd.groupid IS NULL)';
        $getusersparams['fd_groupid'.$suffix] = $groupid;
    }

    if ($ignoreanon) {
        $getuserswhere .= ' AND fp.asmoderator != :fp_moderator'.$suffix;
        $getusersparams['fp_moderator'.$suffix] = mod_forumng::ASMODERATOR_ANON;
    }

    if (!empty($start)) {
        $getuserswhere .= ' AND fp.created >= :fp_start'.$suffix;
        $getusersparams['fp_start'.$suffix] = $start;
    }

    if (!empty($end)) {
        $getuserswhere .= ' AND fp.created <= :fp_end'.$suffix;
        $getusersparams['fp_end'.$suffix] = $end;
    }

    return array($getuserswhere, $getusersparams);
}
