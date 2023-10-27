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

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use mod_forumng_discussion;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/forumng/mod_forumng.php');

/**
 * ForumNG services implementation.
 *
 * @package mod_forumng
 * @copyright 2021 Nhan Vo <nvht2@open.ac.uk>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class delete_discussion extends external_api {

    /**
     * Gets function parameter metadata.
     *
     * @return external_function_parameters Parameter info
     */
    public static function delete_discussion_parameters(): external_function_parameters {
        return new external_function_parameters(array(
                'discussionid' => new external_value(PARAM_INT, 'Discussion ID'),
                'cloneid' => new external_value(PARAM_INT, 'Clone ID', VALUE_DEFAULT, 0),
                'isdeleted' => new external_value(PARAM_BOOL, 'Is deleted discussion'),
        ));
    }
    /**
     * Returns description of method result values.
     *
     * @return external_single_structure
     */
    public static function delete_discussion_returns() : external_single_structure {
        return new external_single_structure([
                'result' => new external_value(PARAM_BOOL, 'Result'),
                'errormsg' => new external_value(PARAM_RAW, 'Any error message for display'),
        ]);
    }
    public static function delete_discussion($discussionid, $cloneid, $isdeleted) {
        $res = new \stdClass();
        try {
            $data = [
                    'cloneid' => $cloneid,
                    'discussionid' => $discussionid,
                    'isdeleted' => $isdeleted,
            ];
            $data = self::validate_parameters(self::delete_discussion_parameters(), $data);

            $discussion = mod_forumng_discussion::get_from_id($data['discussionid'], $data['cloneid']);
            if (!$discussion->can_delete($whynot)) {
                $discussion->require_edit();
            }
            if ($whynot) {
                throw new \Exception(get_string($whynot, 'mod_forumng'));
            }

            if ($isdeleted) {
                $discussion->undelete();
            } else {
                $discussion->delete();
            }

            $res->result = true;
            $res->errormsg = '';
        } catch (\Exception $e) {
            $res->result = false;
            $res->errormsg = $e->getMessage();
        }

        return $res;
    }
}
