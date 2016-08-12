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
 * Administration settings form.
 * @package mod
 * @subpackage forumng
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
$plugin = new stdClass();
require_once($CFG->dirroot . '/mod/forumng/settingslib.php');
require_once($CFG->dirroot.'/mod/forumng/version.php');

if (!empty($plugin->release)) {
$settings->add(new admin_setting_heading('forumng_version', '',
    get_string('displayversion', 'forumng', $plugin->release)));
}

$settings->add(new admin_setting_configcheckbox('forumng_replytouser',
    get_string('replytouser', 'forumng'),
    get_string('configreplytouser', 'forumng'), 1));

$settings->add(new admin_setting_configtext('forumng_usebcc',
    get_string('usebcc', 'forumng'),
    get_string('configusebcc', 'forumng'), 0, PARAM_INT));

$settings->add(new admin_setting_configtext('forumng_donotmailafter',
    get_string('donotmailafter', 'forumng'),
    get_string('configdonotmailafter', 'forumng'), 48, PARAM_INT));

    // Number of discussions on a page
$settings->add(new admin_setting_configtext('forumng_discussionsperpage',
    get_string('discussionsperpage', 'forumng'),
    get_string('configdiscussionsperpage', 'forumng'), 20, PARAM_INT));
if (isset($CFG->maxbytes)) {
    $sizes = get_max_upload_sizes($CFG->maxbytes);
} else {
    $sizes = get_max_upload_sizes(0);
}
unset($sizes[0]);
$sizes[-1] = get_string('forbidattachments', 'forumng');

$settings->add(new admin_setting_configselect('forumng_attachmentmaxbytes',
    get_string('attachmentmaxbytes', 'forumng'),
    get_string('configattachmentmaxbytes', 'forumng'), 512000, $sizes));

// Option about read tracking
$settings->add(new admin_setting_configcheckbox('forumng_trackreadposts',
    get_string('trackreadposts', 'forumng'), get_string('configtrackreadposts', 'forumng'), 1));

// Number of days that a post is considered old and we don't store unread data
$settings->add(new admin_setting_configtext('forumng_readafterdays',
    get_string('readafterdays', 'forumng'),
    get_string('configreadafterdays', 'forumng'), 60, PARAM_INT));

// RSS feeds
if (empty($CFG->enablerssfeeds)) {
    $options = array(0 => get_string('rssglobaldisabled', 'admin'));
    $str = get_string('configenablerssfeeds', 'forumng').'<br />'.
        get_string('configenablerssfeedsdisabled2', 'admin');
} else {
    $options = array(0=>get_string('no'), 1=>get_string('yes'));
    $str = get_string('configenablerssfeeds', 'forumng');
}
$settings->add(new admin_setting_configselect('forumng_enablerssfeeds',
    get_string('enablerssfeeds', 'admin'), $str, 0, $options));

$settings->add(new forumng_admin_setting_configselect_subscription('forumng_subscription',
    get_string('subscription', 'forumng'),
    get_string('configsubscription', 'forumng'), -1, null));

$settings->add(new forumng_admin_setting_configselect_feedtype('forumng_feedtype',
    get_string('feedtype', 'forumng'),
    get_string('configfeedtype', 'forumng'), -1, null));

$settings->add(new forumng_admin_setting_configselect_feeditems('forumng_feeditems',
    get_string('feeditems', 'forumng'),
    get_string('configfeeditems', 'forumng'), -1, null));

$options = array(
    0=>get_string('permanentdeletion_never', 'forumng'),
    1=>get_string('permanentdeletion_soon', 'forumng'),
    1*60*60*24=>'1 '.get_string('day'),
    14*60*60*24=>'14 '.get_string('days'),
    30*60*60*24=>'30 '.get_string('days'),
    365*60*60*24=>'1 '.get_string('year'));
$settings->add(new admin_setting_configselect('forumng_permanentdeletion',
    get_string('permanentdeletion', 'forumng'),
    get_string('configpermanentdeletion', 'forumng'), 30*60*60*24, $options));

// Option about read tracking
$settings->add(new admin_setting_configcheckbox('forumng_showusername',
    get_string('showusername', 'forumng'),
    get_string('configshowusername', 'forumng'), 0));
$settings->add(new admin_setting_configcheckbox('forumng_showidnumber',
    get_string('showidnumber', 'forumng'),
    get_string('configshowidnumber', 'forumng'), 0));

$settings->add(new admin_setting_configtext('forumng_reportunacceptable',
        get_string('reportunacceptable', 'forumng'),
        get_string('configreportunacceptable', 'forumng'), '', PARAM_NOTAGS));

$settings->add(new admin_setting_configcheckbox('forumng_enableadvanced',
    get_string('enableadvanced', 'forumng'),
    get_string('configenableadvanced', 'forumng'), 0));

$settings->add(new admin_setting_configduration('forumng_emailafter',
    get_string('emailafter', 'forumng'),
    get_string('configemailafter', 'forumng'), 120));
