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

define(function() {
    const KEYS = {
        ENTER: 13
    };

    /**
     *
     * @alias module:mod_forum/toggleshortencontent
     */
    var t = {
            /**
             * Initialize.
             */
            initial: function() {
                document.querySelectorAll('.wrapper_fullcontent, .toggle_showless').forEach(elem => {
                    elem.style.display = 'none';
                });

                t.handleShortenContent();
            },

            /**
             * Add events to selectors.
             */
            handleShortenContent: function() {
                const toggleContent = (e, el) => {
                    var parent = el.parentElement.parentElement;
                    var elems = parent.querySelectorAll('.wrapper_shortencontent, .wrapper_fullcontent');
                    elems.forEach(elem => {
                        t.toggle(elem);
                    });

                    elems = parent.querySelectorAll('.toggle_showmore, .toggle_showless');
                    elems.forEach(elem => {
                        t.toggle(elem);
                    });
                };

                document.querySelectorAll('.toggle_showmore, .toggle_showless').forEach(elem => {
                    elem.addEventListener('click', e => {
                        toggleContent(e, elem);
                    });
                    elem.addEventListener('keypress', e => {
                        if (e.keyCode === KEYS.ENTER) {
                            toggleContent(e, elem);
                        }
                    });
                });
                return false;
            },

            /**
             * Toggle the element.
             *
             * @param {HTMLElement} elem
             */
            toggle: (elem) => {
                if (elem.style.display !== 'none') {
                    elem.style.display = 'none';
                } else {
                    elem.style.display = 'inline';
                }
            }
        };
    return t;
});
