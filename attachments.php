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
 * AJAX script used to get file manager draft IDs when making a new reply in
 * the JavaScript mode.
 * @package mod
 * @subpackage forumng
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');
require_once('mod_forumng.php');

// Get parameters, load objects, and check security
$pageparams = array();
$cmid = required_param('id', PARAM_INT);
$pageparams['id'] = $cmid;
$cloneid = optional_param('clone', 0, PARAM_INT);
if ($cloneid) {
    $pageparams['clone'] = $cloneid;
}

$forum = mod_forumng::get_from_cmid($cmid, $cloneid);
$forum->require_view(mod_forumng::NO_GROUPS);

// Work out some standard variables
$filecontext = $forum->get_context(true); // All files stored in real forum, if this is clone
$fileoptions = array('subdirs'=>false, 'maxbytes'=>$forum->get_max_bytes());

// Set up file area
$draftitemid = 0; // We don't accept this form, so it's always a new one
$messagedraftitemid = 0;

// New reply
file_prepare_draft_area($draftitemid, $filecontext->id, 'mod_forumng', 'attachment',
        null, $fileoptions);
file_prepare_draft_area($messagedraftitemid, $filecontext->id, 'mod_forumng', 'message',
        0, $fileoptions);

// Get the files and count
$options = file_get_drafarea_files($draftitemid, '/');
$usercontext = context_user::instance($USER->id);
$fs = get_file_storage();
$files = $fs->get_area_files($usercontext->id, 'user', 'draft', $options->itemid, 'id', false);
$filecount = count($files);
$options->filecount = $filecount;

$messageoptions = file_get_drafarea_files($messagedraftitemid, '/');
$messageoptions->filecount = 0;

$combined = (object)array('options' => $options, 'messageoptions' => $messageoptions);

// Print for output
print json_encode($combined);
