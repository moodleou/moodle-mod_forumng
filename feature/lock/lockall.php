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

/**
 * Multipole lock script. This uses the discussion selector infrastructure to
 * handle the situation when discussions are being selected.
 * @package forumngfeature
 * @subpackage lock
 * @copyright 2013 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../forumngfeature_discussion_selector.php');
require_once($CFG->dirroot . '/mod/forumng/editpost_form.php');

class lockall_on_selector extends forumngfeature_discussion_selector {
    public function get_button_name() {
        return get_string('lock', 'forumngfeature_lock');
    }

    public function get_form($discussion, $all, $selected = array()) {
        global $CFG, $PAGE;
        foreach ($selected as $postid) {
            $params['selectd'.$postid] = $postid;
        }
        $params['id'] = $discussion->get_course_module_id();
        $params['group'] = mod_forumng::get_activity_group($discussion->get_course_module());
        $params['clone'] = optional_param('clone', 0, PARAM_INT);
        $params['fromselect'] = true;
        $customdata = array('params' => $params, 'isdiscussion' => false, 'edit' => false,
                'ispost' => true, 'islock' => true, 'post' => null,
                'isroot' => true, 'forum' => $discussion);

        return new mod_forumng_editpost_form('lockall.php', $customdata);
    }

    public function get_page_name() {
        return get_string('lockdiscussiontitle', 'forumngfeature_lock');
    }

    public function apply($discussion, $all, $selected, $formdata) {
        global $CFG;
        $filecontext = $discussion->get_context();
        $forum = $discussion;
        if (!isset($formdata->mailnow)) {
            $formdata->mailnow = false;
        }
        foreach ($selected as $postid) {
            // Call the lock for selected discussions.
            $discussion = mod_forumng_discussion::get_from_id($postid, $formdata->clone);
            if (!$discussion->is_deleted() && !$discussion->is_locked()) {
                $newpostid = $discussion->lock($formdata->subject, $formdata->message['text'],
                        $formdata->message['format'], $formdata->attachments, $formdata->mailnow,
                        '', '', $formdata->asmoderator);
                // Save attachments.
                file_save_draft_area_files($formdata->attachments, $filecontext->id, 'mod_forumng',
                        'attachment', $newpostid, null);
                $newtext = file_save_draft_area_files($formdata->message['itemid'],
                        $filecontext->id, 'mod_forumng', 'message', $newpostid, null,
                        $formdata->message['text']);
                if ($newtext !== $formdata->message['text']) {
                    mod_forumng_post::update_message_for_files($newpostid, $newtext);
                }
            }
        }
        // Redirect to the Main page.
        redirect('../../view.php?' . $forum->get_link_params(mod_forumng::PARAM_PLAIN));
    }

    public function only_discussion_types() {
        return array('forumng-discussion-short');
    }

    public function exclude_discussion_types() {
        return array('forumng-deleted', 'forumng-locked');
    }
}

// Outside the class.
forumngfeature_discussion_selector::go(new lockall_on_selector());
