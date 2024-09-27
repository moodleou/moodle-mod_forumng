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
 * Behat data generator for mod_forumng.
 *
 * @package   mod_forumng
 * @category  test
 * @copyright 2024 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_mod_forumng_generator extends behat_generator_base {

    /**
     * Get a list of the entities that Behat can create using the generator step.
     *
     * @return array
     */
    protected function get_creatable_entities(): array {
        return [
            'discussions' => [
                'singular' => 'discussion',
                'datagenerator' => 'discussion',
                'required' => ['forum', 'user'],
                'switchids' => ['forum' => 'forum', 'user' => 'userid', 'group' => 'groupid'],
            ],
            'posts' => [
                'singular' => 'post',
                'datagenerator' => 'post',
                'required' => ['discussion'],
                'switchids' => ['discussion' => 'discussionid', 'user' => 'userid'],
            ],
        ];
    }

    /**
     * Get the forum id using an activity idnumber or name.
     *
     * @param string $idnumberorname The forum activity idnumber or name.
     * @return int The forum id
     */
    protected function get_forum_id(string $idnumberorname): int {
        return $this->get_cm_by_activity_name('forumng', $idnumberorname)->instance;
    }

    protected function get_discussion_id(string $subject): int {
        global $DB;
        $result = $DB->get_record_sql("
            SELECT p.discussionid
              FROM {forumng_discussions} d
              JOIN {forumng_posts} p on p.id = d.postid
             WHERE p.subject = ?
        ", [$subject]);
        if ($result) {
            return $result->discussionid;
        } else {
            throw new \coding_exception('Cannot find discussion with subject text');
        }
    }
}
