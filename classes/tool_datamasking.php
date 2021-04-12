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

namespace mod_forumng;

/**
 * Implementation of data masking for this plugin.
 *
 * The corresponding test script tool_datamasking_test.php checks every masked field.
 *
 * @package mod_forumng
 * @copyright 2021 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_datamasking implements \tool_datamasking\plugin {

    public function build_plan(\tool_datamasking\plan $plan): void {
        $plan->table('forumng')->add(
                new \tool_datamasking\unique_email_mask('reportingemail'));

        $plan->table('forumng_posts')->add(new \tool_datamasking\similar_text_mask(
                'message', true, \tool_datamasking\similar_text_mask::MODEL_POST));
        $plan->table('forumng_posts')->add(new \tool_datamasking\similar_text_mask(
                'subject', false, \tool_datamasking\similar_text_mask::MODEL_SUBJECT));

        $plan->table('forumng_drafts')->add(new \tool_datamasking\similar_text_mask(
                'message', true, \tool_datamasking\similar_text_mask::MODEL_POST));
        $plan->table('forumng_drafts')->add(new \tool_datamasking\similar_text_mask(
                'subject', false, \tool_datamasking\similar_text_mask::MODEL_SUBJECT));

        $plan->table('files')->add(new \tool_datamasking\files_mask('mod_forumng', 'attachment'));
        $plan->table('files')->add(new \tool_datamasking\files_mask('mod_forumng', 'draft'));
        $plan->table('files')->add(new \tool_datamasking\files_mask('mod_forumng', 'draftmessage'));
        $plan->table('files')->add(new \tool_datamasking\files_mask('mod_forumng', 'message'));
    }
}
