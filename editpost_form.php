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

    function definition() {
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
        $ajaxversion = $this->_customdata['ajaxversion'];
        $timelimit = isset($this->_customdata['timelimit'])
            ? $this->_customdata['timelimit'] : 0;
        $draft = isset($this->_customdata['draft'])
            ? $this->_customdata['draft'] : null;
        if (!$ajaxversion) {
            $ajaxversion = '';
        }
        if ($ajaxversion && !array_key_exists('draft', $params)) {
            $params['draft'] = 0;
        }

        // Keeps track of whether we add a group selector box
        $groupselector = false;

        if ($ispost) {
            $mform->addElement('header', 'general'.$ajaxversion, '');

            if ($edit && ($timelimit || $ajaxversion)) {
                // For AJAX version, add empty string, which will be
                // populated when retrieving each message. Otherwise,
                // display a 'slightly safer' version of the time limit. (30
                // seconds will display as 1 minute before the real one usually;
                // I used 30 seconds becuase it makes more logical, if not
                // practical, sense compared to the option for a 1-minute timeout.)
                $mform->addElement('static', '', '',
                    '<div id="id_editlimit' . $ajaxversion . '">' .
                    ($timelimit ? get_string('editlimited', 'forumng',
                        userdate($timelimit-30,
                            get_string('strftimetime', 'langconfig'))) : '') .
                    '</div>');
            }

            $quotaleft = $forum->get_remaining_post_quota();
            if (!$edit && $quotaleft != mod_forumng::QUOTA_DOES_NOT_APPLY &&
                ($quotaleft <= 2 || $ajaxversion)) {
                $a = (object)array(
                    'posts' => $quotaleft,
                    'period' => $forum->get_max_posts_period(true, true));

                $text = '';
                $script = '';
                if ($ajaxversion) {
                    $script =
                        '<script type="text/javascript">forumng_quotaleft = ' .
                        $quotaleft . '</script>';
                } else {
                    $text = ($quotaleft <= 2 ? get_string(
                        $quotaleft == 1 ? 'quotaleft_singular' : 'quotaleft_plural',
                        'forumng', $a) : '');
                }
                $mform->addElement('static', '', '',
                    '<div id="id_postlimit' . $ajaxversion .
                    '">' . $text . $script . '</div>');
            }

           $mform->addElement('text', 'subject',
                $isroot ? get_string('subject', 'forumng')
                : get_string('optionalsubject', 'forumng'),
                array('size'=>48, 'id'=>'id_subject' . $ajaxversion));
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

            // Special field just to tell javascript that we're trying to use the
            // html editor
            $mform->addElement('hidden', 'tryinghtmleditor', can_use_html_editor() ? 1 : 0);

            $editorattributes = array('id'=>'id_message' . $ajaxversion,
                    'cols'=>50, 'rows'=> $ajaxversion ? 15 : 30);
            if (!$ajaxversion) {
                $editoroptions = array('maxfiles' => EDITOR_UNLIMITED_FILES,
                    'context'=>$forum->get_context(true));
                $mform->addElement('editor', 'message', get_string('message', 'forumng'),
                        $editorattributes, $editoroptions);
                $gotmessage = true;
            } else {
                // AJAX version has a placeholder, to be installed by live JavaScript
                if (can_use_html_editor()) {
                    $mform->addElement('text', 'message', get_string('message', 'forumng'),
                            $editorattributes);
                    $gotmessage = true;
                } else {
                    $mform->addElement('textarea', 'message[text]', get_string('message', 'forumng'),
                            $editorattributes);
                    $mform->addElement('hidden', 'message[format]', FORMAT_MOODLE);
                    $gotmessage = false;
                }
            }

            if ($gotmessage) {
                $mform->setType('message', PARAM_CLEANHTML);
                $mform->addRule('message', get_string('required'),
                        'required', null, 'client');
            } else {
                $mform->setType('message[text]', PARAM_RAW);
                $mform->addRule('message[text]', get_string('required'),
                        'required', null, 'client');
            }

            // If you can create attachments...
            if ($forum->can_create_attachments()) {
                if (!$ajaxversion) {
                    // Non-AJAX version has normal file manager
                    $mform->addElement('filemanager', 'attachments',
                            get_string('attachments', 'forumng'), null,
                            array('subdirs'=>false, 'maxbytes'=>$forum->get_max_bytes()));
                } else {
                    // Ajax version has a placeholder field only, be filled live
                    $mform->addElement('text', 'attachments', get_string('attachments', 'forumng'),
                            array('id'=>'id_attachments' . $ajaxversion));
                }
            }

            // If you can mail now, we show this option
            $mform->addElement('header', 'id_importance' . $ajaxversion, '');
            $attachmentlist = '';
            if (!$edit && $forum->can_mail_now()) {
                $mform->addElement('checkbox', 'mailnow', get_string('mailnow', 'forumng'));
                $mform->addHelpButton('mailnow', 'mailnow', 'forumng');
            }
            if ($forum->can_set_important() && !$isdiscussion && !$isroot && !$islock) {
                $mform->addElement('checkbox', 'setimportant',
                        get_string('setimportant', 'forumng'));
            }
        }

        // Additional options apply only to discussion
        if ($isdiscussion && $forum->can_manage_discussions()) {

            // Restrict to specific time period (only if you are allowed to
            // see hidden posts, otherwise stupid to let people hide it and
            // then not see)
            if ($forum->can_view_hidden()) {
                $mform->addElement('header', 'id_displayperiod',
                    get_string('displayperiod', 'forumng'));

                $mform->addElement('date_selector', 'timestart',
                    get_string('timestart', 'forumng'), array('optional'=>true));
                $mform->addHelpButton('timestart', 'displayperiod', 'forumng');

                $mform->addElement('date_selector', 'timeend',
                    get_string('timeend', 'forumng'), array('optional'=>true));
            }

            // Discussion options...
            $mform->addElement('header', 'id_stickyoptions',
                get_string('discussionoptions', 'forumng'));

            // Sticky discussion
            $options = array();
            $options[0] = get_string('sticky_no', 'forumng');
            $options[1] = get_string('sticky_yes', 'forumng');
            $mform->addElement('select', 'sticky',
                get_string('sticky', 'forumng'), $options);
            $mform->addHelpButton('sticky', 'sticky', 'forumng');

            // Group
            if ($forum->get_group_mode()) {
                // Group ID comes from the post (if provided) or the params
                if ($post) {
                    $groupid = $post->get_discussion()->get_group_id();
                } else {
                    $groupid = $params['group'];
                }

                // Display as static or dropdown
                if (has_capability('moodle/site:accessallgroups',
                    $forum->get_context())) {
                    // Users with 'access all groups' can move discussions, so
                    // show dropdown with all groups
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
                    // group of the discussion
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

        // Post / save changes button
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
            $submitlabel, array('id'=>'id_submitbutton' . $ajaxversion));
        $buttonarray[] = &$mform->createElement('cancel', '', '',
            array('id'=>'id_cancel' . $ajaxversion));
        if (!$edit) {
            // Can't save draft while editing
            $buttonarray[] = &$mform->createElement('submit', 'savedraft',
                get_string('savedraft', 'forumng'),
                array('id'=>'id_savedraft' . $ajaxversion));
        }
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');

        // Hidden fields
        foreach ($params as $param => $value) {
            // If there's a group selector, don't duplicate the group param
            if ($param == 'group' && $groupselector) {
                continue;
            }
            $mform->addElement('hidden', $param, $value);
        }
        if (!$ajaxversion) {
            // Prevent multiple submits (except of AJAX version)
            $mform->addElement('hidden', 'random', rand());
        }
    }

    function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (isset($data['timeend'])
            && ($data['timeend']!=0) && ($data['timestart']!=0)
            && ($data['timeend'] < $data['timestart'])) {
            $errors['timeend'] = get_string('timestartenderror', 'forumng');
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
