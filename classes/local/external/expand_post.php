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
        $replystructure = array(
            'postid' => new external_value(PARAM_INT, 'Post ID'),
            'discussionid' => new external_value(PARAM_INT, 'Discussion ID'),
            'parentid' => new external_value(PARAM_INT, 'Parent post ID'),
            'title' => new external_value(PARAM_TEXT, 'Post title'),
            'numberofreply' => new external_value(PARAM_INT, 'Number of reply for this post'),
            'authorname' => new external_value(PARAM_TEXT, 'Author of this post'),
            'ismoderator' => new external_value(PARAM_BOOL, 'Is moderator'),
            'authorprofile' => new external_value(PARAM_TEXT, 'Author profile URL'),
            'authoravatar' => new external_value(PARAM_TEXT, 'Author avatar URL'),
            'posttime' => new external_value(PARAM_RAW, 'Post create time'),
            'lastedittime' => new external_value(PARAM_RAW, 'Post last edit time'),
            'deletedtime' => new external_value(PARAM_RAW, 'Post edited time, if not deleted return 0'),
            'content' => new external_value(PARAM_RAW, 'Post content'),
            'attachmenturls' => new external_multiple_structure(
                new external_single_structure(array(
                    'name' => new external_value(PARAM_TEXT, 'Name of attachment'),
                    'url' => new external_value(PARAM_URL, 'URL of attachment')
                ), 'Attachment detail'), 'List of attachment'
            ),
            'isunread' => new external_value(PARAM_BOOL, 'Is unread post'),
            'canedit' => new external_value(PARAM_TEXT, 'Can edit this post or not, if not return the reason.'),
            'candelete' => new external_value(PARAM_TEXT, 'Can delete this post or not, if not return the reason.'),
            'canreport' => new external_value(PARAM_TEXT, 'Can report this post or not, if not return the reason.'),
            'canundelete' => new external_value(PARAM_TEXT, 'Can undelete this post or not, if not return the reason.'),
            'canviewdeleted' => new external_value(PARAM_TEXT, 'Can view deleted post, if not return the reason.'),
        );

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
        $returndata->shortcontent = \mod_forumng_renderer::nice_shorten_text($returndata->content);
        $returndata->replies = array();
        foreach ($post->get_replies() as $reply) {
            $replyobj = mod_forumng_utils::convert_forumng_post_to_object($reply, $returndata->postid);
            $replyobj->content = mod_forumng_output_fragment_formatmessage(array(
                'postid' => $replyobj->postid,
                'rawmessage' => $replyobj->content
            ));
            $replyobj->shortcontent = \mod_forumng_renderer::nice_shorten_text($replyobj->content);
            $returndata->replies[] = $replyobj;
        }

        return $returndata;
    }
}
