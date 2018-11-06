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
     * @var int SHORTENED_LENGTH Shortened post message length.
     */
    const SHORTENED_LENGTH = 50;

    /**
     * @var int NUMBER_POSTS Number of posts to display initially.
     */
    const NUMBER_POSTS = 5;

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

    /* Forum view (list of discussions in the forum) */
    /* --------------------------------------------- */

    /**
     * Returns the forumng view for the mobile app.
     *
     * @param array $args Arguments from tool_mobile_get_content WS
     * @return array HTML, javascript and otherdata
     */
    public static function forumng_view(array $args) {
        global $OUTPUT;

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
        $whynot = '';
        $data = [
            'forum' => $forum,
            'hasgroups' => $hasgroups,
            'groups' => $groups,
            'groupsmenu' => $groupsmenu,
            'hasdiscussions' => $hasdiscussions,
            'cmid' => $cm->id,
            'courseid' => $course->id,
            'canstartdiscussion' => $forumng->can_start_discussion($groupid, $whynot)
        ];
        $html = $OUTPUT->render_from_template('mod_forumng/mobile_discussions_page', $data);

        return [
            'templates' => [
                [
                    'id' => 'main',
                    'html' => $html,
                ]
            ],
            'javascript' => 'window.forumngDiscussionsPageInit(this);',
            'otherdata' => [
                'defaultgroup' => $groupid,
                'hasGroups' => $hasgroups, // Used to hide/show the FAB.
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
        global $USER;
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
                $unreadposts = 0;
                if ($canmarkread) {
                    $unreadposts = $discussion->get_num_unread_posts();
                    // Because unreadposts can be returned as an empty string, but int required.
                    if ($unreadposts === '') {
                        $unreadposts = 0;
                    }
                }
                $unreadpostsalt = get_string('hasunreadposts', 'forumng');
                // Last post.
                $lastposter = $discussion->get_last_post_user();
                $lastuser = $discussion->get_forum()->display_user_name($lastposter);
                $lastpostcell = self::date_or_time($discussion->get_time_modified()) . ' ';
                $moderator = get_string('moderator', 'forumng');
                $lastposteranon = $discussion->get_last_post_anon();
                if ($lastposteranon == \mod_forumng::ASMODERATOR_IDENTIFY) {
                    $lastpostcell .= $lastuser . ' (' . $moderator. ')';
                } else if ($lastposteranon == \mod_forumng::ASMODERATOR_ANON) {
                    if ($discussion->get_forum()->can_post_anonymously()) {
                        $lastpostcell .= $lastuser . ' (' . $moderator. ')';
                    } else {
                        $lastpostcell .= $moderator;
                    }
                } else {
                    if (\mod_forumng_utils::display_discussion_list_item_author_anonymously($discussion->get_forum(), $USER->id)) {
                        $lastpostcell .= get_string('identityprotected', 'forumng');
                    } else {
                        $lastpostcell .= $lastuser;
                    }
                }
                // Author or started by.
                $posteranon = $discussion->get_poster_anon();
                $poster = $discussion->get_poster();
                $username = $discussion->get_forum()->display_user_name($poster);
                $moderator = get_string('moderator', 'forumng');
                if ($posteranon == \mod_forumng::ASMODERATOR_IDENTIFY) {
                    $startedby = $username . ' (' . $moderator . ')';
                } else if ($posteranon == \mod_forumng::ASMODERATOR_ANON) {
                    if ($discussion->get_forum()->can_post_anonymously()) {
                        $startedby = $username . ' (' . $moderator . ')';
                    } else {
                        $startedby = $moderator;
                    }
                } else {
                    if (\mod_forumng_utils::display_discussion_list_item_author_anonymously($discussion->get_forum(), $USER->id)) {
                        $startedby = get_string('identityprotected', 'forumng');
                    } else {
                        $startedby = $username;
                    }
                }
                // No of posts.
                $noofposts = $discussion->get_num_posts() - 1; // Do not count the initial post as a reply.
                if ($noofposts == 1) {
                    $noofposts = strtolower(get_string('totalreply', 'forumng', $noofposts));
                } else {
                    $noofposts = strtolower(get_string('totalreplies', 'forumng', $noofposts));
                }
                // Date root post created.
                $starteddate = self::date_or_time($discussion->get_root_post()->get_created());

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

    /* Discussion view (a listing of posts or replies to the discussion) */
    /* ----------------------------------------------------------------- */

    /**
     * Displays a page in the mobile app showing the posts in a discussion.
     *
     * @param array $args Arguments from tool_mobile_get_content WS
     * @return array HTML, javascript and otherdata
     * @throws \coding_exception
     */
    public static function posts_view(array $args) : array {
        global $OUTPUT;

        $args = (object) $args;
        $discussion = \mod_forumng_discussion::get_from_id($args->discussionid, \mod_forumng::CLONE_DIRECT);
        $discussion->require_view();

        // Auto mark read - it seems that viewing any bit of a discussion counts as viewing all discussion posts.
        if ($discussion->get_forum()->can_mark_read()) {
            if (\mod_forumng::mark_read_automatically()) {
                $discussion->mark_read();
            }
        }

        // Based on renderer.php render_post, and mod_forumng_post.php display_with_children.
        $root = $discussion->get_root_post();
        $defaultimage = $OUTPUT->image_url('u/f2');
        $moderator = get_string('moderator', 'forumng');
        $postdata = self::get_common_post_data($discussion, $root, $defaultimage, $moderator);
        $postdata['cmid'] = $discussion->get_course_module()->id;
        // No of posts or replies.
        $noofposts = $discussion->get_num_posts() - 1; // Do not count the first message as a reply.
        if ($noofposts == 1) {
            $noofreplies = strtolower(get_string('totalreply', 'forumng', $noofposts));
        } else {
            $noofreplies = strtolower(get_string('totalreplies', 'forumng', $noofposts));
        }
        $postdata['noofreplies'] = $noofreplies;
        $postdata['hasreplies'] = $noofposts > 0;

        // Rootpost (or starter message) is now ready to pass to the template.
        $rootpost = (object) $postdata;

        // Initial replies to the root message are prepared but sent via otherdata.
        // Doing it this way allows sending of more chunks of data (via ajax) to be
        // added to the view when required (user scrolls down for more).
        $replies = self::get_more_posts($discussion, 0);

        return [
            'templates' => [
                [
                    'id' => 'main',
                    'html' => $OUTPUT->render_from_template('mod_forumng/mobile_posts_page', $rootpost),
                ],
            ],
            'javascript' => 'window.forumngPostsPageInit(this);',
            'otherdata' => [
                'replies' => json_encode($replies),
                'discussionid' => $discussion->get_id(),
                'totalposts' => $noofposts
            ],
            'files' => []
        ];
    }

    /**
     * Returns more posts (replies) for a discussion, starting from a count.
     *
     * @param \mod_forumng_discussion $discussion
     * @param int $from Starting count
     * @return array $replies
     * @throws \coding_exception
     */
    public static function get_more_posts(\mod_forumng_discussion $discussion, int $from) : array {
        global $OUTPUT;
        $posts = $discussion->get_root_post_replies(0); // 0 means get all.
        $replies = [];
        $count = 0;
        $defaultimage = $OUTPUT->image_url('u/f2');
        $moderator = get_string('moderator', 'forumng');
        foreach ($posts as $post) {
            // Note ipud has a way of returning similar data (see classes/local/external/get_posts.php)
            // but it does not allow the chunked data that mobile needs.
            if ($count < $from || $count >= (self::NUMBER_POSTS + $from)) {
                $count++;
                continue;
            }
            // Get the main post data.
            $postdata = self::get_common_post_data($discussion, $post, $defaultimage, $moderator);
            // Add any sub-replies. All sub-replies and any other deeper levels of reply
            // will be displayed at just one level of indent.
            $subreplies = [];
            self::get_subreplies($discussion, $post, $defaultimage, $moderator, $subreplies);
            $noofposts = count($subreplies);
            if ($noofposts == 1) {
                $noofreplies = strtolower(get_string('totalreply', 'forumng', $noofposts));
            } else {
                $noofreplies = strtolower(get_string('totalreplies', 'forumng', $noofposts));
            }
            $postdata['hasreplies'] = $noofposts > 0;
            $postdata['noofreplies'] = $noofreplies;
            $postdata['subreplies'] = $subreplies;
            $reply = (object) $postdata;
            $replies[] = $reply;
            $count++;
        }
        return $replies;
    }

    /**
     * A recursive function getting subreplies for a post within a discussion.
     *
     * @param \mod_forumng_discussion $discussion
     * @param \mod_forumng_post $post
     * @param string $defaultimage Default image url
     * @param string $moderator Language string for Moderator
     * @param array $subreplies Array passed by reference containing result of this method
     * @throws \coding_exception
     */
    private static function get_subreplies(\mod_forumng_discussion $discussion, \mod_forumng_post $post,
            string $defaultimage, string $moderator, array &$subreplies) {
        $rawsubreplies = $post->get_replies();
        foreach ($rawsubreplies as $sr) {
            $postdata = self::get_common_post_data($discussion, $sr, $defaultimage, $moderator);
            $subreply = (object) $postdata;
            $subreplies[] = $subreply;
            if (count($sr->get_replies())) {
                self::get_subreplies($discussion, $sr, $defaultimage, $moderator, $subreplies);
            }
        }
    }

    /**
     * Returns common data from a post.
     *
     * @param \mod_forumng_discussion $discussion
     * @param \mod_forumng_post $post
     * @param string $defaultimage Default image url
     * @param string $moderator Language string for Moderator
     * @return array
     * @throws \coding_exception
     */
    private static function get_common_post_data(\mod_forumng_discussion $discussion, \mod_forumng_post $post,
            string $defaultimage, string $moderator) {
        global $PAGE, $USER;
        // Author or started by.
        $poster = $post->get_user();
        $posteranon = $post->get_asmoderator();
        if ($poster) {
            $userimage = new \user_picture($poster);
            $startedbyurl = $userimage->get_url($PAGE)->out();
        } else {
            $startedbyurl = $defaultimage;
        }
        $username = $discussion->get_forum()->display_user_name($poster);
        if ($posteranon == \mod_forumng::ASMODERATOR_IDENTIFY) {
            $startedby = $username . ' ' . $moderator;
        } else if ($posteranon == \mod_forumng::ASMODERATOR_ANON) {
            if ($discussion->get_forum()->can_post_anonymously()) {
                $startedby = $username . ' ' . $moderator;
            } else {
                $startedby = $moderator;
                $startedbyurl = $defaultimage;
            }
        } else {
            if (\mod_forumng_utils::display_discussion_list_item_author_anonymously($discussion->get_forum(), $USER->id)) {
                $startedby = get_string('identityprotected', 'forumng');
                $startedbyurl = $defaultimage;
            } else {
                $startedby = $username;
            }
        }
        // Attachments.
        $attachmentarray = [];
        $attachments = $post->get_attachment_names();
        foreach ($attachments as $key => $attachment) {
            $attachmentarray[] = (object) [
                    'name' => $attachment,
                    'url' => $post->get_attachment_url($attachment)->out()
            ];
        }
        // Mark post read.
        $canmarkread = false; // Don't show 'mark post as read' button.
        if ($discussion->get_forum()->can_mark_read() && !\mod_forumng::mark_read_automatically() && $post->is_unread()) {
            $canmarkread = true;
        }
        $whynot = ''; // Required by can_reply but not used.
        return [
            'postid' => $post->get_id(),
            'subject' => $post->get_subject(),
            'startedby' => $startedby,
            'startedbyurl' => $startedbyurl,
            'message' => self::message_display($post),
            'starteddate' => self::date_or_time($post->get_created()),
            'attachments' => $attachmentarray,
            'isimportant' => $post->is_important(),
            'isflagged' => $post->is_flagged(),
            'isunread' => $post->is_unread(),
            'canmarkread' => $canmarkread,
            'isexpanded' => self::show_expanded($post),
            'canreply' => $post->can_reply($whynot),
            'replyto' => get_string('reply', 'mod_forumng', $post->get_id())
        ];
    }

    /**
     * Displays a page in the mobile app for adding (or editing) a discussion.
     *
     * @param array $args Arguments from tool_mobile_get_content WS
     * @return array HTML, javascript and otherdata
     * @throws \coding_exception
     */
    public static function add_discussion(array $args) : array {
        global $OUTPUT;

        $args = (object) $args;
        $cmid = $args->cmid;
        $discussionid = $args->discussionid;
        $selectedgroupid = $args->groupid;
        $forumng = \mod_forumng::get_from_cmid($cmid, \mod_forumng::CLONE_DIRECT);
        $cm = $forumng->get_course_module();
        $groupid = \mod_forumng::get_activity_group($cm, true);
        // Null if student not in a group, or in two groups, and -1 if no groups allowed.
        if ($selectedgroupid > 0 && is_null($groupid)) {
            $groupid = $selectedgroupid;
        }
        $forumng->require_start_discussion($groupid);

        $data = [
            'cmid' => $cmid,
            'submitlabel' => get_string('postdiscussion', 'mod_forumng'),
            'subject' => '',
            'message' => null,
            'maxsize' => $forumng->get_max_bytes() // There is no limit to 'maxSubmissions' in forumng.
        ];
        $html = $OUTPUT->render_from_template('mod_forumng/mobile_add_discussion', $data);

        return [
            'templates' => [
                [
                    'id' => 'main',
                    'html' => $html
                ]
            ],
            'javascript' => 'window.forumngAddDiscussionInit(this);',
            'otherdata' => [
                'files' => json_encode([]),
                'forumng' => $forumng->get_id(),
                'discussion' => $discussionid,
                'group' => $groupid
            ],
            'files' => []
        ];
    }

    /**
     * Displays a page in the mobile app for adding (or editing) a reply.
     *
     * @param array $args Arguments from tool_mobile_get_content WS
     * @return array HTML, javascript and otherdata
     * @throws \coding_exception
     */
    public static function reply(array $args) : array {
        global $OUTPUT;

        $args = (object) $args;
        $replyto = $args->replyto;
        $replytopost = \mod_forumng_post::get_from_id($replyto, 0);
        $replytopost->require_view();
        $forumng = $replytopost->get_forum();
        $cm = $forumng->get_course_module();
        $whynot = '';
        $canreply = $replytopost->can_reply($whynot);
        if (!$canreply) {
            throw new \Exception(get_string($whynot, 'mod_forumng'));
        }

        $data = [
            'cmid' => $cm->id,
            'submitlabel' => get_string('reply', 'mod_forumng', $replytopost->get_id()),
            'subject' => '',
            'message' => null,
            'maxsize' => $forumng->get_max_bytes()
        ];
        $html = $OUTPUT->render_from_template('mod_forumng/mobile_reply', $data);

        return [
            'templates' => [
                [
                    'id' => 'main',
                    'html' => $html
                ]
            ],
            'javascript' => 'window.forumngReplyInit(this);',
            'otherdata' => [
                'files' => json_encode([]),
                'forumng' => $forumng->get_id(),
                'replyto' => $replytopost->get_id()
            ],
            'files' => []
        ];
    }

    /* Utility functions */
    /* ----------------- */

    /**
     * Present a message in one line only, truncating to length.
     *
     * @param string $text
     * @param int $length Optional number of chars to show.
     * @return string
     */
    private static function one_line(string $text, int $length=0) : string {
        $text = strip_tags($text);
        if (!$length) {
            $length = self::SHORTENED_LENGTH;
        }
        return shorten_text($text, $length);
    }

    /**
     * Mobile date/time presentation.
     *
     * If the timestamp is more than a day old then show date,
     * otherwise show the time.
     *
     * @param int $timestamp
     * @return string
     * @throws \coding_exception
     */
    private static function date_or_time(int $timestamp) : string {
        $yesterday = (new \DateTime('-1 day', \core_date::get_user_timezone_object()))->getTimestamp();
        if ($timestamp > $yesterday) {
            $out = userdate($timestamp, get_string('strftimetime24', 'langconfig'));
        } else {
            $out = userdate($timestamp, get_string('strftimedate', 'langconfig'));
        }
        return $out;
    }

    /**
     * Mobile message display.
     *
     * Show the full message if unread, flagged or parent of an unread post, otherwise collapse to one line.
     * If there is a change of subject show that instead of the message on collapsed posts.
     * On the Mobile platform collapsed posts cannot be expanded (without a new ajax call). This minimises data sent.
     *
     * @param \mod_forumng_post $post
     * @param int $length Optional number of chars to show.
     * @return string
     */
    private static function message_display(\mod_forumng_post $post, int $length=0) : string {
        if (self::show_expanded($post)) {
            return $post->get_formatted_message();
        } else {
            if ($post->get_subject()) {
                $message = $post->get_subject();
            } else {
                $message = $post->get_formatted_message();
            }
            return self::one_line($message, $length);
        }
    }

    /**
     * Should a post be shown expanded (in full) or in a shorter collaped format?
     *
     * @param \mod_forumng_post $post
     * @return bool
     * @throws \coding_exception
     */
    private static function show_expanded(\mod_forumng_post $post) : bool {
        // Logic is from mod_forumng_post.php display_with_children.
        if ($post->is_unread() || $post->has_unread_child() || $post->is_flagged()) {
            return true;
        } else {
            return false;
        }
    }
}
