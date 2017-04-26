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
 * Definition of log events
 *
 *
 * @package    mod_forumng
 * @copyright  2012 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $DB;

$logs = array(
    array('module' => 'forumng', 'action' => 'add', 'mtable' => 'forumng', 'field' => 'name'),
    array('module' => 'forumng', 'action' => 'add discussion', 'mtable' => 'forumng', 'field' => 'name'),
    array('module' => 'forumng', 'action' => 'add reply', 'mtable' => 'forumng', 'field' => 'name'),
    array('module' => 'forumng', 'action' => 'delete discussion', 'mtable' => 'forumng', 'field' => 'name'),
    array('module' => 'forumng', 'action' => 'delete post', 'mtable' => 'forumng_posts', 'field' => 'subject'),
    array('module' => 'forumng', 'action' => 'digest ok', 'mtable' => 'forumng', 'field' => 'name'),
    array('module' => 'forumng', 'action' => 'edit post', 'mtable' => 'forumng_posts', 'field' => 'subject'),
    array('module' => 'forumng', 'action' => 'error editpost', 'mtable' => 'forumng', 'field' => 'name'),
    array('module' => 'forumng', 'action' => 'extdashadd', 'mtable' => 'forumng', 'field' => 'name'),
    array('module' => 'forumng', 'action' => 'extdashremove', 'mtable' => 'forumng', 'field' => 'name'),
    array('module' => 'forumng', 'action' => 'forward discussion', 'mtable' => 'forumng', 'field' => 'name'),
    array('module' => 'forumng', 'action' => 'listdeleteddiscussions', 'mtable' => 'forumng', 'field' => 'name'),
    array('module' => 'forumng', 'action' => 'listdeletedposts', 'mtable' => 'forumng', 'field' => 'name'),
    array('module' => 'forumng', 'action' => 'lock discussion', 'mtable' => 'forumng', 'field' => 'name'),
    array('module' => 'forumng', 'action' => 'mail ok', 'mtable' => 'forumng', 'field' => 'name'),
    array('module' => 'forumng', 'action' => 'mail error', 'mtable' => 'forumng', 'field' => 'name'),
    array('module' => 'forumng', 'action' => 'merge discussion', 'mtable' => 'forumng', 'field' => 'name'),
    array('module' => 'forumng', 'action' => 'permdelete discussion', 'mtable' => 'forumng', 'field' => 'name'),
    array('module' => 'forumng', 'action' => 'report post', 'mtable' => 'forumng', 'field' => 'name'),
    array('module' => 'forumng', 'action' => 'split post', 'mtable' => 'forumng', 'field' => 'name'),
    array('module' => 'forumng', 'action' => 'subscribe', 'mtable' => 'forumng', 'field' => 'name'),
    array('module' => 'forumng', 'action' => 'unsubscribe', 'mtable' => 'forumng', 'field' => 'name'),
    array('module' => 'forumng', 'action' => 'update', 'mtable' => 'forumng', 'field' => 'name'),
    array('module' => 'forumng', 'action' => 'undelete discussion', 'mtable' => 'forumng', 'field' => 'name'),
    array('module' => 'forumng', 'action' => 'undelete post', 'mtable' => 'forumng_posts', 'field' => 'subject'),
    array('module' => 'forumng', 'action' => 'unlock discussion', 'mtable' => 'forumng', 'field' => 'name'),
    array('module' => 'forumng', 'action' => 'view', 'mtable' => 'forumng', 'field' => 'name'),
    array('module' => 'forumng', 'action' => 'view all', 'mtable' => 'forumng', 'field' => 'name'),
    array('module' => 'forumng', 'action' => 'view discussion', 'mtable' => 'forumng', 'field' => 'name'),
    array('module' => 'forumng', 'action' => 'view usageinfo', 'mtable' => 'forumng', 'field' => 'name'),
    array('module' => 'forumng', 'action' => 'view user participation', 'mtable' => 'forumng', 'field' => 'name'),
    array('module' => 'forumng', 'action' => 'view participation list', 'mtable' => 'forumng', 'field' => 'name'),
    array('module' => 'forumng', 'action' => 'edit set tags', 'mtable' => 'forumng', 'field' => 'name')
);
