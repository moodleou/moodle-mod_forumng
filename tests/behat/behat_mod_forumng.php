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

use Behat\Behat\Context\Step\Given as Given,
    Behat\Gherkin\Node\TableNode as TableNode;
use Behat\Behat\Context\Step\Then;
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
        $steps = array(
            new Given('I press "' . get_string('addanewdiscussion', 'mod_forumng') . '"'),
            new Given('I set the following fields to these values:', $data),
        );
        if ($this->running_javascript()) {
            $steps[] = new Given('I wait until "#id_submitbutton[disabled]" "css_element" does not exist');
        }
        $steps[] = new Given('I press "' . get_string('postdiscussion', 'forumng') . '"');
        if ($this->running_javascript()) {
            $steps[] = new Given('I wait until the page is ready');
        }
        return $steps;
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
        return $this->interact_with_post('reply', $post, $data);
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
        return $this->interact_with_post('edit', $post, $data);
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
        return $this->interact_with_post('draft', $post, $data);
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
        $steps = array();
        if ($expand = $this->i_expand_post($post)) {
            $steps = array_merge($steps, $expand);
        }
        $steps[] = new Given('I click on ".forumng-post.forumng-p' . $post . ' .forumng-commands .' . $link .' a" "css_element"');
        // Switch steps depending on javascript as page acts differently.
        if ($this->running_javascript()) {
            $steps[] = new Given('I switch to "forumng-post-iframe" iframe');
            $steps[] = new Given('I wait until "' . $savebutton . '" "button" exists');
            if ($type == 'reply') {
                $steps[] = new Then('the "' . $savebutton . '" "button" should be disabled');
            }
            $steps[] = new Given('I set the following fields to these values:', $data);
            // Wait for save button to become enabled (otherwise will skip submit).
            if ($type == 'draft') {
                $steps[] = new Given('I wait until "#id_savedraft[disabled]" "css_element" does not exist');
            } else {
                $steps[] = new Given('I wait until "#id_submitbutton[disabled]" "css_element" does not exist');
            }
            $steps[] = new Then('the "' . $savebutton . '" "button" should be enabled');
            $steps[] = new Given('I press "' . $savebutton . '"');
            $steps[] = new Given('I switch to the main frame');
        } else {
            $steps[] = new Given('I set the following fields to these values:', $data);
            $steps[] = new Given('I press "' . $savebutton . '"');
            // Ensure always expanded as sometimes seem not to be when JS disabled.
            $steps[] = new Given('I expand post "0"');
        }
        return $steps;
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
                    return array(new Given('I follow "' . get_string('expandall', 'mod_forumng') . '"'));
                }
            } else {
                if ($this->find('css', '.forumng-p' . $post . ' .forumng-expandlink', $exc, false, 3)) {
                    // Ensure all posts available for reply as we found expand link.
                    return array(new Given('I click on ".forumng-p' . $post . ' .forumng-expandlink" "css_element"'));
                }
            }
        } catch (ElementNotFoundException $e) {
            return null;
        }
    }
}
