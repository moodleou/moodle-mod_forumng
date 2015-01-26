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
 * The forumngfeature_userposts view event.
 *
 * @package    mod_forumng
 * @copyright  2014 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace forumngfeature_edittags\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The forumngfeature_userposts view event class.
 *
 * @package    mod_forumng
 * @since      Moodle 2.7
 * @copyright  2014 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tags_edited extends \core\event\base {

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'forumng';
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' updated forumng with course module id '$this->contextinstanceid'
                set tags for: Activity level, Group {$this->other['groupid']}";
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event:editedtags', 'forumngfeature_edittags');
    }

    /**
     * Get URL related to the action
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/forumng/feature/edittags/' . $this->other['url']);
    }

    /**
     * Return the legacy event log data.
     *
     * @return array|null
     */
    protected function get_legacy_logdata() {
        $url = str_replace('/mod/forumng/feature/edittags/', '', $this->other['url']);
        return array($this->courseid, 'forumng', 'edit set tags', $url, $this->objectid, $this->contextinstanceid);
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception
     * @return void
     */
    protected function validate_data() {
        parent::validate_data();

        if (!isset($this->other['url'])) {
            throw new \coding_exception('The \'url\' value must be set in other.');
        }

        if ($this->contextlevel != CONTEXT_MODULE) {
            throw new \coding_exception('Context level must be CONTEXT_MODULE.');
        }
    }

}
