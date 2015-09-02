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
 * Form for forumng user preferences.
 *
 * @package    mod_forumng
 * @copyright  2015 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->libdir . '/formslib.php');

class user_edit_forumng_form extends moodleform {

    /**
     * Define the form.
     */
    public function definition () {
        global $CFG, $COURSE;

        $mform = $this->_form;

        $choices = array();
        $choices['0'] = get_string('emaildigestoff');
        $choices['1'] = get_string('emaildigestcomplete');
        $mform->addElement('select', 'maildigest', get_string('emaildigest'), $choices);
        $mform->setDefault('maildigest', $CFG->defaultpreference_maildigest);
        $mform->addHelpButton('maildigest', 'emaildigest');

        $choices = array();
        $choices['1'] = get_string('htmlformat');
        $choices['0'] = get_string('textformat');
        $mform->addElement('select', 'mailformat', get_string('emailformat'), $choices);
        $mform->setDefault('autosubscribe', $CFG->defaultpreference_mailformat);

        // Add some extra hidden fields.
        foreach ($this->_customdata as $name => $default) {
            $mform->addElement('hidden', $name);
            $mform->setType($name, PARAM_INT);
            if (!empty($default)) {
                $mform->setDefault($name, $default);
            }
        }

        $this->add_action_buttons(true, get_string('savechanges'));
    }
}
