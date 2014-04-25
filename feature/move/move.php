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
 * Move to target forum, or display group form if needed.
 * @package forumngfeature
 * @subpackage move
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../../../config.php');
require_once($CFG->dirroot . '/mod/forumng/mod_forumng.php');
require_once(dirname(__FILE__) . '/lib.php');

$d = required_param('d', PARAM_INT);
$target = required_param('target', PARAM_INT);
$pageparams = array('d' => $d, 'target' => $target);
$cloneid = optional_param('clone', 0, PARAM_INT);
if ($cloneid) {
    $pageparams['clone'] = $cloneid;
}
if (!$target) {
    print_error('move_notselected', 'forumng');
}

$discussion = mod_forumng_discussion::get_from_id($d, $cloneid);

// Get target forum.
$targetforum = mod_forumng::get_from_cmid($target, mod_forumng::CLONE_DIRECT);

// If it is a clone, find the original.
$targetforum = $targetforum->get_real_forum();

$targetcmid = $targetforum->get_course_module_id();

// Check permission for move.
$aag = permission_to_move($discussion, $targetforum);

// Work out target group for move.
$targetgroup = get_target_group($discussion, $targetforum, $aag);
$groupmode = $targetforum->get_group_mode();
$options = array();
if ($groupmode && !$targetgroup) {
    $options = get_allowed_groups($targetforum, $aag);
    // If there's only one then we'll use it.
    if (count($options) == 1) {
        reset($options);
        $targetgroup = key($options);
    } else if (count($options) == 0) {
        print_error('move_nogroups', 'forumng');
    }
}

if ((!$targetgroup) && ($groupmode > 0)) {
    // User needs to choose one from form.
    $targetgroup = get_target_group_from_user($targetforum, $cloneid, $d,  $pageparams, $discussion, $options);
}

// Perform move.
$discussion->move($targetforum, $targetgroup);

// Get discussion id.
$id = $discussion->get_id();

// Redirect to new forum.
redirect($targetforum->get_url(mod_forumng::PARAM_PLAIN));

function get_target_group_from_user($targetforum, $cloneid, $d,  $pageparams, $discussion, $options) {
    // User needs to choose one from form.
    require_once(dirname(__FILE__) . '/group_form.php');
    $mform = new mod_forumng_group_form('move.php', (object)array(
            'targetforum' => $targetforum, 'discussionid' => $d,
            'cloneid' => $cloneid, 'groups' => $options));
    if ($mform->is_cancelled()) {
        redirect('../../discuss.php?' .
                $discussion->get_link_params(mod_forumng::PARAM_PLAIN));
    }
    if (($fromform = $mform->get_data()) && array_key_exists($fromform->group, $options)) {
        return $fromform->group;
    } else {
        $pagename = get_string('move');
        $pageurl = new moodle_url('/mod/forumng/feature/move/move.php', $pageparams);
        $out = $discussion->init_page($pageurl, $pagename);
        print $out->header();
        $mform->display();
        print $out->footer();
        exit;
    }
}
