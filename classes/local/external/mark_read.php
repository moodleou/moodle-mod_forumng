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

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/mod/forumng/mod_forumng.php');

/**
 * ForumNG mark post or discussion as read service implementation.
 *
 * @package mod_forumng
 * @copyright 2018 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mark_read extends external_api {

    /**
     * Gets function parameter metadata.
     *
     * @return external_function_parameters Parameter info
     */
    public static function mark_read_parameters() : external_function_parameters {
        return new external_function_parameters([
            'discussion' => new external_value(PARAM_INT, 'Discussion ID', VALUE_DEFAULT, 0),
            'post' => new external_value(PARAM_INT, 'Post ID', VALUE_DEFAULT, 0)
        ]);
    }

    /**
     * Returns description of method result values.
     *
     * @return external_single_structure
     */
    public static function mark_read_returns() : external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether successful'),
            'errormsg' => new external_value(PARAM_RAW, 'Any error message for display')
        ]);
    }

    /**
     * Returns an array with a success indicator and error message (hopefully empty).
     *
     * Either marks all posts in a discussion as read, or marks one post as read.
     *
     * @param int $discussion Discussion id
     * @param int $post Post id
     * @return array
     */
    public static function mark_read(int $discussion, int $post) : array {
        $data = [
            'discussion' => $discussion,
            'post' => $post
        ];
        $data = self::validate_parameters(self::mark_read_parameters(), $data);
        if (($data['discussion'] == 0 && $data['post'] == 0) ||
                ($data['discussion'] != 0 && $data['post'] != 0)) {
            throw new \coding_exception('Must supply only one of discussionid or postid');
        }

        try {
            if ($data['post'] == 0) {
                // Mark discussion as read.
                $discussion = \mod_forumng_discussion::get_from_id($data['discussion'], 0);
                $forum = $discussion->get_forum();
                if (!$forum->can_mark_read()) {
                    return ['success' => false, 'errormsg' => get_string('error_cannotmarkread', 'forumng')];
                }
                $discussion->require_view();
                $discussion->mark_read();
            } else {
                // Mark a post as read.
                $post = \mod_forumng_post::get_from_id($data['post'], 0);
                $forum = $post->get_forum();
                if (!$forum->can_mark_read()) {
                    return ['success' => false, 'errormsg' => get_string('error_cannotmarkread', 'forumng')];
                }
                $post->require_view();
                $post->mark_read(); // Handles posts already marked as read.
            }
            return ['success' => true, 'errormsg' => ''];
        } catch (\Exception $e) {
            return ['success' => false, 'errormsg' => $e->getMessage()];
        }
    }
}
