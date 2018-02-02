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
use mod_forumng_discussion;
use mod_forumng_post;
use mod_forumng_utils;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/forumng/mod_forumng.php');
require_once($CFG->dirroot . '/mod/forumng/renderer.php');
require_once($CFG->libdir . '/externallib.php');

/**
 * Forum services implementation.
 *
 * @package mod_forumng
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class expand_post extends external_api {
    /**
     * Gets function parameter metadata.
     *
     * @return external_function_parameters Parameter info
     */
    public static function expand_post_parameters() {
        return new external_function_parameters(array(
            'discussionid' => new external_value(PARAM_INT, 'Discussion ID'),
            'postid' => new external_value(PARAM_INT, 'Post ID'),
        ));
    }

    /**
     * Returns description of method result values.
     *
     * @return external_single_structure
     */
    public static function expand_post_returns() {
        $replystructure = \mod_forumng_utils::get_ipud_webservice_post_reply_structure();

        $repliesstructure = $replystructure;
        $repliesstructure['replies'] = new external_multiple_structure(
            new external_single_structure($replystructure), 'List of replies belong to first level post'
        );

        return new external_single_structure($repliesstructure);
    }

    /**
     * Get post information and its replies.
     *
     * @param $discussionid integer Dicussion ID
     * @param $postid integer Post ID
     * @return \stdClass
     * @throws \invalid_parameter_exception
     * @throws \moodle_exception
     */
    public static function expand_post($discussionid, $postid) {
        $params = self::validate_parameters(self::expand_post_parameters(), array(
            'discussionid' => $discussionid,
            'postid' => $postid
        ));

        $discussion = mod_forumng_discussion::get_from_id($discussionid, 0, 0, false, true);

        $rootpostid = $discussion->get_root_post()->get_id();

        $post = mod_forumng_post::get_from_id($postid, 0, true);

        // Check that discussion can be viewed [Handles all other permissions].
        $discussion->require_view();

        $returndata = mod_forumng_utils::convert_forumng_post_to_object($post, $rootpostid);
        $returndata->content = mod_forumng_output_fragment_formatmessage(array(
            'postid' => $returndata->postid,
            'rawmessage' => $returndata->content
        ));
        $returndata->shortcontent = \mod_forumng_renderer::nice_shorten_text(strip_tags($returndata->content, '<img>'),
            \mod_forumng::IPUD_SHORTEN_LENGTH);
        $returndata->replies = array();
        foreach ($post->get_replies() as $reply) {
            $replyobj = mod_forumng_utils::convert_forumng_post_to_object($reply, $returndata->postid);
            $replyobj->content = mod_forumng_output_fragment_formatmessage(array(
                'postid' => $replyobj->postid,
                'rawmessage' => $replyobj->content
            ));
            $replyobj->shortcontent = \mod_forumng_renderer::nice_shorten_text(strip_tags($replyobj->content, '<img>'),
                \mod_forumng::IPUD_SHORTEN_LENGTH);
            $returndata->replies[] = $replyobj;
        }

        return $returndata;
    }
}
