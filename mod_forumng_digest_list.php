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

require_once(dirname(__FILE__) . '/mod_forumng.php');

/**
 * Manages a list (based on a database recordset, so not all stored in memory)
 * of posts which need to be included in digests sent to users.
 *
 * The list only includes posts which are due to be included in digests. The
 * same caveats apply as to mod_forumng_mail_list.
 * @package mod
 * @subpackage forumng
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_forumng_digest_list extends mod_forumng_mail_list {
    /** Config flag used to prevent sending mails twice */
    const PENDING_MARK_DIGESTED = 'pending_mark_digested';

    public function __construct($tracetimes) {
        parent::__construct($tracetimes);
    }

    protected function get_pending_flag_name() {
        return self::PENDING_MARK_DIGESTED;
    }

    protected function get_target_mail_state() {
        return mod_forumng::MAILSTATE_DIGESTED;
    }

    protected function get_safety_net($time) {
        // The digest safety net is 24 hours earlier because digest posts may
        // be delayed by 24 hours.
        return parent::get_safety_net($time) - 24 * 3600;
    }

    protected function get_query_where($time) {
        global $CFG;

        // In case cron has not run for a while
        $safetynet = $this->get_safety_net($time);

        global $CFG;
        $sql = "
WHERE
    -- Post must be waiting for digest
    fp.mailstate = " . mod_forumng::MAILSTATE_MAILED . "

    -- Don't mail out really old posts (unless they were previously hidden)
    AND (fp.created > ? OR fd.timestart > ?)

    -- Post and discussion must not have been deleted and we're only looking
    -- at original posts not edited old ones
    AND fp.deleted = 0
    AND fd.deleted = 0
    AND fp.oldversion = 0

    -- Context limitation
    AND x.contextlevel = 70";
        $params = array($safetynet, $safetynet);
        return array($sql, $params);
    }
}
