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
 * This page (list.php) lists the users with in alphabetical order of their
 * last-name with number of discussions and replies. If there are any
 * discussions and/or replies a link is printed for displaying all the posts
 * from that a given user depending on chosen group
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
$pageurl = new moodle_url('/mod/forumng/feature/deletedposts/list.php', $params);

// Construct forum variable (will check id is valid).
$forum = mod_forumng::get_from_cmid($id, $cloneid);
$course = $forum->get_course();
$cm = $forum->get_course_module();

// If this is a clone, redirect to original.
if ($forum->is_clone()) {
    $forum->redirect_to_original();
}

// Check that forum can be viewed [Handles all other permissions].
$groupid = optional_param('group', 0, PARAM_INT);
if (empty($groupid)) {
    $groupid = mod_forumng::get_activity_group($cm, true);
}
$forum->require_view($groupid, 0, true);

if (!$forum->can_manage_discussions()) {
    print_error('error_cannotmanagediscussion', 'forumng');
}

// Get update button, if allowed for current user.
$strforum = get_string("modulename", "forum");

$buttontext = $forum->display_search_form();

// Display header. Because this pagelib class doesn't actually have a
// $buttontext parameter, there has to be a really evil hack.
$out = $forum->init_page($pageurl, get_string('deleteddiscussions', 'forumngfeature_deletedposts'));
print $out->header();
$forum->print_js($cm->id);

// Print ForumNG deleted tabs.
$gid = 0;
if (! empty($groupid) && $groupid > 0) {
    $gid = $groupid;
}

$params['group'] = $gid;

forumng_feature_deleted_print_tabs('list', $params);
print $out->container_start('forumng-main');

// Display group selector if required.
groups_print_activity_menu($cm, $CFG->wwwroot . '/mod/forumng/feature/deletedposts/list.php?' .
        $forum->get_link_params(mod_forumng::PARAM_PLAIN));

// Get forum type to display main part of page.
print_view_page($forum, $groupid);

print $out->container_end();
// To deal with showing tabs.
print '</div>';

// Log request.
$params = array(
    'context' => $forum->get_context(),
    'objectid' => $forum->get_id(),
    'other' => array('url' => $pageurl->out_as_local_url())
);

$event = \forumngfeature_deletedposts\event\deleteddiscussions_viewed::create($params);
$event->add_record_snapshot('course_modules', $forum->get_course_module());
$event->add_record_snapshot('course', $forum->get_course());
$event->trigger();

// Display footer.
print $out->footer($course);
