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

import {linksDisable, linksEnable} from 'mod_forumng/common';
import Url from 'core/url';
import Config from 'core/config';

/**
 * JavaScript to handle star discussion.
 *
 * @module mod_forumng/stardiscussion
 * @copyright 2024 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
export class StarDiscussion {
    /** @var {string} cloneParam Clone id in param */
    cloneParam = '';

    /** @var {Number} ratingStars The number of stars (1-5) used for ratings */
    ratingStars = 0;

    /** @var {string} loaderPix URL of AJAX loader icon */
    loaderPix = '';

    /** @var {object} stringList List of strings */
    stringList = {};

    /** @var {Object} starPix Object containing multiple URLs of the various star icons */
    starPix = {};

    /**
     * Class constructor
     *
     * @param {object} options Options for ajax request
     */
    constructor(options) {
        this.cloneParam = options.cloneParam;
        this.ratingStars = options.ratingStars;
        this.loaderPix = options.loaderPix;
        this.stringList = options.stringList;
        this.starPix = options.starPix;
    }

    /**
     * Initialises rating div, either by turning the standard dropdown into an AJAX one with
     * its own button, or else by turning it into a number of stars.
     *
     * @param {HTMLElement} div  Rating div
     */
    initRating(div) {
        div.classList.add('forumng-ratings-jsenabled');
        div.post = div.closest('.forumng-post');
        div.ratingCount = 0;

        // If there is a select (=user allowed to edit rating), get it and use it to get detail
        // about current rating.
        const selects = div.querySelectorAll('select');
        if (selects.length > 0) {
            div.selector = selects[0];
            div.postId = parseInt(div.selector.name.replace(/^rating/, ''));
            div.userRating = div.selector.value;
            div.canRate = true;
            div.hasUserRating = div.userRating !== 999;
        }

        // If there is information about the current average rating (in a strong tag), get this
        // (there might not be, if rating is not displayed).
        const strongs = div.querySelectorAll('strong');
        if (strongs.length > 0) {
            const strong = strongs[0];
            div.publicRatingValue = strong.firstChild.nodeValue;
            div.publicRating = parseInt(div.publicRatingValue.replace(/\s*\/.*$/,''));
            div.postId = parseInt(strong.id.replace(/^rating_for_/, ''));
            div.hasPublicRating = true;
            div.ratingCount = parseInt(strong.parentNode.querySelector('span').firstChild.nodeValue);
        }

        div.canView = div.classList.contains('forumng-canview');

        if (this.ratingStars) {
            // Get rid of everything inside the area and replace it with magic stars.
            for (const node of div.childNodes) {
                node.remove();
            }
            div.starSpan = document.createElement('span');
            div.appendChild(div.starSpan);
            div.stars = [];
            for (let i = 0; i <= this.ratingStars; i++) {
                const star = document.createElement('img');
                star.setAttribute('width', '16');
                star.setAttribute('height', '16');
                star.rating = i;
                star.setAttribute('alt', i.toString());
                if (div.canRate) {
                    this.starInitEvents(div, star);
                }
                div.starSpan.appendChild(star);
                div.stars.push(star);
            }

            // Set up number of votes.
            div.countSpan = document.createElement('span');
            div.appendChild(div.countSpan);

            // Set initial star value.
            this.setStars(div);
            return null;
        } else {
            // No stars, add AJAX to dropdown.
            if (!div.selector) {
                return null;
            }
            // We move the logic to create the submit rate button to main.js and call initContent after a successful AJAX request.
            return div;
        }
    }

    /**
     * Initializes events on a single star.
     * @param {HTMLElement} div Main rating div
     * @param {HTMLElement} star This star
     */
    starInitEvents(div, star) {
        star.setAttribute('tabIndex', 0);

        star.clickFunction = () => {
            div.newRating = star.rating;
            if (div.hasUserRating && div.userRating == div.newRating) {
                div.newRating = 999;
            }
            const url = `${Config.wwwroot}/mod/forumng/rate.php`;
            const data = `p=${div.postId}${this.cloneParam}&rating=${div.newRating}&ajax=1`;
            const cfg = {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            };

            fetch(url + '?' + data, cfg)
                .then(response => response.text())
                .then(text => {
                    this.starOk(div, { responseText: text });
                })
                .catch(() => {
                    this.deleteError(div);
                })
                .finally(() => {
                    linksDisable(div.post);
                    star.setAttribute('src', this.loaderPix);
                });
        };

        star.addEventListener('click', star.clickFunction);

        star.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                star.clickFunction(e);
            }
        });

        star.addEventListener('focus', () => {
            star.classList.add('forumng-starfocus');
        });

        star.addEventListener('blur', () => {
            star.classList.remove('forumng-starfocus');
        });

        star.addEventListener('mouseover', () => {
            div.hasTempRating = true;
            div.tempRating = star.rating;
            this.setStars(div);
        });

        star.addEventListener('mouseout', () => {
            div.hasTempRating = false;
            this.setStars(div);
        });
    }

    /**
     * Updates stars according to current rating values.
     *
     * @param {HTMLElement} div Main ratings div
     */
    setStars(div) {
        let userPos, publicPos;
        let clearing = false;

        if (div.hasTempRating) {
            if (div.hasUserRating && div.tempRating == div.userRating) {
                clearing = true;
                userPos = -1;
            } else {
                userPos = div.tempRating;
            }
        } else {
            userPos = div.hasUserRating ? div.userRating : -1;
        }

        publicPos = div.hasPublicRating ? div.publicRating : -1;

        for (let i = 0; i < div.stars.length; i++) {
            const user = i == userPos;
            const pub = i <= publicPos;
            const key = (i == 0 ? 'circle-' : 'star-') + (user ? 'y' : 'n') + "-" + (pub ? 'y' : 'n');
            div.stars[i].src = Url.imageUrl(this.starPix[key], 'forumng');
        }

        if (div.ratingCount) {
            const newText = ' ' + (div.ratingCount == 1 ? this.stringList.js_nratings1 :
                this.stringList.js_nratings.replace(/#/, div.ratingCount));
            let existing = div.countSpan.firstChild;
            if (existing) {
                if (existing.nodeValue !== newText) {
                    existing.remove();
                    existing = null;
                }
            }
            if (!existing) {
                div.countSpan.appendChild(document.createTextNode(newText));
            }
        }

        let title = clearing ? this.stringList.js_clicktoclearrating :
            div.tempRating == 1 ? this.stringList.js_clicktosetrating1 :
                this.stringList.js_clicktosetrating.replace(/#/, div.tempRating);

        if (div.canView) {
            if (!div.hasPublicRating) {
                title += ' ' + this.stringList.js_nopublicrating;
            } else {
                title += ' ' + this.stringList.js_publicrating.replace(/#/, div.publicRating);
            }
        }

        if (div.canRate) {
            if (!div.hasUserRating) {
                title += ' ' + this.stringList.js_nouserrating;
            } else {
                title += ' ' + this.stringList.js_userrating.replace(/#/, div.userRating);
            }
        }

        title += ' ' + this.stringList.js_outof.replace(/#/, this.ratingStars);

        for (const star of div.stars) {
            star.title = title.trim();
        }
    }

    /**
     * Handles the AJAX response for a star-click rating event.
     *
     * @param {HTMLElement} div - The main rating div.
     * @param {Object} response - The fetch API response object.
     */
    starOk(div, response) {
        linksEnable(div.post);
        div.userRating = div.newRating;
        div.hasUserRating = div.newRating !== 999;

        const ratingRegex = /<strong id="rating_for_[0-9]+">([0-9]+) \//;
        // Use response.text() to access response body.
        const ratingMatch = ratingRegex.exec(response.responseText);

        if (ratingMatch) {
            div.publicRating = ratingMatch[1];
            div.hasPublicRating = true;
        } else {
            div.hasPublicRating = false;
            div.ratingCount = 0;
        }

        const countRegex = /<span class="forumng-count">([0-9]+)<\/span>/;
        const countMatch = countRegex.exec(response.responseText);
        if (countMatch) {
            div.ratingCount = parseInt(countMatch[1]);
        }

        this.setStars(div);
    }

    /**
     * AJAX response: Delete fails / Set rating fails.
     *
     * @param {Object} div div (for rating), which contains a '.post' variable
     */
    deleteError(div) {
        if (div.loader) {
            div.loader.remove();
        }
        linksEnable(div.post);
        alert(this.stringList.jserr_alter);
    }
}
