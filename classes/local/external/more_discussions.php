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
use external_multiple_structure;
use external_single_structure;
use external_value;
use mod_forumng\output\mobile;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/mod/forumng/mod_forumng.php');

/**
 * ForumNG services implementation.
 *
 * @package mod_forumng
 * @copyright 2018 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class more_discussions extends external_api {

    /**
     * Gets function parameter metadata.
     *
     * @return external_function_parameters Parameter info
     */
    public static function more_discussions_parameters() : external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID'),
            'groupid' => new external_value(PARAM_INT, 'Group ID'),
            'pageno' => new external_value(PARAM_INT, 'Page number')
        ]);
    }

    /**
     * Returns description of method result values.
     *
     * @return external_multiple_structure
     */
    public static function more_discussions_returns() : external_multiple_structure {
        return new external_multiple_structure(
            new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Discussion ID'),
                'subject' => new external_value(PARAM_RAW, 'Discussion subject'),
                'unread' => new external_value(PARAM_TEXT, 'Number of unread posts'),
                'lastpost' => new external_value(PARAM_TEXT, 'Last post information, datetime and name'),
                'lastpostuserid' => new external_value(PARAM_INT, 'Last post user ID or 0'),
                'classes' => new external_value(PARAM_TEXT, 'CSS classes to add to the row'),
                'decorators' => new external_multiple_structure(
                    new external_single_structure([
                        'src' => new external_value(PARAM_URL, 'Decorator icon url'),
                        'alt' => new external_value(PARAM_TEXT, 'Decorator description')
                    ], 'Decorator', VALUE_OPTIONAL)
                )
            ], 'Discussion'), 'List of discussions'
        );
    }

    /**
     * Returns an array of discussions for a forum user that will fill the
     * next viewable page.
     *
     * @param int $cmid
     * @param int $groupid
     * @param int $pageno
     * @return array of discussions
     * @throws \moodle_exception
     */
    public static function more_discussions($cmid, $groupid, $pageno) : array {
        $data = [
            'cmid' => $cmid,
            'groupid' => $groupid,
            'pageno' => $pageno
        ];

        $data = self::validate_parameters(self::more_discussions_parameters(), $data);

        $forumng = \mod_forumng::get_from_cmid($data['cmid'], \mod_forumng::CLONE_DIRECT);
        $forumng->require_view($groupid);
        $context = $forumng->get_context();
        self::validate_context($context);

        $canviewhidden = $forumng->can_view_hidden();
        $discussionlist = $forumng->get_discussion_list($data['groupid'], $canviewhidden, $data['pageno']);
        $sticky = $discussionlist->get_sticky_discussions();
        $normal = $discussionlist->get_normal_discussions();
        $discussions = [];
        mobile::prep_discussions($forumng, $sticky, $discussions);
        mobile::prep_discussions($forumng, $normal, $discussions);

        return $discussions;
    }
}
