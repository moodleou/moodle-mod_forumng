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
 * Show all subscribers to the forum.
 * @package mod
 * @subpackage forumng
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');
require_once('mod_forumng.php');

function my_link_sort($a, $b) {
    $a = core_text::strtolower(substr($a->link, strpos($a->link, '>')+1));
    $b = core_text::strtolower(substr($b->link, strpos($b->link, '>')+1));
    return strcmp($a, $b);
}

$cmid = required_param('id', PARAM_INT);
$cloneid = optional_param('clone', 0, PARAM_INT);
$pageparams = array('id' => $cmid);
if ($cloneid) {
    $pageparams['clone'] = $cloneid;
}

$forum = mod_forumng::get_from_cmid($cmid, $cloneid);
$cm = $forum->get_course_module();
$course = $forum->get_course();

$groupid = mod_forumng::get_activity_group($cm, true);
$forum->require_view($groupid);
if (!$forum->can_view_subscribers()) {
    print_error('subscribers_nopermission', 'forumng');
}
$canmanage = $forum->can_manage_subscriptions();

// Get subscribers
$subscriptionoption = $forum->get_effective_subscription_option();
if ($subscriptionoption == mod_forumng::SUBSCRIPTION_FORCED) {
    $forcedsubscribers = $forum->get_auto_subscribers();
} else {
    $forcedsubscribers = array();
}
if ($forum->is_forced_to_subscribe()) {
    $forcedsubscribers = $forum->get_auto_subscribers();
}

// If they clicked the unsubscribe button, do something different
if (optional_param('unsubscribe', '', PARAM_RAW)) {
    if (!$canmanage) {
        print_error('unsubscribe_nopermission', 'forumng');
    }

    // Header
    $thisurl = new moodle_url('/mod/forumng/subscribers.php', $pageparams);
    $out = $forum->init_page($thisurl, get_string('unsubscribeselected', 'forumng'));
    print $out->header();

    $confirmarray = array('id'=>$cmid, 'confirmunsubscribe'=>1, 'clone'=>$cloneid);
    $list = '<ul>';
    foreach (array_keys($_POST) as $key) {
        $matches = array();
        if (preg_match('~^user([0-9]+)$~', $key, $matches)) {
            $confirmarray[$key] = 1;
            $user = $DB->get_record('user', array('id' => $matches[1]),
                '*', MUST_EXIST);
            $list .= '<li>' . $forum->display_user_link($user) . '</li>';
        }
    }
    $list .= '</ul>';

    print $out->confirm(get_string('confirmbulkunsubscribe', 'forumng'),
            new single_button(new moodle_url('/mod/forumng/subscribers.php', $confirmarray),
                get_string('unsubscribeselected', 'forumng'), 'post'),
            new single_button(new moodle_url('/mod/forumng/subscribers.php',
                array('id'=>$cmid, 'clone'=>$cloneid)),
                get_string('cancel'), 'get'));

    print $list;

    print $out->footer();
    exit;
}
if (optional_param('confirmunsubscribe', 0, PARAM_INT)) {
    if (!$canmanage) {
        print_error('unsubscribe_nopermission', 'forumng');
    }
    $subscribers = $forum->get_subscribers($groupid);
    $transaction = $DB->start_delegated_transaction();
    foreach (array_keys($_POST) as $key) {
        $matches = array();
        if (preg_match('~^user([0-9]+)$~', $key, $matches)) {
            // Use the subscribe list to check this user is on it. That
            // means they can't unsubscribe users in different groups.
            if (array_key_exists($matches[1], $subscribers)) {
                $forum->unsubscribe($matches[1]);
            }
        }
    }
    $transaction->allow_commit();
    redirect('subscribers.php?' . $forum->get_link_params(mod_forumng::PARAM_PLAIN));
}

$thisurl = new moodle_url('/mod/forumng/subscribers.php', $pageparams);
$out = $forum->init_page($thisurl, get_string('subscribers', 'forumng'));
print $out->header();
$forum->print_js();

// Display group selector if required
groups_print_activity_menu($cm, $CFG->wwwroot . '/mod/forumng/subscribers.php?' .
        $forum->get_link_params(mod_forumng::PARAM_PLAIN));

// Get all subscribers
$subscribers = $forum->get_subscribers();
$individualgroup = $groupid != mod_forumng::ALL_GROUPS && $groupid != mod_forumng::NO_GROUPS;

// Remove the subscribers to other groups and discussions which don't belong to this group.
if ($individualgroup) {
    foreach ($subscribers as $key => $user) {
        $removeuser = true;
        if (array_key_exists($groupid, $user->groupids)) {
            $removeuser = false;
        }
        if (in_array($groupid, $user->discussionids)) {
            $removeuser = false;
        }
        if ($user->wholeforum) {
            $removeuser = false;
        }
        if ($removeuser) {
            unset($subscribers[$key]);
        }
    }
}
if (count($subscribers) == 0) {
    print '<p>' . get_string('nosubscribers' .
        ($groupid==mod_forumng::ALL_GROUPS || $groupid==mod_forumng::NO_GROUPS
        ? '' : 'group'), 'forumng') . '</p>';
} else {
    // Get name/link for each subscriber (this is used twice)
    foreach ($subscribers as $user) {
        $user->link = $forum->display_user_link($user);
    }

    // Sort subscribers into name order
    uasort($subscribers, 'my_link_sort');

    // Build table of subscribers
    $table = new html_table;
    $table->head = array(get_string('user'));
    if ($CFG->forumng_showusername) {
        $table->head[] = get_string('username');
    }
    if ($CFG->forumng_showidnumber) {
        $table->head[] = get_string('idnumber');
    }
    $table->head[] = get_string('subscriptions', 'forumng');
    $table->data = array();

    if ($canmanage) {
        // Note: This form has to be a post because if there are a lot of
        // subscribers, the list will be too long to fit in a GET
        print '<form action="subscribers.php" method="post"><div id="forumng-subscription-list">' .
            $forum->get_link_params(mod_forumng::PARAM_FORM);
    }

    $gotsome = false;
    foreach ($subscribers as $user) {
        $row = array();
        $name = $user->link;
        if ($canmanage && !array_key_exists($user->id, $forcedsubscribers)) {
            $name = "<input type='checkbox' name='user{$user->id}' " .
                "value='1' id='check{$user->id}'/> " .
                "<label for='check{$user->id}'>$name</label>";
            $gotsome = true;
        }
        $row[] = $name;
        if ($CFG->forumng_showusername) {
            $row[] = htmlspecialchars($user->username);
        }
        if ($CFG->forumng_showidnumber) {
            $row[] = htmlspecialchars($user->idnumber);
        }
        if ($user->wholeforum) {
            $row[] = get_string('subscribeddiscussionall', 'forumng');
        } else {
            if ($individualgroup) {
                $numberofdiscussions = 0;
                foreach ($user->discussionids as $discussiongroupid) {
                    if ($groupid == $discussiongroupid) {
                        $numberofdiscussions++;
                    }
                }

                if ($numberofdiscussions>0) {
                    $numberofdiscussions = ($numberofdiscussions==1 ?
                            get_string("numberofdiscussion", "forumng", $numberofdiscussions) :
                            get_string("numberofdiscussions", "forumng", $numberofdiscussions)) .
                            '<br />';
                } else {
                    $numberofdiscussions = '';
                }
                $grouplist = '';
                foreach ($user->groupids as $id) {
                    if ($id == $groupid) {
                        $grouplist = get_string('subscribedthisgroup', 'forumng');
                        break;
                    }
                }
            } else {
                $numberofdiscussions = count($user->discussionids);
                if ($numberofdiscussions>0) {
                    $numberofdiscussions = ($numberofdiscussions==1 ?
                            get_string("numberofdiscussion", "forumng", $numberofdiscussions) :
                            get_string("numberofdiscussions", "forumng", $numberofdiscussions)) .
                            '<br />';
                } else {
                    $numberofdiscussions = '';
                }
                $grouplist = '';
                if (count($user->groupids)) {
                    foreach ($user->groupids as $id) {
                        $grouplist .= groups_get_group_name($id) . '<br />';
                    }
                }
            }

            $row[] = $numberofdiscussions . $grouplist;
        }
        if ($user->link) {// CC Inline control structures are not allowed.
            $table->data[] = $row;
        }

    }

    print html_writer::table($table);

    if ($canmanage) {
        if ($gotsome) {
            print '<div id="forumng-buttons"><input type="submit" ' .
                'name="unsubscribe" value="' .
                get_string('unsubscribeselected', 'forumng') . '" /></div>';
        }
        print '</div></form>';
    }
}

print link_arrow_left($forum->get_name(), $forum->get_url(mod_forumng::PARAM_HTML));

print $out->footer($course);
