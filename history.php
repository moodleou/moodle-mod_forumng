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
 * Displays history of a single post.
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

// Get post
$post = mod_forumng_post::get_from_id($postid, $cloneid, true);

// Get convenience variables
$discussion = $post->get_discussion();
$forum = $post->get_forum();
$course = $forum->get_course();
$cm = $forum->get_course_module();

// Init page
$pagename = get_string('historypage', 'forumng', $post->get_effective_subject(true));
$url = new moodle_url('/mod/forumng/history.php', $pageparams);
$out = $discussion->init_page($url, $pagename);

// Do all access security checks
$post->require_view();
if (!$post->can_view_history($whynot)) {
    print_error($whynot, 'forumng');
}

// Work out navigation for header
print $out->header();

// Print current post
print '<h2>'. get_string('currentpost', 'forumng') . '</h2>';
print $post->display(true, array(mod_forumng_post::OPTION_NO_COMMANDS=>true,
        mod_forumng_post::OPTION_EXPANDED=>true));

print '<h2>'. get_string('olderversions', 'forumng') . '</h2>';
$oldversions = $post->get_old_versions();
foreach ($oldversions as $oldpost) {
    print $oldpost->display(true,
        array(mod_forumng_post::OPTION_NO_COMMANDS=>true,
            mod_forumng_post::OPTION_EXPANDED=>true,
            mod_forumng_post::OPTION_FIRST_UNREAD=>true));
}

// Display footer
print $out->footer();
