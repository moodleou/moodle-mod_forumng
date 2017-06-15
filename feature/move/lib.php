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
 * Local library file for forumng.  These are non-standard functions that are used
 * only by the forumng move feature.
 *
 * @package forumngfeature
 * @subpackage move
 * @copyright 2013 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 **/

// Make sure this isn't being directly accessed.
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/forumng/mod_forumng.php');
require_once($CFG->dirroot . '/mod/forumng/feature/move/group_form.php');

function permission_to_move($discussion, $targetforum) {
    if (is_a($discussion, 'mod_forumng_discussion')) {
        // Check permission for move.
        $discussion->require_view();
        $forum = $discussion->get_forum();
    } else {
        $forum = $discussion;
    }
    $aag = check_move_permissions($forum, $targetforum);
    return $aag;
}

function check_move_permissions($forum, $targetforum) {
    // Check permission for move.
    require_capability('mod/forumng:movediscussions',
            $forum->get_context());
    require_capability('mod/forumng:movediscussions',
            $targetforum->get_context());
    $aag = has_capability('moodle/site:accessallgroups',
            $targetforum->get_context());
    return $aag;
}

function get_target_group($discussion, $targetforum, $aag = true) {
    // Work out target group for move.
    $targetgroup = $discussion->get_group_id();
    if ( $targetforum->get_group_mode() == 0) {
        // Target forum doesn't have groups.
        $targetgroup = null;
    } else {
        if (!$targetgroup && $aag && $discussion->get_forum()->get_group_mode() != 0) {
            $targetgroup = null;
        } else if ($targetgroup &&
            ($targetforum->get_grouping() != $discussion->get_forum()->get_grouping())) {
            // Target forum has groups :( Need to decide a group.
            // Had source group, but grouping has changed.
            // See if old group belongs to new grouping.
            $allowedgroups = groups_get_all_groups(
                $targetforum->get_course_id(), 0, $targetforum->get_grouping(), 'g.id');
            if (count($allowedgroups) == 1) {
                $targetgroup = key($allowedgroups);
            } else {
                if (!array_key_exists($targetgroup, $allowedgroups)) {
                    // Old group not in new grouping, so don't know where to put it.
                    $targetgroup = null;
                }
            }
        } else {
            // Check to see whether a single or multigroup forum.
            $groupids = get_allowed_groupids($targetforum, $aag);
            if (count($groupids) == 1) {
                $targetgroup = key($groupids);
            } else {
                if (!array_key_exists($targetgroup, $groupids)) {
                    $targetgroup = null;
                }
            }

        }

    }
    return $targetgroup;
}

function get_allowed_groups($targetforum, $aag) {
    global $USER;

    // We don't actually have a target group, get the list of allowed
    // groups and see if there is only one option - if so we will use it.
    // Work out list of allowed groups for current user.
    $cm = $targetforum->get_course_module();
    $groups = array();

    // Need to get groups allowed for that activity - if any at all.
    $groups = groups_get_activity_allowed_groups($cm, $USER->id);

    $options = array();
    if ($groups) {
        foreach ($groups as $group) {
            $options[$group->id] = format_string($group->name);
        }
    } else {
        if ($aag) {
            $options[0] = get_string('allparticipants');
        }
    }
    return $options;
}

function get_allowed_groupids($targetforum, $aag) {
    global $USER;

    // We don't actually have a target group, get the list of allowed
    // groups and see if there is only one option - if so we will use it.
    // Work out list of allowed groups for current user.
    $cm = $targetforum->get_course_module();
    $groups = array();

    // Need to get groups allowed for that activity - if any at all.
    $groups = groups_get_activity_allowed_groups($cm, $USER->id);

    $options = array();
    if ($groups) {
        foreach ($groups as $group) {
            $options[$group->id] = $group->id;
        }
    } else {
        if ($aag) {
            $options[0] = 0;
        }
    }
    return $options;
}

function get_forum_discussions_groups($selectedids, $forum, $cloneid) {
    $groups = array();

    foreach ($selectedids as $d) {
        // We have the discussions and forum cmid, check to see whether it has groups.
        $discussion = mod_forumng_discussion::get_from_id($d, $cloneid);
        // Work out target group.
        $group = get_target_group($discussion, $forum, true);
        $groups[] = $group;
    }

    $groups = array_unique($groups);
    return $groups;

}

function get_target_groups($selectedids, $targetforum, $cloneid) {
    $targetgroups = get_forum_discussions_groups($selectedids, $targetforum, $cloneid);

    if (count($targetgroups) == 1) {
        // We have one usable target group.
        if (empty($targetgroups[0])) {
            $targetgroups[0] = 0;
        }
    } else {
        if (count($targetgroups) == 0) {
            $targetgroups[0] = 0;
        }
    }

    return $targetgroups;
}

function sort_ignore_case($a, $b) {
    $alower = core_text::strtolower($a);
    $blower = core_text::strtolower($b);
    if ($alower == $blower) {
        return 0;
    }
    return ($alower < $blower) ? -1 : 1;
}

function get_other_course_forums($currentforum) {
    $course = $currentforum->get_course();
    $modinfo = get_fast_modinfo($course);
    $results = array();
    foreach ($modinfo->instances['forumng'] as $other) {
        // Don't let user move discussion to its current forum.
        if ($other->instance == $currentforum->get_id() ||
                $other->id == $currentforum->get_course_module_id()) {
            continue;
        }
        $othercontext = context_module::instance($other->id);
        if (has_capability('mod/forumng:movediscussions', $othercontext) && $other->uservisible) {
            $results[$other->id] = $other->name;
        }
    }
    if (count($results) == 0) {
        return '';
    }

    // Make list alphabetical.
    uasort($results, 'sort_ignore_case');

    return $results;
}
