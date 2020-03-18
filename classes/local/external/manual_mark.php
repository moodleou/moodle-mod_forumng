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
 * ForumNG Manual mark setting.
 *
 * @package mod_forumng
 * @copyright 2020 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manual_mark extends external_api {

    /**
     * Gets function parameter metadata.
     *
     * @return external_function_parameters Parameter info
     */
    public static function manual_mark_parameters() : external_function_parameters {
        return new external_function_parameters([
                'cmid' => new external_value(PARAM_INT, 'Course module Forumng ID', VALUE_DEFAULT, 0),
                'cloneid' => new external_value(PARAM_INT, 'Clone ID', VALUE_DEFAULT, 0),
                'value' => new external_value(PARAM_INT, 'Clone ID', VALUE_DEFAULT, 0)
        ]);
    }

    /**
     * Returns description of method result values.
     *
     * @return external_single_structure
     */
    public static function manual_mark_returns() : external_single_structure {
        return new external_single_structure([
                'result' => new external_value(PARAM_BOOL, 'Manual or auto'),
                'errormsg' => new external_value(PARAM_RAW, 'Any error message for display')
        ]);
    }

    /**
     * Returns an array with a success indicator and error message (hopefully empty).
     *
     *  Enable Auto or Manually mark read all post in forum
     *
     * @param int $cmid course module id
     * @param int $cloneid Clone id
     * @param string $value Clone id
     * @return array
     */
    public static function manual_mark($cmid, $cloneid, $value) : array {
        try {
            $data = [
                    'cmid' => $cmid,
                    'cloneid' => $cloneid,
                    'value' => $value,
            ];
            $data = self::validate_parameters(self::manual_mark_parameters(), $data);
            if ($data['cmid'] == 0) {
                throw new \coding_exception('Must supply only one of forumng course module id');
            }
            $forum = mod_forumng::get_from_cmid($data['cmid'], $data['cloneid']);
            $groupid = mod_forumng::get_activity_group($forum->get_course_module(), false);
            if (!defined('PHPUNIT_TEST')) {
                // There are some issue when we using the require_course_login in the require view
                // For unit test because it want to change the context causing exception.
                $forum->require_view($groupid);
            }
            if ($value) {
                set_user_preference('forumng_manualmark', $value);
            } else {
                unset_user_preference('forumng_manualmark');
            }
            return ['result' => $value, 'errormsg' => ''];
        } catch (\Exception $e) {
            return ['result' => '', 'errormsg' => $e->getMessage()];
        }
    }
}
