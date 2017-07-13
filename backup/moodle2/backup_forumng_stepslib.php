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
 * Define all the backup steps that will be used by the backup_forumng_activity_task.
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Define the complete forumng structure for backup, with file and id annotations.
 */
class backup_forumng_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated.

        $forumng = new backup_nested_element('forumng', array('id'), array(
            'name', 'intro', 'introformat', 'introduction', 'introductionformat', 'type',
            'ratingscale', 'ratingfrom', 'ratinguntil',
            'ratingthreshold', 'grading', 'attachmentmaxbytes',
            'reportingemail', 'subscription', 'feedtype', 'feeditems',
            'maxpostsperiod', 'maxpostsblock', 'postingfrom', 'postinguntil',
            'typedata', 'magicnumber', 'completiondiscussions', 'completionreplies',
            'completionposts', 'removeafter', 'removeto', 'shared', 'originalcmid',
            'gradingscale', 'canpostanon', 'enabletags', 'enableratings', 'timemodified'));

        $discussions = new backup_nested_element('discussions');

        $discussion = new backup_nested_element('discussion', array('id'), array(
            'groupid', 'timestart', 'timeend', 'deleted', 'locked', 'sticky', 'modified'));

        $posts = new backup_nested_element('posts');

        $post = new backup_nested_element('post', array('id'), array(
            'parentpostid', 'userid', 'created', 'modified', 'deleted', 'deleteuserid',
            'important', 'mailstate', 'oldversion', 'edituserid',
            'subject', 'message', 'messageformat', 'attachments', 'asmoderator'));

        $newratings = new backup_nested_element('newratings');

        $newrating = new backup_nested_element('newrating', array('id'), array(
            'component', 'ratingarea', 'scaleid', 'value', 'userid', 'timecreated', 'timemodified'));

        $ratings = new backup_nested_element('ratings');

        $rating = new backup_nested_element('rating', array('id'), array(
            'userid', 'time', 'rating'));

        $subscriptions = new backup_nested_element('subscriptions');

        $subscription = new backup_nested_element('subscription', array('id'), array(
            'userid', 'subscribed', 'discussionid', 'clonecmid', 'groupid'));

        $readdiscussions = new backup_nested_element('readdiscussions');

        $read = new backup_nested_element('read', array('id'), array(
            'userid', 'time'));

        $readposts = new backup_nested_element('readposts');

        $readp = new backup_nested_element('readpost', array('id'), array(
                'userid', 'time'));

        $drafts = new backup_nested_element('drafts');

        $draft = new backup_nested_element('draft', array('id'), array(
            'userid', 'groupid', 'parentpostid',
            'subject', 'message', 'messageformat', 'attachments', 'saved', 'options', 'asmoderator'));

        $flags = new backup_nested_element('flags');

        $flag = new backup_nested_element('flag', array('id'), array(
            'userid', 'flagged'));

        $flagsd = new backup_nested_element('flagsd');

        $flagd = new backup_nested_element('flagd', array('id'), array(
            'userid', 'flagged'));

        $tags = new backup_nested_element('tags');

        $tag = new backup_nested_element('tag', array('id'), array('name', 'rawname'));

        $forumtaginstances = new backup_nested_element('forumtaginstances');

        $forumtaginstance = new backup_nested_element('forumtaginstance', array('id'), array(
            'name', 'rawname', 'tagid', 'itemtype', 'tiuserid', 'ordering', 'component'));

        $forumgrouptaginstances = new backup_nested_element('forumgrouptaginstances');

        $forumgrouptaginstance = new backup_nested_element('forumgrouptaginstance', array('id'), array(
                'name', 'rawname', 'tagid', 'itemtype', 'itemid', 'tiuserid', 'ordering', 'component'));

        // Build the tree.
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

        $discussion->add_child($flagsd);
        $flagsd->add_child($flagd);

        $post->add_child($newratings);
        $newratings->add_child($newrating);

        $post->add_child($ratings);
        $ratings->add_child($rating);

        $post->add_child($flags);
        $flags->add_child($flag);

        $post->add_child($readposts);
        $readposts->add_child($readp);

        $discussion->add_child($tags);
        $tags->add_child($tag);

        $forumng->add_child($forumtaginstances);
        $forumtaginstances->add_child($forumtaginstance);

        $forumng->add_child($forumgrouptaginstances);
        $forumgrouptaginstances->add_child($forumgrouptaginstance);

        // Define sources.
        $forumng->set_source_table('forumng', array('id' => backup::VAR_ACTIVITYID));

        // All these source definitions only happen if we are including user info.
        if ($userinfo) {
            $discussion->set_source_table('forumng_discussions',
                    array('forumngid' => backup::VAR_PARENTID));

            $subscription->set_source_table('forumng_subscriptions',
                    array('forumngid' => backup::VAR_PARENTID));

            $draft->set_source_table('forumng_drafts',
                    array('forumngid' => backup::VAR_PARENTID));

            // Need posts ordered by id so parents are always before childs on restore.
            $post->set_source_sql("SELECT * FROM {forumng_posts} WHERE discussionid = ?" .
                    "ORDER BY id", array(backup::VAR_PARENTID));

            $read->set_source_table('forumng_read', array('discussionid' => backup::VAR_PARENTID));

            $readp->set_source_table('forumng_read_posts', array('postid' => backup::VAR_PARENTID));

            $newrating->set_source_table('rating', array(
                    'contextid' => backup::VAR_CONTEXTID,
                    'itemid' => backup::VAR_PARENTID,
                    'component' => backup_helper::is_sqlparam('mod_forumng'),
                    'ratingarea' => backup_helper::is_sqlparam('post')));
            $newrating->set_source_alias('rating', 'value');

            $rating->set_source_table('forumng_ratings', array('postid' => backup::VAR_PARENTID));

            $flag->set_source_table('forumng_flags', array('postid' => backup::VAR_PARENTID));

            $flagd->set_source_table('forumng_flags', array('discussionid' => backup::VAR_PARENTID));

            $tag->set_source_sql('SELECT t.id, t.name, t.rawname
                                    FROM {tag} t
                                    JOIN {tag_instance} ti ON ti.tagid = t.id
                                   WHERE ti.itemtype = ?
                                     AND ti.component = ?
                                     AND ti.itemid = ?', array(
                                                        backup_helper::is_sqlparam('forumng_discussions'),
                                                        backup_helper::is_sqlparam('mod_forumng'),
                                                        backup::VAR_PARENTID));
        }

        $forumtaginstance->set_source_sql('SELECT t.name, t.rawname, ti.*
                FROM {tag} t
                JOIN {tag_instance} ti ON ti.tagid = t.id
                WHERE ti.contextid = ?
                AND ti.itemid = ?
                AND ti.itemtype = ?
                AND ti.component = ?', array(
                        backup::VAR_CONTEXTID,
                        backup::VAR_PARENTID,
                        backup_helper::is_sqlparam('forumng'),
                        backup_helper::is_sqlparam('mod_forumng')));

        $forumgrouptaginstance->set_source_sql('SELECT t.name, t.rawname, ti.*
                FROM {tag} t
                JOIN {tag_instance} ti ON ti.tagid = t.id
               WHERE ti.contextid = ?
                AND ti.itemtype = ?
                AND ti.component = ?', array(
                      backup::VAR_CONTEXTID,
                      backup_helper::is_sqlparam('groups'),
                      backup_helper::is_sqlparam('mod_forumng')));

        // Define id annotations.
        $forumng->annotate_ids('course_modules', 'originalcmid');
        $forumng->annotate_ids('scale', 'ratingscale');

        $discussion->annotate_ids('group', 'groupid');

        $post->annotate_ids('user', 'userid');
        $post->annotate_ids('user', 'deleteuserid');
        $post->annotate_ids('user', 'edituserid');

        $newrating->annotate_ids('user', 'userid');
        $newrating->annotate_ids('scale', 'scaleid');

        $rating->annotate_ids('user', 'userid');

        $subscription->annotate_ids('user', 'userid');
        $subscription->annotate_ids('group', 'groupid');
        $subscription->annotate_ids('course_modules', 'clonecmid');

        $read->annotate_ids('user', 'userid');
        $readp->annotate_ids('user', 'userid');

        $draft->annotate_ids('user', 'userid');
        $draft->annotate_ids('group', 'groupid');

        $flag->annotate_ids('user', 'userid');

        $forumgrouptaginstance->annotate_ids('group', 'itemid');

        // Define file annotations.
        $forumng->annotate_files('mod_forumng', 'intro', null); // This file area hasn't itemid.
        $forumng->annotate_files('mod_forumng', 'introduction', null); // This file area hasn't itemid.
        $post->annotate_files('mod_forumng', 'message', 'id');
        $post->annotate_files('mod_forumng', 'attachment', 'id');
        $draft->annotate_files('mod_forumng', 'draft', 'id');

        // Return the root element (forumng), wrapped into standard activity structure.
        return $this->prepare_activity_structure($forumng);
    }
}
