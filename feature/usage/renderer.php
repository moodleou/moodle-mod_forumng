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
 * ForumNG usage sub-plugin renderer class
 * @see core_renderer Core renderer (you can call methods in this)
 * @package    mod
 * @subpackage forumngfeature_usage
 * @copyright  2013 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class forumngfeature_usage_renderer extends plugin_renderer_base {
    /**
     * Create a usage list item, detailing a total, user image and some info
     * @param mod_forumng $forum
     * @param int $total
     * @param object $user
     * @param string $info
     * @return string
     */
    public function render_usage_list_item($forum, $total, $user, $info) {
        global $OUTPUT;
        if (is_null($user)) {
            $userimage = html_writer::empty_tag('img',
                    array('src' => $this->image_url('u/f2'), 'alt' => ''));
        } else {
            $userimage = $OUTPUT->user_picture($user);
            if ($forum->is_shared()) {
                // Strip course id if shared forum.
                $userimage = str_replace('&amp;course=' . $forum->get_course()->id, '', $userimage);
            }
        }
        $toreturn = html_writer::div($total, 'forumng_usage_list_tot') .
            html_writer::div($userimage, 'forumng_usage_list_pic') .
            html_writer::div($info, 'forumng_usage_list_info');
        return $toreturn;
    }

    /**
     * Output array of list_items
     * @param array $content
     * @param string $stringname - name of string to use for title and no content
     * @param bool $heading - include a heading with help?
     * @return string
     */
    public function render_usage_list($content, $stringname, $heading = true) {
        $toreturn = '';
        if ($heading) {
            $toreturn .= $this->render_usage_list_heading($stringname);
        }
        if (!empty($content)) {
            $toreturn .= html_writer::alist($content, array('class' => 'forumng_usage_list'), 'ol');
        } else {
            $toreturn .= html_writer::tag('p', get_string($stringname . '_none', 'forumngfeature_usage'));
        }
        return $toreturn;
    }

    public function render_usage_list_heading($stringname) {
        global $OUTPUT;
        $help = $this->help_icon($stringname, 'forumngfeature_usage');
        $toreturn = $OUTPUT->heading(get_string($stringname, 'forumngfeature_usage') . '' . $help,
                4, 'forumng_usage_listhead');
        return $toreturn;
    }

    /**
     * Create output for discussion information (Title, date, user)
     * Returns content and user object (discussion poster, null if anon)
     * @param object $forum
     * @param object $discussion
     * @return array content html, discussion poster user object
     */
    public function render_usage_discussion_info($forum, $discussion) {
        $user = $discussion->get_poster();
        $discusslink = html_writer::link($discussion->get_moodle_url(),
                format_string($discussion->get_subject()));
        $content = html_writer::div($discusslink);
        $content .= html_writer::div(mod_forumng_utils::display_date($discussion->get_time_modified()));
        $content .= html_writer::start_div('fng_userlink');
        if ($discussion->get_poster_anon() == mod_forumng::ASMODERATOR_ANON &&
                !$forum->can_post_anonymously()) {
            // Hide user details if anon post and user does not have permission to see.
            $user = null;
        } else {
            $content .= $forum->display_user_link($user) . ' ';
        }
        if ($discussion->get_poster_anon() != mod_forumng::ASMODERATOR_NO) {
            $content .= get_string('moderator', 'mod_forumng');
        }
        $content .= html_writer::end_div();
        return array($content, $user);
    }

    /**
     * Create output for post information (Title, date, user)
     * Returns content and user object (discussion poster, null if anon)
     * @param object $forum
     * @param object $discussion
     * @param object $post
     * @return array content html, poster user object
     */
    public function render_usage_post_info($forum, $discussion, mod_forumng_post $post) {
        $user = $post->get_user();
        $link = html_writer::link($post->get_url(),
                format_string($post->get_effective_subject()));
        $content = html_writer::div($link);
        $content .= html_writer::div(mod_forumng_utils::display_date($post->get_created()));
        $content .= html_writer::start_div('fng_userlink');
        if ($post->get_asmoderator() == mod_forumng::ASMODERATOR_ANON &&
                !$forum->can_post_anonymously()) {
            // Hide user details if anon post and user does not have permission to see.
            $user = null;
        } else {
            $content .= $forum->display_user_link($user) . ' ';
        }
        if ($post->get_asmoderator() != mod_forumng::ASMODERATOR_NO) {
            $content .= get_string('moderator', 'mod_forumng');
        }
        $content .= html_writer::end_div();
        return array($content, $user);
    }

    /**
     * Renders a dynamic loading (ajax) div container - with alternative for non-js.
     * Calls yui/usageloader.
     * Name of loading area must match a forumngfeature_usage_show... function in locallib
     * @param string $name Unique name used to identify area and load content using related function
     * @param object $forum Used for non-ajax call to function to save query
     * @param array $params key value parameters to send, must include cmid, cloneid and groupid
     */
    public function render_usage_dynamicarea($name, $forum, $params) {
        global $PAGE;
        $content = '';
        if (optional_param('showdynamic', 0, PARAM_BOOL)) {
            // Non ajax - load and display now.
            $funcname = 'forumngfeature_usage_show_' . $name;
            if (function_exists($funcname)) {
                $content .= $funcname($params, $forum);
            }
        } else {
            $params['sesskey'] = sesskey();
            $params['name'] = $name;
            $params = http_build_query($params, '', '&');
            // Noscript text + loader yui etc.
            $url = $PAGE->url;
            $url->param('showdynamic', true);
            $noscript = html_writer::link($url, get_string('noscript', 'forumngfeature_usage'));
            $content = html_writer::start_div('forumngusage_loader forumngusageshow' . $name);
            $content .= html_writer::div($noscript, 'forumngusage_loader_noscript');
            $PAGE->requires->yui_module('moodle-forumngfeature_usage-usageloader',
                    'M.mod_forumng.forumngfeature_usage_loader.init', array($name, $params));
            $content .= html_writer::end_div();
        }
        return $content;
    }

    /**
     * Display the usage ratings
     * @param array $ratinglist list of forum post details to be displayed
     * @param object $forum
     * @param string $gradingstr is the string name to be got from the language file
     * @param string $gradingtype is the numeric value of the forum or chosen grading type
     * @return string
     */
    public function render_usage_ratings($ratinglist, $forum, $gradingstr = '', $gradingtype = 0) {

        // Print out ratings usage.
        $usageoutput = html_writer::start_div('forumng_usage_ratings');
        $grading = $forum->get_grading();
        $usageoutput .= $this->render_usage_list_heading($gradingstr);
        if (($grading == mod_forumng::GRADING_NONE) || ($grading == mod_forumng::GRADING_MANUAL)) {
            $usageoutput .= $this->render_ratings_filter($forum, $gradingtype);
        }
        $usageoutput .= $this->render_usage_list($ratinglist, $gradingstr, false);
        $usageoutput .= html_writer::end_div();
        return $usageoutput;
    }

    /**
     * Display rating filter as a link or dropdown
     * @param mod_forumng $forum
     * @papam int $selectedid for choosing default value from dropdown
     * @return string for printing out
     */
    public function render_ratings_filter($forum, $selectedid = 0) {

        // Display dropdown.
        $options = array();
        $options[mod_forumng::GRADING_AVERAGE] = get_string('grading_average', 'forumng');
        $options[mod_forumng::GRADING_COUNT] = get_string('grading_count', 'forumng');
        $options[mod_forumng::GRADING_SUM] = get_string('grading_sum', 'forumng');

        $usageurl = new moodle_url('/mod/forumng/feature/usage/usage.php?',
                $forum->get_link_params_array(mod_forumng::PARAM_PLAIN));
        $select = new single_select($usageurl, 'ratings', $options, $selectedid, false);
        $select->label = get_string('forumngratingsfilter', 'forumngfeature_usage');
        $select->set_help_icon('forumngratingsfilter', 'forumngfeature_usage');
        $output = $this->render($select);
        $out = '<div class="forumng_ratings_filter">' . $output . '</div>';

        return $out;
    }

}
