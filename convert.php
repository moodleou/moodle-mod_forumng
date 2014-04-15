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
 * Script to convert one or more standard forums to ForumNG, or else to offer
 * the UI for this.
 * @package mod
 * @subpackage forumng
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');
require_once('mod_forumng.php');
require_once($CFG->libdir.'/formslib.php');

class mod_forumng_convert_form extends moodleform {

    public function definition() {

        global $CFG, $USER;
        $mform = $this->_form;
        $course = $this->_customdata;

        // Query for supported forums.
        $forums = mod_forumng_utils::get_convertible_forums($course);

        $forumoptions = array();
        foreach ($forums as $forum) {
            $forumoptions[$forum->id] = $forum->name;
        }

        $mform->addElement('static', '', '', get_string('convert_info', 'forumng'));

        $select = $mform->addElement('select', 'forums', get_string('modulenameplural', 'forum'),
            $forumoptions);
        $select->setMultiple(true);

        $mform->addElement('checkbox', 'nodata', '', get_string('convert_nodata', 'forumng'));

        $mform->addElement('static', '', '', get_string('convert_warning', 'forumng'));
        $mform->addElement('checkbox', 'hide', '', get_string('convert_hide', 'forumng'));

        $this->add_action_buttons(true, get_string('convert_title', 'forumng'));

        $mform->addElement('hidden', 'course', $this->_customdata->id);
        $mform->setType('course', PARAM_INT);
    }
}

$courseid = required_param('course', PARAM_INT);
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
require_login($course);
$pagename = get_string('convert_title', 'forumng');
$PAGE->set_url(new moodle_url('/mod/forumng/convert.php'), array('course' => $courseid));
$PAGE->set_title($pagename);
$PAGE->navbar->add($pagename);
require_capability('moodle/course:manageactivities',
    context_course::instance($courseid));

$mform = new mod_forumng_convert_form('convert.php', $course);
if ($mform->is_cancelled()) {
    redirect($CFG->wwwroot . '/course/view.php?id=' . $courseid);
}
echo $OUTPUT->header();
if ($fromform = $mform->get_data()) {
    echo $OUTPUT->heading($pagename);
    if (empty($fromform->forums) || count($fromform->forums) == 0) {
        print '<p>' . get_string('convert_noneselected', 'forumng') . '</p>';
        echo $OUTPUT->continue_button($CFG->wwwroot . '/mod/forumng/convert.php?course=' . $course->id);
    } else {
        foreach ($fromform->forums as $forumid) {
            mod_forumng::create_from_old_forum($course, $forumid, true,
                optional_param('hide', 0, PARAM_INT) ? true : false,
                optional_param('nodata', 0, PARAM_INT) ? true : false);
        }
        echo $OUTPUT->continue_button($CFG->wwwroot . '/course/view.php?id=' . $course->id);
    }
} else {
    $mform->display();
}

// Display footer.
echo $OUTPUT->footer($course);
