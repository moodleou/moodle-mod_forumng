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
 * Search area class for document posts
 *
 * @package mod_forumng
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_forumng\search;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/forumng/mod_forumng.php');

/**
 * Search area class for document posts
 *
 * @package mod_forumng
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class post extends \core_search\base_mod {

    /**
     * File area relate to Moodle file table.
     */
    const FILEAREA = [
        'ATTACHMENT' => 'attachment',
        'MESSAGE' => 'message'
    ];

    /** @var array Relevant context levels (module context) */
    protected static $levels = [CONTEXT_MODULE];

    /**
     * Returns recordset containing required data for indexing forumng posts.
     *
     * @param int $modifiedfrom
     * @return \moodle_recordset
     */
    public function get_recordset_by_timestamp($modifiedfrom = 0) {
        // Get all posts (without being drafts).
        global $DB;
        $querystring = '
            SELECT f.id, f.course,
                   fp.id as forumpostid, fp.discussionid, fp.parentpostid, fp.userid, fp.created,
                   fp.modified as postmodified, fp.deleted,
                   fp.deleteuserid, fp.important, fp.mailstate, fp.oldversion, fp.edituserid, fp.subject, fp.message,
                   fp.messageformat, fp.attachments, fp.asmoderator,
                   fd.modified as discussionmodified, GREATEST(fp.modified, fd.modified) as modifyorder
              FROM {forumng_posts} fp
              JOIN {forumng_discussions} fd ON fp.discussionid = fd.id
              JOIN {forumng} f ON fd.forumngid = f.id
             WHERE (fp.modified >= ? OR fd.modified >= ?)
                   AND fp.deleted = 0
                   AND fd.deleted = 0
                   AND fp.oldversion = 0
          ORDER BY modifyorder ASC';

        return $DB->get_recordset_sql($querystring, array($modifiedfrom, $modifiedfrom));
    }

    /**
     * Returns the document associated with this post id.
     *
     * @param \stdClass $record
     * @param array $options
     * @return bool|\core_search\document
     */
    public function get_document($record, $options = array()) {
        try {
            $cm = get_coursemodule_from_instance($this->get_module_name(), $record->id, $record->course);
            $context = \context_module::instance($cm->id);
        } catch (\dml_exception $ex) {
            // Don't throw an exception, apparently it might upset the search process.
            debugging('Error retrieving ' . $this->areaid . ' ' . $record->forumpostid .
                    ' document: ' . $ex->getMessage(), DEBUG_DEVELOPER);
            return false;
        }

        // Remember to check time modified between post and discussion.
        if ($record->discussionmodified > $record->postmodified) {
            $timemodified = $record->discussionmodified;
        } else {
            $timemodified = $record->postmodified;
        }

        // Construct the document instance to return.
        $doc = \core_search\document_factory::instance(
                $record->forumpostid, $this->componentname, $this->areaname);

        // Set document title.
        // Document title will be post title.
        $title = $record->subject;
        $doc->set('title', content_to_text($title, false));

        // Set document content.
        $content = $record->message;
        $content = file_rewrite_pluginfile_urls($content, 'pluginfile.php', $context->id, $this->componentname,
                self::FILEAREA['MESSAGE'], $record->forumpostid);
        $doc->set('content', content_to_text($content, FORMAT_HTML));

        // Set document description1: discussion tags.
        $itemtags = $this->get_tag_by_discussion($record->discussionid);
        $itemtagstr = implode(' ', $itemtags);
        $doc->set('description1', content_to_text($itemtagstr, false));

        // Set other search metadata.
        $doc->set('contextid', $context->id);
        $doc->set('type', \core_search\manager::TYPE_TEXT);
        $doc->set('courseid', $record->course);
        $doc->set('modified', $timemodified);
        $doc->set('itemid', $record->forumpostid);
        $doc->set('owneruserid', \core_search\manager::NO_OWNER_ID);
        $doc->set('userid', $record->userid);

        // Set optional 'new' flag.
        if (isset($options['lastindexedtime']) && ($options['lastindexedtime'] < $record->created)) {
            // If the document was created after the last index time, it must be new.
            $doc->set_is_new(true);
        }

        return $doc;
    }

    /**
     * Whether the user can access the document or not.
     *
     * @param int $id Post ID
     * @return int
     */
    public function check_access($id) {
        global $USER;

        // Get post instance and forum instance in an unique record.
        $postinstance = $this->get_post($id);

        if (empty($postinstance) || $postinstance->get_deleted()) {
            // This activity instance was deleted.
            return \core_search\manager::ACCESS_DELETED;
        }

        if ($postinstance->is_old_version()) {
            // This post was an old version.
            return \core_search\manager::ACCESS_DELETED;
        }

        if (!$postinstance->get_discussion()->can_view($USER->id)) {
            return \core_search\manager::ACCESS_DENIED;
        }

        return \core_search\manager::ACCESS_GRANTED;
    }

    /**
     * Returns the specified forumng post.
     *
     * @param int $postid
     * @return \mod_forumng_post|boolean Post object
     */
    protected function get_post($postid) {
        return \mod_forumng_post::get_from_id($postid, 0, false, false, 0, true);
    }

    /**
     * Link to the forumng discussion page
     *
     * @param \core_search\document $doc Document instance returned by get_document function
     * @return \moodle_url
     */
    public function get_doc_url(\core_search\document $doc) {
        $postintance = $this->get_post($doc->get('itemid'));
        return new \moodle_url('/mod/forumng/discuss.php?d=' . $postintance->get_discussion()->get_id() .
                '#p' . $postintance->get_id());
    }

    /**
     * Link to the forumng discussion page
     *
     * @param \core_search\document $doc Document instance returned by get_document function
     * @return \moodle_url
     */
    public function get_context_url(\core_search\document $doc) {
        return $this->get_doc_url($doc);
    }

    /**
     * Returns true if this area uses file indexing.
     *
     * @return bool
     */
    public function uses_file_indexing() {
        return true;
    }

    /**
     * Add the attached description files.
     *
     * @param \core_search\document $document The current document
     * @return null
     */
    public function attach_files($document) {
        $fs = get_file_storage();
        $files = array();

        foreach (self::FILEAREA as $area) {
            $files = array_merge($files, $fs->get_area_files($document->get('contextid'), $this->componentname, $area,
                    $document->get('itemid'), 'sortorder DESC, id ASC'));
        }

        foreach ($files as $file) {
            if ($file->get_filename() == '.') {
                continue;
            }

            $document->add_stored_file($file);
        }
    }

    /**
     * Returns the module name.
     *
     * @return string
     */
    protected function get_module_name() {
        return substr($this->componentname, 4);
    }

    /**
     * Returns array of tag display names
     *
     * @param int $discussionid
     * @return string[]
     */
    public function get_tag_by_discussion($discussionid) {
        return \core_tag_tag::get_item_tags_array('mod_forumng', 'forumng_discussions',
            $discussionid);
    }
}
