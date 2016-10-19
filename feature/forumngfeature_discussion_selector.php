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
 * This uses the discussion selector infrastructure to
 * handle the situation when discussions are being selected.
 * @package mod
 * @subpackage forumng
 * @copyright 2012 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->dirroot . '/mod/forumng/mod_forumng.php');
require_once($CFG->dirroot . '/mod/forumng/feature/forumngfeature_post_selector.php');

/**
 * A class that deals with the various HTTP requests involved in selecting
 * specific discussions (or a whole page of discussions) for processing, either in JavaScript
 * or non-JavaScript modes. Goes with matching JavaScript code in module.js.
 *
 * Example usage, in a file such as forward.php:
 *
 * // start of file
 * require_once('../forumngfeature_discussion_selector.php');
 *
 * class forward_post_selector extends forumngfeature_discussion_selector() {
 *   // class implements the base class methods below
 * }
 *
 * forumngfeature_discussion_selector::go(new forward_post_selector());
 * // end of file
 */
abstract class forumngfeature_discussion_selector extends forumngfeature_post_selector {

    /**
     * For overriding in subclass. If this feature requires a particular
     * capability, require it here. The system will already have checked view
     * permission for the discussion.
     * @param object $context Moodle context object for forum
     * @param mod_forumng $forum Forum object
     */
    public function require_capability($context, $forum) {
        // Default makes no extra checks.
    }

    /**
     * Return array of discussion types (e.g. deleted) that will have selectors
     * Use this to limit options available for selection
     * You must also have an "include" => string added to your discussion list button ouput options
     * @return array
     */
    public function only_discussion_types() {
        return array();
    }

    /**
     * Return array of discussion types (e.g. deleted) that will not have selectors
     * Use this to limit options available for selection
     * You must also have an "exclude" => string added to your discussion list button ouput options
     * @return array
     */
    public function exclude_discussion_types() {
        return array();
    }

    /**
     * This function handles all aspects of page processing and then calls
     * methods in $selector at the appropriate moments.
     * @param post_selector $selector Object that extends this base class
     */
    static public function go($selector) {
        global $PAGE, $FULLME, $SESSION;
        $id = required_param('id', PARAM_INT);
        $cloneid = optional_param('clone', 0, PARAM_INT);
        $fromselect = optional_param('fromselect', 0, PARAM_INT);
        $all = optional_param('all', '', PARAM_RAW);
        $select = optional_param('select', '', PARAM_RAW);
        $isform = optional_param('postselectform', 0, PARAM_INT);
        $PAGE->set_url($FULLME);

        // Get basic objects.
        $forum = mod_forumng::get_from_cmid($id, $cloneid);
        $forumngid = $forum->get_id();
        $params = array_merge($_REQUEST, $forum->get_link_params_array());
        if (optional_param('cancel', '', PARAM_RAW)) {
            // CALL TYPE 6.
            redirect('../../view.php?' .
                    $forum->get_link_params(mod_forumng::PARAM_PLAIN));
        }
        $cm = $forum->get_course_module();
        $course = $forum->get_course();
        $groupid = mod_forumng::get_activity_group($cm, true);

        // Page name and permissions.
        $pagename = $selector->get_page_name();
        $buttonname = $selector->get_button_name();
        $forum->require_view($groupid);
        $selector->require_capability($forum->get_context(), $forum);

        if (!($fromselect || $isform || $all)) {
            // Either an initial request (non-JS) to display the 'dialog' box,
            // or a request to show the list of posts with checkboxes for selection.
            // Both types share same navigation.
            $out = $forum->init_page(new moodle_url('/mod/forumng/view.php', $forum->get_link_params_array()));
            print $out->header();
            if (!$select) {
                // Show initial dialog.
                print $out->box_start();
                print html_writer::tag('h2', $buttonname);
                print html_writer::start_tag('form',
                        array('action' => $_SERVER['PHP_SELF'], 'method'=>'get', 'id' => 'discsel'));
                print html_writer::start_tag('div');
                foreach ($params as $param => $paramval) {
                    print html_writer::empty_tag('input', array('name' => $param,
                            'type' => 'hidden', 'value' => $paramval));
                }
                print html_writer::tag('p', get_string('selectordiscall', 'forumng'));
                print html_writer::start_tag('div', array('class' => 'forumng-buttons'));
                print html_writer::empty_tag('input', array('name' => 'all',
                        'type' => 'submit', 'value' => get_string('selectoralldisc', 'forumng')));
                print html_writer::empty_tag('input', array('name' => 'select',
                        'type' => 'submit', 'value' => get_string('selectorselecteddisc', 'forumng')));
                print html_writer::empty_tag('input', array('name' => 'cancel',
                        'type' => 'submit', 'value' => get_string('cancel')));
                print html_writer::end_tag('div');
                print html_writer::end_tag('div');
                print html_writer::end_tag('form');
                print $out->box_end();
            } else {
                // Show list of posts to select.
                print html_writer::start_tag('div', array('class' => 'forumng-selectintro'));
                print html_writer::tag('p', get_string('selectdiscintro', 'forumng'));
                print html_writer::end_tag('div');
                print html_writer::start_tag('form',
                        array('action' => $_SERVER['PHP_SELF'], 'method'=>'post', 'id' => 'discsel'));
                print html_writer::start_tag('div');
                print $forum->get_link_params(mod_forumng::PARAM_FORM);
                print html_writer::empty_tag('input', array('type' => 'hidden',
                        'name' => 'fromselect', 'value' => '1'));
                foreach ($params as $param => $paramval) {
                    print html_writer::empty_tag('input', array('name' => $param,
                            'type' => 'hidden', 'value' => $paramval));
                }
                // Now show discussions, allow for override at type level if following is no good.
                if (method_exists($forum->get_type(), 'print_select_page')) {
                    print $forum->get_type()->print_select_page($forum, $groupid);
                } else {
                    // Use default processing, get view and hack in selectors.
                    ob_start();
                    $forum->get_type()->print_view_page($forum, $groupid);
                    $discussionhtml = ob_get_contents();
                    ob_end_clean();
                    // Try and hack into the discussion list - must be xhtml...
                    $doc = new DOMDocument('1.0', 'utf-8');
                    @$doc->loadHTML($discussionhtml);
                    $docnew = new DOMDocument('1.0', 'utf-8');
                    $xpath = new DOMXPath($doc);
                    $lists = $xpath->query("//table[contains(concat(' ',normalize-space(@class),' '),' forumng-discussionlist ')]");
                    // Remove all links.
                    foreach ($lists as $list) {
                        $links = $xpath->query("//a|//form", $list);
                        foreach ($links as $node) {
                            if ($node->nodeName == 'a') {
                                // Disable links.
                                $node->removeAttribute('href');
                            } else {
                                // Remove any forms.
                                $node->parentNode->removeChild($node);
                            }
                        }
                        // Add in discussion select.
                        $rows = $xpath->query("//table[@class='generaltable forumng-discussionlist']
                                //tr[not(@class) or @class!='forumng-divider']", $list);
                        for ($a = 0, $len = $rows->length; $a < $len; $a++) {
                            // Add in select options for each row, checking types.
                            $row = $rows->item($a);
                            if ($a == 0) {
                                $newcell = $doc->createElement('th', get_string('selectorselectdisc', 'mod_forumng'));
                                $newcell->setAttribute('class', 'header');
                                $newcell->setAttribute('scope', 'col');
                                $row->appendChild($newcell);
                            } else {
                                $id = $row->getAttribute('id');
                                if (strpos($id, 'discrow') === false) {
                                    continue;
                                }
                                // Get discussion id from row id as added by renderer.
                                $id = str_replace('discrow_', '', $id);
                                // Check if we include checkbox or not.
                                $classar = explode(' ', $row->getAttribute('class'));
                                $includematches = array_intersect($selector->only_discussion_types(), $classar);
                                $excludematches = array_intersect($selector->exclude_discussion_types(), $classar);
                                if ((count($selector->only_discussion_types()) == 0 || count($includematches) > 0)
                                        && count($excludematches) == 0) {
                                    // OK to include, add checkbox and label.
                                    $select = $doc->createElement('input');
                                    $select->setAttribute('type', 'checkbox');
                                    $select->setAttribute('name', "selectd$id");
                                    $select->setAttribute('id', "selectd$id");
                                    $label = $doc->createElement('label', get_string('selectorselectdisc', 'mod_forumng'));
                                    $label->setAttribute('for', "selectd$id");
                                    $label->setAttribute('class', 'accesshide');
                                    $newcell = $doc->createElement('td');
                                    $newcell->setAttribute('class', 'dselect');
                                    $newcell->appendChild($select);
                                    $newcell->appendChild($label);
                                    $row->appendChild($newcell);
                                } else {
                                    $newcell = $doc->createElement('td', '&nbsp;');
                                    $row->appendChild($newcell);
                                }
                            }
                        }
                        // Keep only discussion list by moving to new xml doc.
                        $newnode = $docnew->importNode($list, true);
                        $docnew->appendChild($newnode);
                    }
                    print $docnew->saveHTML();
                }

                print html_writer::start_tag('div', array('class' => 'forumng-selectoutro'));
                print html_writer::empty_tag('input', array('type' => 'submit',
                        'value' => get_string('confirmselection', 'forumng')));
                print html_writer::empty_tag('input', array('type' => 'submit',
                        'name' => 'cancel', 'value' => get_string('cancel')));
                print html_writer::end_tag('div');
                print html_writer::end_tag('div');
                print html_writer::end_tag('form');
            }

            // Display footer.
            print $out->footer();
        } else {

            // Call types 3, 4, and 5 use the form (and may include list of postids).
            $postids = array();
            $selectedids = array();
            foreach ($_POST as $field => $value) {
                $matches = array();
                if (!is_array($value) && (string)$value !== '0' &&
                    preg_match('~^selectd([0-9]+)$~', $field, $matches)) {
                    $selectedids[] = ($matches[1]);
                }
            }

            if (!empty($selectedids)) {
                // Check access.
                foreach ($selectedids as $id) {
                    $discuss = mod_forumng_discussion::get_from_id($id, $cloneid);
                    if ($discuss->can_view()) {
                        $postids[] = $id;
                    }
                }
            } else {
                if (!$all) {
                    // No slections made.
                    redirect('../../view.php?' .
                            $forum->get_link_params(mod_forumng::PARAM_PLAIN));
                }
                // Work out discussion list for this page (e.g. selected All).
                $sortorder = optional_param('sort', 'd' , PARAM_ALPHA);
                if (isset($SESSION->forumng_discussionlist[$forumngid]->sort)) {
                    $sortorder = $SESSION->forumng_discussionlist[$forumngid]->sort;
                }
                $page = optional_param('page', 1, PARAM_INT);
                if (isset($SESSION->forumng_discussionlist[$forumngid]->page)) {
                    $page = $SESSION->forumng_discussionlist[$forumngid]->page;
                }
                $sortchar = substr($sortorder, 0, 1);
                if (strlen($sortorder) == 2) {
                    $sortreverse = (substr($sortorder, 1, 1) == 'r') ? true : false;
                } else {
                    $sortreverse = false;
                }
                $sort = mod_forumng::get_sort_code($sortchar);

                $list = $forum->get_discussion_list($groupid, $forum->can_view_hidden(),
                        $page, $sort, $sortreverse);
                $discussionsarr = array_merge($list->get_sticky_discussions(), $list->get_normal_discussions());
                // Double check ID is valid and user can view.
                for ($a = 0; $a < count($discussionsarr); $a++) {
                    if ($discussionsarr[$a]->can_view()) {
                        $postids[] = $discussionsarr[$a]->get_id();
                    }
                }
            }

            $out = $forum->init_page(new moodle_url('/mod/forumng/view.php', $forum->get_link_params_array()), $pagename);

            // Get form to use.
            $mform = $selector->get_form($forum, $all, $postids);
            if (!$mform) {
                // Some options do not need a confirmation form; in that case,
                // just apply the action immediately.
                $selector->apply($forum, $all, $postids, null);
                exit;
            }

            // Check cancel.
            if ($mform->is_cancelled()) {
                redirect('../../view.php?' .
                        $forum->get_link_params(mod_forumng::PARAM_PLAIN));
            }

            if ($fromform = $mform->get_data()) {
                // User submitted form to confirm process, which should now be
                // applied by selector.
                $selector->apply($forum, $all, $postids, $fromform);
                exit;
            } else {
                print $out->header();
                // User requested form either via JavaScript or the other way, and
                // either with all messages or the whole discussion.

                // Print form.
                print $mform->display();

                // Print optional content that goes after form.
                print $selector->get_content_after_form($forum, $all,
                    $postids, $fromform);

                    // Display footer.
                print $out->footer();
            }
        }
    }

}
