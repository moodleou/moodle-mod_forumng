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

/*
 * JavaScript for toggle show/hide shorten text to work with OSEP Theme.
 *
 * @package mod_forumng
 * @copyright 2020 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery'], function($) {
    /**
     *
     * @alias module:mod_forum/toggleshortencontent
     */
    var t = {
            /**
             * Initialize.
             */
            initial: function() {
                t.handleShortenContent();
            },

            /**
             * Add events to selectors.
             */
            handleShortenContent: function() {
                $('.toggle_showmore,.toggle_showless').on('click', function(e) {
                    e.preventDefault();
                    var contenttargets = $(this).parents('.wrapper_shortencontent, .wrapper_fullcontent');
                    contenttargets.toggle();
                    contenttargets.siblings('.wrapper_shortencontent, .wrapper_fullcontent').toggle();
                });
                return false;
            }
        };
    return t;
});
