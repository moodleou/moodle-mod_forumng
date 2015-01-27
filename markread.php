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
 * Marks a discussion or forum read.
 * @package mod
 * @subpackage forumng
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('mod_forumng.php');

// This script handles requests to mark a discussion or forum read (without
// actually reading it).

// Can be called with id= (cmid) or d= (discussion id).
$cmid = optional_param('id', 0, PARAM_INT);
$discussionid = optional_param('d', 0, PARAM_INT);
$postid = optional_param('p', 0, PARAM_INT);
if ((!$cmid && !$discussionid && !$postid) || ($cmid && $discussionid && $postid)) {
    print_error('error_markreadparams', 'forumng');
}
$cloneid = optional_param('clone', 0, PARAM_INT);
$ajax = optional_param('ajax', 0, PARAM_BOOL);

// Permitted values 'view', 'discuss'
$back = optional_param('back', '', PARAM_ALPHA);
if (!preg_match('~^(discuss|view)$~', $back)) {
    $back = 'view';
}
if (($back == 'discuss' && !$discussionid)) {
    $back = 'view';
}
if ($postid) {
    $back = 'discuss';
}

// Handle whole forum
if ($cmid) {
    $forum = mod_forumng::get_from_cmid($cmid, $cloneid);
    $groupid = optional_param('group', -1, PARAM_INT);
    if ($groupid == 0) {
        // Just the distinction between 0 and null
        $groupid = mod_forumng::ALL_GROUPS;
    } else if ($groupid == -1) {
        $groupid = mod_forumng::NO_GROUPS;
    }
    $forum->require_view($groupid);
    if (!$forum->can_mark_read()) {
        print_error('error_cannotmarkread', 'forumng');
    }
    $forum->mark_read($groupid);
}

// Handle single discussion
if ($discussionid) {
    $discussion = mod_forumng_discussion::get_from_id($discussionid, $cloneid);
    $forum = $discussion->get_forum();
    $discussion->require_view();
    if (!$discussion->get_forum()->can_mark_read()) {
        print_error('error_cannotmarkread', 'forumng');
    }
    $discussion->mark_read();
    $cmid = $discussion->get_forum()->get_course_module_id();
}

// Handle single post.
if ($postid) {
    $post = mod_forumng_post::get_from_id($postid, $cloneid);
    $discussion = $post->get_discussion();
    $forum = $discussion->get_forum();
    $post->require_view();
    if (!$forum->can_mark_read()) {
        print_error('error_cannotmarkread', 'forumng');
    }
    $post->mark_read();
}

if ($ajax) {
    echo 'ok';
    exit;
}

// Redirect back
if ($back == 'discuss') {
    redirect('discuss.php?' . $discussion->get_link_params(mod_forumng::PARAM_PLAIN) . ($postid ? "#p$postid" : ''));
} else {
    redirect($forum->get_url(mod_forumng::PARAM_PLAIN));
}
