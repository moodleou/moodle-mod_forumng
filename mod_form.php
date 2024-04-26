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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/forumng/mod_forumng.php');
require_once($CFG->dirroot.'/mod/forumng/mod_forumng_utils.php');

/**
 * Form for editing module settings.
 * @package mod
 * @subpackage forumng
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_forumng_mod_form extends moodleform_mod {

    private $clone;

    public function definition() {

        global $CFG, $COURSE, $DB;
        $mform = $this->_form;
        $coursecontext = context_course::instance($COURSE->id);
        $forumng = $this->_instance ? $DB->get_record('forumng', array('id' => $this->_instance)) : null;
        $this->clone = $forumng ? $forumng->originalcmid : 0;

        // If this is a clone, don't show the normal form.
        if ($this->clone) {
            $mform->addElement('hidden', 'name', $forumng->name);
            if (!empty($CFG->formatstringstriptags)) {
                $mform->setType('name', PARAM_TEXT);
            } else {
                $mform->setType('name', PARAM_NOTAGS);
            }
            $mform->addElement('static', 'sharedthing', '', get_string(
                    'sharedinfo', 'forumng',
                    $CFG->wwwroot . '/course/modedit.php?update=' .
                    $this->clone . '&amp;return=1'));
            $this->shared_definition_part($coursecontext);
            return;
        }

        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Forum name.
        $mform->addElement('text', 'name', get_string('forumname', 'forumng'),
                array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_NOTAGS);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        // Forum types.
        $types = forumngtype::get_all();
        $options = array();
        foreach ($types as $type) {
            if ($type->is_user_selectable()) {
                $options[$type->get_id()] = $type->get_name();
            }
        }
        $mform->addElement('select', 'type', get_string('forumtype', 'forumng'), $options);
        $mform->addHelpButton('type', 'forumtype', 'forumng');
        $mform->setDefault('type', 'general');

        $this->standard_intro_elements(get_string('forumdescription', 'forumng'));

        $mform->addElement('editor', 'introductioneditor',
                get_string('forumintro', 'forumng'), array('rows' => 15),
                array('maxfiles' => EDITOR_UNLIMITED_FILES, 'noclean' => true, 'context' => $this->context, 'subdirs' => true));
        $mform->setType('introductioneditor', PARAM_RAW); // No XSS prevention here, users must be trusted.

        // Subscription option displays only if enabled at site level.
        if ($CFG->forumng_subscription == -1) {
            $options = mod_forumng::get_subscription_options();
            $mform->addElement('select', 'subscription',
                get_string('subscription', 'forumng'), $options);
            $mform->setDefault('subscription', mod_forumng::SUBSCRIPTION_PERMITTED);
            $mform->addHelpButton('subscription', 'subscription', 'forumng');
        } else {
            // Hidden element contains default value (not used anyhow).
            $mform->addElement('hidden', 'subscription',
                mod_forumng::SUBSCRIPTION_PERMITTED);
            $mform->setType('subscription', PARAM_INT);
        }

        // Max size of attachments.
        $choices = get_max_upload_sizes($CFG->maxbytes, $COURSE->maxbytes);
        $choices[-1] = get_string('uploadnotallowed');
        $mform->addElement('select', 'attachmentmaxbytes',
            get_string('attachmentmaxbytes', 'forumng'), $choices);
        $mform->addHelpButton('attachmentmaxbytes', 'attachmentmaxbytes', 'forumng');
        $mform->setDefault('attachmentmaxbytes', $CFG->forumng_attachmentmaxbytes);

        // Email address for reporting unacceptable post for this forum, default is blank.
        $mform->addElement('text', 'reportingemail', get_string('reportingemail', 'forumng'),
            array('size' => 64));
        $mform->setType('reportingemail', PARAM_NOTAGS);
        $mform->addRule('reportingemail',
                get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('reportingemail', 'reportingemail', 'forumng');
        $mform->addElement('select', 'canpostanon', get_string('canpostanon', 'forumng'), mod_forumng::get_canpostanon_options());
        $mform->addHelpButton('canpostanon', 'canpostanon', 'forumng');
        // Atom/RSS feed on/off/discussions-only.
        if ($CFG->enablerssfeeds && !empty($CFG->forumng_enablerssfeeds)) {
            if ($CFG->forumng_feedtype == -1 || $CFG->forumng_feeditems == -1) {
                $mform->addElement('header', 'feeds', get_string('feeds', 'forumng'));
            }

            if ($CFG->forumng_feedtype == -1) {
                $mform->addElement('select', 'feedtype',
                    get_string('feedtype', 'forumng'), mod_forumng::get_feedtype_options());
                $mform->setDefault('feedtype', mod_forumng::FEEDTYPE_ALL_POSTS);
                $mform->addHelpButton('feedtype', 'feedtype', 'forumng');
            }

            // Atom/RSS feed item count.
            if ($CFG->forumng_feeditems == -1) {
                $mform->addElement('select', 'feeditems',
                    get_string('feeditems', 'forumng'), mod_forumng::get_feeditems_options());
                $mform->setDefault('feeditems', 20);
                $mform->addHelpButton('feeditems', 'feeditems', 'forumng');
            }
        }

        // Add tagging to discussions.
        if ($CFG->usetags && core_tag_tag::is_enabled('mod_forumng', 'forumng')) {
            $mform->addElement('header', 'tagging', get_string('tagging', 'forumng'));
            // Enable tagging.
            $mform->addElement('checkbox', 'enabletags', get_string('enabletagging', 'forumng'));
            $mform->addHelpButton('enabletags', 'tagging', 'forumng');
            // Add 'Set' forumng wide named tags to discussion tagging dropdowns.
            $settags = null;
            if ($forumng) {
                $settags = mod_forumng::get_set_tags($this->_instance);
                // Create 'Set' forumng wide tags.
                $mform->addElement('tags', 'settags', get_string('setforumtags', 'forumng'),
                        array('itemtype' => 'forumng', 'component' => 'mod_forumng'));
                $mform->disabledIf('settags', 'enabletags', 'notchecked');
                $mform->setType('settags', PARAM_TAGLIST);
                $mform->setDefault('settags', $settags);
                $mform->addHelpButton('settags', 'settags', 'forumng');
            }
        }

        // Ratings header.
        $mform->addElement('header', 'ratings', get_string('ratings', 'forumng'));

        $options = array(mod_forumng::FORUMNG_NO_RATING => get_string('noratings', 'forumng'),
                        mod_forumng::FORUMNG_RATING_OBSOLETE => get_string('forumngratingsobsolete', 'forumng'),
                        mod_forumng::FORUMNG_STANDARD_RATING => get_string('standardratings', 'forumng'));
        $mform->addElement('select', 'enableratings', get_string('enableratings', 'forumng'), $options);
        $mform->addHelpButton('enableratings', 'enableratings', 'forumng');

        // Scale.
        $mform->addElement('modgrade', 'ratingscale', get_string('scale'), null, true);
        $mform->disabledIf('ratingscale', 'enableratings', 'eq', 0);
        $mform->setDefault('ratingscale', 5);

        // From/until times.
        $mform->addElement('date_time_selector', 'ratingfrom',
                get_string('ratingfrom', 'forumng'), array('optional' => true));
        $mform->disabledIf('ratingfrom', 'enableratings', 'eq', 0);

        $mform->addElement('date_time_selector', 'ratinguntil',
                get_string('ratinguntil', 'forumng'), array('optional' => true));
        $mform->disabledIf('ratinguntil', 'enableratings', 'eq', 0);

        $mform->addElement('text', 'ratingthreshold',
            get_string('ratingthreshold', 'forumng'));
        $mform->setType('ratingthreshold', PARAM_INT);
        $mform->setDefault('ratingthreshold', 1);
        $mform->addRule('ratingthreshold',
            get_string('error_ratingthreshold', 'forumng'),
            'regex', '/[1-9][0-9]*/', 'client');
        $mform->addHelpButton('ratingthreshold', 'ratingthreshold', 'forumng');
        $mform->disabledIf('ratingthreshold', 'enableratings', 'neq', mod_forumng::FORUMNG_RATING_OBSOLETE);

        // Grading.
        $mform->addElement('header', 'grade', get_string('grading', 'forumng'));

        $mform->addElement('select', 'grading', get_string('gradenoun'),
            mod_forumng::get_grading_options());
        $mform->setDefault('grading', mod_forumng::GRADING_NONE);
        $mform->addHelpButton('grading', 'grading', 'forumng');

        $mform->addElement('modgrade', 'gradingscale', get_string('gradingscale', 'forumng'));
        $mform->disabledIf('gradingscale', 'grading', 'ne', mod_forumng::GRADING_MANUAL);
        $mform->setDefault('gradingscale', 5);

        // Blocking header.
        $mform->addElement('header', 'limitposts', get_string('limitposts', 'forumng'));

        // Post dates.
        $mform->addElement('date_time_selector', 'postingfrom',
                get_string('postingfrom', 'forumng'), array('optional' => true));
        $mform->addElement('date_time_selector', 'postinguntil',
                get_string('postinguntil', 'forumng'), array('optional' => true));

        // User limits.
        $limitgroup = array();
        $limitgroup[] = $mform->createElement(
            'checkbox', 'enablelimit', '');

        $options = mod_forumng::get_max_posts_period_options();

        $limitgroup[] = $mform->createElement('text', 'maxpostsblock',
            '', array('size' => 3));
        $limitgroup[] = $mform->createElement('static', 'staticthing', '',
            ' ' . get_string('postsper', 'forumng') . ' ');
        $limitgroup[] = $mform->createElement('select', 'maxpostsperiod',
            '', $options);

        $mform->addGroup($limitgroup, 'limitgroup',
            get_string('enablelimit', 'forumng'));

        $mform->disabledIf('limitgroup[maxpostsblock]', 'limitgroup[enablelimit]');
        $mform->disabledIf('limitgroup[maxpostsperiod]', 'limitgroup[enablelimit]');

        $mform->addHelpButton('limitgroup', 'enablelimit', 'forumng');

        $mform->setType('limitgroup[maxpostsblock]', PARAM_INT);
        $mform->setDefault('limitgroup[maxpostsblock]', '10');

        // Remove old discussion.
        $options = mod_forumng_utils::create_remove_options();
        $mform->addElement('header', 'manageolddiscussions', get_string('removeolddiscussions', 'forumng'));
        $mform->addElement('select', 'removeafter',
                get_string('removeolddiscussionsafter', 'forumng'), $options);
        $mform->addHelpButton('removeafter', 'removeolddiscussions', 'forumng');
        // Just set default value when creating a new forum.
        if (!$forumng) {
            $mform->setDefault('removeafter', $CFG->forumng_removeolddiscussions);
        }

        $options = mod_forumng_utils::create_action_options();
        $modinfo = get_fast_modinfo($COURSE);
        $targetforumngid = $this->_instance ? $this->_instance : 0;
        // Add all instances to drop down if the user can access them and
        // it's not the same as the current forum.
        if (array_key_exists('forumng', $modinfo->instances)) {
            foreach ($modinfo->instances['forumng'] as $info) {
                if ($info->uservisible && $targetforumngid != $info->instance) {
                    $options[$info->instance] = $info->name;
                }
            }
        }
        $mform->addElement('select', 'removeto',
                get_string('withremoveddiscussions', 'forumng'), $options);
        $mform->disabledIf('removeto', 'removeafter', 'eq', 0);
        $mform->addHelpButton('removeto', 'withremoveddiscussions', 'forumng');
        // Just set default value when creating a new forum.
        if (!$forumng) {
            $mform->setDefault('removeto', $CFG->forumng_withremoveddiscussions);
        }

        // Sharing options are advanced and for administrators only.
        if ($CFG->forumng_enableadvanced && has_capability('mod/forumng:addinstance', context_system::instance())) {
            $mform->addElement('header', 'sharing', get_string('sharing', 'forumng'));
            $mform->addElement('advcheckbox', 'shared', get_string('shared', 'forumng'));
            $mform->addHelpButton('shared', 'shared', 'forumng');

            // Only when creating a forum, you can choose to make it a clone.
            if (!$this->_instance) {
                $sharegroup = array();
                $sharegroup[] = $mform->createElement('checkbox', 'useshared', '');
                $sharegroup[] = $mform->createElement('text', 'originalcmidnumber', '');
                $mform->setType('usesharedgroup[originalcmidnumber]', PARAM_RAW);
                $mform->addGroup($sharegroup, 'usesharedgroup',
                        get_string('useshared', 'forumng'));
                $mform->disabledIf('usesharedgroup[originalcmidnumber]',
                        'usesharedgroup[useshared]', 'notchecked');
                $mform->addHelpButton('usesharedgroup', 'useshared', 'forumng');
            }
        }

        // Do definition that is shared with clone version of form.
        $this->shared_definition_part($coursecontext);

        if (count(mod_forumng_utils::get_convertible_forums($COURSE)) > 0 && !$this->_instance) {
            $mform->addElement('static', '', '', '<div class="forumng-convertoffer">' .
                get_string('offerconvert', 'forumng', $CFG->wwwroot .
                '/mod/forumng/convert.php?course=' . $COURSE->id) . '</div>');
        }
    }

    private function shared_definition_part($coursecontext) {
        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    private function validate_emails($emails) {
        // Loop through string looking for ';' as seperators.
        $emailarray = explode(';' , $emails);
        foreach ($emailarray as $email) {
            if (!validate_email($email) ) {
                return false;
            }
        }
        return true;
    }

    public function validation($data, $files) {
        global $COURSE, $DB;
        $errors = parent::validation($data, $files);
        if (isset($data['limitgroup']['maxpostsblock']) &&
            !preg_match('/^[0-9]{1,9}$/', $data['limitgroup']['maxpostsblock'])) {
            $errors['limitgroup'] = get_string('err_numeric', 'form');
        }
        if (!empty($data['reportingemail']) && !$this->validate_emails($data['reportingemail'])) {
            $errors['reportingemail'] = get_string('invalidemail', 'forumng');
        }
        if (!empty($data['completionpostsenabled']) && (!empty($data['completionposts']) && ($data['completionposts'] < 1  || !is_int($data['completionposts'])))){
            $errors['completionpostsgroup'] = get_string('onlyposnum', 'forumng');
        }
        if (!empty($data['completiondiscussionsenabled']) && (!empty($data['completiondiscussions'])  && ($data['completiondiscussions'] < 1 || !is_int($data['completiondiscussions'])))){
            $errors['completiondiscussionsgroup'] = get_string('onlyposnum', 'forumng');
        }
        if (!empty($data['completionrepliesenabled']) && (!empty($data['completionreplies']) && ($data['completionreplies'] < 1 || !is_int($data['completionreplies'])))){
            $errors['completionrepliesgroup'] = get_string('onlyposnum', 'forumng');
        }
        if (isset($data['timetrackingto'])
                && ($data['timetrackingto'] != 0) && ($data['timetrackingfrom'] != 0)
                && ($data['timetrackingto'] <= $data['timetrackingfrom'])) {
            $errors['completiontracking'] = get_string('completionenddateerror', 'forumng');
        }

        // If old discussions are set to be moved to another forum...
        $targetforumngid = isset($data['removeto']) ? $data['removeto'] : 0;
        $removeafter = isset($data['removeafter']) ? $data['removeafter'] : 0;
        if ($removeafter && $targetforumngid > 0) {
            $modinfo = get_fast_modinfo($COURSE);
            // Look for target forum.
            if (!array_key_exists($targetforumngid, $modinfo->instances['forumng'])) {
                $errors['removeto'] = get_string('errorinvalidforum', 'forumng');
            }
        }

        // If sharing is turned on, check requirements.
        if (!empty($data['shared'])) {
            if (!empty($data['groupmode'])) {
                $errors['groupmode'] = get_string('error_notwhensharing', 'forumng');
            }
            if (!empty($data['grading'])) {
                $errors['grading'] = get_string('error_notwhensharing', 'forumng');
            }
            if (empty($data['cmidnumber'])) {
                $errors['cmidnumber'] = get_string('error_sharingrequiresidnumber', 'forumng');
            } else {
                // Check it's unique.
                $cmid = isset($data['coursemodule']) ? (int)$data['coursemodule'] : 0;
                if ($DB->count_records_select('course_modules', "idnumber = ? AND id <> ?",
                        array($data['cmidnumber'], $cmid))) {
                    $errors['cmidnumber'] = get_string('error_sharingrequiresidnumber', 'forumng');
                }
            }
        } else if (isset($data['shared'])) {
            // They are trying to turn sharing off. You aren't allowed to do
            // this if there are existing references.
            $cmid = isset($data['coursemodule']) ? (int)$data['coursemodule'] : -1;
            if ($DB->count_records('forumng', array('originalcmid' => $cmid))) {
                $errors['shared'] = get_string('error_sharinginuse', 'forumng');
            }
        }

        if (!empty($data['usesharedgroup']['useshared'])) {
            if (empty($data['usesharedgroup']['originalcmidnumber'])) {
                $errors['usesharedgroup'] = get_string('error_sharingidnumbernotfound', 'forumng');
            } else {
                // Check we can find it.
                if (!mod_forumng::get_shared_cm_from_idnumber(
                        $data['usesharedgroup']['originalcmidnumber'])) {
                    $errors['usesharedgroup'] = get_string('error_sharingidnumbernotfound',
                            'forumng');
                }
            }
        }

        // If grading is set to ratings and ratings not enabled.
        if (!empty($data['grading'])) {
            if (($data['grading'] > 0 && $data['grading'] < 6) && (empty($data['enableratings']))) {
                // If grading between 1 and 5 (not = 6 and not = 0) and enableratings is empty (not checked).
                $errors['enableratings'] = get_string('error_ratingrequired', 'forumng');
            }
        }

        if (!empty($data['postingfrom']) && !empty($data['postinguntil'])) {
            if ($data['postingfrom'] >= $data['postinguntil']) {
                $errors['postinguntil'] = get_string('timestartenderror', 'forumng');
            }
        }

        if (!empty($data['ratingfrom']) && !empty($data['ratinguntil'])) {
            if ($data['ratingfrom'] >= $data['ratinguntil']) {
                $errors['ratinguntil'] = get_string('timestartenderror', 'forumng');
            }
        }

        // If completion wordcount is enabled.
        if ((!empty($data['completionwordcountminenabled']) && $data['completionwordcountmin'] <= 0) ||
                (!empty($data['completionwordcountmaxenabled']) && $data['completionwordcountmax'] <= 0) ||
                (!empty($data['completionwordcountminenabled']) && !empty($data['completionwordcountmaxenabled']) &&
                ($data['completionwordcountmin'] > $data['completionwordcountmax']) &&
                        !empty($data['timetrackingfrom']) && !empty($data['timetrackingto'])
                )) {
            $errors['completion'] = get_string('badautocompletion', 'completion');
        }

        return $errors;
    }

    public function data_preprocessing(&$data) {
        if (!empty($data['maxpostsperiod']) && !empty($data['maxpostsblock'])) {
            $data['limitgroup[enablelimit]'] = 1;
            $data['limitgroup[maxpostsperiod]'] = $data['maxpostsperiod'];
            $data['limitgroup[maxpostsblock]'] = $data['maxpostsblock'];
        } else {
            $data['limitgroup[enablelimit]'] = 0;
            $data['limitgroup[maxpostsperiod]'] = 60 * 60 * 24;
            $data['limitgroup[maxpostsblock]'] = 10;
        }

        if ($this->current->instance) {
            $draftitemid = file_get_submitted_draft_itemid('introductioneditor');
            $data['introductioneditor']['format'] = $data['introductionformat'];
            $data['introductioneditor']['text']   = file_prepare_draft_area($draftitemid, $this->context->id,
                    'mod_forumng', 'introduction', 0, array('subdirs' => true), $data['introduction']);
            $data['introductioneditor']['itemid'] = $draftitemid;
        }

        // Set up the completion checkboxes which aren't part of standard data.
        // We also make the default value (if you turn on the checkbox) for those
        // numbers to be 1, this will not apply unless checkbox is ticked.
        $data[$this->get_suffixed_name('completiondiscussionsenabled')] =
                !empty($data[$this->get_suffixed_name('completiondiscussions')]) ? 1 : 0;
        if (empty($data[$this->get_suffixed_name('completiondiscussions')])) {
            $data[$this->get_suffixed_name('completiondiscussions')] = 1;
        }
        $data[$this->get_suffixed_name('completionrepliesenabled')] =
                !empty($data[$this->get_suffixed_name('completionreplies')]) ? 1 : 0;
        if (empty($data[$this->get_suffixed_name('completionreplies')])) {
            $data[$this->get_suffixed_name('completionreplies')] = 1;
        }
        $data[$this->get_suffixed_name('completionpostsenabled')] =
                !empty($data[$this->get_suffixed_name('completionposts')]) ? 1 : 0;
        if (empty($data[$this->get_suffixed_name('completionposts')])) {
            $data[$this->get_suffixed_name('completionposts')] = 1;
        }
        $data[$this->get_suffixed_name('completionwordcountminenabled')] =
                !empty($data[$this->get_suffixed_name('completionwordcountmin')]) ? 1 : 0;
        if (empty($data[$this->get_suffixed_name('completionwordcountmin')])) {
            $data[$this->get_suffixed_name('completionwordcountmin')] = 1;
        }
        $data[$this->get_suffixed_name('completionwordcountmaxenabled')] =
                !empty($data[$this->get_suffixed_name('completionwordcountmax')]) ? 1 : 0;
        if (empty($data[$this->get_suffixed_name('completionwordcountmax')])) {
            $data[$this->get_suffixed_name('completionwordcountmax')] = 1;
        }
    }

    public function add_completion_rules() {
        global $PAGE;
        $mform = $this->_form;

        $group = [];
        $completionpostsenabledel = $this->get_suffixed_name('completionpostsenabled');
        $group[] =& $mform->createElement('checkbox', $completionpostsenabledel, '',
                get_string('completionposts', 'forumng'), ['class' => 'forumng-completion-require']);
        $completionpostsel = $this->get_suffixed_name('completionposts');
        $group[] =& $mform->createElement('text', $completionpostsel, '', array('size' => 3));
        $mform->setType($completionpostsel, PARAM_INT);
        $completionpostsgroupel = $this->get_suffixed_name('completionpostsgroup');
        $mform->addGroup($group, $completionpostsgroupel,
                get_string('completionpostsgroup', 'forumng'), array(' '), false);
        $mform->addHelpButton($completionpostsgroupel, 'completionpostsgroup', 'forumng');
        $mform->disabledIf($completionpostsel, $completionpostsenabledel, 'notchecked');

        $group = [];
        $completiondiscussionsenabledel = $this->get_suffixed_name('completiondiscussionsenabled');
        $group[] =& $mform->createElement('checkbox', $completiondiscussionsenabledel, '',
                get_string('completiondiscussions', 'forumng'), ['class' => 'forumng-completion-require']);
        $completiondiscussionsel = $this->get_suffixed_name('completiondiscussions');
        $group[] =& $mform->createElement('text', $completiondiscussionsel, '', array('size' => 3));
        $mform->setType($completiondiscussionsel, PARAM_INT);
        $completiondiscussionsgroupel = $this->get_suffixed_name('completiondiscussionsgroup');
        $mform->addGroup($group, $completiondiscussionsgroupel,
                get_string('completiondiscussionsgroup', 'forumng'), array(' '), false);
        $mform->addHelpButton($completiondiscussionsgroupel,
                'completiondiscussionsgroup', 'forumng');
        $mform->disabledIf($completiondiscussionsel, $completiondiscussionsenabledel, 'notchecked');

        $group = [];
        $completionrepliesenabledel = $this->get_suffixed_name('completionrepliesenabled');
        $group[] =& $mform->createElement('checkbox', $completionrepliesenabledel, '',
                get_string('completionreplies', 'forumng'), ['class' => 'forumng-completion-require']);
        $completionrepliesel = $this->get_suffixed_name('completionreplies');
        $group[] =& $mform->createElement('text', $completionrepliesel, '', ['size' => 3]);
        $mform->setType($completionrepliesel, PARAM_INT);
        $completionrepliesgroupel = $this->get_suffixed_name('completionrepliesgroup');
        $mform->addGroup($group, $completionrepliesgroupel,
                get_string('completionrepliesgroup', 'forumng'), [' '], false);
        $mform->addHelpButton($completionrepliesgroupel, 'completionrepliesgroup', 'forumng');
        $mform->disabledIf($completionrepliesel, $completionrepliesenabledel, 'notchecked');

        // Restriction for grade completion.
        $mform->disabledIf('completionusegrade', 'grading', 'eq', 0);

        $group = [];
        $completionwordcountminenabledel = $this->get_suffixed_name('completionwordcountminenabled');
        $group[] =& $mform->createElement('checkbox', $completionwordcountminenabledel, '',
                get_string('completionwordcountmin', 'forumng'));
        $completionwordcountminel = $this->get_suffixed_name('completionwordcountmin');
        $group[] =& $mform->createElement('text', $completionwordcountminel, '', ['size' => 3]);
        $mform->setType($completionwordcountminel, PARAM_INT);
        $mform->disabledIf($completionwordcountminel, $completionwordcountminenabledel, 'notchecked');
        $completionwordcountmingroupel = $this->get_suffixed_name('completionwordcountmingroup');
        $mform->addGroup($group, $completionwordcountmingroupel,
                get_string('completionwordcountgroup', 'forumng'), [' '], false);
        $mform->addHelpButton($completionwordcountmingroupel, 'completionwordcountgroup', 'forumng');

        $group = [];
        $completionwordcountmaxenabledel = $this->get_suffixed_name('completionwordcountmaxenabled');
        $group[] =& $mform->createElement('checkbox', $completionwordcountmaxenabledel, '',
                get_string('completionwordcountmax', 'forumng'));
        $completionwordcountmaxel = $this->get_suffixed_name('completionwordcountmax');
        $group[] =& $mform->createElement('text', $completionwordcountmaxel, '', ['size' => 3]);
        $mform->setType($completionwordcountmaxel, PARAM_INT);
        $mform->disabledIf($completionwordcountmaxel, $completionwordcountmaxenabledel, 'notchecked');
        $completionwordcountmaxgroupel = $this->get_suffixed_name('completionwordcountmaxgroup');
        $mform->addGroup($group, $completionwordcountmaxgroupel,'', [' '], false);

        $group = [];
        $timetrackingfromel = $this->get_suffixed_name('timetrackingfrom');
        $group[] =& $mform->createElement('date_time_selector', $timetrackingfromel, get_string('completiontrackingfrom',
                'forumng'), ['optional' => true]);
        $timetrackingtoel = $this->get_suffixed_name('timetrackingto');
        $group[] =& $mform->createElement('date_time_selector', $timetrackingtoel, get_string('completiontrackingto',
                'forumng'), ['optional' => true]);
        $completiontrackingel = $this->get_suffixed_name('completiontracking');
        $mform->addGroup($group, $completiontrackingel, get_string('completiontracking', 'forumng'), [' '], false);
        $mform->addHelpButton($completiontrackingel, 'completiontracking', 'forumng');

        // Use setDefault to set the timetrackingfrom and timetrackingto when unlock the completion.
        // Otherwise, the form will set the current time to those field.
        $data = $this->current;
        if (isset($data->{$this->get_suffixed_name('timetrackingfrom')}) && $data->{$this->get_suffixed_name('timetrackingfrom')} !== 0) {
            $mform->setDefault($timetrackingfromel, $data->{$this->get_suffixed_name('timetrackingfrom')});
        }
        if (isset($data->{$this->get_suffixed_name('timetrackingto')}) && $data->{$this->get_suffixed_name('timetrackingto')} !== 0) {
            $mform->setDefault($timetrackingtoel, $data->{$this->get_suffixed_name('timetrackingto')});
        }
        $timetracking = (object)[
                $timetrackingfromel =>
                        $data->{$this->get_suffixed_name('timetrackingfrom')} ?? 0,
                $timetrackingtoel =>
                        $data->{$this->get_suffixed_name('timetrackingto')} ?? 0,
        ];
        $PAGE->requires->js_call_amd('mod_forumng/mod_form', 'init', [$mform->getAttribute('id'), $timetracking]);

        return [$completiondiscussionsgroupel, $completionrepliesgroupel, $completionpostsgroupel,
                $completionwordcountmingroupel, $completionwordcountmaxgroupel, $completiontrackingel];
    }

    public function completion_rule_enabled($data) {
        return (!empty($data[$this->get_suffixed_name('completiondiscussionsenabled')]) &&
                        $data[$this->get_suffixed_name('completiondiscussions')] != 0) ||
                (!empty($data[$this->get_suffixed_name('completionrepliesenabled')]) &&
                        $data[$this->get_suffixed_name('completionreplies')] != 0) ||
                (!empty($data[$this->get_suffixed_name('completionpostsenabled')]) &&
                        $data[$this->get_suffixed_name('completionposts')] != 0);
    }

    public function get_data() {
        $data = parent::get_data();
        if (!$data) {
            return false;
        }

        // Set the reportingemail to null if empty so that they are consistency.
        if (empty($data->reportingemail)) {
            $data->reportingemail = null;
        }
        // Set the canpostanon to 0 if empty so that they are consistency.
        if (empty($data->canpostanon)) {
            $data->canpostanon = 0;
        }
        // Set the tags to 0 if empty so that they are consistency.
        if (empty($data->enabletags)) {
            $data->enabletags = 0;
        }
        // Set the removeto to null if the default option 'Delete permanently' was select.
        if (empty($data->removeto)) {
            $data->removeto = null;
        }
        // Set the removeto to null if option 'Automatically lock' was selected and removeafter is empty.
        if (($data->removeto == -1) && (empty($data->removeafter)) ) {
            $data->removeto = null;
        }
        // Turn off ratings/limit if required.
        if (empty($data->enableratings)) {
            $data->ratingscale = 0;
        }
        if (empty($data->limitgroup['enablelimit'])) {
            $data->maxpostsperiod = 0;
            $data->maxpostsblock = 0;
        } else {
            $data->maxpostsperiod = $data->limitgroup['maxpostsperiod'];
            $data->maxpostsblock = $data->limitgroup['maxpostsblock'];
        }

        // Turn off completion settings if the checkboxes aren't ticked.
        if (!empty($data->{$this->get_suffixed_name('completionunlocked')})) {
            $autocompletion = !empty($data->{$this->get_suffixed_name('completion')}) &&
                    $data->{$this->get_suffixed_name('completion')} == COMPLETION_TRACKING_AUTOMATIC;
            if (empty($data->{$this->get_suffixed_name('completiondiscussionsenabled')}) || !$autocompletion) {
                $data->{$this->get_suffixed_name('completiondiscussions')} = 0;
            }
            if (empty($data->{$this->get_suffixed_name('completionrepliesenabled')}) || !$autocompletion) {
                $data->{$this->get_suffixed_name('completionreplies')} = 0;
            }
            if (empty($data->{$this->get_suffixed_name('completionpostsenabled')}) || !$autocompletion) {
                $data->{$this->get_suffixed_name('completionposts')} = 0;
            }
            if (empty($data->{$this->get_suffixed_name('completionwordcountminenabled')}) || !$autocompletion) {
                $data->{$this->get_suffixed_name('completionwordcountmin')} = 0;
            }
            if (empty($data->{$this->get_suffixed_name('completionwordcountmaxenabled')}) || !$autocompletion) {
                $data->{$this->get_suffixed_name('completionwordcountmax')} = 0;
            }
            if (empty($data->{$this->get_suffixed_name('timetrackingfrom')}) || !$autocompletion) {
                $data->{$this->get_suffixed_name('timetrackingfrom')} = 0;
            }
            if (empty($data->{$this->get_suffixed_name('timetrackingto')}) || !$autocompletion) {
                $data->{$this->get_suffixed_name('timetrackingto')} = 0;
            }
        }

        // Add in fake form data for clone forums, so core functions expecting it works OK.
        if ($this->clone && !isset($data->introeditor)) {
            // Add fake intro text - is ignored anyway for clones as master is shown.
            $data->introeditor = array('itemid' => 0, 'text' => '', 'format' => FORMAT_MOODLE);
        }

        return $data;
    }

    public function definition_after_data() {
        parent::definition_after_data();
        global $COURSE;
        $mform =& $this->_form;

        if ($this->clone) {
            $mform->removeElement('groupmode');
            return;
        }

        $targetforumngid = $mform->getElementValue('removeto');
        $targetforumngid = $targetforumngid[0] ?? null;
        $removeafter = $mform->getElementValue('removeafter');
        $removeafter = $removeafter[0];
        if ($removeafter && $targetforumngid > 0) {
            $modinfo = get_fast_modinfo($COURSE);
            if (!array_key_exists($targetforumngid, $modinfo->instances['forumng'])) {
                $mform->getElement('removeto')->addOption(
                    get_string('invalidforum', 'forumng'), $targetforumngid);
            }
        }
    }

    /**
     * Get the suffix of name.
     *
     * @param string $fieldname The field name of the completion element.
     * @return string The suffixed name.
     */
    protected function get_suffixed_name(string $fieldname): string {
        return $fieldname . $this->get_suffix();
    }
}
