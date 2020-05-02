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
 * ForumNG add a new discussion webservice.
 *
 * @package mod_forumng
 * @copyright 2020 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class add_draft extends external_api {

    /**
     * Gets function parameter metadata.
     *
     * @return external_function_parameters Parameter info
     */
    public static function add_draft_parameters() : external_function_parameters {
        return new external_function_parameters([
            'forum' => new external_value(PARAM_INT, 'Forumng ID'),
            'draft' => new external_value(PARAM_INT, 'Draft ID', VALUE_DEFAULT, 0),
            'group' => new external_value(PARAM_INT, 'Group ID', VALUE_DEFAULT, 0),
            'replyto' => new external_value(PARAM_INT, 'Post ID to to reply.', VALUE_DEFAULT, 0),
            'subject' => new external_value(PARAM_TEXT, 'Subject of the post'),
            'message' => new external_value(PARAM_RAW, 'Root message for discussion'),
            'draftarea' => new external_value(PARAM_INT, 'Draft area ID for uploaded attachments', VALUE_DEFAULT, 1),
            'showfrom' => new external_value(PARAM_INT, 'Show from date', VALUE_DEFAULT, 0),
            'showsticky' => new external_value(PARAM_INT, 'True/False value for sticky discussion', VALUE_DEFAULT, 0),
            'postas' => new external_value(PARAM_INT, 'Post as', VALUE_DEFAULT, 0),
            'mailnow' => new external_value(PARAM_BOOL, 'Mail now?', VALUE_DEFAULT, false),
            'setimportant' => new external_value(PARAM_BOOL, 'The flag for important', VALUE_DEFAULT, false),
        ]);
    }

    /**
     * Returns description of method result values.
     *
     * @return external_single_structure
     */
    public static function add_draft_returns() : external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Successful creation or update, or failure'),
            'draft' => new external_value(PARAM_INT, 'New or edited draft id'),
            'errormsg' => new external_value(PARAM_RAW, 'Error message for display'),
            'successmsg' => new external_value(PARAM_RAW, 'Success message for display'),
        ]);
    }

    /**
     * Create a new draft.
     *
     * Will also allow editing existing discussion in a later development.
     *
     * @param $forum integer
     * @param $draft integer
     * @param $group integer
     * @param $replyto integer
     * @param $subject string Subject of the post.
     * @param $message string Message of the post (no inline images etc.)
     * @param $draftarea int Draft area id of attached files, 1 indicates no files.
     * @param $showfrom int Start date.
     * @param $showsticky int Add discussion as sticky.
     * @param $postas int Post as.
     * @param $mailnow bool Is mailnow.
     * @param $setimportant bool Is important post.
     * @return array See returns above.
     * @throws \moodle_exception
     */
    public static function add_draft($forum, $draft, $group, $replyto, $subject, $message, $draftarea,
                                     $showfrom, $showsticky, $postas, $mailnow, $setimportant) {
        global $PAGE, $DB;

        $data = [
            'forum' => $forum,
            'draft' => $draft,
            'group' => $group,
            'replyto' => $replyto,
            'subject' => $subject,
            'message' => $message,
            'draftarea' => $draftarea,
            'showfrom' => $showfrom,
            'showsticky' => $showsticky,
            'postas' => $postas,
            'mailnow' => $mailnow,
            'setimportant' => $setimportant
        ];

        try {
            $data = (object) self::validate_parameters(self::add_draft_parameters(), $data);

            if ($data->draft == 0) {
                // Create a new draft.
                $forum = \mod_forumng::get_from_id($data->forum, 0);
            } else {
                // Editing existing draft.
                $draft =  \mod_forumng_draft::get_from_id($data->draft);
                $forum = \mod_forumng::get_from_id($draft->get_forumng_id(), 0);
            }
            if (strlen($data->subject) > 255) {
                return ['success' => false, 'draft' => 0, 'successmsg' => '',
                    'errormsg' => get_string('errormaximumsubjectcharacter', 'mod_forumng')];
            }

            $options = new \stdClass;
            if (isset($data->showfrom)) {
                $options->timestart = $data->showfrom;
            }
            if (isset($data->showsticky)) {
                $options->sticky = $data->showsticky;
            }
            if (isset($data->postas)) {
                $options->asmoderator = $data->postas;
            }
            if (isset($data->mailnow)) {
                $options->mailnow = $data->mailnow;
            }
            if (isset($data->setimportant)) {
                $options->setimportant = $data->setimportant;
            }

            // Set context to prevent notice message.
            $forumcontext = $forum->get_context(true);
            $PAGE->set_context($forumcontext);

            $fileoptions = ['subdirs' => false, 'maxbytes' => $forum->get_max_bytes(), 'maxfiles' => -1];
            $date = get_string('draftexists', 'forumng', \mod_forumng_utils::display_date(time()));
            if ($data->draft == 0) {
                $transaction = $DB->start_delegated_transaction();

                // Save the draft
                $newdraftid = \mod_forumng_draft::save_new(
                    $forum,
                    $data->group ? $data->group : null,
                    $data->replyto ? $data->replyto : null,
                    $data->subject,
                    $data->message, FORMAT_HTML,
                    $data->draftarea > 1, $options);

                // Attachments.
                if ($data->draftarea > 1) {
                    file_save_draft_area_files($data->draftarea, $forumcontext->id, 'mod_forumng',
                        'draft', $newdraftid, $fileoptions);
                }
                $transaction->allow_commit();
                return ['success' => true, 'draft' => $newdraftid, 'errormsg' => '', 'successmsg' => $date];
            } else {
                // This is an update of the existing draft
                $transaction = $DB->start_delegated_transaction();

                // Update the draft itself.
                $draft->update(
                    $data->subject, $data->message, FORMAT_HTML,
                    $data->draftarea > 1,
                    $data->group ? $data->group : null, $options);

                // Save any changes to files
                if ($data->draftarea > 1) {
                    file_save_draft_area_files($data->draftarea, $forumcontext->id, 'mod_forumng',
                        'draft', $draft->get_id(), $fileoptions);
                }
                $transaction->allow_commit();
                return ['success' => true, 'draft' => $draft->get_id(), 'errormsg' => '', 'successmsg' => $date];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'draft' => 0, 'errormsg' => $e->getMessage(), 'successmsg' => ''];
        }
    }
}
