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
 * Script for generating the printable version of the discussion or selected posts.
 * This uses the post selector infrastructure to handle the situation when posts
 * are being selected.
 * @package forumngfeature
 * @subpackage print
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../forumngfeature_post_selector.php');

class print_post_selector extends forumngfeature_post_selector {
    public function get_button_name() {
        return get_string('print', 'forumngfeature_print');
    }
    public function get_page_name() {
        return get_string('print_pagename', 'forumngfeature_print');
    }
    public function apply($discussion, $all, $selected, $formdata) {
        global $COURSE, $USER, $CFG, $PAGE;
        $d = $discussion->get_id();
        $forum = $discussion->get_forum();
        $PAGE->set_pagelayout('embedded');
        $out = mod_forumng_utils::get_renderer();

        print $out->header();
        $backlink = new moodle_url('/mod/forumng/discuss.php',
                $discussion->get_link_params_array());
        print html_writer::start_tag('div', array('class' => 'forumng-printable-header'));
        print html_writer::tag('div',
                link_arrow_left($discussion->get_subject(), $backlink),
                array('class' => 'forumng-printable-backlink'));
        print html_writer::tag('div',
                get_string('printedat', 'forumngfeature_print', userdate(time())),
                array('class' => 'forumng-printable-date'));
        print html_writer::tag('div', '', array('class' => 'clearer'));
        print "\n";
        print $out->box(get_string('back', 'forumngfeature_print', $backlink->out()),
                'generalbox forumng-donotprint');

        print html_writer::start_tag('div', array('class' => 'forumng-showprintable'));
        if ($all) {
            print $out->render_discussion($discussion, array(
                mod_forumng_post::OPTION_NO_COMMANDS => true,
                mod_forumng_post::OPTION_CHILDREN_EXPANDED => true,
                mod_forumng_post::OPTION_PRINTABLE_VERSION => true));
        } else {
            $allhtml = '';
            $alltext = '';
            $discussion->build_selected_posts_email($selected, $alltext, $allhtml,
                    array(mod_forumng_post::OPTION_PRINTABLE_VERSION));
            print $allhtml;
        }

        print html_writer::end_tag('div');
        $forum->print_js(0, false);
        print $out->footer();
    }
}

forumngfeature_post_selector::go(new print_post_selector());
