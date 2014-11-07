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
 * Delete or undelete a post (AJAX or standard).
 * @package mod
 * @subpackage forumng
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');
require_once('mod_forumng.php');

// Get AJAX parameter which might affect error handling
$ajax = optional_param('ajax', 0, PARAM_INT);

// Post ID
$postid = required_param('p', PARAM_INT);
$cloneid = optional_param('clone', 0, PARAM_INT);

// Delete or undelete
$delete = optional_param('delete', 1, PARAM_INT);

// Email author
$email = optional_param('email', 0, PARAM_INT);

// Were the posts expanded?
$expand = optional_param('expand', 0, PARAM_INT);
$expandparam = $expand ? '&expand=1' : '';

$pageparams = array('p'=>$postid);
if ($cloneid) {
    $pageparams['clone'] = $cloneid;
}
if ($delete != 1) {
    $pageparams['delete'] = $delete;
}
if ($ajax) {
    $pageparams['ajax'] = $ajax;
}
if ($expand) {
    $pageparams['expand'] = $expand;
}

$post = mod_forumng_post::get_from_id($postid, $cloneid);

// Get convenience variables
$discussion = $post->get_discussion();
$forum = $post->get_forum();
$course = $forum->get_course();
$cm = $forum->get_course_module();

// Set up page
$pagename = get_string($delete ? 'deletepost' : 'undeletepost', 'forumng',
    $post->get_effective_subject(true));
$url = new moodle_url('/mod/forumng/deletepost.php', $pageparams);
$out = $discussion->init_page($url, $pagename);

// Do all access security checks
$post->require_view();
if ($delete) {
    if (!$post->can_delete($whynot)) {
        print_error($whynot, 'forumng');
    }
} else {
    if (!$post->can_undelete($whynot)) {
        print_error($whynot, 'forumng');
    }
}

// Is this the actual delete?
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $email != 1) {
    // Delete or undelete the post
    if ($delete) {
        $post->delete();
    } else {
        $post->undelete();
    }

    // Redirect back
    if ($ajax) {
        mod_forumng_post::print_for_ajax_and_exit($postid, $cloneid);
    }

    // Only include post id if user can see deleted posts
    $postid = '';
    if (!$delete || has_capability('mod/forumng:editanypost', $forum->get_context())) {
        $postid = '#p' . $post->get_id();
    }

    redirect('discuss.php?' . $discussion->get_link_params(mod_forumng::PARAM_PLAIN) . $expandparam . $postid);
}

if ($email) {
    require_once('deletepost_form.php');

    $urlparams = array('p' => $postid, 'delete' => $delete, 'email' => $email);
    if ($cloneid) {
        $urlparams['clone'] = $cloneid;
    }

    $url = new moodle_url("{$CFG->wwwroot}/mod/forumng/deletepost.php", $urlparams);
    $mform = new mod_forumng_deletepost_form($url);

    if ($mform->is_cancelled()) {
        // Form is cancelled, redirect back to the discussion.
        redirect('discuss.php?' . $discussion->get_link_params(mod_forumng::PARAM_PLAIN) . $expandparam);

    } else if ($submitted = $mform->get_data()) {
        // Store copy of the post for the author.
        $messagepost = $post->display(true, array(mod_forumng_post::OPTION_NO_COMMANDS => true,
                mod_forumng_post::OPTION_SINGLE_POST => true));

        // Delete the post
        $post->delete();

        // Set up the email.
        $messagetext = $submitted->message['text'];
        $copyself = (isset($submitted->copyself))? true : false;
        $includepost = (isset($submitted->includepost))? true : false;
        $user = $post->get_user();
        $from = $SITE->fullname;
        $subject = get_string('deletedforumpost', 'forumng');
        $message = html_to_text($messagetext);

        // Always enable HTML version
        $messagehtml = $out->deletion_email(text_to_html($messagetext));

        // Include the copy of the post in the email to the author.
        if ($includepost) {
            $messagehtml .= $messagepost;
            $message .=  $post->display(false, array(mod_forumng_post::OPTION_NO_COMMANDS => true,
                mod_forumng_post::OPTION_SINGLE_POST => true));
        }

        // Send an email to the author of the post.
        if (!email_to_user($user, $from, $subject, $message, $messagehtml)) {
            print_error(get_string('emailerror', 'forumng'));
        }

        // Prepare for copies.
        $emails = array();
        $subject = strtoupper(get_string('copy')) . ' - ' . $subject;
        if ($copyself) {
            // Send an email copy to the current user, with prefered format.
            if (!email_to_user($USER, $from, $subject, $message, $messagehtml)) {
                print_error(get_string('emailerror', 'forumng'));
            }
        }

        // Addition of 'Email address of other recipients'.
        if (!empty($submitted->emailadd)) {
            $emails = preg_split('~[; ]+~', $submitted->emailadd);
        }

        // If there are any recipients listed send them an HTML copy.
        if (!empty($emails[0])) {
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

        // redirect back to the discussion.
        redirect('discuss.php?' . $discussion->get_link_params(mod_forumng::PARAM_PLAIN) . $expandparam);
    }
}

// Confirm page. Work out navigation for header
print $out->header();

// Include forum JS
$forum->print_js($cm->id, true);

if ($email) {
    // prepare the object for the get_string
    $emailmessage = new stdClass;
    $emailmessage->subject = $post->get_effective_subject(true);
    $emailmessage->firstname = $USER->firstname;
    $emailmessage->lastname = $USER->lastname;
    $emailmessage->course = $COURSE->fullname;
    $emailmessage->forum = $post->get_forum()->get_name();
    $emailmessage->deleteurl = $CFG->wwwroot . '/mod/forumng/discuss.php?' .
            $discussion->get_link_params(mod_forumng::PARAM_PLAIN);
    $formdata = new stdClass;

    // Use the plain
    $messagetext = get_string('emailcontentplain', 'forumng', $emailmessage);

    $formdata->message['text'] = $messagetext;
    $formdata->expand = $expand;

    $mform->set_data($formdata);
    $mform->display();
    // output the html for use when JS is enabled
    echo $out->delete_form_html(get_string('emailcontenthtml', 'forumng', $emailmessage));
} else {
    // Show confirm option
    if ($delete) {
        $confirmstring = get_string('confirmdelete', 'forumng');
        if ($post->is_root_post()) {
            $confirmstring .= ' ' . get_string('confirmdelete_nodiscussion', 'forumng');
        }

        $deletebutton = new single_button(new moodle_url('/mod/forumng/deletepost.php',
                        array('p'=>$post->get_id(), 'delete'=>$delete,
                        'clone'=>$cloneid, 'expand'=>$expand)),
                        $delete ? get_string('delete') : get_string('undelete', 'forumng'),
                        'post');
        $cancelbutton = new single_button(new moodle_url('/mod/forumng/discuss.php',
                        array('d'=>$discussion->get_id(), 'clone'=>$cloneid, 'expand'=>$expand)),
                        get_string('cancel'), 'get');
        if ($USER->id == $post->get_user()->id) {
            print $out->confirm($confirmstring, $deletebutton, $cancelbutton);
        } else {
            print $out->confirm_three_button($confirmstring,
                    new single_button(new moodle_url('/mod/forumng/deletepost.php',
                        array('p'=>$post->get_id(), 'delete'=>$delete,
                        'clone'=>$cloneid, 'email' => 1, 'expand'=>$expand)),
                        $delete ? get_string('deleteemailpostbutton', 'forumng') :
                        get_string('undelete', 'forumng'), 'post'),
                    $deletebutton,
                    $cancelbutton);
        }
    } else {
        $confirmstring = get_string('confirmundelete', 'forumng');
        print $out->confirm($confirmstring,
                new single_button(new moodle_url('/mod/forumng/deletepost.php',
                    array('p'=>$post->get_id(), 'delete'=>$delete,
                    'clone'=>$cloneid, 'expand'=>$expand)),
                    $delete ? get_string('delete') : get_string('undelete', 'forumng'), 'post'),
                new single_button(new moodle_url('/mod/forumng/discuss.php',
                    array('d'=>$discussion->get_id(), 'clone'=>$cloneid, 'expand'=>$expand)),
                    get_string('cancel'), 'get'));
    }

}

// Print post
print $post->display(true, array(mod_forumng_post::OPTION_NO_COMMANDS => true,
        mod_forumng_post::OPTION_SINGLE_POST => true));

// Display footer
print $out->footer();
