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
 * Serve Atom/RSS feed.
 *
 * @package mod_forumng
 * @copyright 2012 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('mod_forumng.php');

require_once($CFG->libdir . '/rsslib.php');
require_once('atomlib.php');

// Parameters identify desired forum and group
$d = optional_param('d', 0, PARAM_INT);
if (!$d) {
    $cmid = required_param('id', PARAM_INT);
    $groupid = optional_param('group', 'unspecified', PARAM_INT);
}
$cloneid = optional_param('clone', 0, PARAM_INT);

// User identification
$userid = required_param('user', PARAM_INT);
$key = required_param('key', PARAM_ALPHANUM);

// Get user entry and set hack flag necessary for it to work with the OU's SSO system
$user = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);
$user->samspretendlogin = true;

// Get Moodle to log them in
$user = complete_user_login($user);
if (!$user) {
    throw new moodle_exception('', 'forumng');
}

// Feed format
$format = required_param('format', PARAM_ALPHA);
$rss = $format == 'rss';

// Load forum
if ($d) {
    $discussion = mod_forumng_discussion::get_from_id($d, $cloneid);
    $forum = $discussion->get_forum();
    $groupid = $discussion->get_group_id();
    $url = $discussion->get_url(mod_forumng::PARAM_PLAIN);
} else {
    $forum = mod_forumng::get_from_cmid($cmid, $cloneid);
    $url = $forum->get_url(mod_forumng::PARAM_PLAIN);
    if ($groupid == 'unspecified') {
        $groupid = $forum->get_group_mode() == SEPARATEGROUPS
            ? mod_forumng::ALL_GROUPS : mod_forumng::NO_GROUPS;
    } else {
        $url .= '&group=' . $groupid;
    }
}

// Check it allows feeds
$feedtype = $forum->get_effective_feed_option();
switch ($feedtype) {
    case mod_forumng::FEEDTYPE_DISCUSSIONS:
        if (!$d) {
            break;
        }
        // Fall through
    case mod_forumng::FEEDTYPE_NONE:
        print_error('feed_notavailable', 'forumng');
}

// Check that the key is valid
$correctkey = $forum->get_feed_key($groupid, $userid);
if ($correctkey != $key) {
    print_error('feed_nopermission', 'forumng');
}

// Get most recent posts or discussions
if ($feedtype == mod_forumng::FEEDTYPE_DISCUSSIONS) {
    $discussions = $forum->get_feed_discussions($groupid, $userid);
    $latest = count($discussions)
        ? reset($discussions)->get_time_modified() : time();
} else {
    $posts = $d ? $discussion->get_feed_posts($userid)
        : $forum->get_feed_posts($groupid, $userid);
    $latest = count($posts)
        ? reset($posts)->get_created() : time();
}

$since = isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])
    ? $_SERVER['HTTP_IF_MODIFIED_SINCE'] : false;
if ($since && strtotime($since) >= $latest) {
    header('HTTP/1.0 304 Not Modified');
    exit;
}
header('Last-Modified: ' .gmdate('D, d M Y H:i:s', $latest) . ' GMT');

// This variable is used by the MathML filter so that we can make it do
// different things with RSS feed output (basically it turns off everything
// clever and uses an image).
global $ou_feed_output;
$ou_feed_output = true;

// Check they still have permission to the forum.
// Note that making these checks is a bit expensive so we might have
// a performance concern, will deal with that later if needed. This is done
// after the last-modified check so we can skip it if possible.
if ($d) {
    $discussion->require_view($userid);
} else {
    $forum->require_view($groupid, $userid);
}

// Unless the feed is of discussion titles only, you can't view it except
// if you can view the content of discussions
if ($feedtype != mod_forumng::FEEDTYPE_DISCUSSIONS &&
    !$forum->can_view_discussions($userid)) {
    print_error('feed_nopermission', 'forumng');
}

// Place data into standard format for atomlib/rsslib
if ($d) {
    $feedname = format_string($forum->get_name()) . ': ' .
        format_string($discussion->get_subject());
    $feedsummary = '';
} else {
    $feedname = format_string($forum->get_name());
    $feedsummary = $forum->get_introduction();
}

$feeddata = array();
if (isset($discussions)) {
    foreach ($discussions as $discussion) {
        $data = new stdClass;

        $data->title = format_string($discussion->get_subject());
        $data->description = '';
        $data->author = $forum->display_user_name(
            $discussion->get_poster());
        $data->link = $discussion->get_url();
        $data->pubdate = $discussion->get_time_modified();

        $feeddata[] = $data;
    }
} else {
    foreach ($posts as $post) {
        $data = new stdClass;

        // Title is post subject, if any...
        $data->title = format_string($post->get_subject());
        if ($data->title === null) {
            $data->title = '';
        }
        // ...plus discussion subject (but not for discussion feed)
        if (!$d) {
            $data->title =
                format_string($post->get_discussion()->get_subject()) .
                ': ' . $data->title;
        }

        // Remaining details straightforward
        $data->description = $post->get_formatted_message();
        if ($post->get_asmoderator() == mod_forumng::ASMODERATOR_NO ||
                $forum->can_post_anonymously()) {
            $data->author = $forum->display_user_name($post->get_user());
        } else {
            $data->author = get_string('moderator', 'forumng');
        }
        $data->link = $post->get_url();
        $data->pubdate = $post->get_modified();

        $feeddata[] = $data;
    }
}

// Now output all posts
if ($rss) {
    header('Content-type: application/rss+xml');
    echo rss_standard_header($feedname, $url, $feedsummary);
    echo rss_add_items($feeddata);
    echo rss_standard_footer();
} else {
    header('Content-type: application/atom+xml');
    $updated = count($feeddata)==0 ? time() : reset($feeddata)->pubdate;

    if (trim($feedsummary) === '') {
        $feedsummary = '';
    } else {
        // A valid atom feed requires plain text in the subtitle tag, or html if
        // the subtitle tag contains the attribute type="html". This html option
        // is not available in the atomlib, so lets convert html to text here.
        $feedsummary = strip_tags($feedsummary);
    }

    echo atom_standard_header($FULLME , $FULLME, $updated, $feedname, $feedsummary);
    echo atom_add_items($feeddata);
    echo atom_standard_footer();
}
