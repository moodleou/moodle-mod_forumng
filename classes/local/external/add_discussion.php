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
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class add_discussion extends external_api {

    /**
     * Gets function parameter metadata.
     *
     * @return external_function_parameters Parameter info
     */
    public static function add_discussion_parameters() : external_function_parameters {
        return new external_function_parameters([
            'forum' => new external_value(PARAM_INT, 'Forumng ID'),
            'discussion' => new external_value(PARAM_INT, 'Discussion ID', VALUE_DEFAULT, 0),
            'group' => new external_value(PARAM_INT, 'Group ID', VALUE_DEFAULT, 0),
            'subject' => new external_value(PARAM_TEXT, 'Subject of the post'),
            'message' => new external_value(PARAM_RAW, 'Root message for discussion'),
            'draftarea' => new external_value(PARAM_INT, 'Draft area ID for uploaded attachments', VALUE_DEFAULT, 1)
        ]);
    }

    /**
     * Returns description of method result values.
     *
     * @return external_single_structure
     */
    public static function add_discussion_returns() : external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Successful creation or update, or failure'),
            'discussion' => new external_value(PARAM_INT, 'New or edited discussion id'),
            'errormsg' => new external_value(PARAM_RAW, 'Error message for display')
        ]);
    }

    /**
     * Create a new discussion.
     *
     * Will also allow editing existing discussion in a later development.
     *
     * @param $forum integer
     * @param $discussion integer Where 0 indicates a new discussion.
     * @param $group integer
     * @param $subject string Subject of the post.
     * @param $message string Message of the post (no inline images etc.)
     * @param $draftarea int Draft area id of attached files, 1 indicates no files.
     * @return array See returns above.
     * @throws \moodle_exception
     */
    public static function add_discussion($forum, $discussion, $group, $subject, $message, $draftarea) {
        global $PAGE, $DB;

        // Notes - only creating a new discussion is supported at present
        // These items require more work for the mobile platform:
        // Sticky, timestart and timeend, locking, save as draft, post as moderator are only available
        // with $forum->can_manage_discussions() so not on mobile for now.
        // Tags.
        // Email author.
        // editing.
        // offline.

        $data = [
            'forum' => $forum,
            'discussion' => $discussion,
            'group' => $group,
            'subject' => $subject,
            'message' => $message,
            'draftarea' => $draftarea
        ];

        try {
            $data = (object) self::validate_parameters(self::add_discussion_parameters(), $data);

            if ($data->discussion == 0) {
                // Create a new discussion.
                $forum = \mod_forumng::get_from_id($data->forum, 0);
            } else {
                // Editing existing discussion.
                $discussion = \mod_forumng_discussion::get_from_id($data->discussion, 0);
                $forum = $discussion->get_forum();
            }

            // Set context to prevent notice message.
            $forumcontext = $forum->get_context(true);
            $PAGE->set_context($forumcontext);

            $fileoptions = array('subdirs' => false, 'maxbytes' => $forum->get_max_bytes(), 'maxfiles' => -1);
            if ($data->discussion == 0) {
                // Sanity check.
                $whynot = '';
                if (!$forum->can_start_discussion($data->group, $whynot)) {
                    return ['success' => false, 'discussion' => 0, 'errormsg' => get_string($whynot, 'mod_forumng')];
                }
                $transaction = $DB->start_delegated_transaction();
                list($newdiscussionid, $newpostid) = $forum->create_discussion($data->group, $data->subject, $data->message,
                        FORMAT_HTML, $data->draftarea > 1);
                // Attachments.
                if ($data->draftarea > 1) {
                    file_save_draft_area_files($data->draftarea, $forumcontext->id, 'mod_forumng',
                            'attachment', $newpostid, $fileoptions);
                }
                $transaction->allow_commit();
                return ['success' => true, 'discussion' => $newdiscussionid, 'errormsg' => ''];
            } else {
                // TODO this section is not complete.
                return ['success' => false, 'discussion' => $data->discussion, 'errormsg' => 'Edit discussion not working yet'];
                // see line 631 in editpost.php
                // can only edit discussion settings that we are not using on mobile at the moment, other edits are in post!
                // so need to do the section from line 606 in editpost.php here
            }
        } catch (\Exception $e) {
            return ['success' => false, 'discussion' => 0, 'errormsg' => $e->getMessage()];
        }
    }
}
