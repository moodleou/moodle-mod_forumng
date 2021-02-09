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

namespace mod_forumng\local\external;

use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use mod_forumng_discussion;

require_once($CFG->dirroot . '/mod/forumng/mod_forumng.php');
require_once($CFG->dirroot . '/mod/forumng/renderer.php');

/**
 * Forum services implementation to get discussion subject from discussionid.
 *
 * @package mod_forumng
 * @copyright 2021 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_discussion extends external_api {
    /**
     * Gets function parameter metadata.
     *
     * @return external_function_parameters Parameter info
     */
    public static function get_discussion_parameters() {
        return new external_function_parameters(array(
            'discussionid' => new external_value(PARAM_INT, 'Discussion ID')
        ));
    }

    /**
     * Returns description of method result values.
     *
     * @return external_single_structure
     */
    public static function get_discussion_returns() : external_single_structure {
        return new external_single_structure([
            'subject' => new external_value(PARAM_RAW, 'Discussion subject'),
        ]);
    }

    /**
     * Get discussion subject from discussion id.
     *
     * @param $discussionid
     * @return object
     * @throws \invalid_parameter_exception
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public static function get_discussion($discussionid) {
        $params = self::validate_parameters(self::get_discussion_parameters(), array(
            'discussionid' => $discussionid
        ));
        $discussion = mod_forumng_discussion::get_from_id($params['discussionid'], 0, 0, false, true);

        // Check that discussion can be viewed [Handles all other permissions].
        $discussion->require_view();

        return [
            'subject' => $discussion->get_subject()
        ];
    }
}
