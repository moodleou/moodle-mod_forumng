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
 * Forum backup structure step.
 * @package mod
 * @subpackage forumng
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the backup steps that will be used by the backup_forumng_activity_task
 */

/**
 * Define the complete forumng structure for backup, with file and id annotations
 */
class backup_forumng_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // To know if we are including userinfo
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated

        $forumng = new backup_nested_element('forumng', array('id'), array(
            'name', 'intro', 'introformat', 'type',
            'ratingscale', 'ratingfrom', 'ratinguntil',
            'ratingthreshold', 'grading', 'attachmentmaxbytes',
            'reportingemail', 'subscription', 'feedtype', 'feeditems',
            'maxpostsperiod', 'maxpostsblock', 'postingfrom', 'postinguntil',
            'typedata', 'magicnumber', 'completiondiscussions', 'completionreplies',
            'completionposts', 'removeafter', 'removeto', 'shared', 'originalcmid', 'gradingscale'));

        $discussions = new backup_nested_element('discussions');

        $discussion = new backup_nested_element('discussion', array('id'), array(
            'groupid', 'timestart', 'timeend', 'deleted', 'locked', 'sticky'));

        $posts = new backup_nested_element('posts');

        $post = new backup_nested_element('post', array('id'), array(
            'parentpostid', 'userid', 'created', 'modified', 'deleted', 'deleteuserid',
            'important', 'mailstate', 'oldversion', 'edituserid',
            'subject', 'message', 'messageformat', 'attachments'));

        $ratings = new backup_nested_element('ratings');

        $rating = new backup_nested_element('rating', array('id'), array(
            'userid', 'time', 'rating'));

        $subscriptions = new backup_nested_element('subscriptions');

        $subscription = new backup_nested_element('subscription', array('id'), array(
            'userid', 'subscribed', 'discussionid', 'clonecmid', 'groupid'));

        $readdiscussions = new backup_nested_element('readdiscussions');

        $read = new backup_nested_element('read', array('id'), array(
            'userid', 'time'));

        $drafts = new backup_nested_element('drafts');

        $draft = new backup_nested_element('draft', array('id'), array(
            'userid', 'groupid', 'parentpostid',
            'subject', 'message', 'messageformat', 'attachments', 'saved', 'options'));

        $flags = new backup_nested_element('flags');

        $flag = new backup_nested_element('flag', array('id'), array(
            'userid', 'flagged'));

        // Build the tree
        $forumng->add_child($discussions);
        $discussions->add_child($discussion);

        $forumng->add_child($subscriptions);
        $subscriptions->add_child($subscription);

        $forumng->add_child($drafts);
        $drafts->add_child($draft);

        $discussion->add_child($posts);
        $posts->add_child($post);

        $discussion->add_child($readdiscussions);
        $readdiscussions->add_child($read);

        $post->add_child($ratings);
        $ratings->add_child($rating);

        $post->add_child($flags);
        $flags->add_child($flag);

        // Define sources
        $forumng->set_source_table('forumng', array('id' => backup::VAR_ACTIVITYID));

        // All these source definitions only happen if we are including user info
        if ($userinfo) {
            $discussion->set_source_table('forumng_discussions',
                    array('forumngid' => backup::VAR_PARENTID));

            $subscription->set_source_table('forumng_subscriptions',
                    array('forumngid' => backup::VAR_PARENTID));

            $draft->set_source_table('forumng_drafts',
                    array('forumngid' => backup::VAR_PARENTID));

            // Need posts ordered by id so parents are always before childs on restore
            $post->set_source_sql("SELECT * FROM {forumng_posts} WHERE discussionid = ?" .
                    "ORDER BY id", array(backup::VAR_PARENTID));

            $read->set_source_table('forumng_read', array('discussionid' => backup::VAR_PARENTID));

            $rating->set_source_table('forumng_ratings', array('postid' => backup::VAR_PARENTID));

            $flag->set_source_table('forumng_flags', array('postid' => backup::VAR_PARENTID));
        }

        // Define id annotations
        $forumng->annotate_ids('course_modules', 'originalcmid');
        $forumng->annotate_ids('scale', 'ratingscale');

        $discussion->annotate_ids('group', 'groupid');

        $post->annotate_ids('user', 'userid');
        $post->annotate_ids('user', 'deleteuserid');
        $post->annotate_ids('user', 'edituserid');

        $rating->annotate_ids('user', 'userid');

        $subscription->annotate_ids('user', 'userid');
        $subscription->annotate_ids('group', 'groupid');
        $subscription->annotate_ids('course_modules', 'clonecmid');

        $read->annotate_ids('user', 'userid');

        $draft->annotate_ids('user', 'userid');
        $draft->annotate_ids('group', 'groupid');

        $flag->annotate_ids('user', 'userid');

        // Define file annotations
        $forumng->annotate_files('mod_forumng', 'intro', null); // This file area hasn't itemid
        $post->annotate_files('mod_forumng', 'message', 'id');
        $post->annotate_files('mod_forumng', 'attachment', 'id');
        $draft->annotate_files('mod_forumng', 'draft', 'id');

        // Return the root element (forumng), wrapped into standard activity structure
        return $this->prepare_activity_structure($forumng);
    }
}
