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

/**
 * Forum services declarations.
 *
 * @package mod_forumng
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$services = array(
        'ForumNG IPUD' => array(
                'shortname' => 'ipud',
                'functions' => array(
                        'mod_forumng_get_posts',
                        'mod_forumng_expand_post',
                        'mod_forumng_create_reply',
                        'mod_forumng_edit_post',
                        'mod_forumng_delete_post',
                        'mod_forumng_undelete_post'
                ),
                'requiredcapability' => '',
                'restrictedusers' => 0,
                'enabled' => 1
        ),
);

$functions = array(
        'mod_forumng_get_posts' => array(
                'classname'   => 'mod_forumng\local\external\get_posts',
                'methodname'  => 'get_posts',
                'description' => 'Get posts belong to discussion',
                'type'        => 'read',
                'ajax'        => true
        ),
        'mod_forumng_get_discussion' => array(
            'classname'   => 'mod_forumng\local\external\get_discussion',
            'methodname'  => 'get_discussion',
            'description' => 'Get discussion',
            'type'        => 'read',
            'capabilities' => 'mod/forumng:viewdiscussion',
            'services' => [MOODLE_OFFICIAL_MOBILE_SERVICE, 'local_mobile']
        ),
        'mod_forumng_expand_post' => array(
                'classname'   => 'mod_forumng\local\external\expand_post',
                'methodname'  => 'expand_post',
                'description' => 'Get information if post and its replies',
                'type'        => 'read',
                'ajax'        => true
        ),
        'mod_forumng_create_reply' => array(
                'classname'   => 'mod_forumng\local\external\create_reply',
                'methodname'  => 'create_reply',
                'description' => 'Create reply for post',
                'type'        => 'write',
                'ajax'        => true
        ),
        'mod_forumng_edit_post' => array(
                'classname'   => 'mod_forumng\local\external\edit_post',
                'methodname'  => 'edit_post',
                'description' => 'Edit post',
                'type'        => 'write',
                'ajax'        => true
        ),
        'mod_forumng_delete_post' => array(
                'classname'   => 'mod_forumng\local\external\delete_post',
                'methodname'  => 'delete_post',
                'description' => 'Delete post',
                'type'        => 'write',
                'ajax'        => true
        ),
        'mod_forumng_undelete_post' => array(
                'classname'   => 'mod_forumng\local\external\undelete_post',
                'methodname'  => 'undelete_post',
                'description' => 'Undelete post',
                'type'        => 'write',
                'ajax'        => true
        ),
        'mod_forumng_get_more_discussions' => array(
                'classname' => '\mod_forumng\local\external\more_discussions',
                'methodname' => 'more_discussions',
                'description' => 'Get more discussions for a forum user.',
                'type' => 'read',
                'capabilities' => 'mod/forumng:viewdiscussion',
                'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE, 'local_mobile')
        ),
        'mod_forumng_add_discussion' => array(
                'classname' => '\mod_forumng\local\external\add_discussion',
                'methodname' => 'add_discussion',
                'description' => 'Create or edit a discussion.',
                'type' => 'write',
                'capabilities' => 'mod/forumng:startdiscussion',
                'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE, 'local_mobile')
        ),
        'mod_forumng_reply' => array(
                'classname' => '\mod_forumng\local\external\reply',
                'methodname' => 'reply',
                'description' => 'Create or edit a post.',
                'type' => 'write',
                'capabilities' => 'mod/forumng:replypost',
                'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE, 'local_mobile')
        ),
        'mod_forumng_mark_read' => array(
                'classname' => '\mod_forumng\local\external\mark_read',
                'methodname' => 'mark_read',
                'description' => 'Mark a post or a discussion as read.',
                'type' => 'write',
                'capabilities' => 'mod/forumng:viewdiscussion',
                'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE, 'local_mobile')
        ),
        'mod_forumng_mark_all_post_read' => array(
                'classname' => '\mod_forumng\local\external\mark_all_post_read',
                'methodname' => 'mark_all_post_read',
                'description' => 'Mark all post read',
                'type' => 'write',
                'capabilities' => 'mod/forumng:viewdiscussion',
                'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE, 'local_mobile')
        ),
        'mod_forumng_lock_discussion' => array(
                'classname' => '\mod_forumng\local\external\lock_discussion',
                'methodname' => 'lock_discussion',
                'description' => 'Lock discussion in forum',
                'type' => 'write',
                'capabilities' => 'mod/forumng:viewdiscussion',
                'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE, 'local_mobile')
        ),
        'mod_forumng_delete_post_mobile' => array(
                'classname'   => 'mod_forumng\local\external\delete_post',
                'methodname'  => 'delete_post',
                'description' => 'Delete post',
                'type'        => 'write',
                'capabilities' => 'mod/forumng:replypost',
                'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE, 'local_mobile')
        ),
        'mod_forumng_undelete_post_mobile' => array(
                'classname'   => 'mod_forumng\local\external\undelete_post',
                'methodname'  => 'undelete_post',
                'description' => 'Undelete post',
                'type'        => 'write',
                'capabilities' => 'mod/forumng:replypost',
                'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE, 'local_mobile')
        ),
        'mod_forumng_add_draft' => array(
                'classname' => '\mod_forumng\local\external\add_draft',
                'methodname' => 'add_draft',
                'description' => 'Create or edit a draft.',
                'type' => 'write',
                'capabilities' => 'mod/forumng:startdiscussion',
                'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE, 'local_mobile')
        ),
        'mod_forumng_delete_draft' => array(
                'classname' => '\mod_forumng\local\external\delete_draft',
                'methodname' => 'delete_draft',
                'description' => 'Delete draft.',
                'type' => 'write',
                'capabilities' => 'mod/forumng:startdiscussion',
                'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE, 'local_mobile')
        ),
);
