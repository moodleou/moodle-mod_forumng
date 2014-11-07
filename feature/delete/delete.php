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
 * Deletes a discussion after confirm.
 * @package forumngfeature
 * @subpackage delete
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../../config.php');
require_once($CFG->dirroot . '/mod/forumng/mod_forumng.php');

$d = required_param('d', PARAM_INT);
$pageparams = array('d' => $d);
$cloneid = optional_param('clone', 0, PARAM_INT);
if ($cloneid) {
    $pageparams['clone'] = $cloneid;
}
$delete = required_param('delete', PARAM_INT);
$pageparams['delete'] = $delete;

$email = optional_param('email', 0, PARAM_INT);
$pageparams['email'] = $email;

$expand = optional_param('expand', 0, PARAM_INT);
$expandparam = $expand ? '&expand=1' : '';

$notdeleted = optional_param('notdeleted', 0, PARAM_INT);

$discussion = mod_forumng_discussion::get_from_id($d, $cloneid);
$forum = $discussion->get_forum();
$cm = $forum->get_course_module();
$course = $forum->get_course();

// Let discussion author delete if allowed, otherwise check permission for change.
if ($USER->id != $discussion->get_poster()->id || $discussion->get_root_post()->has_children() ||
        !$discussion->get_root_post()->can_edit($whynot)) {
    $discussion->require_edit();
}

// Set up page.
$pagename = get_string(
        $delete ? 'deletediscussion' : 'undeletediscussion', 'forumngfeature_delete');
$url = new moodle_url('/mod/forumng/feature/delete/delete.php', $pageparams);
$out = $discussion->init_page($url, $pagename);

if ($email) {
    require_once('deletediscussion_form.php');

    $urlparams = array('d' => $d, 'delete' => $delete, 'email' => $email);
    if ($cloneid) {
        $urlparams['clone'] = $cloneid;
    }

    $contributors = false;
    if (count(get_contributor_ids($discussion)) > 1) {
        $contributors = true;
    }
    $urlparams['contributors'] = $contributors;

    $mform = new mod_forumng_deletediscussion_form($url, $urlparams);

    if ($mform->is_cancelled()) {
        // Form is cancelled, redirect back to the discussion.
        redirect('../../discuss.php?' . $discussion->get_link_params(mod_forumng::PARAM_PLAIN) . $expandparam);
    } else if ($submitted = $mform->get_data()) {
        // Delete the discussion.
        $discussion->delete();

        // Set up the email.
        $messagetext = $submitted->message['text'];
        $copyself = (isset($submitted->copyself))? true : false;
        $post = $discussion->get_root_post();
        $user = $post->get_user();
        $from = $SITE->fullname;
        $subject = get_string('deletedforumpost', 'forumng');
        $notifymessagetext = '';
        $notifycontributors = (isset($submitted->notifycontributors))? true : false;
        if (isset($submitted->notifymessage['text'])) {
            $notifymessagetext = $submitted->notifymessage['text'];
        }

        // Always enable HTML version.
        $messagehtml = $out->deletion_email(text_to_html($messagetext));
        $notifymessagehtml = $out->deletion_email(text_to_html($notifymessagetext));

        // Send an email to the author of the discussion post, using prefered format.
        if (!email_to_user($user, $from, $subject, html_to_text($messagetext), $messagehtml)) {
            print_error(get_string('emailerror', 'forumng'));
        }

        // Get copy email addresses.
        $contribemails = $emails = $selfmail = $contributorsemails = array();
        // Prepare for copies.
        $subject = strtoupper(get_string('copy')) . ' - ' . $subject;
        if ($copyself) {
            // Send an email copy to the current user, with prefered format.
            if (!email_to_user($USER, $from, $subject, html_to_text($messagetext), $messagehtml)) {
                print_error(get_string('emailerror', 'forumng'));
            }
            $selfmail[] = $USER->email;
        }
        // Addition of 'Email address of other recipients'.
        if (!empty($submitted->emailadd)) {
            $emails = preg_split('~[; ]+~', $submitted->emailadd);
        }

        // If there are any contributors notify them (if sent delete copy email won't).
        if ($notifycontributors) {
            $contribemails = array_merge($emails, $selfmail);
            $contribemails = array_merge($contribemails, array($user->email));
            $contributorsemails = get_posts_discussion_email_details($discussion, $contribemails);
        }
        // Send copy HTML emails.
        if (!empty($emails)) {
            foreach ($emails as $email) {
                $fakeuser = (object)array(
                        'email' => $email,
                        'mailformat' => 1,
                        'id' => -1
                );
                if (!email_to_user($fakeuser, $from, $subject, '', $messagehtml)) {
                    print_error(get_string('emailerror', 'forumng'));
                }
            }
        }
        // Send contributor emails, using prefered format.
        if (!empty($contributorsemails)) {
            $subject = get_string('deletedforumpost', 'forumng');
            foreach ($contributorsemails as $contrib) {
                if (isset($contrib['email'])) {
                    $fakeuser = (object)array(
                        'email' => $contrib['email'],
                        'mailformat' => $contrib['mailformat'],
                        'id' => -1
                    );
                    if (!email_to_user($fakeuser, $from, $subject, html_to_text($notifymessagetext), $notifymessagehtml)) {
                        print_error(get_string('emailerror', 'forumng'));
                    }
                }
            }
        }

        // Redirect back to the forum view.
        redirect('../../view.php?' . $forum->get_link_params(mod_forumng::PARAM_PLAIN) . $expandparam);
    }
}

// Is this the actual delete?
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!$email) {
        if ($delete) {
            $discussion->delete();
            redirect($forum->get_url(mod_forumng::PARAM_PLAIN));
        } else {
            $discussion->undelete();
            redirect('../../discuss.php?' . $discussion->get_link_params(mod_forumng::PARAM_PLAIN));
        }
    }
}

// Confirm page. Work out navigation for header.
print $out->header();

if ($email) {
    // Prepare the object for the get_string.
    $emailmessage = new stdClass();
    $emailmessage->subject = $discussion->get_subject();
    $emailmessage->firstname = $USER->firstname;
    $emailmessage->lastname = $USER->lastname;
    $emailmessage->course = $COURSE->fullname;
    $emailmessage->forum = $forum->get_name();

    $formdata = new stdClass();
    // Use the plain.
    $formdata->message['text'] = get_string('emailcontenthtml', 'forumngfeature_delete', $emailmessage);
    $formdata->notifymessage['text'] = get_string('notifycontributorsemailcontenthtml', 'forumngfeature_delete', $emailmessage);
    $formdata->expand = $expand;
    $mform->set_data($formdata);
    $mform->display();

} else {
    // Need to test for child posts and user id against creator id.
    $childposts = $discussion->get_root_post()->has_children();
    $creator = $discussion->get_poster();
    if (!$childposts && $creator->id == $USER->id) {
        $notdeleted = true;// Force no email option when not applicable.
    }

    // Show confirm options.
    if ($delete && !$notdeleted) {
        // Show confirm or email option.
        $confirmstring = get_string($delete ? 'confirmdeletediscussion'
                : 'confirmundeletediscussion', 'forumngfeature_delete');
        $deletebutton = new single_button(new moodle_url('/mod/forumng/feature/delete/delete.php',
                array('d' => $discussion->get_id(), 'delete' => $delete, 'clone' => $cloneid)),
                $delete ? get_string('delete') : get_string('undelete', 'forumng'), 'post');
        $deleteandemailbutton = new single_button(new moodle_url('/mod/forumng/feature/delete/delete.php',
                array('d' => $discussion->get_id(), 'delete' => $delete, 'clone' => $cloneid, 'email' => 1)),
                get_string('deleteandemail', 'forumngfeature_delete'), 'post');
        $cancelbutton = new single_button(new moodle_url('/mod/forumng/discuss.php',
                array('d' => $discussion->get_id(), 'clone' => $cloneid)),
                get_string('cancel'), 'get');
        print $out->confirm_three_button($confirmstring, $deleteandemailbutton, $deletebutton, $cancelbutton);
    } else {
        // No email option.
        $confirmstring = get_string($delete ? 'confirmdeletediscussion'
                : 'confirmundeletediscussion', 'forumngfeature_delete');
        print $out->confirm($confirmstring,
                new single_button(new moodle_url('/mod/forumng/feature/delete/delete.php',
                        array('d' => $discussion->get_id(), 'delete' => $delete, 'clone' => $cloneid)),
                        $delete ? get_string('delete') : get_string('undelete', 'forumng'), 'post'),
                new single_button(new moodle_url('/mod/forumng/discuss.php',
                        array('d' => $discussion->get_id(), 'clone' => $cloneid)),
                        get_string('cancel'), 'get'));
    }
}
// Display footer.
print $out->footer();

function get_contributor_ids($discussion) {
    $post = $discussion->get_root_post();
    $userids = array();
    // Get associative array of user ids.
    $post->list_all_user_ids($userids, true);
    // Remove duplicate user ids.
    $userids = array_keys($userids);
    array_unique($userids);
    return $userids;
}

/**
 * Gets emails for all contributors to discussion (where post not deleted)
 * If email is already in list to notify then ignore
 * @param object $discussion
 * @param array $emails
 * @return array
 */
function get_posts_discussion_email_details($discussion, $emails) {
    global $CFG;
    require_once($CFG->dirroot.'/user/lib.php');
    $contribemails = array();
    $userids = get_contributor_ids($discussion);
    // Get contributor details.
    $users = user_get_users_by_id($userids);
    foreach ($users as $user) {
        if (!in_array($user->email, $emails)) {
            $details = array();
            $details['email'] = $user->email;
            $details['mailformat'] = $user->mailformat;
            $details['username'] = $user->username;
            $contribemails[] = $details;
        }
    }
    return $contribemails;
}
