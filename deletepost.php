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
 * Delete or undelete a post (AJAX or standard).
 * @package mod
 * @subpackage forumng
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');
require_once('mod_forumng.php');

// Get AJAX parameter which might affect error handling
$ajax = optional_param('ajax', 0, PARAM_INT);

// Post ID
$postid = required_param('p', PARAM_INT);
$cloneid = optional_param('clone', 0, PARAM_INT);

// Delete or undelete
$delete = optional_param('delete', 1, PARAM_INT);

$pageparams = array('p'=>$postid);
if ($cloneid) {
    $pageparams['clone'] = $cloneid;
}
if ($delete != 1) {
    $pageparams['delete'] = $delete;
}
if ($ajax) {
    $pageparams['ajax'] = $ajax;
}

// Get post
$post = mod_forumng_post::get_from_id($postid, $cloneid);

// Get convenience variables
$discussion = $post->get_discussion();
$forum = $post->get_forum();
$course = $forum->get_course();
$cm = $forum->get_course_module();

// Set up page
$pagename = get_string($delete ? 'deletepost' : 'undeletepost', 'forumng',
    $post->get_effective_subject(true));
$url = new moodle_url('/mod/forumng/deletepost.php', $pageparams);
$out = $discussion->init_page($url, $pagename);

// Do all access security checks
$post->require_view();
if ($delete) {
    if (!$post->can_delete($whynot)) {
        print_error($whynot, 'forumng');
    }
} else {
    if (!$post->can_undelete($whynot)) {
        print_error($whynot, 'forumng');
    }
}

// Is this the actual delete?
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Delete or undelete the post
    if ($delete) {
        $post->delete();
    } else {
        $post->undelete();
    }

    // Redirect back
    if ($ajax) {
        mod_forumng_post::print_for_ajax_and_exit($postid, $cloneid);
    }

    // Only include post id if user can see deleted posts
    $postid = '';
    if (!$delete || has_capability('mod/forumng:editanypost', $forum->get_context())) {
        $postid = '#p' . $post->get_id();
    }
    redirect('discuss.php?' . $discussion->get_link_params(mod_forumng::PARAM_PLAIN) . $postid);
}

// Confirm page. Work out navigation for header
print $out->header();

// Show confirm option
if ($delete) {
    $confirmstring = get_string('confirmdelete', 'forumng');
    if ($post->is_root_post()) {
        $confirmstring .= ' ' . get_string('confirmdelete_nodiscussion', 'forumng');
    }
} else {
    $confirmstring = get_string('confirmundelete', 'forumng');
}
print $out->confirm($confirmstring,
        new single_button(new moodle_url('/mod/forumng/deletepost.php',
            array('p'=>$post->get_id(), 'delete'=>$delete, 'clone'=>$cloneid)),
            $delete ? get_string('delete') : get_string('undelete', 'forumng'), 'post'),
        new single_button(new moodle_url('/mod/forumng/discuss.php',
            array('d'=>$discussion->get_id(), 'clone'=>$cloneid)),
            get_string('cancel'), 'get'));

// Print post
print $post->display(true,
        array(mod_forumng_post::OPTION_NO_COMMANDS=>true));

// Display footer
print $out->footer();
