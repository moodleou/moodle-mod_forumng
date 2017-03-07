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
 * Show discussion page.
 * @package mod
 * @subpackage forumng
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');
require_once('mod_forumng.php');

// Require discussion parameter here. Other parameters may be required in forum
// type.
$discussionid = required_param('d', PARAM_INT);
$pageparams = array('d' => $discussionid);
$cloneid = optional_param('clone', 0, PARAM_INT);
if ($cloneid) {
    $pageparams['clone' ]= $cloneid;
}

// Construct discussion variable (will check id is valid)
// Retrieve new copy of discussion from database, but store it in cache
// for further use.
$discussion = mod_forumng_discussion::get_from_id($discussionid, $cloneid,
        0, false, true);
$forum = $discussion->get_forum();
$course = $forum->get_course();

$cm = $forum->get_course_module();
$context = $forum->get_context();

$draftid = optional_param('draft', 0, PARAM_INT);
if ($draftid) {
    $pageparams['draft'] = $draftid;
    $draft = mod_forumng_draft::get_from_id($draftid);
    if (!$draft->is_reply() ||
        $draft->get_discussion_id() != $discussionid) {
        print_error('draft_mismatch', 'forumng', $forum->get_url(mod_forumng::PARAM_HTML));
    }
    $root = $discussion->get_root_post();
    $inreplyto = $root->find_child($draft->get_parent_post_id(), false);
    if (!$inreplyto || !$inreplyto->can_reply($whynot) || !$discussion->can_view()) {
        if (!$whynot) {
            $whynot = 'reply_missing';
        }
        print_error('draft_cannotreply', 'forumng', $forum->get_url(mod_forumng::PARAM_HTML),
            get_string($whynot, 'forumng'));
    }
    $inreplyto->force_expand();
}

// Check that discussion can be viewed [Handles all other permissions]
$discussion->require_view();

// Atom header meta tag
$feedtype = $forum->get_effective_feed_option();
if ($feedtype == mod_forumng::FEEDTYPE_ALL_POSTS) {
    $atomurl = $discussion->get_feed_url(mod_forumng::FEEDFORMAT_ATOM);
    $meta = '<link rel="alternate" type="application/atom+xml" ' .
      'title="Atom feed" href="' . htmlspecialchars($atomurl) . '" />';
} else {
    $meta = '';
}

// Display header
$url = new moodle_url('/mod/forumng/discuss.php', $pageparams);
$pagename = shorten_text(s($discussion->get_subject()));
if (!defined('BEHAT_SITE_RUNNING')) {
    $PAGE->set_pagelayout('base');
}
$out = $forum->init_page($url, $pagename);
print $out->header();

// Include forum JS
$forum->print_js($cm->id, true);

// If replying with a draft, we stick some JSON stuff in the page so that the draft
// can be retrieved.
if ($draftid) {
    print $draft->prepare_edit_js($forum);
}

// Print main div
print '<div id="forumng-main" class="forumng-discuss forumng-nojs' .
    ($discussion->is_deleted() ? ' forumng-deleted-discussion' : '' ) . '">';
print $out->render_switch_link($forum);
print $OUTPUT->skip_link_target();

// Get forum type to display main part of page
$type = $forum->get_type();
$type->print_discussion_page($discussion);

print '</div>';

if ($bad = $PAGE->devicetypeinuse == 'legacy') {
    print '<div class="forumng-bad-browser">'.
        get_string('badbrowser', 'forumng', $bad) . '</div>';
}

// Log request
$discussion->log('view discussion');

// Display footer
print $out->footer();
