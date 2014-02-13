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
 * Called via ajax when updating a usage element dynamically.
 * cmid (id param) must be sent.
 *
 * @package    mod
 * @subpackage forumng
 * @copyright  2014 The open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);
header('Content-Type: application/json');
require_once(dirname(__FILE__) . '/../../../../config.php');
require_once($CFG->dirroot . '/mod/forumng/feature/usage/locallib.php');
require_once($CFG->dirroot . '/mod/forumng/mod_forumng.php');

require_sesskey();
$type = required_param('name', PARAM_TEXT);

$func = "forumngfeature_usage_show_$type";

if (function_exists($func)) {
    echo json_encode(array('content' => $func($_GET)));
}
