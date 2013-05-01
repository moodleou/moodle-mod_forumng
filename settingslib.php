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
 * Administration settings lib - lazy loading of option values.
 * @package mod
 * @subpackage forumng
 * @copyright 2013 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class forumng_admin_setting_configselect_subscription extends admin_setting_configselect {
    public function load_choices() {
        global $CFG;
        require_once($CFG->dirroot . '/mod/forumng/mod_forumng.php');
        $options = mod_forumng::get_subscription_options();
        $options[-1] = get_string('perforumoption', 'forumng');
        $this->choices = $options;
        return true;
    }
}

class forumng_admin_setting_configselect_feedtype extends admin_setting_configselect {
    public function load_choices() {
        global $CFG;
        require_once($CFG->dirroot . '/mod/forumng/mod_forumng.php');
        $options = mod_forumng::get_feedtype_options();
        $options[-1] = get_string('perforumoption', 'forumng');
        $this->choices = $options;
        return true;
    }
}

class forumng_admin_setting_configselect_feeditems extends admin_setting_configselect {
    public function load_choices() {
        global $CFG;
        require_once($CFG->dirroot . '/mod/forumng/mod_forumng.php');
        $options = mod_forumng::get_feeditems_options();
        $options[-1] = get_string('perforumoption', 'forumng');
        $this->choices = $options;
        return true;
    }
}
