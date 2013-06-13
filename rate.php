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
 * Set rating for a post or multiple posts in a discussion (AJAX / non-JS).
 * @package mod
 * @subpackage forumng
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');
require_once('mod_forumng.php');

// Discussion ID (to do a bunch at once)
$discussionid = optional_param('d', 0, PARAM_INT);
$cloneid = optional_param('clone', 0, PARAM_INT);
if ($discussionid) {
    // Get discussion and check basic security
    $discussion = mod_forumng_discussion::get_from_id($discussionid, $cloneid);
    $discussion->require_view();

    // Get list of posts to change
    $changes = array();
    foreach ($_POST as $key => $value) {
        $matches = array();
        if (preg_match('~^rating([0-9]+)$~', $key, $matches) &&
            preg_match('~^[0-9]+$~', $value)) {
            $changes[$matches[1]] = (int)$value;
        }
    }

    $transaction = $DB->start_delegated_transaction();
    $rootpost = $discussion->get_root_post();
    foreach ($changes as $postid => $rating) {
        $post = $rootpost->find_child($postid, true);
        if (!$post->can_rate()) {
            print_error('rate_nopermission', 'forumng', '', $postid);
        }
        $post->rate($rating);
    }
    $transaction->allow_commit();
    redirect('discuss.php?' . $discussion->get_link_params(mod_forumng::PARAM_PLAIN));
}

// Post ID (to do a single post)
$postid = required_param('p', PARAM_INT);
$ajax = optional_param('ajax', 0, PARAM_INT);
$rating = required_param('rating', PARAM_INT);

// Get post and check basic security
$post = mod_forumng_post::get_from_id($postid, $cloneid);
$post->require_view();
if (!$post->can_rate()) {
    print_error('rate_nopermission', 'forumng', '', $postid);
}

$post->rate($rating);
if ($ajax) {
    mod_forumng_post::print_for_ajax_and_exit($postid, $cloneid);
}
redirect('discuss.php?' .
        $post->get_discussion()->get_link_params(mod_forumng::PARAM_PLAIN) .
        '#'. $postid);
