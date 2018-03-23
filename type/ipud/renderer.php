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
require_once($CFG->dirroot . '/mod/forumng/mod_forumng.php');
require_once($CFG->dirroot . '/mod/forumng/renderer.php');

/**
 * Forum NG Ipud renderer class
 *
 * Forum type: In Page discussion type
 * @package forumngtype_ipud
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class forumngtype_ipud_renderer extends mod_forumng_renderer {

    /**
     * Render discussion list start table heading with location forum,remove start by column.
     *
     * @param mod_forumng $forum
     * @param string $th
     * @param int $nextnum
     * @param array $sortdata
     * @param string $unreadpart
     * @param string $grouppart
     * @return string
     */
    public function render_discussion_list_start_table($forum, $th, $nextnum, $sortdata, $unreadpart, $grouppart) {
        $lpnum = $nextnum + 1;
        $npnum = $nextnum + 2;
        $sbnum = $nextnum + 3;
        $table = html_writer::start_tag('table', array('class' => 'generaltable forumng-discussionlist'));
        $table .= html_writer::start_tag('thead');
        $table .= html_writer::start_tag('tr');
        // Subject column th.
        $table .= "{$th}0'>" . $sortdata[mod_forumng::SORT_SUBJECT]->before . get_string('discussion', 'forumng') .
            $sortdata[mod_forumng::SORT_SUBJECT]->after;
        $table .= html_writer::end_tag('th');
        // Last post column.
        $table .= $unreadpart . $grouppart;
        $table .= "{$th}{$lpnum}'>" . $sortdata[mod_forumng::SORT_DATE]->before . get_string('lastpost', 'forumng') .
            $sortdata[mod_forumng::SORT_DATE]->after;
        // Posts column.
        $table .= html_writer::end_tag('th');
        $table .= "{$th}{$npnum} forumng-postscol'>" . $sortdata[mod_forumng::SORT_POSTS]->before .
            get_string('posts', 'forumng') . $sortdata[mod_forumng::SORT_POSTS]->after;
        $table .= html_writer::end_tag('th');
        // Location column.
        // Only show for user has permission see location column.
        if ($forum->get_type()->can_mark_read($forum)) {
            $table .= "{$th}{$sbnum} ipud-lastcol'>" . get_string('location', 'forumng');
            $table .= html_writer::end_tag('th');
        }
        $table .= html_writer::end_tag('tr');
        $table .= html_writer::end_tag('thead');
        $table .= html_writer::start_tag('tbody');
        return $table;
    }

    /**
     * Renders discussion locations detail in the list item.
     *
     * @param mod_forumng_discussion $discussion object
     * @param int $courseid course id
     * @param int $num Cur cell number
     * @return string td html tag containing the locations detail.
     */
    public function render_discussion_list_item_author($discussion, $courseid, $num) {
        $html = '';
        $forum = $discussion->get_forum();
        if ($forum->get_type()->can_mark_read($forum)) {
            $html = html_writer::start_tag('td', array('class' => 'forumng-location cell c' . $num . ' ipud-lastcol'));
            $html .= html_writer::tag('a', get_string('linktoforumview', 'forumng'),
                array('href' => 'discuss.php?' . $discussion->get_link_params(mod_forumng::PARAM_PLAIN)));
            $html .= html_writer::end_tag('td');
        }

        return $html;
    }

    /**
     * Render discussion column to include description from fisrt post.
     *
     * @param int $courseid
     * @param mod_forumng_discussion $discussion
     * @param array $taglinks
     * @return string
     */
    public function render_discussion_list_item_discussion($courseid, $discussion, $taglinks) {
        $description = $discussion->get_root_post()->get_formatted_message();
        if (!is_null($discussion->get_group_id())) {
            $location = $discussion->get_location(true) . '&groupid=' . $discussion->get_group_id();
        } else {
            $location = $discussion->get_location(true);
        }
        $result = html_writer::tag('a', format_string($discussion->get_subject(true), true, $courseid),
            array('href' => $location));
        $result .= html_writer::tag('div', $description, array('class' => 'forumng-description'));
        $result .= $taglinks;
        return $result;
    }

    /**
     * Show subscription, marking and rss in header + add unread skip + title + location.
     * Show first post and location discussion title
     * Ipud renderer need to use mod_forumng_renderer,so that the theme can override this function.
     *
     * @see mod_forumng_renderer::render_discussion_header()
     */
    public function render_discussion_header($discussion) {
        $out = mod_forumng_utils::get_renderer();
        $html = $out->render_discussion_header($discussion);
        $html .= $this->render_discussion_after_header($discussion);
        return $html;
    }

    /**
     * Displays a discussion (main part of discussion page) with given options.
     * @param mod_forumng_discussion $discussion
     * @param object $options
     * @return string HTML content of discussion
     */
    public function render_discussion($discussion, $options) {
        $options[mod_forumng_post::OPTION_DONT_DISPLAY_ROOTPOST] = true;

        return parent::render_discussion($discussion, $options);
    }

    /**
     * Render content below content discussion. For ipud,we render reply form for rootpost.
     *
     * @param mod_forumng_discussion $discussion
     */
    public function render_content_below_content_discussion($discussion) {
        global $CFG, $PAGE;
        require_once($CFG->dirroot . '/mod/forumng/editpost_form.php');
        $rootpost = $discussion->get_root_post();
        $params = array('replyto' => $discussion->get_root_post()->get_id());
        $params['d'] = $discussion->get_id();
        $params['replyto'] = $rootpost->get_id();
        $url = 'editpost.php?d=' . $discussion->get_id() . '&replyto=' . $rootpost->get_id();
        $replyoption = $discussion->get_forum()->get_type()->get_reply_options(false, true);
        $mform = new mod_forumng_editpost_form($url,
            array('params' => $params, 'isdiscussion' => false,
                  'forum' => $discussion->get_forum(), 'edit' => false, 'ispost' => true, 'islock' => false,
                  'post' => isset($rootpost) ? $rootpost : null, 'isroot' => false, 'ipud' => true,
                  'iframe' => false, 'replyoption' => $replyoption,
                  'timelimit' => !$rootpost->can_ignore_edit_time_limit() ? $rootpost->get_edit_time_limit() : 0,
                  'draft' => false, 'tags' => false, 'forumtags' => false));

        // Include form javascript to disabled post reply button when content is empty.
        $simple = get_user_preferences('forumng_simplemode', '');
        if ($PAGE->devicetypeinuse == 'legacy' || $simple) {
            return;
        }
        $module = array(
            'name'      => 'mod_forumng_form',
            'fullpath'  => '/mod/forumng/form.js',
            'requires'  => array('base', 'node'),
            'strings'   => array(array('edit_timeout', 'forumng'))
        );
        $PAGE->requires->js_init_call('M.mod_forumng_form.init',
            array($discussion->get_root_post()->get_id()), false, $module);

        return html_writer::tag('div', $mform->get_html(), array('class' => 'forumng-bottom-reply'));
    }

    /**
     * Render html after header.
     *
     * @param mod_forumng_discussion $discussion
     * @return string
     */
    public function render_discussion_after_header($discussion) {
        // Add first post content to discussion heading.
        $out = html_writer::start_div('clearfix') . html_writer::end_div();
        $out .= html_writer::start_tag('div', array('class' => 'forumng-ipud-description'));
        $out .= $discussion->get_root_post()->get_formatted_message();
        $out .= html_writer::end_tag('div');
        if (!is_null($discussion->get_group_id())) {
            $location = $discussion->get_location(true) . '&groupid=' . $discussion->get_group_id();
        } else {
            $location = $discussion->get_location(true);
        }
        if ($location) {
            $out .= html_writer::start_tag('div', array('class' => 'forumng-ipud-olink'));
            $out .= html_writer::tag('a', get_string('linktodiscussion', 'forumngtype_ipud'), array('href' => $location));
            $out .= html_writer::end_tag('div');
        }
        return $out;
    }

    /**
     * Override the display order and layout of the command buttons
     * Remove jumpto and add total reply,move edit to first and reply to last.
     * Ipud renderer need to use mod_forumng_renderer,so that the theme can override this function.
     *
     * @param array $commandsarray Array of HTML strings
     * @return string HTML code for the commands buttons
     */
    public function render_commands($commandsarray) {
        $out = mod_forumng_utils::get_renderer();
        $html = $out->render_commands($commandsarray);
        return $html;
    }

    /**
     * Change the appearance of expand link by using a image instead of text.
     * Ipud renderer need to use mod_forumng_renderer,so that the theme can override this function.
     *
     * @param string $linkprefix prefix of the expand link url
     * @param mod_forumng_discussion $discussion object
     * @param mod_forumng_post $post object
     * @return string HTML code for the expand link
     */
    public function render_expand_link($linkprefix, $discussion, $post) {
        $out = mod_forumng_utils::get_renderer();
        $html = $out->render_expand_link($linkprefix, $discussion, $post);
        return $html;
    }

    /**
     * Renders last post details for a discussion.
     *
     * @param mod_forumng_discussion $discussion object
     * @param int $lastpostanon boolen if last post was anon
     * @param int $num value for specifying the position the last post details are rendered to in the item list
     * @returns string the td html tag containing the last post details
     */
    public function render_discussion_list_item_lastpost($discussion, $lastposteranon, $num) {
        $result = parent::render_discussion_list_item_lastpost($discussion, $lastposteranon, $num);
        if ($discussion->get_num_posts() == 0) {
            $result = html_writer::start_tag('td', array('class' => 'cell c' . $num . ' forumng-lastpost'));
            $lastpostcell = get_string('nopostsyet', 'forumngtype_ipud');
            $result .= $lastpostcell . html_writer::end_tag('td');
        }
        return $result;
    }
}
