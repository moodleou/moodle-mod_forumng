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
 * Script for editing a post or discussion. Has many variants such as new
 * post, new discussion, reply, edit post, save draft post, continue existing
 * draft post.
 * @package mod
 * @subpackage forumng
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Annoyingly it is necessary to define AJAX_SCRIPT before config.php runs
// (Moodle generally assumes you will use different scripts for AJAX but we
// want to use the same one)
if (isset($_REQUEST['ajax'])) {
    define('AJAX_SCRIPT', true);
}
require_once('../../config.php');
require_once('mod_forumng.php');
require_once($CFG->dirroot . '/tag/lib.php');

$pageparams = array();

// Get AJAX parameter
$ajax = optional_param('ajax', 0, PARAM_INT);
if ($ajax) {
    $pageparams['ajax'] = $ajax;
}
$iframe = optional_param('iframe', 0, PARAM_INT);
if ($iframe) {
    $pageparams['iframe'] = $iframe;
}

function finish($postid, $cloneid, $url, $fromform, $ajaxdata='', $iframeredirect=false) {
    global $ajax, $iframe;
    if ($ajax) {
        if ($ajaxdata) {
            // Print AJAX data if specified
            header('Content-Type: text/plain');
            print $ajaxdata;
            exit;
        } else {
            // Default otherwise is to print post
            mod_forumng_post::print_for_ajax_and_exit($postid, $cloneid,
                array(mod_forumng_post::OPTION_DISCUSSION_SUBJECT => true));
        }
    }
    if ($iframe) {
        if ($iframeredirect) {
            // Still redirect, even though it's in an iframe.
            redirect($url . '&iframe=1');
        } else {
            // Do not redirect, just output new post.
            mod_forumng_post::print_for_iframe_and_exit($postid, $cloneid,
                array(mod_forumng_post::OPTION_DISCUSSION_SUBJECT => true));
        }
    }

    redirect($url);
}

function send_edit_email($formdata, $post) {
    global $USER, $SITE;

    // Set up the email.
    $user = $post->get_user();
    $from = $SITE->fullname;
    $subject = get_string('editedforumpost', 'forumng');
    $messagetext = $formdata->emailmessage['text'];

    // Send an email to the author of the post, using prefered format.
    if (!email_to_user($user, $from, $subject, html_to_text($messagetext), $messagetext)) {
        print_error(get_string('emailerror', 'forumng'));
    }

    // Prepare for copies.
    $emails = array();
    $subject = strtoupper(get_string('copy')) . ' - '. $subject;
    if (!empty($formdata->emailself)) {
        // Send an email copy to the current user, using prefered format.
        if (!email_to_user($USER, $from, $subject, html_to_text($messagetext), $messagetext)) {
            print_error(get_string('emailerror', 'forumng'));
        }
    }

    // Addition of 'Email address of other recipients'.
    if (!empty($formdata->emailadd)) {
        $emails = preg_split('~[; ]+~', $formdata->emailadd);
    }

    // If there are any recipients listed send them a HTML copy.
    if (!empty($emails[0])) {
        foreach ($emails as $email) {
            $fakeuser = (object)array(
                    'email' => $email,
                    'mailformat' => 1,
                    'id' => -1
            );
            if (!email_to_user($fakeuser, $from, $subject, '', $messagetext)) {
                print_error(get_string('emailerror', 'forumng'));
            }
        }
    }
}

try {
    // Get type of action/request and check security
    $isdiscussion = false;
    $isroot = false;
    $ispost = false;
    $edit = false;
    $islock = false;
    $cloneid = optional_param('clone', 0, PARAM_INT);
    if ($cloneid) {
        $pageparams['clone'] = $cloneid;
    }

    // Were all posts expanded?
    $expand = optional_param('expand', 0, PARAM_INT);
    $expandparam = $expand ? '&expand=1' : '';

    // See if this is a draft post
    $draft = null;
    $replytoid = 0;
    $cmid = 0;
    $groupid = 0;
    $forum = null;
    $post = null;
    $tags = null;
    $forumtags = null;
    if ($draftid = optional_param('draft', 0, PARAM_INT)) {
        $pageparams['draft'] = $draftid;
        $draft = mod_forumng_draft::get_from_id($draftid);

        // Draft post must be for current user!
        if ($draft->get_user_id() != $USER->id) {
            print_error('draft_mismatch', 'forumng');
        }
        if ($draft->is_reply()) {
            $replytoid = $draft->get_parent_post_id();
        } else {
            $forum = mod_forumng::get_from_id($draft->get_forumng_id(),
                optional_param('clone', 0, PARAM_INT));
            $groupid = $draft->get_group_id();
        }
    }

    if ($forum || ($cmid = optional_param('id', 0, PARAM_INT))) {
        // For new discussions, id (forum cmid) and groupid are required (groupid
        // may be mod_forumng::ALL_GROUPS if required)
        if ($forum) {
            // Came from draft post
            $cmid = $forum->get_course_module_id();
        } else {
            $pageparams['id'] = $cmid;
            $forum = mod_forumng::get_from_cmid($cmid, $cloneid);
        }
        if ($forum->get_group_mode()) {
            if (!$draft) {
                $groupid = required_param('group', PARAM_INT);
                $pageparams['group'] = $groupid;
            }
            if ($groupid == 0) {
                $groupid = mod_forumng::ALL_GROUPS;
            }
        } else {
            $groupid = mod_forumng::NO_GROUPS;
        }

        $post = null;

        // Handles all access security checks
        $forum->require_start_discussion($groupid);

        $isdiscussion = true;
        $isroot = true;
        $ispost = true;
        if ($draftid) {
            $params = array('draft'=>$draftid, 'group'=>$groupid);
        } else {
            $params = array('id'=>$cmid, 'group'=>$groupid);
        }
        $pagename = get_string('addanewdiscussion', 'forumng');
        $forumtags = array();
        foreach ($forum->get_tags_used($groupid, true) as $tag) {
            $forumtags[core_tag_tag::make_display_name($tag, false)] = core_tag_tag::make_display_name($tag, true);
        }

    } else if ($replytoid ||
        ($replytoid = optional_param('replyto', 0, PARAM_INT))) {
        if ($replytoid) {
            $pageparams['replyto'] = $replytoid;
        }
        // For replies, replyto= (post id of one we're replying to) is required
        $replyto = mod_forumng_post::get_from_id($replytoid, $cloneid);
        $discussion = $replyto->get_discussion();
        $forum = $replyto->get_forum();

        // Handles all access security checks
        $replyto->require_reply();

        $ispost = true;
        if ($draftid) {
            $params = array('draft'=>$draftid);
        } else {
            $params = array('replyto'=>$replytoid);
        }
        $pagename = get_string('replytopost', 'forumng',
            $replyto->get_effective_subject(true));
    } else if ($lock = optional_param('lock', 0, PARAM_INT)) {
        $pageparams['lock'] = $lock;
        // For locks, d= discussion id of discussion we're locking
        $discussionid = required_param('d', PARAM_INT);
        $discussion = mod_forumng_discussion::get_from_id($discussionid, $cloneid);
        $replyto = $discussion->get_root_post();
        $forum = $discussion->get_forum();
        $discussion->require_edit();
        if ($discussion->is_locked()) {
            print_error('edit_locked', 'forumng');
        }

        $ispost = true;
        $islock = true;
        $params = array('d'=>$discussionid, 'lock'=>1);
        $pagename = get_string('lockdiscussion', 'forumngfeature_lock',
            $replyto->get_effective_subject(false));
    } else if ($discussionid = optional_param('d', 0, PARAM_INT)) {
        $pageparams['d'] = $discussionid;
        // To edit discussion settings only (not the standard post settings
        // such as subject, which everyone can edit), use d (discussion id)
        $discussion = mod_forumng_discussion::get_from_id($discussionid, $cloneid);
        $post = $discussion->get_root_post();
        $forum = $discussion->get_forum();
        $discussion->require_edit();

        $isdiscussion = true;
        $edit = true;
        $params = array('d'=>$discussionid);
        $pagename = get_string('editdiscussionoptions', 'forumng',
            $post->get_effective_subject(false));
        $tags = $discussion->get_tags(true);
        $forumtags = array();
        foreach ($forum->get_tags_used($discussion->get_group_id(), true) as $tag) {
            $forumtags[core_tag_tag::make_display_name($tag, false)] = core_tag_tag::make_display_name($tag, true);
        }
    } else {
        // To edit existing posts, p (forum post id) is required
        $postid = required_param('p', PARAM_INT);
        $pageparams['p'] = $postid;
        $post = mod_forumng_post::get_from_id($postid, $cloneid);
        $discussion = $post->get_discussion();
        $forum = $post->get_forum();

        // Handles all access security checks
        $post->require_edit();

        $isroot = $post->is_root_post();
        $ispost = true;
        $edit = true;
        $params = array('p'=>$postid);
        $pagename = get_string('editpost', 'forumng',
            $post->get_effective_subject(true));
    }

    // Get other useful variables (convenience)
    $course = $forum->get_course();
    $cm = $forum->get_course_module();
    $filecontext = $forum->get_context(true); // All files stored in real forum, if this is clone
    $fileoptions = array('subdirs'=>false, 'maxbytes'=>$forum->get_max_bytes());

    // Set up basic page things (needed for form)
    $PAGE->set_context($forum->get_context());
    $PAGE->set_cm($cm, $course);
    $PAGE->set_url(new moodle_url('/mod/forumng/editpost.php', $pageparams));
    if (defined('BEHAT_SITE_RUNNING')) {
        if ($iframe){
            $PAGE->set_pagelayout('embedded');
        }
    } else {
        $PAGE->set_pagelayout($iframe ? 'embedded' : 'base');
    }
    if ($iframe) {
        $PAGE->add_body_class('forumng-iframe');
    }

    // See if this is a save action or a form view
    require_once('editpost_form.php');
    if ($cloneid) {
        // Clone parameter is required for all actions
        $params['clone'] = $cloneid;
    }
    // Iframe parameter always available.
    if ($iframe) {
        $params['iframe'] = 1;
    }
    // Expand parameter always available
    $params['expand'] = $expand;
    $mform = new mod_forumng_editpost_form('editpost.php',
        array('params'=>$params, 'isdiscussion'=>$isdiscussion,
            'forum'=>$forum, 'edit'=>$edit, 'ispost'=>$ispost, 'islock'=>$islock,
            'post'=>isset($post) ? $post : null, 'isroot'=>$isroot,
            'iframe' => $iframe ? true : false,
            'timelimit' => $ispost && $edit && !$post->can_ignore_edit_time_limit()
                ? $post->get_edit_time_limit() : 0,
            'draft' => $draft, 'tags' => $tags, 'forumtags' => $forumtags));

    if (is_object($post)) {
        // Not a new discussion/post so we are editing a pre-existing post.
        $formdata = new stdClass();
        // Use the html message.
        $discussion = $post->get_discussion();
        // Prepare the object for the get_string.
        $emailmessage = new stdClass();
        $emailmessage->subject = $post->get_effective_subject(true);
        $emailmessage->editinguser = fullname($USER);
        $emailmessage->course = $COURSE->fullname;
        $emailmessage->forum = $forum->get_name();
        $emailmessage->editurl = $CFG->wwwroot . '/mod/forumng/discuss.php?'
                . $discussion->get_link_params(mod_forumng::PARAM_PLAIN)
                . '#p' . $post->get_id();
        // Use the html text.
        $formdata->emailmessage['text'] = get_string('emaileditedcontenthtml', 'forumng', $emailmessage);
        $mform->set_data($formdata);
    }

    if ($mform->is_cancelled()) {
        if ($iframe) {
            // If we got to cancel in an iframe do js sucess code so iframe closes.
            finish(0, $cloneid, '', null, null, false);
        }
        if ($edit) {
            redirect('discuss.php?' .
                    $post->get_discussion()->get_link_params(mod_forumng::PARAM_PLAIN) .
                    $expandparam);
        } else if ($islock || $replytoid) {
            redirect('discuss.php?' . $discussion->get_link_params(mod_forumng::PARAM_PLAIN) .
                    $expandparam);
        } else {
            redirect('view.php?' . $forum->get_link_params(mod_forumng::PARAM_PLAIN));
        }
    } else if ($fromform = $mform->get_data()) {
        // Set up values which might not be defined
        if ($ispost) {
            // Blank subject counts as null
            if (trim($fromform->subject)==='') {
                $fromform->subject = null;
            }

            if (!isset($fromform->mailnow)) {
                $fromform->mailnow = false;
            }

            if (!isset($fromform->setimportant)) {
                $fromform->setimportant = false;
            }
        }
        if ($isdiscussion) {
            if (!isset($fromform->timestart)) {
                $fromform->timestart = 0;
            }
            if (!isset($fromform->timeend)) {
                $fromform->timeend = 0;
            }
            if (!isset($fromform->sticky)) {
                $fromform->sticky = false;
            }
            if (!isset($fromform->tags)) {
                $fromform->tags = null;
            } else if (empty($fromform->tags)) {
                $fromform->tags = array();
            }
            // The form time is midnight, but because we want it to be
            // inclusive, set it to 23:59:59 on that day.
            if ($fromform->timeend) {
                $fromform->timeend = strtotime('23:59:59', $fromform->timeend);
            }
        }
        if (!isset($fromform->asmoderator)) {
            $fromform->asmoderator = 0;
        }
        $hasattachments = false;
        if (isset($fromform->attachments)) {
            $usercontext = context_user::instance($USER->id);
            $fs = get_file_storage();
            $hasattachments = count($fs->get_area_files($usercontext->id, 'user', 'draft',
                    $fromform->attachments, 'id'));
        }

        $savedraft = isset($fromform->savedraft);
        if ($savedraft) {
            $options = new stdClass;
            if (isset($fromform->timestart)) {
                $options->timestart = $fromform->timestart;
            }
            if (isset($fromform->timeend)) {
                $options->timeend = $fromform->timeend;
            }
            if (isset($fromform->sticky)) {
                $options->sticky = $fromform->sticky;
            }
            if (isset($fromform->asmoderator)) {
                $options->asmoderator = $fromform->asmoderator;
            }
            if (isset($fromform->mailnow)) {
                $options->mailnow = $fromform->mailnow;
            }
            if (isset($fromform->setimportant)) {
                $options->setimportant = $fromform->setimportant;
            }
            $date = get_string('draftexists', 'forumng',
                mod_forumng_utils::display_date(time()));
            if ($draft) {
                // This is an update of the existing draft
                $transaction = $DB->start_delegated_transaction();

                // Save any changes to files
                if (isset($fromform->attachments)) {
                    file_save_draft_area_files($fromform->attachments, $filecontext->id, 'mod_forumng',
                            'draft', $draft->get_id(), $fileoptions);
                }
                if (!empty($fromform->message['itemid'])) {
                    $fromform->message['text'] = file_save_draft_area_files($fromform->message['itemid'],
                            $filecontext->id, 'mod_forumng', 'draftmessage',
                            $draft->get_id(), $fileoptions, $fromform->message['text']);
                }

                // Update the draft itself
                $draft->update(
                    $fromform->subject, $fromform->message['text'], $fromform->message['format'],
                    $hasattachments,
                    $isdiscussion && $fromform->group ? $fromform->group : null, $options);

                // Redirect to edit it again
                $transaction->allow_commit();
                finish(0, $cloneid, 'editpost.php?draft=' . $draft->get_id() .
                        $forum->get_clone_param(mod_forumng::PARAM_PLAIN) .
                        $expandparam, $fromform, $draft->get_id() . ':' . $date, true);
            } else {
                // This is a new draft
                $transaction = $DB->start_delegated_transaction();

                // Save the draft
                $newdraftid = mod_forumng_draft::save_new(
                    $forum,
                    $isdiscussion ? $groupid : null,
                    $replytoid ? $replytoid : null,
                    $fromform->subject,
                    $fromform->message['text'], $fromform->message['format'],
                    $hasattachments, $options);

                // Save any attachments
                if (isset($fromform->attachments)) {
                    file_save_draft_area_files($fromform->attachments, $filecontext->id, 'mod_forumng',
                            'draft', $newdraftid, $fileoptions);
                }
                if (!empty($fromform->message['itemid'])) {
                    $newtext = file_save_draft_area_files($fromform->message['itemid'],
                            $filecontext->id, 'mod_forumng', 'draftmessage', $newdraftid, $fileoptions,
                            $fromform->message['text']);
                    if ($newtext !== $fromform->message['text']) {
                        mod_forumng_draft::update_message_for_files($newdraftid, $newtext);
                    }
                }

                // Redirect to edit it again
                $transaction->allow_commit();
                finish(0, $cloneid, 'editpost.php?draft=' . $newdraftid .
                        $forum->get_clone_param(mod_forumng::PARAM_PLAIN) .
                        $expandparam, $fromform, $newdraftid . ':' . $date, true);
            }
        } else if (!$edit) {
            // Check the random number is unique in session
            $random = optional_param('random', 0, PARAM_INT);
            if ($random) {
                if (!isset($SESSION->forumng_createdrandoms)) {
                    $SESSION->forumng_createdrandoms = array();
                }
                $now = time();
                foreach ($SESSION->forumng_createdrandoms as $r => $then) {
                    // Since this is meant to stop you clicking twice quickly,
                    // expire anything older than 1 minute
                    if ($then < $now - 60) {
                        unset($SESSION->forumng_createdrandoms[$r]);
                    }
                }
                if (isset($SESSION->forumng_createdrandoms[$random])) {
                    print_error('error_duplicate', 'forumng',
                            $forum->get_url(mod_forumng::PARAM_PLAIN));
                }
                $SESSION->forumng_createdrandoms[$random] = $now;
            }

            // Creating new
            if ($isdiscussion) {
                $transaction = $DB->start_delegated_transaction();
                // Create new discussion
                list($discussionid, $postid) =
                    $forum->create_discussion($groupid,
                            $fromform->subject, $fromform->message['text'],
                            $fromform->message['format'], $hasattachments, !empty($fromform->mailnow),
                            $fromform->timestart, $fromform->timeend, false, $fromform->sticky,
                            0, true, $fromform->asmoderator, $fromform->tags);

                // Save attachments
                if (isset($fromform->attachments)) {
                    file_save_draft_area_files($fromform->attachments, $filecontext->id, 'mod_forumng',
                            'attachment', $postid, $fileoptions);
                }
                $newtext = file_save_draft_area_files($fromform->message['itemid'],
                        $filecontext->id, 'mod_forumng', 'message', $postid, $fileoptions,
                        $fromform->message['text']);
                if ($newtext !== $fromform->message['text']) {
                    mod_forumng_post::update_message_for_files($postid, $newtext);
                }

                // If there's a draft, delete it
                if ($draft) {
                    $draft->delete($filecontext);
                }

                // Redirect to view discussion
                $transaction->allow_commit();
                finish($postid, $cloneid, 'discuss.php?d=' . $discussionid .
                        $forum->get_clone_param(mod_forumng::PARAM_PLAIN) .
                        $expandparam, $fromform);
            } else if ($islock) {
                // Create a new lock post
                $transaction = $DB->start_delegated_transaction();
                $postid = $discussion->lock($fromform->subject, $fromform->message['text'],
                        $fromform->message['format'], $hasattachments, !empty($fromform->mailnow),
                        0, true, $fromform->asmoderator);

                // Save attachments
                if (isset($fromform->attachments)) {
                    file_save_draft_area_files($fromform->attachments, $filecontext->id, 'mod_forumng',
                            'attachment', $postid, $fileoptions);
                }
                $newtext = file_save_draft_area_files($fromform->message['itemid'],
                        $filecontext->id, 'mod_forumng', 'message', $postid, $fileoptions,
                        $fromform->message['text']);
                if ($newtext !== $fromform->message['text']) {
                    mod_forumng_post::update_message_for_files($postid, $newtext);
                }

                // Redirect to view discussion
                $transaction->allow_commit();
                finish($postid, $cloneid, 'discuss.php?' .
                        $replyto->get_discussion()->get_link_params(mod_forumng::PARAM_PLAIN) .
                        $expandparam, $fromform);
            } else {
                // Create a new reply
                $transaction = $DB->start_delegated_transaction();

                $postid = $replyto->reply($fromform->subject, $fromform->message['text'],
                        $fromform->message['format'], $hasattachments, !empty($fromform->setimportant),
                        !empty($fromform->mailnow), 0, true, $fromform->asmoderator);

                // Save attachments
                if (isset($fromform->attachments)) {
                    file_save_draft_area_files($fromform->attachments, $filecontext->id, 'mod_forumng',
                            'attachment', $postid, $fileoptions);
                }
                if (!empty($fromform->message['itemid'])) {
                    $newtext = file_save_draft_area_files($fromform->message['itemid'],
                            $filecontext->id, 'mod_forumng', 'message', $postid, $fileoptions,
                            $fromform->message['text']);
                    if ($newtext !== $fromform->message['text']) {
                        mod_forumng_post::update_message_for_files($postid, $newtext);
                    }
                }

                // If there's a draft, get rid of it
                if ($draft) {
                    $draft->delete($filecontext);
                }

                // Redirect to view discussion
                $transaction->allow_commit();
                finish($postid, $cloneid, 'discuss.php?' .
                        $replyto->get_discussion()->get_link_params(mod_forumng::PARAM_PLAIN) .
                        $expandparam . '#p' . $postid, $fromform);
            }
        } else {
            // Editing

            // Group changes together
            $transaction = $DB->start_delegated_transaction();

            // 1. Edit post if applicable
            if ($ispost) {
                $gotsubject = $post->edit_start($fromform->subject, $hasattachments,
                        !empty($fromform->setimportant), !empty($fromform->mailnow),
                        0, true, $fromform->asmoderator);

                if (isset($fromform->attachments)) {
                    file_save_draft_area_files($fromform->attachments, $filecontext->id, 'mod_forumng',
                            'attachment', $post->get_id(), $fileoptions);
                }
                // itemid is not present when using text-only editor
                if (!empty($fromform->message['itemid'])) {
                    $fromform->message['text'] = file_save_draft_area_files($fromform->message['itemid'],
                            $filecontext->id, 'mod_forumng', 'message', $postid, $fileoptions,
                            $fromform->message['text']);
                }

                $post->edit_finish($fromform->message['text'], $fromform->message['format'],
                        $gotsubject);

                if (!empty($fromform->emailauthor)) {
                    send_edit_email($fromform, $post);
                }
            }

            // 2. Edit discussion settings if applicable
            if ($isdiscussion) {
                $discussion = $post->get_discussion();
                $groupid = isset($fromform->group) ? $fromform->group
                    : $discussion->get_group_id();
                $discussion->edit_settings($groupid, $fromform->timestart,
                    $fromform->timeend, $discussion->is_locked(),
                    !empty($fromform->sticky), $fromform->tags);
            }

            // Redirect to view discussion
            $transaction->allow_commit();
            finish($post->get_id(), $cloneid, 'discuss.php?' .
                $post->get_discussion()->get_link_params(mod_forumng::PARAM_PLAIN) .
                $expandparam . '#p' . $post->get_id(),
                $fromform);
        }

    } else {
        if ($ajax) {
            // If this is an AJAX request we can't go printing the form, this
            // must be an error
            header('Content-Type: text/plain', true, 500);
            print 'Form redisplay attempt';
            exit;
        }
        $navigation = array();

        // Include link to discussion except when creating new discussion
        if (!$isdiscussion || $edit) {
            $PAGE->navbar->add(shorten_text(s($discussion->get_subject())),
                    $discussion->get_url(mod_forumng::PARAM_HTML));
        }
        $PAGE->navbar->add($pagename);

        $buttontext = '';

        $PAGE->set_heading($course->fullname);
        $PAGE->set_title(format_string($forum->get_name()) . ': ' . $pagename);
        $PAGE->set_button($buttontext);

        $out = mod_forumng_utils::get_renderer();
        print $out->header();

        print $out->skip_link_target();

        // If replying, print original post here
        if (!$isdiscussion && !$edit && !$islock && !$iframe) {
            print '<div class="forumng-replyto">' .
                $replyto->display(true,
                    array(mod_forumng_post::OPTION_NO_COMMANDS=>true,
                        // Hack, otherwise it requires whole-discussion info
                        // Should really have a OPTION_SINGLE_POST which would
                        // have the same effect and be more logical/reusable
                        mod_forumng_post::OPTION_FIRST_UNREAD=>false)) .
                '</div>';
        }

        // If draft has been saved, print that here
        if ($draft) {
            print '<div class="forumng-draftexists">'.
                get_string('draftexists', 'forumng',
                    mod_forumng_utils::display_date($draft->get_saved())) . '</div>';
        }

        // Set up initial data
        $initialvalues = new stdClass;
        if ($edit) {
            // Work out initial values for all form fields
            if ($isdiscussion) {
                $initialvalues->timestart = $discussion->get_time_start();
                $initialvalues->timeend = $discussion->get_time_end();
                $initialvalues->sticky = $discussion->is_sticky() ? 1 : 0;
                $initialvalues->groupid = $discussion->get_group_id();
                $groupid = $discussion->get_group_id();
            }
            $initialvalues->subject = $post->get_subject();
            $initialvalues->message = array('text'=>$post->get_raw_message(),
                    'format'=>$post->get_format());
            $initialvalues->setimportant = $post->is_important();

            $draftitemid = file_get_submitted_draft_itemid('attachments');
            file_prepare_draft_area($draftitemid, $filecontext->id, 'mod_forumng',
                    'attachment', $post->get_id(), $fileoptions);
            $initialvalues->attachments = $draftitemid;
            $initialvalues->asmoderator = $post->get_asmoderator();

            $messagedraftitemid = file_get_submitted_draft_itemid('message');
            $initialvalues->message['text'] = file_prepare_draft_area($messagedraftitemid,
                    $filecontext->id, 'mod_forumng', 'message', $post->get_id(), $fileoptions,
                    $initialvalues->message['text']);
            $initialvalues->message['itemid'] = $messagedraftitemid;
        }
        if ($draft) {
            $initialvalues->subject = $draft->get_subject();
            $initialvalues->message = array('text'=>$draft->get_raw_message(),
                    'format'=>$draft->get_format());
            if ($isdiscussion) {
                $initialvalues->groupid = $draft->get_group_id();
            }
            if ($options = $draft->get_options()) {
                if (isset($options->timestart)) {
                    $initialvalues->timestart = $options->timestart;
                }
                if (isset($options->timeend)) {
                    $initialvalues->timeend = $options->timeend;
                }
                if (isset($options->sticky)) {
                    $initialvalues->sticky = $options->sticky;
                }
                if (isset($options->mailnow)) {
                    $initialvalues->mailnow = $options->mailnow;
                }
                if (isset($options->setimportant)) {
                    $initialvalues->setimportant = $options->setimportant;
                }
            }
            $draftitemid = file_get_submitted_draft_itemid('attachments');
            file_prepare_draft_area($draftitemid, $filecontext->id, 'mod_forumng',
                    'draft', $draft->get_id(), $fileoptions);
            $initialvalues->attachments = $draftitemid;

            $messagedraftitemid = file_get_submitted_draft_itemid('message');
            $initialvalues->message = file_prepare_draft_area($messagedraftitemid, $filecontext->id,
                    'mod_forumng', 'draftmessage', $draft->get_id(), $fileoptions,
                    $initialvalues->message);
            $initialvalues->message['itemid'] = $messagedraftitemid;
        }
        if ($edit || $draft) {
            $mform->set_data($initialvalues);
        } else {
            $draftitemid = file_get_submitted_draft_itemid('attachments');
            file_prepare_draft_area($draftitemid, $filecontext->id, 'mod_forumng',
                    'attachment', null, $fileoptions);
            $initialvalues->attachments = $draftitemid;

            $messagedraftitemid = file_get_submitted_draft_itemid('message');
            file_prepare_draft_area($messagedraftitemid, $filecontext->id,
                    'mod_forumng', 'message', 0, $fileoptions);
            $initialvalues->message = array('text' => '', 'format' => editors_get_preferred_format(),
                    'itemid'=> $messagedraftitemid);

            $mform->set_data($initialvalues);
        }

        // Require JavaScript (form.js).
        $forum->print_form_js();

        // Print form
        $mform->display();

        // In iframe mode, inform parent that iframe has loaded.
        if ($iframe) {
            $PAGE->requires->js_init_code('window.parent.iframe_has_loaded(window);', true);
        }

        $PAGE->requires->strings_for_js(array('savefailtitle', 'savefailnetwork', 'numberofdiscussions'), 'forumng');
        $PAGE->requires->yui_module('moodle-mod_forumng-savecheck', 'M.mod_forumng.savecheck.init',
                array($forum->get_context()->id));

        // Display footer
        print $out->footer();
    }
} catch (Exception $e) {
    // Add special entry to log
    mod_forumng_utils::log_exception($e);

    // Let default exception handler cope with it
    throw $e;
}
