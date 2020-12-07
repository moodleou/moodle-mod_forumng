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
 * Shows list of all forums on a course.
 * @package mod
 * @subpackage forumng
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');
require_once('mod_forumng.php');

// Require ID parameter for course
$id = required_param('id', PARAM_INT);
$pageparams = array('id' => $id);
$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);

require_login($course);

// No additional parameters required for course view (hmm)

// Get some strings
$strforums = get_string('forums', 'forumng');
$strforum = get_string('forum', 'forumng');
$strdescription = get_string('description');
$strsubscribed = get_string('subscribed', 'forumng');
$strdiscussionsunread = get_string('discussionsunread', 'forumng');
$strsubscribe = get_string('subscribeshort', 'forumng');
$strunsubscribe = get_string('unsubscribeshort', 'forumng');
$stryes = get_string('yes');
$strno = get_string('no');
$strpartial = get_string('partialsubscribed', 'forumng');
$strfeeds = get_string('feeds', 'forumng');
$strweek = get_string('week');
$strsection = get_string('section');

$coursecontext = context_course::instance($id);
$canmaybesubscribe = (!isguestuser()
    && has_capability('moodle/course:view', $coursecontext));

// TODO Add search form to button
$buttontext = '';

// Display header
$PAGE->set_url(new moodle_url('/mod/forumng/index.php', $pageparams));
$PAGE->set_context($coursecontext);
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add($strforums);

$out = mod_forumng_utils::get_renderer();
print $out->header();

// Decide what kind of course format it is
$useweeks = $course->format == 'weeks' || $course->format == 'weekscss';
$usesections = $course->format == 'topics';

// Set up table to include all forums
$table = new html_table();
$table->head  = array ($strforum, $strdescription, $strdiscussionsunread);
$table->align = array ('left', 'left', 'center');
if ($useweeks || $usesections) {
    array_unshift($table->head, $useweeks ? $strweek : $strsection);
    array_unshift($table->align, 'left');
}
if ($canmaybesubscribe) {
    $table->head[] = $strsubscribed;
    $table->align[] = 'center';
}

if ($showrss = (($canmaybesubscribe || $course->id == SITEID) &&
    !empty($CFG->enablerssfeeds) && !empty($CFG->forumng_enablerssfeeds))) {
    $table->head[] = $strfeeds;
    $table->align[] = 'center';
}

// Construct forums array
$forums = mod_forumng::get_course_forums($course, 0, mod_forumng::UNREAD_DISCUSSIONS,
    array(), true);

// Display all forums
$currentsection = 0;
$cansubscribesomething = false;
$canunsubscribesomething = false;
foreach ($forums as $forum) {
    $cm = $forum->get_course_module();

    // Skip forum if it's not visible or you can't read discussions there
    if (!$cm->uservisible ||
        !has_capability('mod/forumng:viewdiscussion', $forum->get_context())) {
        continue;
    }

    $row = array();

    // Get section number
    if ($cm->sectionnum != $currentsection) {
        $printsection = $cm->sectionnum;
        // Between each section add a horizontal gap (copied this code,
        // can't say I like it)
        if ($currentsection) {
            $learningtable->data[] = 'hr';
        }
        $currentsection = $cm->sectionnum;
    } else {
        $printsection = '';
    }
    if ($useweeks || $usesections) {
        $row[] = $printsection;
    }

    if ($cm->visible) {
        $style = '';
    } else {
        $style = 'class="dimmed"';
    }

    // Get name and intro
    $row[] =   "<a href='view.php?id={$cm->id}' $style>" .
        format_string($forum->get_name()) . '</a>';
    $activity = (object) array('intro' => $forum->get_introduction(),
            'introformat' => FORMAT_HTML);
    $row[] = format_module_intro('forumng', $activity, $forum->get_course_module_id(true));

    // Get discussion count
    $discussions = $forum->get_num_discussions();
    $unread = $forum->get_num_unread_discussions();
    $row[] = "$discussions ($unread)";

    $subscriptioninfo = $forum->get_subscription_info();
    $subscribed = $subscriptioninfo->wholeforum || count($subscriptioninfo->discussionids) > 0 ||
        count($subscriptioninfo->groupids) > 0;
    if ($subscriptioninfo->wholeforum) {
        // Subscribed to the entire forum.
        $strtemp = $stryes;
    } else if (count($subscriptioninfo->discussionids) == 0 &&
            count($subscriptioninfo->groupids) == 0) {
        $strtemp = $strno;
    } else {
        // Treat partial subscribe the same as subscribe on the index page
        // but display 'Partial' instead of 'Yes'.
        $strtemp = $strpartial;
    }

    // If you have option to subscribe, show subscribed and possibly
    // subscribe/unsubscribe button
    if ($canmaybesubscribe) {
        $subscribetext = "<div class='forumng-subscribecell'>";
        $subscribetext .= $strtemp;
        $option = $forum->get_effective_subscription_option();
        if ($forum->can_change_subscription()) {
            if ($subscribed) {
                // Here print unsubscribe button for full subscribed or partial subscribed forum.
                $canunsubscribesomething = true;
                $submitbutton = "<input type='submit' name='submitunsubscribe'
                        value='$strunsubscribe'/>";
            } else {
                $cansubscribesomething = true;
                $submitbutton = "<input type='submit' name='submitsubscribe'
                        value='$strsubscribe'/>";
            }
            $subscribetext .= "&nbsp;" .
                    "<form method='post' action='subscribe.php'><div>" .
                    $forum->get_link_params(mod_forumng::PARAM_FORM) .
                    "<input type='hidden' name='back' value='index' />" .
                    $submitbutton . "</div></form>";
        }
        $subscribetext .= '</div>';
        $row[] = $subscribetext;
    }

    // If this forum has RSS/Atom feeds, show link
    if ($showrss) {
        if ($type = $forum->get_effective_feed_option()) {
            // Get group (may end up being none)
            $groupid = mod_forumng::get_activity_group(
                $forum->get_course_module(), false);

            $row[] = $forum->display_feed_links($groupid);
        } else {
            $row[] = '&nbsp;';
        }
    }

    $table->data[] = $row;
}

print html_writer::table($table);

// 'Subscribe all' links
if ($canmaybesubscribe) {
    print '<div class="forumng-allsubscribe">';

    $subscribedisabled = $cansubscribesomething ? '' : 'disabled="disabled"';
    $unsubscribedisabled = $canunsubscribesomething ? '' : 'disabled="disabled"';

    print "<form method='post' action='subscribe.php'><div>" .
    "<input type='hidden' name='course' value='{$course->id}' />" .
    "<input type='hidden' name='back' value='index' />" .
    "<input type='submit' name='submitsubscribe' value='" .
    get_string('allsubscribe', 'forumng') . "' $subscribedisabled/>" .
    "<input type='submit' name='submitunsubscribe' value='" .
    get_string('allunsubscribe', 'forumng') . "' $unsubscribedisabled/>" .
    "</div></form> ";

    print '</div>';
}

$params = array(
        'context' => $coursecontext
);
$event = \mod_forumng\event\course_module_instance_list_viewed::create($params);
$event->add_record_snapshot('course', $course);
$event->trigger();

print $out->footer();
