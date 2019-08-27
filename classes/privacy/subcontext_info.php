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
 * Privacy Subsystem implementation for mod_forumng.
 *
 * @package mod_forumng
 * @copyright 2018 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_forumng\privacy;

defined('MOODLE_INTERNAL') || die();

/** Subject of discussion and post are limited to 32 characters. */
const LENGTH_LIMIT = 32;

/**
 * Subcontext subcontext_info trait.
 *
 * @package mod_forumng
 * @copyright 2018 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
trait subcontext_info {
    /**
     * Get the discussion part of the subcontext.
     *
     * @param \stdClass $discussion The discussion
     * @return array
     */
    protected static function get_discussion_area(\stdClass $discussion) : Array {
        global $DB;
        $sql = "SELECT p.*
                  FROM {forumng_discussions} d
                  JOIN {forumng_posts} p ON p.discussionid = d.id
                 WHERE d.id = :discussionid AND p.parentpostid IS NULL
            ";
        $discussionobj = $DB->get_record_sql($sql, ['discussionid' => $discussion->id]);
        $name = isset($discussionobj->subject) ? $discussionobj->subject : '';

        $pathparts = [];
        if (!empty($discussion->groupname)) {
            $pathparts[] = get_string('groups');
            $pathparts[] = $discussion->groupname;
        }

        $parts = [
            $discussion->id,
            str_replace('/', '_', substr($name, 0, LENGTH_LIMIT))
        ];

        $discussionname = implode('-', $parts);

        $pathparts[] = get_string('discussions', 'mod_forumng');
        $pathparts[] = $discussionname;

        return $pathparts;
    }

    /**
     * Get the post part of the subcontext.
     *
     * @param \stdClass $post The post.
     * @return array
     */
    protected static function get_post_area(\stdClass $post) : Array {
        $parts = [
            $post->created,
            str_replace('/', '_', substr($post->subject, 0, LENGTH_LIMIT)),
            $post->id,
        ];
        $area[] = implode('-', $parts);

        return $area;
    }

    /**
     * Get the parent subcontext for the supplied forum, discussion, and post combination.
     *
     * @param \stdClass $post The post.
     * @return array
     */
    protected static function get_post_area_for_parent(\stdClass $post) {
        global $DB;

        $subcontext = [];
        if ($parent = $DB->get_record('forumng_posts', ['id' => $post->parentpostid], 'id, created, subject')) {
            $subcontext = array_merge($subcontext, static::get_post_area($parent));
        }
        $subcontext = array_merge($subcontext, static::get_post_area($post));

        return $subcontext;
    }

    /**
     * Get the subcontext for the supplied forum, discussion, and post combination.
     *
     * @param \stdClass $forum The forum.
     * @param \stdClass $discussion The discussion
     * @param \stdClass $post The post.
     * @return  array
     */
    protected static function get_subcontext($forum, $discussion = null, $post = null) {
        $subcontext = [];
        if (null !== $discussion) {
            $subcontext += self::get_discussion_area($discussion);

            if (null !== $post) {
                $subcontext[] = get_string('posts', 'mod_forumng');
                $subcontext = array_merge($subcontext, static::get_post_area_for_parent($post));
            }
        }

        return $subcontext;

    }
}
