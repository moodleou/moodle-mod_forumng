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
 * Export to portfolio script. This uses the post selector infrastructure to
 * handle the situation when posts are being selected.
 * @package forumngfeature
 * @subpackage export
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../forumngfeature_post_selector.php');

class export_post_selector extends forumngfeature_post_selector {
    public function get_button_name() {
        return get_string('export', 'forumngfeature_export');
    }

    public function apply($discussion, $all, $selected, $formdata) {
        global $COURSE, $USER, $CFG;

        if (!$CFG->enableportfolios) {
            return '';
        }

        /*
         * It is necessary to encode the array of selected discussion posts as text in order to
         * pass the data in the button callback options because it gets filtered as PARAM_ALPHA
         */
        if (is_array($selected) && !empty($selected)) {
            $seltext = implode('x', $selected);
            $letters = array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j');
            $posts = '';
            $selarray = str_split($seltext);
            foreach ($selarray as $char) {
                $posts .= ($char == 'x')? $char:$letters[$char];
            }
        } else {
            $posts = '';
        }

        // get the id of the forum
        $forumobj = $discussion->get_forum();
        $forumngid = $forumobj->get_id();

        require_once($CFG->libdir . '/portfoliolib.php');

        // check if the forum is shared and pass the cloneid in the callback options
        $linkparams = $discussion->get_link_params_array();
        if (array_key_exists('clone', $linkparams)) {
            $cloneid = $linkparams['clone'];
        } else {
            $cloneid = 0;
        }

        $button = new portfolio_add_button();
        $button->set_callback_options('forumng_all_portfolio_caller',
                array('forumngid' => $forumngid,
                'cloneid' => $cloneid,
                'posts' => $posts,
                'discussionid' => $discussion->get_id()),
                'mod_forumng');
        $button->set_formats(PORTFOLIO_FORMAT_PLAINHTML);

        // redirect to the export page
        redirect($button->to_html(PORTFOLIO_ADD_FAKE_URL));
    }
}

forumngfeature_post_selector::go(new export_post_selector());
