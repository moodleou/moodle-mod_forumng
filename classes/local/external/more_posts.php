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
class more_posts extends external_api {

    /**
     * Gets function parameter metadata.
     *
     * @return external_function_parameters Parameter info
     */
    public static function more_posts_parameters() : external_function_parameters {
        return new external_function_parameters([
            'discussionid' => new external_value(PARAM_INT, 'Discussion ID'),
            'from' => new external_value(PARAM_INT, 'Show posts from this number')
        ]);
    }

    /**
     * Returns description of method result values.
     *
     * @return external_single_structure
     */
    public static function more_posts_returns() : external_multiple_structure {
        return new external_multiple_structure(
            new external_single_structure([
                'postid' => new external_value(PARAM_INT, 'post ID'),
                'subject' => new external_value(PARAM_RAW, 'post subject'),
                'startedby' => new external_value(PARAM_TEXT, 'Name of person replying'),
                'startedbyurl' => new external_value(PARAM_TEXT, 'URL for image of person replying'),
                'message' => new external_value(PARAM_RAW, 'Text or html'),
                'starteddate' => new external_value(PARAM_TEXT, 'Date of creation of reply'),
                'attachments' => new external_multiple_structure(
                    new external_single_structure([
                        'name' => new external_value(PARAM_TEXT, 'Attachment name'),
                        'url' => new external_value(PARAM_TEXT, 'URL for attachment'),
                    ], 'Attachments', VALUE_OPTIONAL)
                ),
                'isimportant' => new external_value(PARAM_BOOL, 'Whether this post is marked important'),
                'isflagged' => new external_value(PARAM_BOOL, 'Whether this post is flagged'),
                'isunread' => new external_value(PARAM_BOOL, 'Whether this post is unread'),
                'isexpanded' => new external_value(PARAM_BOOL, 'Whether this post is displayed expanded'),
                'canreply' => new external_value(PARAM_BOOL, 'Whether the user can reply to this post'),
                'hasreplies' => new external_value(PARAM_BOOL, 'Whether there are any replies to this post'),
                'subreplies' => new external_multiple_structure(
                    new external_single_structure([
                        'postid' => new external_value(PARAM_INT, 'post ID'),
                        'subject' => new external_value(PARAM_RAW, 'post subject'),
                        'startedby' => new external_value(PARAM_TEXT, 'Name of person replying'),
                        'startedbyurl' => new external_value(PARAM_TEXT, 'URL for image of person replying'),
                        'message' => new external_value(PARAM_RAW, 'Text or html'),
                        'starteddate' => new external_value(PARAM_TEXT, 'Date of creation of reply'),
                        'attachments' => new external_multiple_structure(
                            new external_single_structure([
                                'name' => new external_value(PARAM_TEXT, 'Attachment name'),
                                'url' => new external_value(PARAM_TEXT, 'URL for attachment'),
                            ], 'Attachments', VALUE_OPTIONAL)
                        ),
                        'isimportant' => new external_value(PARAM_BOOL, 'Whether this post is marked important'),
                        'isflagged' => new external_value(PARAM_BOOL, 'Whether this post is flagged'),
                        'isunread' => new external_value(PARAM_BOOL, 'Whether this post is unread'),
                        'isexpanded' => new external_value(PARAM_BOOL, 'Whether this post is displayed expanded'),
                        'canreply' => new external_value(PARAM_BOOL, 'Whether the user can reply to this post')
                    ], 'Subreply', VALUE_OPTIONAL)
                ),
            ], 'Posts'), 'List of replies'
        );
    }

    /**
     * Returns an array of more posts for a discussion.
     *
     * @param int $discussionid
     * @param int $from This identifies the start post of a chunk of posts to be returned
     * @return array of posts
     * @throws \moodle_exception
     */
    public static function more_posts(int $discussionid, int $from) : array {
        $data = [
            'discussionid' => $discussionid,
            'from' => $from
        ];
        $data = self::validate_parameters(self::more_posts_parameters(), $data);

        $discussion = \mod_forumng_discussion::get_from_id($data['discussionid'], \mod_forumng::CLONE_DIRECT);
        $discussion->require_view();

        return mobile::get_more_posts($discussion, $data['from']);
    }
}
