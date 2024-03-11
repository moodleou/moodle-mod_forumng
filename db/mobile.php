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
 * Mobile plugin.
 *
 * @package mod_forumng
 * @copyright 2018 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$addons = [
    'mod_forumng' => [
        'handlers' => [
            'forumngview' => [
                'delegate' => 'CoreCourseModuleDelegate',
                'method' => 'forumng_view',
                'displaydata' => [
                    'icon' => $CFG->wwwroot . '/theme/osep/pix_plugins/mod/forumng/monologo.svg',
                    'class' => 'core-course-module-forumng-handler'
                ],
                'offlinefunctions' => [],
                'downloadbutton' => false,
                'displayprefetch' => false,
                'styles' => [
                    'url' => $CFG->wwwroot . '/mod/forumng/mobile_css.php?v=2024010200',
                    'version' => 2024010200
                ],
                'init' => 'mobile_forumng_init'
            ]
        ],
        'lang' => [
            ['pluginname', 'forumng'],
            ['discussions', 'forumng'],
            ['group', 'forumng'],
            ['nodiscussions', 'forumng'],
            ['addanewdiscussion', 'forumng'],
            ['subject', 'forumng'],
            ['message', 'forumng'],
            ['attachments', 'forumng'],
            ['erroremptysubject', 'forumng'],
            ['erroremptymessage', 'forumng'],
            ['cannotcreatediscussion', 'forumng'],
            ['cannotcreatereply', 'forumng'],
            ['postreply', 'forumng'],
            ['optionalsubject', 'forumng'],
            ['markpostread', 'forumng'],
            ['markallasread', 'forumng'],
            ['sortbydateoflastpost', 'forumng'],
            ['sortbytitle','forumng'],
            ['sortbymostunreadposts', 'forumng'],
            ['drafts', 'forumng'],
            ['draft', 'forumng'],
            ['date', 'forumng'],
            ['noselection', 'forumng'],
            ['leavemessage', 'forumng'],
            ['displayoption', 'forumng'],
            ['displayperiodmobile', 'forumng'],
            ['postasmobile', 'forumng'],
            ['errormaximumsubjectcharacter', 'forumng'],
            ['lock', 'forumng'],
            ['lockdiscussion', 'forumng'],
            ['lockedtitle', 'forumng'],
            ['expandallpost', 'forumng'],
            ['collapseallpost', 'forumng'],
            ['confirmdelete', 'forumng'],
            ['confirmundelete', 'forumng'],
            ['deletepostbutton', 'forumng'],
            ['undeletepostbutton', 'forumng'],
            ['editpostmobile', 'forumng'],
            ['expandallpost', 'forumng'],
            ['savedraft', 'forumng'],
            ['cannotcreatedraft', 'forumng'],
            ['confirmdeletedraft', 'forumng'],
            ['cannotuploadfile', 'forumng'],
            ['sortdiscussions', 'forumng'],
            ['unread', 'forumng'],
            ['important', 'forumng'],
            ['required', 'forumng'],
            ['requiredsubject', 'forumng'],
            ['confirmdeletediscussion', 'forumngfeature_delete'],
            ['confirmundeletediscussion', 'forumngfeature_delete'],
            ['delete', 'forumng'],
            ['undelete', 'forumng'],
        ]
    ]
];
