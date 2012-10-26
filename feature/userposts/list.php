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

$cmid = required_param('id', PARAM_INT);
$cloneid = optional_param('clone', 0, PARAM_INT);
$pageparams = array('id' => $cmid);
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

// Print page header.
$thisurl = new moodle_url('/mod/forumng/feature/userposts/list.php', $pageparams);
$out = $forum->init_page($thisurl, get_string('userposts', 'forumngfeature_userposts'));
print $out->header();

// Display group selector if required.
groups_print_activity_menu($cm, $CFG->wwwroot . '/mod/forumng/feature/userposts/list.php?' .
        $forum->get_link_params(mod_forumng::PARAM_PLAIN));

print '<div class="clearer"></div>';

// Get all users.
if (!$users = $forum->get_monitored_users($groupid)) {
    // No users, print info and stop.
    print_string('nothingtodisplay', 'forumng');

    // Display link to the discussion.
    print link_arrow_left($forum->get_name(), '../../view.php?id=' . $cmid);

    // Display footer.
    print $out->footer();
    return;
}

$counts = $forum->get_all_user_post_counts($groupid);

// Is grading enabled and available for the current user?
$grades = array();
$cangrade = $forum->can_grade();
if ($cangrade) {
    $grades = grade_get_grades($course->id, 'mod', 'forumng',
            $forum->get_id(), array_keys($users));
}

$data = array();
foreach ($users as $id => $u) {
    // Set table-row colour to gray for users without data (default).
    $span = "<span style='color:gray'>";

    $row = array();
    $username = $u->firstname . ' ' . $u->lastname;
    $username .= $CFG->forumng_showusername ? ' (' . $u->username . ')' : '';
    $showallpostsby = null;

    if (isset($counts[$id])) {
        $count = $counts[$id];
    } else {
        $count = (object)array('discussions'=>0, 'replies'=>0);
    }
    if ($count->discussions || $count->replies) {
        // Set table-row colour to black for students with data.
        $span = "<span style='color:black'>";
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
    }
    $row[0] = $span . $username . "</span>";
    $row[1] = $span . $count->discussions . "</span>";
    $row[2] = $span . $count->replies . "</span>";
    $row[3] = $span . $showallpostsby . "</span>";

    // Grades for all users regardless of whether they have participated or not.
    $gradeitem = '';
    if ($grades) {
        $user = new stdClass();
        $attributes = array('userid' => $id);
        if (!isset($grades->items[0]->grades[$id]->grade)) {
            $user->grade = -1;
        } else {
            $user->grade = $grades->items[0]->grades[$id]->grade;
            $user->grade = abs($user->grade);
        }
        $menu = html_writer::select(make_grades_menu($forum->get_grading_scale()),
                'menu[' . $id . ']', $user->grade,
                array(-1 => get_string('nograde')), $attributes);
        $gradeitem  = html_writer::tag('div', $menu, array('id' => 'gradeuser' . $id . ''));
    }

    $row[4] = $span . $gradeitem . "</span>";
    $data[] = $row;
}
// Setup the table layout.
$userstr = get_string('user');
$discussions = get_string('discussions', 'forumng');
$replies = get_string('replies', 'forumng');
$action = get_string('action', 'forumngfeature_userposts');
$grade = '';
if ($cangrade) {
    $grade = get_string('grade', 'forumng');
}
$table = new html_table();
$table->head  = array($userstr, $discussions, $replies, "<span class='accesshide'>$action</span>", $grade);
$table->size  = array('30%', '10%', '10%', '40%', '10%');
$table->align = array('left', 'right', 'right', 'left', 'left');
$table->width = '100%';
$table->data  = $data;

// Display heading.
print $out->heading(get_string('userposts', 'forumngfeature_userposts'));

// Print out participation form.
if ($cangrade) {
    print forumngfeature_userposts_grade_form_header($cmid, $groupid);
}

// Display the table.
print html_writer::tag('div', html_writer::table($table),
        array('class' => 'forumng-userpoststable'));

// Print out grade form footer.
if ($cangrade) {
    print forumngfeature_userposts_grade_form_footer();
}

// Display link to the discussion.
$url = '../../view.php?id=' . $cmid;
print link_arrow_left($forum->get_name(), $url);

// Display footer.
print $out->footer();


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
    $output .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'id',
            'value' => $cmid));
    $output .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'group',
            'value' => $groupid));
    $output .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sesskey',
            'value' => $USER->sesskey));
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
