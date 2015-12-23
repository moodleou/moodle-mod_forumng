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
 * Script called when you change the value of manual-mark option.
 * @package forumngfeature
 * @subpackage manualmark
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../../../config.php');
require_once('../../mod_forumng.php');

// This script toggles the user's 'automatically mark read' preference.

$cloneid = optional_param('clone', 0, PARAM_INT);
$did = optional_param('d', 0, PARAM_INT);

if ($did) {
    $dis = mod_forumng_discussion::get_from_id($did, $cloneid);
    $forum = $dis->get_forum();
} else {
    $id = required_param('id', PARAM_INT);
    $forum = mod_forumng::get_from_cmid($id, $cloneid);
}

$groupid = mod_forumng::get_activity_group($forum->get_course_module(), false);
$forum->require_view($groupid);

$manualmark = !mod_forumng::mark_read_automatically();
if ($manualmark) {
    unset_user_preference('forumng_manualmark');
} else {
    set_user_preference('forumng_manualmark', 1);
}

redirect($forum->get_url(mod_forumng::PARAM_PLAIN));
