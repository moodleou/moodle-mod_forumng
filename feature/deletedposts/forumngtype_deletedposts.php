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
 * Discussion feature: Deletedposts.
 * @package forumngfeature
 * @subpackage deletedposts
 * @copyright 2012 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/mod/forumng/feature/deletedposts/forumngfeature_deletedposts.php');

/**
 * Displays the view page (usually showing a list of discussions).
 * @param mod_forumng $forum Forum
 * @param int $groupid Group ID
 */
function print_view_page($forum, $groupid) {
    global $PAGE;
    $out = mod_forumng_utils::get_renderer();
    $forumngid = $forum->get_id();
    $baseurl = 'list.php?' . $forum->get_link_params(mod_forumng::PARAM_PLAIN);

    $sortorder = optional_param('sort', '' , PARAM_ALPHA);
    if (!$sortorder) {
        $sortlink = optional_param('sortlink', '' , PARAM_ALPHA);
        if ($sortlink) {
            $sortorder = $sortlink;
        } else {
            $sortorder = 'd';
        }
    }

    $page = optional_param('page', 0, PARAM_INT);
    if (!$page) {
        $page = 1;
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

    $list = $forum->get_discussion_list($groupid, $forum->can_view_hidden(),
            $page, $sort, $sortreverse);

    $normal = $list->get_normal_discussions();

    $fg = new forumngtype_general();
    // Remove discussions from list if the forumtype thinks we can't see them or they are not deleted.
    foreach ($normal as $key => $value) {
        if (! $fg->can_view_discussion($value) || !$value->is_deleted()) {
            unset($normal[$key]);
        }
    }

    $pagecount = get_page_count(count($normal), $page);
    // Intro.
    print $out->render_introduction($forum);

    print display_paging_bar($baseurl, $pagecount, $page);

    if (count($normal) > 0) {
        print $out->render_discussion_list_start(
               $forum, $groupid, $baseurl, $sort, $sortreverse);
        foreach ($normal as $discussion) {
            if ($discussion->is_deleted()) {
                print $out->render_discussion_list_item($discussion, $groupid,
                        $discussion == end($normal));
            }
        }
        print $out->render_discussion_list_end($forum, $groupid);
    } else {
        print '<p class="forumng-nodiscussions">' .
                get_string('nodeleteddiscussions', 'forumngfeature_deletedposts') . '</p>';
    }

    print display_paging_bar($baseurl, $pagecount, $page);

}

/**
 * Displays a Moodle standard paging bar for this result.
 * @param string $baseurl Base URL (may include page= if you like)
 * @return string HTML code for paging bar
 */
function display_paging_bar($baseurl, $pagecount, $page) {
    // Don't do anything if no pages.
    if ($pagecount < 2) {
        return '';
    }

    // Remove page= if included and append &.
    $baseurl = preg_replace('~&page=[0-9]+~', '', $baseurl) . '&';
    $out = mod_forumng_utils::get_renderer();
    $result = $out->render(new paging_bar($pagecount,
            $page-1, 1, htmlspecialchars($baseurl)));
    // This is really damn annoying but discussionlist pages start from 1
    // not 0, so need to change the params.
    $result = preg_replace_callback('~(&amp;page=)([0-9]+)~',
            'munge_page_number', $result);

    return $result;
}

function munge_page_number($matches) {
    // Always add &page= to the paging bar url no matter if it is the first page.
    return $matches[1] . ($matches[2]+1);
}

function get_page_count($count, &$page) {
    global $CFG;
    // Check page index makes sense.
    $pagecount = ceil($count / $CFG->forumng_discussionsperpage);
    if ($pagecount < 1) {
        $pagecount = 1;
    }
    if (($page > $pagecount || $page < 1) ) {
        if ($ignoreinvalidpage) {
            $page = 1;
        } else {
            throw new coding_exception("Invalid page \$page, expecting 1.. ".$pagecount);
        }
    }

    return $pagecount;

}
