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
 * Forumng mail subscription preferences (uses user record fields).
 *
 * @package    mod_forumng
 * @copyright  2015 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/user/editlib.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/mod/forumng/preferences_form.php');

$userid = optional_param('id', $USER->id, PARAM_INT); // User id.
$courseid = optional_param('course', SITEID, PARAM_INT); // Course id (defaults to Site).
$cmid = optional_param('fid', null, PARAM_INT); // Forum cmid - for redirects.

$PAGE->set_url('/mod/forumng/preferences.php', array('id' => $userid, 'course' => $courseid, 'fid' => $cmid));

list($user, $course) = useredit_setup_preference_page($userid, $courseid);

// Create form.
$forumform = new user_edit_forumng_form(null, array('id' => $userid, 'course' => $courseid, 'fid' => $cmid));
if ($user->maildigest == 2) {
    $user->maildigest = 1;
}
$forumform->set_data($user);

$redirect = new moodle_url('/user/preferences.php', array('userid' => $user->id));
if ($cmid) {
    $redirect = new moodle_url('/mod/forumng/view.php', array('id' => $cmid));
}

if ($forumform->is_cancelled()) {
    redirect($redirect);
} else if ($data = $forumform->get_data()) {

    $user->maildigest = $data->maildigest;
    $user->mailformat = $data->mailformat;

    user_update_user($user, false, true);

    if ($USER->id == $user->id) {
        $USER->maildigest = $data->maildigest;
        $USER->mailformat = $data->mailformat;
    }

    redirect($redirect);
}

// Display page header.
$streditmyforum = get_string('forumpreferences');
$userfullname = fullname($user, true);

// Add forum info to breadcrumbs.
if ($cmid) {
    $modinfo = get_fast_modinfo($courseid);
    $forum = $modinfo->get_cm($cmid);
    $PAGE->navbar->add(format_text($CFG->navshowfullcoursenames ? $course->fullname : $course->shortname),
            "/course/view.php?id=$courseid");
    $PAGE->navbar->add($forum->get_formatted_name(), $redirect);
}

$PAGE->navbar->includesettingsbase = true;
$PAGE->set_title("$course->shortname: $streditmyforum");
$PAGE->set_heading($userfullname);

echo $OUTPUT->header();
echo $OUTPUT->heading($streditmyforum);

// Finally display THE form.
$forumform->display();

// And proper footer.
echo $OUTPUT->footer();
