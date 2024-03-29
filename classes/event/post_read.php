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
 * The mod_forumng post read event.
 *
 * @package    mod_forumng
 * @copyright  2019 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_forumng\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The mod_forumng post read event class.
 *
 * @package    mod_forumng
 * @since      Moodle 2.7
 * @copyright  2014 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class post_read extends \core\event\base {

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'forumng_posts';
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' marked as read post $this->objectid from discussion
         {$this->other['discussid']} in the forum with the course module id '$this->contextinstanceid'";
    }

    /**
     * Return localised event name.
     *
     * @return string
     * @throws \moodle_exception
     */
    public static function get_name() {
        return get_string('event:postread', 'mod_forumng');
    }

    /**
     * Get URL related to the action
     *
     * @return \moodle_url
     * @throws \moodle_exception
     */
    public function get_url() {
        return new \moodle_url('\\mod\\forumng\\' . $this->other['logurl']);
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception
     * @return void
     */
    protected function validate_data() {
        parent::validate_data();

        if (!isset($this->other['logurl'])) {
            throw new \coding_exception('The \'logurl\' value must be set in other.');
        }
        if (!isset($this->other['discussid'])) {
            throw new \coding_exception('The \'discussid\' value must be set in other.');
        }

        if ($this->contextlevel != CONTEXT_MODULE) {
            throw new \coding_exception('Context level must be CONTEXT_MODULE.');
        }
    }

}
