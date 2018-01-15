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
        'Forum list service' => array(
                'shortname' => 'forumlist',
                'functions' => array ('mod_forumng_get_forum_list'),
                'requiredcapability' => '',
                'restrictedusers' => 0,
                'enabled' => 1
        ),
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
        'mod_forumng_get_forum_list' => array(
                'classname'   => 'mod_forumng_external',
                'methodname'  => 'get_forum_list',
                'classpath'   => 'mod/forumng/externallib.php',
                'description' => 'Lists forums for user on course',
                'type'        => 'read'
        ),
        'mod_forumng_get_posts' => array(
                'classname'   => 'mod_forumng\local\external\get_posts',
                'methodname'  => 'get_posts',
                'description' => 'Get posts belong to discussion',
                'type'        => 'read',
                'ajax'        => true
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
);
