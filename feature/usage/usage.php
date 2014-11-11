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
 * This page give usage stats for the current forum (group).
 * Access to the whole page is controlled by capability.
 * Each item displayed may also check capabilities for display.
 * @package forumngfeature_usage
 * @copyright 2013 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../../../config.php');
require_once($CFG->dirroot . '/mod/forumng/mod_forumng.php');
require_once($CFG->dirroot. '/mod/forumng/feature/usage/locallib.php');

$cmid = required_param('id', PARAM_INT);
$cloneid = optional_param('clone', 0, PARAM_INT);
$ratings = optional_param('ratings', 0, PARAM_INT);
$pageparams = array('id' => $cmid);
if ($cloneid) {
    $pageparams['clone'] = $cloneid;
}
$forum = mod_forumng::get_from_cmid($cmid, $cloneid);
$course = $forum->get_course();
$cm = $forum->get_course_module();
$context = $forum->get_context();

$groupid = mod_forumng::get_activity_group($cm, true);
if ($groupid != mod_forumng::NO_GROUPS && $groupid != mod_forumng::ALL_GROUPS) {
    $pageparams['group'] = $groupid;
    $groupwhere = 'AND (fd.groupid = ? OR fd.groupid IS NULL)';
    $groupparams = array($groupid);
} else {
    $groupwhere = '';
    $groupparams = array();
}

$ajaxparams = $pageparams;

// Check access.
$forum->require_view($groupid);
require_capability('forumngfeature/usage:view', $forum->get_context());

// Print page header.
$thisurl = new moodle_url('/mod/forumng/feature/usage/usage.php', $pageparams);
$mainrenderer = $forum->init_page($thisurl, get_string('title', 'forumngfeature_usage'));
$renderer = $PAGE->get_renderer('forumngfeature_usage');
echo $OUTPUT->header();
// Display group selector if required.
$thisurl->remove_params('group');// Remove group param so not included in group selector.
groups_print_activity_menu($cm, $thisurl);
echo $OUTPUT->heading(get_string('title', 'forumngfeature_usage'));
// Contribution.
echo html_writer::start_div('forumng_usage_section');
echo $OUTPUT->heading(get_string('contribution', 'forumngfeature_usage'), 3, 'forumng_usage_sectitle');
// Get all user posts, discussions count (as used in participation screen).
$posts = $forum->get_all_user_post_counts($groupid, true);
$contribcount = 5;
// Sort by replies/discussions.
$mostposts = $mostdiscussions = $posts;
uasort($mostposts, function($a, $b) {
    return  $a->replies < $b->replies ? 1 : -1;
});
uasort($mostdiscussions, function($a, $b) {
    return  $a->discussions < $b->discussions ? 1 : -1;
});
$postkeys = array_keys($mostposts);
$discusskeys = array_keys($mostdiscussions);
echo html_writer::start_div('forumng_usage_contrib');
// Start posts/replies.
echo html_writer::start_div('forumng_usage_contrib_cont');
$toplist = array();
$totaltoshow = $contribcount > count($posts) ? count($posts) : $contribcount;
$userfields = user_picture::fields();
for ($a = 0; $a < $totaltoshow; $a++) {
    // Create list of most posts.
    if ($mostposts[$postkeys[$a]]->replies > 0) {
        if ($user = $DB->get_record('user', array('id' => $postkeys[$a]), $userfields)) {
            $toplist[] = $renderer->render_usage_list_item($forum,
                    $mostposts[$postkeys[$a]]->replies, $user,
                    html_writer::div($forum->display_user_link($user), 'fng_userlink'));
        }
    }
}
echo $renderer->render_usage_list($toplist, 'mostposts');
echo html_writer::end_div();
// End posts.
echo html_writer::start_div('forumng_usage_contrib_cont');
$toplist = array();
$totaltoshow = $contribcount > count($posts) ? count($posts) : $contribcount;
for ($a = 0; $a < $totaltoshow; $a++) {
    if ($mostdiscussions[$discusskeys[$a]]->discussions > 0) {
        // Add to list of most new discussions.
        if ($user = $DB->get_record('user', array('id' => $discusskeys[$a]), $userfields)) {
                $toplist[] = $renderer->render_usage_list_item($forum,
                        $mostdiscussions[$discusskeys[$a]]->discussions, $user,
                        html_writer::div($forum->display_user_link($user), 'fng_userlink'));
        }
    }
}
echo $renderer->render_usage_list($toplist, 'mostdiscussions');
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::div('', 'clearer');
flush();// In case any lengthy stats flush, so something is showing.
// End contribution stats, now show usage.
$usageoutput = '';
if (has_capability('forumngfeature/usage:viewusage', $forum->get_context())) {
    // Show post history.
    $dateform = new forumngfeature_usage_usagechartdate(null, array('params' => $pageparams));
    $starttime = 0;
    $endtime = time();
    if ($formdata = $dateform->get_data()) {
        if (!empty($formdata->usagedatefrom)) {
            $starttime = $formdata->usagedatefrom;
        }
        if (!empty($formdata->usagedateto)) {
            if (!empty($formdata->usagedatefrom) && $formdata->usagedatefrom > $formdata->usagedateto) {
                // Ensure date is after from date.
                $formdata->usagedateto = $formdata->usagedatefrom;
            }
            // Set end time to next day from that selected -1 second (end of same day).
            $endtime = strtotime('tomorrow', $formdata->usagedateto) - 1;
            $dateform->set_data($formdata);
        }
    }
    // Get all valid posts - note includes anonymous posts.
    $allposts = $DB->get_recordset_sql("
        SELECT fp.created FROM {forumng_posts} fp
    INNER JOIN {forumng_discussions} fd ON fd.id = fp.discussionid
         WHERE fd.forumngid = ?
           AND fp.deleted = 0
           AND fd.deleted = 0
           AND fp.oldversion = 0
           AND (fp.created >= ? AND fp.created <= ?)
        $groupwhere
      ORDER BY fp.created asc", array_merge(array($forum->get_id(), $starttime, $endtime), $groupparams));
    $days = array();
    if ($starttime == 0) {
        $starttime = $COURSE->startdate;// Earliest start time.
    }
    $startdate = new DateTime(gmdate('m/d/yy', $starttime));
    if (!isset($allposts->current()->created) || $allposts->current()->created > $starttime) {
        // Setup the start date so even if not a post in it (or no posts), it will display.
        $days[ltrim(userdate($starttime, get_string('strftimedate', 'langconfig')))] = 0;
    }
    foreach ($allposts as $post) {
        $date = ltrim(userdate($post->created, get_string('strftimedate', 'langconfig')));
        if (!isset($days[$date])) {
            $days[$date] = 1;
        } else {
            $days[$date]++;
        }
    }
    $endday = ltrim(userdate($endtime, get_string('strftimedate', 'langconfig')));
    $enddate = new DateTime(gmdate('m/d/yy', $endtime));
    if (!isset($days[$endday])) {
        // Make graph go up to end time if no posts that day.
        $days[$endday] = 0;
    }
    $allposts->close();
    // Setup YUI chart data. Gets passed to js.
    $data = array();
    $postcount = 0;
    $datelabel = get_string('usagechartday', 'forumngfeature_usage');
    $postslabel = get_string('usagechartposts', 'forumngfeature_usage');
    $totallabel = get_string('usagecharttotal', 'forumngfeature_usage');
    foreach ($days as $day => $count) {
        $postcount += $count;
        $data[] = (object) array(
                $datelabel => $day,
                $postslabel => $count,
                $totallabel => $postcount
                );
    }
    $axes = (object) array(
            $datelabel => (object) array(
                    'type' => 'time',
                    'keys' => array($datelabel),
                    'labelFormat' => get_string('strftimedate', 'langconfig'),
                    'position' => 'bottom',
                    ),
            $postslabel => (object) array(
                    'position' => 'left',
                    'keys' => array($postslabel),
                    'type' => 'numeric',
                    'title' => get_string('usagechartpostslabel', 'forumngfeature_usage'),
                    'minimum' => 0
                    ),
            $totallabel => (object) array(
                    'position' => 'right',
                    'keys' => array($totallabel),
                    'type' => 'numeric',
                    'title' => get_string('usagecharttotallabel', 'forumngfeature_usage'),
                    'minimum' => 0
                    )

            );
    $options = (object) array(
            'render' => '#usagechart',
            'categoryKey' => $datelabel,
            'categoryType' => 'time',
            'styles' => (object) array('axes' => (object) array($datelabel => (object) array('label' => (object) array('rotation' => -90)))),
            'type' => 'combo',
            'seriesCollection' => array(
                    (object) array(
                            'type' => 'column',
                            'yKey' => $postslabel,
                            )
                    )
            );
    // There are 11 day labels shown by default on chart - if less available update axis.
    $interval = $startdate->diff($enddate);
    $totaldays = $interval->days + 1;// Add 1 to day diff as we always show start and end days.
    if ($totaldays < 11) {
        // Show axis labels for each day.
        $options->styles->axes->$datelabel->majorUnit = new stdClass();
        $options->styles->axes->$datelabel->majorUnit->count = $totaldays;
    }
    if (count($data) > 1) {
        // Chart only works if more than 1 record.
        $PAGE->requires->yui_module('moodle-forumngfeature_usage-usagegraph',
                'M.mod_forumng.forumngfeature_usage_chart.output', array($data, $axes, $options));
    }

    $usageoutput .= html_writer::start_div('forumng_usage_usagechart');
    $help = $OUTPUT->help_icon('usagechartpoststot', 'forumngfeature_usage');
    $usageoutput .= $OUTPUT->heading(get_string('usagechartpoststotal', 'forumngfeature_usage',
            $postcount) . $help, 4);
    $usageoutput .= $dateform->render();
    // Accessible table of chart.
    $charttable = new html_table();
    $charttable->head = array($datelabel, $postslabel, $totallabel);
    $charttable->data = $data;
    $charttable->summary = get_string('usagechartpoststable', 'forumngfeature_usage');
    if (count($data) > 1) {
        // Show table hidden for screenreaders (also keyboard focus toggled by css).
        $usageoutput .= get_accesshide(html_writer::table($charttable), 'div', 'skip', 'tabindex = 0');
        $usageoutput .= html_writer::div('', 'forumng_usage_chart', array('id' => 'usagechart'));
    } else {
        // Show table instead of chart.
        $usageoutput .= html_writer::table($charttable);
    }
    $usageoutput .= html_writer::end_div();
}
if ($forum->can_view_subscribers()) {
    // View subscriber info.
    $subs = $forum->get_subscribers($groupid);
    $discussioncount = 0;
    $groupcount = 0;
    $wholecount = 0;
    foreach ($subs as $subscriber) {
        if (!empty($subscriber->wholeforum)) {
            $wholecount++;
        }
        if (!empty($subscriber->discussionids)) {
            $discussioncount += count($subscriber->discussionids);
        }
        if (!empty($subscriber->groupids)) {
            $groupcount += count($subscriber->groupids);
        }
    }
    $usageoutput .= html_writer::start_div('forumng_usage_subscribers');
    $help = $OUTPUT->help_icon('usagesubscribers', 'forumngfeature_usage');
    $usageoutput .= $OUTPUT->heading(get_string('usagesubscribers', 'forumngfeature_usage') . $help, 4);
    $subtable = new html_table();
    $subtable->summary = get_string('usagesubscribers', 'forumngfeature_usage');
    $subtable->head = array(get_string('usagesubscribertabletype', 'forumngfeature_usage'),
            get_string('usagesubscribertabletotal', 'forumngfeature_usage'));
    $subtable->data = array(
            array(get_string('usagesubscribertable_all', 'forumngfeature_usage'), count($subs)),
            array(get_string('usagesubscribertable_whole', 'forumngfeature_usage'), $wholecount),
            array(get_string('usagesubscribertable_group', 'forumngfeature_usage'), $groupcount),
            array(get_string('usagesubscribertable_discuss', 'forumngfeature_usage'), $discussioncount));
    $usageoutput .= html_writer::table($subtable);
    $usageoutput .= html_writer::end_div();
}
if (has_capability('mod/forumng:viewreadinfo', $forum->get_context())) {
    $usageoutput .= html_writer::start_div('forumng_usage_readers');
    $usageoutput .= $renderer->render_usage_list_heading('mostreaders');
    $usageoutput .= $renderer->render_usage_dynamicarea('mostreaders', $forum, $ajaxparams);
    $usageoutput .= html_writer::end_div();
}
if (has_capability('forumngfeature/usage:viewflagged', $forum->get_context())) {
    // View posts that have been flagged.
    $flagged = $DB->get_recordset_sql("
            SELECT COUNT(ff.id) AS count, fp.id
              FROM {forumng_flags} ff
        INNER JOIN {forumng_posts} fp ON fp.id = ff.postid
        INNER JOIN {forumng_discussions} fd ON fd.id = fp.discussionid
             WHERE fd.forumngid = ?
               AND fd.deleted = 0
               AND fp.deleted = 0
               AND fp.oldversion = 0
            $groupwhere
          GROUP BY fp.id
          ORDER BY count desc, fp.id desc", array_merge(array($forum->get_id()), $groupparams), 0, 5);
    $flaggedlist = array();
    foreach ($flagged as $apost) {
        $post = mod_forumng_post::get_from_id($apost->id, $cloneid, true, true);
        list($content, $user) = $renderer->render_usage_post_info($forum, $post->get_discussion(), $post);
        $flaggedlist[] = $renderer->render_usage_list_item($forum, $apost->count, $user, $content);
    }
    $usageoutput .= html_writer::start_div('forumng_usage_flagged');
    $usageoutput .= $renderer->render_usage_list($flaggedlist, 'mostflagged');
    $usageoutput .= html_writer::end_div();
    // View discussions that have been flagged.
    $flagged = $DB->get_recordset_sql("
            SELECT COUNT(ff.id) AS count, fd.id
            FROM {forumng_flags} ff
            INNER JOIN {forumng_discussions} fd ON fd.id = ff.discussionid
            WHERE fd.forumngid = ?
            AND fd.deleted = 0
            $groupwhere
            GROUP BY fd.id
            ORDER BY count desc, fd.id desc", array_merge(array($forum->get_id()), $groupparams), 0, 5);
    $flaggedlist = array();
    foreach ($flagged as $adiscuss) {
        $discuss = mod_forumng_discussion::get_from_id($adiscuss->id, $cloneid, 0, true);
        list($content, $user) = $renderer->render_usage_discussion_info($forum, $discuss);
        $flaggedlist[] = $renderer->render_usage_list_item($forum, $adiscuss->count, $user, $content);
    }
    $usageoutput .= html_writer::start_div('forumng_usage_flagged');
    $usageoutput .= $renderer->render_usage_list($flaggedlist, 'mostflaggeddiscussions');
    $usageoutput .= html_writer::end_div();
}

// Show ratings.
if (has_capability('mod/forumng:viewanyrating', $forum->get_context())) {
    $gradingstr = '';
    $ratingtype = $forum->get_enableratings();
    if ($ratingtype && $forum->get_rating_scale() != 0) {
        // Get grading type from forum.
        $gradingtype = $forum->get_grading();
        $counttype = '';
        if (($gradingtype == mod_forumng::GRADING_NONE) || ($gradingtype == mod_forumng::GRADING_MANUAL)) {
            // If ratings (grading type) not set get default display grading type depending upon rating scale type.
            if (!$ratings) {
                $scaletype = $forum->get_rating_scale();
                if ($scaletype > 0) {
                    $gradingtype = mod_forumng::GRADING_AVERAGE;
                } else if ($scaletype < 0) {
                    $gradingtype = mod_forumng::GRADING_COUNT;
                }
            }
        }

        if ($ratings) {
            $gradingtype = $ratings;
        }

        $orderby = ' rawgrade DESC';
        // Build up sql.
        switch ($gradingtype) {
            case mod_forumng::GRADING_AVERAGE:
                // Grading: Average of ratings.
                $counttype = ' AVG(r.rating) AS rawgrade';
                $gradingstr = 'forumng_ratings_grading_average';
                break;

            case mod_forumng::GRADING_COUNT:
                // Grading: Count of ratings.
                $counttype = ' COUNT(r.rating) AS rawgrade';
                $gradingstr = 'forumng_ratings_grading_count';
                break;

            case mod_forumng::GRADING_MAX:
                // Grading: Max rating.
                $counttype = ' MAX(r.rating) AS rawgrade';
                $gradingstr = 'forumng_ratings_grading_max';
                break;

            case mod_forumng::GRADING_MIN:
                // Grading: Min rating.
                $counttype = ' MIN(r.rating) AS rawgrade';
                $gradingstr = 'forumng_ratings_grading_min';
                break;

            case mod_forumng::GRADING_SUM:
                // Grading: Sum of ratings.
                $counttype = ' SUM(r.rating) as rawgrade';
                $gradingstr = 'forumng_ratings_grading_sum';
                break;
        }

        $ratingslist = array();
        $conditionsparams = array($forum->get_id());
        $conditions = '  fd.forumngid = ?';
        $havingparams = array();
        if ($ratingtype == mod_forumng::FORUMNG_STANDARD_RATING ) {
            // Moodle ratings.
            $postid = ' r.itemid AS postid ';
            $from = ' {rating} r ';
            $postjoin = 'INNER JOIN {forumng_posts} fp ON r.itemid = fp.id';
            $conditions .= ' AND r.component = \'mod_forumng\'';
            $conditions .= ' AND r.contextid = ?';
            $conditionsparams[] = $forum->get_context()->id;
            $groupby = ' GROUP BY r.itemid ';
            $having = '';
        } else {
            // Old forumng ratings (obsolete).
            $postid = ' r.postid AS postid ';
            $from = ' {forumng_ratings} r';
            $postjoin = 'INNER JOIN {forumng_posts} fp ON r.postid = fp.id';
            $having = 'HAVING COUNT(r.rating) >= ?';
            $havingparams[] = $forum->get_rating_threshold();
            $groupby = ' GROUP BY r.postid';
        }

        $conditionsparams = array_merge($conditionsparams, $groupparams, $havingparams);

        $ratingsl = $DB->get_recordset_sql("SELECT $counttype, $postid
                FROM $from
                $postjoin
                INNER JOIN  {forumng_discussions} fd ON fp.discussionid = fd.id
                INNER JOIN  {forumng} f ON f.id = fd.forumngid
                WHERE $conditions
                AND fd.deleted = 0
                AND fp.deleted = 0
                AND fp.oldversion = 0
                $groupwhere
                $groupby
                $having
                ORDER BY rawgrade DESC
                ", $conditionsparams, 0, 5);

        // Get the ratings.
        foreach ($ratingsl as $apost) {
            if ($gradingtype == mod_forumng::GRADING_AVERAGE) {
                $apost->rawgrade = round($apost->rawgrade, 2);
            }
            $post = mod_forumng_post::get_from_id($apost->postid, $cloneid, true, true);
            list($content, $user) = $renderer->render_usage_post_info($forum, $post->get_discussion(), $post);
            $ratingslist[] = $renderer->render_usage_list_item($forum, $apost->rawgrade, $user, $content);
        }

        // Print out ratings usage.
        $usageoutput .= $renderer->render_usage_ratings($ratingslist, $forum, $gradingstr, $gradingtype);
    }
}

if (!empty($usageoutput)) {
    echo html_writer::start_div('forumng_usage_section');
    echo $OUTPUT->heading(get_string('usage', 'forumngfeature_usage'), 4, 'forumng_usage_sectitle');
    echo $usageoutput;
    echo html_writer::start_div('clearer') . html_writer::end_div();
    echo html_writer::end_div();
}
echo $OUTPUT->footer();
// Log usage view.
$params = array(
    'context' => $forum->get_context(),
    'objectid' => $forum->get_id(),
    'other' => array('url' => $thisurl->out_as_local_url())
);

$event = \forumngfeature_usage\event\usage_viewed::create($params);
$event->add_record_snapshot('course_modules', $forum->get_course_module());
$event->add_record_snapshot('course', $forum->get_course());
$event->trigger();
