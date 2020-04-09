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
use mod_forumng;

require_once(__DIR__ . '/../../mod_forumng.php');
require_once(__DIR__ . '/../../mod_forumng_utils.php');
require_once(__DIR__ . '/../../renderer.php');
require_once(__DIR__ . '/../../feature/forumngfeature_discussion.php');
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

    /** Discussion sort: by date. */
    const SORT_DATE = 0;
    /** Discussion sort: by subject. */
    const SORT_SUBJECT = 1;
    /** Discussion sort: by unread replies. */
    const SORT_UNREAD = 4;


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
        $isupdate = empty($args->isupdate) ? 0 : 1;
        $cmid = $args->cmid;
        $sortid = empty($args->sortid) ? 0 : $args->sortid;

        // This is based on view.php and forumngtype_general.php.
        $forumng = \mod_forumng::get_from_cmid($cmid, \mod_forumng::CLONE_DIRECT);
        $isipud = $forumng->get_type() instanceof \forumngtype_ipud;
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

        try {
            $forumng->require_view($groupid);
        } catch (\moodle_exception $e) {
            $error = $e->getMessage();
            $data = [
                'error' => $error,
            ];

            $html = $OUTPUT->render_from_template('mod_forumng/mobile_discussions_page', $data);
            return [
                'templates' => [
                    [
                        'id' => 'main',
                        'html' => $html,
                    ]
                ],
                'javascript' => '',
                'otherdata' => [
                ],
                'files' => []
            ];
        }

        // Completion and logging.
        // Do this forum view logging from the server end, as it is surely not possible to view the forum when offline.
        $completion = new \completion_info($course);
        $completion->set_module_viewed($cm);
        $forumng->log('view'); // Note for later. Log post reads from the mobile end.
        $pageurl = new \moodle_url('/mod/forumng/view.php', ['id' => $forumng->get_id()]); // Not actually used.
        $forumngrenderer = $forumng->init_page($pageurl); // Renderer is not needed, but setting up the $PAGE is probably useful.

        // Groups.
        $hasgroups = false; // No visible groups, or user not in a group - display nothing.
        $groups = []; // User in more than one group, or has access to all - display group selector.
        $grouplabel = get_string('group', 'forumng');
        $allowedgroups = groups_get_activity_allowed_groups($cm);
        $activegroup = groups_get_activity_group($cm, true, $allowedgroups);
        $groupmode = $forumng->get_group_mode();
        if ($groupmode) {
            if ($allowedgroups) {
                $hasgroups = true;
                $aag = has_capability('moodle/site:accessallgroups', $context);
                if ($groupmode == VISIBLEGROUPS or $aag) {
                    $groups[] = (object)['groupid' => 0, 'groupname' => get_string('allparticipants')];
                    $groupid = $activegroup;
                }
                foreach ($allowedgroups as $g) {
                    $groups[] = (object)['groupid' => $g->id, 'groupname' => format_string($g->name)];
                }
                if ($groupmode == VISIBLEGROUPS) {
                    $grouplabel = get_string('groupsvisible', 'group');
                } else {
                    $grouplabel = get_string('groupsseparate', 'group');
                }
                if ($aag and $cm->groupingid) {
                    if ($grouping = groups_get_grouping($cm->groupingid)) {
                        $grouplabel = $grouplabel . ' (' . format_string($grouping->name) . ')';
                    }
                }
            }

            if (!is_null($selectedgroupid)) {
                $groupid = $selectedgroupid;
            }

            if (!$isupdate) {
                $groupid = $activegroup;
            }
        }

        // Discussions.
        $canviewhidden = $forumng->can_view_hidden();
        $page = 1; // Forumng only serves 'pages' of discussions. So start with the first page, infinite scroll provides more.
        $discussionlist = $forumng->get_discussion_list($groupid, $canviewhidden, '', $sortid);
        $totaldiscussions = $discussionlist->get_total_discussions();
        $hasdiscussions = $totaldiscussions > 0;
        $sticky = $discussionlist->get_sticky_discussions();
        $normal = $discussionlist->get_normal_discussions();
        $discussions = [];
        $manualmark = !mod_forumng::mark_read_automatically();

        // Sticky always first.
        self::prep_discussions($forumng, $sticky, $discussions);
        self::prep_discussions($forumng, $normal, $discussions);

        // Print info about the start and end dates of the forum from the form setting.
        $message = '';
        $stringend =
            has_capability('mod/forumng:ignorepostlimits', $forumng->get_context())
                ? 'capable' : '';
        $startdate = $forumng->get_postingfrom();
        $enddate = $forumng->get_postinguntil();

        if (time() < $startdate) {
            $message = get_string('beforestartdate' . $stringend,
                'forumng', \mod_forumng_utils::display_date($startdate));
        } else if (time() < $enddate) {
            $message = get_string('beforeenddate' . $stringend,
                'forumng', \mod_forumng_utils::display_date($enddate));
        }

        if ($enddate && time() >= $enddate) {
            $message = get_string('afterenddate' . $stringend,
                'forumng', \mod_forumng_utils::display_date($enddate));

        }

        // Draft posts.
        $drafts = $forumng->get_drafts();
        $hasdrafts = false;
        $draftposts = [];
        $decorators = '';
        $shortenedlenght = 40;
        if (count($drafts) > 0) {
            $hasdrafts = true;

            $renderer = $forumng->get_type()->get_renderer();
            $heading = get_string('drafts', 'forumng');
            $urlhelp = $renderer->image_url('help')->out();
            $althelp = get_string('helpprefix3', 'format_oustudyplan', $heading);
            $decorators = (object)['heading' => $heading, 'url' => $urlhelp, 'alt' => $althelp,
                'content' => get_string('drafts_help', 'forumng')];
            foreach ($drafts as $draft) {
                $subject = format_string($draft->get_subject());
                $subject = format_text($subject, FORMAT_HTML);
                $summary = strip_tags($draft->get_formatted_message($forumng));
                $summary = self::one_line($summary, $shortenedlenght);
                $summary = format_text($summary, FORMAT_HTML);
                if (trim($summary) === '') {
                    $summary = get_string('notext', 'forumng');
                }
                if (strlen($subject) >= $shortenedlenght) {
                    $subject = self::one_line($subject, $shortenedlenght);
                    $summary = '';
                }
                if ($draft->is_reply()) {
                    $user = fullname($draft->get_reply_to_user(), has_capability('moodle/site:viewfullnames', $context));
                    $discussionsubject = format_string($draft->get_discussion_subject()) . get_string('draft_inreplyto',
                            'forumng', $user);
                } else {
                    $discussionsubject = get_string('draft_newdiscussion', 'forumng');
                }
                $date = \mod_forumng_utils::display_date($draft->get_saved());
                $draftposts[] = (object)['subject' => $subject, 'message' => $summary,
                    'discussionsubject' => $discussionsubject, 'date' => $date];
            }
        }

        // Sort discussion.
        $selectedsort = self::SORT_DATE;
        if ($sortid > 0) {
            $selectedsort = $sortid;
        }

        $sortoption[] = (object)['sortid' => self::SORT_SUBJECT, 'title' => get_string('sortbytitle', 'forumng')];
        $sortoption[] = (object)['sortid' => self::SORT_DATE, 'title' => get_string('sortbydateoflastpost', 'forumng')];
        if ($forumng->can_mark_read()) {
            $sortoption[] = (object)['sortid' => self::SORT_UNREAD, 'title' => get_string('sortbymostunreadposts', 'forumng')];
        }

        // Data prep.
        $forum = new \stdClass();
        $forum->id = $forumng->get_id();
        $forum->name = format_string($forumng->get_name());
        // Not used currently $forum->intro = $cm->get_formatted_content().
        list($forum->introduction, $unusedintroductionformat) =
                external_format_text($forumng->get_introduction(), $forumng->get_introduction_format(), $context->id,
                    'mod_forumng', 'introduction');
        $whynot = '';
        $data = [
            'forum' => $forum,
            'hasgroups' => $hasgroups,
            'groups' => $groups,
            'grouplabel' => $grouplabel,
            'hasdiscussions' => $hasdiscussions,
            'cmid' => $cm->id,
            'courseid' => $course->id,
            'canstartdiscussion' => $forumng->can_start_discussion($groupid, $whynot),
            'message' => $message,
            'decorators' => $decorators,
            'hasdrafts' => $hasdrafts,
            'isipud' => $isipud,
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
                'page' => $page,
                'draftposts' => json_encode($draftposts),
                'selectedsort' => $selectedsort,
                'sortoption' => json_encode($sortoption),
                'manualmark' => $manualmark,
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
                if ($discussion->is_locked()) {
                    $classes .= ' forumng-locked';
                    $alts[] = get_string('alt_discussion_locked', 'forumng');
                    $icons[] = ['i/unlock', 'moodle'];
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
                $rootpost = $discussion->get_root_post()->get_formatted_message();
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
                $lastpostcell = \mod_forumng_utils::display_date($discussion->get_time_modified());
                $discussurl = '';
                if($forum->get_type() instanceof \forumngtype_ipud) {
                    $lastpostcell = count($discussion->get_root_post()->get_replies()) > 0  ? $lastpostcell :
                            get_string('nopostsyet', 'forumngtype_ipud');
                    if (!is_null($discussion->get_group_id())) {
                        $discussurl = $discussion->get_location(true) . '&groupid=' . $discussion->get_group_id();
                    } else {
                        $discussurl = $discussion->get_location(true);
                    }

                    $classes .= ' forumng-ipud';
                }

                $discussions[] = (object)[
                    'id' => $discussion->get_id(),
                    'subject' => $subject,
                    'unread' => '' . $unreadposts,
                    'rootpost' => $rootpost,
                    'url' => $discussurl,
                    'unreadpostsalt' => $unreadpostsalt,
                    'lastpost' => $lastpostcell,
                    'classes' => $classes,
                    'decorators' => $decorators,
                    'groupid' => $discussion->get_group_id(),
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

        // Check can lock discussion.
        $canlock = false;
        $type = $discussion->get_forum()->get_type();
        foreach (\forumngfeature_discussion::get_all($type) as $feature) {
            if (get_class($feature) === 'forumngfeature_lock' && $feature->should_display($discussion) &&
                $type->allow_forumngfeature_discussion($discussion, $feature)) {
                $canlock = true;
            }
        }

        // Default collaspe all posts.
        $toggle = get_string('expandallpost', 'forumng');

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

        // Initial replies to the root message are prepared but sent via otherdata.
        // Doing it this way allows sending of more chunks of data (via ajax) to be
        // added to the view when required (user scrolls down for more).
        $replies = self::get_more_posts($discussion, 0);

        $renderer = $discussion->get_forum()->get_type()->get_renderer();
        $importanticon = (object)[
            'url' => $renderer->image_url('exclamation_mark', 'mod_forumng')->out(),
            'alt' => get_string('important', 'forumng'),
        ];

        $islock = false;
        $lockpost = '';
        if ($discussion->is_locked()) {
            $lockpost = end($replies);
            $islock = true;
            $canlock = false;
            array_pop($replies);
            if(is_null($lockpost->subject)) {
                $lockpost->subject = 'Re: ' . $postdata['subject'];
            }
        }
        $postdata['canlock'] = $canlock;

        // Rootpost (or starter message) is now ready to pass to the template.
        $rootpost = (object)$postdata;

        return [
            'templates' => [
                [
                    'id' => 'main',
                    'html' => $OUTPUT->render_from_template('mod_forumng/mobile_posts_page', $rootpost),
                ],
            ],
            'javascript' => 'window.forumngPostsPageInit(this);',
            'otherdata' => [
                'files' => json_encode([]),
                'replies' => json_encode($replies),
                'discussionid' => $discussion->get_id(),
                'forumngid' => $discussion->get_forum()->get_id(),
                'importanticon' => json_encode($importanticon),
                'totalposts' => $noofposts,
                'toggle' => strtoupper($toggle),
                'canlock' => $canlock,
                'islock' => $islock,
                'lockpost' => json_encode($lockpost),
                'lock' => 0,
                'postas' => 0,
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
        $defaultimage = $OUTPUT->image_url('u/f2');
        $moderator = get_string('moderator', 'forumng');
        foreach ($posts as $post) {
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

        $hasanon = true;
        $createdbymoderator = '';
        if ($posteranon == \mod_forumng::ASMODERATOR_IDENTIFY) {
            $startedby = $username . '<br><strong>' . $moderator . '</strong>';
        } else if ($posteranon == \mod_forumng::ASMODERATOR_ANON) {
            $startedby = $post->is_important() ? '<br><strong>' . $moderator . '</strong>' : '<strong>' . $moderator . '</strong>';
            $createdbymoderator = get_string('createdbymoderator', 'forumng', $username);
            if (!$discussion->get_forum()->can_post_anonymously()) {
                $startedbyurl = $defaultimage;
                $hasanon = false;
                $createdbymoderator= '';
            }
        } else {
            if (\mod_forumng_utils::display_discussion_list_item_author_anonymously($discussion->get_forum(), $USER->id)) {
                $startedby = get_string('identityprotected', 'forumng');
                $startedbyurl = $defaultimage;
            } else {
                $startedby = $username;
            }
        }
        $suject = $post->get_subject();
        $message = $post->get_formatted_message();
        if ($post->is_root_post()) {
            $suject = str_replace('"', '&quot;', $post->get_subject());
            $message = str_replace('"', '&quot;', $post->get_formatted_message());
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
        $forumng = $discussion->get_forum();
        if ($forumng->can_mark_read() && !\mod_forumng::mark_read_automatically() && $post->is_unread()) {
            $canmarkread = true;
        }
        $hasoption = false;
        $options = self::post_as_option($forumng);
        if (!empty($options)) {
            $hasoption = true;
        }
        $whynot = ''; // Required by can_reply but not used.
        $shortentext = self::shorten_text_display($post);
        $shortendisplay = '<strong>' . $shortentext->shortensubject . '</strong> ' . $shortentext->shortenmessage;
        if (strlen($suject) >= self::SHORTENED_LENGTH) {
            $shortendisplay = '<strong>' . $shortentext->shortensubject . '</strong>';
        }

        $historyedit = '';
        $isanon = ($posteranon == \mod_forumng::ASMODERATOR_ANON && $forumng->get_can_post_anon());
        $indicatemoderator = ($posteranon == \mod_forumng::ASMODERATOR_IDENTIFY);
        if ((($isanon && $discussion->get_forum()->can_post_anonymously()) || $indicatemoderator) || !$isanon) {
            if ($edituser = $post->get_edit_user()) {
                $edit = new \stdClass;
                $edit->date = \mod_forumng_utils::display_date($post->get_modified());

                if (\mod_forumng_utils::display_discussion_author_anonymously($post, $USER->id)) {
                    $edit->name = get_string('identityprotected', 'mod_forumng');
                } else {
                    $edit->name = $discussion->get_forum()->display_user_name($edituser);
                }
                if ($edituser->id == $post->get_user()->id) {
                    $historyedit = get_string('editbyself', 'forumng', $edit->date);
                } else {
                    $historyedit = get_string('editbyother', 'forumng', $edit);
                }
                $href = new \moodle_url('/mod/forumng/history.php');
                if ($post->can_view_history($whynot)) {
                    $url = ' (<a href="' . $href . '?' . $post->get_link_params(mod_forumng::PARAM_HTML) . '">' .
                        get_string('history', 'forumng') . '</a>)';
                    $historyedit .= $url;
                }
            }
        }

        return [
            'postid' => $post->get_id(),
            'subject' => $suject,
            'startedby' => $startedby,
            'startedbyurl' => $startedbyurl,
            'message' => $message,
            'starteddate' => \mod_forumng_utils::display_date($post->get_created()),
            'attachments' => $attachmentarray,
            'isimportant' => $post->is_important(),
            'isflagged' => $post->is_flagged(),
            'isunread' => $post->is_unread() ? 'unread-post' : '',
            'canmarkread' => $canmarkread,
            'isexpanded' => self::show_expanded($post),
            'shortendisplay' => $shortendisplay,
            'canreply' => $post->can_reply($whynot),
            'canedit' => $post->can_edit($whynot),
            'candelete' => $post->can_delete($whynot),
            'replyto' => get_string('reply', 'mod_forumng', $post->get_id()),
            'edit' => get_string('edit', 'mod_forumng', $post->get_id()),
            'delete' => get_string('delete', 'mod_forumng', $post->get_id()),
            'postasstring' => get_string('asmoderator', 'mod_forumng'),
            'hasoption' => $hasoption,
            'options' => $options,
            'maxsize' => $forumng->get_max_bytes(),
            'hasanon' => $hasanon,
            'createdbymoderator' => $createdbymoderator,
            'historyedit' => $historyedit
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
        $groupid = (int)$args->groupid;
        $forumng = \mod_forumng::get_from_cmid($cmid, \mod_forumng::CLONE_DIRECT);
        $cm = $forumng->get_course_module();
        // Only add moderator element to post edit form if op1 or op2 available.
        $options = null;
        $hasoption = false;
        $canviewhidden = $forumng->can_manage_discussions() && $forumng->can_view_hidden();
        $cantag = $forumng->can_manage_discussions() || $forumng->can_tag_discussion();
        $options = self::post_as_option($forumng);
        if (!empty($options)) {
            $hasoption = true;
        }

        $forumng->require_start_discussion($groupid);
        $postas = get_string('postasmobile', 'mod_forumng');
        $displayoption = get_string('displayoption', 'mod_forumng');
        $displayperiod = get_string('displayperiodmobile', 'mod_forumng');
        $data = [
            'cmid' => $cmid,
            'submitlabel' => get_string('postdiscussion', 'mod_forumng'),
            'subject' => '',
            'message' => null,
            'maxsize' => $forumng->get_max_bytes(), // There is no limit to 'maxSubmissions' in forumng.
            'sticky' => 0,
            'options' => $options,
            'hasoption' => $hasoption,
            'canviewhidden' => $canviewhidden,
            'cantag' => $cantag,
            'postas' => $postas,
            'displayoption' => $displayoption,
            'displayperiod' => $displayperiod,
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
                'group' => $groupid,
                'showsticky' => 0,
                'showfrom' => 0,
                'postas' => 0,
                'cmid' => $forumng->get_course_module_id(),
                'maxyear' => date('Y', strtotime('+30 years')),
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
     * Mobile shorten text display.
     *
     * Show shorten text of subject and message in post.
     * @param \mod_forumng_post $post
     * @return string
     */
    private static function shorten_text_display(\mod_forumng_post $post) : object {
        $message = strip_tags($post->get_formatted_message(), '<img>');
        $subject = format_string($post->get_subject());
        $subject = format_text($subject, FORMAT_HTML);

        $shortensubject = $subject;
        if (strlen($subject) >= self::SHORTENED_LENGTH) {
            $shortensubject = \mod_forumng_renderer::nice_shorten_text($shortensubject, self::SHORTENED_LENGTH);
        }

        $shortenmessage = $message;
        if (strlen($message) >= self::SHORTENED_LENGTH) {
            $shortenmessage = \mod_forumng_renderer::nice_shorten_text($message, self::SHORTENED_LENGTH);
            $shortenmessage = format_text($shortenmessage, FORMAT_HTML);
        }

        return (object)['shortensubject' => $shortensubject, 'shortenmessage' => $shortenmessage];
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

    /**
     * Show Post as option.
     *
     * @param mod_forumng $forumng
     * @return array
     * @throws \coding_exception
     */
    private static function post_as_option(\mod_forumng $forumng) {
        $options = [];
        if ($forumng->can_indicate_moderator()) {
            $option1 = new \stdClass();
            $option1->key = \mod_forumng::ASMODERATOR_NO;
            if ($forumng->get_can_post_anon() == mod_forumng::CANPOSTATON_NONMODERATOR) {
                $option1->value = get_string('asmoderator_post_anon', 'forumng');
            } else {
                $option1->value = get_string('asmoderator_post', 'forumng');
            }
            $options[] = $option1;
            $option2 = new \stdClass();
            $option2->key = \mod_forumng::ASMODERATOR_IDENTIFY;
            $option2->value = get_string('asmoderator_self', 'forumng');
            $options[] = $option2;
            if ($forumng->can_post_anonymously()) {
                $option3 = new \stdClass();
                $option3->key = \mod_forumng::ASMODERATOR_ANON;
                $option3->value = get_string('asmoderator_anon', 'forumng');
                $options[] = $option3;
            }
        }
        return $options;
    }
}
