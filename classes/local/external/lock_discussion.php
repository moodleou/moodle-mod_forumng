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
class lock_discussion extends external_api {

    /**
     * Gets function parameter metadata.
     *
     * @return external_function_parameters Parameter info
     */
    public static function lock_discussion_parameters() : external_function_parameters {
        return new external_function_parameters([
            'discussionid' => new external_value(PARAM_INT, 'Discussion ID'),
            'cloneid' => new external_value(PARAM_INT, 'Clone ID', VALUE_DEFAULT, 0),
            'subject' => new external_value(PARAM_TEXT, 'Subject of the post'),
            'message' => new external_value(PARAM_RAW, 'Message for the post'),
            'draftarea' => new external_value(PARAM_INT, 'Draft area ID for uploaded attachments', VALUE_DEFAULT, 1),
            'postas' => new external_value(PARAM_INT, 'Post as', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Returns description of method result values.
     *
     * @return external_single_structure
     */
    public static function lock_discussion_returns() : external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Successful creation or update, or failure'),
            'post' => new external_value(PARAM_INT, 'New or edited post id'),
            'errormsg' => new external_value(PARAM_RAW, 'Any error message for display')
        ]);
    }

    /**
     * Returns an array with a success indicator and error message (hopefully empty).
     *
     *  Lock discussion in forum
     *
     * @param int $discussionid discussion id
     * @param int $cloneid Clone id
     * @param string $subject Subject if being changed.
     * @param string $message Message of the post (no inline images etc.)
     * @param int $draftarea Draft area id of attached files, 1 indicates no files attached.
     * @param int $postas Post as.
     * @return array
     */
    public static function lock_discussion($discussionid, $cloneid, $subject, $message, $draftarea, $postas) : array {
        global $PAGE, $DB;

        $data = [
            'discussionid' => $discussionid,
            'cloneid' => $cloneid,
            'subject' => $subject,
            'message' => $message,
            'draftarea' => $draftarea,
            'postas' => $postas,
        ];

        try {
            $data = (object)self::validate_parameters(self::lock_discussion_parameters(), $data);

            $discussion = \mod_forumng_discussion::get_from_id($data->discussionid, $data->cloneid);
            $forum = $discussion->get_forum();
            $discussion->require_edit();
            if ($discussion->is_locked()) {
                return ['success' => false, 'errormsg' => get_string('edit_locked', 'mod_forumng')];
            }

            // Set context to prevent notice message.
            $forumcontext = $forum->get_context(true);
            $PAGE->set_context($forumcontext);

            $fileoptions = ['subdirs' => false, 'maxbytes' => $forum->get_max_bytes(), 'maxfiles' => -1];

            $transaction = $DB->start_delegated_transaction();
            $postid = $discussion->lock($data->subject, $data->message, FORMAT_HTML,
                $data->draftarea > 1, false, 0, true, $data->postas);
            // Attachments - note draftarea=1 is used to indicate no attachments.
            if ($data->draftarea > 1) {
                file_save_draft_area_files($data->draftarea, $forumcontext->id, 'mod_forumng',
                    'attachment', $postid, $fileoptions);
            }
            $transaction->allow_commit();
            return ['success' => true, 'post' => $postid, 'errormsg' => ''];

        } catch (\Exception $e) {
            return ['success' => false, 'post' => 0, 'errormsg' => $e->getMessage()];
        }
    }
}
