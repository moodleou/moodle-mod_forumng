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
 * Forum type: Normal forum.
 * @package forumngtype
 * @subpackage general
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class forumngtype_general extends forumngtype {
    /**
     * Displays the view page (usually showing a list of discussions).
     * @param mod_forumng $forum Forum
     * @param int $groupid Group ID
     */
    public function print_view_page($forum, $groupid) {
        global $SESSION, $PAGE, $USER;
        $out = mod_forumng_utils::get_renderer();
        $forumngid = $forum->get_id();
        $baseurl = 'view.php?' . $forum->get_link_params(mod_forumng::PARAM_PLAIN);

        if (isset($SESSION->forumng_discussionlist[$forumngid]) &&
            property_exists($SESSION->forumng_discussionlist[$forumngid], 'groupid') &&
            $SESSION->forumng_discussionlist[$forumngid]->groupid != $groupid) {
            unset($SESSION->forumng_discussionlist[$forumngid]->page);
            unset($SESSION->forumng_discussionlist[$forumngid]->groupid);
        }

        // Remember the sort order and page number in session variables
        // Unset the page session variable when the sort links are clicked
        // or groupid has been changed (using the group dropdown box)
        $sortorder = optional_param('sort', '' , PARAM_ALPHA);
        if (!$sortorder) {
            if (isset($SESSION->forumng_discussionlist[$forumngid]->sort)) {
                $sortorder = $SESSION->forumng_discussionlist[$forumngid]->sort;
            } else {
                $sortorder = 'd';
            }
        } else {
            if (optional_param('sortlink', '' , PARAM_ALPHA)) {
                if (!isset($SESSION->forumng_discussionlist[$forumngid])) {
                    $SESSION->forumng_discussionlist[$forumngid] = new stdClass();
                }
                $SESSION->forumng_discussionlist[$forumngid]->sort = $sortorder;
                unset ($SESSION->forumng_discussionlist[$forumngid]->page);
            }
        }

        $page = optional_param('page', 0, PARAM_INT);
        if (!$page) {
            if (isset($SESSION->forumng_discussionlist[$forumngid]->page)) {
                $page = $SESSION->forumng_discussionlist[$forumngid]->page;
            } else {
                $page = 1;
            }
        } else {
            if (!isset($SESSION->forumng_discussionlist[$forumngid])) {
                $SESSION->forumng_discussionlist[$forumngid] = new stdClass();
            }
            $SESSION->forumng_discussionlist[$forumngid]->page = $page;
            $SESSION->forumng_discussionlist[$forumngid]->groupid = $groupid;
        }

        $baseurl .= '&page='.$page;

        $sortchar = substr($sortorder, 0, 1);
        if (strlen($sortorder) == 2) {
            $sortreverse = (substr($sortorder, 1, 1) == 'r') ? true : false;
        } else {
            $sortreverse = false;
        }

        $baseurl .= '&sort='.$sortchar;
        $baseurl .= ($sortreverse) ? 'r':'';

        $sort = mod_forumng::get_sort_code($sortchar);

        // Get tagid if used.
        $tag = optional_param('tag', null, PARAM_ALPHANUM);
        $list = $forum->get_discussion_list($groupid, $forum->can_view_hidden(),
                $page, $sort, $sortreverse, 0, true,  $tag);
        $sticky = $list->get_sticky_discussions();
        $normal = $list->get_normal_discussions();

        // Remove discussions from list if the forumtype thinks we can't see
        // them
        foreach ($sticky as $key => $value) {
            if (!$this->can_view_discussion($value)) {
                unset($sticky[$key]);
            }
        }
        foreach ($normal as $key => $value) {
            if (!$this->can_view_discussion($value)) {
                unset($normal[$key]);
            }
        }

        // Intro
        print $out->render_introduction($forum);

        // Flagged posts skip link.
        $flaggedposts = $forum->get_flagged_posts();
        $flagdiscussions = $forum->get_flagged_discussions();
        $flaggeddiscussions = array();

        // Need to loop through flagged discussions removing any that can not be viewed by user.
        foreach ($flagdiscussions as $discussion) {
            if ($discussion->can_view($USER->id)) {
                // Add to flagged discussions.
                array_push($flaggeddiscussions, $discussion);
            }
        }

        if ((count($flaggedposts) + count($flaggeddiscussions)) > 0) {
            $output = html_writer::start_tag('div', array('class' => 'forumng-flagged-link'));
            $output .= $out->render_flagged_list_link($flaggeddiscussions, true);
            $output .= $out->render_flagged_list_link($flaggedposts);
            $output .= html_writer::end_tag('div');
            echo $output;
        }

        // Draft posts
        $drafts = $forum->get_drafts();
        if (count($drafts) > 0) {
            print $out->render_draft_list_start();
            foreach ($drafts as $draft) {
                print $out->render_draft_list_item($forum, $draft, $draft==end($drafts));
            }
            print $out->render_draft_list_end();
        }

        // Print info about the start and end dates of the forum from the form setting
        $stringend =
            has_capability('mod/forumng:ignorepostlimits', $forum->get_context())
            ? 'capable' : '';
        $startdate = $forum->get_postingfrom();
        $enddate = $forum->get_postinguntil();

        // Before start date
        if (time() < $startdate) {
            $message = get_string('beforestartdate' . $stringend,
                    'forumng', mod_forumng_utils::display_date($startdate));
            print "<div class='forumng-show-dates'>$message</div>";
        } else if (time() < $enddate) {
            $message = get_string('beforeenddate' . $stringend,
                    'forumng', mod_forumng_utils::display_date($enddate));
            print "<div class='forumng-show-dates'>$message</div>";
        }

        // After end date
        if ($enddate && time() >= $enddate) {
            $message = get_string('afterenddate' . $stringend,
                    'forumng', mod_forumng_utils::display_date($enddate));
            print "<div class='forumng-show-dates'>$message</div>";
        }

        // Show Alert info.
        if ($forum->has_reporting_email()) {
            print $out->box(get_string('alert_intro', 'forumng'), 'generalbox', 'forumng-reportingon');
        }

        // Post button - temporarily disabled when in all-groups mode
        print ($groupid == null) ? '':$forum->display_post_button($groupid);
        if ($taglist = $forum->get_tags_used($groupid)) {
            print $out->render_tag_filter($taglist, $forum, $tag);
        }

        // Provide link to skip sticky discussions.
        if (count($sticky) > 0 && count($normal) > 0) {
            print $out->render_skip_link(key($normal));
        }

        print $list->display_paging_bar($baseurl);

        if (count($sticky) + count($normal) > 0) {
            print $out->render_discussion_list_start(
                    $forum, $groupid, $baseurl, $sort, $sortreverse);
            foreach ($sticky as $discussion) {
                print $out->render_discussion_list_item($discussion, $groupid,
                    count($normal) == 0 && $discussion == end($sticky));
            }
            if (count($sticky) > 0 && count($normal) > 0) {
                print $out->render_discussion_list_divider($forum, $groupid);
            }
            foreach ($normal as $discussion) {
                print $out->render_discussion_list_item($discussion, $groupid,
                        $discussion == end($normal));
            }
            print $out->render_discussion_list_end($forum, $groupid);
        } else {
            print '<p class="forumng-nodiscussions">' .
                $this->get_string($forum, 'nodiscussions') . '</p>';
        }

        print $list->display_paging_bar($baseurl);

        print $forum->display_forumngfeature_discussion_lists($groupid);

        // Flagged discussions.
        if (count($flaggeddiscussions) > 0) {
            print $out->render_flagged_list_start(true);
            foreach ($flaggeddiscussions as $discussion) {
                print $out->render_flagged_discuss_list_item($discussion, $discussion === end($flaggeddiscussions));
            }
            print $out->render_flagged_list_end();
        }

        // Flagged posts.
        if (count($flaggedposts) > 0) {
            print $out->render_flagged_list_start();
            foreach ($flaggedposts as $post) {
                print $out->render_flagged_list_item($post,
                    $post === end($flaggedposts));
            }
            print $out->render_flagged_list_end();
        }

        print $out->render_forum_footer($forum, $groupid);

        // display the warning message for invalid archive setting
        print $forum->display_archive_warning();

        // Display sharing information
        print $forum->display_sharing_info();
    }

    /**
     * Displays the discussion page.
     * @param mod_forumng_discussion $discussion Discussion
     */
    public function print_discussion_page($discussion) {
        global $PAGE;
        $out = mod_forumng_utils::get_renderer();

        print $out->render_discussion_header($discussion);

        $previousread = (int)$discussion->get_time_read();

        // 'Read date' option (used when viewing all posts so that they keep
        // their read/unread colouring)
        $timeread = optional_param('timeread', 0, PARAM_INT);
        if ($timeread) {
            $discussion->pretend_time_read($timeread);
            $previousread = $timeread;
        }

        // 'Expand all' option (always chosen for non-JS browsers)
        $expandall = optional_param('expand', 0, PARAM_INT)
            || $PAGE->devicetypeinuse == 'legacy';
        // 'Expand all' option (always chosen for non-JS browsers)
        $collapseall = optional_param('collapse', 0, PARAM_INT);
        if (!$collapseall && !$expandall && $PAGE->devicetypeinuse == 'mobile') {
            $collapseall = 1;
        }

        // Link back to first unread post if there is one
        print $discussion->display_unread_skip_link();

        // Magic expand tracker (for use in JS only, never set server-side).
        // This tracks expanded posts, and makes the Back button 'work' in
        // the sense that it will expand these posts again.
        print '<form method="post" action="."><div>'.
            '<input type="hidden" id="expanded_posts" name="expanded_posts" ' .
            'value="" /></div></form>';

        // Get content for all posts in the discussion
        $options = array();
        if ($expandall) {
            $options[mod_forumng_post::OPTION_CHILDREN_EXPANDED] = true;
        }
        if ($collapseall) {
            $options[mod_forumng_post::OPTION_CHILDREN_COLLAPSED] = true;
        }
        $content = $out->render_discussion($discussion, $options);

        // Some post display options use the read time to construct links
        // (usually for non-JS version) so that unread state is maintained.
        $options[mod_forumng_post::OPTION_READ_TIME] = $previousread;

        // Display expand all option if there are any 'Expand' links in content
        $fakedate = '&amp;timeread=' . $previousread;
        print '<div id="forumng-expandall">';
        $showexpandall = preg_match(
            '~<a [^>]*href="discuss\.php\?d=[0-9]+[^"]*&amp;expand=1#p[0-9]+">~',
            $content);
        // Note: On bad browsers we always expand all posts
        $showcollapseall = preg_match(
            '~<div class="forumng-post forumng-full.*<div class="forumng-post forumng-full~s',
            $content) && $PAGE->devicetypeinuse != 'legacy';
        if ($showexpandall) {
            print '<a class="forumng-expandall-link" href="' .
                        $discussion->get_url(mod_forumng::PARAM_HTML) . '&amp;expand=1' .
                        $fakedate . '">' . get_string('expandall', 'forumng') . '</a>';
            if ($showcollapseall) {
                print '<span class="forumng-dot-separator"> &#x2022; </span>';
            }
        }
        if ($showcollapseall) {
            print '<a class="forumng-collapseall-link" href="' .
                    $discussion->get_url(mod_forumng::PARAM_HTML) . '&amp;collapse=1' .
                    $fakedate . '">' . get_string('collapseall', 'forumng') . '</a> ';
        }
        print '</div>';

        // Display content
        print $content;

        // Link back to forum
        print $discussion->display_link_back_to_forum();

        // Display discussion features (row of buttons)
        print $discussion->display_forumngfeature_discussions();

        print $out->render_discussion_footer($discussion);

        // Set read data [shouldn't this logic be somewhere else as it is not
        // part of display?]
        if (mod_forumng::mark_read_automatically()) {
            $discussion->mark_read();
        }
    }
}
