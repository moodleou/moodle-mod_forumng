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
 * Page for saving grades for all or one user participation
 *
 * @package mod
 * @subpackage forumng
 * @copyright 2012 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../../../config.php');
require_once($CFG->dirroot . '/mod/forumng/lib.php');
require_once($CFG->dirroot . '/mod/forumng/mod_forumng.php');
require_once($CFG->dirroot. '/mod/forumng/feature/userposts/locallib.php');

$id = required_param('id', PARAM_INT); // Course Module ID.
$groupid = optional_param('group', 0, PARAM_INT);
$userid = optional_param('user', 0, PARAM_INT);

$params = array();
$params['id'] = $id;
$params['group'] = $groupid;

$url = new moodle_url('/mod/forumng/feature/userposts/savegrades.php');

$forum = mod_forumng::get_from_cmid($id, mod_forumng::CLONE_DIRECT);

if (!$cm = $forum->get_course_module()) {
    print_error('invalidcoursemodule');
}

$PAGE->set_cm($cm);

$course = $forum->get_course();
$context = context_module::instance($cm->id);
require_course_login($course, true, $cm);

// Grading capability check.
if (!$forum->can_grade()) {
    print_error('nopermissiontoshow');
}

$mode = '';
if (!empty($_POST['menu'])) {
    $mode = 'bulk';
    $gradeinfo = $_POST['menu'];
} else if ($userid && !empty($_POST['grade'])) {
    $gradeinfo[$userid] = $_POST['grade'];
}
// Update grades.
if (!empty($gradeinfo)) {
    forumngfeature_userposts_update_grades($gradeinfo, $cm, $forum);
}

// Rebuild course cache.
rebuild_course_cache($course->id, true);
// Redirect.
redirect('list.php?id=' . $id . '&group=' . $groupid);
