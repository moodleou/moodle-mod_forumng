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
 * The mod_forumng mail sent ok event.
 * Uses: mail subscription, mail digest, individual user mail (default).
 * Set other['type'] to: 'sub' or 'digest'
 *
 * @package    mod_forumng
 * @copyright  2014 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_forumng\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The mod_forumng mail sent event class.
 *
 * @package    mod_forumng
 * @since      Moodle 2.7
 * @copyright  2014 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mail_sent extends \core\event\base {

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        if (isset($this->other['type'])) {
            // Subscription or digest.
            $name = $this->other['type'] == 'digest' ? 'digest' : 'subscription';
            return "The $name mails sent OK. {$this->other['count']}.";
        } else {
            // Individual mail.
            return "Forumng email with subject '{$this->other['subject']}' sent to user '$this->relateduserid'
                username:({$this->other['username']})";
        }
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event:mailsent', 'mod_forumng');
    }

    /**
     * Get URL related to the action
     *
     * @return \moodle_url
     */
    public function get_url() {
        switch ($this->contextlevel) {
            case CONTEXT_SYSTEM:
                return new \moodle_url('\admin\cron.php');
                break;
            case CONTEXT_MODULE:
                return new \moodle_url('\\mod\\forumng\\view.php?id=' . $this->contextinstanceid);
               break;
            case CONTEXT_COURSE:
                return new \moodle_url('\\mod\\forumng\\index.php?id=' . $this->contextinstanceid);
                break;
        }
    }

    /**
     * Return the legacy event log data.
     *
     * @return array|null
     */
    protected function get_legacy_logdata() {
        global $SITE;
        if (isset($this->other['type'])) {
            // Subscription or digest.
            $name = $this->other['type'] == 'digest' ? 'digest' : 'mail';
            $file = $this->contextlevel == CONTEXT_COURSE ? 'index' : 'view';
            return array($this->courseid, 'forumng', $name . ' ok', $file .'.php?id=' . $this->contextinstanceid,
                $this->other['count'], $this->contextinstanceid);
        } else {
            // Individual mail.
            return array($SITE->id, 'library', $name . ' ok', 'cron',
                'emailsent ' . $this->relateduserid . ' (' . $this->other['username'] . '): ' .
                    $this->other['subject']);
        }
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception
     * @return void
     */
    protected function validate_data() {
        parent::validate_data();

        if (!isset($this->other['type'])) {
            throw new \coding_exception('The \'type\' value must be set in other.');
        }
        if (!empty($this->other['type'])) {
            if (!isset($this->other['count'])) {
                throw new \coding_exception('The \'count\' value must be set in other.');
            }
        } else {
            if (!isset($this->relateduserid)) {
                throw new \coding_exception('The \'relateduserid\' must be set.');
            }
            if (!isset($this->other['username'])) {
                throw new \coding_exception('The \'username\' must be set in other.');
            }
            if (!isset($this->other['subject'])) {
                throw new \coding_exception('The \'subject\' must be set in other.');
            }
        }
    }

}
