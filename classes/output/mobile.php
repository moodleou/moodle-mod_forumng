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
 * Mobile plugin.
 *
 * @package mod_forumng
 * @copyright 2018 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_forumng\output;

defined('MOODLE_INTERNAL') || die();

use context_module;

/**
 * Mobile output class for forumng
 *
 * @package mod_forumng
 * @copyright 2018 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mobile {

    /**
     * Returns the forumng view for the mobile app.
     *
     * @param array $args Arguments from tool_mobile_get_content WS
     * @return array HTML, javascript and otherdata
     */
    public static function forumng_view(array $args) {
        global $OUTPUT, $DB;

        $args = (object) $args;
        $cm = get_coursemodule_from_id('forumng', $args->cmid, 0, false, MUST_EXIST);

        require_login($cm->course->id, false, null, true, true);
        $context = context_module::instance($cm->id);
        require_capability('mod/forumng:view', $context);

        $forumng = $DB->get_record('forumng', array('id' => $cm->instance));
        $forumng->name = format_string($forumng->name);
        // Only introduction is shown on the forumng/view page, intro is used on the Forums area page and studyplanner.
        list($forumng->introduction, $forumng->introductionformat) =
                external_format_text($forumng->introduction, $forumng->introductionformat, $context->id, 'mod_forumng',
                    'introduction');
        $data = [
            'forumng' => $forumng,
            'cmid' => $cm->id,
            'courseid' => $cm->course->id
        ];
        $html = $OUTPUT->render_from_template('mod_forumng/mobile_discussions_page', $data);

        return [
            'templates' => [
                [
                    'id' => 'main',
                    'html' => $html,
                ],
            ],
            'javascript' => '',
            'otherdata' => '',
            'files' => []
        ];
    }
}
