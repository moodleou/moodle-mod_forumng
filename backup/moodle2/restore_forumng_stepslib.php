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
 * Forum restore step.
 * @package mod
 * @subpackage forumng
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Structure step to restore one forumng activity
 */
class restore_forumng_activity_structure_step extends restore_activity_structure_step {

    /**
     * ID of forum processed in this step
     * @var int
     */
    private $forumngid;

    protected function define_structure() {
        $this->potential_dot();

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('forumng', '/activity/forumng');
        if ($userinfo) {
            $paths[] = new restore_path_element('forumng_discussion',
                    '/activity/forumng/discussions/discussion');
            $paths[] = new restore_path_element('forumng_post',
                    '/activity/forumng/discussions/discussion/posts/post');
            $paths[] = new restore_path_element('forumng_rating',
                    '/activity/forumng/discussions/discussion/posts/post/ratings/rating');
            $paths[] = new restore_path_element('forumng_flag',
                    '/activity/forumng/discussions/discussion/posts/post/flags/flag');
            $paths[] = new restore_path_element('forumng_subscription',
                    '/activity/forumng/subscriptions/subscription');
            $paths[] = new restore_path_element('forumng_read',
                    '/activity/forumng/discussions/discussion/readdiscussions/read');
            $paths[] = new restore_path_element('forumng_draft',
                    '/activity/forumng/drafts/draft');
        }

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    /**
     * In case of long-running restores, we support the optional
     * potential_dot method in the restore logger, anywhere in the chain
     * (this is for OU custom use).
     */
    private function potential_dot() {
        $logger = $this->get_logger();
        while ($logger) {
            if (method_exists($logger, 'potential_dot')) {
                $logger->potential_dot();
            }
            $logger = $logger->get_next();
        }
    }

    protected function process_forumng($data) {
        global $DB;

        $this->potential_dot();

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $data->postingfrom = $this->apply_date_offset($data->postingfrom);
        $data->postinguntil = $this->apply_date_offset($data->postinguntil);
        $data->ratingfrom = $this->apply_date_offset($data->ratingfrom);
        $data->ratinguntil = $this->apply_date_offset($data->ratinguntil);

        if ($data->ratingscale < 0) { // scale found, get mapping
            $data->ratingscale = -($this->get_mappingid_or_null('scale', abs($data->ratingscale)));
        }

        $newitemid = $DB->insert_record('forumng', $data);
        $this->apply_activity_instance($newitemid);
        $this->forumngid = $newitemid;
    }

    protected function process_forumng_discussion($data) {
        global $DB;

        $this->potential_dot();

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();
        $data->forumngid = $this->get_new_parentid('forumng');

        $data->timestart = $this->apply_date_offset($data->timestart);
        $data->timeend = $this->apply_date_offset($data->timeend);
        $data->groupid = $this->get_mappingid_or_null('group', $data->groupid);

        // postid is handled in process_forumng_post
        // lastpostid is handled in after_execute

        $newitemid = $DB->insert_record('forumng_discussions', $data);
        $this->set_mapping('forumng_discussion', $oldid, $newitemid);
    }

    private function get_mappingid_or_null($type, $oldid) {
        if ($oldid === null) {
            return null;
        }
        return $this->get_mappingid($type, $oldid);
    }

    protected function process_forumng_post($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->discussionid = $this->get_new_parentid('forumng_discussion');
        $data->created = $this->apply_date_offset($data->created);
        $data->modified = $this->apply_date_offset($data->modified);
        $data->deleted = $this->apply_date_offset($data->deleted);
        $data->userid = $this->get_mappingid_or_null('user', $data->userid);
        $data->deleteuserid = $this->get_mappingid_or_null('user', $data->deleteuserid);
        $data->edituserid = $this->get_mappingid_or_null('user', $data->edituserid);

        // If post has parent, map it (it has been already restored)
        if (!empty($data->parentpostid)) {
            $data->parentpostid = $this->get_mappingid('forumng_post', $data->parentpostid);
        }

        $newitemid = $DB->insert_record('forumng_posts', $data);
        $this->set_mapping('forumng_post', $oldid, $newitemid, true);

        // If !post->parent, it's the 1st post. Set it in discussion
        if (empty($data->parentpostid)) {
            $DB->set_field('forumng_discussions', 'postid', $newitemid,
                array('id' => $data->discussionid));
        }
    }

    protected function process_forumng_draft($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->forumngid = $this->get_new_parentid('forumng');
        $data->userid = $this->get_mappingid_or_null('user', $data->userid);
        $data->groupid = $this->get_mappingid_or_null('group', $data->groupid);

        // If post has parent, map it (it has been already restored)
        if (!empty($data->parentpostid)) {
            $data->parentpostid = $this->get_mappingid_or_null('forumng_post',
            $data->parentpostid);
        }

        $newitemid = $DB->insert_record('forumng_drafts', $data);
        $this->set_mapping('forumng_draft', $oldid, $newitemid, true);
    }

    protected function process_forumng_rating($data) {
        global $DB;

        $data = (object)$data;

        $data->postid = $this->get_new_parentid('forumng_post');
        $data->userid = $this->get_mappingid_or_null('user', $data->userid);
        $data->time = $this->apply_date_offset($data->time);

        $DB->insert_record('forumng_ratings', $data);
    }

    protected function process_forumng_flag($data) {
        global $DB;

        $data = (object)$data;

        $data->postid = $this->get_new_parentid('forumng_post');
        $data->userid = $this->get_mappingid_or_null('user', $data->userid);

        $DB->insert_record('forumng_flags', $data);
    }

    protected function process_forumng_subscription($data) {
        global $DB;

        $data = (object)$data;

        $data->forumngid = $this->get_new_parentid('forumng');
        $data->userid = $this->get_mappingid_or_null('user', $data->userid);
        $data->discussionid = $this->get_mappingid_or_null('forumng_discussion',
                $data->discussionid);
        $data->clonecmid = $this->get_mappingid_or_null('course_modules', $data->clonecmid);
        $data->groupid = $this->get_mappingid_or_null('group', $data->groupid);

        $DB->insert_record('forumng_subscriptions', $data);
    }

    protected function process_forumng_read($data) {
        global $DB;

        $data = (object)$data;

        $data->discussionid = $this->get_new_parentid('forumng_discussion');
        $data->userid = $this->get_mappingid_or_null('user', $data->userid);

        $DB->insert_record('forumng_read', $data);
    }

    protected function after_execute() {
        global $DB, $CFG;

        // Add forumng related files, no need to match by
        // itemname (just internally handled context)
        $this->add_related_files('mod_forumng', 'intro', null);

        // Add post related files, matching by itemname = 'forumng_post'
        $this->add_related_files('mod_forumng', 'message', 'forumng_post');
        $this->add_related_files('mod_forumng', 'attachment', 'forumng_post');
        $this->add_related_files('mod_forumng', 'draft', 'forumng_draft');

        // Now fix the lastpostid for each discussion
        // TODO Does this work on MySQL? No idea.
        $DB->execute("
UPDATE {forumng_discussions} SET lastpostid=(
    SELECT
        MAX(id)
    FROM
        {forumng_posts} fp
    WHERE
        fp.discussionid = {forumng_discussions}.id
        AND fp.oldversion=0
        AND fp.deleted=0
) WHERE forumngid = ?", array($this->forumngid));

        require_once($CFG->dirroot . '/mod/forumng/mod_forumng.php');
        // Create search index if user data restored.
        if ($this->get_setting_value('userinfo') && mod_forumng::search_installed()) {
            mod_forumng::search_update_all(false, $this->get_courseid(), $this->task->get_moduleid());
        }
    }
}
