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
 * A scheduled task for Forumng cron.
 *
 * @package mod_forumng
 * @copyright 2014 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_forumng\task;

class email_normal extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('forumngcrontaskemails', 'mod_forumng');
    }

    /**
     * Run forumng cron send forum emails normally.
     */
    public function execute() {
        global $CFG;
        // Duplicate of check in email_to_user.
        if (!empty($CFG->noemailever)) {
            mtrace("Not sending forum emails because all mail is disabled.");
            return;
        }
        require_once($CFG->dirroot . '/mod/forumng/mod_forumng_cron.php');
        \mod_forumng_cron::email_normal();
    }
}
