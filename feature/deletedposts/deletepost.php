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
 * Discussion feature: Deletedposts.
 * @package forumngfeature
 * @subpackage deletedposts
 * @copyright 2012 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../../config.php');
require_once($CFG->dirroot . '/mod/forumng/mod_forumng.php');
require_once($CFG->dirroot. '/mod/forumng/feature/deletedposts/locallib.php');

// Redirect to deletepostlist.php.
$postid = required_param('p', PARAM_INT);
$pageparams = array('p' => $postid);
$pageparams['p'] = $postid;
$cloneid = optional_param('clone', 0, PARAM_INT);
if ($cloneid) {
    $pageparams['clone' ]= $cloneid;
}

$post = mod_forumng_post::get_from_id($postid, $cloneid);

// Get convenience variables.
$discussion = $post->get_discussion();
$forum = $post->get_forum();
$course = $forum->get_course();
$cm = $forum->get_course_module();

$pageparams['id'] =  $cm->id;
$delete = optional_param('delete', 0, PARAM_INT);
$groupid = optional_param('group', 0, PARAM_INT);
$pageparams['group'] = $groupid;

// Set up page.
$pagename = get_string($delete ? 'deletepost' : 'undeletepost', 'forumng',
        $post->get_effective_subject(true));
$url = new moodle_url('/mod/forumng/feature/deletedposts/deletepost.php', $pageparams);
$out = $discussion->init_page($url, $pagename);

// Do all access security checks.
$post->require_view();
$whynot = '';
if (!$post->can_undelete($whynot)) {
    print_error($whynot, 'forumng');
} else {
    // Undelete the post.
    $post->undelete();
}

$pageurl = new moodle_url('/mod/forumng/feature/deletedposts/deletedpostslist.php', $pageparams);

redirect($pageurl);
