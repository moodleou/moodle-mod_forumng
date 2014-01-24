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
 * Library for usage feature inc forms.
 * @package forumngfeature_usage
 * @copyright 2013 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class forumngfeature_usage_usagechartdate extends moodleform {
    public function definition() {
        global $COURSE;
        $mform =& $this->_form;
        $options = array(
                'startyear' => userdate($COURSE->startdate, '%Y'),
                'optional' => true,
                'stopyear' => date('Y'));
        $mform->addElement('date_selector', 'usagedatefrom', get_string('from'), $options);
        $mform->addElement('date_selector', 'usagedateto', get_string('to'), $options);
        foreach ($this->_customdata['params'] as $param => $val) {
            $mform->addElement('hidden', $param, $val);
            $mform->setType($param, PARAM_INT);
        }
        $this->add_action_buttons(false, get_string('usagechartdatesubmit', 'forumngfeature_usage'));
    }
}
