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
 * Mobile plugin.
 *
 * @package mod_forumng
 * @copyright 2018 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_forumng\output;

defined('MOODLE_INTERNAL') || die();

use context_module;

require_once(__DIR__ . '/../../mod_forumng.php');
require_once(__DIR__ . '/../../mod_forumng_utils.php');
require_once($CFG->libdir . '/externallib.php');

/**
 * Mobile output class for forumng
 *
 * @package mod_forumng
 * @copyright 2018 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mobile {

    /**
     * Returns the forumng view for the mobile app.
     *
     * @param array $args Arguments from tool_mobile_get_content WS
     * @return array HTML, javascript and otherdata
     */
    public static function forumng_view(array $args) {
        global $OUTPUT, $CFG;

        $args = (object) $args;
        $selectedgroupid = empty($args->group) ? 0 : $args->group;
        $cmid = $args->cmid;

        // This is based on view.php and forumngtype_general.php.
        $forumng = \mod_forumng::get_from_cmid($cmid, \mod_forumng::CLONE_DIRECT);
        if ($forumng->is_clone()) {
            $forumng = $forumng->get_real_forum();
            $a = new \stdClass();
            $a->url = new \moodle_url('/mod/forumng/view.php', ['id' => $forumng->get_id()]);
            $a->shortname = $forumng->get_course()->shortname;
            throw new \moodle_exception('sharedviewinfoclone', 'forumng', '', $a);
        }
        $course = $forumng->get_course();
        $cm = $forumng->get_course_module();
        $context = $forumng->get_context();
        $groupid = \mod_forumng::get_activity_group($cm, true); // Null if student not in a group, or in two groups.
        if ($selectedgroupid > 0 && is_null($groupid)) {
            $groupid = $selectedgroupid;
        }
        $forumng->require_view($groupid); // This can throw an error about aag capability (in an error dialog).

        // Completion and logging.
        // Do this forum view logging from the server end, as it is surely not possible to view the forum when offline.
        $completion = new \completion_info($course);
        $completion->set_module_viewed($cm);
        $forumng->log('view'); // Note for later. Log post reads from the mobile end.
        $pageurl = new \moodle_url('/mod/forumng/view.php', ['id' => $forumng->get_id()]); // Not actually used.
        $forumngrenderer = $forumng->init_page($pageurl); // Renderer is not needed, but setting up the $PAGE is probably useful.

        // Groups.
        $hasgroups = false; // No visible groups, or user not in a group - display nothing.
        $groupsmenu = ''; // User in one group - display the group name.
        $groups = []; // User in more than one group, or has access to all - display group selector.
        $allowedgroups = groups_get_activity_allowed_groups($cm);
        if ($allowedgroups) {
            if (count($allowedgroups) == 1) {
                // Get the text for the current group.
                $groupsmenu = groups_print_activity_menu($cm, $forumng->get_url(\mod_forumng::PARAM_HTML), true);
            } else {
                $hasgroups = true;
                $groups[] = (object)['groupid' => 0, 'groupname' => get_string('allparticipants')];
                foreach ($allowedgroups as $g) {
                    $groups[] = (object)['groupid' => $g->id, 'groupname' => format_string($g->name)];
                }
            }
        }

        // Discussions.
        $canviewhidden = $forumng->can_view_hidden();
        $page = 1; // Forumng only serves 'pages' of discussions. So start with the first page, infinite scroll provides more.
        $discussionlist = $forumng->get_discussion_list($groupid, $canviewhidden);
        $totaldiscussions = $discussionlist->get_total_discussions();
        $hasdiscussions = $totaldiscussions > 0;
        $sticky = $discussionlist->get_sticky_discussions();
        $normal = $discussionlist->get_normal_discussions();
        $discussions = [];
        // Sticky always first.
        self::prep_discussions($forumng, $sticky, $discussions);
        self::prep_discussions($forumng, $normal, $discussions);

        // Data prep.
        $forum = new \stdClass();
        $forum->id = $forumng->get_id();
        // Not used currently $forum->name = format_string($forumng->get_name()).
        // Not used currently $forum->intro = $cm->get_formatted_content().
        list($forum->introduction, $unusedintroductionformat) =
                external_format_text($forumng->get_introduction(), $forumng->get_introduction_format(), $context->id,
                    'mod_forumng', 'introduction');
        $data = [
            'forum' => $forum,
            'hasgroups' => $hasgroups,
            'groups' => $groups,
            'groupsmenu' => $groupsmenu,
            'hasdiscussions' => $hasdiscussions,
            'cmid' => $cm->id,
            'courseid' => $course->id
        ];
        $html = $OUTPUT->render_from_template('mod_forumng/mobile_discussions_page', $data);

        return [
            'templates' => [
                [
                    'id' => 'main',
                    'html' => $html,
                ]
            ],
            'javascript' => file_get_contents($CFG->dirroot . '/mod/forumng/appjs/mobile_forumng.js'),
            'otherdata' => [
                'defaultgroup' => $groupid,
                'discussions' => json_encode($discussions), // Cannot put arrays in otherdata.
                'totaldiscussions' => $totaldiscussions,
                'page' => $page
            ],
            'files' => []
        ];
    }

    /**
     * Populate the discussions array with suitably formatted data.
     *
     * @param \mod_forumng $forum Main ForumNG object.
     * @param array $discussionlist Array of sticky or normal discussion objects.
     * @param array $discussions
     * @throws \coding_exception
     */
    public static function prep_discussions(\mod_forumng $forum, array $discussionlist, array &$discussions) {
        // Ref: forumng/renderer.php render_discussion_list_item.
        foreach ($discussionlist as $key => $discussion) {
            // Only add discussions if the forumtype thinks we can see them.
            if ($forum->get_type()->can_view_discussion($discussion)) {
                // Icon and class decorators.
                $classes = '';
                $alts = [];
                $icons = [];
                $decorators = [];
                if ($discussion->is_deleted()) {
                    $classes .= ' forumng-deleted';
                    $alts[] = get_string('alt_discussion_deleted', 'forumng');
                    $icons[] = []; // No icon, text will be output on its own.
                }
                if (!$discussion->is_within_time_period()) {
                    $classes .= ' forumng-timeout';
                    $alts[] = get_string('alt_discussion_timeout', 'forumng');
                    $icons[] = ['timeout', 'mod_forumng'];
                }
                if ($discussion->is_sticky()) {
                    $classes .= ' forumng-sticky';
                    $alts[] = get_string('alt_discussion_sticky', 'forumng');
                    $icons[] = ['sticky', 'mod_forumng'];
                }
                if ($discussion->is_locked()) {
                    $classes .= ' forumng-locked';
                    $alts[] = get_string('alt_discussion_locked', 'forumng');
                    $icons[] = ['i/unlock', 'moodle'];
                }
                $renderer = $forum->get_type()->get_renderer();
                foreach ($icons as $index => $icon) {
                    $alt = $alts[$index];
                    $url = '';
                    if ($icon) {
                        $url = $renderer->image_url($icon[0], $icon[1])->out();
                    }
                    $decorators[] = (object)['src' => $url, 'alt' => $alt];
                }
                // Subject.
                $subject = format_string($discussion->get_subject(true));
                // Unread.
                $canmarkread = $discussion->get_forum()->can_mark_read();
                $unreadposts = '';
                if ($canmarkread) {
                    $unreadposts = $discussion->get_num_unread_posts();
                }
                $unreadpostsalt = get_string('hasunreadposts', 'forumng');
                // Last post.
                $lastposter = $discussion->get_last_post_user();
                $lastuserlink = $discussion->get_forum()->display_user_link($lastposter);
                $timestr = \mod_forumng_utils::display_date($discussion->get_time_modified());
                $moderator = get_string('moderator', 'forumng');
                $lastposteranon = $discussion->get_last_post_anon();
                if ($lastposteranon == \mod_forumng::ASMODERATOR_IDENTIFY) {
                    $lastpostcell = $timestr . ' ' . $lastuserlink . ' ' . $moderator;
                } else if ($lastposteranon == \mod_forumng::ASMODERATOR_ANON) {
                    if ($discussion->get_forum()->can_post_anonymously()) {
                        $lastpostcell = $timestr . ' ' . $lastuserlink . ' ' . $moderator;
                    } else {
                        $lastpostcell = $timestr . ' ' . $moderator;
                    }
                } else {
                    $lastpostcell = $timestr . ' ' . $lastposter->firstname . ' ' . $lastposter->lastname;
                }
                // Author or started by.
                $posteranon = $discussion->get_poster_anon();
                $poster = $discussion->get_poster();
                $username = $discussion->get_forum()->display_user_name($poster);
                $moderator = get_string('moderator', 'forumng');
                if ($posteranon == \mod_forumng::ASMODERATOR_IDENTIFY) {
                    $startedby = $username . ' ' . $moderator;
                } else if ($posteranon == \mod_forumng::ASMODERATOR_ANON) {
                    if ($discussion->get_forum()->can_post_anonymously()) {
                        $startedby = $username . ' ' . $moderator;
                    } else {
                        $startedby = $moderator;
                    }
                } else {
                    $startedby = $username;
                }
                // No of posts.
                $noofposts = $discussion->get_num_posts();
                if ($noofposts == 1) {
                    $noofposts = get_string('totalreply', 'forumng', $noofposts);
                } else {
                    $noofposts = get_string('totalreplies', 'forumng', $noofposts);
                }
                // If the date for the root post is required then it means this heavyweight function.
                $starteddate = userdate($discussion->get_root_post()->get_created(), get_string('strftimedate', 'langconfig'));

                $discussions[] = (object)[
                    'id' => $discussion->get_id(),
                    'subject' => $subject,
                    'unread' => '' . $unreadposts,
                    'unreadpostsalt' => $unreadpostsalt,
                    'startedby' => $startedby,
                    'starteddate' => $starteddate,
                    'noofposts' => $noofposts,
                    'lastpost' => $lastpostcell,
                    'lastpostuserid' => $lastposter->id, // Linking to last user is not completed yet.
                    'classes' => $classes,
                    'decorators' => $decorators
                ];
            }
        }
    }

    /**
     * Returns shared (global) templates and information for the mobile app feature.
     *
     * @param array $args Arguments (empty)
     * @return array Array with information required by app
     */
    public static function mobile_forumng_init(array $args) : array {
        global $CFG;
        return [
                'templates' => [],
                'javascript' => file_get_contents($CFG->dirroot . '/mod/forumng/appjs/mobile_init.js'),
                'otherdata' => '',
                'files' => []
        ];
    }
}
