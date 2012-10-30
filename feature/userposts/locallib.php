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
 * Local library file for forumng.  These are non-standard functions that are used
 * only by forumng.
 *
 * @package mod
 * @subpackage forumng
 * @copyright 2012 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/** Make sure this isn't being directly accessed */
defined('MOODLE_INTERNAL') || die();

// Include the files that are required by this module.
require_once($CFG->dirroot . '/mod/forumng/lib.php');

/**
 * Grades users from the list.php or user.php page.
 *
 * @param array $newgrades mixed optional array/object of grade(s);
 * @param object $cm course module object
 * @param mod_forumng $forumng Forum
 */
function forumngfeature_userposts_update_grades($newgrades, $cm, mod_forumng $forumng) {
    global $CFG, $SESSION;

    require_once($CFG->libdir.'/gradelib.php');
    $grades = grade_get_grades($forumng->get_course_id(), 'mod',
            'forumng', $forumng->get_id(), array_keys($newgrades));

    foreach ($grades->items[0]->grades as $key => $grade) {
        if (array_key_exists($key, $newgrades)) {
            if ($newgrades[$key] != $grade->grade) {
                if ($newgrades[$key] == -1) {
                    // No grade.
                    $grade->rawgrade = null;
                } else {
                    $grade->rawgrade = $newgrades[$key];
                }
                $grade->userid = $key;
                $forumng->cmidnumber = $cm->id;
                forumngfeature_userposts_grade_item_update($forumng, $grade);
            }
        }
    }

    // Add a message to display to the page.
    if (!isset($SESSION->forumnggradesupdated)) {
        $SESSION->forumnggradesupdated = get_string('gradesupdated', 'forumngfeature_userposts');
    }
}

/**
 * Update grade item for given forumng.
 *
 * @param mod_forumng $forumng Forum with extra cmidnumber.
 * @param mixed optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return int 0 if ok, error code otherwise
 */
function forumngfeature_userposts_grade_item_update(mod_forumng $forumng, $grades = null) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    $params = array('itemname' => $forumng->get_name());

    $gradingscale = $forumng->get_grading_scale();
    if ($gradingscale > 0) {
        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax']  = $gradingscale;
        $params['grademin']  = 0;
    } else if ($gradingscale < 0) {
        $params['gradetype'] = GRADE_TYPE_SCALE;
        $params['scaleid']   = -$gradingscale;
    } else {
        $params['gradetype'] = GRADE_TYPE_NONE;
    }

    if ($grades  === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }

    return grade_update('mod/forumng', $forumng->get_course_id(), 'mod',
            'forumng', $forumng->get_id(), 0, $grades, $params);
}

/**
 * Render single user's grading form.
 *
 * @param int $cmid course module id
 * @param mod_forumng $forumng Forum
 * @param object $user object
 * @param int $groupid id of group to which user belongs
 */
function forumngfeature_userposts_display_user_grade($cmid, mod_forumng $forumng, $user, $groupid) {
    global $CFG;

    require_once($CFG->libdir.'/gradelib.php');
    $grades = grade_get_grades($forumng->get_course_id(), 'mod', 'forumng', $forumng->get_id(), $user->id);

    if ($grades) {
        if (!isset($grades->items[0]->grades[$user->id]->grade)) {
            $user->grade = -1;
        } else {
            $user->grade = abs($grades->items[0]->grades[$user->id]->grade);
        }
        $grademenu = make_grades_menu($forumng->get_grading_scale());
        $grademenu[-1] = get_string('nograde');

        $formparams = array();
        $formparams['id'] = $cmid;
        $formparams['user'] = $user->id;
        $formparams['group'] = $groupid;
        $formaction = new moodle_url('/mod/forumng/feature/userposts/savegrades.php', $formparams);
        $mform = new MoodleQuickForm('savegrade', 'post', $formaction,
                '', array('class' => 'savegrade'));

        $mform->addElement('header', 'usergrade', get_string('usergrade', 'forumngfeature_userposts'));

        $mform->addElement('select', 'grade', get_string('grade'),  $grademenu);
        $mform->setDefault('grade', $user->grade);

        $mform->addElement('submit', 'savechanges', get_string('savechanges'));

        $mform->display();
    }
}

require_once($CFG->libdir . '/tablelib.php');
/**
 * Class forumng_participation_table
 * extends flexible_table to override download rules
 */
class forumng_participation_table extends flexible_table {

    /**
     * This function is not part of the public api.
     *
     * Overriding here to avoid downloading in unsupported formats
     */
    public function get_download_menu() {
        $exportclasses = array('csv' => get_string('downloadcsv', 'table'));
        return $exportclasses;
    }
}
