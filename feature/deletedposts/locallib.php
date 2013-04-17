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
 * Local library file for forumng.  These are non-standard functions that are used
 * only by forumng.
 *
 * @package forumngfeature
 * @subpackage deletedposts
 * @copyright 2012 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/** Make sure this isn't being directly accessed */
defined('MOODLE_INTERNAL') || die();

/**
 *
 * @param string $selected is the selected tab
 * @param  array $params url parameters to be encoded
 */
function forumng_feature_deleted_print_tabs($selected, $params) {
    global $CFG;

    $tabrow = array();

    $listurl = new moodle_url('/mod/forumng/feature/deletedposts/list.php', $params);
    $deletedpostslisturl = new moodle_url('/mod/forumng/feature/deletedposts/deletedpostslist.php', $params);

    $tabrow[] = new tabobject('list',
            $listurl->out() , get_string('deleteddiscussions', 'forumngfeature_deletedposts'));

    $tabrow[] = new tabobject('deletedpostslist',
            $deletedpostslisturl->out(), get_string('deletedposts', 'forumngfeature_deletedposts'));

    $tabs = array();
    $tabs[] = $tabrow;
    print_tabs($tabs, $selected, array(''), array(''));

    print '<div id="forumng_belowtabs">';
}

function get_deleted_posts_for_forumng($forumngid, $groupid = 0, $deleteuserid = 0, $creatorid = 0) {
    global $DB, $CFG, $USER;

    $where = ' fp.deleted != 0';
    $whereparams = array();

    if (! empty($groupid)) {
        $where .=  ' AND fd.groupid = ?';
        $whereparams[] = $groupid;
    }

    if (! empty($deleteuserid)) {
        $where .=  ' AND fp.deleteuserid = ?';
        $whereparams[] = $deleteuserid;
    }

    if (! empty($creatorid)) {
        $where .=  ' AND fp.userid = ?';
        $whereparams[] = $creatorid;
    }

    $where .= ' AND fd.deleted = ?';
    $whereparams[] = 0;

    $where .= ' AND fd.forumngid = ?';
    $whereparams[] = $forumngid;

    $orderby = 'fd.id, fp.id';

    // See line 827 of mod_forumng_post.php.
    $result = mod_forumng_post::query_posts($where, $whereparams, $orderby, false, false, false, 0, true);
    return $result;
}

function get_forumng_deleted_discussion_posts($forum, $groupid = 0, $deleteuserid = 0, $creatorid = 0) {
    $forumngid = $forum->get_id();
    // Get deleted posts for this forum by discussion.
    $rows = get_deleted_posts_for_forumng($forumngid, $groupid, $deleteuserid, $creatorid);
    $prevdiscussionid = 0;
    $discussioncount = 0;
    $deletedpostscount = 0;
    $result = array();
    foreach ($rows as $rec) {
        $discussionfields = mod_forumng_utils::extract_subobject($rec, 'fd_');
        if ($discussionfields->id != $prevdiscussionid) {
            $discussion = new mod_forumng_discussion($forum, $discussionfields, false, -1);
            $prevdiscussionid = $discussionfields->id;
        }
        $result[$rec->id] = new mod_forumng_post($discussion, $rec);
    }

    return $result;
}

/**
 * Filters an arreay of forumng posts by deleted and/or created the posts.
 * @param array $posts array of forumng posts to filtered
 * @param int $deleteuser id of user who deleted the posts
 * @param int $creatorid id of user who created the posts
 * @return array of posts filtered by who deleted and created the posts
 */
function get_filteredposts($posts, $deleteuser = 0, $creatorid = 0) {
    $filteredposts = array();
    $filtered = false;

    foreach ($posts as $postid => $post) {
        if ($deleteuser > 0) {
            $deleteduser = $post->get_delete_user();
            if ($deleteduser->id == $deleteuser ) {
                // Check to see whether creator filter set or not.
                if ($creatorid > 0) {
                    $creatoruser = $post->get_user();
                    if ($creatoruser->id == $creatorid) {
                        // Add to filteredpost array.
                        $filteredposts[] = $post;
                    }
                } else {
                    // Creator not set, add to filteredpost array.
                    $filteredposts[] = $post;
                }
            }
            $filtered = true;
        } else {
            // Check to see whether creator filter set or not.
            if ($creatorid > 0) {
                $creatoruser = $post->get_user();
                if ($creatoruser->id == $creatorid) {
                    // Add to filteredpost array.
                    $filteredposts[] = $post;
                }
                $filtered = true;
            }
        }
    }

    if (! $filtered) {
        $filteredposts = $posts;
    }

    return $filteredposts;
}
