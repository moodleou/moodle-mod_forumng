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
 * Form for email forwarding.
 * @package forumngfeature
 * @subpackage edittags
 * @copyright 2014 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->libdir . '/formslib.php');

class mod_forumng_edittags_form extends moodleform {

    public function definition() {
        $mform =& $this->_form;

        // Header.
        $mform->addElement('header', '', get_string('editdiscussiontags', 'forumngfeature_edittags'));
        $tags = $this->_customdata['tags'];

        // Tag discussion.
        $tagselect = $mform->addElement('autocomplete', 'tags', get_string('discussiontags', 'forumng'),
                $this->_customdata['forumtags'], array('tags' => true, 'placeholder' => get_string('entertags', 'tag')));
        $tagselect->setMultiple(true);
        $mform->setType('tags', PARAM_TAGLIST);
        $mform->setDefault('tags', $tags);
        $mform->addHelpButton('tags', 'discussiontags', 'forumng');

        // Hidden fields.
        foreach ($this->_customdata['params'] as $param => $value) {
            $mform->addElement('hidden', $param, $value);
            $mform->setType($param, PARAM_INT);
        }

        // Add some buttons.
        $this->add_action_buttons();
    }

}
