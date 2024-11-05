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

import {get_strings as getStrings} from 'core/str';
import Notification from 'core/notification';
import * as filterEvents from 'core_filters/events';
import {Expander} from 'mod_forumng/expander';
import {StarDiscussion} from 'mod_forumng/stardiscussion';
import {SelectDiscussion} from 'mod_forumng/selectdiscussion';
import * as Common from 'mod_forumng/common';
import Pending from 'core/pending';
import Config from 'core/config';
import * as Rating from 'local_themeextras/rating';

/**
 * JavaScript to handle forumng.
 *
 * @module mod_forumng/main
 * @copyright 2024 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class Main {
    /** @var {string} cloneParam Clone id in param */
    cloneParam = '';

    /** @var {Number} cloneID Clone ID or 0 if none */
    cloneID = 0;

    /** @var {Number} cmID Course-module id of this forum */
    cmID = 0;

    /** @var {Number} ratingStars The number of stars (1-5) used for ratings */
    ratingStars = 0;

    /** @var {Number} discussionID Discussion if of this forum */
    discussionID = 0;

    /** @var {Array} expireLinks List of expire links */
    expireLinks = [];

    /** @var {Object} select Object containing selected node */
    select = {};

    /** @var {Number} quotaLeft If set, the number of posts left in post quota */
    quotaLeft = 0;

    /** @var {string} loaderPix URL of AJAX loader icon */
    loaderPix = '';

    /** @var {boolean} mouseUser Mouse event status */
    mouseUser = false;

    /** @var {Number} viewportWidth Viewport width */
    viewportWidth = -1;

    /** @var {Object} starPix Object containing multiple URLs of the various star icons */
    starPix = {};

    /** @var {boolean} nowEditing Editing status */
    nowEditing = false;

    /** @var {*} postQuota The number (seconds) or text description of the max-posts period of the current forum */
    postQuota = false;

    /** @var {Object} mainStrings Object containing main strings */
    mainStrings = {
        'rate': null,
        'expand': '#',
        'collapse': '#',
        'jserr_load': null,
        'jserr_save': null,
        'jserr_alter': null,
        'jserr_attachments': null,
        'confirmdelete': null,
        'confirmundelete': null,
        'confirmdeletediscuss': null,
        'deleteemailpostbutton': null,
        'deletepostbutton': null,
        'undeletepostbutton': null,
        'js_nratings': null,
        'js_nratings1': null,
        'js_nopublicrating': null,
        'js_publicrating': null,
        'js_nouserrating': null,
        'js_userrating': null,
        'js_outof': null,
        'js_clicktosetrating': null,
        'js_clicktosetrating1': null,
        'js_clicktoclearrating': null,
        'selectlabel': null,
        'selectintro': null,
        'confirmselection': null,
        'selectedposts': null,
        'discussion': null,
        'selectorall': null,
        'selectoralldisc': null,
        'selectorselecteddisc': null,
        'selectordiscall': null,
        'selectdiscintro': null,
        'staron': null,
        'staroff': null,
        'clearstar': null,
        'setstar': null,
        'starpost': null,
    };

    /** @var {object} stringList List of strings */
    stringList = {};


    /**
     * Class constructor
     *
     * @param {object} options Options for ajax request
     */
    constructor(options) {
        this.cmID = options.cmid;
        this.cloneID = options.cloneid;
        this.cloneParam = options.cloneid ? '&clone=' + options.cloneid : '';
        this.ratingStars = options.ratingstars;
        this.quotaLeft = options.quotaleft;
        this.loaderPix = options.loaderpix;
        this.starPix = options.starpix;
        this.postQuota = options.postquota;
    }

    /**
     * Main init function called from HTML.
     *
     */
    async initializer() {
        await this.prepareStrings();
        this.domInit();
        this.urgentInit();
    }

    /**
     * Prepare list of strings with key, component and value.
     *
     */
    async prepareStrings() {
        let requests = [];
        if (this.postQuota !== null) {
            this.mainStrings['quotaleft_plural'] = {
                posts: '#',
                period: this.postQuota,
            };
            this.mainStrings['quotaleft_singular'] = {
                posts: '#',
                period: this.postQuota,
            };
        }

        Object.entries(this.mainStrings).forEach(([string, value]) => {
            requests.push({
                'key': string,
                'component': 'forumng',
                'param': value,
            });
        });

        ['cancel', 'delete', 'add', 'selectall', 'deselectall'].forEach(string => {
            this.mainStrings[string] = '#';
            requests.push({
                'key': string,
                'component': 'moodle',
            });
        });

        this.stringList = Object.fromEntries(await getStrings(requests)
            .then(fetchedStrings => new Map(
                Object.entries(this.mainStrings).map(([key], index) => ([key, fetchedStrings[index]]))
            )).catch(Notification.exception));
    }

    /**
     * Main initialisation done on DOM ready.
     *
     */
    domInit() {
        // Magicalise the hidden 'switch view mode' link.
        if (document.getElementById('forumng-switchlinkid')) {
            let link = document.getElementById('forumng-switchlinkid');
            this.initSwitchLink(link);
        }

        // Handle pages other than the discussion page.
        if (document.getElementById('page-mod-forumng-subscribers')) {
            this.initSubscribers();
            return;
        }
        if (document.getElementById('page-mod-forumng-view')) {
            this.initView();
            return;
        }
        if (document.getElementById('page-mod-forumng-discuss')) {
            this.initDiscuss();
            return;
        }
        if (document.getElementById('page-mod-forumng-feature-deletedposts-deletedpostslist')) {
            this.initContent(document);
        }
        if (document.getElementById('page-mod-forumng-feature-userposts-user')) {
            this.initContent(document);
        }
        if (document.getElementById('page-mod-forumng-feature-print-print')) {
            this.initContent(document);
            this.printPage();
            return;
        }
        if (document.getElementById('page-mod-forumng-deletepost')) {
            this.initDeletepost();
            return;
        }
    }

    /**
     * Urgent init; this is not done as urgently as it used to be :( because it's only
     * in footer. I probably need to figure out a better way to do it. TODO
     */
    urgentInit() {
        // Create new stylesheet in head.
        let newStyle = document.createElement('style');
        newStyle.setAttribute("type", "text/css");

        let selector = '.forumng-ratings';
        let rules = 'display:none';

        if (document.styleSheets && document.styleSheets.length > 0) {
            // Check for addRule support (for older IE).
            if (document.styleSheets[0].addRule) {
                let head = document.getElementsByTagName('head')[0];
                head.appendChild(newStyle);
                document.styleSheets[document.styleSheets.length - 1].addRule(selector, rules);
            } else {
                // Other browsers, use text content.
                let styleText = selector + " { " + rules + " }";
                newStyle.appendChild(document.createTextNode(styleText));
                document.getElementsByTagName('head')[0].appendChild(newStyle);
            }
        }
    }

    /**
     * Sets up the 'accessible mode switch' link so that it becomes visible (not accesshide)
     * if you tab to it, including its parent.
     *
     * @param {HTMLElement} link - Link tag.
     */
    initSwitchLink(link) {
        link.addEventListener('focus', () => {
            link.parentNode.style.position = 'static';
        });
        link.addEventListener('blur', () => {
            link.parentNode.style.position = 'absolute';
        });
    }

    /**
     * Initialises all JavaScript for the discussion page.
     */
    initDiscuss() {
        // Get discussion id.
        this.discussionID = window.location.search.replace(/^.*[?&]d=([0-9]+).*$/, '$1');

        // Get param post id.
        let postid = this.getValueParameter('p');
        if (postid) {
            let link = document.getElementById(postid);
            if (link) {
                // For bookmarking.
                window.location.hash = link.id;
                link.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        }

        // Tell CSS that we have JS working.
        let forumngMain = document.getElementById('forumng-main');
        if (forumngMain) {
            forumngMain.classList.remove('forumng-nojs');
        }

        // To avoid having nested forms (breaks IE), remove the non-JS action form.
        let div = document.querySelector('#forumng-actionform > div');
        if (div) {
            let form = div.parentNode;
            div.remove();
            form.parentNode.insertBefore(div, form);
            form.remove();
        }

        let div1 = document.querySelector('.forumng_deldiscussion');
        if (div1) {
            this.initDeldiscussion(div1);
        }

        this.initContent(document.querySelector('#forumng-main'));

        // Hide 'save ratings' button if present.
        let saveall = document.getElementById('forumng-saveallratings');
        if (saveall) {
            saveall.parentNode.removeChild(saveall);
        }

        // Init feature buttons.
        this.initFeatureButtons(false);

        // Apply stop indents.
        this.applyStopIndents();
        let region = Common.getElementScreenPosition(document.querySelector('#forumng-main'));
        this.viewportWidth = region.right - region.left;
        setInterval(() => {
            let region = Common.getElementScreenPosition(document.querySelector('#forumng-main'));
            let width = region.right - region.left;
            if (width !== this.viewportWidth) {
                this.viewportWidth = width;
                this.applyStopIndents();
            }
        }, 250);
    }

    /**
     * Initialises all JavaScript for the deletepost page.
     */
    initDeletepost() {
        // if JS is enabled then we can copy the html version of the text to
        // the textarea used by tinymce, otherwise plain text is used by default.
        let msg = document.getElementById('delete-form-html');
        if (msg) {
            let messagehtml = msg.textContent;
            document.getElementById('id_forumng_delete_msg').innerHTML = messagehtml;
        }
    }

    /**
     * Initialises all JavaScript for the delete discussion page.
     *
     * @param {HTMLElement} div - Discussion element.
     */
    initDeldiscussion(div) {
        const form = div.parentNode.parentNode;
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            // Build confirmation message with buttons.
            const deleteButtons = [this.stringList.deleteemailpostbutton, this.stringList.deletepostbutton];
            const actionUrl = form.action + '?d=' + this.discussionID;
            const cloneParam = this.cloneID ? '&clone=' + this.cloneID : '';
            this.confirm(this.stringList.confirmdeletediscuss,
                deleteButtons,
                this.stringList.cancel,
                null, [
                    function() {
                        location.href = actionUrl + '&delete=1&email=1' + cloneParam;
                    },
                    function() {
                        location.href = actionUrl + '&delete=1&email=0&notdeleted=1' + cloneParam;
                    },
                ]
            );
        });
    }


    /**
     * Initialises 'content' i.e. posts and related. Can be called on the whole page or on
     * a single post.
     *
     * @param {HTMLElement} node to run on (e.g. document node or a post div)
     */
    initContent(node) {
        // When the selector is in use, and this is being run on a single post, then
        // do special init for the post.
        if (this.select.on && node.classList.contains('forumng-post')) {
            this.selectInitPost(node, true);
        }

        // Get post ID from location hash (initial run only).
        const expandposts = {};
        if (node == document || node.id == 'forumng-main') {
            // Post from location bar.
            if (window.location.hash) {
                let match = window.location.hash.match(/p([0-9]+)$/);
                if (match) {
                    expandposts[parseInt(match[1])] = true;
                }
            }
            // Posts listed as expanded (from Back button).
            let expandedList = document.getElementById('expanded_posts');
            let posts = expandedList ? expandedList.value.split(',') : [];
            for (let i = 0; i < posts.length; i++) {
                expandposts[posts[i]] = true;
            }
        }

        // Kill reply links if necessary.
        if (this.quotaLeft == 0) {
            this.killReplyLinks();
        }

        // Process all links within the node.
        node.querySelectorAll('a').forEach((link) => {
            let href = link.href;

            // Ignore mobile links.
            if (link.classList.contains('forumng-mobilepost-link')) {
                return;
            }

            // Any link with &expires= will be hidden a bit before that time.
            let match = href.match(/[?&]expires=([0-9]+)(&|$)/);
            if (match) {
                this.initExpiry(link, parseInt(match[1]));
                href = link.getAttribute('href');
            }

            // Magicalise 'Expand' links.
            match = href.match(/\/discuss\.php\?d=([0-9]+).*&expand=1#p([0-9]+)$/);
            if (match && link.classList.contains('forumng-expandlink')) {
                this.initExpand(link, match[2], expandposts[parseInt(match[2])]);
            }

            // 'Collapse' links.
            match = href.match(/\/discuss\.php\?d=([0-9]+).*&collapse=1#p([0-9]+)$/);
            if (match && link.classList.contains('forumng-collapselink')) {
                this.initCollapse(link, match[2]);
            }

            // Magicalise 'Reply' links.
            match = href.match(/\/editpost\.php\?replyto=([0-9]+).*$/);
            if (match) {
                this.initReply(link, parseInt(match[1]));
            }

            // Magicalise 'Edit' links.
            match = href.match(/\/editpost\.php\?p=([0-9]+).*$/);
            if (match) {
                this.initEdit(link, parseInt(match[1]));
            }

            // Magicalise 'Delete' / 'Undelete' links.
            match = href.match(
                /\/deletepost\.php\?p=([0-9]+)(?:&clone=[0-9]+)?(?:&delete=([0-9]+))?(?:&currentuser=([0-9]))?(?:&expand=1)?$/
            );
            if (match) {
                this.initDelete(link, parseInt(match[1]), match[2] && match[2] == 0, match[3]);
            }

            // Magicalise the hidden parent-post links.
            if (link.classList.contains('forumng-parentlink')) {
                this.initParentLink(link);
            }

            // Magicalise the jump-to links.
            if (link.parentNode.classList.contains('forumng-jumpto')) {
                this.initJumpLink(link);
            }

            // Magicalise the mark post read link.
            if (link.parentNode.classList.contains('forumng-markread')) {
                this.initPostread(link);
            }

        });
        // Magicalise rating sections.
        let ratings = node.querySelectorAll('div.forumng-ratings');
        if (ratings.length > 0) {
            let ratingoptions = {
                'cloneParam': this.cloneParam,
                'ratingStars': this.ratingStars,
                'loaderPix': this.loaderPix,
                'starPix': this.starPix,
                'stringList': this.stringList,
            };
            let ratingins = new StarDiscussion(ratingoptions);
            ratings.forEach((rating) => {
                let div = ratingins.initRating(rating);
                if (typeof div === 'object' && div !== null) {
                    this.createRateButton(div);
                }
            });

        }

        // Find any inputs with the zero-disable feature.
        let zeroDisableInputs = node.querySelectorAll('input.forumng-zero-disable');
        zeroDisableInputs.forEach((input) => {
            this.zeroDisable(input);
        });

        // Set up flags.
        this.initFlags(node);

        // Notify the filters about the modified nodes.
        filterEvents.notifyFilterContentUpdated(node);
    }

    /**
     * Creates a rate new button element, inserts it into the DOM, and attaches event listeners.
     *
     * @param {Object} div - The container element information.
     */
    createRateButton(div) {
        const newButton = document.createElement('input');
        newButton.setAttribute('type', 'button');
        newButton.setAttribute('value', this.stringList.rate);
        div.selector.parentNode.insertBefore(newButton, div.selector.nextSibling);

        newButton.addEventListener('click', () => {
            newButton.disabled = true;

            const url = `${Config.wwwroot}/mod/forumng/rate.php`;
            const data = `p=${div.postId}${this.cloneParam}&rating=${div.selector.value}&ajax=1`;
            const cfg = {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            };

            fetch(url + '?' + data, cfg)
                .then(response => response.text())
                .then(text => {
                    this.deleteOk(div, {responseText: text});
                })
                .catch(() => {
                    this.deleteError(div);
                });

            Common.linksDisable(div.post);

            div.loader = document.createElement('img');
            div.loader.setAttribute('alt', '');
            div.loader.style.position = 'absolute';
            div.loader.setAttribute('src', this.loaderPix);
            div.parentNode.appendChild(div.loader);
            const byregion = Common.getElementScreenPosition(newButton);
            div.loader.style.left = `${byregion.right + 3}px`;
            div.loader.style.top = `${byregion.top + 2}px`;
        });
    }

    /**
     * Applies expiry to links. Some links are marked with &expires=(time) to indicate that the
     * JavaScript should disable them around that time. (Edit, delete links.)
     *
     * @param {HTMLElement} link - Link node
     * @param {number} seconds - Number of seconds to expire after
     */
    initExpiry(link, seconds) {
        // Actually expires a bit early
        link.forumngExpiryJavatime = seconds * 1000 - 45000 + Date.now();
        link.href = link.href.replace(/[?&]expires=[0-9]+/, '');

        this.expireLinks.push(link);

        if (this.expireLinks.length == 1) {
            const timerid = setInterval(() => {
                const current = Date.now();
                for (let i = this.expireLinks.length - 1; i >= 0; i--) {
                    if (current > this.expireLinks[i].forumngExpiryJavatime) {
                        const deadlink = this.expireLinks[i];
                        if (deadlink.parentNode) {
                            deadlink.parentNode.remove();
                        }
                        this.expireLinks.splice(i, 1);
                    }
                }
                if (this.expireLinks.length == 0) {
                    clearInterval(timerid);
                }
            }, 15000);
        }
    }

    /**
     * Removes reply links within a given tag. This is used if you run out of quota so that
     * you cannot create new replies.
     *
     */
    killReplyLinks() {
        const links = document.querySelectorAll('#forumng-main a[href]');
        links.forEach(link => {
            if (link.href.match(/editpost\.php\?replyto=[0-9]+.*$/)) {
                link.remove();
            }
        });
    }

    /**
     * Removes iframe and marks it closed.
     *
     * @param {HTMLIFrameElement} iframe - The iframe element to remove
     * @param {boolean} scrollToParent - If true (default), scrolls to parent after removing iframe
     */
    removeIframe(iframe, scrollToParent = true) {
        const parent = iframe.parentNode;
        parent.removeChild(iframe);
        parent.removeAttribute('style');
        if (this.isMobile()) {
            // Hide iframe container also.
            parent.style.display = 'none';
            // Remove scroll event trapping.
            window.addEventListener('scroll', (event) => {
                event.preventDefault();
            });
        }

        this.nowEditing = false;

        if (scrollToParent) {
            Common.scrollPage(parent.parentNode);
        }

        Common.linksEnable(document.body);
    }

    /**
     * Get the device's OS.
     *
     * @return {string} Device's OS.
     */
    detectOS() {
        const ua = navigator.userAgent;

        if (/android/i.test(ua)) {
            return Common.androidOS;
        } else if (/iPad|iPhone|iPod/.test(ua)) {
            return Common.iOS;
        }
        return Common.otherOS;
    }

    /**
     * Check is mobile.
     * @return {boolean} Is mobile
     */
    isMobile() {
        return this.detectOS() === Common.iOS || this.detectOS() === Common.androidOS;
    }

    /**
     * Initialises an iframe and adds it.
     *
     * @param {string} src - iframe URL (&iframe=1 automatically added)
     * @param {HTMLElement} post - Post node to add iframe
     * @returns {HTMLIFrameElement|null} - Iframe element or null if already editing (abort)
     */
    initIframe(src, post) {
        // Check if post is unread.
        const classnamePost = post.className.split(" ");
        let isUnread = classnamePost.includes('forumng-unread');

        // Check we're not already editing.
        if (this.nowEditing) {
            return null;
        }
        this.nowEditing = true;

        let pendingPromise = new Pending('forumng_iframe');

        // Add special style that marks links disabled.
        Common.linksDisable(document.body);

        // Create iframe container and iframe elements.
        const iframecon = document.createElement('div');
        iframecon.classList.add('iframecon');
        const iframe = document.createElement('iframe');
        iframe.className = 'forumng-inlineform';
        iframe.name = 'forumng-post-iframe';
        iframe.height = 500;
        src += '&iframe=1';
        iframe.src = src;

        // Function to handle iframe load.
        window.iframe_has_loaded = (innerwin) => {
            // Add unread class if necessary.
            if (isUnread) {
                innerwin.document.body.className += ' iframe-post-unread';
            }
            pendingPromise.resolve();
            let pendingPromisefil = new Pending('forumng_iframe_load');

            const doc = innerwin.document;
            let counter = 0;

            // Roll up edit author fieldset in edit post (do here so height correct).
            const editemailHead = doc.getElementById('id_id_emailauthor');
            if (editemailHead) {
                editemailHead.className += ' collapsed';
            }

            // Check size and enlarge iframe if required.
            const fixHeight = () => {
                let pendingPromisefir = new Pending('forumng_iframe_resize');
                if (doc.body.scrollHeight > Number(iframe.height)) {
                    iframe.height = doc.body.scrollHeight + 2;
                    iframecon.style.height = `${doc.body.scrollHeight + 2}px`;
                    iframecon.style.overflow = 'unset';
                }

                // Check if the mobile view is activated, if so, then we align the
                // iframe to the top left position and make it dominate the whole page,
                // which basically make it behave like a pop-up overlay dialog.
                //
                // Create + set this.isMobile in the mobile theme to activate.
                if (this.isMobile()) {
                    iframecon.style.display = 'block';
                    iframecon.style.width = '100%';
                    iframecon.style.height = '100%';

                    if (this.detectOS() === Common.iOS) {
                        // Make fixed div work in iOS.
                        iframecon.style.position = 'fixed';
                        iframecon.style.top = '0px';
                        iframecon.style.left = '0px';
                        iframecon.style.zIndex = '9999';
                        iframecon.style.overflow = 'scroll';
                        iframecon.style.webkitOverflowScrolling = 'touch';
                        iframe.style.position = 'relative';
                        iframe.style.top = 'initial';
                        iframe.style.left = 'initial';
                    } else {
                        iframe.style.position = 'fixed';
                        iframe.style.top = '0px';
                        iframe.style.left = '0px';
                        iframe.style.zIndex = '9999';
                    }
                    iframe.style.height = '100%';
                    iframe.style.width = '100%';
                    iframe.style.overflowX = 'hidden';
                    iframe.style.overflowY = 'auto';

                    if (iframe.clientHeight >= innerwin.document.querySelector('#page').clientHeight) {
                        // If iframe size larger than content then hide scroll.
                        iframe.scrolling = 'no';
                    } else {
                        iframe.scrolling = 'auto';
                    }

                    doc.body.focus();
                    window.scrollTo(0, 0);
                }

                counter++;
                if (counter < 20) {
                    // Keep resizing iframe as filemanager takes a while to initialise.
                    setTimeout(fixHeight, 500);
                }
                pendingPromisefir.resolve();
            };
            fixHeight();

            // Add cancel handler that just removes the iframe - Except Atto as autosave cancel needed.
            doc.getElementById('id_cancel').addEventListener('click', (e) => {
                // Check if empty text, if so close iframe (Hack to stop 'required' issue).
                let blank = false;
                if (innerwin.document.getElementById('id_message') &&
                    (innerwin.document.getElementById('id_message').innerText === ''
                        || innerwin.document.getElementById('id_message').textContent === '')) {
                    blank = true;
                }
                if (!innerwin.document.querySelector('.editor_atto') || blank) {
                    if (!e) {
                        e = window.event;
                    }
                    if (e) {
                        if (e.stopPropagation) {
                            e.stopPropagation();
                        } else {
                            e.cancelBubble = true;
                        }
                    }
                    this.removeIframe(iframe);
                    return false;
                }
            });

            const draftbut = doc.getElementById('id_savedraft');
            if (draftbut) {
                draftbut.addEventListener('click', () => {
                    const x = setInterval(() => {
                        const draftexists = innerwin.document.querySelector('.forumng-draftexists');
                        if (draftexists) {
                            Common.scrollPage(document.querySelector('.forumng-inlineform'));
                            clearInterval(x);
                        }
                    }, 500);
                });
            }

            // Focus the editor.
            const try_focus = () => {
                if (innerwin.tinyMCE) {
                    for (let edId in innerwin.tinyMCE.editors) {
                        if (edId === 'id_message') {
                            innerwin.tinyMCE.execCommand('mceFocus', false, 'id_message');
                            return;
                        }
                    }
                }
                setTimeout(try_focus, 100);
            };
            if (!this.isMobile()) {
                setTimeout(try_focus, 250);
            }
            pendingPromisefil.resolve();
        };

        // Put iframe in as last thing in post (except the 'end post' marker).
        const ends = post.querySelectorAll('div.forumng-endpost');
        const last = ends[ends.length - 1];
        last.parentNode.insertBefore(iframecon, last.nextSibling);
        iframecon.appendChild(iframe);

        return iframe;
    }

    /**
     * Initialises edit links.
     *
     * @param {HTMLElement} link Link node
     * @param {number} postid Post ID to edit
     */
    initEdit(link, postid) {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            if (this.areLinksDisabled(link)) {
                return;
            }

            // Get post.
            const post = link.closest('.forumng-post');
            const rootpost = !post.closest('.forumng-replies');

            // Make iframe.
            let src = `editpost.php?p=${postid}`;
            if (this.cloneID) {
                src += `&clone=${this.cloneID}`;
            }
            const iframe = this.initIframe(src, post);
            if (!iframe) {
                return;
            }

            // Function that gets called when the iframe has completed successfully.
            window.iframe_success = (innerwin) => {
                // Add item just in front of existing post, then delete existing.
                const scriptcommands = [];
                const newpost = this.prepareNewPost(innerwin, scriptcommands);

                if (newpost.innerHTML !== '') {
                    post.parentNode.insertBefore(newpost, post);
                    post.parentNode.removeChild(post);

                    setTimeout(() => {
                        // Reload all the images - fixes chrome issue.
                        /* eslint-disable no-self-assign */
                        newpost.querySelectorAll('img').forEach(img => {
                            img.src = img.src;
                        });
                    }, 100);

                    // Run script commands.
                    /* eslint-disable no-eval */
                    scriptcommands.forEach(cmd => {
                        eval(cmd);
                    });

                    // For discussion, do special handling.
                    if (rootpost) {
                        // Get subject and remove its node.
                        const subjectinput = newpost.querySelector('input[name=discussion_subject]');
                        const subject = subjectinput.value;
                        subjectinput.remove();

                        const discussionTitles = document.querySelectorAll('.forumng_discussion_title');
                        discussionTitles.forEach(title => {
                            title.innerHTML = subject;
                        });

                        const navbar = document.querySelector('#page-header .navbar ul, #page-navbar ul');
                        if (navbar) {
                            // Find subject inside the breadcrumb (last <span> in last <li>).
                            const lastspan = navbar.querySelector('li:last-child > span:last-child');
                            if (lastspan) {
                                while (lastspan.firstChild) {
                                    lastspan.removeChild(lastspan.firstChild);
                                }
                                lastspan.appendChild(document.createTextNode(' ' + subject));
                            }
                        }
                    }

                    // Sort out links.
                    this.initContent(newpost);
                }

                // Remove the iframe.
                // This needs to be placed here for mobile devices to work.
                this.removeIframe(iframe);
                window.iframe_success = null;
            };
        });
    }

    /**
     * Extracts a new post from the result of a reply or edit script.
     *
     * @param {Window} innerwin Iframe window
     * @param {Array} scriptcommands Script commands will be added to this array
     * @return {HTMLElement} New post element
     */
    prepareNewPost(innerwin, scriptcommands) {
        const responsetext = innerwin.document.body.firstChild.innerHTML;
        const newdiv = document.createElement('div');
        let extractedHTML = this.extractJs(responsetext, scriptcommands);
        newdiv.innerHTML = extractedHTML;
        const newpost = newdiv.firstElementChild;
        newdiv.removeChild(newpost);
        return newpost;
    }

    /**
     * Initialises reply links.
     *
     * @param {HTMLElement} link Link node
     * @param {number} replytoid Post ID to reply to
     */
    initReply(link, replytoid) {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            if (this.areLinksDisabled(link)) {
                return;
            }

            // This function is also used when setting up a reply that includes existing draft text.
            const draft = window.forumng_draft ? window.forumng_draft : false;
            window.forumng_draft = null;

            // Get post.
            const post = link.closest('.forumng-post');

            // Make iframe.
            let src = 'editpost.php?';
            if (draft) {
                src += 'draft=' + draft.id;
            } else {
                src += 'replyto=' + replytoid;
            }
            if (this.cloneID) {
                src += '&clone=' + this.cloneID;
            }
            const iframe = this.initIframe(src, post);
            if (!iframe) {
                return;
            }

            // Function that gets called when the iframe has completed successfully.
            window.iframe_success = (innerwin) => {
                // Get replies div.
                let replies;
                if (post.nextElementSibling && post.nextElementSibling.classList.contains('forumng-replies')) {
                    replies = post.nextElementSibling;
                } else {
                    replies = document.createElement('div');
                    replies.className = 'forumng-replies';
                    // Insert the newly created replies div.
                    post.parentNode.insertBefore(replies, post.nextElementSibling);
                    this.applyStopIndents();
                }

                // Add item there.
                const scriptcommands = [];
                const newpost = this.prepareNewPost(innerwin, scriptcommands);
                replies.appendChild(newpost);

                // Run script commands.
                /* eslint-disable no-eval */
                scriptcommands.forEach(cmd => eval(cmd));

                // Set up JavaScript behaviour in new post.
                this.initContent(newpost);

                // Update quota left.
                if (this.quotaLeft > 0) {
                    this.quotaLeft--;

                    // If out of quota, kill all the reply links.
                    if (this.quotaLeft == 0) {
                        this.killReplyLinks();
                    }
                }

                // Remove the iframe.
                // This needs to be placed here for mobile devices to work.
                this.removeIframe(iframe, false);
                window.iframe_success = null;

                // Scroll to it (must do this after frame removed or height will be incorrect).
                Common.scrollPage(newpost, null);
            };

            // Mark that we've got a reply there.
            iframe.replytoid = replytoid;

            const quotaDiv = document.querySelector('#id_postlimit1');
            if (quotaDiv) {
                const quotaItem = quotaDiv.closest('.fitem');
                if (this.quotaLeft > 2 || this.quotaLeft < 0) {
                    quotaItem.style.display = 'none';
                } else {
                    quotaItem.style.display = 'block';
                    let text = (this.quotaLeft == 1)
                        ? this.stringList.quotaleft_singular
                        : this.stringList.quotaleft_plural;
                    text = text.replace('#', this.quotaLeft);
                    quotaDiv.innerHTML = text;
                }
            }
        });

        // When we create the reply link that a draft post uses, make it click itself.
        if (window.forumng_draft && window.forumng_draft.parentpostid == replytoid) {
            setTimeout(() => {
                Common.simulateClick(link);
            }, 0);
        }
    }

    /**
     * Initialises all flag icons on the page (discussion or main page) or a post.
     * @param {HTMLElement} node Root element
     */
    initFlags(node) {
        node.querySelectorAll('div.forumng-flagpost').forEach(div => {
            this.initFlagDiv(div);
        });
    }

    /**
     * Initialises a single flag icon based on the div.
     *
     * @param {HTMLElement} div forumng-flag div
     */
    initFlagDiv(div) {
        div.anchor = div.querySelector('a');
        div.span = div.querySelector('span');
        // Get on state from image icon.
        div.icon = div.querySelector('img.icon');
        div.on = div.icon.src.match(/star\.on/) !== null;

        const handleClick = (e) => {
            if (div.anchor.classList.contains('forumng-disabled')) {
                return false;
            }
            const cfg = {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
            };
            fetch(div.anchor.href + '&ajax=1', cfg)
                .then(() => {
                    div.on = !div.on;
                    div.icon.src = div.icon.src.replace(/star\.o(n|ff)/, `star.${div.on ? 'on' : 'off'}`);
                    div.anchor.href = div.anchor.href.replace(/\&flag=(0|1)/, `&flag=${div.on ? 0 : 1}`);
                    div.anchor.title = div.on ? this.stringList.clearstar : this.stringList.setstar;
                    if (div.on) {
                        div.classList.add('starred-post');
                    } else {
                        div.classList.remove('starred-post');
                    }
                    const text = div.span.innerHTML;
                    if (text) {
                        div.span.innerHTML = div.on ? this.stringList.clearstar : this.stringList.starpost;
                    }
                })
                .catch(() => {
                    alert(this.stringList.jserr_alter);
                });
            e.preventDefault();
        };

        // Remove all other event listeners just in case this function is called multiple times.
        div.icon.removeEventListener('click', handleClick);
        div.anchor.addEventListener('click', handleClick);
    }

    /**
     * Initializes the post read functionality.
     *
     * @param {HTMLElement} link - The link element that triggers the read action.
     */
    initPostread(link) {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            if (this.areLinksDisabled(link) || link.classList.contains('disabled')) {
                return;
            }
            let pendingPromise = new Pending('forumng_postread');
            const url = link.href + '&ajax=1';
            const cfg = {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                },
            };

            fetch(url, cfg)
                .then(async (response) => {
                    // Read response body text and check if word ok is in text.
                    const text = await response.text();
                    if (!text.includes('ok')) {
                        alert(this.stringList.jserr_alter);
                        pendingPromise.resolve();
                        return;
                    }
                    link.classList.add('disabled');
                    link.setAttribute('disabled', 'disabled');
                    const post = link.closest('div.forumng-unread');
                    if (post) {
                        post.classList.replace('forumng-unread', 'forumng-read');
                    }
                    pendingPromise.resolve();
                })
                .catch(() => {
                    alert(this.stringList.jserr_alter);
                    pendingPromise.resolve();
                });
        });
    }

    /**
     * Initialises the feature buttons that run along the bottom of a discussion. Some
     * of these may use the 'post selector' feature, which requires JavaScript.
     *
     * Looks for some ou-specific links that are treated as the real buttons
     *
     * @param {boolean} islist True if discussion list
     */
    initFeatureButtons(islist) {
        // Get all forms.
        if (islist) {
            // Feature btns on discussion list.
            document.querySelectorAll('form.forumng-dselectorbutton input[type=submit],' +
                ' #osep-bottombutton-export, #osep-bottombutton-print').forEach(node => {
                // For hacked buttons do extra, inc passing original input node target.
                if (node.tagName === 'A') {
                    const discussions = document.querySelectorAll('.forumng-discussionlist tr');
                    if (discussions.length == 0) {
                        node.style.display = 'none';
                    }
                }
                this.initSelectButton(node, true);
            });
        } else {
            document.querySelectorAll('form.forumng-selectorbutton, #osep-bottombutton-export, #osep-bottombutton-print')
                .forEach(node => {
                    let submit;
                    if (node.tagName === 'A') {
                        submit = node;
                    } else {
                        submit = node.querySelector('input[type=submit]');
                    }
                    this.initSelectButton(submit, false);
                });
        }
    }

    /**
     * Adds JS to the button which runs the selector feature, causing it to call the
     * 'confirm' prompt to ask whether you want to do the discussion or selected posts.
     *
     * @param {HTMLElement} submit Submit button node
     * @param {boolean} isList True if discussion list
     */
    initSelectButton(submit, isList) {
        if (isList) {
            // Check there are discussions to select.
            const discussions = document.querySelectorAll('.forumng-discussionlist tr');
            if (discussions.length == 0) {
                submit.disabled = true;
                return;
            }
            submit.addEventListener('click', (e) => {
                e.preventDefault();
                if (submit.tagName === 'A') {
                    if (submit.id === 'osep-bottombutton-export') {
                        submit = document.querySelector('.forumngfeature_export form.forumng-dselectorbutton input[type=submit]');
                    } else if (submit.id === 'osep-bottombutton-print') {
                        submit = document.querySelector('.forumngfeature_print form.forumng-dselectorbutton input[type=submit]');
                    }
                }

                // Pick up any discussion types we specifically include or exclude.
                let include = [];
                let exclude = [];
                const form = submit.form;
                const includeEl = form.querySelector('input[name=include]');
                if (includeEl) {
                    include = includeEl.value.split(',');
                }
                const excludeEl = form.querySelector('input[name=exclude]');
                if (excludeEl) {
                    exclude = excludeEl.value.split(',');
                }

                // Pick up inputs needed from form.
                let inputs = '';
                let inputNodes = form.querySelectorAll('input[type=hidden]');
                if (inputNodes.length == 0) {
                    inputNodes = form.children[0].querySelectorAll('input[type=hidden]');
                }
                inputNodes.forEach(node => {
                    inputs += `&${node.name}=${node.value}`;
                });

                this.confirm(`<h4>${submit.value}</h4><p>${this.stringList.selectordiscall}</p>`,
                    [this.stringList.selectoralldisc, this.stringList.selectorselecteddisc],
                    this.stringList.cancel,
                    null, [
                        function() {
                            location.href = `${form.action}?all=1${this.cloneParam}${inputs}`;
                        }.bind(this),
                        function() {
                            let discussionoptions = {
                                'cloneID': this.cloneID,
                                'stringList': this.stringList,
                                'select': this.select,
                            };
                            let selectDiscussion = new SelectDiscussion(discussionoptions);
                            if (!document.querySelector('div.forumng-main > form')) {
                                selectDiscussion.selectDiscussInit(submit, include, exclude);
                            }
                        }.bind(this)
                    ]);
            });
            return;
        }
        submit.addEventListener('click', (e) => {
            e.preventDefault();
            if (submit.tagName === 'A') {
                if (submit.id === 'osep-bottombutton-export') {
                    submit = document.querySelector('.forumngfeature_dis_export form.forumng-selectorbutton input[type=submit]');
                } else if (submit.id === 'osep-bottombutton-print') {
                    submit = document.querySelector('.forumngfeature_dis_print form.forumng-selectorbutton input[type=submit]');
                }
            }

            this.confirm(`<h4>${submit.value}</h4><p>${this.stringList.selectorall}</p>`,
                [this.stringList.discussion, this.stringList.selectedposts],
                this.stringList.cancel,
                null, [
                    function() {
                        location.href = `${submit.form.action}?d=${this.discussionID}${this.cloneParam}&all=1`;
                    }.bind(this),
                    function() {
                        if (document.querySelector('div.forumng-selectbuttons')) {
                            this.selectInit(null);
                        }
                        this.selectInit(submit);
                    }.bind(this),
                ]);
        });
    }

    /**
     * Stops indent from increasing once it gets past a certain minimum-post-width limit (this
     * varies depending on browser size). Without this, discussions with a lot of nesting levels
     * were getting ridiculous indent so that the actual posts were one word wide.
     *
     */
    applyStopIndents() {
        // Pick max indent level.
        const region = Common.getElementScreenPosition(document.getElementById('forumng-main'));
        const width = region.right - region.left;
        const isMobile = window.matchMedia('(max-width: 767px)').matches;
        // Min size at which the editor still looks ok.
        const minWidth = isMobile ? 256 : 550;
        const maxIndentPixels = width - minWidth;
        // This can't go lower than 1, otherwise the indentation never stops.
        let stopIndent = 1;

        if (isMobile) {
            // All indents are 6px at this width.
            if (maxIndentPixels > 6) {
                stopIndent = Math.floor(maxIndentPixels / 6);
            }
        } else {
            // There are 5 indents of 40px then 5 of 30px, then all 20px.
            if (maxIndentPixels > 350) {
                stopIndent = 10 + Math.floor((maxIndentPixels - 350) / 20);
            } else if (maxIndentPixels > 200) {
                stopIndent = 5 + Math.floor((maxIndentPixels - 200) / 30);
            } else if (maxIndentPixels >= 40) {
                stopIndent = Math.floor(maxIndentPixels / 40);
            }
        }

        // Fix indents for all tags.
        document.querySelectorAll('div.forumng-replies').forEach(reply => {
            const indent = this.getReplyIndent(reply);
            if (indent == stopIndent) {
                reply.classList.add('forumng-stop-indent');
            } else {
                reply.classList.remove('forumng-stop-indent');
            }
        });
    }

    /**
     * Obtains indent level of a reply (i.e. how many levels it is indented by).
     * @param {HTMLElement} reply Reply div
     *
     * @returns {number} Indent level
     */
    getReplyIndent(reply) {
        // Use cached indent count if available.
        if (reply.forumng_indent) {
            return reply.forumng_indent;
        }

        let indent = 1;
        // Go through each parent to find its nesting.
        let ancestor = reply.parentElement;

        while (ancestor && ancestor !== document.documentElement) {
            if (ancestor.classList.contains('forumng-replies')) {
                indent += 1;
                ancestor = ancestor.parentElement;
            } else {
                ancestor = ancestor.parentElement;
            }
        }

        reply.forumng_indent = indent;
        return indent;
    }

    /**
     * Checks if links are disabled including a particular link.
     *
     * @param {HTMLElement} link Element pointing to link
     * @returns {boolean} True if links are disabled, false otherwise
     */
    areLinksDisabled(link) {
        // True if links are disabled either at body or commands level.
        return document.body.linksdisabled ||
            link.closest('.forumng-commands').linksdisabled;
    }

    /**
     * Deeply clones an object or array and changes the 'itemid' property to a new value.
     *
     * @param {Object|Array} obj Object or array to clone and modify
     * @param {string} itemid New value for 'itemid' property
     * @returns {Object|Array} Cloned object or array with modified 'itemid' properties
     */
    deepCloneChangeItemid(obj, itemid) {
        if (Array.isArray(obj)) {
            var c = [];
        } else {
            var c = {};
        }

        for (let key in obj) {
            if (Object.prototype.hasOwnProperty.call(obj, key)) {
                if (key == 'itemid') {
                    c[key] = itemid;
                } else if (typeof obj[key] == 'object' && obj[key] !== null) {
                    c[key] = this.deepCloneChangeItemid(obj[key], itemid);
                } else {
                    c[key] = obj[key];
                }
            }
        }

        return c;
    }

    /**
     * Calls a function multiple times using setTimeout to avoid stack overflow.
     *
     * @param {Function} fn The function to call
     * @param {number} count The number of times to call the function
     */
    setTimeoutMulti(fn, count) {
        if (count > 0) {
            setTimeout(() => {
                this.setTimeoutMulti(fn, count - 1);
            }, 0);
        } else {
            fn();
        }
    }

    /**
     * Initializes the 'parent' links so that when tabbed to, the parent div/text displays.
     *
     * @param {HTMLElement} link Link node
     */
    initParentLink(link) {
        link.addEventListener('focus', () => {
            link.parentNode.style.position = 'static';
        });

        link.addEventListener('blur', () => {
            link.parentNode.style.position = 'absolute';
        });
    }

    /**
     * Initializes an expand link so that it can use AJAX to retrieve the message.
     *
     * @param {HTMLElement} link Link node
     * @param {number} postid Post ID
     * @param {boolean} expandNow If true, expand this post immediately
     */
    initExpand(link, postid, expandNow) {
        link.post = link.closest('.forumng-post');
        link.post.expandLink = link;
        link.loader = link.nextElementSibling;
        while (link.loader && link.loader.nodeName.toLowerCase() !== 'img') {
            link.loader = link.loader.nextElementSibling;
        }
        if (link.loader) {
            link.loader.originalSrc = link.loader.src;
        }
        link.postid = postid;
        link.delay = true;

        this.processLinkRetrieveMessage(link);

        // Automatically expand message listed in URL (if any).
        if (expandNow) {
            link.delay = false;
            Common.simulateClick(link);
        }
    }

    /**
     * Processes link hashtag URL to retrieve the message when you expand the post.
     *
     * @param {object} link Link node
     * @param {boolean} expandNow If true, expand this post immediately
     */
    processLinkRetrieveMessage(link, expandNow = true) {
        // Replace 'expand/collapse all' text with 'expand/collapse'.
        const postNum = link.post.className.replace(/^.*forumng-p([0-9]+).*$/, '$1');

        const text = expandNow ? link.querySelector('.forumng-expandtext') : link.querySelector('.forumng-collapsetext');
        if (text && expandNow) {
            text.innerHTML = this.stringList.expand.replace('#', postNum);
        }
        if (text && !expandNow) {
            text.innerHTML = this.stringList.collapse.replace('#', postNum);
        }

        // Add to post number to alt when using an image in expand link (expand_text lang string).
        const linkImg = link.querySelector('img.fng-eai');
        if (linkImg) {
            linkImg.alt = `${linkImg.alt} ${postNum}`;
        }

        link.addEventListener('click', (e) => {
            e.preventDefault();
            if (link.inProcess) {
                return;
            }
            link.post.focushandler = null;

            const data = `p=${link.postid}${this.cloneParam}&short=${expandNow ? 'true' : 'false'}`;
            const url = `${Config.wwwroot}/mod/forumng/expandpost.php`;

            const cfg = {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                },
            };

            fetch(url + '?' + data, cfg)
                .then(response => response.text())
                .then(text => {
                    this.expandOk(link, {responseText: text});
                })
                .catch(() => {
                    this.expandError(link);
                });

            if (link.loader) {
                link.loader.src = this.loaderPix;
            }
            link.inProcess = true;
        });
    }

    /**
     * Initializes a collapse link so that it can use AJAX to retrieve the message.
     *
     * @param {HTMLElement} link Link node
     * @param {number} postid Post ID
     */
    initCollapse(link, postid) {
        link.post = link.closest('.forumng-post');
        link.loader = link.nextElementSibling;
        while (link.loader && link.loader.nodeName.toLowerCase() !== 'img') {
            link.loader = link.loader.nextElementSibling;
        }
        if (link.loader) {
            link.loader.originalsrc = link.loader.src;
        }
        link.postid = postid;
        link.delay = true;

        this.processLinkRetrieveMessage(link, false);
    }

    /**
     * Some browsers cannot execute JavaScript just by inserting script tags.
     * To avoid that problem, remove all script tags from the given content,
     * and run them later.
     *
     * @param {string} text HTML content
     * @param {Array} scriptCommands Array of commands (the commands will be pushed
     *   into this)
     * @return {string} New text with JS removed
     */
    extractJs(text, scriptCommands) {
        const scriptRegexp = /<script[^>]*>([\s\S]*?)<\/script>/g;

        let result;
        while ((result = scriptRegexp.exec(text)) !== null) {
            scriptCommands.push(result[1]);
        }

        return text.replace(scriptRegexp, '');
    }

    /**
     * AJAX response: Expand completes successfully.
     *
     * @param {object} link Link node
     * @param {object} o YUI response object
     */
    expandOk(link, o) {
        const newDiv = document.createElement('div');
        const scriptCommands = [];
        newDiv.innerHTML = this.extractJs(o.responseText, scriptCommands);
        let newpost = newDiv.firstChild;
        newDiv.removeChild(newpost);
        const focushandler = link.post.focushandler;

        // If in select mode, note previous selection value.
        let previousSelect = false;
        if (this.select.on) {
            previousSelect = document.querySelector(`#checkp${link.postid}`).checked;
        }

        const expander = new Expander(link.post);
        const linkPostParent = link.post.parentNode;
        if (linkPostParent) {
            linkPostParent.insertBefore(newpost, link.post);
        }
        link.post.remove();

        // Run script commands.
        /* eslint-disable no-eval */
        scriptCommands.forEach(cmd => eval(cmd));

        this.initContent(newpost);
        if (previousSelect) {
            const checkbox = document.querySelector(`#checkp${link.postid}`);
            checkbox.checked = true;
        }
        if (document.body.linksdisabled) {
            Common.linksDisable(newpost);
            // It is not individually disabled, only as part of the general disable, so remove
            // the individual marker.
            newpost.linksdisabled = false;
        }

        const tracker = document.querySelector('#expanded_posts');
        tracker.value = `${tracker.value}${tracker.value == '' ? '' : ','}${link.postid}`;

        // For core ratings, init js on expand.
        if (newpost.querySelector('.forumng-ratings-standard')) {
            Rating.initRating(link.postid);
            Rating.initPopup(link.postid);
        }

        if (!link.delay) {
            // Skip the expanding animation.
            return;
        }

        expander.go(newpost);

        if (focushandler) {
            focushandler();
        } else {
            // Replace focus on expand element which got wiped.
            const authorSpan = newpost.querySelectorAll('span.forumng-author');
            if (authorSpan.length > 0) {
                // By default, focus on author name link.
                // The timeout here is because otherwise IE7 sometimes crashes.
                setTimeout(() => {
                    authorSpan[0].firstChild.focus();
                }, 0);
            } else {
                // If author name link is not present, focus on first link (which is usually
                // the 'this is post 3, parent is post N' link).
                const links = newpost.querySelectorAll('a[href]');
                if (links.length > 0) {
                    links[0].focus();
                }
            }
        }
    }

    /**
     * AJAX response: Expand fails.
     *
     * @param {HTMLElement} link Link node
     */
    expandError(link) {
        link.inProcess = false;
        link.loader.src = link.loader.originalsrc;
        alert(this.stringList.jserr_load);
    }

    /**
     * Initialises a 'jump' link (next unread).
     * @param {HTMLElement} link Link node
     */
    initJumpLink(link) {
        link.addEventListener('mousedown', () => {
            this.mouseUser = true;
        });

        link.addEventListener('click', (e) => {
            e.preventDefault();
            const id = link.getAttribute('href').split('#')[1];

            // Function to set up focus.
            const focuser = () => {
                const targetPost = document.querySelector(`#${id}`).parentNode;
                const jumpTo = targetPost.querySelector('.forumng-jumpto');
                if (this.mouseUser && jumpTo) {
                    // For mouse (~= visual) users, focus the next link so that after
                    // clicking the first time, they can then repeatedly press return.
                    const equivalent = jumpTo.querySelector(`a.${link.className}`);
                    if (equivalent) {
                        this.focus(equivalent);
                    } else {
                        const prev = jumpTo.querySelector('a.forumng-prev');
                        const next = jumpTo.querySelector('a.forumng-next');
                        if (prev || next) {
                            this.focus(prev ? prev : next);
                        } else {
                            this.focus(jumpTo.querySelector('a'));
                        }
                    }
                } else {
                    // For keyboard-only users, go to the start of the post (makes more sense).
                    const author = targetPost.querySelector('.forumng-author');
                    this.focus(author.querySelector('a'));
                }
            };

            // Get link target and expand it if required.
            const targetPost2 = document.querySelector(`#${id}`).parentNode;
            if (targetPost2.expandLink) {
                Common.simulateClick(targetPost2.expandLink);
                targetPost2.focushandler = focuser;
            }

            const targetPost3 = document.querySelector(`#${id}`).parentNode;
            // If post has already been expanded, focus it now.
            if (!targetPost3.focushandler) {
                focuser();
            }

            // Scroll to it.
            Common.scrollPage(targetPost2);
        });
    }

    /**
     * Focuses the given node (after timeout).
     * @param {HTMLElement} x Node to focus
     */
    focus(x) {
        setTimeout(() => {
            x.focus();
        }, 0);
    }

    /**
     * Initialises a delete link.
     *
     * @param {HTMLElement} link Link node
     * @param {string} postId Post ID
     * @param {boolean} undelete True if it's actually undelete
     * @param {string} currentUser The current user
     */
    initDelete(link, postId, undelete, currentUser) {
        link.postId = postId;
        link.post = link.closest('.forumng-post');
        link.addEventListener('click', (e) => {
            e.preventDefault();
            if (this.areLinksDisabled(link)) {
                return;
            }
            const url = `${Config.wwwroot}/mod/forumng/deletepost.php`;
            const data = `p=${link.postId}${this.cloneParam}&delete=${undelete ? 0 : 1}&ajax=1`;
            const deleteAndEmail = () => {
                window.location = url + `?p=${link.postId}${this.cloneParam}&delete=1&ajax=1&email=1`;
            };
            const deleteOnly = () => {
                const cfg = {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                };
                fetch(url + '?' + data, cfg)
                    .then(response => response.text())
                    .then(text => {
                        this.deleteOk(link, {responseText: text});
                    })
                    .catch(() => {
                        this.deleteError(link);
                    });
                Common.linksDisable(link.post);
                link.loader = document.createElement('img');
                link.loader.alt = '';
                link.loader.src = this.loaderPix;
                link.loader.style.position = 'absolute';
                link.parentNode.appendChild(link.loader);
                const linkRegion = Common.getElementScreenPosition(link);
                link.loader.style.left = `${linkRegion.right + 3}px`;
                link.loader.style.top = `${linkRegion.top}px`;
            };

            const deleteButtons = currentUser
                ? [this.stringList.deletepostbutton]
                : [this.stringList.deleteemailpostbutton, this.stringList.deletepostbutton];

            const deleteOptions = currentUser
                ? [deleteOnly, '']
                : [deleteAndEmail, deleteOnly, ''];

            this.confirm(
                undelete ? this.stringList.confirmundelete : this.stringList.confirmdelete,
                undelete ? this.stringList.undeletepostbutton : deleteButtons,
                this.stringList.cancel, link.post,
                undelete ? deleteOnly : deleteOptions
            );
        });
    }

    /**
     * AJAX response: Delete completes OK / Set rating completes OK.
     *
     * @param {Object} link Link (for delete) or div (for rating), which contains a '.post' variable
     * @param {Object} response Response object
     *   for the post object
     */
    deleteOk(link, response) {
        const newDiv = document.createElement('div');
        newDiv.innerHTML = response.responseText;
        let newPost = newDiv.firstChild;

        // Post may be blank when deleting (if not admin).
        if (newPost) {
            link.post.parentNode.insertBefore(newPost, link.post);
        }

        Common.linksEnable(link.post);
        link.post.remove();

        if (newPost) {
            this.initContent(newPost);
        }
    }

    /**
     * AJAX response: Delete fails / Set rating fails.
     *
     * @param {Object} link Link (for delete), which contains a '.post' variable
     *   for the post object
     */
    deleteError(link) {
        if (link.loader) {
            link.loader.remove();
        }
        Common.linksEnable(link.post);
        alert(this.stringList.jserr_alter);
    }


    /**
     * Displays a fancy dialog box on a faded-out background in the middle of the
     * screen.
     * @param {string} message Message to display (may include html; if heading is included,
     *     we recommend h4)
     * @param {string} actionText Name for action button(s). May be a single string or
     *     array if you need multiple buttons
     * @param {string} cancelText Name for cancel button
     * @param {HTMLElement} highlight HTML element that should be highlighted (with an orange
     *     box), used e.g. to indicate which post is being deleted
     * @param {function} action Function that gets run if user clicks the action button
     *     (if there are multiple action buttons, this too must be an array)
     */
    confirm(message, actionText, cancelText, highlight, action) {
        if (typeof actionText == 'string') {
            // There is only one action (text and functions); make it look like an array.
            actionText = [actionText];
            action = [action];
        }

        const fadePanel = document.createElement('div');
        fadePanel.classList.add('forumng-fadepanel');
        document.body.appendChild(fadePanel);
        fadePanel.style.position = 'absolute';
        fadePanel.style.top = '0';
        fadePanel.style.left = '0';
        fadePanel.style.width = Common.getDocWidth() + 'px';
        fadePanel.style.height = Common.getDocHeight() + 'px';
        fadePanel.style.zIndex = 10;

        fadePanel.style.opacity = 0.0;
        // Animate to 0.5 opacity over 250ms with linear easing.
        this.animateOpacity(fadePanel, 0.0, 0.5, 250, this.linearEasing);

        // Handle highlight element.
        let highlightDiv = null;
        if (highlight) {
            const highlightRegion = Common.getElementScreenPosition(highlight);

            highlightDiv = document.createElement('div');
            highlightDiv.classList.add('forumng-highlightbox');
            document.body.appendChild(highlightDiv);

            highlightDiv.style.position = 'absolute';
            highlightDiv.style.top = highlightRegion.top + 'px';
            highlightDiv.style.left = highlightRegion.left + 'px';
            highlightDiv.style.zIndex = 15;

            const height = highlightRegion.bottom - highlightRegion.top -
                Common.removePX(window.getComputedStyle(highlightDiv).borderTopWidth) -
                Common.removePX(window.getComputedStyle(highlightDiv).borderBottomWidth) -
                Common.removePX(window.getComputedStyle(highlightDiv).paddingTop) -
                Common.removePX(window.getComputedStyle(highlightDiv).paddingBottom);
            const width = highlightRegion.right - highlightRegion.left -
                Common.removePX(window.getComputedStyle(highlightDiv).borderLeftWidth) -
                Common.removePX(window.getComputedStyle(highlightDiv).borderRightWidth) -
                Common.removePX(window.getComputedStyle(highlightDiv).paddingLeft) -
                Common.removePX(window.getComputedStyle(highlightDiv).paddingRight);

            highlightDiv.style.height = height + 'px';
            highlightDiv.style.width = width + 'px';
        }

        // Create dialog element.
        const dialog = document.createElement('div');
        dialog.classList.add('forumng-confirmdialog');
        document.body.appendChild(dialog);

        // Set dialog position.
        dialog.style.position = 'absolute';
        dialog.style.zIndex = 20;

        // Get viewport dimensions.
        const region = Common.getViewportRegion();

        region.height = region.bottom - region.top;
        region.width = region.right - region.left;

        dialog.style.top = (region.top + region.height / 3) + 'px';

        // Set dialog width based on window size.
        const page = Common.getElementScreenPosition(document.getElementById('page'));
        const pixelsWidth = page.right - page.left;
        let leftAdjuster = 0;
        let requiredBoxWidth = 630;
        if (pixelsWidth < 700) {
            requiredBoxWidth = pixelsWidth - 40;
            leftAdjuster = 5;
        }
        dialog.style.width = (requiredBoxWidth - 10) + 'px';

        let leftValue = region.left + region.width / 2 - (requiredBoxWidth / 2) - leftAdjuster;
        // Ensure left position is not negative.
        leftValue = Math.max(leftValue, 0);
        dialog.style.left = leftValue + 'px';

        const messageDiv = document.createElement('div');
        messageDiv.classList.add('forumng-message');
        messageDiv.tabIndex = -1;
        messageDiv.innerHTML = message;
        dialog.appendChild(messageDiv);

        const buttonDiv = document.createElement('div');
        buttonDiv.className = 'forumng-buttons';
        dialog.appendChild(buttonDiv);

        const cancel = document.createElement('input');
        cancel.type = 'button';
        cancel.value = cancelText;
        cancel.addEventListener('click', () => {
            dialog.remove();
            fadePanel.remove();
            if (highlightDiv) {
                highlightDiv.remove();
            }
        });

        for (var i = 0; i < actionText.length; i++) {
            buttonDiv.appendChild(this.confirmMakeButton(actionText[i], action[i], cancel, false));
        }

        this.focus(messageDiv);
        buttonDiv.appendChild(cancel);
    }

    /**
     * Makes a button for the confirm dialog.
     *
     * @param {string} actionText - Text
     * @param {function} action - Function to call if button is clicked
     * @param {HTMLElement} cancel - Cancel button (which is automatically clicked before calling action)
     * @param {boolean} focus - True if this should be focused
     * @returns {HTMLInputElement} - The created button element
     */
    confirmMakeButton(actionText, action, cancel, focus) {
        const yes = document.createElement('input');
        yes.type = 'button';
        yes.value = actionText;

        yes.addEventListener('click', () => {
            Common.simulateClick(cancel);
            action();
        });

        if (focus) {
            yes.focus();
        }

        return yes;
    }

    /**
     * Logs data using the console if available.
     *
     * @param {string} thing Thing to log
     */
    log(thing) {
        if (typeof console !== 'undefined') {
            window.console.debug(thing);
        }
    }

    /**
     * Initialises forum main page JavaScript.
     *
     */
    initView() {
        // Set the focus on the sort links when clicked.
        this.focusSortLinks();

        // Set up all the draft links to use discuss.php instead of non-JS version.
        document.querySelectorAll('.forumng-main a').forEach(link => {
            const match = link.className.match(/^forumng-draftreply-([0-9]+)-([0-9]+)$/);
            if (match) {
                const linkMatch = link.href.match(/draft=([0-9]+)(&clone=[0-9]+)?$/);
                if (linkMatch) {
                    link.href = `discuss.php?d=${match[1]}${this.cloneParam}&draft=${linkMatch[1]}#p${match[2]}`;
                }
            }
        });

        // Set up flag icons.
        this.initFlags(document.body);

        // Change selected buttons into links with text in brackets.
        document.querySelectorAll('input.forumng-button-to-link').forEach(button => {
            this.turnButtonIntoLink(button);
        });

        // Init feature buttons.
        this.initFeatureButtons(true);
    }

    /**
     * Focuses sort links after reloading page when a sort link was clicked.
     */
    focusSortLinks() {
        const url = window.location.href;
        const searchIndex = url.search(/&sortlink=/);
        if (searchIndex !== -1) {
            const sortLinkId = "sortlink_" + url.substr(searchIndex + 10, 1);
            this.focus(document.querySelector('#' + sortLinkId));
        }
    }

    /**
     * On the main page, turns a button into a link with text in brackets (this is we
     * can have commands which are POST requests but look like links).
     *
     * @param {HTMLElement} button Button node
     */
    turnButtonIntoLink(button) {
        const span = document.createElement('span');
        span.appendChild(document.createTextNode('('));
        const link = document.createElement('a');
        link.appendChild(document.createTextNode(button.value));
        link.href = '#';
        link.addEventListener('click', (e) => {
            e.preventDefault();
            Common.simulateClick(button);
        });
        span.appendChild(link);
        span.appendChild(document.createTextNode(') '));
        button.parentNode.insertBefore(span, button);
        button.style.display = 'none';
    }

    /**
     * Initialises the post selector feature, switching the whole page into post select mode.
     *
     * @param {HTMLElement|null} target Target button that indicates where the resulting selection will be posted,
     *   or null to cancel select mode
     */
    selectInit(target) {
        this.select.on = target ? true : false;

        const posts = document.querySelectorAll('div.forumng-post');
        const confirm = document.createElement('input');
        confirm.setAttribute('type', 'submit');

        const extraneousDisplay = this.select.on ? 'none' : 'block';
        document.querySelector('#forumng-expandall').style.display = extraneousDisplay;
        document.querySelector('#forumng-features').style.display = extraneousDisplay;
        const subscribeOptions = document.querySelector('#forumng-subscribe-options');
        if (subscribeOptions) {
            subscribeOptions.style.display = extraneousDisplay;
        }

        const main = document.querySelector('#forumng-main');
        const all = document.createElement('input');
        const none = document.createElement('input');
        if (this.select.on) {
            // Make form around main elements.
            const form = document.createElement('form');
            this.select.form = form;
            form.setAttribute('method', 'post');
            form.setAttribute('action', target.form.action);
            main.classList.add('forumng-selectmode');

            form.inputs = document.createElement('div');
            form.appendChild(form.inputs);
            let field = document.createElement('input');
            field.setAttribute('type', 'hidden');
            field.setAttribute('name', 'd');
            field.setAttribute('value', this.discussionID);
            form.inputs.appendChild(field);
            field = document.createElement('input');
            field.setAttribute('type', 'hidden');
            field.setAttribute('name', 'fromselect');
            field.setAttribute('value', '1');
            form.inputs.appendChild(field);
            if (this.cloneID) {
                field = document.createElement('input');
                field.setAttribute('type', 'hidden');
                field.setAttribute('name', 'clone');
                field.setAttribute('value', this.cloneID);
                form.inputs.appendChild(field);
            }

            // Make intro.
            form.intro = document.createElement('div');
            form.intro.classList.add('forumng-selectintro');
            main.parentNode.insertBefore(form.intro, main);
            const introText = document.createElement('p');
            introText.innerHTML = this.stringList.selectintro;
            form.intro.appendChild(introText);

            // Make buttons to select all/none.
            const selectButtons = document.createElement('div');
            selectButtons.classList.add('forumng-selectbuttons');
            form.intro.appendChild(selectButtons);

            all.setAttribute('type', 'button');
            all.setAttribute('value', this.stringList.selectall);
            selectButtons.appendChild(all);
            all.addEventListener('click', () => {
                const posts = document.querySelectorAll('div.forumng-post');
                posts.forEach(post => {
                    if (!post.check.checked) {
                        Common.simulateClick(post.check);
                    }
                });
                all.disabled = true;
                none.disabled = false;
            });
            selectButtons.appendChild(document.createTextNode(' '));


            none.setAttribute('type', 'button');
            none.setAttribute('value', this.stringList.deselectall);
            selectButtons.appendChild(none);
            none.addEventListener('click', () => {
                const posts = document.querySelectorAll('div.forumng-post');
                posts.forEach(post => {
                    if (post.check.checked) {
                        Common.simulateClick(post.check);
                    }
                });
                all.disabled = false;
                none.disabled = true;
            });

            main.appendChild(form);

            // Make outro.
            form.outro = document.createElement('div');
            form.outro.classList.add('forumng-selectoutro');
            form.appendChild(form.outro);

            confirm.setAttribute('value', this.stringList.confirmselection);
            form.outro.appendChild(confirm);

            form.outro.appendChild(document.createTextNode(' '));

            const cancel = document.createElement('input');
            cancel.setAttribute('type', 'button');
            cancel.setAttribute('id', 'forumng-cancel-select');
            cancel.setAttribute('value', this.stringList.cancel);
            form.outro.appendChild(cancel);
            cancel.addEventListener('click', () => {
                this.selectInit(null);
            });

            Common.scrollPage(form.intro, null);
        } else {
            const form = this.select.form;
            form.remove();
            form.intro.remove();
            form.outro.remove();
            main.classList.remove('forumng-selectmode');
            this.select.form = null;
        }

        window.forumng_select_changed = () => {
            const posts = document.querySelectorAll('div.forumng-post');
            let ok = false;
            let checkcount = 0;
            posts.forEach(post => {
                if (post.check.checked) {
                    ok = true;
                    checkcount++;
                }
            });
            none.disabled = !ok;
            confirm.disabled = !ok;
            all.disabled = checkcount == posts.length;
        };

        posts.forEach(post => {
            this.selectInitPost(post, this.select.on);
        });

        if (this.select.on) {
            window.forumng_select_changed();
        }
    }


    /**
     * Initialises a single post within select mode.
     *
     * @param {HTMLElement} post Post div
     * @param {boolean} on True if select is being turned on, false if it's being turned off
     */
    selectInitPost(post, on) {
        if (on) {
            const info = post.querySelector('div.forumng-info');
            if (!info) {
                return;
            }
            const span = document.createElement('span');
            const spanSeparator = document.createElement('span');
            span.appendChild(spanSeparator);
            spanSeparator.classList.add('forumng-separator');
            spanSeparator.textContent = ' \u2022 ';
            info.appendChild(span);
            post.extraSpan = span;
            post.classList.add('forumng-deselected');
            const postid = post.querySelector(':scope > a').id;

            const check = document.createElement('input');
            check.setAttribute('type', 'checkbox');
            check.id = 'check' + postid;
            post.check = check;
            span.appendChild(check);

            const label = document.createElement('label');
            label.classList.add('accesshide');
            label.setAttribute('for', check.id);
            label.textContent = this.stringList.selectlabel;
            span.appendChild(label);

            Common.linksDisable(document.body);

            let hidden = document.querySelector("input[name='select" + postid + "']");
            if (!hidden) {
                hidden = document.createElement('input');
                hidden.setAttribute('type', 'hidden');
                hidden.setAttribute('value', '0');
                hidden.setAttribute('name', 'select' + postid);
                this.select.form.appendChild(hidden);
            }
            post.forumng_hidden = hidden;

            check.addEventListener('click', () => {
                if (check.checked) {
                    post.classList.remove('forumng-deselected');
                    post.forumng_hidden.value = 1;
                } else {
                    post.classList.add('forumng-deselected');
                    post.forumng_hidden.value = 0;
                }
                window.forumng_select_changed();
            });
        } else {
            if (post.extraSpan) {
                post.extraSpan.remove();
            }
            post.classList.remove('forumng-deselected');
            Common.linksEnable(document.body);
        }
    }

    /**
     * Sets up certain selects (currently just the 'Move discussion' one) so that their
     * accompanying button is disabled when the select is set to 0.
     *
     * @param {HTMLElement} submit Submit button that goes with select
     */
    zeroDisable(submit) {
        // Assuming submit is an HTMLInputElement.
        const select = submit.previousElementSibling;
        if (!select || select.nodeName.toLowerCase() !== 'select') {
            this.log('Warning: Zero-disable feature incorrectly applied.');
            return;
        }

        const update = () => {
            if (submit.classList.contains('forumng-zero-disable')) {
                submit.disabled = select.value == 0;
            }
        };

        update();
        select.addEventListener('change', update);
    }

    /**
     * Asks browser to print the page.
     */
    printPage() {
        window.print();
    }

    /**
     * Initialises the subscriber list page JavaScript.
     *
     */
    initSubscribers() {
        const buttonsDiv = document.getElementById('forumng-buttons');
        const selectAll = document.createElement('input');
        selectAll.type = 'button';
        selectAll.value = this.stringList.selectall;
        buttonsDiv.appendChild(document.createTextNode(' '));
        buttonsDiv.appendChild(selectAll);

        const deselectAll = document.createElement('input');
        deselectAll.type = 'button';
        deselectAll.value = this.stringList.deselectall;
        buttonsDiv.appendChild(document.createTextNode(' '));
        buttonsDiv.appendChild(deselectAll);

        let unsubscribe;
        const inputs = document.getElementById('forumng-subscription-list').querySelectorAll('input');
        const all = [];

        for (const input of inputs) {
            if (input.name.startsWith('user')) {
                all.push(input);
            } else if (input.name == 'unsubscribe') {
                unsubscribe = input;
            }
        }

        const update = () => {
            let allSelected = true;
            let noneSelected = true;

            for (const item of all) {
                if (item.checked) {
                    noneSelected = false;
                } else {
                    allSelected = false;
                }
            }

            selectAll.disabled = allSelected;
            deselectAll.disabled = noneSelected;
            unsubscribe.disabled = noneSelected;
        };

        update();

        all.forEach((item) => item.addEventListener('click', update));

        selectAll.addEventListener('click', () => {
            all.forEach((item) => {
                item.checked = true;
            });
            update();
        });

        deselectAll.addEventListener('click', () => {
            all.forEach((item) => {
                item.checked = false;
            });
            update();
        });
    }

    /**
     * Retrieves the value of a parameter from the query string of the current URL.
     *
     * @param {string} parameterName - The name of the parameter to retrieve.
     * @returns {string|null} The value of the specified parameter if found, otherwise null.
     */
    getValueParameter(parameterName) {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get(parameterName) || null;
    }

    /**
     * Animates the opacity of an element from a start value to an end value over a specified duration.
     *
     * @param {HTMLElement} element - The DOM element to animate
     * @param {number} startOpacity - The initial opacity value
     * @param {number} endOpacity - The target opacity value
     * @param {number} duration - The duration of the animation in milliseconds
     * @param {function} easingFunction - The easing function to use for the animation
     */
    animateOpacity(element, startOpacity, endOpacity, duration, easingFunction) {
        let start = null;
        const deltaOpacity = endOpacity - startOpacity;

        /**
         * A step function to perform the animation frame by frame.
         *
         * @param {DOMHighResTimeStamp} timestamp - The current time.
         */
        function step(timestamp) {
            if (!start) {
                start = timestamp;
            }
            const progress = timestamp - start;
            const easeProgress = easingFunction(progress / duration);
            element.style.opacity = startOpacity + deltaOpacity * easeProgress;

            if (progress < duration) {
                requestAnimationFrame(step);
            } else {
                element.style.opacity = endOpacity;
            }
        }

        requestAnimationFrame(step);
    }

    /**
     * A linear easing function.
     *
     * @param {number} t - The progress of the animation (a value between 0 and 1)
     * @returns {number} - The adjusted progress
     */
    linearEasing(t) {
        return t;
    }
}

/**
 * Initialise function
 *
 * @param {object} options Options for ajax request
 */
export const init = (options) => {
    const main = new Main(options);
    main.initializer();
};
