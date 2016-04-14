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
 * JavaScript to support forum + discussion feature buttons.
 *
 * @package mod_forumng
 * @copyright 2016 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module mod_forumng/featurebtns
 */
define(['jquery', 'core/str'], function($, strings) {
    /**
     * Are feature buttons currently hidden by user?
     *
     * @type {boolean}
     */
    var mobileIsHidden  = true;
    /**
     * @alias module:mod_forumng/featurebtns
     */
    var t = {
        /**
         * Hide most feature buttons on mobile device.
         *
         * @public
         */
        initMobileHide : function() {
            var w = $(window);
            if (w.width() < 768) {
                t.mobileHide();
            }
            $(window).on('resize', function() {
                if (w.width() < 768 && mobileIsHidden) {
                    t.mobileHide();
                } else if (w.width() > 768 && mobileIsHidden) {
                    t.mobileShow();
                }
            });
        },

        /**
         * Hides feature buttons not needed on mobile.
         * Creates a button to show them again.
         *
         * @private
         */
        mobileHide : function() {
            if (!mobileIsHidden) {
                /* User has already chosen to show buttons, keep on */
                return;
            }
            var tohide = false;
            $('#forumng-features:first > div form:not(.fng-mobile-on)').each(function(index, e) {
                /* Loop so we can ignore features already disabled in CSS */
                var el = $(e);
                if (el.css('display') !== 'none') {
                    el.css('display', 'none');
                    tohide = true;
                }
            });
            if (tohide) {
                // Create button that will show the feature buttons again.
                if ($('#forumng-features:first div.mobileshow').length) {
                    $('#forumng-features:first > div.mobileshow').css('display', '');
                } else {
                    var lstring = '';
                    var allstrings = strings.get_strings([
                        {key: 'tooltip_show_features', component: 'mod_forumng'}
                    ]);
                    // Wait for promise to end.
                    var promise = $.when(allstrings);
                    // How we deal with promise objects is by adding callbacks.
                    promise.done(function(langstrings) {
                        lstring = langstrings[0];
                    });
                    var html = '<div class="mobileshow"><form method="get" action="#" class="fng-mobile-on">' +
                        '<input type="submit" value="..."' + ' title="' + lstring + '"></form></div>';
                    // Add button after last shown on mobile button (use parents, some child-divs).
                    $('#forumng-features:first > div form.fng-mobile-on').last().parents('div[class^=forumngfeature_]').after(html);
                    $('#forumng-features:first div.mobileshow form input:first').on('click', function(e) {
                        e.preventDefault();
                        t.mobileShow();
                        mobileIsHidden = false;
                    });
                }
            }
        },
        /**
         * Shows all feature buttons.
         * Creates a button to show them again.
         *
         * @private
         */
        mobileShow : function() {
            $('#forumng-features:first > div:not(.fng-mobile-on) form').css('display', '');
            $('#forumng-features:first > div.mobileshow').css('display', 'none');
        }
    };
    return t;
});
