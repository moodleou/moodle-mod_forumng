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
 * Developer test tool to construct a big forum. Maybe doesn't work.
 * @package mod
 * @subpackage forumng
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Note: As a development test tool, this script is English-only.
require_once('../../../config.php');
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/mod/forumng/lib.php');
require_once($CFG->dirroot.'/mod/forumng/mod_forumng.php');
require_once($CFG->dirroot.'/mod/forumng/mod_forumng_exception.php');
require_login();
require_capability('moodle/site:config', context_system::instance());
if (!debugging('', DEBUG_DEVELOPER)) {
    throw new moodle_exception('Available only in debug mode');
}

/**
 * Returns random numbers with some kind of nice distribution that is mainly
 * low then tails off, but ends up averaging at the mean. The number is never
 * less than 1.
 * @param int $mean Mean value
 * @return int Random number
 */
function my_random($mean) {
    $mean-=0.5; // Because we add 1, but also do floor. Note that I tested this
                // function over a fair number of runs; it appears to get
                // the correct mean.
    return floor((mt_rand(0, 1000)/1000) * (mt_rand(0, 1000)/1000) * 4.0 * $mean)+1;
}

/**
 * Returns true if a percentage chance comes off.
 * @param float $percent Percentage value. May be floating-point.
 * @return bool True if the chance hits.
 */
function my_random_percentage($percent) {
    $value = 100.0 * mt_rand() / (mt_getrandmax()+1);
    return ($value < $percent);
}

function get_post_text() {
    // 660 is the current mean number of bytes for a forum post text on Learn.
    $length = my_random(660);
    $text = '';
    for ($i=0; $i < $length; $i++) {
        $text .= '0';
        if (my_random_percentage(20)) {
            $text .= ' ';
        }
    }
    return $text;
}

function make_post($discussion, &$allposts, &$userids, $ratingpercent) {
    // Make reply
    static $index = 0;
    $index++;
    $replyto = $allposts[rand(0, count($allposts)-1)];
    $newpostid = $replyto->reply(
        my_random_percentage(25) ? 'Reply '.$index : null,
        get_post_text(), FORMAT_HTML,
        array(), false, $userids[mt_rand(0, count($userids)-1)], false);
    $newpost = mod_forumng_post::get_from_id($newpostid, mod_forumng::CLONE_DIRECT);
    $allposts[] = $newpost;

    // Add ratings
    for ($i=0; $i<count($userids); $i++) {
        if (my_random_percentage($ratingpercent)) {
            $newpost->rate(2, $userids[$i]);
        }
    }
}

function make_discussion($forum, $posts, $readusers, &$userids, $ratingpercent) {
    core_php_time_limit::raise(200);

    // Make discussion
    static $index = 0;
    $index++;
    list($discussionid, $postid) = $forum->create_discussion(null,
        'Discussion '.$index, get_post_text(), FORMAT_HTML, array(), false,
        0, 0, false, false, $userids[mt_rand(0, count($userids)-1)], false);
    $discussion = mod_forumng_discussion::get_from_id($discussionid, mod_forumng::CLONE_DIRECT);

    // Make posts
    $count = my_random($posts)-1;
    $allposts = array($discussion->get_root_post());
    for ($i=0; $i < $count; $i++) {
        make_post($discussion, $allposts, $userids, $ratingpercent);
    }

    // Mark the discussion read if requested
    if ($readusers > 0) {
        $now = time();
        for ($i = 0; $i < $readusers; $i++) {
            $discussion->mark_read($now, $userids[$i]);
        }
    }

    // Progress
    print '.';
}

function make_forumng($courseid, $starttime, $discussions, $posts,
    $readpercent, $readusers, &$userids, $subscribepercent, $ratingpercent) {
    global $DB;
    $section = $DB->get_record('course_sections', array('course' => $courseid, 'section' => 0),
            '*', MUST_EXIST);

    $transaction = $DB->start_delegated_transaction();

    // Create course modules record
    $mod = new stdClass;
    $mod->course = $courseid;
    $mod->module = $DB->get_field('modules', 'id', array('name' => 'forumng'));
    $mod->section = $section->section; // was $section->id; logical but incorrect!
    $mod->added = $starttime;
    $mod->visible = 1;

    // course_modules and course_sections each contain a reference
    // to each other, so we have to update one of them twice.
    // Note: This is unbelievable!!! $mod->section MUST BE section number (not id)
    //       Adds course_module with section number, add_mod_to_section uses
    //       section number (& course id) to get section id, which is returned
    //       course module record then updated to replace section number by id!!!
    if (! $mod->coursemodule = add_course_module($mod) ) {
        throw new Exception("Could not add a new course module");
    }

    if (!$sectionid = course_add_cm_to_section($mod->course, $mod->coursemodule, $mod->section)) {
        throw new Exception("Could not add the new course module to that section");
    }

    // Create forum object
    $forumng = new stdClass;
    static $index = 0;
    $index++;
    $forumng->name = 'Perf test '.date('Ymd H:j', $starttime).' '.$index;
    $forumng->course = $courseid;
    $forumng->section = $section;
    $forumng->cmidnumber = $mod->coursemodule;

    if (!($forumng->id = forumng_add_instance($forumng))) {
        throw new dml_exception('Failed to add forum');
    }

    // Mark cm object as owning it
    $updatemod = new stdClass;
    $updatemod->id = $mod->coursemodule;
    $updatemod->instance = $forumng->id;
    $updatemod->section = $sectionid;
    $DB->update_record('course_modules', $updatemod);

    // Make it be random users included in the forum
    shuffle($userids);

    // OK, forum is created. Let's make discussions
    $forum = mod_forumng::get_from_id($forumng->id, mod_forumng::CLONE_DIRECT);
    $count = my_random($discussions);
    for ($i=0; $i < $count; $i++) {
        make_discussion($forum, $posts,
            my_random_percentage($readpercent) ? $readusers : 0, $userids,
            $ratingpercent);
    }

    // Add subscribe users
    core_php_time_limit::raise(200);
    for ($i=0; $i < $readusers; $i++) {
        if (my_random_percentage($subscribepercent)) {
            $forum->subscribe($userids[$i]);
        }
    }

    $transaction->allow_commit();
}

function wipe_forumng($cm) {
    global $DB;
    $transaction = $DB->start_delegated_transaction();

    // Tell forum to delete itself
    if (!forumng_delete_instance($cm->instance)) {
        throw new Exception("Could not delete forum instance {$cm->instance}");
    }

    // Delete course_module entry
    $DB->delete_records('course_modules', array('id' => $cm->id));

    // Update section
    if (!delete_mod_from_section($cm->id, $cm->section)) {
        throw new Exception("Could not delete module {$cm->id} from section {$cm->section}");
    }

    $transaction->allow_commit();
}

function make_forums($courseid, $count, $discussions, $posts,
    $readpercent, $readusers, $subscribepercent, $ratingpercent) {
    global $DB;

    // Require course to exist
    $DB->get_record('course', array('id'=>$courseid), '*', MUST_EXIST);

    // Get all course users
    $context = context_course::instance($courseid);
    $rs = $DB->get_recordset_sql("
SELECT
    DISTINCT userid
FROM
    {role_assignments}
WHERE
    contextid = ?", array($context->id));
    $userids = array();
    foreach ($rs as $record) {
        $userids[] = $record->userid;
    }
    $rs->close();
    if ($readusers > count($userids)) {
        throw new moodle_exception('error_makebig', 'forumng', '', (object)array(
                'users' => count($userids), 'readusers' => $readusers));
    }

    // Create forums
    print "<h3>Making $count forums</h3><pre>";
    $time = time();
    for ($i=0; $i < $count; $i++) {
        make_forumng($courseid, $time, $discussions, $posts,
            $readpercent, $readusers, $userids, $subscribepercent,
            $ratingpercent);
        print 'X';
        if (($i+1)%20==0) {
            print " (".($i+1).")\n";
        }
        flush();
    }
    print "</pre>";

    rebuild_course_cache($courseid);
}

function wipe_forums($courseid) {
    global $DB;

    // Get list of course-module IDs
    $forumngid = $DB->get_field('modules', 'id', array('name' => 'forumng'));
    $cms = $DB->get_records_sql("
SELECT
    id, course, instance, section
FROM
    {course_modules} cm
WHERE
    cm.module = ? AND cm.course = ?", array($forumngid, $courseid));

    $count = count($cms);
    print "<h3>Wiping $count forums</h3><pre>";
    $time = time();
    $i = 0;
    foreach ($cms as $cm) {
        wipe_forumng($cm);
        print '.';
        if (($i+1)%20==0) {
            print " (".($i+1).")\n";
        }
        flush();
        $i++;
    }
    print "</pre>";

    rebuild_course_cache($courseid);
}

function make_student($courseid, $username) {
    global $DB;

    // Create user record
    $user = new StdClass;
    $user->username = $username;
    $user->lastname = $username;
    $user->email = $username . '@somewhere';
    $user->emailstop = 1;
    $user->firstname = 'Ms';
    $user->auth = 'manual';
    $user->password = md5('apple');
    $user->id = $DB->insert_record('user', $user);

    // Assign to course
    $context = context_course::instance($courseid);
    static $roleid;
    if (!$roleid) {
        $roleid = $DB->get_field('role', 'id', array('shortname' => 'student'));
    }
    if (!role_assign($roleid, $user->id, 0, $context->id)) {
        throw new Exception('Failed to assign role');
    }
}

function make_students($courseid, $count) {
    global $DB;
    print "<h3>Making $count students</h3><pre>";
    $time = time();
    $transaction = $DB->start_delegated_transaction();
    for ($i=0; $i < $count; $i++) {
        make_student($courseid, $time.'_'.$i);
        print '.';
        if (($i+1)%20==0) {
            print " (".($i+1).")\n";
        }
        flush();
    }
    $transaction->allow_commit();
    print "</pre>";

}

class make_big_form extends moodleform {
    public function definition() {
        global $CFG;

        $mform =& $this->_form;

        $mform->addElement('header', 'h1', 'Test course');

        $mform->addElement('text', 'course', 'Course ID');
        $mform->setType('course', PARAM_INT);

        $mform->addElement('header', 'h1', 'Create forums');

        $mform->addElement('text', 'forums', 'Number of forums');
        $mform->setType('forums', PARAM_INT);
        $mform->setDefault('forums', 10);

        $mform->addElement('text', 'readusers', 'Number of users reading each forum');
        $mform->setType('readusers', PARAM_INT);
        $mform->setDefault('readusers', 1000);

        $mform->addElement('text', 'discussions', 'Number of discussions per forum (avg)');
        $mform->setType('discussions', PARAM_FLOAT);
        $mform->setDefault('discussions', 100);

        $mform->addElement('text', 'posts', 'Number of posts per discussion (avg)');
        $mform->setType('posts', PARAM_FLOAT);
        $mform->setDefault('posts', 10);

        $mform->addElement('text', 'readpercent',
                '% of recent discussions (that include read data)');
        $mform->setType('readpercent', PARAM_FLOAT);
        $mform->setDefault('readpercent', 10);

        $mform->addElement('text', 'subscribepercent', '% of users who subscribe');
        $mform->setType('subscribepercent', PARAM_FLOAT);
        $mform->setDefault('subscribepercent', 50);

        $mform->addElement('text', 'ratingpercent', '% chance of each user rating each post');
        $mform->setType('ratingpercent', PARAM_FLOAT);
        $mform->setDefault('ratingpercent', 0.1);

        $mform->addElement('submit', 'submitcreate', 'Create forums');

        $mform->addElement('header', 'h2', 'Wipe all forums');

        $mform->addElement('text', 'confirm', 'Are you sure? (Type yes)');
        $mform->setType('text', PARAM_ALPHA);

        $mform->addElement('submit', 'submitwipe', 'Wipe forums');

        $mform->addElement('header', 'h2', 'Create course students');

        $mform->addElement('text', 'students', 'Number of students to create');
        $mform->setType('students', PARAM_INT);
        $mform->setDefault('students', 100);

        $mform->addElement('submit', 'submitstudents', 'Make new students');
    }
}
$mform = new make_big_form();

$PAGE->set_heading('Make big forums');
$PAGE->set_title('Make big forums');
echo $OUTPUT->header();

// Standard moodleform if statement.
if ($mform->is_cancelled()) {

    // Don't think this will ever happen, but do nothing.

} else if ($fromform = $mform->get_data()) {

    if (isset($fromform->submitcreate)) {
        make_forums($fromform->course, $fromform->forums,
            $fromform->discussions, $fromform->posts, $fromform->readpercent,
            $fromform->readusers, $fromform->subscribepercent,
            $fromform->ratingpercent);
            // TODO Ratings aren't done yet!!
    } else if (isset($fromform->submitwipe)) {
        if (required_param('confirm', PARAM_ALPHA) != 'yes') {
            throw new moodle_exception('You didn\'t type yes to confirm the wipe.');
        }
        wipe_forums($fromform->course);
    } else if (isset($fromform->submitstudents)) {
        make_students($fromform->course, $fromform->students);
    }
}

print '<h3>Current counts</h3><ul>';
print '<li>Forums: <strong>' . $DB->count_records('forumng') . '</strong></li>';
print '<li>Discussions: <strong>' . $DB->count_records('forumng_discussions') . '</strong></li>';
print '<li>Posts: <strong>' . $DB->count_records('forumng_posts') . '</strong></li>';
print '<li>User/discussion read data: <strong>' .
        $DB->count_records('forumng_read') . '</strong></li>';
print '<li>User/forum subscriptions: <strong>' .
        $DB->count_records('forumng_subscriptions') . '</strong></li>';
print '<li>User/post ratings: <strong>' .
        $DB->count_records('forumng_ratings') . '</strong></li>';
print '</ul>';

function show_maximum_per($label, $of, $per, $url) {
    global $CFG, $DB;

    $results = $DB->get_records_sql("
SELECT $per AS field, COUNT(1) AS count
FROM {" . $of . "}
GROUP BY $per
ORDER BY 2 DESC", 0, 1);
    $result = $results ? reset($results)
            : (object)array('field'=>0, 'count'=>0);

    $displayurl = str_replace('$$', $result->field, $url);

    print "<li>$label: <strong>$result->count</strong>
            (<a href='$CFG->wwwroot$displayurl'>id=$result->field</a>)</li>";
}

print '<h3>Maximums</h3><ul>';
show_maximum_per('Forums per course', 'forumng', 'course', '/course/view.php?id=$$');
show_maximum_per('Discussions per forum', 'forumng_discussions', 'forumngid',
        '/mod/forumng/view.php?id=$$');
show_maximum_per('Subscriptions per forum', 'forumng_subscriptions',
        'forumngid', '/mod/forumng/view.php?id=$$');
show_maximum_per('Posts per discussion', 'forumng_posts', 'discussionid',
        '/mod/forumng/discuss.php?id=$$');
print '</ul>';


$mform->display();

print_footer();
