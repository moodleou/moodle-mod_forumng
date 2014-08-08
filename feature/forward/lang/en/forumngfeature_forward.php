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
 * Lang strings.
 * @package forumngfeature
 * @subpackage forward
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$string['forward'] = 'Forward by email';
$string['forward_intro'] = 'Optional introduction';
$string['forward_email'] = 'Email address of recipient';
$string['forward_ccme'] = 'Copy to your own email';
$string['forward_preface'] = '<p>These forum posts were sent to you on behalf of {$a->name}.</p>';
$string['forward_done'] = 'The email has been sent.';
$string['forward_info_selected'] = 'The selected posts (shown below this form) will be emailed to the address or addresses you enter here. The email will be sent under your name <strong>{$a->fullname}</strong> from your email address <strong>{$a->email}</strong>.';
$string['forward_info_all'] = 'This discussion will be emailed to the address or addresses you enter here. The email will be sent under your name <strong>{$a->fullname}</strong> from your email address <strong>{$a->email}</strong>.';
$string['forward_email_help'] = 'Enter one or more email address(es) separated by spaces or semicolons.';
$string['pluginname'] = 'Forward posts by email';
$string['event:discussionforwarded'] = 'Forward discussion';
