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
 * Library for usage feature inc forms.
 * @package forumngfeature_usage
 * @copyright 2013 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

function forumngfeature_usage_show_mostreaders($params, $forum = null) {
    global $DB, $PAGE;
    $cloneid = empty($params['clone']) ? 0 : $params['clone'];
    if ($forum == null) {
        if (empty($params['id'])) {
            throw new moodle_exception('Missing forum id param');
        }
        $forum = mod_forumng::get_from_cmid($params['id'], $cloneid);
    }
    $groupwhere = '';
    $groupwhere2 = '';
    $groupparams = array();
    $groupid = 0;
    if (!empty($params['group']) && $params['group'] != mod_forumng::NO_GROUPS &&
            $params['group'] != mod_forumng::ALL_GROUPS) {
        $groupwhere = 'AND (fd.groupid = :groupid1 OR fd.groupid IS NULL)';
        $groupwhere2 = 'AND (fd.groupid = :groupid2 OR fd.groupid IS NULL)';
        $groupid = $params['group'];
        $groupparams = array('groupid1' => $groupid, 'groupid2' => $groupid);
    }
    if (has_capability('mod/forumng:viewreadinfo', $forum->get_context())) {
        if (!$PAGE->has_set_url()) {
            // Set context when called via ajax.
            $PAGE->set_context($forum->get_context());
        }
        $renderer = $PAGE->get_renderer('forumngfeature_usage');
        // Only include enrolled users.
        list($sql, $params) = get_enrolled_sql($forum->get_context(), '', $groupid, true);
        // View discussions read.
        $readers = $DB->get_recordset_sql($sql = "
                SELECT COUNT(fr.userid) AS count, fr.discussionid
                  FROM (
                       SELECT fd.id AS discussionid, fr.userid
                         FROM {forumng_discussions} fd
                         JOIN {forumng_read} fr ON fr.discussionid = fd.id
                        WHERE fd.forumngid = :forumid1 AND fd.deleted = 0
                              $groupwhere
                    UNION ALL
                       SELECT fd.id AS discussionid, frp.userid
                         FROM {forumng_discussions} fd
                         JOIN {forumng_posts} fp ON fp.discussionid = fd.id
                         JOIN {forumng_read_posts} frp ON frp.postid = fp.id
                        WHERE fd.forumngid = :forumid2 AND fp.deleted = 0
                              AND fp.oldversion = 0 AND fd.deleted = 0
                              $groupwhere2
                       ) fr
                 WHERE fr.userid IN ($sql)
              GROUP BY fr.discussionid
              ORDER BY count desc, fr.discussionid desc", array_merge(
                        array('forumid1' => $forum->get_id(), 'forumid2' => $forum->get_id()),
                        $groupparams, $params), 0, 5);
        $readerlist = array();
        foreach ($readers as $discuss) {
            $discussion = mod_forumng_discussion::get_from_id($discuss->discussionid, $cloneid);
            list($content, $user) = $renderer->render_usage_discussion_info($forum, $discussion);
            $readerlist[] = $renderer->render_usage_list_item($forum, $discuss->count, $user, $content);
        }
        return $renderer->render_usage_list($readerlist, 'mostreaders', false);
    }
}

class forumngfeature_usage_usagechartdate extends moodleform {
    public function definition() {
        global $COURSE;
        $mform =& $this->_form;
        $options = array(
                'startyear' => userdate($COURSE->startdate, '%Y'),
                'optional' => true,
                'stopyear' => date('Y'));
        $mform->addElement('date_selector', 'usagedatefrom', get_string('from'), $options);
        $mform->addElement('date_selector', 'usagedateto', get_string('to'), $options);
        foreach ($this->_customdata['params'] as $param => $val) {
            $mform->addElement('hidden', $param, $val);
            $mform->setType($param, PARAM_INT);
        }
        $this->add_action_buttons(false, get_string('usagechartdatesubmit', 'forumngfeature_usage'));
    }
}
