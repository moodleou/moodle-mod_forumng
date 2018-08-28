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
 * Privacy Subsystem implementation for mod_forumng.
 *
 * @package mod_forumng
 * @copyright 2018 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_forumng\privacy;

use \core_privacy\local\request\approved_contextlist;
use \core_privacy\local\request\writer;
use \core_privacy\local\request\helper as request_helper;
use \core_privacy\local\metadata\collection;
use \core_privacy\local\request\transform;

defined('MOODLE_INTERNAL') || die();

/**
 * Implementation of the privacy subsystem plugin provider for the forumng activity module.
 *
 * @package mod_forumng
 * @copyright 2018 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
        // This plugin has data.
        \core_privacy\local\metadata\provider,

        // This plugin currently implements the original plugin\provider interface.
        \core_privacy\local\request\plugin\provider,

        // This plugin has some sitewide user preferences to export.
        \core_privacy\local\request\user_preference_provider {

    use subcontext_info;

    /**
     * Returns meta data about this system.
     *
     * @param collection $items The initialised collection to add items to.
     * @return collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $items): collection {
        // The 'forumng' table does not store any specific user data.
        // The 'forumng_discussions' table does not store any specific user data.

        // The 'forumng_posts' table stores the metadata about each forum discussion.
        $items->add_database_table('forumng_posts', [
                'created' => 'privacy:metadata:forumng_posts:created',
                'modified' => 'privacy:metadata:forumng_posts:modified',
                'subject' => 'privacy:metadata:forumng_posts:subject',
                'message' => 'privacy:metadata:forumng_posts:message',
                'userid' => 'privacy:metadata:forumng_posts:userid',
                'deleted' => 'privacy:metadata:forumng_posts:deleted',
                'messageformat' => 'privacy:metadata:forumng_posts:messageformat',
                'mailstate' => 'privacy:metadata:forumng_posts:mailstate',
                'important' => 'privacy:metadata:forumng_posts:important',
                'attachments' => 'privacy:metadata:forumng_posts:attachments',
                'asmoderator' => 'privacy:metadata:forumng_posts:asmoderator',
                'deleteuserid' => 'privacy:metadata:forumng_posts:deleteuserid',
                'edituserid' => 'privacy:metadata:forumng_posts:edituserid',
                'oldversion' => 'privacy:metadata:forumng_posts:oldversion'
        ], 'privacy:metadata:forumng_posts');

        // The forumng_ratings table store post rating data have been rated by each user.
        $items->add_database_table('forumng_ratings', [
                'userid' => 'privacy:metadata:forumng_ratings:userid',
                'time' => 'privacy:metadata:forumng_ratings:time',
                'rating' => 'privacy:metadata:forumng_ratings:rating'
        ], 'privacy:metadata:forumng_ratings');

        // The forumng_subscriptions table store which forum user is subscribed to by email.
        $items->add_database_table('forumng_subscriptions', [
                'userid' => 'privacy:metadata:forumng_subscriptions:userid',
                'subscribed' => 'privacy:metadata:forumng_subscriptions:subscribed',
        ], 'privacy:metadata:forumng_subscriptions');

        // The 'forumng_read' table stores data about which discussion have been read by each user.
        $items->add_database_table('forumng_read', [
                'userid' => 'privacy:metadata:forumng_read:userid',
                'time' => 'privacy:metadata:forumng_read:time',
        ], 'privacy:metadata:forumng_read');

        // The forumng_read_posts table store data about posts have been read by each user.
        $items->add_database_table('forumng_read_posts', [
                'userid' => 'privacy:metadata:forumng_read_posts:userid',
                'time' => 'privacy:metadata:forumng_read_posts:time',
        ], 'privacy:metadata:forumng_read_posts');

        // The forumng_drafts table store draft message create by each user.
        $items->add_database_table('forumng_drafts', [
                'userid' => 'privacy:metadata:forumng_drafts:userid',
                'subject' => 'privacy:metadata:forumng_drafts:subject',
                'message' => 'privacy:metadata:forumng_drafts:message',
                'messageformat' => 'privacy:metadata:forumng_drafts:messageformat',
                'attachments' => 'privacy:metadata:forumng_drafts:attachments',
                'saved' => 'privacy:metadata:forumng_drafts:saved',
                'options' => 'privacy:metadata:forumng_drafts:options',

        ], 'privacy:metadata:forumng_drafts');

        // The forumng_flags table store individual posts that are of interest by user.
        $items->add_database_table('forumng_flags', [
                'userid' => 'privacy:metadata:forumng_flags:userid',
                'flagged' => 'privacy:metadata:forumng_flags:flagged',
        ], 'privacy:metadata:forumng_flags');

        // Forum posts can be tagged and rated.
        $items->link_subsystem('core_tag', 'privacy:metadata:core_tag');
        $items->link_subsystem('core_rating', 'privacy:metadata:core_rating');
        $items->link_subsystem('core_files', 'privacy:metadata:core_files');

        // There are several user preferences.
        $items->add_user_preference('forumng_simplemode', 'privacy:metadata:preference:forumng_simplemode');
        $items->add_user_preference('maildigest', 'privacy:metadata:preference:maildigest');

        return $items;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * In the case of forum, that is any forum where the user has made any post, rated any content, or has any preferences.
     *
     * @param int $userid The user to search.
     * @return contextlist $contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid): \core_privacy\local\request\contextlist {
        $ratingsql = \core_rating\privacy\provider::get_sql_join('rat', 'mod_forumng', 'post', 'p.id', $userid);
        // Fetch all forumng discussions, and forumng posts.
        $sql = "SELECT c.id
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {forumng} f ON f.id = cm.instance
             LEFT JOIN {forumng_discussions} d ON d.forumngid = f.id
             LEFT JOIN {forumng_posts} p ON p.discussionid = d.id
             LEFT JOIN {forumng_ratings} r ON r.postid = p.id AND r.userid = :ruserid
             LEFT JOIN {forumng_subscriptions} sub ON sub.forumngid = f.id AND sub.userid = :subuserid
             LEFT JOIN {forumng_read} hasreadd ON hasreadd.discussionid = d.id AND hasreadd.userid = :hasreadduserid
             LEFT JOIN {forumng_read_posts} hasreadp ON hasreadp.postid = p.id AND hasreadp.userid = :hasreadpuserid
             LEFT JOIN {forumng_drafts} draft ON draft.forumngid = f.id AND draft.userid = :draftuserid
             LEFT JOIN {forumng_flags} flag ON flag.postid = p.id AND flag.discussionid = d.id AND flag.userid = :flaguserid
                       {$ratingsql->join}
                 WHERE (
                       p.userid = :postuserid OR
                       r.id IS NOT NULL OR
                       sub.id IS NOT NULL OR
                       hasreadd.id IS NOT NULL OR
                       hasreadp.id IS NOT NULL OR
                       draft.id IS NOT NULL OR
                       flag.id IS NOT NULL OR
                       {$ratingsql->userwhere}
                       )
        ";
        $params = [
                'modname' => 'forumng',
                'contextlevel' => CONTEXT_MODULE,
                'postuserid' => $userid,
                'ruserid' => $userid,
                'subuserid' => $userid,
                'hasreadduserid' => $userid,
                'hasreadpuserid' => $userid,
                'draftuserid' => $userid,
                'flaguserid' => $userid,
        ];
        $params += $ratingsql->params;
        $contextlist = new \core_privacy\local\request\contextlist();
        $contextlist->add_from_sql($sql, $params);
        return $contextlist;
    }

    /**
     * Store all user preferences for the plugin.
     *
     * @param int $userid The userid of the user whose data is to be exported.
     */
    public static function export_user_preferences(int $userid) {
        $user = \core_user::get_user($userid);

        switch ($user->maildigest) {
            case 1:
                $digestdescription = get_string('emaildigestcomplete');
                break;
            case 2:
                $digestdescription = get_string('emaildigestsubjects');
                break;
            case 0:
            default:
                $digestdescription = get_string('emaildigestoff');
                break;
        }
        writer::export_user_preference('mod_forumng', 'maildigest', $user->maildigest, $digestdescription);
        $forumng_simplemode = get_user_preferences('forumng_simplemode', null, $userid);
        if (isset($forumng_simplemode)) {
            writer::export_user_preference(
                    'mod_forumng',
                    'forumng_simplemode', transform::yesno($forumng_simplemode),
                    get_string('privacy:metadata:preference:forumng_simplemode', 'mod_forumng')
            );
        }
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist)) {
            return;
        }
        $user = $contextlist->get_user();

        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);

        $sql = "SELECT c.id AS contextid,
                       f.*,
                       cm.id AS cmid
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid
                  JOIN {forumng} f ON f.id = cm.instance
                 WHERE (
                       c.id {$contextsql}
                       )";
        $params = $contextparams;
        // Keep a mapping of forumid to contextid.
        $mappings = [];

        $forums = $DB->get_recordset_sql($sql, $params);
        foreach ($forums as $forum) {
            $mappings[$forum->id] = $forum->contextid;

            $context = \context::instance_by_id($mappings[$forum->id]);

            // Store the main forum data.
            $data = request_helper::get_context_data($context, $user);
            writer::with_context($context)->export_data([], $data);
            request_helper::export_context_files($context, $user);
            static::export_forum_subscription_data($user, $context, $forum);
            static::export_forum_draft_data($user, $context, $forum);
        }
        $forums->close();

        if (!empty($mappings)) {
            // Store all discussion data for this forum.
            static::export_discussion_data($user, $mappings);

            // Store all post data for this forum.
            static::export_all_posts($user, $mappings);
        }
    }

    /**
     * Store all information about all subscription that we have detected this user to have access to in forum.
     *
     * @param \stdClass $user Object representing current user being considered
     * @param \context_module $context The instance of the forum context.
     * @param \stdClass $forum The discussion whose data is being exported.
     */
    protected static function export_forum_subscription_data($user, \context_module $context, \stdClass $forum) {
        global $DB;
        $sql = 'SELECT * FROM {forumng_subscriptions} WHERE userid=:userid AND forumngid=:forumngid';
        $params = [
                'userid' => $user->id,
                'forumngid' => $forum->id,
        ];
        $subscription = $DB->get_recordset_sql($sql, $params);
        if ($subscription) {
            foreach ($subscription as $sub) {
                $subscriptiondata = (object) [
                        'userid' => self::you_or_somebody_else($sub->userid, $user->id),
                        'subscribed' => transform::yesno($sub->subscribed),
                ];
                // Store the forum subscription.
                $area = get_string('forumngsubscriptions', 'mod_forumng') . '-' . $sub->id;
                writer::with_context($context)->export_data(
                        [$area], $subscriptiondata);
            }
        }
        $subscription->close();
    }

    /**
     * Store all information about all draft post that we have detected this user to have access to in forum.
     *
     * @param \stdClass $user Object representing current user being considered
     * @param \context_module $context The instance of the forum context.
     * @param \stdClass $forum The discussion whose data is being exported.
     */
    protected static function export_forum_draft_data($user, \context_module $context, \stdClass $forum) {
        global $DB;
        $sql = 'SELECT * FROM {forumng_drafts} WHERE userid=:userid AND forumngid=:forumngid';
        $params = [
                'userid' => $user->id,
                'forumngid' => $forum->id,
        ];
        $draft = $DB->get_recordset_sql($sql, $params);
        if ($draft) {
            foreach ($draft as $d) {
                $draftdata = (object) [
                        'userid' => self::you_or_somebody_else($d->userid, $user->id),
                        'subject' => $d->subject,
                        'message' => $d->message,
                        'messageformat' => $d->messageformat,
                        'attachments' => transform::yesno($d->attachments),
                        'saved' => transform::datetime($d->saved),
                ];
                // Store the forum draft.
                // Store the associated files.
                $area = get_string('forumngdraft', 'mod_forumng') . '-' . $d->id;
                writer::with_context($context)->export_data(
                        [$area], $draftdata)->export_area_files(
                        [$area], 'mod_forumng', 'draft', $d->id);
            }
        }
        $draft->close();
    }

    /**
     * Store all information about all discussions that we have detected this user to have access to.
     *
     * @param \stdClass $user Object representing current user being considered
     * @param array $mappings A list of mappings from forumid => contextid.
     * @return array Which forums had data written for them.
     */
    protected static function export_discussion_data($user, array $mappings) {
        global $DB;

        // Find all of the discussions for this forum.
        list($foruminsql, $forumparams) = $DB->get_in_or_equal(array_keys($mappings), SQL_PARAMS_NAMED);
        $sql = "SELECT d.*,
                       g.name as groupname
                  FROM {forumng} f
                  JOIN {forumng_discussions} d ON d.forumngid = f.id
             LEFT JOIN {groups} g ON g.id = d.groupid
                  JOIN {forumng_posts} p ON p.discussionid = d.id
                 WHERE f.id ${foruminsql}
                       AND (p.userid = :postuserid)
        ";
        $params = [
                'postuserid' => $user->id,
        ];
        $params += $forumparams;
        // Keep track of the forums which have data.
        $forumswithdata = [];
        $discussions = $DB->get_recordset_sql($sql, $params);
        foreach ($discussions as $discussion) {
            $forumswithdata[$discussion->forumngid] = true;
            $context = \context::instance_by_id($mappings[$discussion->forumngid]);
            static::export_discussion_read_data($user, $context, $discussion);
            static::export_discussion_flag_data($user, $context, $discussion);
            $discussiondata = (object) [
                    'timestart' => transform::datetime($discussion->timestart),
                    'timeend' => transform::datetime($discussion->timeend),
                    'deleted' => transform::yesno($discussion->deleted),
                    'locked' => transform::yesno($discussion->locked),
                    'sticky' => transform::yesno($discussion->sticky),
                    'modified' => transform::datetime($discussion->modified),
                    'ipudloc' => $discussion->ipudloc
            ];
            $discussionarea = static::get_discussion_area($discussion);
            // Store the discussion content.
            writer::with_context($context)->export_data($discussionarea, $discussiondata);
            // Store all tags against this discussion.
            \core_tag\privacy\provider::export_item_tags($user->id, $context,  $discussionarea, 'mod_forumng',
                    'forumng_discussions', $discussion->id);
            // Forum discussions do not have any files associately directly with them.
        }
        $discussions->close();

        return $forumswithdata;
    }

    /**
     * Store all information about all posts that we have detected this user to have access to.
     *
     * @param \stdClass $user Object representing current user being considered
     * @param array $mappings A list of mappings from forumid => contextid.
     * @return array Which forums had data written for them.
     */
    protected static function export_all_posts($user, array $mappings) {
        global $DB;
        // Find all of the posts, and post subscriptions for this forum.
        list($foruminsql, $forumparams) = $DB->get_in_or_equal(array_keys($mappings), SQL_PARAMS_NAMED);
        $ratingsql = \core_rating\privacy\provider::get_sql_join('rat', 'mod_forumng', 'post', 'p.id', $user->id);
        $sql = "SELECT p.discussionid AS id,
                       f.id AS forumngid,
                       d.groupid
                  FROM {forumng} f
                  JOIN {forumng_discussions} d ON d.forumngid = f.id
                  JOIN {forumng_posts} p ON p.discussionid = d.id
                       {$ratingsql->join}
                 WHERE f.id ${foruminsql} AND
                       (
                       p.userid = :postuserid
                       )
              GROUP BY f.id, p.discussionid, d.groupid
        ";
        $params = [
                'postuserid' => $user->id,
        ];
        $params += $forumparams;
        $params += $ratingsql->params;
        $discussions = $DB->get_records_sql($sql, $params);
        foreach ($discussions as $discussion) {
            $context = \context::instance_by_id($mappings[$discussion->forumngid]);
            static::export_all_posts_in_discussion($user, $context, $discussion);
        }
    }

    /**
     * Store all information about all posts that we have detected this user to have access to.
     *
     * @param \stdClass $user Object representing current user being considered
     * @param \context $context The instance of the forum context.
     * @param \stdClass $discussion The discussion whose data is being exported.
     */
    protected static function export_all_posts_in_discussion($user, \context $context, \stdClass $discussion) {
        global $DB;
        $discussionid = $discussion->id;

        // Find all of the posts, and post read for this forum.
        $ratingsql = \core_rating\privacy\provider::get_sql_join('rat', 'mod_forumng', 'post', 'p.id', $user->id);
        $sql = "SELECT p.*,
                       d.forumngid AS forumngid,
                       fr.time as readtime,
                       fr.id AS readflag,
                       fr.userid AS fruserid,
                       flag.id AS hasflag,
                       flag.flagged AS flagged,
                       flag.userid AS flaguserid,
                       rat.id AS hasratings
                  FROM {forumng_discussions} d
                  JOIN {forumng_posts} p ON p.discussionid = d.id
             LEFT JOIN {forumng_read_posts} fr ON fr.postid = p.id AND fr.userid = :readuserid
             LEFT JOIN {forumng_flags} flag ON flag.postid = p.id AND flag.userid = :flaguserid
                       {$ratingsql->join} AND {$ratingsql->userwhere}
                 WHERE d.id = :discussionid
        ";
        $params = [
                'discussionid' => $discussionid,
                'readuserid' => $user->id,
                'flaguserid' => $user->id,
                'ratinguserid' => $user->id
        ];

        $params += $ratingsql->params;
        // Keep track of the forums which have data.
        $structure = (object) [
                'children' => [],
        ];
        $posts = $DB->get_records_sql($sql, $params);
        foreach ($posts as $post) {
            $post->hasdata = (isset($post->hasdata)) ? $post->hasdata : false;
            $post->hasdata = ($post->hasdata || (!empty($post->hasratings) || !empty($post->hasratings) ||
                            !empty($post->readflag) || !empty($post->hasflag) || ($post->userid == $user->id)));
            if (0 == $post->parentpostid) {
                $structure->children[$post->id] = $post;
            } else {
                if (empty($posts[$post->parentpostid]->children)) {
                    $posts[$post->parentpostid]->children = [];
                }
                $posts[$post->parentpostid]->children[$post->id] = $post;
            }

            // Set all parents.
            if ($post->hasdata) {
                $curpost = $post;
                while ($curpost->parentpostid != 0) {
                    $curpost = $posts[$curpost->parentpostid];
                    $curpost->hasdata = true;
                }
            }
        }

        $discussionarea = static::get_discussion_area($discussion);
        $discussionarea[] = get_string('posts', 'mod_forumng');
        static::export_posts_in_structure($user, $context, $discussionarea, $structure);
    }

    /**
     * Export all posts in the provided structure.
     *
     * @param \stdClass $user Object representing current user being considered
     * @param \context $context The instance of the forum context.
     * @param array $parentarea The subcontext of the parent.
     * @param \stdClass $structure The post structure and all of its children
     */
    protected static function export_posts_in_structure($user, \context $context, $parentarea, \stdClass $structure) {
        foreach ($structure->children as $post) {
            if (!$post->hasdata) {
                // This tree has no content belonging to the user. Skip it and all children.
                continue;
            }

            $postarea = array_merge($parentarea, static::get_post_area($post));

            // Store the post content.
            static::export_post_data($user, $context, $postarea, $post);

            if (isset($post->children)) {
                // Now export children of this post.
                static::export_posts_in_structure($user, $context, $postarea, $post);
            }
        }
    }

    /**
     * Export all data in the post.
     *
     * @param \stdClass $user Object representing current user being considered
     * @param \context $context The instance of the forum context.
     * @param array $postarea The subcontext of the parent.
     * @param \stdClass $post The post structure and all of its children
     */
    protected static function export_post_data($user, \context $context, $postarea, $post) {
        // Store related metadata.
        static::export_read_post_data($user, $context, $postarea, $post);
        static::export_flags_post_data($user, $context, $postarea, $post);
        static::export_custom_rating_post_data($user, $context, $postarea, $post);
        $postdata = (object) [
                'deleted' => transform::yesno($post->deleted),
                'deleted_by_you' => transform::yesno($post->deleteuserid == $user->id),
                'edited_by_you' => transform::yesno($post->edituserid == $user->id),
                'important' => transform::yesno($post->important),
                'mailstate' => $post->mailstate,
                'oldversion' => $post->oldversion,
                'subject' => format_string($post->subject, true),
                'message' => $post->message,
                'messageformat' => $post->messageformat,
                'attachments' => transform::yesno($post->attachments),
                'asmoderator' => transform::yesno($post->asmoderator),
                'created' => transform::datetime($post->created),
                'modified' => transform::datetime($post->modified),
                'author_was_you' => transform::yesno($post->userid == $user->id)
        ];

        if ($post->userid == $user->id) {
            $postdata->message = writer::with_context($context)->rewrite_pluginfile_urls(
                    $postarea, 'mod_forumng', 'post', $post->id, $post->message);
            // Store the post.
            // Store the associated files.
            writer::with_context($context)->export_data(
                    $postarea, $postdata)->export_area_files(
                    $postarea, 'mod_forumng', 'attachment', $post->id);

            // Store all ratings against this post as the post belongs to the user. All ratings on it are ratings of their content.
            \core_rating\privacy\provider::export_area_ratings($user->id, $context, $postarea, 'mod_forumng',
                    'post', $post->id, false);
        }
        // Check for any ratings that the user has made on this post.
        \core_rating\privacy\provider::export_area_ratings($user->id,
                $context,
                $postarea,
                'mod_forumng',
                'post',
                $post->id,
                $user->id,
                true
        );
    }

    /**
     * Store custom rating information about a particular forum post.
     *
     * @param \stdClass $user Object representing current user being considered
     * @param \context_module $context The instance of the forum context.
     * @param array $postarea The subcontext for this post.
     * @param \stdClass $post The post whose data is being exported.
     * @return bool Whether any data was stored.
     */
    protected static function export_custom_rating_post_data($user, \context_module $context,
            array $postarea, \stdClass $post) {
        global $DB;
        $ratings = $DB->get_records('forumng_ratings', ['postid' => $post->id]);
        if ($ratings) {
            foreach ($ratings as $r) {
                $a = (object) [
                        'author' => self::you_or_somebody_else($r->userid, $user->id),
                        'time' => transform::datetime($r->time),
                        'customrating' => $r->rating
                ];
                writer::with_context($context)->export_metadata(
                        $postarea,
                        'customrating' . $r->id,
                        $a,
                        get_string('privacy:postwasrated', 'mod_forumng', $a)
                );
            }
            return true;
        }
        return false;
    }

    /**
     * Store flagged information about a particular forum post.
     *
     * @param \stdClass $user Object representing current user being considered
     * @param \context_module $context The instance of the forum context.
     * @param array $postarea The subcontext for this post.
     * @param \stdClass $post The post whose data is being exported.
     * @return bool Whether any data was stored.
     */
    protected static function export_flags_post_data($user, \context_module $context, array $postarea, \stdClass $post) {
        if (null !== $post->flagged && $post->flaguserid == $user->id) {
            writer::with_context($context)->export_metadata(
                    $postarea,
                    'flags' . $post->hasflag,
                    (object) [
                            'userid' => self::you_or_somebody_else($post->flaguserid, $user->id),
                            'flagged' => transform::yesno($post->flagged)
                    ],
                    get_string('privacy:postwasflagged', 'mod_forumng')
            );

            return true;
        }
        return false;
    }

    /**
     * Store read-tracking information about a particular forum post.
     *
     * @param \stdClass $user Object representing current user being considered
     * @param \context_module $context The instance of the forum context.
     * @param array $postarea The subcontext for this post.
     * @param \stdClass $post The post whose data is being exported.
     * @return bool Whether any data was stored.
     */
    protected static function export_read_post_data($user, \context_module $context, array $postarea, \stdClass $post) {
        if (null !== $post->readflag && $user->id == $post->fruserid) {
            $readtime = transform::datetime($post->readtime);
            $a = (object) [
                    'time' => $readtime,
            ];
            writer::with_context($context)->export_metadata(
                    $postarea,
                    'read_post' . $post->readflag,
                    (object) [
                            'userid' => self::you_or_somebody_else($post->fruserid, $user->id),
                            'time' => $readtime,
                    ],
                    get_string('privacy:postwasread', 'mod_forumng', $a)
            );

            return true;
        }

        return false;
    }

    /**
     * Store read-tracking information about a particular discussion.
     *
     * @param \stdClass $user Object representing current user being considered
     * @param \context_module $context The instance of the forum context.
     * @param array $postarea The subcontext for this post.
     * @param \stdClass $post The post whose data is being exported.
     * @return bool Whether any data was stored.
     */
    protected static function export_discussion_read_data($user, \context_module $context, \stdClass $discussion) {
        global $DB;
        $sql = 'SELECT * FROM {forumng_read} WHERE userid = :userid AND discussionid = :discussionid';
        $params = [
                'userid' => $user->id,
                'discussionid' => $discussion->id,
        ];
        $read = $DB->get_recordset_sql($sql, $params);
        foreach ($read as $r) {
            $readdata = (object) [
                    'userid' => self::you_or_somebody_else($r->userid, $user->id),
                    'time' => transform::datetime($r->time)
            ];
            // Store the discussion read data.
            $discussionarea = static::get_discussion_area($discussion);
            $discussionarea[] = get_string('forumngreaddiscussion', 'mod_forumng');
            writer::with_context($context)->export_data($discussionarea, $readdata);
        }
        $read->close();

    }

    /**
     * Store flag information about a particular discussion.
     *
     * @param \stdClass $user Object representing current user being considered
     * @param \context_module $context The instance of the forum context.
     * @param array $postarea The subcontext for this post.
     * @param \stdClass $post The post whose data is being exported.
     * @return bool Whether any data was stored.
     */
    protected static function export_discussion_flag_data($user, \context_module $context, \stdClass $discussion) {
        global $DB;
        $sql = 'SELECT * FROM {forumng_flags} WHERE userid = :userid AND discussionid = :discussionid AND postid = 0';
        $params = [
                'userid' => $user->id,
                'discussionid' => $discussion->id,
        ];
        $flag = $DB->get_recordset_sql($sql, $params);
        foreach ($flag as $f) {
            $readdata = (object) [
                    'userid' => self::you_or_somebody_else($f->userid, $user->id),
                    'flagged' => transform::yesno($f->flagged)
            ];

            // Store the discussion flag data.
            $discussionarea = static::get_discussion_area($discussion);
            $discussionarea[] = get_string('forumngflagdiscussion', 'mod_forumng');
            writer::with_context($context)->export_data($discussionarea, $readdata);
        }
        $flag->close();
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        // Check that this is a context_module.
        if (!$context instanceof \context_module) {
            return;
        }

        // Get the course module.
        if (!$cm = get_coursemodule_from_id('forumng', $context->instanceid)) {
            return;
        }
        $forumid = $cm->instance;

        $DB->delete_records('forumng_subscriptions', ['forumngid' => $forumid]);
        $DB->delete_records('forumng_drafts', ['forumngid' => $forumid]);

        // Delete all discussion items.
        $query = 'discussionid IN (SELECT id FROM {forumng_discussions} WHERE forumngid = :forumngid)';
        $param = ['forumngid' => $forumid];
        $DB->delete_records_select(
                'forumng_read', $query, $param
        );
        $DB->delete_records_select(
                'forumng_flags', $query, $param
        );
        $postquery = 'postid IN (SELECT p.id FROM {forumng_discussions} d
                           JOIN {forumng_posts} p on p.discussionid = d.id
                          WHERE d.forumngid = :forumngid)';

        $DB->delete_records_select(
                'forumng_ratings', $postquery, $param
        );
        $DB->delete_records_select(
                'forumng_read_posts', $postquery, $param
        );
        $DB->delete_records_select(
                'forumng_posts', $query, $param
        );
        // Delete all ratings in the context.
        \core_rating\privacy\provider::delete_ratings($context, 'mod_forumng', 'post');
        $discussionsql = "SELECT id FROM {forumng_discussions}
                           WHERE forumngid = :forumngid";
        // Delete all Tags.
        \core_tag\privacy\provider::delete_item_tags_select($context, 'mod_forumng', 'forumng_discussions',
                "IN ($discussionsql)", $param);

        // Delete discussion in forum.
        $DB->delete_records('forumng_discussions', $param);

        // Delete all files from the posts.
        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'mod_forumng', 'post');
        $fs->delete_area_files($context->id, 'mod_forumng', 'draft');
        $fs->delete_area_files($context->id, 'mod_forumng', 'attachments');
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        $user = $contextlist->get_user();
        $userid = $user->id;
        $fs = get_file_storage();
        foreach ($contextlist as $context) {
            // Get the course module.
            $cm = $DB->get_record('course_modules', ['id' => $context->instanceid]);
            $forum = $DB->get_record('forumng', ['id' => $cm->instance]);
            $draftsql = 'SELECT id FROM {forumng_drafts} WHERE userid = :userid';
            $draftsparam = [
                    'forumngid' => $forum->id,
                    'userid' => $userid,
            ];
            $fs->delete_area_files_select($context->id, 'mod_forumng', 'draft',
                    "IN ($draftsql)", $draftsparam);
            $DB->delete_records('forumng_drafts', $draftsparam);
            // Delete all discussion items.
            $DB->delete_records_select(
                    'forumng_subscriptions',
                    "userid = :userid",
                    [
                            'userid' => $userid,
                    ]
            );

            $DB->delete_records_select(
                    'forumng_read',
                    "userid = :userid AND discussionid IN (SELECT id FROM {forumng_discussions} WHERE forumngid = :forumngid)",
                    [
                            'userid' => $userid,
                            'forumngid' => $forum->id,
                    ]
            );

            $DB->delete_records_select(
                    'forumng_flags',
                    "userid = :userid AND discussionid IN (SELECT id FROM {forumng_discussions} WHERE forumngid = :forumngid)",
                    [
                            'userid' => $userid,
                            'forumngid' => $forum->id,
                    ]
            );

            $DB->delete_records_select(
                    'forumng_read_posts',
                    "userid = :userid",
                    [
                            'userid' => $userid,
                    ]
            );

            $DB->delete_records_select(
                    'forumng_ratings',
                    "userid = :userid",
                    [
                            'userid' => $userid,
                    ]
            );
            // Do not delete discussion or forum posts.
            // Instead update them to reflect that the content has been deleted.
            $childpostsql = "userid = :userid AND parentpostid IS NOT NULL AND discussionid IN
                    (SELECT id FROM {forumng_discussions} WHERE forumngid = :forumngid)";
            $postsql = "userid = :userid AND discussionid IN (SELECT id FROM {forumng_discussions}
                                                                WHERE forumngid = :forumngid)";
            $postidsql = "SELECT fp.id FROM {forumng_posts} fp WHERE {$postsql}";
            $postparams = [
                    'forumngid' => $forum->id,
                    'userid' => $userid,
            ];

            // Update the subject.
            $DB->set_field_select('forumng_posts', 'subject', '', $childpostsql, $postparams);

            // Update the subject and its format.
            $DB->set_field_select('forumng_posts', 'message', '', $postsql, $postparams);
            $DB->set_field_select('forumng_posts', 'messageformat', FORMAT_PLAIN, $postsql, $postparams);

            // Mark the post as deleted.
            $DB->set_field_select('forumng_posts', 'deleted', 1, $postsql, $postparams);

            \core_rating\privacy\provider::delete_ratings_select($context, 'mod_forumng', 'post',
                    "IN ($postidsql)", $postparams);

            $postquery = 'postid IN (SELECT p.id FROM {forumng_discussions} d
                               JOIN {forumng_posts} p on p.discussionid = d.id and p.userid = :userid
                              WHERE d.forumngid = :forumngid)';
            $DB->delete_records_select('forumng_read_posts', $postquery, ['userid' => $userid, 'forumngid' => $forum->id]);

            $fs->delete_area_files_select($context->id, 'mod_forumng', 'post',
                    "IN ($postidsql)", $postparams);
            $fs->delete_area_files_select($context->id, 'mod_forumng', 'attachments',
                    "IN ($postidsql)", $postparams);
        }
    }

    /**
     * Removes personally-identifiable data from a user id for export.
     *
     * @param int $userid User id of a person
     * @param \stdClass $user Object representing current user being considered
     * @return string 'You' if the two users match, 'Somebody else' otherwise
     */
    protected static function you_or_somebody_else($userid, $currentuserid) {
        if ($userid == $currentuserid) {
            return get_string('privacy_you', 'mod_forumng');
        } else {
            return get_string('privacy_somebodyelse', 'mod_forumng');
        }
    }
}
