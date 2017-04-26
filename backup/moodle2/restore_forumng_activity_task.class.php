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
 * Forum restore task.
 * @package mod
 * @subpackage forumng
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Because it exists (must).
require_once($CFG->dirroot . '/mod/forumng/backup/moodle2/restore_forumng_stepslib.php');

/**
 * forumng restore task that provides all the settings and steps to perform one
 * complete restore of the activity.
 */
class restore_forumng_activity_task extends restore_activity_task {

    /**
     * Define (add) particular settings this activity can have.
     */
    protected function define_my_settings() {
        // No particular settings for this activity.
    }

    /**
     * Define (add) particular steps this activity can have.
     */
    protected function define_my_steps() {
        // Choice only has one structure step.
        $this->add_step(new restore_forumng_activity_structure_step('forumng_structure',
                'forumng.xml'));
    }

    /**
     * Define the contents in the activity that must be
     * processed by the link decoder.
     */
    static public function define_decode_contents() {
        $contents = array();

        $contents[] = new restore_decode_content('forumng', array('intro'));
        $contents[] = new restore_decode_content('forumng', array('introduction'));
        $contents[] = new restore_decode_content('forumng_posts', array('message'));

        return $contents;
    }

    /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder.
     */
    static public function define_decode_rules() {
        $rules = array();

        // List of forumngs in course.
        $rules[] = new restore_decode_rule('FORUMNGINDEX',
                '/mod/forumng/index.php?id=$1', 'course');
        // Forum by cm->id.
        $rules[] = new restore_decode_rule('FORUMNGVIEWBYID',
                '/mod/forumng/view.php?id=$1', 'course_module');
        // Link to forumng discussion.
        $rules[] = new restore_decode_rule('FORUMNGDISCUSSIONVIEW',
                '/mod/forumng/discuss.php?d=$1', 'forumng_discussion');
        // Link to discussion with anchor post.
        $rules[] = new restore_decode_rule('FORUMNGDISCUSSIONVIEWINSIDE',
                '/mod/forumng/discuss.php?d=$1#p$2', array('forumng_discussion', 'forumng_post'));

        return $rules;
    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * forumng logs. It must return one array
     * of {@link restore_log_rule} objects.
     */
    static public function define_restore_log_rules() {
        $rules = array();

        $rules[] = new restore_log_rule('forumng', 'add',
                'view.php?id={course_module}', '{forumng}');
        $rules[] = new restore_log_rule('forumng', 'update',
                'view.php?id={course_module}', '{forumng}');
        $rules[] = new restore_log_rule('forumng', 'view',
                'view.php?id={course_module}', '{forumng}');
        /*
        TODO Figure out all the things it can possibly put in log and work out transformation for all/most
        $rules[] = new restore_log_rule('forumng', 'view forumng',
        'view.php?id={course_module}', '{forumng}');
        $rules[] = new restore_log_rule('forumng', 'mark read',
                'view.php?f={forumng}', '{forumng}');
        $rules[] = new restore_log_rule('forumng', 'start tracking',
        'view.php?f={forumng}', '{forumng}');
        $rules[] = new restore_log_rule('forumng', 'stop tracking',
        'view.php?f={forumng}', '{forumng}');
        $rules[] = new restore_log_rule('forumng', 'subscribe',
        'view.php?f={forumng}', '{forumng}');
        $rules[] = new restore_log_rule('forumng', 'unsubscribe',
        'view.php?f={forumng}', '{forumng}');
        $rules[] = new restore_log_rule('forumng', 'subscriber',
        'subscribers.php?id={forumng}', '{forumng}');
        $rules[] = new restore_log_rule('forumng', 'subscribers',
        'subscribers.php?id={forumng}', '{forumng}');
        $rules[] = new restore_log_rule('forumng', 'view subscribers',
        'subscribers.php?id={forumng}', '{forumng}');
        $rules[] = new restore_log_rule('forumng', 'add discussion',
        'discuss.php?d={forumng_discussion}', '{forumng_discussion}');
        $rules[] = new restore_log_rule('forumng', 'view discussion',
        'discuss.php?d={forumng_discussion}', '{forumng_discussion}');
        $rules[] = new restore_log_rule('forumng', 'move discussion',
        'discuss.php?d={forumng_discussion}', '{forumng_discussion}');
        $rules[] = new restore_log_rule('forumng', 'delete discussi',
                'view.php?id={course_module}', '{forumng}', null, 'delete discussion');
        $rules[] = new restore_log_rule('forumng', 'delete discussion',
                'view.php?id={course_module}', '{forumng}');
        $rules[] = new restore_log_rule('forumng', 'add post',
                'discuss.php?d={forumng_discussion}&parent={forumng_post}', '{forumng_post}');
        $rules[] = new restore_log_rule('forumng', 'update post',
                'discuss.php?d={forumng_discussion}&parent={forumng_post}', '{forumng_post}');
        $rules[] = new restore_log_rule('forumng', 'prune post',
                'discuss.php?d={forumng_discussion}', '{forumng_post}');
        $rules[] = new restore_log_rule('forumng', 'delete post',
                'discuss.php?d={forumng_discussion}', '[post]');
        */
        return $rules;
    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * course logs. It must return one array
     * of {@link restore_log_rule} objects.
     *
     * Note this rules are applied when restoring course logs
     * by the restore final task, but are defined here at
     * activity level. All them are rules not linked to any module instance (cmid = 0).
     */
    static public function define_restore_log_rules_for_course() {
        $rules = array();
        /*
        TODO Sort this out too
        $rules[] = new restore_log_rule('forumng', 'view forumngs', 'index.php?id={course}', null);
        $rules[] = new restore_log_rule('forumng',
                'subscribeall', 'index.php?id={course}', '{course}');
        $rules[] = new restore_log_rule('forumng',
                'unsubscribeall', 'index.php?id={course}', '{course}');
        $rules[] = new restore_log_rule('forumng',
                'user report', 'user.php?course={course}&id={user}&mode=[mode]', '{user}');
        $rules[] = new restore_log_rule('forumng',
                'search', 'search.php?id={course}&search=[searchenc]', '[search]');
        */
        return $rules;
    }
}
