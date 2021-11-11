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
 * Capabilities.
 * @package forumngfeature
 * @subpackage edittags
 * @copyright 2014 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../../config.php');
require_once($CFG->dirroot . '/mod/forumng/mod_forumng.php');

$cmid = required_param('id', PARAM_INT);
$cloneid = optional_param('clone', 0, PARAM_INT);

$forum = mod_forumng::get_from_cmid($cmid, $cloneid);
$course = $forum->get_course();
$cm = $forum->get_course_module();
$context = $forum->get_context(true);

// Get group id for forum.
$groupid = mod_forumng::get_activity_group($cm, true);

$fid = $forum->get_id();

// Check access.
$forum->require_view($groupid);

$managesettags = has_capability('forumngfeature/edittags:managesettags', $forum->get_context());
$editsettags = has_capability('forumngfeature/edittags:editsettags', $forum->get_context());

$groupmode = groups_get_activity_groupmode($cm, $course);

$groupsaccessto = array();
if ($managesettags || $editsettags) {
    if ($groupmode) {
        $groupsaccessto = groups_get_activity_allowed_groups($cm);
        if (!$managesettags && empty($groupsaccessto)) {
            // Nothing to show - shouldn't get button anyway so error.
            print_error('nopermissiontoshow');
        }
    }
} else {
    // Get out of here.
    print_error('nopermissiontoshow');
}

require_once('managetags_form.php');

$urlparams = array('id' => $cmid);

if ($cloneid) {
    $urlparams['clone'] = $cloneid;
}

$groups = array();
$managedtags = array();
if ($managesettags) {
    $groupdetails = new stdClass();
    $groupdetails->id = 0;
    $groupdetails->name = get_string('forumtags', 'forumngfeature_edittags');
    $groups[] = $groupdetails;
    // Get forum set tags.
    $tags = $forum->get_set_tags($fid);
    $managedtags[] = $tags;
}

foreach ($groupsaccessto as $group) {
    $tags = $forum->get_set_tags($fid, $group->id, true);
    $managedtags[] = $tags;
    $groups[] = $group;
}

$customdata = array(
        'options' => array(),
        'id' => $cmid,
        'groups' => $groups,
        'tags' => $managedtags,
        'fid' => $fid,
        'params' => $urlparams
);

// Set up page.
$pagename = get_string('editsettags', 'forumngfeature_edittags');
$url = new moodle_url('/mod/forumng/feature/edittags/managetags.php', $urlparams);
$out = $forum->init_page($url, $pagename);

$mform = new mod_forumng_managetags_form($url, $customdata);

if ($mform->is_cancelled()) {
    // Form is cancelled, redirect back to the discussion.
    redirect('../../view.php?' . $forum->get_link_params(mod_forumng::PARAM_PLAIN));
} else if ($fromform = $mform->get_data()) {
    foreach ($groups as $group) {
        $settagsindex = 'settags_' . $group->id;
        if (isset($fromform->$settagsindex)) {
            if ($group->id) {
                mod_forumng::set_group_tags($forum->get_id(), $group->id, $fromform->$settagsindex);
            } else {
                core_tag_tag::set_item_tags('mod_forumng', 'forumng', $fid, $context, $fromform->$settagsindex);
            }
        }
    }
    redirect('../../view.php?' . $forum->get_link_params(mod_forumng::PARAM_PLAIN));
} else {
    $navigation = array();
    print $out->header();
    $mform->display();

    // Log request.
    $params = array(
            'context' => $forum->get_context(),
            'objectid' => $forum->get_id(),
            'other' => array('url' => 'managetags.php?' . $forum->get_link_params(mod_forumng::PARAM_PLAIN), 'groupid' => $groupid)
    );

    $event = \forumngfeature_edittags\event\tags_edited::create($params);
    $event->add_record_snapshot('course_modules', $forum->get_course_module());
    $event->add_record_snapshot('course', $forum->get_course());
    $event->trigger();

    // Display footer.
    print $out->footer();

}
