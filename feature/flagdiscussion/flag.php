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
 * @package forumngfeature_flagdiscussion
 * @copyright 2014 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../../config.php');
require_once($CFG->dirroot . '/mod/forumng/mod_forumng.php');

global $CFG, $USER;

$d = required_param('d', PARAM_INT);
$cloneid = optional_param('clone', 0, PARAM_INT);

// 1 = set flag, 0 = clear it.
$flag = required_param('flag', PARAM_INT);
$back = optional_param('back', '', PARAM_ALPHA);

$discussion = mod_forumng_discussion::get_from_id($d, $cloneid);
$forum = $discussion->get_forum();

// Require that you can see this discussion (etc) and flag them.
$discussion->require_view();
if (!$discussion->can_flag()) {
    print_error('flaggingnotallowed', 'forumngfeature_flag');
}

$discussion->set_flagged($flag, $USER->id);

// Need to redirect back to correct page.
// If successful it should redirect back to the either to the discussion page (default)
// or forum main page (if 'back' param sent).
if (empty($back)) {
    redirect('../../discuss.php?' . $discussion->get_link_params(mod_forumng::PARAM_PLAIN));
} else {
    // Redirect back to view page.
    redirect($forum->get_url(mod_forumng::PARAM_PLAIN));
}
