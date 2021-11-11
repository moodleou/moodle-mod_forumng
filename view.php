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
 * Main forum page view.
 * @package mod
 * @subpackage forumng
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');

require_once('mod_forumng.php');

// Require ID parameter here. Other parameters may be required in forum type.
$id = required_param('id', PARAM_INT);

// On the view page ONLY we allow a default for the clone parameter that won't
// cause an error if it's omitted. All other pages have default 0, which will
// show up any errors caused if the parameter is omitted somewhere.
$cloneid = optional_param('clone', mod_forumng::CLONE_DIRECT, PARAM_INT);

$params = array('id' => $id);
if ($cloneid !== mod_forumng::CLONE_DIRECT) {
    $params['clone'] = $cloneid;
}
$pageurl = new moodle_url('/mod/forumng/view.php', $params);

// Construct forum variable (will check id is valid)
$forum = mod_forumng::get_from_cmid($id, $cloneid);
$course = $forum->get_course();
$cm = $forum->get_course_module();

// If this is a clone, redirect to original
if ($forum->is_clone()) {
    $forum->redirect_to_original();
}

// Check that forum can be viewed [Handles all other permissions]
$groupid = mod_forumng::get_activity_group($cm, true);
$forum->require_view($groupid, 0, true);

// Get update button, if allowed for current user
$strforum = get_string("modulename", "forum");

// Atom header meta tag
$feedtype = $forum->get_effective_feed_option();
if ($feedtype == mod_forumng::FEEDTYPE_DISCUSSIONS ||
    ($feedtype == mod_forumng::FEEDTYPE_ALL_POSTS
        && $forum->can_view_discussions())) {
    $atomurl = $forum->get_feed_url(mod_forumng::FEEDFORMAT_ATOM, $groupid);
    $meta = '<link rel="alternate" type="application/atom+xml" ' .
      'title="Atom feed" href="' . htmlspecialchars($atomurl) . '" />';
} else {
    $meta = '';
}

// Initialize $PAGE, compute blocks
global $CURRENTFORUM;
$CURRENTFORUM = $forum;

// Update completion 'viewed' flag if in use
$completion = new completion_info($course);
$completion->set_module_viewed($cm);
// Display header. Because this pagelib class doesn't actually have a
// $buttontext parameter, there has to be a really evil hack
$out = $forum->init_page($pageurl);
print $out->header();
$forum->print_js($cm->id);

print $out->render_forum_header($forum, $groupid);

// Display group selector if required
groups_print_activity_menu($cm, $forum->get_url(mod_forumng::PARAM_HTML));

print $out->container_start('forumng-main');
print $out->render_switch_link($forum);
print $out->skip_link_target();
// Get forum type to display main part of page
$forum->get_type()->print_view_page($forum, $groupid);

print $out->container_end();

// Log request
$forum->log('view');

// Display footer
print $out->footer($course);
