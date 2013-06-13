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
 * This script handles actions from the single 'action form' form that is used
 * to handle some discussion actions (currently: ratings and flags).
 *
 * There is a single form because it is desirable to edit all ratings at once,
 * which means the form needs to encompass the whole page, and it is not
 * possible to nest forms inside each other.
 *
 * This form is used only for non-Javascript support. The supported actions
 * (ratings and flags) have their own scripts; this script decodes its
 * parameters and than requires the relevant script to use that.
 * @package mod
 * @subpackage forumng
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

/**
 * Checks whether a POST key matches a given action. If it matches, parameters
 * are extracted from the string and hacked into the POST parameters that will
 * be passed to the real script.
 * @param string $key POST parameter key
 * @param string $prefix Desired key name prefix
 */
function match_action($key, $prefix) {
    if (strpos($key, $prefix) !== 0) {
        return false;
    }

    $params = substr($key, strlen($prefix));
    $matches = array();
    while (preg_match('~^_([a-z]+)_([^_]+)(.*)$~', $params, $matches)) {
        $_POST[$matches[1]] = $matches[2];
        $params = $matches[3];
    }
    return true;
}

// Loop through all POST parameters looking for a valid action.
foreach ($_POST as $key => $value) {
    if (match_action($key, 'action_flag')) {
        require_once('flagpost.php');
        exit;
    }
    if (match_action($key, 'action_rate')) {
        require_once('rate.php');
        exit;
    }
}

// If no actions were found, print error.
print_error('unknownuseraction');
