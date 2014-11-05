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
 * Move single or multiple discussions to target forum, or display group form if needed.
 * @package forumngfeature
 * @subpackage move
 * @copyright 2013 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../../../config.php');
require_once($CFG->dirroot . '/mod/forumng/mod_forumng.php');
require_once(dirname(__FILE__) . '/lib.php');

$cancel = optional_param('cancel', '', PARAM_TEXT);
$id = required_param('id', PARAM_INT);
$clone  = optional_param('clone', 0, PARAM_INT);
$multigroups = optional_param('multigroups', 0, PARAM_INT);
$directmove = optional_param('directmove', 0, PARAM_INT);

// Get the forum the discussions are being moved from.
$forum = mod_forumng::get_from_cmid($id, $clone);

// Check for cancel and if cancelled redirect back to forum view page.
if (!empty($cancel)) {
    redirect('../../view.php?' .
            $forum->get_link_params(mod_forumng::PARAM_PLAIN));
}
$targetgroup = optional_param('group', 0, PARAM_INT);
$target = optional_param('forum', 0, PARAM_INT);
if ($target == 0) {
    $target = required_param('target', PARAM_INT);
}
$targetforum = mod_forumng::get_from_cmid($target, mod_forumng::CLONE_DIRECT);

// Sets up chosen target group.
$chosentargetgroup = optional_param('chosengroup', 0, PARAM_INT);
if (!$targetgroup) {
    $targetgroup = $chosentargetgroup;
}

if (!$targetgroup) {
    $cm = $forum->get_course_module();
    $targetgroup = mod_forumng::get_activity_group($cm, false);
}

// Security check against user and their capabilities.
$forum->require_view($targetgroup);
check_move_permissions($forum, $targetforum);

// If it is a clone, find the original.
$targetforum = $targetforum->get_real_forum();
$targetgroupmode = $targetforum->get_group_mode();

// Get selected ids.
$selectedids = array();
foreach ($_POST as $field => $value) {
    $matches = array();
    if (!is_array($value) && (string)$value !== '0' && preg_match('~^selectd([0-9]+)$~', $field, $matches)) {
        $selectedids[] = ($matches[1]);
    }
}

if ($targetgroupmode != 0) {
    $targetgroups = get_target_groups($selectedids, $targetforum, mod_forumng::CLONE_DIRECT);
    if (count($targetgroups) == 1) {
        // We have one usable target group.
        if (!empty($targetgroups[0])) {
            $targetgroup = $targetgroups[0];
            $chosentargetgroup = $targetgroup;
        } else {
            $targetgroups[0] = 0;
        }
    } else {
        if (count($targetgroups) == 0) {
            $targetgroups[0] = 0;
        } else {
            $multigroups = 1;
        }
    }
}

$targetgroupdiff = 1;
$groupsfound = true;
$options = array();
if ($targetgroupmode) {
    $options = get_allowed_groupids($targetforum, false);
    // Need to find the difference between original groups and options.
    // See http://www.php.net/manual/en/function.array-diff.php .
    $result = array_diff($targetgroups, $options);
    $targetgroupdiff = count($result);
    $optionsnum = count($options);
    // Check to see whether we have some matching groups.
    $optionsdiff = count(array_diff($options, $targetgroups));
    if ($optionsnum === $optionsdiff) {
        // No groups found.
        $groupsfound = false;
    }
}

if (!$chosentargetgroup && $targetgroup > 0 && !$groupsfound) {
    // Target group set from source forum, but not in target - ensure choice given.
    $targetgroup = 0;
}

if (($targetgroup > 0) || ($targetgroupmode == 0) || ($targetgroupdiff == 0) || $groupsfound) {
    // We have a targetgroup or target group mode is nogroups.
    $targetgroupfound = true;
    foreach ($selectedids as $d) {
        // We have the discussions and targetforum cmid, check to see whether it has groups.
        $discussion = mod_forumng_discussion::get_from_id($d, $clone);
        if (!$discussion->is_deleted()) {
            // Check permission for move.
            $aag = permission_to_move($discussion, $targetforum);
            // Check to see whether we have the discussion.
            // Work out target group for move.
            $discussiongroupid = $discussion->get_group_id();
            if (!$chosentargetgroup) {
                $targetgroup = 0;
                if (in_array ($discussiongroupid, $options, true)) {
                    $targetgroup = $discussiongroupid;
                } else {
                    if ($targetgroupmode > 0) {
                        // We have no target group so try to work one out if need be.
                        // Work out target group for move.
                        $targetgroup = get_target_group($discussion, $targetforum, true);
                    }
                }
                if ($targetgroupmode == 0) {
                    $targetgroup = 0;
                }
                if ($targetgroup == null) {
                    // Target group not found.
                    $targetgroupfound = false;
                }
                if ($targetgroup < 1) {
                    // Target forum has no groups so set target group to null.
                    $targetgroup = null;
                }
            }
            // Perform move.
            $discussion->move($targetforum, $targetgroup);
        }
    }

    if (!$targetgroupfound && !($targetgroupmode == 0 && $forum->get_group_mode() == 0)) {
        global $OUTPUT, $PAGE;
        $pagelink = new moodle_url($targetforum->get_url(mod_forumng::PARAM_PLAIN));
        $PAGE->set_url($pagelink);
        echo $OUTPUT->header();
        if ($targetgroupmode == 0) {
            // No groups found in target forum.
            // Get source forum group mode for use below.
            $sourcegroupmode = $forum->get_group_mode();
            if ($sourcegroupmode != 0) {
                // Groups found in source forum.
                echo $OUTPUT->notification(get_string('nogroup_info', 'forumngfeature_move'));
            }
        } else {
            echo $OUTPUT->notification(get_string('group_info', 'forumngfeature_move'));
        }
        echo $OUTPUT->continue_button($pagelink);
        echo $OUTPUT->footer();
        exit;
    } else {
        // Redirect to new forum.
        redirect($targetforum->get_url(mod_forumng::PARAM_PLAIN));
    }

} else {

    foreach ($selectedids as $discussionid) {
        $params['selectd'.$discussionid] = $discussionid;
    }

    // Get the id of the forum.
    $params['target'] = $target;
    $params['clone'] = $clone;
    $params['id'] = $forum->get_course_module_id();
    $params['multigroups'] = $multigroups;
    $params['targetgroupmode'] = $targetgroupmode;

    $pagename = get_string('move');
    $pageurl = new moodle_url('/mod/forumng/feature/move/movealltogroups.php', $params);
    $out = $forum->init_page($pageurl, $pagename);

    $customdata = array('params' => $params, 'targetforum' => $targetforum);

    require_once(dirname(__FILE__) . '/group_form.php');
    $mform = new mod_forumng_moveall_groups_form('movealltogroups.php', $customdata);

    if ($mform->is_cancelled()) {
        redirect('../../view.php?' .
                $forum->get_link_params(mod_forumng::PARAM_PLAIN));
    } else {
        echo $out->header();
        $mform->display();
        echo $out->footer();
        exit;
    }
}
