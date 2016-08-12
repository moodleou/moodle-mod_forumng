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

require_once($CFG->libdir.'/formslib.php');

/**
 * Form for editing a post or discussion.
 * @package mod
 * @subpackage forumng
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_forumng_editpost_form extends moodleform {

    public function definition() {
        global $CFG, $USER;
        $mform = $this->_form;
        $params = $this->_customdata['params'];
        $forum = $this->_customdata['forum'];
        $edit = $this->_customdata['edit'];
        $isdiscussion = $this->_customdata['isdiscussion'];
        $isroot = $this->_customdata['isroot'];
        $ispost = $this->_customdata['ispost'];
        $islock = $this->_customdata['islock'];
        $post = $this->_customdata['post'];
        $timelimit = isset($this->_customdata['timelimit'])
            ? $this->_customdata['timelimit'] : 0;
        $draft = isset($this->_customdata['draft'])
            ? $this->_customdata['draft'] : null;
        $tags = isset($this->_customdata['tags'])
            ? $this->_customdata['tags'] : null;
        $tagoptions = isset($this->_customdata['forumtags'])
                ? $this->_customdata['forumtags'] : null;
        // Keeps track of whether we add a group selector box.
        $groupselector = false;

        if ($ispost) {

            if ($edit && $timelimit) {
                // Note: We display a safer version of the time limit (30 seconds
                // sooner), to allow leeway for server to save it.
                $mform->addElement('static', '', '',
                    html_writer::tag('div',
                        get_string('editlimited', 'forumng', userdate($timelimit-30,
                            get_string('strftimetime', 'langconfig'))),
                        array('id' => 'id_editlimit')));
                $mform->addElement('hidden', 'timelimit', $timelimit);
                $mform->setType('timelimit', PARAM_INT);
            }

            $quotaleft = $forum->get_remaining_post_quota();
            if (!$edit && $quotaleft != mod_forumng::QUOTA_DOES_NOT_APPLY && $quotaleft <= 2) {
                $a = (object)array(
                    'posts' => $quotaleft,
                    'period' => $forum->get_max_posts_period(true, true));

                $text = '';
                $script = '';
                $text = ($quotaleft <= 2 ? get_string(
                    $quotaleft == 1 ? 'quotaleft_singular' : 'quotaleft_plural',
                    'forumng', $a) : '');
                $mform->addElement('static', '', '',
                    '<div id="id_postlimit">' . $text . $script . '</div>');
            }

            $mform->addElement('text', 'subject',
                $isroot ? get_string('subject', 'forumng')
                : get_string('optionalsubject', 'forumng'),
                array('id'=>'id_subject'));
            $mform->setType('subject', PARAM_TEXT);
            $mform->addRule('subject', get_string('maximumchars', '', 255),
                'maxlength', 255, 'client');
            if ($isroot) {
                $mform->addRule('subject', get_string('required'),
                    'required', null, 'client');
                $mform->addRule('subject', get_string('required'),
                    'regex', '/\S+/', 'client');
            }
            if ($islock) {
                $mform->setDefault('subject',
                    get_string('locksubject', 'forumngfeature_lock'));
            }

            $editorattributes = array('id' => 'id_message',
                    'cols' => 50, 'rows' => !empty($params['iframe']) ? 15 : 30);
            $editoroptions = array(
                'maxfiles' => EDITOR_UNLIMITED_FILES,
                'context' => $forum->get_context(true),
                'maxbytes' => $forum->get_max_bytes()
            );
            $mform->addElement('editor', 'message', get_string('message', 'forumng'),
                    $editorattributes, $editoroptions);
            $mform->setType('message', PARAM_RAW);
            $mform->addRule('message', get_string('required'),
                    'required', null, 'client');

            // If you can create attachments...
            if ($forum->can_create_attachments()) {
                $mform->addElement('filemanager', 'attachments',
                        get_string('attachments', 'forumng'), null,
                        array('subdirs' => false, 'maxbytes' => $forum->get_max_bytes()));
            }

            // If you can mail now, we show this option.
            $attachmentlist = '';
            if ($forum->can_set_important() && !$isdiscussion && !$isroot && !$islock) {
                $mform->addElement('checkbox', 'setimportant',
                        get_string('setimportant', 'forumng'));
            }
            // Only add moderator element to post edit form if op1 or op2 available.
            if ($forum->can_post_anonymously() || $forum->can_indicate_moderator()) {
                $options=array();
                $options[mod_forumng::ASMODERATOR_NO] = get_string('asmoderator_post', 'forumng');
                if ($forum->can_indicate_moderator()) {
                    $options[mod_forumng::ASMODERATOR_IDENTIFY] = get_string('asmoderator_self', 'forumng');
                }
                if ($forum->can_post_anonymously()) {
                    $options[mod_forumng::ASMODERATOR_ANON] = get_string('asmoderator_anon', 'forumng');
                }
                $mform->addElement('select', 'asmoderator', get_string('asmoderator', 'forumng'), $options);
                $mform->addHelpButton('asmoderator', 'asmoderator', 'forumng');
                $mform->setDefault('asmoderator', 0);
                $mform->setType('asmoderator', PARAM_INT);
            } else {
                $mform->addElement('hidden', 'asmoderator', 0);
                $mform->setType('asmoderator', PARAM_INT);
            }

            if ($edit && ($post->get_user()->id != $USER->id)) {
                // Email author.
                $mform->addElement('header', 'id_emailauthor', get_string('emailauthor', 'forumng'));
                $mform->addElement('checkbox', 'emailauthor',
                        get_string('emailauthor', 'forumng'));
                $mform->addHelpButton('emailauthor', 'emailauthor', 'forumng');

                // Message box.
                $mform->addElement('editor', 'emailmessage', get_string('emailmessage', 'forumng'),
                        array('size' => '64', 'id' => 'id_forumng_edit_msg'));
                $mform->setType('emailmessage', PARAM_RAW);

                $mform->addElement('checkbox', 'emailself', get_string('copytoself', 'forumng'));
                $mform->disabledIf('emailself', 'emailauthor', 'notchecked');

                // Adding optional text field 'Email address of other recipients'.
                $mform->addElement('text', 'emailadd', get_string('extra_emails', 'forumng'),
                        array('size' => '48'));
                $mform->addHelpButton('emailadd', 'extra_emails', 'forumng');
                $mform->setType('emailadd', PARAM_RAW);
                $mform->disabledIf('emailadd', 'emailauthor', 'notchecked');
            }
        }

        // Additional options apply only to discussion.
        if ($isdiscussion && $forum->can_manage_discussions()) {

            // Restrict to specific time period (only if you are allowed to
            // see hidden posts, otherwise stupid to let people hide it and
            // then not see).
            if ($forum->can_view_hidden()) {
                $mform->addElement('header', 'id_displayperiod',
                    get_string('displayperiod', 'forumng'));

                $mform->addElement('date_selector', 'timestart',
                    get_string('timestart', 'forumng'), array('optional'=>true));
                $mform->addHelpButton('timestart', 'displayperiod', 'forumng');

                $mform->addElement('date_selector', 'timeend',
                    get_string('timeend', 'forumng'), array('optional'=>true));
            }
        }

        // Discussion options...
        if ($isdiscussion && ($forum->can_manage_discussions() || $forum->can_tag_discussion())) {
            $mform->addElement('header', 'id_stickyoptions',
                get_string('discussionoptions', 'forumng'));

            // Sticky discussion.
            if ($forum->can_manage_discussions()) {
                $options = array();
                $options[0] = get_string('sticky_no', 'forumng');
                $options[1] = get_string('sticky_yes', 'forumng');
                $mform->addElement('select', 'sticky',
                        get_string('sticky', 'forumng'), $options);
                $mform->addHelpButton('sticky', 'sticky', 'forumng');
            }

            // Tag discussion.
            if ($forum->can_tag_discussion()) {
                $tagselect = $mform->addElement('autocomplete', 'tags', get_string('discussiontags', 'forumng'),
                        $tagoptions, array('tags' => true, 'placeholder' => get_string('entertags', 'tag')));
                $tagselect->setMultiple(true);
                $mform->setType('tags', PARAM_TAGLIST);
                $mform->setDefault('tags', $tags);
                $mform->addHelpButton('tags', 'discussiontags', 'forumng');
            }
        }

        // Group.
        if ($isdiscussion && $forum->can_manage_discussions()) {
            if ($forum->get_group_mode()) {
                // Group ID comes from the post (if provided) or the params.
                if ($post) {
                    $groupid = $post->get_discussion()->get_group_id();
                } else {
                    $groupid = $params['group'];
                }

                // Display as static or dropdown.
                if (has_capability('moodle/site:accessallgroups',
                    $forum->get_context())) {
                    // Users with 'access all groups' can move discussions, so
                    // show dropdown with all groups.
                    $cm = $forum->get_course_module();
                    $groups = groups_get_all_groups(
                        $cm->course,
                        has_capability('moodle/site:accessallgroups',
                            $forum->get_context()) ? 0 : $USER->id,
                        $cm->groupingid);
                    $options = array();
                    $options[mod_forumng::ALL_GROUPS] = get_string('allparticipants');
                    foreach ($groups as $group) {
                        $options[$group->id] = format_string($group->name);
                    }
                    $mform->addElement('select', 'group', get_string('group'),
                        $options);
                    $mform->setDefault('group', $groupid);
                    $groupselector = true;
                } else {
                    // Users without 'access all groups' only see the current
                    // group of the discussion.
                    if ($groupid == mod_forumng::ALL_GROUPS) {
                        $groupname = get_string('allparticipants');
                    } else {
                        $group = groups_get_group($groupid);
                        $groupname = format_string($group->name);
                    }
                    $mform->addElement('static', 'groupinfo',
                        get_string('group'), $groupname);
                }
            }

            // Note: Lock/unlock is not available here. When locking a
            // discussion you are prompted to give a reason (=new post).
            // This is available from the discussion page. Unlocking is
            // available from a link in the special 'discussion is locked'
            // message that appears at the top of the discussion page.
        }

        // Post / save changes button.
        if ($edit) {
            $submitlabel = get_string('savechanges');
        } else if ($islock) {
            $submitlabel = get_string('lockdiscussionbutton', 'forumngfeature_lock');
        } else if ($isdiscussion) {
            $submitlabel = get_string('postdiscussion', 'forumng');
        } else {
            $submitlabel = get_string('postreply', 'forumng');
        }

        $buttonarray = array();
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton',
            $submitlabel, array('id' => 'id_submitbutton'));
        $buttonarray[] = &$mform->createElement('cancel', '', '',
            array('id' => 'id_cancel'));
        if (!$edit && !$islock) {
            // Can't save draft while editing.
            $buttonarray[] = &$mform->createElement('submit', 'savedraft',
                get_string('savedraft', 'forumng'),
                array('id'=>'id_savedraft'));
        }
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');

        // Hidden fields.
        foreach ($params as $param => $value) {
            // If there's a group selector, don't duplicate the group param.
            if ($param == 'group' && $groupselector) {
                continue;
            }
            $mform->addElement('hidden', $param, $value);
            $mform->setType($param, PARAM_INT);
        }
        // Prevent multiple submits.
        $mform->addElement('hidden', 'random', rand());
        $mform->setType('random', PARAM_INT);
    }

    public function validation($data, $files) {
        global $USER;
        $errors = parent::validation($data, $files);
        if (isset($data['timeend'])
            && ($data['timeend']!=0) && ($data['timestart']!=0)
            && ($data['timeend'] < $data['timestart'])) {
            $errors['timeend'] = get_string('timestartenderror', 'forumng');
        }
        if (!empty($data['emailadd'])) {
            $emails = preg_split('~[; ]+~', $data['emailadd']);
            if (count($emails) < 1) {
                $errors['emailadd'] = get_string('invalidemails', 'forumng');
            } else {
                foreach ($emails as $email) {
                    if (!validate_email($email)) {
                        $errors['emailadd'] = get_string('invalidemails', 'forumng');
                        break;
                    }
                }
            }
        }
        if (!empty($data['subject'])) {
            // Sometimes broken browsers/password managers autocomplete the
            // subject field with the student's OUCU or PI. We do not allow
            // them to submit the post in this case.
            $subject = core_text::strtolower($data['subject']);
            if ($subject === core_text::strtolower($USER->username) ||
                    $subject === core_text::strtolower($USER->idnumber)) {
                if ($this->_customdata['isroot']) {
                    $errors['subject'] = get_string('error_identityinsubject_discussion', 'forumng');
                } else {
                    $errors['subject'] = get_string('error_identityinsubject_reply', 'forumng');
                }
            }
        }
        return $errors;
    }

    /**
     * Obtains HTML for form; needed so that this can be printed for AJAX version.
     * @return string HTML for form
     */
    public function get_html() {
        return $this->_form->toHtml();
    }
}
