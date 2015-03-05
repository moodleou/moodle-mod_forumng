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
 * Flags/unflags a post (AJAX or non-JS).
 * @package mod
 * @subpackage forumng
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');
require_once('mod_forumng.php');

// Post ID
$postid = required_param('p', PARAM_INT);
$cloneid = optional_param('clone', mod_forumng::CLONE_DIRECT, PARAM_INT);

// 1 = set flag, 0 = clear it
$flag = required_param('flag', PARAM_INT);

// If the ajax flag is set, this only changes the flag and does not redirect
$ajax = optional_param('ajax', 0, PARAM_INT);

// Optional back parameter
$back = optional_param('back', 'discuss', PARAM_ALPHA);

// Optional time-read parameter (this is used to preserve unread state when
// redirecting back to the discussion
$timeread = optional_param('timeread', 0, PARAM_INT);

try {
    // Get post
    $post = mod_forumng_post::get_from_id($postid, $cloneid, true, true);

    // Do all access security checks
    $post->require_view();
    if (!$post->can_flag()) {
        print_error('error_nopermission', 'forumng');
    }

    // Change the flag
    $post->set_flagged($flag);

    // If it's ajax, that's done
    if ($ajax) {
        print 'ok';
        exit;
    }

    // Redirect
    if ($back == 'view') {
        redirect($post->get_forum()->get_url(mod_forumng::PARAM_PLAIN));
    } else {
        redirect('discuss.php?' .
            $post->get_discussion()->get_link_params(mod_forumng::PARAM_PLAIN) .
            ($timeread ? '&timeread=' . $timeread : '') .
            '#p' . $post->get_id());
    }

} catch (coding_exception $e) {
    header('Content-Type: text/plain', true, 500);
    print $e->getMessage();
}
