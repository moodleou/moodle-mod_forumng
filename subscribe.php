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
 * This script handles requests to subscribe/unsubscribe from a forum or a discussion.
 * It operates in two modes: 'go back' mode, where after subscribing it
 * redirects, and 'full' mode (normally used only for links in email) where
 * it displays information about the action.
 *
 * Specify either course (id) or (course-module) id or discussion (d). If you specify a course
 * then it subscribes/unsubscribes to everything you have access to on that
 * course.
 * @package mod
 * @subpackage forumng
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');
require_once('mod_forumng.php');

$courseid = optional_param('course', 0, PARAM_INT);
$cmid = optional_param('id', 0, PARAM_INT);
$discussionid = optional_param('d', 0, PARAM_INT);
$groupid = optional_param('g', 0, PARAM_INT);
$cloneid = optional_param('clone', 0, PARAM_INT);

$pageparams = array();
if ($courseid) {
    $pageparams['course'] = $courseid;
}
if ($cmid) {
    $pageparams['id'] = $cmid;
}
if ($discussionid) {
    $pageparams['d'] = $discussionid;
}
if ($groupid) {
    $pageparams['g'] = $groupid;
}
if ($cloneid) {
    $pageparams['clone'] = $cloneid;
}
$pageurl = new moodle_url('/mod/forumng/subscribe.php', $pageparams);

$requestingsubscribe = optional_param('submitsubscribe', '', PARAM_RAW);
$requestingunsubscribe = optional_param('submitunsubscribe', '', PARAM_RAW);
$requestingsubscribegroup = optional_param('submitsubscribe_thisgroup', '', PARAM_RAW);
$requestingunsubscribegroup = optional_param('submitunsubscribe_thisgroup', '', PARAM_RAW);
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    // Get request always does unsubscribe
    $requestingunsubscribe = 'y';
    $requestingsubscribe = '';
}

// Only one of the $courseid, $discussionid and $cmid must be true, also subscribe/unsubscribe.
$options = ($courseid ? 1 : 0) + ($cmid ? 1 : 0) + ($discussionid ? 1 : 0);
$subscribeoptions = ($requestingsubscribe ? 1 : 0) + ($requestingunsubscribe ? 1 : 0) +
    ($requestingsubscribegroup ? 1 : 0) + ($requestingunsubscribegroup ? 1 : 0);
// if group is set check that:
// - subscribe/uns-group must be set
// - cmid is set
// - discussionid is not set
// If group is not set, check that:
// - subscribe/uns_group is NOT set
if ($groupid && ($requestingsubscribegroup || $requestingunsubscribegroup) &&
        $cmid && !$discussionid) {
    $groupok = true;
} else if (!($groupid || $requestingunsubscribegroup || $requestingsubscribegroup)) {
    $groupok = true;
} else {
    $groupok = false;
}
if ($options != 1 || $subscribeoptions != 1 || !$groupok) {
    print_error('error_subscribeparams', 'forumng');
}

// Permitted values 'index', 'view', 'discuss', nothing
$back = optional_param('back', '', PARAM_ALPHA);
if (!preg_match('~^(index|view|discuss)$~', $back)) {
    $back = '';
}
if (($back=='index' && !($cmid || $courseid))) {
    $back = '';
}
if (($back=='view' && !$cmid)) {
    $back = '';
}
if (($back=='discuss' && !$discussionid)) {
    $back = '';
}
global $USER;
$userid = $USER->id;

/**
 * Return a list of groups the user belongs to that apply to this forum (same grouping)
 * @param int $userid
 * @param int $forumngid
 * @return an array of group lists or an empty array
 */
function get_group_list($userid, $forumngid) {
    global $DB;
    $sqlgroup = "
SELECT
    g.id AS groupid
FROM
    {forumng} f
    INNER JOIN {course_modules} cm on f.id = cm.instance
    INNER JOIN {modules} m on cm.module = m.id
    INNER JOIN {groups_members} gm ON gm.userid = ?
    INNER JOIN {groups} g ON gm.groupid = g.id AND g.courseid = cm.course
    LEFT JOIN {groupings_groups} gg ON gg.groupid = g.id AND cm.groupingid = gg.groupingid
WHERE
    f.id = ?
    AND m.name = 'forumng'
    AND (cm.groupingid = 0 or gg.id IS NOT NULL)";

    $rs = $DB->get_recordset_sql($sqlgroup, array($userid, $forumngid));
    $results = array();
    foreach ($rs as $rec) {
        $results[] = $rec->groupid;
    }
    $rs->close();
    return $results;
}

// Decide the subscription confirmation string for not directing.
if ($requestingsubscribe) {
    $subscribe = true;
} else {
    $subscribe = false;
}
$confirmtext = get_string(
    $subscribe ? 'subscribe_already' : 'unsubscribe_already', 'forumng');

// Handle single discussion
if ($discussionid) {
    $discussion = mod_forumng_discussion::get_from_id($discussionid, $cloneid);
    $discussion->require_view();
    $forum = $discussion->get_forum();
    if (!$discussion->can_subscribe() && !$discussion->can_unsubscribe()) {
        print_error('error_cannotchangediscussionsubscription', 'forumng');
    }
    if ($requestingsubscribe && $discussion->can_subscribe()) {
        $discussion->subscribe();
        $confirmtext = get_string('subscribe_confirm', 'forumng');
    } else if ($requestingunsubscribe && $discussion->can_unsubscribe()) {
        $discussion->unsubscribe();
        $confirmtext = get_string('unsubscribe_confirm', 'forumng');
    }
}

// Handle single forum
if ($cmid) {
    $forum = mod_forumng::get_from_cmid($cmid, $cloneid);
    $forumngid = $forum->get_id();
    $grouplist = -1;
    if ($groupid) {
        $forum->require_view($groupid);
    } else {
        // If it is a separate groups forum and current user does not have access all groups
        $context = context_module::instance($cmid);
        $aaguser = has_capability('moodle/site:accessallgroups', $context);
        if ($forum->get_group_mode() == SEPARATEGROUPS && !$aaguser) {
            $grouplist = get_group_list($userid, $forumngid);
            // Get list of groups that this user belongs to that apply to this
            // forum (same grouping). Call require_view on the first group in this list, or
            // on NO_GROUPS if they don't have any groups
            if (count($grouplist) == 0) {
                $forum->require_view(mod_forumng::NO_GROUPS);
            } else {
                $forum->require_view($grouplist[0]);
            }

        } else {
            // Require access to all groups (if any)
            $forum->require_view(mod_forumng::NO_GROUPS);
        }
    }

    if (isguestuser()) {
        // This section allows users who are responding to the unsubscribe
        // email link yet who may have already got guest access to the site.
        // The display of the yes/no option is similar to other module behaviour
        // though we could just redirect to login instead.
        $wwwroot = $CFG->wwwroot.'/login/index.php';
        if (!empty($CFG->loginhttps)) {
            $wwwroot = str_replace('http:', 'https:', $wwwroot);
        }
        $out = $forum->init_page($pageurl, get_string('unsubscribeshort', 'forumng'));
        print $out->header();

        print $out->confirm(
                get_string('noguestsubscribe', 'forumng').'<br /><br />'.get_string('liketologin'),
                new single_button(new moodle_url('/login/', array()),
                    get_string('yes'), 'get'),
                new single_button(new moodle_url('/', array()),
                    get_string('no'), 'get'));

        print $out->footer();
        exit;
    }

    if (!$forum->can_change_subscription()) {
        print_error('error_cannotchangesubscription', 'forumng');
    }
    $subscriptioninfo = $forum->get_subscription_info();
    $discussionidcount = count($subscriptioninfo->discussionids);
    $groupidcount = count($subscriptioninfo->groupids);
    if (!$forum->get_group_mode()) {
        // No group mode.
        if ($requestingsubscribegroup || $requestingunsubscribegroup) {
            print_error('error_cannotchangegroupsubscription', 'forumng');
        }
        if ($subscriptioninfo->wholeforum) {
            // Subscribed to the entire forum.
            $subscribed = mod_forumng::FULLY_SUBSCRIBED;
        } else if ($discussionidcount == 0) {
            $subscribed = mod_forumng::NOT_SUBSCRIBED;
        } else {
            $subscribed = mod_forumng::PARTIALLY_SUBSCRIBED;
        }
        if ($requestingsubscribe && $subscribed != mod_forumng::FULLY_SUBSCRIBED) {
            $forum->subscribe();
            $confirmtext = get_string('subscribe_confirm', 'forumng');
        } else if ($requestingunsubscribe && $subscribed != mod_forumng::NOT_SUBSCRIBED) {
            $forum->unsubscribe();
            $confirmtext = get_string('unsubscribe_confirm', 'forumng');
        }
    } else {
        if ($subscriptioninfo->wholeforum) {
            if ($requestingunsubscribe) {
                $forum->unsubscribe();
                $confirmtext = get_string('unsubscribe_confirm', 'forumng');
            } else {
                print_error('error_invalidsubscriptionrequest', 'forumng');
            }
        } else if ($discussionidcount != 0 || $groupidcount != 0 ) {
            // Possible for subscribing to /unsubscribing from forum/group.
            if ($requestingsubscribe) {
                if ($grouplist == -1) {
                    $forum->subscribe();
                } else {
                    foreach ($grouplist as $groupid) {
                        $forum->subscribe(0, $groupid);
                    }
                }
                $confirmtext = get_string('subscribe_confirm', 'forumng');
            } else if ($requestingunsubscribe) {
                $forum->unsubscribe();
                $confirmtext = get_string('unsubscribe_confirm', 'forumng');
            } else if ($requestingsubscribegroup) {
                // Check whether the user has subscribed to this group or not
                $cansubscribetogroup = true;
                foreach ($subscriptioninfo->groupids as $id) {
                    if ($id == $groupid) {
                        $cansubscribetogroup = false;
                        break;
                    }
                }
                if ($cansubscribetogroup) {
                    $forum->subscribe(0, $groupid);
                    $confirmtext = get_string('subscribe_confirm_group', 'forumng');
                } else {
                    print_error('subscribe_already_group', 'forumng');
                }
            } else if ($requestingunsubscribegroup) {
                $canunsubscribefromgroup = false;
                foreach ($subscriptioninfo->groupids as $id) {
                    if ($id == $groupid) {
                        $canunsubscribefromgroup = true;
                        break;
                    }
                }
                // Check if subscribed to any discussions belong to this group.
                foreach ($subscriptioninfo->discussionids as $id => $grpid) {
                    if ($grpid == $groupid) {
                        $canunsubscribefromgroup = true;
                        break;
                    }
                }
                if ($canunsubscribefromgroup) {
                    $forum->unsubscribe(0, $groupid);
                    $confirmtext = get_string('unsubscribe_confirm_group', 'forumng');
                } else {
                    print_error('unsubscribe_already_group', 'forumng');
                }
            } else {
                print_error('error_invalidsubscriptionrequest', 'forumng');
            }

        } else {
            // Not subscribed yet
            if ($requestingsubscribe) {
                // TODO Change to take account of group list if there is one
                if ($grouplist == -1) {
                    $forum->subscribe();
                } else {
                    foreach ($grouplist as $groupid) {
                        $forum->subscribe(0, $groupid);
                    }
                }
                $confirmtext = get_string('subscribe_confirm', 'forumng');
            } else if ($requestingsubscribegroup && $groupid) {
                $forum->subscribe(0, $groupid);
                $confirmtext = get_string('subscribe_confirm_group', 'forumng');
            } else {
                print_error('error_invalidsubscriptionrequest', 'forumng');
            }
        }
    }

}

// Handle whole course
if ($courseid) {
    $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
    require_login($course);
    $forums = mod_forumng::get_course_forums($course, 0, mod_forumng::UNREAD_NONE, array(), true);

    foreach ($forums as $forum) {
        if (!$forum->can_change_subscription()) {
            continue;
        }
        $subscriptioninfo = $forum->get_subscription_info();
        $discussionidcount = count($subscriptioninfo->discussionids);
        if ($subscriptioninfo->wholeforum) {
            // Subscribed to the entire forum.
            $subscribed = mod_forumng::FULLY_SUBSCRIBED;
        } else if ($discussionidcount == 0) {
            $subscribed = mod_forumng::NOT_SUBSCRIBED;
        } else {
            $subscribed = mod_forumng::PARTIALLY_SUBSCRIBED;
        }
        if ($forum->can_change_subscription()) {
            if ($requestingsubscribe && $subscribed != mod_forumng::FULLY_SUBSCRIBED) {
                 // If this is separate groups and user does not have access all groups,
                // then make a group list
                $grouplist = -1;
                // if separate groups and not access all groups , set to list of groups
                $context = $forum->get_context();
                $aaguser = has_capability('moodle/site:accessallgroups', $context);
                if ($forum->get_group_mode() == SEPARATEGROUPS && !$aaguser) {
                    $grouplist = get_group_list($userid, $forumngid);
                }
                if ($grouplist == -1) {
                    $forum->subscribe();
                } else {
                    foreach ($grouplist as $groupid) {
                        $forum->subscribe(0, $groupid);
                    }
                }
                $confirmtext = get_string('subscribe_confirm', 'forumng');
            } else if ($requestingunsubscribe && $subscribed != mod_forumng::NOT_SUBSCRIBED) {
                $forum->unsubscribe();
                $confirmtext = get_string('unsubscribe_confirm', 'forumng');
            }
        }
    }
}

// Redirect back
$backurl ='';
if ($back == 'index') {
    if (!$courseid) {
        $courseid = $forum->get_course()->id;
    }
    redirect('index.php?id=' . $courseid);
}
if ($back == 'view') {
    redirect($forum->get_url(mod_forumng::PARAM_PLAIN));
}
if ($back == 'discuss') {
    redirect('discuss.php?' . $discussion->get_link_params(mod_forumng::PARAM_PLAIN));
}

// Not redirecting? OK, confirm
if ($cmid || $discussionid) {
    $backurl = $forum->get_url(mod_forumng::PARAM_HTML);
    $out = $forum->init_page($pageurl, get_string(
        $subscribe ? 'subscribeshort' : 'unsubscribeshort', 'forumng'));
    print $out->header();
    print $out->notification($confirmtext, 'success');
    print $out->continue_button($backurl);
    print $out->footer();
} else {
    $backurl = $CFG->wwwroot . '/course/view.php?id=' . $courseid;
    $PAGE->set_url($pageurl);
    $PAGE->set_context(context_course::instance($courseid));
    $PAGE->set_heading($COURSE->fullname);
    $PAGE->set_title($COURSE->shortname);
    $out = forum_utils::get_renderer();
    print $out->header();
    print $out->notification($confirmtext, 'success');
    print $out->continue_button($backurl);
    print $out->footer();
}
