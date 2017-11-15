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

defined('MOODLE_INTERNAL') || die;
require_once($CFG->dirroot . '/mod/forumng/type/general/forumngtype_general.php');

/**
 * Forum type: In Page discussion type
 * @package forumngtype_ipud
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class forumngtype_ipud extends forumngtype_general {


    /**
     * Obtains the forumtype_ipud renderer.
     * @return forumngtype_ipud_renderer Singleton renderer
     */
    public static function get_renderer() {
        // It probably doesn't take very long to construct one, but let's cache it anyhow.
        static $out;
        if (!$out) {
            global $PAGE;
            $out = $PAGE->get_renderer('forumngtype_ipud');
        }
        return $out;
    }

    /**
     * Checks if user is allowed to post to this forum (if capabilities permit).
     * You are not allowed to manually post to this forum.
     *
     * Default implementation just returns true.
     * @param mod_forumng $forum Forum
     * @param string &$whynot Output parameter - set to a language string name
     *   to give a specific reason for failure that is displayed on view
     *   screen where the button would be (otherwise will not report one)
     * @param int $userid User ID or 0 for current user
     * @return bool False to prevent user posting; true to allow it subject
     *   to normal restrictions
     */
    public function can_post($forum, &$whynot, $userid=0) {
        $whynot = 'error_new_discussion_ipud';
        return false;
    }

    /**
     * User is not allow reply to a level two post.
     *
     *
     * @param mod_forumng_post $inreplyto Post being replied to
     * @param int $userid User ID or 0 for current user
     * @return bool False to prevent user posting; true to allow it subject
     *   to normal restrictions
     */
    public function can_reply($inreplyto, $userid=0) {
        if (!$inreplyto->is_root_post()) {
            try {
                $parent = $inreplyto->get_parent();
                if (!$parent->is_root_post()) {
                    return false;
                }
            } catch (moodle_exception $e) {
                // In case we can't load the parent post.We should do query DB to get parentpostid.
                $discussion = $inreplyto->get_discussion();
                $currentpost = $inreplyto->query_posts('fp.id = ?', array($inreplyto->get_id()));
                $currentpost = reset($currentpost);
                // If parent is root,then it post level one.
                if ($currentpost->parentpostid == $discussion->get_root_post()->get_id()) {
                    return true;
                } else {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Get list of prevent features in forum.
     * Default just returns empty array. This could be used to veto selected features.
     * @return array True to allow
     */
    public static function prevent_forumngfeature_discussion() {
        $removedfeatures = array('move', 'lock');
        return $removedfeatures;
    }

    /**
     * Checks if user is allowed to view a discussion on this forum (if
     * capabilities/groups permit).
     * User need capability permit to view discussion in a discussion page.
     *
     * Note that implementing this function usually also requires implementation
     * of get_unread_restriction_sql.
     *
     * @param mod_forumng_discussion $discussion Discussion
     * @param int $userid User ID or 0 for current user
     * @return bool False to prevent user viewing; true to allow it subject
     *   to normal restrictions
     */
    public function can_view_discussion($discussion, $userid=0) {
        global $PAGE;
        $forum = $discussion->get_forum();
        $context = $forum->get_context();
        // User without permission can not go to discussion in the discussion link.
        // User can still see discussion in the forum.
        if (!has_capability('mod/forumng:viewrealipud', $context) && $PAGE->pagetype == 'mod-forumng-discuss') {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Check user has viewrealipud capability base on forum type.
     * For ipud to check viewrealipud capability.
     *
     * @param mod_forumng $forum
     * @return bool
     */
    public function can_mark_read($forum) {
        $context = $forum->get_context();
        if (has_capability('mod/forumng:viewrealipud', $context)) {
            return true;
        }
        return false;
    }
}
