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
 * A list of discussions, suitable for displaying on the forum index page. The
 * discussions may be divided into two categories: sticky and normal discussions.
 * Each discussion object contains enough information to display its entry on
 * the forum index, but does not (yet) contain actual messages.
 * @see mod_forumng_discussion
 * @package mod
 * @subpackage forumng
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_forumng_discussion_list {
    private $page, $pagecount, $discussioncount;
    private $normaldiscussions, $stickydiscussions;

    /**
     * Constructs list (internal use only).
     * @param int $page Page number (1-based)
     * @param int $pagecount Count of pages
     * @param int $discussioncount Count of all discussions
     */
    public function __construct($page, $pagecount, $discussioncount) {
        $this->page = $page;
        $this->pagecount = $pagecount;
        $this->discussioncount = $discussioncount;
        $this->normaldiscussions = array();
        $this->stickydiscussions = array();
    }

    /**
     * Adds a discussion to the list (internal use only).
     * @param mod_forumng_discussion $discussion
     */
    public function add_discussion($discussion) {
        if ($discussion->is_sticky() && !$discussion->is_deleted()) {
            $this->stickydiscussions[$discussion->get_id()]=$discussion;
        } else {
            $this->normaldiscussions[$discussion->get_id()]=$discussion;
        }
    }

    /**
     * @return array Array of all sticky discussions (mod_forumng_discussion objects)
     *   in the order they should be displayed; empty array if none
     */
    public function get_sticky_discussions() {
        return $this->stickydiscussions;
    }

    /**
     * @return array Array of all normal discussions (mod_forumng_discussion objects)
     *   in the order they should be displayed; empty array if none
     */
    public function get_normal_discussions() {
        return $this->normaldiscussions;
    }

    /**
     * @return int Page index - 1 is first page
     */
    public function get_page_index() {
        return $this->page;
    }

    /**
     * @return int Total number of available pages - e.g. if this is 6,
     *   then pages 1..6 are available.
     */
    public function get_total_pages() {
        return $this->pagecount;
    }

    /**
     * @return int Total number of discussions (not just the ones included
     *   in this list)
     */
    public function get_total_discussions() {
        return $this->discussioncount;
    }

    /**
     * @return bool True if there are no discussions in this list
     *   (get_sticky_discussions and get_normal_discussions both return
     *   empty arrrays)
     */
    public function is_empty() {
        return count($this->stickydiscussions)+count($this->normaldiscussions)==0;
    }

    /**
     * Displays a Moodle standard paging bar for this result.
     * @param string $baseurl Base URL (may include page= if you like)
     * @return string HTML code for paging bar
     */
    public function display_paging_bar($baseurl) {
        // Don't do anything if no pages
        if ($this->pagecount < 2) {
            return '';
        }

        // Remove page= if included and append &
        $baseurl = preg_replace('~&page=[0-9]+~', '', $baseurl) . '&';
        $out = mod_forumng_utils::get_renderer();
        $result = $out->render(new paging_bar($this->pagecount,
                $this->page-1, 1, htmlspecialchars($baseurl)));
        // This is really damn annoying but discussionlist pages start from 1
        // not 0, so need to change the params
        $result = preg_replace_callback('~(&amp;page=)([0-9]+)~',
            'mod_forumng_discussion_list::munge_page_number', $result);

        return $result;
    }

    private static function munge_page_number($matches) {
        // Always add &page= to the paging bar url no matter if it is the first page.
        return $matches[1] . ($matches[2]+1);
    }
}
