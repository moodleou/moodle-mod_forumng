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

import {removePX} from 'mod_forumng/common';

/**
 * Expander helper for mod_forumng.
 *
 * @module mod_forumng/expander
 * @copyright 2024 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
export class Expander {

    /**
     * Expands an object. Construct with original object (to determine the initial
     * size) then add something into it or replace it, then call go() with the
     * new object.
     *
     * @param {Object} originalObj Original object
     */
    constructor(originalObj) {
        this.shrinkHeight = originalObj ? removePX(originalObj.style.height) : 0;
        this.lastHeight = -1;
    }

    /**
     * Starts expand animation.
     *
     * @param {Object} newObj New object to expand
     */
    go(newObj) {
        // Check if the initial height is valid
        if (isNaN(this.shrinkHeight)) {
            return;
        }

        newObj.style.maxHeight = this.shrinkHeight + 'px';
        newObj.style.overflow = 'hidden';

        const outer = this;
        let timeoutId = setInterval(() => {
            const currentHeight = newObj.offsetHeight;
            if (outer.lastHeight === currentHeight) {
                newObj.style.maxHeight = '';
                newObj.style.overflow = 'visible';
                clearInterval(timeoutId);
                return;
            }
            outer.lastHeight = currentHeight;
            outer.shrinkHeight += 20;
            newObj.style.maxHeight = outer.shrinkHeight + 'px';
        }, 20);
    }
}