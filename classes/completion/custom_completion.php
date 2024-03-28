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
declare(strict_types=1);

namespace mod_forumng\completion;

use core_completion\activity_custom_completion;

/**
 * Activity custom completion subclass for the data activity.
 *
 * Class for defining mod_forumng's custom completion rules and fetching the completion statuses
 * of the custom completion rules for a given data instance and a user.
 *
 * @package mod_forumng
 * @copyright 2021 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_completion extends activity_custom_completion {
    /**
     * @param string $rule
     * @return int
     * @throws \coding_exception
     */
    public function get_state(string $rule): int {
        // Use forum object to handle this request.
        $this->validate_rule($rule);
        $forum = \mod_forumng::get_from_cmid($this->cm->id, \mod_forumng::CLONE_DIRECT);
        $result = $forum->get_completion_state($this->userid, $rule);
        return $result ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
    }
    /**
     * Fetch the list of custom completion rules that this module defines.
     *
     * @return array
     */
    public static function get_defined_custom_rules(): array {
        return [
                'completionposts',
                'completiondiscussions',
                'completionreplies',
                'completionwordcountmin',
                'completionwordcountmax',
                'timetrackingfrom',
                'timetrackingto',
        ];
    }

    /**
     * Returns an associative array of the descriptions of custom completion rules.
     *
     * @return array
     */
    public function get_custom_rule_descriptions(): array {
        $completionposts = $this->cm->customdata->customcompletionrules['completionposts'] ?? 0;
        $completiondiscussions = $this->cm->customdata->customcompletionrules['completiondiscussions'] ?? 0;
        $completionreplies = $this->cm->customdata->customcompletionrules['completionreplies'] ?? 0;
        $completionwordcountmin = $this->cm->customdata->customcompletionrules['completionwordcountmin'] ?? 0;
        $completionwordcountmax = $this->cm->customdata->customcompletionrules['completionwordcountmax'] ?? 0;
        $timetrackingfrom = $this->cm->customdata->customcompletionrules['timetrackingfrom'] ?? 0;
        $timetrackingto = $this->cm->customdata->customcompletionrules['timetrackingto'] ?? 0;

        return [
            'completionposts' => get_string('completiondetail:posts', 'forumng', $completionposts),
            'completiondiscussions' => get_string('completiondetail:discussions', 'forumng', $completiondiscussions),
            'completionreplies' => get_string('completiondetail:replies', 'forumng', $completionreplies),
            'completionwordcountmin' => get_string('completiondetail:wordcountmin', 'forumng', $completionwordcountmin),
            'completionwordcountmax' => get_string('completiondetail:wordcountmax', 'forumng', $completionwordcountmax),
            'timetrackingfrom' =>
                    get_string('completiondetail:trackingfrom', 'forumng', date('m/d/Y h:i', (int)$timetrackingfrom)),
            'timetrackingto' =>
                    get_string('completiondetail:trackingto', 'forumng', date('m/d/Y h:i', (int)$timetrackingto)),
        ];
    }

    /**
     * Returns an array of all completion rules, in the order they should be displayed to users.
     *
     * @return array
     */
    public function get_sort_order(): array {
        return [
                'completionview',
                'completionposts',
                'completiondiscussions',
                'completionwordcountmin',
                'completionwordcountmax',
                'completionreplies',
                'completionusegrade',
                'timetrackingfrom',
                'timetrackingto',
        ];
    }
}
