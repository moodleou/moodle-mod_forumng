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
 * Steps definitions related with the forumng activity.
 *
 * @package    mod_forumng
 * @category   test
 * @copyright  2014 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Gherkin\Node\TableNode as TableNode;
use Behat\Mink\Exception\ElementNotFoundException;

/**
 * forum-related steps definitions.
 *
 * @package    mod_forumng
 * @category   test
 * @copyright  2013 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_mod_forumng extends behat_base {

    /**
     * Adds a discussion to the current forum with the provided data. You should be in the main view page.
     * End point is the discussion page.
     *
     * @Given /^I add a discussion with the following data:$/
     * @param TableNode $data
     */
    public function i_add_a_dicussion_with_the_following_data(TableNode $data) {
        $this->execute('behat_forms::press_button', array(get_string('addanewdiscussion', 'mod_forumng')));
        $this->execute('behat_forms::i_set_the_following_fields_to_these_values', array($data));
        if ($this->running_javascript()) {
            $this->execute('behat_general::wait_until_does_not_exists',
                    array('#id_submitbutton[disabled]', 'css_element'));
        }
        $this->execute('behat_forms::press_button', array(get_string('postdiscussion', 'mod_forumng')));
        if ($this->running_javascript()) {
            $this->execute('behat_general::wait_until_the_page_is_ready', array());
        }
    }

    /**
     * Updates forumng post created date.
     * Indentified by subject and using date format (YYYY-MM-DD OR YYYY/MM/DD).
     * E.g. | Discussion 1 | 2015-01-20 |
     * @Given /^I amend the forumng posts to new created date:$/
     * @param TableNode $data
     */
    public function i_amend_the_forumng_posts_to_new_created_date(TableNode $data) {
        global $DB;

        foreach ($data->getRows() as $rowdata) {
            $conditions = array('subject' => $rowdata[0]);
            $idtochange = $DB->get_field('forumng_posts', 'id', $conditions);
            if ($idtochange) {
                $updateobject = new stdClass();
                $updateobject->created = trim(strtotime(str_replace('/', '-', $rowdata[1])));
                $updateobject->id = $idtochange;
                $DB->update_record('forumng_posts', $updateobject);
            }
        }
    }

    /**
     * Updates forumng post rated date.
     * Indentified by rater username, subject and using date format (YYYY-MM-DD OR YYYY/MM/DD).
     * E.g. | student1 | Discussion 1 | 2015-01-20 |
     * @Given /^I amend the forumng rated posts to new rated date:$/
     * @param TableNode $data
     */
    public function i_amend_the_forumng_rated_posts_to_new_rated_date(TableNode $data) {
        global $DB;

        foreach ($data->getRows() as $rowdata) {
            $conditions = array('subject' => $rowdata[1]);
            $postid = $DB->get_field('forumng_posts', 'id', $conditions);
            if ($postid) {
                $conditions = $conditions = array('username' => $rowdata[0]);
                $userid = $DB->get_field('user', 'id', $conditions);
                if ($userid) {
                    $conditions = array(
                            'userid' => $userid, 'itemid' => $postid, 'component' => 'mod_forumng', 'ratingarea' => 'post');
                    $ratingid = $DB->get_field('rating', 'id', $conditions);
                    if ($ratingid) {
                        $newtime = trim(strtotime(str_replace('/', '-', $rowdata[2])));
                        $updateobject = new stdClass();
                        $updateobject->id = $ratingid;
                        $updateobject->timecreated = $newtime;
                        $updateobject->timemodified = $newtime;
                        $updateobject->itemid = $postid;
                        $DB->update_record('rating', $updateobject);
                    }
                }
            }
        }
    }

    /**
     * Replies to numbered post (e.g. "2" is second post on page) with the provided data.
     * You should be in the discussion view page.
     * Note this step will always expand the post.
     *
     * @Given /^I reply to post "(?P<post_number>\d+)" with the following data:$/
     * @param int $post
     * @param TableNode $data
     */
    public function i_reply_to_post_with_the_following_data($post, TableNode $data) {
        $this->interact_with_post('reply', $post, $data);
    }

    /**
     * Edits a numbered post (e.g. "2" is second post on page) with the provided data.
     * You should be in the discussion view page.
     * Note this step will always expand the post.
     *
     * @Given /^I edit post "(?P<post_number>\d+)" with the following data:$/
     * @param int $post
     * @param TableNode $data
     */
    public function i_edit_post_with_the_following_data($post, TableNode $data) {
        $this->interact_with_post('edit', $post, $data);
    }

    /**
     * Replies to numbered post (e.g. "2" is second post on page) with the provided data.
     * You should be in the discussion view page.
     * Note this step will always expand the post.
     *
     * @Given /^I reply to post "(?P<post_number>\d+)" as draft with the following data:$/
     * @param int $post
     * @param TableNode $data
     */
    public function i_reply_to_post_as_draft_with_the_following_data($post, TableNode $data) {
        $this->interact_with_post('draft', $post, $data);
    }

    /**
     * This function is the one that does the post steps and adds to form
     * The type used reflects the different types of interaction with post
     * @param string $type 'reply'(default) or 'edit' or 'draft'
     * @param int $post
     * @param TableNode $data
     * @return multitype:\Behat\Behat\Context\Step\Given \Behat\Behat\Context\Step\Then
     */
    private function interact_with_post($type = 'reply', $post, TableNode $data) {
        $link = 'forumng-replylink';
        $savebutton = get_string('postreply', 'forumng');
        if ($type == 'edit') {
            $link = 'forumng-edit';
            $savebutton = get_string('savechanges');
        } else if ($type == 'draft') {
            $savebutton = get_string('savedraft', 'forumng');
        }
        $this->i_expand_post($post);
        $this->execute('behat_general::i_click_on', array('.forumng-post.forumng-p' . $post .
                ' .forumng-commands .' . $link .' a', 'css_element'));
        // Switch steps depending on javascript as page acts differently.
        if ($this->running_javascript()) {
            $this->execute('behat_general::switch_to_iframe', array('forumng-post-iframe'));
            $this->execute('behat_general::wait_until_exists', array($savebutton, 'button'));
            $this->execute('behat_general::wait_until_the_page_is_ready', array());
            if ($type == 'reply') {
                $this->execute('behat_general::the_element_should_be_disabled',
                        array($savebutton, 'button'));
            }
            $this->execute('behat_forms::i_set_the_following_fields_to_these_values', array($data));
            // Wait for save button to become enabled (otherwise will skip submit).
            if ($type == 'draft') {
                $this->execute('behat_general::wait_until_does_not_exists',
                        array('#id_savedraft[disabled]', 'css_element'));
            } else {
                $this->execute('behat_general::wait_until_does_not_exists',
                        array('#id_submitbutton[disabled]', 'css_element'));
            }
            $this->execute('behat_general::the_element_should_be_enabled',
                    array($savebutton, 'button'));
            $this->execute('behat_forms::press_button', array($savebutton));
            $this->execute('behat_general::switch_to_the_main_frame', array());
            $this->execute('behat_general::wait_until_does_not_exists',
                    array('iframe[name=forumng-post-iframe]', 'css_element'));
        } else {
            $this->execute('behat_forms::i_set_the_following_fields_to_these_values', array($data));
            $this->execute('behat_forms::press_button', array($savebutton));
            // Ensure always expanded as sometimes seem not to be when JS disabled.
            $this->execute('behat_mod_forumng::i_expand_post', array('0'));
        }
    }

    /**
     * Expands post on page (post number i.e. "2" = second post)
     * "0" for expand all
     *
     * @Given /^I expand post "(?P<post_number>\d+)"$/
     * @param int $post
     * @param TableNode $data
     */
    public function i_expand_post($post = null) {
        try {
            $exc = new ElementNotFoundException($this->getSession());
            if (empty($post)) {
                if ($this->find('css', '.forumng-expandall-link', $exc, false, 3)) {
                    // Ensure all posts available for reply as we found expand link.
                    $this->execute('behat_general::click_link',
                            array(get_string('expandall', 'mod_forumng')));
                }
            } else {
                if ($this->find('css', '.forumng-p' . $post . ' .forumng-expandlink', $exc, false, 3)) {
                    // Ensure all posts available for reply as we found expand link.
                    $this->execute('behat_general::i_click_on', array(
                            '.forumng-p' . $post . ' .forumng-expandlink', 'css_element'));
                }
            }
        } catch (ElementNotFoundException $e) {
            // I guess we ignore this? Don't know why.
            return;
        }
    }
}
