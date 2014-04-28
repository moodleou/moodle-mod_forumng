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
 * Search that lets you do full-text and/or author and date conditions.
 * @package mod
 * @subpackage forumng
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/formslib.php');
require_once('mod_forumng.php');
require_once('advancedsearchlib.php');

class advancedsearch_form extends moodleform {
    public function definition() {
        global $CFG;
        $mform =& $this->_form;

        $mform->addElement('header', 'heading', get_string('advancedsearch', 'forumng'));

        $mform->addElement('hidden', 'course', $this->_customdata['course']);
        $mform->setType('course', PARAM_INT);
        $mform->addElement('hidden', 'id', $this->_customdata['id']);
        $mform->setType('id', PARAM_INT);
        if (isset($this->_customdata['cloneid'])) {
            $mform->addElement('hidden', 'clone', $this->_customdata['cloneid']);
            $mform->setType('clone', PARAM_INT);
        }

        // Words to be searched.
        $mform->addElement('text', 'query', get_string('words', 'forumng'), 'size="40"');
        $mform->setType('query', PARAM_TEXT);

        // Author name or OUCU to be filtered.
        $mform->addElement('text', 'author', get_string('authorname', 'forumng'), 'size="40"');
        $mform->setType('author', PARAM_TEXT);

        // Posted as moderator.
        $mform->addElement('checkbox', 'asmoderator', get_string('postedasmoderator', 'forumng'));

        // Date range_from to be filtered
        $mform->addElement('date_time_selector', 'datefrom',
                get_string('daterangefrom', 'forumng'),
                array('optional' => true, 'startyear' => 2011, 'step' => 1,
                'defaulttime' => $this->_customdata['datefrom']));

        // Date range_to to be filtered
        $mform->addElement('date_time_selector', 'dateto',
                get_string('daterangeto', 'forumng'),
                array('optional'=>true, 'startyear' => 2011, 'step' => 1,
                'defaulttime' => $this->_customdata['dateto']));

        // Add help buttons
        $mform->addHelpButton('query', 'words', 'forumng');
        $mform->addHelpButton('author', 'authorname', 'forumng');
        $mform->addHelpButton('asmoderator', 'postedasmoderator', 'forumng');
        $mform->addHelpButton('datefrom', 'daterangefrom', 'forumng');

        // Add "Search all forums"/"Search this forum" and "Cancel" buttons
        if ($this->_customdata['course']) {
            $this->add_action_buttons(true, get_string('searchallforums', 'forumng'));
        } else {
            $this->add_action_buttons(true, get_string('searchthisforum', 'forumng'));
        }
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $datefrom = $data['datefrom'];
        $dateto = $data['dateto'];
        if (isset($data['asmoderator'])) {
            $asmoderator = $data['asmoderator'];
        } else {
            $asmoderator = 0;
        }
        if ($datefrom > time()) {
            $errors['datefrom'] = get_string('inappropriatedateortime', 'forumng');
        }
        if (($datefrom > $dateto) && $dateto) {
            $errors['dateto'] = get_string('daterangemismatch', 'forumng');
        }
        if ((($data['query'] == '') && ($data['author'] == '') && !$datefrom && !$dateto) && (!$asmoderator)) {
            $errors['query'] = get_string('nosearchcriteria', 'forumng');
        }
        return $errors;
    }
}
/*////////////////////////////////////////////////////////////////////////////*/

$url = new Moodle_url('/mod/forumng/advancedsearch.php');
$courseid = optional_param('course', 0,  PARAM_INT);
if ($courseid) {
    $url->param('course', $courseid);
    $cmid = 0;
} else {
    $cmid = required_param('id', PARAM_INT);
    $url->param('id', $cmid);
}
$query = trim(optional_param('query', '', PARAM_RAW));
if ($query !== '') {
    $url->param('query', $query);
}
$author = trim(optional_param('author', '', PARAM_RAW));
if ($author !== '') {
    $url->param('author', $author);
}
$cloneid = optional_param('clone', 0, PARAM_INT);
if ($cloneid) {
    // clone is used to mark shared forums
    $url->param('clone', $cloneid);
}

$asmoderator = optional_param('asmoderator', 0, PARAM_INT);
$url->param('asmoderator', $asmoderator);

$datefrom = optional_param_array('datefrom', 0, PARAM_INT);
if (!empty($datefrom)) {
    foreach ($datefrom as $key => $value) {
        $url->param('datefrom[' . $key . ']', $value);
    }
}
$dateto = optional_param_array('dateto', 0, PARAM_INT);
if (!empty($dateto)) {
    foreach ($dateto as $key => $value) {
        $url->param('dateto[' . $key . ']', $value);
    }
}
// The below are necessary to fool the form into thinking it was submitted again
// when further requests are made for multiple pages / changing group. This is
// kind of a horrible way to make the page but it means we can use get_data
// instead of manually interpreting date dropdowns (incorrectly).
$submitbutton = optional_param('submitbutton', '', PARAM_RAW);
if ($submitbutton) {
    $url->param('submitbutton', $submitbutton);
}
$sesskey = optional_param('sesskey', '', PARAM_RAW);
if ($sesskey) {
    $url->param('sesskey', $sesskey);
}
$form = optional_param('_qf__advancedsearch_form', 0, PARAM_INT);
if ($form) {
    $url->param('_qf__advancedsearch_form', $form);
}


// Search in a single forum
if ($cmid) {
    $forum = mod_forumng::get_from_cmid($cmid, $cloneid);
    $cm = $forum->get_course_module();
    $course = $forum->get_course();
    $forum->require_view(mod_forumng::NO_GROUPS, 0, true);
    mod_forumng::search_installed();
    $allforums = false;
}
if ($courseid) {
    $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
    require_login($course);
    $coursecontext = context_course::instance($courseid);
    mod_forumng::search_installed();
    $allforums = true;
}

// Set up page
$PAGE->set_url($url);
$PAGE->set_heading($course->fullname);
if ($allforums) {
    $PAGE->set_context($coursecontext);
    $PAGE->set_title($course->shortname . ': ' . get_string('searchallforums', 'forumng'));
} else {
    $PAGE->set_context($forum->get_context());
    $PAGE->set_cm($cm, $course);
    $PAGE->set_title($course->shortname . ': ' . format_string($forum->get_name()));
}
$PAGE->set_pagelayout('base');
$PAGE->navbar->add(get_string('advancedsearch', 'forumng'));

// Set up the form.
$now = date('Y-m-d');
$defaultdatefrom = empty($datefrom) ? date_parse($now . ' 0:0') : $datefrom;
$defaultdateto = empty($dateto) ? date_parse($now . ' 23:59') : $dateto;
if ($allforums) {
    $editform = new advancedsearch_form('advancedsearch.php',
            array('course'=> $courseid, 'id'=> $cmid, 'datefrom' => $defaultdatefrom,
            'dateto' => $defaultdateto), 'get');
} else {
    $editform = new advancedsearch_form('advancedsearch.php',
            array('course'=> $courseid, 'id'=> $cmid, 'cloneid' => $cloneid,
            'datefrom' => $defaultdatefrom, 'dateto' => $defaultdateto), 'get');
}
$inputdata = new stdClass;
$inputdata->query = $query;
$inputdata->author = $author;
$editform->set_data($inputdata);

if ($editform->is_cancelled()) {
    if (isset($forum) ) {
        $returnurl = $forum->get_url(mod_forumng::PARAM_PLAIN);
    } else {
        $returnurl = $CFG->wwwroot . '/course/view.php?id=' . $course->id;
    }
    redirect($returnurl, '', 0);
}

// Process form data.
$data = $editform->get_data();

// Display header
$out = mod_forumng_utils::get_renderer();
print $out->header();

$searchtitle = forumng_get_search_results_title($query, $data ? $data->author : '',
        $data ? $data->datefrom : 0, $data ? $data->dateto : 0);

if (!$allforums) {
    // Display group selector if required
    groups_print_activity_menu($cm, $url);
    $groupid = mod_forumng::get_activity_group($cm, true);
    $forum->require_view($groupid, 0, true);
    print '<br/><br/>';
}
$editform->display();

if ($data) {
    // Searching for free text with or without filtering author and date range.
    if ($query) {
        $result = new local_ousearch_search($query);
        if ($allforums) {
            // Search all forums.
            // NOTE: I think this code branch is no longer used as we removed
            // the 'all forums' facility to the resources_search block, but
            // perhaps it may be used in standard Moodle installs somehow.
            $result->set_plugin('mod/forumng');
            $result->set_course_id($courseid);
            $result->set_visible_modules_in_course($COURSE);

            // Restrict them to the groups they belong to.
            if (!isset($USER->groupmember[$courseid])) {
                $result->set_group_ids(array());
            } else {
                $result->set_group_ids($USER->groupmember[$courseid]);
            }
            // Add exceptions where they can see other groups.
            $result->set_group_exceptions(local_ousearch_search::get_group_exceptions($courseid));

            $result->set_user_id($USER->id);
        } else {
            // Search this forum.
            $result->set_coursemodule($forum->get_course_module(true));
            if ($groupid && $groupid!=mod_forumng::NO_GROUPS) {
                $result->set_group_id($groupid);
            }
        }
        // Pass necessary data to filter function using ugly global.
        global $forumngfilteroptions;
        $forumngfilteroptions = (object)array('author' => trim($data->author),
                'datefrom' => $data->datefrom, 'dateto' => $data->dateto,
                'asmoderator' => !empty($data->asmoderator));
        $result->set_filter('forumng_exclude_words_filter');
        print $result->display_results($url, $searchtitle);
    } else {
        // Searching without free text using author and/or date range.
        $page = optional_param('page', 0, PARAM_INT);
        $prevpage = $page-FORUMNG_SEARCH_RESULTSPERPAGE;
        $prevrange = ($page-FORUMNG_SEARCH_RESULTSPERPAGE+1) . ' - ' . $page;

        // Get result from database query.
        if ($allforums) {
            $results = forumng_get_results_for_all_forums($course, trim($data->author),
                    $data->datefrom, $data->dateto, $page, !empty($data->asmoderator));
        } else {
            $results = forumng_get_results_for_this_forum($forum, $groupid, trim($data->author),
                    $data->datefrom, $data->dateto, $page, !empty($data->asmoderator));
        }
        $nextpage = $page + FORUMNG_SEARCH_RESULTSPERPAGE;

        $linknext = null;
        $linkprev = null;

        if ($results->success) {
            if (($page-FORUMNG_SEARCH_RESULTSPERPAGE+1)>0) {
                $url->param('page', $prevpage);
                $linkprev = $url->out(false);
            }
            if ($results->numberofentries == FORUMNG_SEARCH_RESULTSPERPAGE) {
                $url->param('page', $nextpage);
                $linknext = $url->out(false);
            }
        }
        if ($results->done ===1) {
            if (($page-FORUMNG_SEARCH_RESULTSPERPAGE+1)>0) {
                $url->param('page', $prevpage);
                $linkprev = $url->out(false);
            }
        }
        print local_ousearch_search::format_results($results, $searchtitle, $page+1, $linkprev,
                        $prevrange, $linknext, $results->searchtime);
    }
}

print $out->footer();
