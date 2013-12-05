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
 * AJAX script that returns content of a single post.
 * @package mod
 * @subpackage forumng
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');
require_once('mod_forumng.php');

// Post ID
$postid = required_param('p', PARAM_INT);
$cloneid = optional_param('clone', 0, PARAM_INT);
$raw = optional_param('raw', 0, PARAM_INT);
$pageparams = array('p' => $postid);
if ($cloneid) {
    $pageparams['clone'] = $cloneid;
}
if ($raw) {
    $pageparams['raw'] = $raw;
}

try {
    // Get post
    $post = mod_forumng_post::get_from_id($postid, $cloneid, true, true);

    // Do all access security checks
    $post->require_view();
    $post->get_discussion()->init_page(new moodle_url('/mod/forumng/expandpost.php', $pageparams),
            '');

    // Display post
    if ($raw) {
        print $post->prepare_edit_json();
    } else {
        mod_forumng_post::print_for_ajax_and_exit($post);
    }
} catch (coding_exception $e) {
    header('Content-Type: text/plain', true, 500);
    print $e->getMessage();
}
