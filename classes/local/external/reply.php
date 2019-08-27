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

require_once($CFG->dirroot . '/mod/forumng/mod_forumng.php');
require_once($CFG->libdir . '/externallib.php');

/**
 * ForumNG reply webservice.
 *
 * @package mod_forumng
 * @copyright 2018 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class reply extends external_api {

    /**
     * Gets function parameter metadata.
     *
     * @return external_function_parameters Parameter info
     */
    public static function reply_parameters() : external_function_parameters {
        return new external_function_parameters([
            'replyto' => new external_value(PARAM_INT, 'ID of the post being replied to'),
            'subject' => new external_value(PARAM_TEXT, 'Subject of the post', VALUE_DEFAULT, ''),
            'message' => new external_value(PARAM_RAW, 'Message for the post'),
            'draftarea' => new external_value(PARAM_INT, 'Draft area ID for uploaded attachments', VALUE_DEFAULT, 1),
            'editing' => new external_value(PARAM_BOOL, 'Is editing', VALUE_DEFAULT, false)
        ]);
    }

    /**
     * Returns description of method result values.
     *
     * @return external_single_structure
     */
    public static function reply_returns() : external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Successful creation or update, or failure'),
            'post' => new external_value(PARAM_INT, 'New or edited post id'),
            'errormsg' => new external_value(PARAM_RAW, 'Error message for display')
        ]);
    }

    /**
     * Create or edit a reply.
     *
     * Will also allow editing existing discussion in a later development.
     *
     * @param integer $replyto Id of post being replied to.
     * @param string $subject Subject if being changed.
     * @param string $message Message of the post (no inline images etc.)
     * @param int $draftarea Draft area id of attached files, 1 indicates no files attached.
     * @param bool $editing Is editing?
     * @return array See returns above.
     * @throws \moodle_exception
     */
    public static function reply($replyto, $subject, $message, $draftarea, $editing) {
        global $PAGE, $DB;

        // Notes - only creating a new reply is supported at present
        // These items may require more work for the mobile platform:
        // editing.
        // offline.
        // important, mail now, (as moderator).

        $data = [
            'replyto' => $replyto,
            'subject' => $subject,
            'message' => $message,
            'draftarea' => $draftarea,
            'editing' => $editing
        ];

        try {
            $data = (object) self::validate_parameters(self::reply_parameters(), $data);

            $replypost = \mod_forumng_post::get_from_id($data->replyto, 0);
            $replypost->require_view();
            $forum = $replypost->get_forum();

            // Set context to prevent notice message.
            $forumcontext = $forum->get_context(true);
            $PAGE->set_context($forumcontext);

            $fileoptions = array('subdirs' => false, 'maxbytes' => $forum->get_max_bytes(), 'maxfiles' => -1);
            if (!$data->editing) {
                // Sanity check.
                $whynot = '';
                if (!$replypost->can_reply($whynot)) {
                    return ['success' => false, 'post' => 0, 'errormsg' => get_string($whynot, 'mod_forumng')];
                }
                $transaction = $DB->start_delegated_transaction();
                $newpostid = $replypost->reply($data->subject, $data->message, FORMAT_HTML, $data->draftarea > 1);
                // Attachments - note draftarea=1 is used to indicate no attachments.
                if ($data->draftarea > 1) {
                    file_save_draft_area_files($data->draftarea, $forumcontext->id, 'mod_forumng',
                            'attachment', $newpostid, $fileoptions);
                }
                $transaction->allow_commit();
                return ['success' => true, 'post' => $newpostid, 'errormsg' => ''];
            } else {
                // Editing a post (reply).
                // TODO this section is not complete.
                return ['success' => false, 'post' => $data->replyto, 'errormsg' => 'Edit posts not working yet'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'post' => 0, 'errormsg' => $e->getMessage()];
        }
    }
}
