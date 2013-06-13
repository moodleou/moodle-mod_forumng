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
 * Represents a draft forum post (reply or discussion), as stored in the
 * forumng_drafts database table.
 * @see forum
 * @package mod
 * @subpackage forumng
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_forumng_draft {
    private $draftfields;

    /**
     * Queries for draft posts, including necessary joins with other fields.
     * @param string $where Text of WHERE clause e.g. 'fdr.id=14'. May refer
     *   to aliases fdr (drafts), fd (discussions), fp (posts; post being
     *   replied to), fpfirst (first post in discussion), and u (user being
     *   replied to)
     * @return array Array of mod_forumng_draft objects (empty if none)
     */
    public static function query_drafts($where, $whereparams) {
        global $DB;
        $result = array();
        $rs = $DB->get_recordset_sql("
SELECT
    fdr.*, fd.id AS discussionid, fpfirst.subject AS discussionsubject,
    f.course AS courseid,
    " . mod_forumng_utils::select_username_fields('u', false) . "
FROM
    {forumng_drafts} fdr
    LEFT JOIN {forumng_posts} fp ON fdr.parentpostid = fp.id
    LEFT JOIN {forumng_discussions} fd ON fp.discussionid = fd.id
    LEFT JOIN {forumng_posts} fpfirst ON fd.postid = fpfirst.id
    LEFT JOIN {user} u ON fp.userid = u.id
    INNER JOIN {forumng} f ON fdr.forumngid = f.id
WHERE
    $where
ORDER BY
    fdr.saved DESC", $whereparams);
        foreach ($rs as $rec) {
            $result[] = new mod_forumng_draft($rec);
        }
        $rs->close();
        return $result;
    }

    /**
     * @param int $draftid ID of draft
     * @return mod_forumng_draft Draft post
     */
    public static function get_from_id($draftid) {
        $posts = self::query_drafts("fdr.id = ?", array($draftid));
        if (count($posts) == 0) {
            throw new moodle_exception('error_draftnotfound', 'forumng', '');
        }
        return reset($posts);
    }

    /**
     * Constructs draft post.
     * @param object $draftfields Fields from query_drafts query
     */
    private function __construct($draftfields) {
        $draftfields->replytouser =
            mod_forumng_utils::extract_subobject($draftfields, 'u_');
        $this->draftfields = $draftfields;
    }

    /**
     * Saves a new draft message.
     * @param int $forumngid ID of forum
     * @param int $groupid Group ID (null if none)
     * @param int $parentpostid ID of post this is in reply to, or 0 for
     *   a new discussion
     * @param string $subject Subject of draft post
     * @param string $message Message of draft post
     * @param int $messageformat Format (FORMAT_xx) of message
     * @param bool $attachments True if draft contains attachments
     * @param string $options Options (null if none)
     * @param int $userid User ID or 0 for current
     * @return int ID of new draft
     */
    public static function save_new($forum, $groupid, $parentpostid, $subject,
            $message, $messageformat, $attachments, $options, $userid=0) {
        global $DB;
        $userid = mod_forumng_utils::get_real_userid($userid);
        $serializedoptions = $options ? serialize($options) : null;
        $record = (object)array('userid' => $userid, 'forumngid' => $forum->get_id(),
            'parentpostid' => ($parentpostid ? $parentpostid : null),
            'subject' => $subject, 'message' => $message,
            'messageformat' => $messageformat,
            'attachments' => $attachments ? 1 : 0,
            'saved' => time(), 'groupid' => $groupid, 'options' => $serializedoptions);
        return $DB->insert_record('forumng_drafts', $record);
    }

    /**
     * Updates the message field of a draft entry. This is necessary in some cases where
     * the user includes images etc. in the message; these are initially included using
     * a draft URL which has to be changed to a special relative path on convert, and we
     * can't do that until the draft ID is known. Additionally, we don't have a draft object
     * at that point, hence use of static function.
     * @param int $draftid ID of draft to update
     * @param string $newtext Updated message text
     */
    public static function update_message_for_files($draftid, $newtext) {
        global $DB;
        $DB->set_field('forumng_drafts', 'message', $newtext, array('id'=>$draftid));
    }

    /**
     * Updates an existing draft message.
     * @param string $subject Subject of draft post
     * @param string $message Message of draft post
     * @param int $messageformat Format (FORMAT_xx) of message
     * @param bool $attachments True if draft now has attachments
     * @param int $groupid Group ID (null if none)
     * @param object $options Options (null if none)
     */
    public function update($subject, $message,
            $messageformat, $attachments, $groupid, $options) {
        global $DB;

        $serializedoptions = $options ? serialize($options) : null;

        $record = (object)array(
            'id' => $this->get_id(),
            'subject' => $subject, 'message' => $message,
            'messageformat' => $messageformat, 'attachments' => $attachments ? 1 : 0,
            'groupid' => $groupid, 'options' => $serializedoptions, 'saved' => time());

        // Do database update
        $DB->update_record('forumng_drafts', $record);
    }

    /**
     * Deletes an existing draft message.
     * @param object $filecontext Context used for files (=forum context)
     */
    public function delete($filecontext) {
        global $DB;
        $transaction = $DB->start_delegated_transaction();

        // Delete record
        $DB->delete_records('forumng_drafts', array('id' => $this->draftfields->id));

        // Delete attachments
        $fs = get_file_storage();
        if ($this->has_attachments()) {
            $fs->delete_area_files($filecontext->id, 'mod_forumng', 'draft', $this->get_id());
        }

        // Delete message files
        $fs->delete_area_files($filecontext->id, 'mod_forumng', 'draftmessage', $this->get_id());

        $transaction->allow_commit();
    }

    // Direct fields
    /*//////////////*/

    /**
     * @return int ID of this draft
     */
    public function get_id() {
        return $this->draftfields->id;
    }

    /**
     * @return int ID of user making draft
     */
    public function get_user_id() {
        return $this->draftfields->userid;
    }

    /**
     * @return int ID of forum containing draft
     */
    public function get_forumng_id() {
        return $this->draftfields->forumngid;
    }

    /**
     * @return int Time (seconds since epoch) this draft was saved
     */
    public function get_saved() {
        return $this->draftfields->saved;
    }

    /**
     * @return string Message subject
     */
    public function get_subject() {
        return $this->draftfields->subject;
    }

    /**
     * @return string Message content
     */
    public function get_raw_message() {
        return $this->draftfields->message;
    }

    /**
     * @param mod_forumng $forum Forum object
     * @return string Message after format_text and replacing file URLs
     */
    public function get_formatted_message($forum) {
        $context = $forum->get_context(true);
        $text = file_rewrite_pluginfile_urls($this->draftfields->message, 'pluginfile.php',
            $context->id, 'mod_forumng', 'draftmessage', $this->draftfields->id);
        $textoptions = new stdClass();
        // Don't put a <p> tag round post
        $textoptions->para = false;
        // Does not indicate that we trust the text, only that the
        // TRUSTTEXT marker would be supported. At present though it isn't (hm)
        $textoptions->trusttext = false;
        if (trusttext_active() && $this->get_user_id() && has_capability('moodle/site:trustcontent',
                $context, $this->get_user_id())) {
            // Support trusted text when initial author is safe.
            $textoptions->trusted = true;
        }
        $textoptions->context = $context;
        return format_text($text, $this->draftfields->messageformat, $textoptions);
    }

    /**
     * @return int Format (FORMAT_xx) of message content
     */
    public function get_format() {
        return $this->draftfields->messageformat;
    }

    /**
     * @return object Options object (may be null)
     */
    public function get_options() {
        return $this->draftfields->options
            ? unserialize($this->draftfields->options) : null;
    }

    // Discussion-related information from joins
    /*//////////////////////////////////////////*/

    /**
     * @return bool True if this is a new discussion, false if it's a reply
     */
    public function is_new_discussion() {
        return is_null($this->draftfields->discussionid);
    }

    /**
     * @return bool True if this is a reply, false if it's a new discussion
     */
    public function is_reply() {
        return !is_null($this->draftfields->discussionid);
    }

    /**
     * @return int ID of group for new discussion (this field is not set for
     *   replies)
     */
    public function get_group_id() {
        return $this->draftfields->groupid;
    }

    /**
     * Utility function to check this draft is about a reply in an existing
     * discussion.
     * @throws mod_forumng_exception If this is a new discussion (so no id yet)
     */
    private function check_discussion_exists() {
        if (!$this->draftfields->discussionid) {
            throw new invalid_state_exception("Draft message does not have discussion");
        }
    }

    /**
     * @return int Discussion id
     * @throws mod_forumng_exception If this is a new discussion (so no id yet)
     */
    public function get_discussion_id() {
        $this->check_discussion_exists();
        return $this->draftfields->discussionid;
    }

    /**
     * @return string Discussion subject
     * @throws mod_forumng_exception If this is a new discussion
     */
    public function get_discussion_subject() {
        $this->check_discussion_exists();
        return $this->draftfields->discussionsubject;
    }

    /**
     * @return object Moodle user object (selected fields) for post being
     *   replied to
     * @throws mod_forumng_exception If this is a new discussion
     */
    public function get_reply_to_user() {
        $this->check_discussion_exists();
        return $this->draftfields->replytouser;
    }

    /**
     * @return int Parent post that is being replied to
     * @throws mod_forumng_exception If this is a new discussion
     */
    public function get_parent_post_id() {
        $this->check_discussion_exists();
        return $this->draftfields->parentpostid;
    }

    // Attachments
    /*////////////*/

    /**
     * @return bool True if this draft has any attachments
     */
    public function has_attachments() {
        return $this->draftfields->attachments ? true : false;
    }

    // UI
    /*///*/

    /**
     * Prints the content of this draft as a JavaScript variable (including
     * surrounding script tag).
     * @param mod_forumng $forum Forum object
     * @return string HTML (including JS) code to place in page
     */
    public function prepare_edit_js($forum) {
        global $USER;

        // Copy fields
        $fields = clone($this->draftfields);

        // Prepare file areas
        $fileoptions = array('subdirs'=>false, 'maxbytes'=>$forum->get_max_bytes());
        $filecontext = $forum->get_context(true);

        // Prepare draft area for attachments
        $draftitemid = 0;
        file_prepare_draft_area($draftitemid, $filecontext->id,
                'mod_forumng', 'draft', $this->get_id(), $fileoptions);

        // Prepare draft area for message files
        $messagedraftitemid = 0;
        $fields->message = file_prepare_draft_area($messagedraftitemid, $filecontext->id,
                'mod_forumng', 'draftmessage', $this->get_id(), $fileoptions, $fields->message);

        // Get list of files for main attachment area
        $options = file_get_drafarea_files($draftitemid, '/');
        $usercontext = context_user::instance($USER->id);
        $fs = get_file_storage();
        $files = $fs->get_area_files($usercontext->id, 'user', 'draft',
                $options->itemid, 'id', false);
        $options->filecount = count($files);
        $fields->attachmentoptions = $options;

        // Get list of files for message area
        $messageoptions = file_get_drafarea_files($messagedraftitemid, '/');
        $files = $fs->get_area_files($usercontext->id, 'user', 'draft',
                $messageoptions->itemid, 'id', false);
        $messageoptions->filecount = count($files);
        $fields->messageoptions = $messageoptions;

        // Unset things we don't need in JS
        unset($fields->discussionid);
        unset($fields->discussionsubject);
        unset($fields->courseid);
        unset($fields->replytouser);
        unset($fields->options);
        unset($fields->attachments);

        // Add options
        foreach ((array)($this->get_options()) as $key => $value) {
            $fields->{$key} = $value;
        }

        return "<script type='text/javascript'>\n" .
                "var forumng_draft = " . json_encode($fields) . ";\n</script>\n";
    }
}
