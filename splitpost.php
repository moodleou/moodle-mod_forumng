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
 * Split post or show form for splitting post.
 * @package mod
 * @subpackage forumng
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');
require_once('mod_forumng.php');

// Post ID
$postid = required_param('p', PARAM_INT);
$pageparams = array('p'=>$postid);
$cloneid = optional_param('clone', 0, PARAM_INT);
if ($cloneid) {
    $pageparams['clone'] = $cloneid;
}
// Were all posts expanded?
$expand = optional_param('expand', 0, PARAM_INT);
$expandparam = $expand ? '&expand=1' : '';

// Get post
$post = mod_forumng_post::get_from_id($postid, $cloneid, true);

// Get convenience variables
$discussion = $post->get_discussion();
$forum = $post->get_forum();
$course = $forum->get_course();
$cm = $forum->get_course_module();

// Set up page
$pagename = get_string('splitpost', 'forumng', $post->get_effective_subject(true));
$url = new moodle_url('/mod/forumng/splitpost.php', $pageparams);
$out = $discussion->init_page($url, $pagename);

// Do all access security checks
$post->require_view();
if (!$post->can_split($whynot)) {
    print_error($whynot, 'forumng');
}

require_once('splitpost_form.php');
$mform = new mod_forumng_splitpost_form('splitpost.php',
    array('p'=>$postid, 'clone'=>$cloneid, 'expand' => $expand));

if ($mform->is_cancelled()) {
    redirect('discuss.php?' . $discussion->get_link_params(mod_forumng::PARAM_PLAIN) .
            $expandparam);
} else if ($fromform = $mform->get_data(false)) {
    // Split post
    $newdiscussionid = $post->split($fromform->subject);

    // Redirect back
    redirect('discuss.php?d=' . $newdiscussionid .
            $forum->get_clone_param(mod_forumng::PARAM_PLAIN));
}

// Confirm page. Work out navigation for header
print $out->header();

// Print post
if ($post->get_subject() != null) {
    $mform->set_data(array('subject' => $post->get_subject()));
}

// Print form
$mform->display();

print '<div class="forumng-exampleposts">';

// Print posts
print $post->display_with_children(
    array(mod_forumng_post::OPTION_NO_COMMANDS=>true,
        mod_forumng_post::OPTION_CHILDREN_EXPANDED=>true));

print '</div>';

// Display footer
print $out->footer();
