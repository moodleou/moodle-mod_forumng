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

global $OUTPUT;

$cmid = required_param('id', PARAM_INT);
$cloneid = optional_param('clone', 0, PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);
$download   = optional_param('download', '', PARAM_TEXT);
$pageparams = array('id' => $cmid, 'download' => $download, 'page' => $page);
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
$ptable = new forumng_participation_table('mod-forumng-participation');
$ptable->set_attribute('class', 'flexible generaltable');
$ptable->set_attribute('width', '100%');
$ptable->define_columns(array('c1', 'c2', 'c3', 'c4', 'c5'));
$ptable->define_headers(array($userstr, $discussions, $replies, $action, $grade));
$ptable->define_baseurl($PAGE->url);
$filename = "$course->shortname-".format_string($forum->get_name(), true);
if ($groupid != -1 && !empty($download)) {
    $groupname = $DB->get_field('groups', 'name', array('id' => $groupid));
    $filename .= '-'.format_string($groupname, true);
}
$ptable->is_downloading($download, $filename, get_string('userposts', 'forumngfeature_userposts'));

$users = get_enrolled_users($context, '', $groupid > 0 ? $groupid : 0,
        'u.id, u.lastname, u.firstname, u.username, u.picture, u.imagealt, u.email');

if (!$ptable->is_downloading()) {
    $ptable->pagesize($perpage, count($users));
    $offset = $page * $perpage;
    $endposition = $offset + $perpage;
} else {
    // Always export all users.
    $endposition = count($users);
    $offset = 0;
}

if (empty($download)) {
    print $out->header();

    // Display group selector if required.
    groups_print_activity_menu($cm, $CFG->wwwroot . '/mod/forumng/feature/userposts/list.php?' .
            $forum->get_link_params(mod_forumng::PARAM_PLAIN));

    print '<div class="clearer"></div>';

    // Get all users.
    if (!$users) {
        // No users, print info and stop.
        print_string('nothingtodisplay', 'forumng');

        // Display link to the discussion.
        print link_arrow_left($forum->get_name(), '../../view.php?id=' . $cmid);

        // Display footer.
        print $out->footer();
        return;
    }
}
$counts = $forum->get_all_user_post_counts($groupid);

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
foreach ($users as $id => $u) {
    $row = array();
    $picture = $OUTPUT->user_picture($u, array('courseid' => $course->id));
    $username = fullname($u);
    $userurl = new moodle_url('/user/view.php?',
            array('id' => $id, 'course' => $course->id));
    $userdetails = html_writer::link($userurl, $username);

    $username .= $CFG->forumng_showusername ? ' (' . $u->username . ')' : '';
    $showallpostsby = null;

    if (isset($counts[$id])) {
        $count = $counts[$id];
    } else {
        $count = (object)array('discussions'=>0, 'replies'=>0);
    }
    $span = '';
    $postspan = '';
    if (($count->discussions || $count->replies) && empty($download)) {
        $showallpostsby = get_string('showallpostsby', 'forumngfeature_userposts', $username);

        // Build url and the params.
        $url = $CFG->wwwroot .
                "/mod/forumng/feature/userposts/user.php?" .
                $forum->get_link_params(mod_forumng::PARAM_HTML) .
                '&amp;user=' . $id;
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
    $row[1] = $span . $count->discussions . $postspan;
    $row[2] = $span . $count->replies . $postspan;
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
    $data[] = $row;
}

if (empty($download)) {
    // Display heading.
    print $out->heading(get_string('userposts', 'forumngfeature_userposts'));
    echo $ptable->download_buttons();
    // Print out participation form.
    if ($cangrade) {
        print forumngfeature_userposts_grade_form_header($cmid, $groupid);
    }
}
$ptable->downloadable = false;
$ptable->setup();
for ($datacount = count($data); $offset < $endposition && $offset < $datacount; $offset++) {
    $ptable->add_data($data[$offset]);
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

    add_to_log($course->id, 'forumng', 'view', 'list.php?' . $thisurl->get_query_string(false));
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
