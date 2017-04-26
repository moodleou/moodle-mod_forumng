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
 * Forum backup task.
 * @package mod
 * @subpackage forumng
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Because it exists (must).
require_once($CFG->dirroot . '/mod/forumng/backup/moodle2/backup_forumng_stepslib.php');

// Because it exists (optional).
require_once($CFG->dirroot . '/mod/forumng/backup/moodle2/backup_forumng_settingslib.php');

/**
 * forumng backup task that provides all the settings and steps to perform one
 * complete backup of the activity.
 */
class backup_forumng_activity_task extends backup_activity_task {

    /**
     * Define (add) particular settings this activity can have.
     */
    protected function define_my_settings() {
        // No particular settings for this activity.
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        // Forum only has one structure step.
        $this->add_step(new backup_forumng_activity_structure_step('forumng structure',
                'forumng.xml'));
    }

    /**
     * Code the transformations to perform in the activity in
     * order to get transportable (encoded) links.
     */
    static public function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, "/");

        // Link to the list of forumngs.
        $search = "/(".$base."\/mod\/forumng\/index.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@FORUMNGINDEX*$2@$', $content);

        // Link to forumng view by moduleid.
        $search = "/(".$base."\/mod\/forumng\/view.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@FORUMNGVIEWBYID*$2@$', $content);

        // Link to forumng discussion with relative syntax.
        $search = "/(".$base."\/mod\/forumng\/discuss.php\?d\=)([0-9]+)\#p([0-9]+)/";
        $content = preg_replace($search, '$@FORUMNGDISCUSSIONVIEWINSIDE*$2*$3@$', $content);

        // Link to forumng discussion by discussionid.
        $search = "/(".$base."\/mod\/forumng\/discuss.php\?d\=)([0-9]+)/";
        $content = preg_replace($search, '$@FORUMNGDISCUSSIONVIEW*$2@$', $content);

        return $content;
    }
}
