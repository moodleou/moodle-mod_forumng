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
 * Discussion feature: Deletedposts.
 * @package forumngfeature
 * @subpackage deletedposts
 * @copyright 2012 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../../config.php');
require_once($CFG->dirroot . '/mod/forumng/mod_forumng.php');
require_once($CFG->dirroot. '/mod/forumng/feature/deletedposts/forumngtype_deletedposts.php');
require_once($CFG->dirroot. '/mod/forumng/feature/deletedposts/locallib.php');

$id = required_param('id', PARAM_INT);
$cloneid = optional_param('clone', 0, PARAM_INT);

$params = array('id' => $id);
if ($cloneid !== mod_forumng::CLONE_DIRECT) {
    $params['clone'] = $cloneid;
}
$groupid = optional_param('group', 0, PARAM_INT);
$user = optional_param('user', 0, PARAM_INT);   // Use $user for display purpses not for lower drop down.
$deleteuser = optional_param('deleteuser', 0, PARAM_INT);

$pageurl = new moodle_url('/mod/forumng/feature/deletedposts/deletedpostslist.php', $params);

// Construct forum variable (will check id is valid).
$forum = mod_forumng::get_from_cmid($id, $cloneid);
$course = $forum->get_course();
$cm = $forum->get_course_module();

// If this is a clone, redirect to original.
if ($forum->is_clone()) {
    $forum->redirect_to_original();
}

// Check that forum can be viewed [Handles all other permissions].
if (empty($groupid)) {
    $groupid = mod_forumng::get_activity_group($cm, true);
}
$forum->require_view($groupid, 0, true);

if (!$forum->can_manage_discussions()) {
    print_error('error_cannotmanagediscussion', 'forumng');
}

// Check that USER can edit posts.
global $USER;

if (!has_capability('mod/forumng:editanypost', $forum->get_context(), $USER->id)) {
    print_error('edit_nopermission', 'forumng');
}

// Get update button, if allowed for current user.
$strforum = get_string('modulename', 'forum');

$buttontext = $forum->display_search_form();

// Display header. Because this pagelib class doesn't actually have a
// $buttontext parameter, there has to be a really evil hack.
$out = $forum->init_page($pageurl, get_string('deletedposts', 'forumngfeature_deletedposts'));
print $out->header();
$forum->print_js($cm->id);

// Print ForumNG deleted tabs.
$gid = 0;
if (! empty($groupid) && $groupid > 0) {
    $gid = $groupid;
}

forumng_feature_deleted_print_tabs('deletedpostslist', $params);

print $out->container_start('forumng-main');

// Get forum type to display main part of page.
$posts = get_forumng_deleted_discussion_posts($forum, $gid);

if ($posts) {
    $post = reset($posts);
}

// Need to filtered posts array.
$filteredposts = get_filteredposts($posts, $deleteuser, $user);
if (empty($filteredposts) && $user > 0) {
    // Invalid user choice set so reset to 0.
    $user = 0;
    $filteredposts = get_filteredposts($posts, $deleteuser);
}

// Get deleted user and creator.
$yourname = fullname($USER);

// Create the deleted posts creator array.
$users = array();
$cname = '';
if ($user) {
    $creator = $DB->get_record('user', array('id' => $user), '*', MUST_EXIST);
    $cname = fullname($creator);
    $users[$user] = $cname;
}

// Create the deleted posts deleter array.
$deleteusers = array($USER->id => $yourname);
$dname = '';
if ($deleteuser) {
    $deleter = $DB->get_record('user', array('id' => $deleteuser), '*', MUST_EXIST);
    $dname = fullname($deleter);
    $deleteusers[$deleteuser] = $dname;
}

$deleteusers[0] = get_string('anyone', 'forumngfeature_deletedposts');
$users[0] = get_string('all', 'forumngfeature_deletedposts');
foreach ($posts as $postid => $post) {
    $deleteduser = $post->get_delete_user();
    $deleteusers[$deleteduser->id] = fullname($deleteduser);
    if ($deleteuser == 0 || $deleteduser->id == $deleteuser) {
        $creatoruser = $post->get_user();
        $users[$creatoruser->id] = fullname($creatoruser);
    }
}

$params = array_merge($forum->get_link_params_array(mod_forumng::PARAM_PLAIN), $params);

$gparams = $params;
$gparams['user'] = $user;
$gparams['deleteuser'] = $deleteuser;
$urlroot = new moodle_url('/mod/forumng/feature/deletedposts/deletedpostslist.php?',  $gparams);

// Display group selector if required.
groups_print_activity_menu($cm, $urlroot);
$params['group'] = $gid;

// Set up params for user and deleteuser selectors.
$dparams = $params;
$cparams = $params;
$dparams['user'] = $user;
$cparams['deleteuser'] = $deleteuser;

$userurl = new moodle_url('/mod/forumng/feature/deletedposts/deletedpostslist.php?',  $cparams);
$deleteurl = new moodle_url('/mod/forumng/feature/deletedposts/deletedpostslist.php?',  $dparams);

// Display deleted by selector.
$select = new single_select($deleteurl, 'deleteuser', $deleteusers, $deleteuser, null, 'selectdeleteuser');
$select->label = get_string('deletedby', 'forumngfeature_deletedposts');
$output = $OUTPUT->render($select);
$outl = '<div class="individualselector">'.$output.'</div>';
echo $outl;
// Display created by selector.
$select = new single_select($userurl, 'user', $users, $user, null, 'selectuser');
$select->label = get_string('createdby', 'forumngfeature_deletedposts');
$output = $OUTPUT->render($select);
$outl = '<div class="individualselector">'.$output.'</div>';
echo $outl;

// Remember userid for future reference.
$creatorid = $user;

// Set pagename.
if ($filteredposts) {
    $post = reset($filteredposts);
    $user = $post->get_user();
} else {
    // If we get here no deleted posts have been found for user and deleteduser.
    if (!$user) {
        $cname = get_string('anyone', 'forumngfeature_deletedposts');
    }
    if (!$deleteuser) {
        $dname = get_string('anyone', 'forumngfeature_deletedposts');
    }

    $deleteddata = new stdClass();
    $deleteddata->cusername = $cname;
    $deleteddata->dusername = $dname;

    $info = html_writer::start_div('forumng-deleted-posts-info');
    $info .= get_string('nodeletedpostsfound', 'forumngfeature_deletedposts', $deleteddata);
    $info .= html_writer::end_div();

    echo $info;
}

$out = mod_forumng_utils::get_renderer();
$options = array(
        mod_forumng_post::OPTION_NO_COMMANDS => true,
        mod_forumng_post::OPTION_FIRST_UNREAD => false,
        mod_forumng_post::OPTION_UNREAD_NOT_HIGHLIGHTED => true,
        mod_forumng_post::OPTION_EXPANDED => true,
        mod_forumng_post::OPTION_COMMAND_UNDELETE => true,
        mod_forumng_post::OPTION_SINGLE_POST => true,
        mod_forumng_post::OPTION_VIEW_DELETED_INFO => true);

$rootpostoptions  = array(
        mod_forumng_post::OPTION_NO_COMMANDS => true,
        mod_forumng_post::OPTION_FIRST_UNREAD => false,
        mod_forumng_post::OPTION_UNREAD_NOT_HIGHLIGHTED => true,
        mod_forumng_post::OPTION_EXPANDED => true,
        mod_forumng_post::OPTION_VIEW_DELETED_INFO => true);

$prevdiscussionid = 0;

foreach ($filteredposts as $postid => $post) {
    if (!$post->is_root_post()) {
        $discussion = $post->get_discussion();
        $discussionid = $discussion->get_id();
        if ($discussionid != $prevdiscussionid) {
            // What we need to do is to get the discussion post.
            $rootpost = $discussion->get_root_post();
            print $rootpost->display(true, $rootpostoptions);
            $prevdiscussionid = $discussionid;
        }
    } else {
        print get_string('newdiscussion', 'forumng');
    }

    echo '<div class="forumng-replies forumng-feature-deletedposts">';
    print $post->display(true, $options);
    echo '</div>'; // To deal with replies.
}

print $out->container_end();

echo '</div>'; // To deal with the tabs.

// Log request.
$params = array(
    'context' => $forum->get_context(),
    'objectid' => $forum->get_id(),
    'other' => array('url' => $pageurl->out_as_local_url())
);

$event = \forumngfeature_deletedposts\event\deletedposts_viewed::create($params);
$event->add_record_snapshot('course_modules', $forum->get_course_module());
$event->add_record_snapshot('course', $forum->get_course());
$event->trigger();

// Display footer.
print $out->footer($course);
