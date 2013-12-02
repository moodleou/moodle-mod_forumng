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
 * Deletes a discussion after confirm.
 * @package forumngfeature
 * @subpackage delete
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../../config.php');
require_once($CFG->dirroot . '/mod/forumng/mod_forumng.php');

$d = required_param('d', PARAM_INT);
$pageparams = array('d'=>$d);
$cloneid = optional_param('clone', 0, PARAM_INT);
if ($cloneid) {
    $pageparams['clone'] = $cloneid;
}
$delete = required_param('delete', PARAM_INT);
$pageparams['delete'] = $delete;

$discussion = mod_forumng_discussion::get_from_id($d, $cloneid);
$forum = $discussion->get_forum();
$cm = $forum->get_course_module();
$course = $forum->get_course();

// Check permission for change
$discussion->require_edit();

// Set up page
$pagename = get_string(
        $delete ? 'deletediscussion' : 'undeletediscussion', 'forumngfeature_delete');
$url = new moodle_url('/mod/forumng/feature/delete/delete.php', $pageparams);
$out = $discussion->init_page($url, $pagename);

// Is this the actual delete?
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($delete) {
        $discussion->delete();
        redirect($forum->get_url(mod_forumng::PARAM_PLAIN));
    } else {
        $discussion->undelete();
        redirect('../../discuss.php?' . $discussion->get_link_params(mod_forumng::PARAM_PLAIN));
    }
}

// Confirm page. Work out navigation for header
print $out->header();

// Show confirm option
$confirmstring = get_string($delete ? 'confirmdeletediscussion'
        : 'confirmundeletediscussion', 'forumngfeature_delete');
print $out->confirm($confirmstring,
        new single_button(new moodle_url('/mod/forumng/feature/delete/delete.php',
            array('d'=>$discussion->get_id(), 'delete'=>$delete, 'clone'=>$cloneid)),
            $delete ? get_string('delete') : get_string('undelete', 'forumng'), 'post'),
        new single_button(new moodle_url('/mod/forumng/discuss.php',
            array('d'=>$discussion->get_id(), 'clone'=>$cloneid)),
            get_string('cancel'), 'get'));


// Display footer
print $out->footer();
