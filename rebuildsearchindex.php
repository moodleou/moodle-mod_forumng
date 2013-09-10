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
 * This script is for use only temporarily to respond to a glitch in the
 * forum -> ForumNG conversion script where it didn't build search indexes.
 * This file lets the search index be manually rebuilt. We should probably
 * delete it later.
 * @package mod
 * @subpackage forumng
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/forumng/mod_forumng.php');

$cmid = required_param('id', PARAM_INT);
$cloneid = optional_param('clone', 0, PARAM_INT);

$forum = mod_forumng::get_from_cmid($cmid, $cloneid);
$cm = $forum->get_course_module();
require_login($cm->course, false, $cm);
require_capability('moodle/restore:restoreactivity', $cm->context);
mod_forumng::search_installed();
$PAGE->set_url('/mod/forumng/rebuildsearchindex.php', array('id' => $cmid, 'clone' => $cloneid));
// This script is not very user friendly. Once it finishes, it's done...
echo $OUTPUT->header();
mod_forumng::search_update_all(true, $cm->course, $cm->id);
echo $OUTPUT->footer();
