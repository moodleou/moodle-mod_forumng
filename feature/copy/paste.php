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
 * Script used to paste discussion into new forum.
 * @package forumngfeature
 * @subpackage copy
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Scripts for paste the discussion or cancel the paste.
require_once('../../../../config.php');
require_once($CFG->dirroot . '/mod/forumng/mod_forumng.php');

$cmid = required_param('cmid', PARAM_INT);
$groupid = optional_param('group', mod_forumng::NO_GROUPS, PARAM_INT);
$cloneid = optional_param('clone', 0, PARAM_INT);

$targetforum = mod_forumng::get_from_cmid($cmid, $cloneid);
if (optional_param('cancel', '', PARAM_RAW)) {
    unset($SESSION->forumng_copyfrom);
    redirect($targetforum->get_url(mod_forumng::PARAM_PLAIN));
}
// If the paste action has already been done or cancelled in a different window/tab.
if (!isset($SESSION->forumng_copyfrom)) {
    redirect($targetforum->get_url(mod_forumng::PARAM_PLAIN));
}
$olddiscussionid = $SESSION->forumng_copyfrom;
$oldcloneid = $SESSION->forumng_copyfromclone;
$olddiscussion = mod_forumng_discussion::get_from_id($olddiscussionid, $oldcloneid);
// Check permission to copy the discussion
require_capability('mod/forumng:copydiscussion',
    $olddiscussion->get_forum()->get_context());
// Security check to see if can start a new discussion in the target forum.
$targetforum->require_start_discussion($groupid);
$olddiscussion->copy($targetforum, $groupid);
unset($SESSION->forumng_copyfrom);
redirect($targetforum->get_url(mod_forumng::PARAM_PLAIN));
