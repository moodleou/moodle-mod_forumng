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
 * Basic fulltext search using ousearch library.
 * @package mod
 * @subpackage forumng
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');
require_once('mod_forumng.php');

$cmid = required_param('id', PARAM_INT);
$querytext = required_param('query', PARAM_RAW);
$pageparams = array('id'=>$cmid, 'query'=>$querytext);
$cloneid = optional_param('clone', 0, PARAM_INT);
if ($cloneid) {
    $pageparams['clone'] = $cloneid;
}

$forum = mod_forumng::get_from_cmid($cmid, $cloneid);
$cm = $forum->get_course_module();
$course = $forum->get_course();
$groupid = mod_forumng::get_activity_group($cm, true);
$forum->require_view($groupid, 0, true);
mod_forumng::search_installed();

// If no search text has been entered, go straight to advanced search.
if (empty($querytext)) {
    redirect('advancedsearch.php?' . $forum->get_link_params(mod_forumng::PARAM_HTML) .
            '&amp;action=0');
}

// Search form for header
$buttontext = $forum->display_search_form($querytext);

// Display header
$PAGE->set_url(new moodle_url('/mod/forumng/search.php', $pageparams));
$PAGE->set_context($forum->get_context());
$PAGE->set_heading($course->fullname);
$PAGE->set_title($course->shortname . ': ' . format_string($forum->get_name()));
$PAGE->set_button($buttontext);
$PAGE->set_cm($cm, $course);
$PAGE->set_pagelayout('base');
$PAGE->navbar->add(get_string('searchfor', 'local_ousearch', $querytext));
$out = mod_forumng_utils::get_renderer();
print $out->header();

// Display group selector if required
groups_print_activity_menu($cm, $CFG->wwwroot . '/mod/forumng/search.php?' .
    $forum->get_link_params(mod_forumng::PARAM_HTML) . '&amp;query=' .
    rawurlencode($querytext));

$searchurl = 'search.php?' . $forum->get_link_params(mod_forumng::PARAM_PLAIN);
$query = new local_ousearch_search($querytext);
$query->set_coursemodule($forum->get_course_module(true));
if ($groupid && $groupid!=mod_forumng::NO_GROUPS) {
    $query->set_group_id($groupid);
}
print $query->display_results($searchurl);

// Print advanced search link.
$options = $forum->get_link_params(mod_forumng::PARAM_HTML);
$options .= '&amp;action=0';
$options .= ($querytext) ? '&amp;query=' . rawurlencode($querytext) : '';
$url = $CFG->wwwroot .'/mod/forumng/advancedsearch.php?' . $options;
$strlink = get_string('advancedsearch', 'forumng');
print "<div class='advanced-search-link'><a href=\"$url\">$strlink</a>";
// Add link to search the rest of this website if service available.
if (!empty($CFG->block_resources_search_baseurl)) {
    $params = array('course' => $course->id, 'query' => $querytext);
    $restofwebsiteurl = new moodle_url('/blocks/resources_search/search.php', $params);
    $strrestofwebsite = get_string('restofwebsite', 'local_ousearch');
    $altlink = html_writer::start_tag('div');
    $altlink .= html_writer::link($restofwebsiteurl, $strrestofwebsite);
    $altlink .= html_writer::end_tag('div');
    print $altlink;
}
print '</div>';

print $out->footer();
