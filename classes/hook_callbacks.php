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

namespace mod_forumng;

global $CFG;
require_once($CFG->dirroot . '/mod/forumng/mod_forumng_discussion.php');
require_once($CFG->dirroot . '/mod/forumng/mod_forumng_post.php');
require_once($CFG->dirroot . '/mod/forumng/mod_forumng.php');

use mod_forumng_discussion;
use mod_forumng_post;

/**
 * Hook callbacks.
 *
 * @package mod_forumng
 * @copyright 2024 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {

    /**
     * Called when the system wants to find out if an activity is searchable, to decide whether to
     * display a search box in the header.
     *
     * @param \local_moodleglobalsearch\hook\activity_search_info $hook
     */
    public static function activity_search_info(\local_moodleglobalsearch\hook\activity_search_info $hook) {
        if ($hook->is_page('mod-forumng-view', 'mod-forumng-discuss')) {
            $hook->enable_search(get_string('searchthisforum', 'forumng'));
        }
    }

    /**
     * Clean AI prompts by removing dynamic elements for better caching.
     * For forum discussions, ensure all posts are expanded to include full content.
     *
     * @param \tool_ouadmin\hook\before_prompt_generation $hook Hook containing prompt to clean.
     * @return void
     */
    public static function before_prompt_generation(\tool_ouadmin\hook\before_prompt_generation $hook): void {
        $originalprompt = $hook->get_prompt();
        $contextid = $hook->get_contextid();

        // Early exit if not in a ForumNG.
        if (!self::is_forumng_context($contextid)) {
            return;
        }

        $discussionid = self::get_discussion_id_from_prompt($originalprompt);

        if (!$discussionid) {
            return;
        }

        try {
            $cleanedcontent = self::extract_forum_discussion_content($discussionid);
            if ($cleanedcontent) {
                $hook->set_prompt($cleanedcontent);
            }
        } catch (\Exception $e) {
            debugging('Failed to extract forum discussion content: ' . $e->getMessage());
        }
    }

    /**
     * Extracts and cleans discussion content from a ForumNG context.
     *
     * @param int $dicussionid The discussion ID.
     * @return string|null The cleaned discussion content, or null on failure.
     */
    private static function extract_forum_discussion_content(int $dicussionid): ?string {

        $discussion = self::get_discussion_by_id($dicussionid);

        if (!$discussion) {
            return null;
        }

        return self::render_and_clean_discussion($discussion);
    }

    /**
     * Renders a discussion with all posts expanded and cleans the content.
     *
     * @param mod_forumng_discussion $discussion The discussion to render.
     * @return string The cleaned discussion content.
     */
    private static function render_and_clean_discussion(mod_forumng_discussion $discussion): string {
        $forum = $discussion->get_forum();
        $renderer = $forum->get_type()->get_renderer();

        $renderoptions = [
            mod_forumng_post::OPTION_CHILDREN_EXPANDED => true,
            mod_forumng_post::OPTION_NO_COMMANDS => true
        ];

        $expandedcontent = $renderer->render_discussion($discussion, $renderoptions);

        return self::clean_html_content($expandedcontent);
    }

    /**
     * Cleans HTML content by stripping tags and normalizing whitespace.
     *
     * @param string $content The HTML content to clean.
     * @return string The cleaned content.
     */
    private static function clean_html_content(string $content): string {
        // Strip HTML tags.
        $cleanedcontent = strip_tags($content);

        // Decode HTML entities.
        $cleanedcontent = html_entity_decode($cleanedcontent, ENT_QUOTES, 'UTF-8');

        // Normalize whitespace (replace multiple spaces, tabs, newlines with single space).
        $cleanedcontent = preg_replace('/\s+/', ' ', $cleanedcontent);

        // Trim leading/trailing whitespace.
        return trim($cleanedcontent);
    }

    /**
     * Checks if the given context is a ForumNG module context.
     *
     * @param int $contextid The context ID to check.
     * @return bool True if the context is a ForumNG module context, false otherwise.
     */
    private static function is_forumng_context(int $contextid): bool {
        try {
            $context = \context::instance_by_id($contextid);

            if ($context->contextlevel !== CONTEXT_MODULE) {
                return false;
            }

            $coursemodule = get_coursemodule_from_id('forumng', $context->instanceid);

            return $coursemodule !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Gets a discussion by its ID.
     *
     * @param int $discussionid The discussion ID.
     * @return mod_forumng_discussion|null The discussion object or null if not found.
     */
    private static function get_discussion_by_id(int $discussionid): ?mod_forumng_discussion {
        try {
            return mod_forumng_discussion::get_from_id($discussionid, 0);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Extracts discussion ID from the prompt text.
     *
     * @param string $originalprompt The original prompt text.
     * @return int|null The discussion ID or null if not found.
     */
    private static function get_discussion_id_from_prompt(string $originalprompt): ?int {
        if (preg_match('/discussionidforpromptai:(\d+)/', $originalprompt, $matches)) {
            return (int)$matches[1];
        }

        return null;
    }
}
