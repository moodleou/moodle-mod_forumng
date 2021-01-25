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
use mod_forumng;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/mod/forumng/mod_forumng.php');

/**
 * ForumNG Mark all post read webservice.
 *
 * @package mod_forumng
 * @copyright 2020 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class mark_all_post_read extends external_api {
    /**
     * Gets function parameter metadata.
     *
     * @return external_function_parameters Parameter info
     */
    public static function mark_all_post_read_parameters() : external_function_parameters {
        return new external_function_parameters([
                'cmid' => new external_value(PARAM_INT, 'Course module Forumng ID', VALUE_DEFAULT, 0),
                'cloneid' => new external_value(PARAM_INT, 'Clone ID', VALUE_DEFAULT, 0),
                'groupid' => new external_value(PARAM_INT, 'group ID', VALUE_DEFAULT, -1),
                'discussionid' => new external_value(PARAM_INT, 'Discussion Id', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Returns description of method result values.
     *
     * @return external_single_structure
     */
    public static function mark_all_post_read_returns() : external_single_structure {
        return new external_single_structure([
                'result' => new external_value(PARAM_BOOL, 'Result'),
                'errormsg' => new external_value(PARAM_RAW, 'Any error message for display')
        ]);
    }

    /**
     * Returns an array with a success indicator and error message (hopefully empty).
     *
     *  Mark all post read for fourm.
     *
     * @param int $cmid course module id
     * @param int $cloneid Clone id
     * @param int $groupid Clone id
     * @param int $discussionid Discussion id
     * @return array
     */
    public static function mark_all_post_read($cmid, $cloneid, $groupid, $discussionid = 0) : array {
        try {
            $data = [
                    'cmid' => $cmid,
                    'cloneid' => $cloneid,
                    'groupid' => $groupid,
                    'discussionid' => $discussionid
            ];
            $data = self::validate_parameters(self::mark_all_post_read_parameters(), $data);
            if ($data['cmid'] == 0) {
                throw new \coding_exception('Must supply only one of forumng course module id');
            }
            $forum = mod_forumng::get_from_cmid($cmid, $cloneid);
            if ($groupid == 0) {
                // Just the distinction between 0 and null.
                $groupid = mod_forumng::ALL_GROUPS;
            } else if ($groupid == -1) {
                $groupid = mod_forumng::NO_GROUPS;
            }

            $forum->require_view($groupid);
            if (!$forum->can_mark_read()) {
                throw new \moodle_exception('error_cannotmarkread', 'forumng');
            }
            if (!$discussionid) {
                $forum->mark_read($groupid);
            } else {
                $discussion = \mod_forumng_discussion::get_from_id($discussionid, $cloneid);
                $discussion->require_view();
                if (!$discussion->get_forum()->can_mark_read()) {
                    print_error('error_cannotmarkread', 'forumng');
                }
                $discussion->mark_read();
            }
            return ['result' => true, 'errormsg' => ''];
        } catch (\Exception $e) {
            return ['result' => false, 'errormsg' => $e->getMessage()];
        }
    }
}
