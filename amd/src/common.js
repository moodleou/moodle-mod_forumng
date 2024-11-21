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
 * Common helper for mod_forumng.
 *
 * @module mod_forumng/common
 * @copyright 2024 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

export const androidOS = 'Android';
export const iOS = 'iOS';
export const otherOS = 'Other';

/**
 * Removes 'px' from the end of a string, if present, and converts it to a number.
 *
 * @param {string} string Text that possibly is a number with 'px' in
 * @return Value as number
 */
export const removePX = (string) => {
    return parseInt(string.replace(/px$/, ''));
};

/**
 * Simulates click on a link.
 *
 * @param {object} link Thing you want to click on (HTMLElement)
 */
export const simulateClick = (link) => {
    if (link.click) {
        link.click();
    } else {
        let event = new MouseEvent('click', {
            'view': window,
            'bubbles': true,
            'cancelable': true,
        });
        link.dispatchEvent(event);
    }
};

/**
 * Disables links for a post or whole page. This is used to grey out other options while
 * you are replying to a post.
 *
 * Note that the disable status is remembered at the level of the element, so if you disable
 * it for a post, you should enable it for the same post too, not just the whole page
 * @param {HTMLElement} root Element within which to disable command links
 */
export const linksDisable = (root) => {
    root.linksdisabled = true;
    const links = document.querySelectorAll('ul.forumng-commands a');
    links.forEach(link => {
        link.oldonclick = link.onclick;
        link.onclick = function() {
            return false;
        };
        link.style.cursor = 'default';
        link.tabIndex = -1;
        if (!link.classList.contains('forumng-disabled')) {
            link.classList.add('forumng-disabled');
        }
    });
};

/**
 * Enables links again after they were disabled.
 *
 * @param {HTMLElement} root Element within which to enable command links
 */
export const linksEnable = (root) => {
    root.linksdisabled = false;
    const links = document.querySelectorAll('ul.forumng-commands a');
    links.forEach(link => {
        if (link.oldonclick) {
            link.onclick = link.oldonclick;
            // Wanted to do 'delete' but it crashes ie.
            link.oldonclick = false;
        } else {
            link.onclick = function() {};
        }
        link.style.cursor = 'pointer';
        link.tabIndex = 0;
        link.className = link.className.replace('forumng-disabled', '');
    });
};

/**
 * Scrolls the page so that a given target is at the top.
 *
 * @param {HTMLElement} target Node to scroll to
 * @param {Function} [after] Callback to run after scrolling finishes
 */
export const scrollPage = (target, after) => {
    const scrollTo = target.getBoundingClientRect().top + window.pageYOffset;
    const scrollDuration = Math.min(0.5, Math.abs(window.scrollY - scrollTo) / 200);
    const easingFunction = t => t * (2 - t);

    let start = null;

    /**
     * A step function to perform the scrollpage.
     *
     * @param {DOMHighResTimeStamp} timestamp - The current time.
     */
    function scrollStep(timestamp) {
        if (!start) {
            start = timestamp;
        }
        const progress = timestamp - start;
        const scrollProgress = Math.min(progress / (scrollDuration * 1000), 1);
        window.scrollTo(0, window.scrollY + (scrollTo - window.scrollY) * easingFunction(scrollProgress));

        if (scrollProgress < 1) {
            window.requestAnimationFrame(scrollStep);
        } else if (after) {
            after();
        }
    }

    window.requestAnimationFrame(scrollStep);
};

/**
 * Document width.
 *
 * @return {number} The current width of the document
 */
export const getDocWidth = () => {
    return Math.max(
        document.documentElement.clientWidth,
        document.documentElement.scrollWidth,
        document.documentElement.offsetWidth,
        document.body.scrollWidth,
        document.body.offsetWidth,
    );
};

/**
 * Document height.
 *
 * @return {number} The current height of the document
 */
export const getDocHeight = () => {
    return Math.max(
        document.documentElement.clientHeight,
        document.documentElement.scrollHeight,
        document.documentElement.offsetHeight,
        document.body.scrollHeight,
        document.body.offsetHeight,
    );
};

/**
 * Retrieves the position and dimensions of an element relative to the screen viewport.
 *
 * @param {HTMLElement} element - The element to get the screen position for
 * @returns {Object} An object containing the screen position and dimensions of the element
 * @property {number} top - The top position of the element relative to the screen
 * @property {number} left - The left position of the element relative to the screen
 * @property {number} bottom - The bottom position of the element relative to the screen
 * @property {number} right - The right position of the element relative to the screen
 * @property {number} width - The width of the element
 * @property {number} height - The height of the element
 */
export const getElementScreenPosition = (element) => {
    const rect = element.getBoundingClientRect();
    const scrollLeft = window.pageXOffset || document.documentElement.scrollLeft;
    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;

    return {
        top: rect.top + scrollTop,
        left: rect.left + scrollLeft,
        bottom: rect.bottom + scrollTop,
        right: rect.right + scrollLeft,
        width: rect.width,
        height: rect.height
    };
};

/**
 * Retrieves the dimensions and position of the viewport relative to the document.
 *
 * @returns {Object} An object containing the dimensions and position of the viewport
 * @property {number} width - The width of the viewport
 * @property {number} height - The height of the viewport
 * @property {number} top - The vertical scroll position (top edge) of the viewport
 * @property {number} bottom - The vertical scroll position (bottom edge) of the viewport
 * @property {number} left - The horizontal scroll position (left edge) of the viewport
 * @property {number} right - The horizontal scroll position (right edge) of the viewport
 */
export const getViewportRegion = () => {
    var width = window.innerWidth || document.documentElement.clientWidth;
    var height = window.innerHeight || document.documentElement.clientHeight;
    var scrollLeft = window.pageXOffset || document.documentElement.scrollLeft;
    var scrollTop = window.pageYOffset || document.documentElement.scrollTop;

    return {
        width: width,
        height: height,
        top: scrollTop,
        bottom: scrollTop + height,
        left: scrollLeft,
        right: scrollLeft + width
    };
};
