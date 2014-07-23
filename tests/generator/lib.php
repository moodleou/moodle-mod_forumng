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
 * Data generator.
 *
 * @package mod_forumng
 * @copyright 2014 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * ForumnNG module data generator class.
 *
 * @package mod_forumng
 * @copyright 2014 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_forumng_generator extends testing_module_generator {

    private $forumdiscussioncount = 0;
    private $forumpostcount = 0;

    public function reset() {
        $this->forumdiscussioncount = 0;
        $this->forumpostcount = 0;
        return parent::reset();
    }

    /**
     * Creates new forumng module instance.
     *
     * @param array|stdClass $record Resource module record, as from form
     * @param array $options Standard options about how to create it
     * @return stdClass Activity record, with extra cmid field
     */
    public function create_instance($record = null, array $options = null) {
        global $CFG;
        require_once($CFG->dirroot . '/mod/forumng/lib.php');

        // Count generated modules.
        $i = $this->instancecount + 1;

        // Ensure the record can be modified without affecting calling code.
        $record = (object)(array)$record;
        $options = (array)$options;

        // Course is required.
        if (empty($record->course)) {
            throw new coding_exception('module generator requires $record->course');
        }

        // Fill in optional values if not specified.
        if (!isset($record->name)) {
            $record->name = get_string('pluginname', 'forumng') . ' ' . $i;
        }
        if (!isset($record->intro)) {
            $record->intro = 'Test forum ' . $i;
        }
        if (!isset($record->introformat)) {
            $record->introformat = FORMAT_MOODLE;
        }

        if (isset($options['idnumber'])) {
            $record->cmidnumber = $options['idnumber'];
        }

        // Now actually add the instance.
        return parent::create_instance($record, $options);
    }

    /**
     * Function to create a dummy discussion.
     *
     * @param array|stdClass $record
     * @return stdClass the discussion object
     */
    public function create_discussion($record = null) {
        global $CFG;
        require_once($CFG->dirroot . '/mod/forumng/mod_forumng.php');

        // Increment the forum discussion count.
        $this->forumdiscussioncount++;

        $record = (array) $record;

        if (!isset($record['course'])) {
            throw new coding_exception('course must be present, in mod_forumng_generator::create_discussion() $record');
        }

        if (!isset($record['forum'])) {
            throw new coding_exception('forum must be present, in mod_forumng_generator::create_discussion() $record');
        }

        if (!isset($record['userid'])) {
            throw new coding_exception('userid must be present, in mod_forumng_generator::create_discussion() $record');
        }

        if (!isset($record['groupid'])) {
            $record['groupid'] = -1;
        }

        if (!isset($record['subject'])) {
            $record['subject'] = "Subject for discussion " . $this->forumdiscussioncount;
        }

        if (!isset($record['message'])) {
            $record['message'] = html_writer::tag('p', 'Message for discussion ' . $this->forumdiscussioncount);
        }

        if (!isset($record['format'])) {
            $record['format'] = FORMAT_MOODLE;
        }

        if (!isset($record['timestart'])) {
            $record['timestart'] = 0;
        }

        // Get a forum object.
        $forum = mod_forumng::get_from_id($record['forum'], mod_forumng::CLONE_DIRECT);

        // Create the discussion.
        $discussionid = $forum->create_discussion($record['groupid'], $record['subject'],
                $record['message'], $record['format'], false, false, $record['timestart'], 0, false,
                false, $record['userid']);

        return $discussionid;
    }

    /**
     * Function to create a dummy post.
     *
     * @param array|stdClass $record
     * @return stdClass the post object
     */
    public function create_post($record = null) {
        global $DB;

        // Increment the forum post count.
        $this->forumpostcount++;

        // Variable to store time.
        $time = time() + $this->forumpostcount;

        $record = (array) $record;

        if (!isset($record['discussionid'])) {
            throw new coding_exception('discussion must be present, in mod_forumng_generator::create_post() $record');
        }

        if (!isset($record['userid'])) {
            throw new coding_exception('userid must be present, in mod_forumng_generator::create_post() $record');
        }

        if (!isset($record['parentpostid'])) {
            $record['parentpostid'] = 0;
        }

        if (!isset($record['subject'])) {
            $record['subject'] = 'Forum post subject ' . $this->forumpostcount;
        }

        if (!isset($record['message'])) {
            $record['message'] = html_writer::tag('p', 'Forum message post ' . $this->forumpostcount);
        }

        if (!isset($record['created'])) {
            $record['created'] = $time;
        }

        if (!isset($record['modified'])) {
            $record['modified'] = $time;
        }

        if (!isset($record['messageformat'])) {
            $record['messageformat'] = FORMAT_MOODLE;
        }

        $record = (object) $record;

        // Add the post.
        $record->id = $DB->insert_record('forumng_posts', $record);

        $updatedis = new stdClass();
        $updatedis->id = $record->discussionid;
        $updatedis->lastpostid = $record->id;
        $DB->update_record('forumng_discussions', $updatedis);

        return $record;
    }
}
