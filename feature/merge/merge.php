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
 * Either start the merge process, or finish it (depending on 'stage' param).
 * @package forumngfeature
 * @subpackage merge
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../../../config.php');
require_once($CFG->dirroot . '/mod/forumng/mod_forumng.php');

$d = required_param('d', PARAM_INT);
$pageparams = array('d'=>$d);
$cloneid = optional_param('clone', 0, PARAM_INT);
if ($cloneid) {
    $pageparams['clone'] = $cloneid;
}
$stage = optional_param('stage', 1, PARAM_INT);
if ($stage != 1) {
    $pageparams['stage'] = $stage;
}

$discussion = mod_forumng_discussion::get_from_id($d, $cloneid);
$forum = $discussion->get_forum();
$cm = $forum->get_course_module();
$course = $forum->get_course();

// Set up page
$pagename = get_string('merge', 'forumngfeature_merge');
$url = new moodle_url('/mod/forumng/feature/merge/merge.php', $pageparams);
$out = $discussion->init_page($url, $pagename);

// Require that you can see this discussion (etc) and merge them
$discussion->require_view();
if (!$discussion->can_split($whynot)) {
    print_error($whynot, 'forumng');
}

if ($stage == 2) {
    if (!confirm_sesskey()) {
        print_error('invalidsesskey');
    }

    if (!isset($_POST['cancel'])) {
        // Get source discussion and check permissions
        $sourcediscussion = mod_forumng_discussion::get_from_id(
                $SESSION->forumng_mergefrom->discussionid,
                $SESSION->forumng_mergefrom->cloneid);
        $sourcediscussion->require_view();
        if (!$sourcediscussion->can_split($whynot)) {
            print_error($whynot, 'forumng');
        }
        if ($sourcediscussion->get_forum()->get_id() != $discussion->get_forum()->get_id()) {
            print_error('error_wrongforum', 'forumngfeature_merge');
        }

        // Do actual merge
        $sourcediscussion->merge_into($discussion);
    }

    unset($SESSION->forumng_mergefrom);
    redirect('../../discuss.php?' . $discussion->get_link_params(mod_forumng::PARAM_PLAIN));
}

// Create form
require_once('merge_form.php');
$mform = new mod_forumng_merge_form('merge.php', array('d'=>$d, 'clone'=>$cloneid));

if ($mform->is_cancelled()) {
    redirect('../../discuss.php?' . $discussion->get_link_params(mod_forumng::PARAM_PLAIN));
} else if (($fromform = $mform->get_data(false)) ||
    get_user_preferences('forumng_hidemergehelp', 0)) {
    // Remember in session that the discussion is being merged
    $SESSION->forumng_mergefrom = new stdClass;
    $SESSION->forumng_mergefrom->discussionid = $d;
    $SESSION->forumng_mergefrom->forumid = $forum->get_id();
    $SESSION->forumng_mergefrom->cloneid = $cloneid;

    if (!empty($fromform->hidelater)) {
        set_user_preference('forumng_hidemergehelp', 1);
    }

    // Redirect back to view page
    redirect($forum->get_url(mod_forumng::PARAM_PLAIN));
}

// Work out navigation for header
print $out->header();

// Print form
$mform->display();

print $out->footer();
