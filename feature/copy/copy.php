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
 * Copy script. Copies forum reference into session, after confirm form.
 * @package forumngfeature
 * @subpackage copy
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../../../config.php');
require_once($CFG->dirroot . '/mod/forumng/mod_forumng.php');

$d = required_param('d', PARAM_INT);
$pageparams = array('d' => $d);
$cloneid = optional_param('clone', 0, PARAM_INT);
if ($cloneid) {
    $pageparams['clone'] = $cloneid;
}

$discussion = mod_forumng_discussion::get_from_id($d, $cloneid);
$forum = $discussion->get_forum();
$cm = $forum->get_course_module();
$course = $forum->get_course();

// Require that you can see this discussion (etc) and copy them
$discussion->require_view();
require_capability('mod/forumng:copydiscussion',
    $discussion->get_forum()->get_context());

$pagename = get_string('copy_title', 'forumngfeature_copy');
$pageurl = new moodle_url('/mod/forumng/feature/copy/copy.php', $pageparams);
$out = $discussion->init_page($pageurl, $pagename);

// Create form
require_once('forumngfeature_copy_form.php');
$mform = new forumngfeature_copy_form('copy.php', array('d'=>$d, 'clone'=>$cloneid));

if ($mform->is_cancelled()) {
    redirect('../../discuss.php?' . $discussion->get_link_params(mod_forumng::PARAM_PLAIN));
} else if (($fromform = $mform->get_data(false)) ||
    get_user_preferences('forumng_hidecopyhelp', 0)) {
    // Remember in session that the discussion is being copied
    $SESSION->forumng_copyfrom = $d;
    $SESSION->forumng_copyfromclone = $cloneid;
    $SESSION->forumng_copyfromforum = $forum->get_id();
    if ($forum->get_group_mode() != NOGROUPS) {
        $SESSION->forumng_copyfromgroup = $discussion->get_group_id();
    } else {
        $SESSION->forumng_copyfromgroup = mod_forumng::NO_GROUPS;
    }

    if (!empty($fromform->hidelater)) {
        set_user_preference('forumng_hidecopyhelp', 1);
    }
    // Redirect back to view page
    redirect($forum->get_url(mod_forumng::PARAM_PLAIN));
}

print $out->header();

// Print form
$mform->display();
print $out->footer();
