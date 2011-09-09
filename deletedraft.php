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
 * Delete a draft post, after confirmation.
 * @package mod
 * @subpackage forumng
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');
require_once('mod_forumng.php');

$draftid = required_param('draft', PARAM_INT);
$pageparams = array('draft' => $draftid);
$cloneid = optional_param('clone', 0, PARAM_INT);
if ($cloneid) {
    $pageparams['clone'] = $cloneid;
}

// Load draft and forum
$draft = mod_forumng_draft::get_from_id($draftid);
$forum = mod_forumng::get_from_id($draft->get_forumng_id(), $cloneid);
$course = $forum->get_course();
$cm = $forum->get_course_module();

$pagename = get_string('deletedraft', 'forumng');
$out = $forum->init_page(new moodle_url('/mod/forumng/deletedraft.php', $pageparams), $pagename);

// Check it belongs to current user
if ($USER->id != $draft->get_user_id()) {
    print_error('draft_mismatch', 'forumng');
}

// If they are actually deleting it, go ahead
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $draft->delete($forum->get_context(true));
    redirect($forum->get_url(mod_forumng::PARAM_PLAIN));
}

// Confirm page. Work out navigation for header
print $out->header();

print $out->confirm(get_string('confirmdeletedraft', 'forumng'),
        new single_button(new moodle_url('/mod/forumng/deletedraft.php',
            array('draft'=>$draftid, 'clone'=>$cloneid)),
            get_string('delete'), 'post'),
        new single_button(new moodle_url('/mod/forumng/view.php',
            array('id'=>$forum->get_course_module_id(), 'clone'=>$cloneid)),
            get_string('cancel'), 'get'));

print '<div class="forumng-post">';
print '<div class="forumng-1"></div>';
print '<div class="forumng-2"></div>';
print '<div class="forumng-pic">';
print $out->user_picture($USER, array('courseid'=>$course->id));
print '</div>';
if ($subject = $draft->get_subject()) {
    print '<h3 class="forumng-subject">' . format_string($subject) . '</h3>';
}
print '<div class="forumng-postmain">';
print $draft->get_formatted_message($forum);
print '</div>';
print '</div>';

print $out->footer();
