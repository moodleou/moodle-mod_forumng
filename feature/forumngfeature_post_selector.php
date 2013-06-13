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

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->dirroot . '/mod/forumng/mod_forumng.php');
require_once($CFG->dirroot . '/mod/forumng/mod_forumng_cron.php');
/**
 * A class that deals with the various HTTP requests involved in selecting
 * specific posts (or a whole discussion) for processing, either in JavaScript
 * or non-JavaScript modes. Goes with matching JavaScript code in forumng.js.
 *
 * Example usage, in a file such as forward.php:
 *
 * // start of file
 * require_once('../post_selector.php');
 *
 * class forward_post_selector extends post_selector() {
 *   // class implements the base class methods below
 * }
 *
 * post_selector::go(new forward_post_selector());
 * // end of file
 * @package mod
 * @subpackage forumng
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class forumngfeature_post_selector {
    /**
     * For overriding in subclass. If this feature requires a particular
     * capability, require it here. The system will already have checked view
     * permission for the discussion.
     * @param object $context Moodle context object for forum
     * @param mod_forumng_discussion $discussion Discussion object
     */
    public function require_capability($context, $discussion) {
        // Default makes no extra checks
    }

    /**
     * @return string Name of page for display in title etc (default is the
     *   same as button name)
     */
    public function get_page_name() {
        return $this->get_button_name();
    }

    /**
     * @return string Text of button used to activate this feature
     */
    public abstract function get_button_name();

    /**
     * For overriding in subclass. If there is a form, return the form object.
     * If there is no form, return null.
     *
     * NOTE: The form MUST contain a hidden field called 'postselectform' which
     * MUST always be set to 1.
     *
     * @param mod_forumng_discussion $discussion Discussion object
     * @param bool $all True if whole discussion is selected
     * @param array $selected Array of selected post IDs (if not $all)
     * @return object Form object or null if none
     */
    public function get_form($discussion, $all, $selected = array()) {
        return null;
    }

    /**
     * For overriding in subclass. Called when posts have been selected. If
     * there is a form then this is called only once the form has also been
     * submitted. If there is no form then this is called as soon as posts have
     * been selected (immediately after get_form). This function must been defined
     * in your own class on what you want to do after you have selected the posts/discussion
     * @param mod_forumng_discussion $discussion
     * @param bool $all
     * @param array $selected Array of post IDs (if not $all)
     * @param object $formdata Data from form (if any; null if no form)
     */
    public abstract function apply($discussion, $all, $selected, $formdata);

    /**
     * When displaying the form, extra content (such as an example of the
     * selected messages) can be displayed after it by overriding this function.
     * Default returns blank.
     * @param mod_forumng_discussion $discussion
     * @param bool $all
     * @param array $selected Array of post IDs (if not $all)
     * @param object $formdata Data from form (if any; null if no form)
     * @return string HTML content to display after form
     */
    public function get_content_after_form($discussion, $all, $selected, $formdata) {
        return '';
    }

    /**
     * This function handles all aspects of page processing and then calls
     * methods in $selector at the appropriate moments.
     * @param post_selector $selector Object that extends this base class
     * @param string $rawurl Raw 'name' part of url e.g. '/mod/forumng/feature/frog/frog.php'
     */
    public static function go($selector) {
        global $PAGE, $FULLME;
        $d = required_param('d', PARAM_INT);
        $cloneid = optional_param('clone', 0, PARAM_INT);
        $PAGE->set_url($FULLME);

        $fromselect = optional_param('fromselect', 0, PARAM_INT);
        $all = optional_param('all', '', PARAM_RAW);
        $select = optional_param('select', '', PARAM_RAW);

        // Get basic objects
        $discussion = mod_forumng_discussion::get_from_id($d, $cloneid);
        if (optional_param('cancel', '', PARAM_RAW)) {
            // CALL TYPE 6
            redirect('../../discuss.php?' .
                    $discussion->get_link_params(mod_forumng::PARAM_PLAIN));
        }
        $forum = $discussion->get_forum();
        $cm = $forum->get_course_module();
        $course = $forum->get_course();
        $isform = optional_param('postselectform', 0, PARAM_INT);

        // Page name and permissions
        $pagename = $selector->get_page_name();
        $buttonname = $selector->get_button_name();
        $discussion->require_view();
        $selector->require_capability($forum->get_context(), $discussion);

        if (!($fromselect || $isform || $all)) {
            // Either an initial request (non-JS) to display the 'dialog' box,
            // or a request to show the list of posts with checkboxes for
            // selection

            // Both types share same navigation
            $out = $discussion->init_page($discussion->get_moodle_url(), $pagename);
            print $out->header();
            if (!$select) {
                // Show initial dialog
                print $out->box_start();
                print html_writer::tag('h2', $buttonname);
                print html_writer::start_tag('form',
                        array('action' => $_SERVER['PHP_SELF'], 'method'=>'get'));
                print html_writer::start_tag('div');
                print $discussion->get_link_params(mod_forumng::PARAM_FORM);
                print html_writer::tag('p', get_string('selectorall', 'forumng'));
                print html_writer::start_tag('div', array('class' => 'forumng-buttons'));
                print html_writer::empty_tag('input', array('name' => 'all',
                        'type' => 'submit', 'value' => get_string('discussion', 'forumng')));
                print html_writer::empty_tag('input', array('name' => 'select',
                        'type' => 'submit', 'value' => get_string('selectedposts', 'forumng')));
                print html_writer::end_tag('div');
                print html_writer::end_tag('div');
                print html_writer::end_tag('form');
                print $out->box_end();
            } else {
                // Show list of posts to select
                print html_writer::start_tag('div', array('class' => 'forumng-selectintro'));
                print html_writer::tag('p', get_string('selectintro', 'forumng'));
                print html_writer::end_tag('div');
                print html_writer::start_tag('form',
                        array('action' => $_SERVER['PHP_SELF'], 'method'=>'post'));
                print html_writer::start_tag('div');
                print $discussion->get_link_params(mod_forumng::PARAM_FORM);
                print html_writer::empty_tag('input', array('type' => 'hidden',
                        'name' => 'fromselect', 'value' => '1'));

                print $out->render_discussion($discussion, array(
                        mod_forumng_post::OPTION_NO_COMMANDS => true,
                        mod_forumng_post::OPTION_CHILDREN_EXPANDED => true,
                        mod_forumng_post::OPTION_SELECTABLE => true));

                print html_writer::start_tag('div', array('class' => 'forumng-selectoutro'));
                print html_writer::empty_tag('input', array('type' => 'submit',
                        'value' => get_string('confirmselection', 'forumng')));
                print html_writer::empty_tag('input', array('type' => 'submit',
                        'name' => 'cancel', 'value' => get_string('cancel')));
                print html_writer::end_tag('div');
                print html_writer::end_tag('div');
                print html_writer::end_tag('form');
            }

            // Display footer
            print $out->footer();
        } else {
            // Call types 3, 4, and 5 use the form (and may include list of postids)
            if ($all) {
                $postids = false;
            } else {
                $postids = array();
                foreach ($_POST as $field => $value) {
                    $matches = array();
                    if (!is_array($value) && (string)$value !== '0' &&
                        preg_match('~^selectp([0-9]+)$~', $field, $matches)) {
                        $postids[] = $matches[1];
                    }
                }
            }

            $out = $discussion->init_page($discussion->get_moodle_url(), $pagename);

            // Get form to use
            $mform = $selector->get_form($discussion, $all, $postids);
            if (!$mform) {
                // Some options do not need a confirmation form; in that case,
                // just apply the action immediately.
                $selector->apply($discussion, $all, $postids, null);
                exit;
            }

            // Check cancel
            if ($mform->is_cancelled()) {
                redirect('../../discuss.php?' .
                        $discussion->get_link_params(mod_forumng::PARAM_PLAIN));
            }

            if ($fromform = $mform->get_data()) {
                // User submitted form to confirm process, which should now be
                // applied by selector.
                $selector->apply($discussion, $all, $postids, $fromform);
                exit;
            } else {
                print $out->header();
                // User requested form either via JavaScript or the other way, and
                // either with all messages or the whole discussion.

                // Print form
                print $mform->display();

                // Print optional content that goes after form
                print $selector->get_content_after_form($discussion, $all,
                    $postids, $fromform);

                    // Display footer
                print $out->footer();
            }
        }
    }

}
