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

class mod_forumng_managetags_form extends moodleform {

    public function definition() {
        $mform =& $this->_form;
        $tags = $this->_customdata['tags'];
        $groups = $this->_customdata['groups'];
        // Add tagging to discussions.
        $mform->addElement('header', '', get_string('editsettags', 'forumngfeature_edittags'));

        $i = 0;
        foreach ($groups as $group) {
            if ($group->id === 0) {
                $options = array('itemtype' => 'forumng', 'component' => 'mod_forumng');
            } else {
                $options = array('itemtype' => 'groups', 'component' => 'mod_forumng');
            }
            // Add 'Set' forumng wide and group named tags to discussion tagging dropdowns.
            $mform->addElement('tags', 'settags_' . $group->id, get_string('gettagsgrouplabel',
                    'forumngfeature_edittags', $group->name), $options);
            $mform->setType('settags_' . $group->id, PARAM_TAGLIST);
            $mform->setDefault('settags_' . $group->id, $tags[$i]);
            if ($group->id == 0) {
                $mform->addHelpButton('settags_' . $group->id, 'settags', 'forumng');
            } else {
                $mform->addHelpButton('settags_' . $group->id, 'setgrptags', 'forumngfeature_edittags');
            }

            $i++;
        }
        // Hidden fields.
        foreach ($this->_customdata['params'] as $param => $value) {
            $mform->addElement('hidden', $param, $value);
            $mform->setType($param, PARAM_INT);
        }

        // Add some buttons.
        $this->add_action_buttons();

    }

}
