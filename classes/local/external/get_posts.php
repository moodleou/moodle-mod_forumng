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
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use mod_forumng_discussion;
use mod_forumng_utils;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/forumng/mod_forumng.php');
require_once($CFG->dirroot . '/mod/forumng/renderer.php');

/**
 * Forum services implementation.
 *
 * @package mod_forumng
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_posts extends external_api {

    /**
     * Gets function parameter metadata.
     *
     * @return external_function_parameters Parameter info
     */
    public static function get_posts_parameters() {
        return new external_function_parameters(array(
            'discussionid' => new external_value(PARAM_INT, 'Discussion ID'),
            'numbertoshow' => new external_value(PARAM_INT, 'Number of posts to show 0 will return all posts and its replies',
                    VALUE_DEFAULT, 3),
        ));
    }

    /**
     * Returns description of method result values.
     *
     * @return external_multiple_structure
     */
    public static function get_posts_returns() {

        $replystructure = \mod_forumng_utils::get_ipud_webservice_post_reply_structure();

        $repliesstructure = $replystructure;
        $repliesstructure['replies'] = new external_multiple_structure(
            new external_single_structure($replystructure), 'List of replies belong to first level post'
        );

        return new external_single_structure(
            array(
                'totalpost' => new external_value(PARAM_INT, 'Total post belong to this discussion'),
                'data' => new external_multiple_structure(new external_single_structure($repliesstructure))
            )
        );
    }

    /**
     * Get posts belong to diccussion.
     *
     * @param $discussionid
     * @param $numbertoshow
     * @return array
     * @throws \invalid_parameter_exception
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public static function get_posts($discussionid, $numbertoshow) {
        global $PAGE;

        $params = self::validate_parameters(self::get_posts_parameters(), array(
            'discussionid' => $discussionid,
            'numbertoshow' => $numbertoshow
        ));

        $discussion = mod_forumng_discussion::get_from_id($discussionid, 0, 0, false, true);

        // Check that discussion can be viewed [Handles all other permissions].
        $discussion->require_view();

        $posts = $discussion->get_root_post_replies($numbertoshow, true);

        $returndata = array();

        foreach ($posts as $key => $post) {
            $item = mod_forumng_utils::convert_forumng_post_to_object($post, $discussion->get_root_post()->get_id());
            $item->content = mod_forumng_output_fragment_formatmessage(array(
                'postid' => $item->postid,
                'rawmessage' => $item->content
            ));
            $item->shortcontent = \mod_forumng_renderer::nice_shorten_text(strip_tags($item->content, '<img><del>'),
                \mod_forumng::IPUD_SHORTEN_LENGTH);
            $item->replies = array();
            if ($numbertoshow == 0) {
                foreach ($post->get_replies() as $reply) {
                    $reply = mod_forumng_utils::convert_forumng_post_to_object($reply,
                        $discussion->get_root_post()->get_id());
                    $reply->content = mod_forumng_output_fragment_formatmessage(array(
                        'postid' => $reply->postid,
                        'rawmessage' => $reply->content
                    ));
                    $reply->shortcontent = \mod_forumng_renderer::nice_shorten_text(strip_tags($reply->content, '<img><del>'),
                        \mod_forumng::IPUD_SHORTEN_LENGTH);
                    $item->replies[] = $reply;
                }
            }
            $returndata[] = $item;
        }

        return array(
            'totalpost' => $discussion->get_num_posts(),
            'data' => $returndata
        );
    }
}
