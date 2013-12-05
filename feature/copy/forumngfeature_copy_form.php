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
 * Copy form with warning.
 * @package forumngfeature
 * @subpackage copy
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->libdir.'/formslib.php');

class forumngfeature_copy_form extends moodleform {

    public function definition() {

        global $CFG;
        $mform =& $this->_form;

        $mform->addElement('static', 'whatever', '',
            get_string('copy_info', 'forumngfeature_copy').'<br />');

        $mform->addElement('checkbox', 'hidelater',
            '', get_string('hidelater', 'forumng'));

        $this->add_action_buttons(true, get_string('copy_begin', 'forumngfeature_copy'));

        // Hidden fields
        foreach ($this->_customdata as $param => $value) {
            $mform->addElement('hidden', $param, $value);
            $mform->setType($param, PARAM_INT);
        }
    }
}
