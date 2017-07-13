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

defined('MOODLE_INTERNAL') || die();

/**
 * Structure step to restore one forumng activity.
 */
class restore_forumng_activity_structure_step extends restore_activity_structure_step {

    /**
     * ID of forum processed in this step.
     * @var int
     */
    private $forumngid, $shared, $type, $moveintrofiles;
    private $grouptags = array();

    protected function define_structure() {
        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('forumng', '/activity/forumng');
        if ($userinfo) {
            $paths[] = new restore_path_element('forumng_discussion',
                    '/activity/forumng/discussions/discussion');
            $paths[] = new restore_path_element('forumng_post',
                    '/activity/forumng/discussions/discussion/posts/post');
            $paths[] = new restore_path_element('rating',
                    '/activity/forumng/discussions/discussion/posts/post/newratings/newrating');
            $paths[] = new restore_path_element('forumng_rating',
                    '/activity/forumng/discussions/discussion/posts/post/ratings/rating');
            $paths[] = new restore_path_element('forumng_flag',
                    '/activity/forumng/discussions/discussion/posts/post/flags/flag');
            $paths[] = new restore_path_element('forumng_subscription',
                    '/activity/forumng/subscriptions/subscription');
            $paths[] = new restore_path_element('forumng_read',
                    '/activity/forumng/discussions/discussion/readdiscussions/read');
            $paths[] = new restore_path_element('forumng_readpost',
                    '/activity/forumng/discussions/discussion/posts/post/readposts/readpost');
            $paths[] = new restore_path_element('forumng_draft',
                    '/activity/forumng/drafts/draft');
            $paths[] = new restore_path_element('forumng_flagd',
                    '/activity/forumng/discussions/discussion/flagsd/flagd');
            $paths[] = new restore_path_element('forumng_tag',
                    '/activity/forumng/discussions/discussion/tags/tag');
        }

        $paths[] = new restore_path_element('forumng_forumtaginstance',
                '/activity/forumng/forumtaginstances/forumtaginstance');
        $paths[] = new restore_path_element('forumng_forumgrouptaginstance',
                '/activity/forumng/forumgrouptaginstances/forumgrouptaginstance');

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    protected function process_forumng($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        // If we are restoring an old backup, introduction will not be present, and
        // what was previously in intro needs to be moved to introduction. Will also need to handle files.
        if (!isset($data->introduction)) {
            $data->introduction = $data->intro;
            $data->introductionformat = $data->introformat;
            $data->intro = '';
            $data->introformat = FORMAT_HTML;
            $this->moveintrofiles = true;
        }

        $data->postingfrom = $this->apply_date_offset($data->postingfrom);
        $data->postinguntil = $this->apply_date_offset($data->postinguntil);
        $data->ratingfrom = $this->apply_date_offset($data->ratingfrom);
        $data->ratinguntil = $this->apply_date_offset($data->ratinguntil);

        if ($data->ratingscale < 0) { // Scale found, get mapping.
            $data->ratingscale = -($this->get_mappingid_or_null('scale', abs($data->ratingscale)));
        }

        if (empty($data->timemodified)) {
            $data->timemodified = time();
        }

        $newitemid = $DB->insert_record('forumng', $data);
        $this->apply_activity_instance($newitemid);
        $this->forumngid = $newitemid;
        $this->shared = $data->shared;
        $this->type = $data->type;
    }

    protected function process_forumng_discussion($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();
        $data->forumngid = $this->get_new_parentid('forumng');

        $data->timestart = $this->apply_date_offset($data->timestart);
        $data->timeend = $this->apply_date_offset($data->timeend);
        $data->groupid = $this->get_mappingid_or_null('group', $data->groupid);
        $data->modified = time();

        // Field postid is handled in process_forumng_post.
        // Field lastpostid is handled in after_execute.

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
        $data->userid = $this->get_mappingid_or_null('user', $data->userid);
        $data->deleteuserid = $this->get_mappingid_or_null('user', $data->deleteuserid);
        $data->edituserid = $this->get_mappingid_or_null('user', $data->edituserid);

        // If post has parent, map it (it has been already restored).
        if (!empty($data->parentpostid)) {
            $data->parentpostid = $this->get_mappingid('forumng_post', $data->parentpostid);
        }

        $newitemid = $DB->insert_record('forumng_posts', $data);
        $this->set_mapping('forumng_post', $oldid, $newitemid, true);

        // If !post->parent, it's the 1st post. Set it in discussion.
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

        // If post has parent, map it (it has been already restored).
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

        $DB->insert_record('forumng_ratings', $data);
    }

    protected function process_forumng_flagd($data) {
        global $DB;

        $data = (object)$data;
        $data->discussionid  = $this->get_new_parentid('forumng_discussion');
        $data->userid = $this->get_mappingid_or_null('user', $data->userid);
        $data->postid = 0;

        $DB->insert_record('forumng_flags', $data);
    }

    protected function process_forumng_flag($data) {
        global $DB;

        $data = (object)$data;

        $data->postid = $this->get_new_parentid('forumng_post');
        $data->userid = $this->get_mappingid_or_null('user', $data->userid);
        $data->discussionid = 0;

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

    protected function process_forumng_readpost($data) {
        global $DB;

        $data = (object)$data;

        $data->postid = $this->get_new_parentid('forumng_post');
        $data->userid = $this->get_mappingid_or_null('user', $data->userid);

        $DB->insert_record('forumng_read_posts', $data);
    }

    protected function process_forumng_tag($data) {
        global $CFG;

        $data = (object)$data;
        $oldid = $data->id;

        if (empty($CFG->usetags) || !core_tag_tag::is_enabled('mod_forumng', 'forumng_discussions')) {
            // Tags disabled in server, nothing to process.
            return;
        }

        $tag = $data->rawname;
        $itemid = $this->get_new_parentid('forumng_discussion');
        $forumid = $this->get_new_parentid('forumng');

        $cm = get_coursemodule_from_instance('forumng', $forumid);
        core_tag_tag::add_item_tag('mod_forumng', 'forumng_discussions', $itemid, context_module::instance($cm->id), $tag);
    }

    protected function process_forumng_forumtaginstance($data) {
        global $CFG;

        $data = (object)$data;
        $oldid = $data->id;

        if (empty($CFG->usetags) || !core_tag_tag::is_enabled('mod_forumng', 'forumng')) {
            // Tags disabled in server, nothing to process.
            return;
        }

        $tag = $data->rawname;
        $forumid = $this->get_new_parentid('forumng');

        $cm = get_coursemodule_from_instance('forumng', $forumid);
        core_tag_tag::add_item_tag('mod_forumng', 'forumng', $forumid, context_module::instance($cm->id), $tag);
    }

    protected function process_forumng_forumgrouptaginstance($data) {
        global $CFG;

        $data = (object)$data;
        $oldid = $data->id;

        if (empty($CFG->usetags)) { // Tags disabled in server, nothing to process.
            return;
        }

        $tag = $data->rawname;

        $groupid = $this->get_mappingid_or_null('group', $data->itemid);

        if (isset($this->grouptags[$groupid])) {
            $this->grouptags[$groupid][] = $tag;
        } else {
            $this->grouptags[$groupid] = array($tag);
        }
    }

    protected function after_execute() {
        global $DB, $CFG;

        // Add forumng related files, no need to match by
        // itemname (just internally handled context).
        $this->add_related_files('mod_forumng', 'intro', null);
        if (!$this->moveintrofiles) {
            $this->add_related_files('mod_forumng', 'introduction', null);
        }

        // Add post related files, matching by itemname = 'forumng_post'.
        $this->add_related_files('mod_forumng', 'message', 'forumng_post');
        $this->add_related_files('mod_forumng', 'attachment', 'forumng_post');
        $this->add_related_files('mod_forumng', 'draft', 'forumng_draft');

        if ($this->moveintrofiles) {
            $DB->execute("
                    UPDATE {files}
                       SET filearea = 'introduction'
                     WHERE component = 'mod_forumng'
                       AND filearea = 'intro'
                       AND contextid = ?
                    ", array($this->task->get_contextid()));
        }

        // Now fix the lastpostid for each discussion.
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
            mod_forumng::search_update_all(false, $this->get_courseid(), $this->task->get_moduleid(),
                    $this->get_task()->get_progress());
        }

        // See what $this->task->get_moduleid() contains, then check master|clone.
        // If we have the module id we can use that to get the cm.
        if ($cmid = $this->task->get_moduleid()) {
            // Check to see whether forum is shared or a clone.
            if ($this->shared) {
                // Master.
                // Get the ID number - probably from the course_modules table using $this->task->get_moduleid() in a query.
                $cm = $DB->get_record('course_modules', array('id' => $cmid), '*', MUST_EXIST);
                $idnumber = $cm->idnumber;
                // Check for any other forumng module instances using the same ID number - .
                // if any then add _1 to end (if already has _number at end then increment that number e.g. _2).
                $newidnumber = $this->get_new_idnumber($idnumber);
                // Set new idnumber.
                $cm->idnumber = $newidnumber;
                $DB->update_record('course_modules', $cm);
            } else if ($this->type === 'clone') {
                // Clone.
                $cm = $DB->get_record('course_modules', array('id' => $cmid), '*', MUST_EXIST);
                $forumng = $DB->get_record('forumng', array('id' => $cm->instance), '*', MUST_EXIST);
                // If clone get original cmid ID number, if exists and has AUTO_ prefix,
                // If exists and has AUTO_ prefix.
                if (!empty($forumng->originalcmid)) {
                    $originalcm = $DB->get_record_sql('select * from {course_modules} where module =
                            (select id from {modules} where name = ?) and id = ?', array('forumng', $forumng->originalcmid));
                    $strippedidnumber = $originalcm->idnumber;
                    // If exists and has AUTO_ prefix.
                    $idnumber = $this->strip_auto_from_idnumber($originalcm->idnumber);
                    if ($idnumber != $originalcm->idnumber) {
                        $strippedidnumber = $idnumber;
                        // We have an AUTO_ prefix.
                        // Do we have a valid suffix.
                        $suffix = $this->get_possible_suffix($originalcm->idnumber, '_');
                        if ($suffix != 0) {
                            // We have a valid suffix.
                            // Remove the suffix from the idnumber by getting the last '_' .
                            $strippedidnumber = $this->remove_suffix($idnumber, '_');
                        }
                        // Place auto back in.
                        $strippedidnumber = 'AUTO_'.$strippedidnumber;
                        // Get cmid for all forumng module instances (joining to forumng table to ensure we get only master forums)
                        // that have matching idnumber (regardless of their _number suffix).
                        $cms = $DB->get_records_sql('select cm.id as cmid, cm.idnumber from {course_modules} cm
                                inner join {forumng} f on cm.instance = f.id where cm.idnumber like ?
                                and f.shared = ?
                                and cm.module = (select id from {modules} where name = ?)
                                order by cmid desc', array($strippedidnumber.'%', 1, 'forumng'));
                        // Set originalcmid field.
                        $forumng->originalcmid = $this->get_cmid_for_forumng_idnumbers($strippedidnumber, $cms);
                        if ($forumng->originalcmid) {
                            $DB->update_record('forumng', $forumng);
                        }
                    }
                }
            }
        }
        if (!empty($this->grouptags)) {
            foreach ($this->grouptags as $groupid => $tags) {
                mod_forumng::set_group_tags($this->forumngid, $groupid, $tags);
            }
        }
    }

    protected function get_new_idnumber($idnumber) {
        global $DB;

        // Check for any other forumng module instances using the same ID number.
        $cms = $DB->get_records_sql('select * from {course_modules} where module =
                (select id from {modules} where name = ?) and idnumber = ?', array('forumng', $idnumber));

        if (!empty($cms) && (count($cms) == 1)) {
            return $idnumber;
        }
        // If any then add _1 to end (if already has _number at end then increment that number e.g. _2).
        // Check new ID number doesn't exist and repeat as needed.

        // Do we have a valid suffix.
        $strippedidnumber = 0;
        $suffix = $this->get_possible_suffix($idnumber, '_');
        if ($suffix != 0) {
            // We have a valid suffix.
            // Remove the suffix from the idnumber by getting the last '_' .
            $idnum = $this->remove_suffix($idnumber, '_');
            $strippedidnumber = $idnum;
        } else {
            $idnum = $idnumber;
        }

        $cms = $DB->get_records_sql('select * from {course_modules} where module =
                (select id from {modules} where name = ?) and idnumber like ?
                 order by id desc', array('forumng', $idnum.'%'));

        $newidnumber = 0;

        if (!empty($cms) && (count($cms) > 1)) {
            // Current record is the new record, so get next record  which should contain the highest increment of the idnumber.
            $cm = next($cms);
            // Do we have a valid suffix.
            $suffix = $this->get_possible_suffix($cm->idnumber, '_');
            if ($suffix != 0) {
                $strippedidnumber = $this->remove_suffix($cm->idnumber, '_');
            }

            if ($strippedidnumber === $idnum) {
                // Increment suffix by 1 prior to adding to make a new idnumber.
                $suffix++;
                // Create the new idnumber.
                $newidnumber = $strippedidnumber.'_'.$suffix;
            } else {
                $newidnumber = $idnum.'_1';
            }

        } else {
            $newidnumber = $idnumber;
        }
        return $newidnumber;
    }

    protected function get_cmid_for_forumng_idnumbers($strippedidnumber, $cms) {
        $cmid = 0;
        if (!empty($cms) && (count($cms) > 0)) {
            // We are in a search for the cmid.
            reset($cms);
            // Get top record which should contain the highest increment of the idnumber.
            foreach ($cms as $cm) {
                // Strip suffix from cm idnumber.
                $idnum = $this->remove_suffix($cm->idnumber, '_');
                if ($idnum === $strippedidnumber) {
                    return $cm->cmid;
                }
            }
        }
        return $cmid;
    }

    protected function remove_suffix($str, $suffixexp) {
        $id = '';
        if (strrchr ($str, $suffixexp)) {
            $chunks = explode($suffixexp, $str);
            $suffix = array_pop($chunks);
            $id = implode('_', $chunks);
        } else {
            $id = $str;
        }
        return $id;
    }

    protected function strip_auto_from_idnumber($cmidnumber) {
        $id = '';
        $pos = strrpos($cmidnumber, 'AUTO_');
        if ($pos !== false) {
            // Strip out the AUTO.
            $pos = 5;
            $id = substr($cmidnumber, $pos);
        } else {
            $id = $cmidnumber;
        }
        return $id;
    }

    protected function get_possible_suffix($str, $suffixexp) {
        $suffix = substr(strrchr($str, $suffixexp), 1);
        if (is_numeric($suffix)) {
            $suffix = $suffix + 0; // Force conversion to relevant numeric type.
        }
        if (is_int($suffix)) {
            return $suffix;
        } else {
            return 0;
        }
    }

    protected function process_rating($data) {
        global $DB;

        $data = (object)$data;

        // Cannot use ratings API, cause, it's missing the ability to specify times (modified/created).
        $data->contextid = $this->task->get_contextid();
        $data->itemid = $this->get_new_parentid('forumng_post');
        if ($data->scaleid < 0) {// Scale found, get mapping.
            $data->scaleid = -($this->get_mappingid('scale', abs($data->scaleid)));
        }
        $data->rating = $data->value;
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        // Make sure that we have both component and ratingarea set. These were added in 2.1.
        // Prior to that all ratings were for entries so we know what to set them too.
        if (empty($data->component)) {
            $data->component = 'mod_forumng';
        }
        if (empty($data->ratingarea)) {
            $data->ratingarea = 'post';
        }

        $newitemid = $DB->insert_record('rating', $data);
    }
}
