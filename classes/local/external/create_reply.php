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
use mod_forumng_editpost_form;
use mod_forumng_post;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/forumng/mod_forumng.php');
require_once($CFG->dirroot . '/mod/forumng/editpost_form.php');
require_once($CFG->dirroot . '/mod/forumng/renderer.php');
require_once($CFG->libdir . '/externallib.php');

/**
 * ForumNG services implementation.
 *
 * @package mod_forumng
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class create_reply extends external_api {

    /**
     * Gets function parameter metadata.
     *
     * @return external_function_parameters Parameter info
     */
    public static function create_reply_parameters() {
        return new external_function_parameters(array(
            'replyto' => new external_value(PARAM_INT, 'Post ID to to reply.'),
            'subject' => new external_value(PARAM_TEXT, 'Subject of the post'),
            'message' => new external_function_parameters(array(
                'text' => new external_value(PARAM_RAW, 'Message of the post'),
                'format' => new external_value(PARAM_TEXT, 'Format of the message'),
                'itemid' => new external_value(PARAM_TEXT, 'Item ID', VALUE_DEFAULT),
            ))
        ));
    }

    /**
     * Returns description of method result values.
     *
     * @return external_single_structure
     */
    public static function create_reply_returns() {
        $replystructure = \mod_forumng_utils::get_ipud_webservice_post_reply_structure();

        return new external_single_structure($replystructure);
    }

    /**
     * Check permission and create reply for post.
     *
     * @param $replyto integer Reply post ID.
     * @param $subject string Subject of the post.
     * @param $message string Message of the post.
     * @return \stdClass
     * @throws \moodle_exception
     */
    public static function create_reply($replyto, $subject, $message) {
        global $PAGE, $DB, $USER;

        $data = array(
            'replyto' => $replyto,
            'subject' => $subject,
            'message' => $message
        );

        // Validate web service's parammeters.
        $data = self::validate_parameters(self::create_reply_parameters(), $data);

        // Get info of the post being reply to.
        $replypost = mod_forumng_post::get_from_id($replyto, 0);
        $forum = $replypost->get_forum();

        // Set context to prevent notice message.
        $forumcontext = $forum->get_context();
        $PAGE->set_context($forumcontext);

        // Check if current user can reply to this post.
        $whynot = '';
        if (!$replypost->can_reply($whynot)) {
            throw new \moodle_exception($whynot, 'forumng');
        }

        // Insert this field to be able to call "get_data".
        $data['_qf__mod_forumng_editpost_form'] = 1;

        // Assign data to edit post form, this will also check for session key.
        $mform = new mod_forumng_editpost_form('', array(
            'params' => array('replyto' => $replyto),
            'forum' => $forum,
            'ispost' => true,
            'islock' => false,
            'edit' => false,
            'post' => $replypost,
            'isroot' => false,
            'isdiscussion' => false,
        ), 'post', '', null, true, $data);

        // Validate form data.
        $validatedata = $mform->get_data();

        if ($validatedata) {
            // Form validation success, create new reply.
            $newpostid = $replypost->reply($validatedata->subject, $validatedata->message['text'],
                $validatedata->message['format']);

            // The itemid is not present when using text-only editor.
            if (!empty($validatedata->message['itemid'])) {
                $fileoptions = array('subdirs' => false, 'maxbytes' => $forum->get_max_bytes());
                $newtext = file_save_draft_area_files($validatedata->message['itemid'],
                    $forumcontext->id, 'mod_forumng', 'message', $newpostid, $fileoptions,
                    $validatedata->message['text']);

                if ($newtext !== $validatedata->message['text']) {
                    \mod_forumng_post::update_message_for_files($newpostid, $newtext);
                }
            }

            // Get reply detail information and then return.
            $newpost = mod_forumng_post::get_from_id($newpostid, 0, true);
            $postobj = \mod_forumng_utils::convert_forumng_post_to_object($newpost, $replyto);
            // Format content message to get the real content url and format.
            $postobj->content = mod_forumng_output_fragment_formatmessage(array(
                'postid' => $postobj->postid,
                'rawmessage' => $postobj->content
            ));
            $postobj->shortcontent = \mod_forumng_renderer::nice_shorten_text(strip_tags($postobj->content, '<img>'),
                \mod_forumng::IPUD_SHORTEN_LENGTH);

            // Clear the atto editor saved message.
            $DB->delete_records('editor_atto_autosave', array(
                'userid' => $USER->id,
                'elementid' => 'id_message' . $replypost->get_id(),
                'contextid' => $forumcontext->id
            ));

            return $postobj;
        } else {
            // Form validation error, return info to user as json.
            $errors = array_merge($mform->validation($data, array()), $mform->get_form_errors());
            throw new \moodle_exception('error_form_validation', 'mod_forumng', '', json_encode($errors));
        }
    }
}
