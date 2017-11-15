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
    public function render_discussion_list_start_table($forum , $th, $nextnum, $sortdata, $unreadpart, $grouppart) {
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
        $table .= "{$th}{$lpnum}'>" .  $sortdata[mod_forumng::SORT_DATE]->before . get_string('lastpost', 'forumng') .
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
                array('href' => 'discuss.php?' . $discussion->get_link_params(mod_forumng::PARAM_HTML)));
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
    public function render_discussion_list_item_discussion($courseid , $discussion, $taglinks) {
        $description = $discussion->get_root_post()->get_formatted_message();
        $location = $discussion->get_location(true);
        $result = html_writer::tag('a', format_string($discussion->get_subject(true), true, $courseid),
            array('href' => $location));
        $result .= html_writer::tag('div', $description, array('class' => 'forumng-description'));
        $result .= $taglinks;
        return $result;
    }
}
