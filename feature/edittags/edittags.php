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

$d = required_param('d', PARAM_INT);
$cloneid = optional_param('clone', 0, PARAM_INT);

$pageparams = array('d' => $d);
if ($cloneid) {
    $pageparams['clone'] = $cloneid;
}

$discussion = mod_forumng_discussion::get_from_id($d, $cloneid);
$forum = $discussion->get_forum();
$cm = $forum->get_course_module();
$course = $forum->get_course();

$context = $forum->get_context();

// Check forum access (using forum group, if required).
$groupid = $discussion->get_group_id();
if (empty($groupid)) {
    $groupid = mod_forumng::NO_GROUPS;
}

// Check access.
$forum->require_view($groupid);
if (!$forum->can_tag_discussion()) {
    // Get out of here.
    print_error('nopermissiontoshow');
}

$user = $discussion->get_poster();
if ($USER->id != $user->id) {
    if (!$discussion->can_manage()) {
        // Get out of here.
        print_error('nopermissiontoshow');
    }
}

require_once('edittags_form.php');

if ($groupid == mod_forumng::NO_GROUPS || $groupid == mod_forumng::ALL_GROUPS) {
    $groupid = 0;
}

$urlparams = array('d' => $d);

if ($cloneid) {
    $urlparams['clone'] = $cloneid;
}

$tags = $discussion->get_tags(true);

$forumtags = array();
foreach ($forum->get_tags_used($groupid, true) as $tag) {
    $forumtags[$tag->name] = core_tag_tag::make_display_name($tag);
}

$customdata = array(
        'options' => array(),
        'cmid' => $cm->id,
        'group' => $groupid,
        'tags' => $tags,
        'forumtags' => $forumtags,
        'params' => $urlparams
);

// Set up page.
$pagename = get_string('editdiscussiontags', 'forumngfeature_edittags');
$url = new moodle_url('/mod/forumng/feature/edittags/edittags.php', $pageparams);
$out = $discussion->init_page($url, $pagename);

$mform = new mod_forumng_edittags_form($url, $customdata);

if ($mform->is_cancelled()) {
    // Form is cancelled, redirect back to the discussion.
    redirect('../../discuss.php?' . $discussion->get_link_params(mod_forumng::PARAM_PLAIN));
} else if ($fromform = $mform->get_data()) {
    if (!isset($fromform->tags)) {
        $fromform->tags = null;
    } else if (empty($fromform->tags)) {
        $fromform->tags = array();
    }
    $discussion = mod_forumng_discussion::get_from_id($d, $cloneid);
    $discussion->edit_settings(mod_forumng_discussion::NOCHANGE, mod_forumng_discussion::NOCHANGE,
            mod_forumng_discussion::NOCHANGE, mod_forumng_discussion::NOCHANGE, mod_forumng_discussion::NOCHANGE,
            $fromform->tags);
    redirect('../../discuss.php?' . $discussion->get_link_params(mod_forumng::PARAM_PLAIN));
} else {
    $navigation = array();
    print $out->header();
    // Require JavaScript (form.js).
    $forum->print_form_js();
    $mform->display();

    $PAGE->requires->strings_for_js(array('numberofdiscussions'), 'forumng');

    // Display footer.
    print $out->footer();

}
