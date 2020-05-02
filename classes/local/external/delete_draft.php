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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/forumng/mod_forumng.php');
require_once($CFG->libdir . '/externallib.php');

/**
 * ForumNG services implementation.
 *
 * @package mod_forumng
 * @copyright 2020 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class delete_draft extends external_api {

    /**
     * Gets function parameter metadata.
     *
     * @return external_function_parameters Parameter info
     */
    public static function delete_draft_parameters() {
        return new external_function_parameters([
            'draftid' => new external_value(PARAM_INT, 'Draft ID')
        ]);
    }

    /**
     * Returns description of method result values.
     *
     * @return external_single_structure
     */
    public static function delete_draft_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Delete draft successfully or not.'),
            'errormsg' => new external_value(PARAM_TEXT, 'Message in case delete draft failed.')
        ]);
    }

    /**
     * Check permission and delete draft.
     *
     * @param $draftid integer Draft ID which will be deleted.
     * @return array See returns above.
     * @throws \moodle_exception
     */
    public static function delete_draft($draftid) {
        global $USER;
        // Validate web service's parammeters.
        self::validate_parameters(self::delete_draft_parameters(), ['draftid' => $draftid]);
        try {
            $draft = \mod_forumng_draft::get_from_id($draftid);
            $forum = \mod_forumng::get_from_id($draft->get_forumng_id(), 0);
            // Check it belongs to current user
            if ($USER->id != $draft->get_user_id()) {
                return ['success' => false, 'errormsg' => get_string('draft_mismatch', 'mod_forumng')];
            }
            $draft->delete($forum->get_context(true));
            return ['success' => true, 'errormsg' => ''];
        } catch (\Exception $e) {
            return ['success' => false, 'errormsg' => $e->getMessage()];
        }
    }
}
