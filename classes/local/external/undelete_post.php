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
use mod_forumng_post;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/forumng/mod_forumng.php');
require_once($CFG->dirroot . '/mod/forumng/renderer.php');
require_once($CFG->libdir . '/externallib.php');

/**
 * ForumNG services implementation.
 *
 * @package mod_forumng
 * @copyright 2018 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class undelete_post extends external_api {

    /**
     * Gets function parameter metadata.
     *
     * @return external_function_parameters Parameter info
     */
    public static function undelete_post_parameters() {
        return new external_function_parameters(array(
            'postid' => new external_value(PARAM_INT, 'Post ID')
        ));
    }

    /**
     * Returns description of method result values.
     *
     * @return external_single_structure
     */
    public static function undelete_post_returns() {
        $replystructure = \mod_forumng_utils::get_ipud_webservice_post_reply_structure();

        return new external_single_structure(array(
            'success' => new external_value(PARAM_BOOL, 'Undelete post successfully or not.'),
            'message' => new external_value(PARAM_TEXT, 'Message in case Undelete post failed.'),
            'postinfo' => new external_single_structure($replystructure, '', VALUE_DEFAULT, null)
        ));
    }

    /**
     * Check permission and undelete post.
     *
     * @param $postid integer Post ID which will be undeleted.
     * @return \stdClass
     */
    public static function undelete_post($postid) {
        global $PAGE;

        // Validate web service's parammeters.
        self::validate_parameters(self::undelete_post_parameters(), array(
            'postid' => $postid,
        ));

        // Get info of post being deleted.
        $post = mod_forumng_post::get_from_id($postid, 0, true);

        // Check if current user can delete the post.
        $whynot = '';
        $response = new \stdClass();
        if ($post->can_undelete($whynot)) {
            // Set context to prevent notice message when convert post to object.
            $PAGE->set_context($post->get_forum()->get_context());

            // Delete the post.
            $post->undelete();

            // Get new post from DB to have correct info.
            $post = mod_forumng_post::get_from_id($post->get_id(), 0, true);

            $response->success = true;
            $response->message = '';
            $response->postinfo = \mod_forumng_utils::convert_forumng_post_to_object($post,
                $post->get_parent()->get_id());
            $response->postinfo->content = mod_forumng_output_fragment_formatmessage(array(
                'postid' => $response->postinfo->postid,
                'rawmessage' => $response->postinfo->content
            ));
            $response->postinfo->shortcontent = \mod_forumng_renderer::nice_shorten_text(
                strip_tags($response->postinfo->content, '<img>'), \mod_forumng::IPUD_SHORTEN_LENGTH
            );
        } else {
            // User can't delete post, return reason.
            $response->success = false;
            $response->message = get_string($whynot, 'mod_forumng');
        }

        return $response;
    }
}
