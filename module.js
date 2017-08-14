M.mod_forumng = {
    Y : null,
    cloneparam: '',
    cloneid: 0,
    cmid: 0,
    ratingstars: 0,
    discussionid: 0,
    expirelinks: [],
    select: {},
    quotaleft: 0,
    loaderpix: '',
    mouseuser: false,
    viewportwidth: -1,
    starpix: {},
    nowediting: false,

    /**
     * Main init function called from HTML. Sets a few variables then listens for DOM ready.
     * @param Y YUI object
     * @param cmid Course-module id of this forum
     * @param cloneid Clone ID or 0 if none
     * @param ratingstars If non-zero, the number of stars (1-5) used for ratings
     * @param quotaleft If set, the number of posts left in post quota
     * @param loaderpix URL of AJAX loader icon
     * @param starpix Object containing multiple URLs of the various star icons
     */
    init : function(Y, cmid, cloneid, ratingstars, quotaleft, loaderpix, starpix) {
        this.Y = Y;
        this.cmid = cmid;
        this.cloneid = cloneid;
        this.cloneparam = cloneid ? '&clone=' + cloneid : '';
        this.ratingstars = ratingstars;
        this.quotaleft = quotaleft;
        this.loaderpix = loaderpix;
        this.starpix = starpix;

        Y.on("domready", this.dom_init, M.mod_forumng);
        this.urgent_init();
    },

    /**
     * Simulates click on a link. YUI is supposed to be able to do this but
     * it blatantly doesn't work in IE (YUI ticket #2530063).
     * @param link Thing you want to click on (YUI node)
     */
    simulate_click : function(link) {
        var node = this.Y.Node.getDOMNode(link);
        if (node.click) {
            // IE has this function, it is also in DOM for some things
            node.click();
        } else {
            // For other browsers, hopefully YUI will work
            link.simulate('click');
        }
    },

    /**
     * Main initialisation done on DOM ready.
     */
    dom_init : function() {
        // b****y IE doesn't have a trim function
        if (!String.prototype.trim) {
            String.prototype.trim = function() {
                return this.replace(/^\s+/, '') . replace(/\s+$/, '');
            };
        }

        //Magicalise the hidden 'switch view mode' link
        if (document.getElementById('forumng-switchlinkid')) {
            var link = this.Y.one('#forumng-switchlinkid');
            this.init_switchlink(link);
        }

        // Handle pages other than the discussion page
        if (document.getElementById('page-mod-forumng-subscribers')) {
            this.init_subscribers();
            return;
        }
        if (document.getElementById('page-mod-forumng-view')) {
            this.init_view();
            return;
        }
        if (document.getElementById('page-mod-forumng-discuss')) {
            this.init_discuss();
            return;
        }
        if (document.getElementById('page-mod-forumng-feature-deletedposts-deletedpostslist')) {
            this.init_content(this.Y.one(document));
        }
        if (document.getElementById('page-mod-forumng-feature-print-print')) {
            this.init_content(this.Y.one(document));
            this.print_page();
            return;
        }
        if (document.getElementById('page-mod-forumng-deletepost')) {
            this.init_deletepost();
            return;
        }
    },

    /**
     * Urgent init; this is not done as urgently as it used to be :( because it's only
     * in footer. I probably need to figure out a better way to do it. TODO
     */
    urgent_init : function() {
        // Create new stylesheet in head
        var newstyle = document.createElement("style");
        newstyle.setAttribute("type", "text/css");

        var selector = '.forumng-ratings';
        var rules = 'display:none';

        if (document.styleSheets && document.styleSheets.length > 0 &&
            document.styleSheets[0].addRule) {
            // Internet Explorer addRule usage
            document.getElementsByTagName("head")[0].appendChild(newstyle);
            document.styleSheets[document.styleSheets.length - 1].addRule(
                selector, rules);
        } else {
            // Other browsers, just add stylesheet into DOM
            newstyle.appendChild(
                document.createTextNode(selector + " {" + rules + "}"));
            document.getElementsByTagName("head")[0].appendChild(newstyle);
        }
    },

    /**
     * Sets up the 'accessible mode switch' link so that it becomes visible (not accesshide)
     * if you tab to it, including its parent.
     * @param link Link tag
     */
    init_switchlink : function(link) {
        link.on('focus', function() {
            link.get('parentNode').setStyle('position', 'static');
        }, this);
        link.on('blur', function() {
            link.get('parentNode').setStyle('position', 'absolute');
        }, this);
    },

    /**
     * Initialises all JavaScript for the discussion page.
     */
    init_discuss : function() {
        // Get discussion id
        this.discussionid = window.location.search.replace(
            /^.*[?&]d=([0-9]+).*$/ , '$1');

        // Tell CSS that we have JS working
        this.Y.one('#forumng-main').removeClass('forumng-nojs');

        // To avoid having nested forms (breaks IE), remove the non-JS action form
        var div = this.Y.one('#forumng-actionform > div');
        if (div) {
            var form = div.get('parentNode');
            div.remove();
            form.get('parentNode').insertBefore(div, form);
            form.remove();
        }

        var div1 = this.Y.one('.forumng_deldiscussion');
        if (div1) {
            this.init_deldiscussion(div1);
        }

        // Set up magic links
        this.forumng_expirelinks = [];
        this.init_content(this.Y.one('#forumng-main'));

        // Hide 'save ratings' button if present
        var saveall = document.getElementById('forumng-saveallratings');
        if (saveall) {
            saveall.parentNode.removeChild(saveall);
        }

        // Init feature buttons
        this.init_feature_buttons(false);

        // Apply stop indents
        this.apply_stop_indents();
        var region = this.Y.one('#forumng-main').get('region');
        this.viewportwidth = region.right - region.left;
        setInterval(function() {
            var region = this.Y.one('#forumng-main').get('region');
            var width = region.right - region.left;
            if (width != M.mod_forumng.viewportwidth) {
                M.mod_forumng.viewportwidth = width;
                M.mod_forumng.apply_stop_indents();
            }
        }, 250);
    },

    /**
     * Initialises all JavaScript for the deletepost page.
     */
    init_deletepost : function() {
        // if JS is enabled then we can copy the html version of the text to
        // the textarea used by tinymce, otherwise plain text is used by default.
        var msg = this.Y.one('#delete-form-html');
        if (msg) {
            var messagehtml = msg.getContent();
            this.Y.one('#id_forumng_delete_msg').set('innerHTML', messagehtml);
        }
    },

    init_deldiscussion : function(div) {
        var form = div.get('parentNode').get('parentNode');
        form.on('submit', function(e) {
            e.preventDefault();
           var deletebuttons = new Array(
                   M.str.forumng.deleteemailpostbutton,
                   M.str.forumng.deletepostbutton
                   );
            this.confirm(M.str.forumng.confirmdeletediscuss,
                deletebuttons,
                M.str.moodle.cancel,
                null, [
                       function() {
                           location.href = form.get('action') + '?d=' +
                           M.mod_forumng.discussionid + '&delete=1' +
                           '&email=1' + '&clone=' + M.mod_forumng.cloneid;
                       }, function() {
                           location.href = form.get('action') + '?d=' +
                           M.mod_forumng.discussionid + '&delete=1' +
                           '&email=0' + '&notdeleted=1' + '&clone=' + M.mod_forumng.cloneid;
                       }]);
        }, this);

    },

    /**
     * Initialises 'content' i.e. posts and related. Can be called on the whole page or on
     * a single post.
     * @param node Node to run on (e.g. document node or a post div)
     */
    init_content : function(node) {
        // When the selector is in use, and this is being run on a single post, then
        // do special init for the post
        if (this.select.on && node.hasClass('forumng-post')) {
            this.select_init_post(node, true);
        }

        // Get post id listed as URL anchor, if any (initial run only)
        var expandposts = new Object;
        if (this.Y.Node.getDOMNode(node) == document || node.get('id') == 'forumng-main') {
            // Post from location bar
            if (window.location.hash) {
                var match = window.location.hash.match(/p([0-9]+)$/);
                if (match) {
                    expandposts[parseInt(match[1])] = true;
                }
            }
            // Posts listed as expanded (from Back button)
            var expandedList = this.Y.one('#expanded_posts');
            if (expandedList) {
                var posts = expandedList.get('value').split(',');
            } else {
                var posts = new Array();
            }
            for (var i=0; i<posts.length; i++) {
                expandposts[posts[i]] = true;
            }
        }

        // Kill reply links if necessary
        if (this.quotaleft == 0) {
            this.kill_reply_links();
        }

        // Add JS to other links
        node.all('a').each(function(link, index, list) {
            var href = link.get('href');

            // Ignore mobile links
            if (link.hasClass('forumng-mobilepost-link')) {
                return;
            }

            // Any link with &expires= will be hidden a bit before that time
            match = href.match(/[?&]expires=([0-9]+)(&|$)/);
            if (match) {
                this.init_expiry(link, parseInt(match[1]));
                href = link.get('href');
            }

            // Magicalise 'Expand' links
            var match = href.match(
                /\/discuss\.php\?d=([0-9]+).*&expand=1#p([0-9]+)$/);
            if (match && link.hasClass('forumng-expandlink')) {
                this.init_expand(link, match[2], expandposts[parseInt(match[2])]);
            }

            // Magicalise 'Reply' links
            match = href.match(/\/editpost\.php\?replyto=([0-9]+).*$/);
            if (match) {
                this.init_reply(link, parseInt(match[1]));
            }

            // Magicalise 'Edit' links
            match = href.match(/\/editpost\.php\?p=([0-9]+).*$/);
            if (match) {
                this.init_edit(link, parseInt(match[1]));
            }

            // Magicalise 'Delete' / 'Undelete' links
            match = href.match(/\/deletepost\.php\?p=([0-9]+)(?:&clone=[0-9]+)?(?:&expand=1)?(?:&delete=([0-9]+))?(?:&currentuser=([0-9]))?$/);
            if (match) {
                this.init_delete(link, parseInt(match[1]), match[2] && match[2]==0, match[3]);
            }

            // Magicalise the hidden parent-post links
            if (link.hasClass('forumng-parentlink')) {
                this.init_parentlink(link);
            }

            // Magicalise the jump-to links
            if (link.get('parentNode').hasClass('forumng-jumpto')) {
                this.init_jumplink(link);
            }

            // Magicalise the mark post read link.
            if (link.get('parentNode').hasClass('forumng-markread')) {
                this.init_postread(link);
            }
        }, this);

        // Magicalise rating sections
        node.all('div.forumng-ratings').each(function(rating, index, list) {
            this.init_rating(rating);
        }, this);

        // Find any inputs with the zero-disable feature
        node.all('input.forumng-zero-disable').each(function(input, index, list) {
            this.zero_disable(input);
        }, this);

        // Set up flags
        this.init_flags(node);
    },

    /**
     * Applies expiry to links. Some links are marked with &expires=(time) to indicate that the
     * JavaScript should disable them around that time. (Edit, delete links.)
     * @param link Link node
     * @param seconds Number of seconds to expire after
     */
    init_expiry: function(link, seconds) {
        // Actually expires a bit early
        link.forumng_expiry_javatime = seconds * 1000 - 45000 + new Date().getTime();
        link.set('href', link.get('href').replace(/[?&]expires=[0-9]+/, ''));

        this.expirelinks.push(link);
        if (this.expirelinks.length == 1) {
            var timerid = setInterval(function() {
                var current = new Date().getTime();
                for (var i=M.mod_forumng.expirelinks.length-1; i>=0; i--) {
                    if (current > M.mod_forumng.expirelinks[i].forumng_expiry_javatime) {
                        var deadlink = M.mod_forumng.expirelinks[i];
                        deadlink.get('parentNode').remove();
                        M.mod_forumng.expirelinks.splice(i, 1);
                    }
                }
                if (M.mod_forumng.expirelinks.length == 0) {
                    clearInterval(timerid);
                }
            }, 15000);
        }
    },

    /**
     * Removes reply links within a given tag. This is used if you run out of quota so that
     * you cannot create new replies.
     * @param root Root node to search within
     */
    kill_reply_links: function(root) {
        this.Y.all('#forumng-main a[href]').each(function(link, index, list) {
            if (link.get('href').match(/editpost\.php\?replyto=[0-9]+.*$/)) {
                link.remove();
            }
        });
    },

    /**
     * Removes iframe and marks it closed.
     *
     * @param scrollToParent If true (default), scrolls to parent after removing iframe
     */
    remove_iframe : function(iframe, scrollToParent) {
        var parent = iframe.get('parentNode');
        parent.removeChild(iframe);
        if (M.is_mobile) {
            // Hide iframe container also.
            parent.setStyle('display', 'none');
            // Remove scroll event trapping.
            Y.one('window').detach('scroll');
        }
        this.nowediting = false;
        if (scrollToParent === undefined || scrollToParent) {
            this.scroll_page(parent.get('parentNode'));
        }
        this.links_enable(document.body);
    },

    /**
     * Initialises an iframe and adds it.
     *
     * @param src iframe URL (&iframe=1 automatically added)
     * @param post Post node to add iframe
     * @return Iframe YUI node or null if already editing (abort)
     */
    init_iframe : function(src, post) {
        // Check we're not already editing
        var t = this;
        if (this.nowediting) {
            return null;
        }
        this.nowediting = true;

        // Add special style that marks links disabled
        this.links_disable(document.body);

        var iframecon = this.Y.one(document.createElement('div'));
        iframecon.addClass('iframecon');
        var iframe = this.Y.one(document.createElement('iframe'));
        iframe.set('className', 'forumng-inlineform');
        iframe.set('name', 'forumng-post-iframe');
        iframe.set('height', 500);
        src += '&iframe=1';
        iframe.set('src', src);
        window.iframe_has_loaded = function(innerwin) {
            // Note: I am avoiding using YUI because I think there are
            // probably two YUI instances and I don't want it to get
            // confused.
            var doc = innerwin.document;
            var counter = 0;

            // Roll up edit author fieldset in edit post (do here so height correct).
            var editemailhead = innerwin.document.getElementById('id_id_emailauthor');
            if (editemailhead) {
                editemailhead.className += ' collapsed';
            }

            // Check size and enlarge iframe if required.
            var fix_height = function() {
                if(doc.body.scrollHeight > Number(iframe.get('height'))) {
                    iframe.set('height', (doc.body.scrollHeight + 2));
                    iframecon.set('height', (doc.body.scrollHeight + 2));
                }

                // Check if the mobile view is activated, if so, then we align the
                // iframe to the top left position and make it dominate the whole page,
                // which basically make it behave like a pop-up overlay dialog.
                //
                // Create + set M.is_mobile in the mobile theme to activate.
                if (M.is_mobile) {
                    iframecon.setStyle('display', 'block');
                    iframecon.setStyle('width', '100%');
                    iframecon.setStyle('height', '100%');
                    if (M.mod_forumng.Y.UA.ios) {
                        // Make fixed div work in iOS.
                        iframecon.setStyle('position', 'fixed');
                        iframecon.setStyle('top', '0px');
                        iframecon.setStyle('left', '0px');
                        iframecon.setStyle('z-index', '9999');
                        iframecon.setStyle('overflow', 'scroll');
                        iframecon.setStyle('-webkit-overflow-scrolling', 'touch');
                        iframe.setStyle('position', 'relative');
                        iframe.setStyle('top', 'initial');
                        iframe.setStyle('left', 'initial');
                    } else {
                        iframe.setStyle('position', 'fixed');
                        iframe.setStyle('top', '0px');
                        iframe.setStyle('left', '0px');
                        iframe.setStyle('z-index', '9999');
                    }
                    iframe.setStyle('height', '100%');
                    iframe.setStyle('width', '100%');
                    iframe.setStyle('overflowX', 'hidden');
                    iframe.setStyle('overflowY', 'auto');
                    if (iframe.get('clientHeight') >= iframe.get('contentWindow.document').one('#page').get('clientHeight')) {
                        // If iframe size larger than content then hide scroll.
                        iframe.set('scrolling', 'no');
                    } else {
                        iframe.set('scrolling', 'auto');
                    }
                    doc.body.focus();
                    window.scrollTo(0, 0);
                } else {
                    if (M.cfg.theme.indexOf('ou') != -1 || M.theme_ou) {
                        if (iframe.get('parentNode') && iframe.get('parentNode').get('parentNode')) {
                            iframe.set('width', iframe.get('parentNode').get('parentNode').getComputedStyle('width'));
                        }
                        if (M.mod_forumng.Y.UA.ios || M.mod_forumng.Y.UA.android) {
                            var margin = parseInt(iframe.get('parentNode').get('parentNode').getComputedStyle('width')) - doc.body.scrollWidth;
                            if (margin < 0) {
                                iframecon.setStyle('margin-left', margin);
                                iframe.setStyle('width', doc.body.scrollWidth);
                                iframecon.setStyle('width', doc.body.scrollWidth);
                                }
                            }
                        }
                    }
                counter++;
                if (counter < 20) {
                    // Keep resizing iframe as filemanager takes a while to initialise.
                    setTimeout(fix_height, 500);
                }
            };
            fix_height();

            // Add cancel handler that just removes the iframe - Except Atto as autosave cancel needed.
            doc.getElementById('id_cancel').onclick = function(e) {
                var blank = false;// Check if empty text, if so close iframe (Hack to stop 'required' issue).
                if (innerwin.Y.one('#id_message') && (innerwin.Y.one('#id_message').get('innerText') === ''
                        || innerwin.Y.one('#id_message').get('textContent') === '')) {
                    blank = true;
                }
                if (!innerwin.Y.one('.editor_atto') || blank) {
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
                    t.remove_iframe(iframe);
                    return false;
                }
            };

            // Focus the editor.
            var try_focus = function() {
                if (innerwin.tinyMCE) {
                    for(edId in innerwin.tinyMCE.editors) {
                        if (edId === 'id_message') {
                            innerwin.tinyMCE.execCommand('mceFocus', false, 'id_message');
                            return;
                        }
                    }
                }
                setTimeout(try_focus, 100);
            };
            if (!M.is_mobile) {
                setTimeout(try_focus, 250);
            }
        };

        // Put it in as last thing in post (except the 'end post' marker).
        var ends = post.all('div.forumng-endpost');
        var last = ends.pop();
        last.get('parentNode').insertBefore(iframecon, last);
        iframecon.append(iframe);

        return iframe;
    },

    /**
     * Initialises edit links.
     * @param link Link node
     * @param postid Post ID to edit
     */
    init_edit: function(link, postid) {
        var t = this;
        link.on('click', function(e) {
            e.preventDefault();
            if (this.are_links_disabled(link)) {
                return;
            }

            // Get post.
            var post = link.ancestor('.forumng-post');
            var rootpost = !post.ancestor('.forumng-replies');

            // Make iframe.
            var src = 'editpost.php?p=' + postid;
            if (this.cloneid) {
                src += '&clone=' + this.cloneid;
            }
            var iframe = this.init_iframe(src, post);
            if (!iframe) {
                return;
            }

            // Function that gets called when the iframe has completed successfully.
            window.iframe_success = function(innerwin) {
                // Add item just in front of existing post, then delete existing.
                var scriptcommands = [];
                var newpost = t.prepare_new_post(innerwin, scriptcommands);
                if (newpost.get('innerHTML') !== '') {
                    post.get('parentNode').insertBefore(newpost, post);
                    post.get('parentNode').removeChild(post);
                    t.Y.later(100, this, function(post) {
                        // Reload all the images - fixes chrome issue.
                        post.all('img').each(function(img){
                            img.set('src', img.get('src'));
                        });
                    }, newpost);

                    // Run script commands.
                    for (var i=0; i<scriptcommands.length; i++) {
                        eval(scriptcommands[i]);
                    }

                    // For discussion, do special handling.
                    if (rootpost) {
                        // Get subject and remove its node.
                        var subjectinput = newpost.one('input[name=discussion_subject]');
                        var subject = subjectinput.get('value');
                        subjectinput.remove();

                        if (Y.one('.forumng_discussion_title')) {
                            // OSEP theme discussion title
                            Y.all('.forumng_discussion_title').setHTML(subject);
                        }

                        var navbar = Y.one('#page-header .navbar ul, #page-navbar ul');
                        if (navbar) {
                            // Find subject inside the breadcrumb (last <span> in last <li>).
                            var lastspan = navbar.one('li:last-child > span:last-child');

                            // Text is inside here, replace it.
                            if (lastspan) {
                                lastspan.get('childNodes').each(function (node, index, list) {
                                    node.remove();
                                });
                                lastspan.appendChild(document.createTextNode(' ' + subject));
                            }
                        }

                    }

                    // Sort out links.
                    t.init_content(newpost);
                }
                // Remove the iframe.
                // This needs to be palced here for mobile devices to work.
                t.remove_iframe(iframe);
                window.iframe_success = null;
            };
        }, this);
    },

    /**
     * Extracts a new post from the result of a reply or edit script.
     * @param innerwin Iframe window
     * @param scriptcommands Script commands will be added to this array
     * @return New post YUI node
     */
    prepare_new_post : function(innerwin, scriptcommands) {
        var responsetext = innerwin.document.body.firstChild.innerHTML;
        var newdiv = document.createElement('div');
        var scriptcommands = [];
        newdiv.innerHTML = this.extract_js(responsetext, scriptcommands);
        var newpost = newdiv.firstChild;
        newdiv.removeChild(newpost);
        newpost = this.Y.one(newpost);
        return newpost;
    },

    /**
     * Initialises reply links.
     * @param link Link node
     * @param postid Post ID to reply to
     */
    init_reply: function(link, replytoid) {
        var t = this;
        link.on('click', function(e) {
            e.preventDefault();
            if (this.are_links_disabled(link)) {
                return;
            }

            // This function is also used when setting up a reply that
            // includs existing draft text.
            var draft = window.forumng_draft ? window.forumng_draft : false;
            window.forumng_draft = null;

            // Get post.
            var post = link.ancestor('.forumng-post');

            // Make iframe.
            var src = 'editpost.php?';
            if (draft) {
                src += 'draft=' + draft.id;
            } else {
                src += 'replyto=' + replytoid;
            }
            if (this.cloneid) {
                src += '&clone=' + this.cloneid;
            }
            var iframe = this.init_iframe(src, post);
            if (!iframe) {
                return;
            }

            // Function that gets called when the iframe has completed successfully.
            window.iframe_success = function(innerwin) {
                // Get replies div.
                var replies;
                if (post.get('nextSibling')
                    && post.get('nextSibling').hasClass('forumng-replies')) {
                    replies = post.get('nextSibling');
                } else {
                    replies = document.createElement('div');
                    replies.className = 'forumng-replies';
                    replies = t.Y.one(replies);
                    post.get('parentNode').insertBefore(replies, post.get('nextSibling'));
                    t.apply_stop_indents();
                }

                // Add item there.
                var scriptcommands = [];
                var newpost = t.prepare_new_post(innerwin, scriptcommands);
                replies.appendChild(newpost);

                // Run script commands.
                for (var i=0; i<scriptcommands.length; i++) {
                    eval(scriptcommands[i]);
                }

                // Set up JavaScript behaviour in new post.
                t.init_content(newpost);

                // Update quota left.
                if (t.quotaleft > 0) {
                    t.quotaleft--;

                    // If out of quota, kill all the reply links
                    if (t.quotaleft == 0) {
                        t.kill_reply_links(document);
                    }
                }

                // Remove the iframe.
                // This needs to be palced here for mobile devices to work.
                t.remove_iframe(iframe, false);
                window.iframe_success = null;

                // Scroll to it (must do this after frame removed or height will be incorrect).
                t.scroll_page(newpost, null);
            };

            // Mark that we've got a reply there.
            iframe.replytoid = replytoid;

            var quotaDiv = this.Y.one('#id_postlimit1');
            if (quotaDiv) {
                var quotaItem = quotaDiv.ancestor('.fitem');
                if (this.quotaleft > 2 || this.quotaleft < 0) {
                    quotaItem.setStyle('display', 'none');
                } else {
                    quotaItem.setStyle('display', 'block');
                    var text = (this.quotaleft == 1)
                        ? M.str.forumng.quotaleft_singular
                        : M.str.forumng.quotaleft_plural;
                    text = text.replace('#', this.quotaleft);
                    quotaDiv.set('innerHTML', text);
                }
            }
        }, this);

        // When we create the reply link that a draft post uses, make it click itself
        if (window.forumng_draft && window.forumng_draft.parentpostid==replytoid) {
            setTimeout( function() { M.mod_forumng.simulate_click(link); }, 0);
        }
    },

    /**
     * Initialises all flag icons on the page (discussion or main page) or a post.
     * @param node Root element
     */
    init_flags: function(node) {
        node.all('div.forumng-flagpost').each(function(div) {
            this.init_flag_div(div);
        }, this);
    },

    /**
     * Initialises a single flag icon based on the div.
     * @param div forumng-flag div
     */
    init_flag_div: function(div) {
        div.anchor = div.one(' a');
        div.span = div.one(' span');
        // Get on state from image icon.
        div.icon = div.one(' img.icon');
        div.on = div.icon.get('src').match(/flag\.on/);
        // Remove all other event listeners just in case this func called multiple times.
        this.Y.Event.purgeElement(div.icon, false, 'click');
        div.anchor.on('click', function(e) {
            var cfg = {
                method: 'POST',
                timeout: 10000,
                context: M.mod_forumng,
                on: {
                    success: function(o) {
                        div.on = !div.on;
                        div.icon.set('src', div.icon.get('src').replace(/flag\.o(n|ff)/,
                                'flag.' + (div.on ? 'on' : 'off')));
                        div.anchor.set('href', div.anchor.get('href').replace(/\&flag=(0|1)/, '&flag=' + (div.on ? 0 : 1)));
                        div.anchor.set('title',
                                div.on ? M.str.forumng.clearflag : M.str.forumng.setflag);
                        var text = div.span.get('innerHTML');
                        if (text) {
                            div.span.set('innerHTML', div.on ? M.str.forumng.clearflag : M.str.forumng.flagpost);
                        }
                    },
                    failure: function(o) {
                        alert(M.str.forumng.jserr_alter);
                    }
                }
            };
            this.Y.io(div.anchor.get('href') + '&ajax=1', cfg);
            e.preventDefault();
        }, this);
    },

    init_postread: function(link) {
        var t = this;
        link.on('click', function(e) {
            e.preventDefault();
            if (t.are_links_disabled(link) || link.hasClass('disabled')) {
                return;
            }
            var cfg = {
                    method: 'GET',
                    timeout: 10000,
                    context: M.mod_forumng,
                    on: {
                        success: function(o) {
                            if (!o.content == 'ok') {
                                alert(M.str.forumng.jserr_alter);
                                return;
                            }
                            link.addClass('disabled');
                            link.set('disabled', 'disabled');
                            var post = link.ancestor('div.forumng-unread');
                            if (post) {
                                post.replaceClass('forumng-unread', 'forumng-read');
                            }
                        },
                        failure: function(o) {
                            alert(M.str.forumng.jserr_alter);
                        }
                    }
            };
            t.Y.io(link.get('href') + '&ajax=1', cfg);
        });
    },

    /**
     * Initialises the feature buttons that run along the bottom of a discussion. Some
     * of these may use the 'post selector' feature, which requires JavaScript.
     *
     * Looks for some ou-specific links that are treated as the real buttons
     *
     * @param islist True if discussion list
     */
    init_feature_buttons: function(islist) {
        // Get all forms
        if (islist) {
            // Feature btns on discussion list.
            this.Y.all('form.forumng-dselectorbutton input[type=submit], #osep-bottombutton-export, #osep-bottombutton-print').each(
                    function(node) {
                        // For hacked buttons do extra, inc passing original input node target.
                        if (node.get('tagName') === 'A') {
                            var discussions = this.Y.all('.forumng-discussionlist tr');
                            if (discussions.size() == 0) {
                                node.setStyle('display', 'none');
                            }
                        }
                        this.init_select_button(node, true);
            }, this);
        } else {
            var featureForms = this.Y.all('form.forumng-selectorbutton, #osep-bottombutton-export, #osep-bottombutton-print').each(
                    function(node, index, list) {
                        var submit;
                        if (node.get('tagName') === 'A') {
                            submit = node;
                        } else {
                            submit = node.one('input[type=submit]');
                        }
                this.init_select_button(submit, false);
            }, this);
        }
    },

    /**
     * Adds JS to the button which runs the selector feature, causing it to call the
     * 'confirm' prompt to ask whether you want to do the discussion or selected posts.
     * @param submit Submit button node
     * @param islist True if discussion list
     */
    init_select_button: function(submit, islist) {
        if (islist) {
            // Check there are discussions to select.
            var discussions = this.Y.all('.forumng-discussionlist tr');
            if (discussions.size() == 0) {
                submit.set('disabled', 'disabled');
                return;
            }
            submit.on('click', function(e) {
                e.preventDefault();
                if (submit.get('tagName') === 'A') {
                    if (submit.get('id') === 'osep-bottombutton-export') {
                        submit = this.Y.one('.forumngfeature_export form.forumng-dselectorbutton input[type=submit]');
                    } else if (submit.get('id') === 'osep-bottombutton-print') {
                        submit = this.Y.one('.forumngfeature_print form.forumng-dselectorbutton input[type=submit]');
                    }
                }
                var outerThis = this;
                // Pick up any discussion types we specifically include or exclude.
                var include = new Array();
                var exclude = new Array();
                var includeel = submit.get('form').get('children').item(0).one('input[name=include]');
                if (includeel) {
                    include = includeel.get('value').split(',');
                }
                var excludeel = submit.get('form').get('children').item(0).one('input[name=exclude]');
                if (excludeel) {
                    exclude = excludeel.get('value').split(',');
                }
                // Pick up inputs needed from form.
                var inputs = '';
                var inputnodes = submit.get('form').all('input[type=hidden]');
                // Some browsers (IE7) don't pick inputs in div up.
                if (inputnodes.size() == 0) {
                    inputnodes = submit.get('form').get('children').item(0).all('input[type=hidden]');
                }
                inputnodes.each(
                        function (node) {inputs += '&' + node.get('name') + '=' + node.get('value');}
                );

                this.confirm("<h4>" + submit.get('value') + "</h4><p>" +
                    M.str.forumng.selectordiscall + "</p>",
                    [M.str.forumng.selectoralldisc, M.str.forumng.selectorselecteddisc],
                    M.str.moodle.cancel,
                    null, [function() {
                        location.href = submit.get('form').get('action') + '?all=1' +
                        M.mod_forumng.cloneparam + inputs;
                    }, function() {
                        if (!this.Y.one('div.forumng-main > form')) {
                            outerThis.select_discuss_init(submit, include, exclude);
                        }
                    }]);
            }, this);
            return;
        }
        submit.on('click', function(e) {
            e.preventDefault();
            if (submit.get('tagName') === 'A') {
                if (submit.get('id') === 'osep-bottombutton-export') {
                    submit = this.Y.one('.forumngfeature_dis_export form.forumng-selectorbutton input[type=submit]');
                } else if (submit.get('id') === 'osep-bottombutton-print') {
                    submit = this.Y.one('.forumngfeature_dis_print form.forumng-selectorbutton input[type=submit]');
                }
            }
            var outerThis = this;
            this.confirm("<h4>" + submit.get('value') + "</h4><p>" +
                M.str.forumng.selectorall + "</p>",
                [M.str.forumng.discussion, M.str.forumng.selectedposts],
                M.str.moodle.cancel,
                null, [function() {
                    location.href = submit.get('form').get('action') + '?d=' +
                        M.mod_forumng.discussionid + M.mod_forumng.cloneparam + '&all=1';
                }, function() {
                    if (this.Y.one('div.forumng-selectbuttons')) {
                        outerThis.select_init(null);
                    }
                    outerThis.select_init(submit);
                }]);
        }, this);
    },

    /**
     * Stops indent from increasing once it gets past a certain minimum-post-width limit (this
     * varies depending on browser size). Without this, discussions with a lot of nesting levels
     * were getting ridiculous indent so that the actual posts were one word wide.
     */
    apply_stop_indents: function() {
        // Pick max indent level
        var region = this.Y.DOM.region(document.getElementById('forumng-main'));
        var width = region.right - region.left;
        var minwidth = 550; // Min size at which the stupid editor doesn't get cut off
        var maxindentpixels = width - minwidth;
        var stopIndent;

        // There are 5 indents of 40px then 5 of 30px, then all 20px
        if (maxindentpixels > 350) {
            stopIndent = 10 + Math.floor((maxindentpixels - 350) / 20);
        } else if (maxindentpixels > 200) {
            stopIndent = 5 + Math.floor((maxindentpixels - 200) / 30);
        } else {
            stopIndent = Math.floor(maxindentpixels / 40);
        }

        // Fix indents for all tags
        this.Y.all('div.forumng-replies').each(function(reply, index, list) {
            var indent = this.get_reply_indent(reply);
            if (indent == stopIndent){
                reply.addClass('forumng-stop-indent');
            } else {
                reply.removeClass('forumng-stop-indent');
            }
        }, this);
    },

    /**
     * Obtains indent level of a reply (i.e. how many levels it is indented by).
     * @param reply Reply div
     */
    get_reply_indent: function(reply) {
        // Use cached indent count if available
        if (reply.forumng_indent) {
            return reply.forumng_indent;
        }

        var indent = 1;
        // Go through each parent to find its nesting.
        var ancestor = reply.ancestor('div.forumng-replies');
        if (ancestor) {
            indent += this.get_reply_indent(ancestor);
        }
        reply.forumng_indent = indent;
        return indent;
    },

    /**
     * Disables links for a post or whole page. This is used to grey out other options while
     * you are replying to a post.
     *
     * Note that the disable status is remembered at the level of the element, so if you disable
     * it for a post, you should enable it for the same post too, not just the whole page.
     * @param root Element within which to disable command links
     */
    links_disable: function(root) {
        root.linksdisabled = true;
        var links = M.mod_forumng.Y.all('ul.forumng-commands a').getDOMNodes();
        for (var j = 0, len = links.length; j < len; j++) {
            links[j].oldonclick = links[j].onclick;
            links[j].onclick = function() {
                return false;
            };
            links[j].style.cursor = 'default';
            links[j].tabIndex = -1;
            if (links[j].className.indexOf('forumng-disabled') === -1) {
                links[j].className += ' forumng-disabled';
            }
        }
    },

    /**
     * Enables links again after they were disabled.
     * @param root Element within which to enable command links
     */
    links_enable: function(root) {
        root.linksdisabled = false;
        var links = M.mod_forumng.Y.all('ul.forumng-commands a').getDOMNodes();
        for (var j=0, len = links.length; j < len; j++) {
            if (links[j].oldonclick) {
                links[j].onclick = links[j].oldonclick;
                links[j].oldonclick = false; // Wanted to do 'delete' but it crashes ie
            } else {
                links[j].onclick = function(){};
            }
            links[j].style.cursor = 'auto';
            links[j].tabIndex = 0;
            links[j].className = links[j].className.replace(' forumng-disabled', '');
        }
    },

    /**
     * Checks if link are disabled including particular link
     * @param link YUI element pointing to link
     */
    are_links_disabled: function(link) {
        // True if links are disabled either at body or commands level
        return document.body.linksdisabled ||
                this.Y.Node.getDOMNode(link.ancestor('.forumng-commands')).linksdisabled;
    },

    /**
     * Initialises the HTML editor (if used) within a form, ready for a reply or edit.
     * @param form Form object
     * @param settings If set, contains information required to start the editor and attachments
     *   immediately without an AJAX request
     */
    init_editor_and_attachments: function(form, settings) {
        // If attachments are not permitted there is no need to make a server request
        // even for a reply
        if (!settings && !form.get('attachments')) {
            settings = { attachmentoptions : false, messageoptions : false, message: '' };
        }

        if (settings) {
            // Set up editor and attachments using predefined details
            this.start_editor(form, settings.message, settings.messageoptions);
            this.start_file_manager(form, settings.attachmentoptions);
        } else {
            // We need to make AJAX request for replies, to get the attachment IDs
            var cfg = {
                method: 'GET',
                timeout: 10000,
                context: this,
                arguments: form,
                on: {
                    success: this.initattachments_ok,
                    failure: this.initattachments_error
                }
            };
            this.Y.io('attachments.php?id=' + this.cmid + this.cloneparam, cfg);
        }
    },

    deep_clone_change_itemid : function(obj, itemid) {
        if (obj instanceof Array) {
          var c = [];
        } else {
          var c = {};
        }
        for (var key in obj) {
            if (key == 'itemid') {
                c[key] = itemid;
            } else if (typeof obj[key] == 'object') {
                c[key] = this.deep_clone_change_itemid(obj[key], itemid);
            } else {
                c[key] = obj[key];
            }
        }
        return c;
    },

    set_timeout_multi : function(fn, count) {
      if (count > 0) {
        setTimeout(function() { M.mod_forumng.set_timeout_multi(fn, count-1); }, 0);
      } else {
        fn();
      }
    },

    /**
     * Handles an AJAX response that is a 'success' by checking if it looks like
     * a Moodle error response and, if so, treating it as error instead.
     */
    possible_success_handler : function(ok, error) {
        return function(transactionid, o, form) {
            // 'Success' status still applies if we get a Moodle AJAX failure
            // exception, or if we get redirected to some other page such as
            // an SSO login page that produces actual HTML and not the data
            // we were expecting (which is either an HTML fragment or JSON
            // format depending on the request).
            if (/^{"error":/.test(o.responseText) ||
                    o.responseText.indexOf('</html>') != -1) {
                error.apply(M.mod_forumng, [transactionid, o, form]);
            } else {
                ok.apply(M.mod_forumng, [transactionid, o, form]);
            }
        };
    },

    /**
     * Initialises the 'parent' links so that when tabbed to, the parent div/text displays.
     * @param link Link node
     */
    init_parentlink: function(link) {
        link.on('focus', function() {
            link.get('parentNode').setStyle('position', 'static');
        }, this);
        link.on('blur', function() {
            link.get('parentNode').setStyle('position', 'absolute');
        }, this);
    },

    /**
     * Initialises an expand link so that it can use AJAX to retrieve the message.
     * @param link Link node
     * @param postid Post ID
     * @param expandnow If true, expand this post immediately
     */
    init_expand: function(link, postid, expandnow) {
        link.post = link.ancestor('.forumng-post');
        link.post.expandlink = link;
        link.loader = link.get('nextSibling');
        while (link.loader.get('nodeName').toLowerCase() != 'img') {
            link.loader = link.loader.get('nextSibling');
        }
        link.loader.originalsrc = link.loader.get('src');
        link.postid = postid;
        link.delay = true;

        // Replace 'expand all' text with 'expand this post'
        var postnum = link.post.get('className').replace(/^.*forumng-p([0-9]+).*$/, '$1');
        var text = link.one('.forumng-expandtext');
        if (text) {
            text.set('innerHTML', M.str.forumng.expand.replace('#', postnum));
        }
        // Add to post number to alt when using an image in expand link (expand_text lang string).
        var linkimg = link.one('img.fng-eai');
        if (linkimg) {
            linkimg.set('alt', linkimg.get('alt') + ' ' + postnum);
        }

        link.on('click', function(e) {
            e.preventDefault();
            if (link.inProcess) {
                return;
            }
            link.post.focushandler = null;

            var cfg = {
                method: 'GET',
                data: 'p=' + link.postid + this.cloneparam,
                timeout: 10000,
                context: this,
                arguments: link,
                on: {
                    success: this.expand_ok,
                    failure: this.expand_error
                }
            };
            this.Y.io('expandpost.php', cfg);
            link.loader.set('src', this.loaderpix);
            link.inProcess = true;
        }, this);

        // Automatically expand message listed in URL (if any)
        if (expandnow) {
            link.delay = false;
            M.mod_forumng.simulate_click(link);
        }
    },

    /**
     * Some browsers cannot execute JavaScript just by inserting script tags.
     * To avoid that problem, remove all script tags from the given content,
     * and run them later.
     * @param text HTML content
     * @param scriptCommands Array of commands (the commands will be pushed
     *   into this)
     * @return New text with JS removed
     */
    extract_js: function(text, scriptCommands) {
        var scriptRegexp = /<script[^>]*>([\s\S]*?)<\/script>/g;

        while ((result = scriptRegexp.exec(text)) != null) {
            scriptCommands.push(result[1]);
        }

        return text.replace(scriptRegexp, '');
    },

    /**
     * AJAX response: Expand completes successfully.
     * @param transactionid YUI transaction id
     * @param o YUI response object
     * @param link Link node
     */
    expand_ok: function(transactionid, o, link) {
        var newDiv = document.createElement('div');
        var scriptCommands = [];
        newDiv.innerHTML = this.extract_js(o.responseText, scriptCommands);
        var newpost = newDiv.firstChild;
        newDiv.removeChild(newpost);
        newpost = this.Y.one(newpost);
        var focushandler = link.post.focushandler;

        // If in select mode, note previous selection value
        var previousSelect = false;
        if (this.select.on) {
            previousSelect = this.Y.one('#checkp' + link.postid).get('checked');
        }

        var expander = new forumng_expander(link.post);
        var linkpostparent = link.post.get('parentNode');
        if (linkpostparent) {
            linkpostparent.insertBefore(newpost, link.post);
        }
        link.post.remove();

        // Run script commands
        for (var i=0; i<scriptCommands.length; i++) {
            eval(scriptCommands[i]);
        }

        this.init_content(newpost);
        if (previousSelect) {
            var checkbox = this.Y.one('#checkp' + link.postid);
            checkbox.set('checked', 'true');
        }
        if (document.body.linksdisabled) {
            this.links_disable(newpost);
            // It is not individually disabled, only as part of the general disable, so remove
            // the individual marker
            newpost.linksdisabled = false;
        }

        var tracker = this.Y.one('#expanded_posts');

        tracker.set('value', tracker.get('value') + (tracker.get('value')=='' ? '' : ',') +
                 link.postid);

        // For core ratings, init js on expand.
        if (newpost.one('.forumng-ratings-standard')) {
            if (M.core_rating && M.core_rating.init) {
                M.core_rating.init(this.Y);
            } else if (M.local_themeextras && M.local_themeextras.rating.init) {
                // OU only code for custom ratings.
                M.local_themeextras.rating.init(link.postid);
                M.local_themeextras.rating.init_count_popup(link.postid);
            }
        }

        if (!link.delay) {
            // Skip the expanding animation
            return;
        }

        expander.go(newpost);

        if (focushandler) {
            focushandler();
        } else {
            // Replace focus on expand element which got wiped.
            var authorspan = newpost.all('span.forumng-author');
            if (authorspan.length > 0) {
                // By default, focus on author name link.
                // The timeout here is because otherwise IE7 sometimes crashes
                setTimeout(function() { authorspan[0].get('firstChild').focus(); }, 0);
            } else {
                // If author name link is not present, focus on first link (which is usually
                // the 'this is post 3, parent is post N' link).
                var links = newpost.all('a[href]');
                if (links.length > 0) {
                    links[0].focus();
                }
            }
        }
    },

    /**
     * AJAX response: Expand fails.
     * @param transactionid YUI transaction id
     * @param o YUI response object
     * @param form Form node
     */
    expand_error: function(transactionid, o, link) {
        link.inProcess = false;
        link.loader.set('src', link.loader.originalsrc);
        alert(M.str.forumng.jserr_load);
    },

    /**
     * Removes 'px' from the end of a string, if present, and converts it to a number.
     * @param string Text that possibly is a number with 'px' in
     * @return Value as number
     */
    remove_px: function(string) {
        return parseInt(string.replace(/px$/, ''));
    },

    /**
     * Initialises a 'jump' link (next unread).
     * @param link Link node
     */
    init_jumplink: function(link) {
        link.on('mousedown', function() {
            this.mouseuser = true;
        }, this);
        link.on('click', function(e) {
            e.preventDefault();
            var id = link.get('href').substring(link.get('href').indexOf('#') + 1);

            // Function to set up focus
            var focuser = function() {
                var targetpost = M.mod_forumng.Y.one('#' + id).get('parentNode');
                var jumpto = targetpost.one('.forumng-jumpto');
                if (M.mod_forumng.mouseuser && jumpto) {
                    // For mouse (~= visual) users, focus the next link so that after
                    // clicking the first time, they can then repeatedly press return
                    var equivalent = jumpto.one('a.' + link.get('className'));
                    if (equivalent) {
                        M.mod_forumng.focus(equivalent);
                    } else {
                        var prev = jumpto.one('a.forumng-prev');
                        var next = jumpto.one('a.forumng-next');
                        if (prev || next) {
                            M.mod_forumng.focus(prev ? prev : next);
                        } else {
                            M.mod_forumng.focus(jumpto.one('a'));
                        }
                    }
                } else {
                    // For keyboard-only users, go to the start of the post (makes more sense)
                    var author = targetpost.one('.forumng-author');
                    M.mod_forumng.focus(author.one('a'));
                }
            };

            // Get link target and expand it if required
            var targetpost2 = this.Y.one('#' + id).get('parentNode');
            if (targetpost2.expandlink) {
                M.mod_forumng.simulate_click(targetpost2.expandlink);
                targetpost2.focushandler = focuser;
            }
            var targetpost3 = this.Y.one('#' + id).get('parentNode');
            // If post has already been expanded, focus it now
            if (!targetpost3.focushandler) {
                focuser();
            }

            // Scroll to it
            this.scroll_page(targetpost2);
        }, this);
    },

    /**
     * Scrolls the page so that a given target is at the top.
     * @param target Node to scroll to
     * @param after Callback to run after scrolling finishes
     */
    scroll_page: function(target, after) {
        var scrollTo = target.getY();

        var cfg = {
            node: this.Y.one(window),
            duration: Math.min(0.5, Math.abs(this.Y.DOM.docScrollY() - scrollTo)/200),
            easing: this.Y.Easing.easeOut,
            to: { scroll: [this.Y.DOM.docScrollX(), scrollTo] }
        };
        var anim = new this.Y.Anim(cfg);

        if (after) {
            anim.on('end', after, this);
        }
        anim.run();
    },

    /**
     * Focuses the given node (after timeout).
     * @param x Node to focus
     */
    focus : function(x) {
        setTimeout(function() { x.focus(); }, 0);
    },

    /**
     * Initialises a delete link.
     * @param link Link node
     * @param postid Post ID
     * @param undeleted True if it's actually undelete
     */
    init_delete : function(link, postid, undelete, currentuser) {
        link.postid = postid;
        link.post = link.ancestor('.forumng-post');
        link.on('click', function(e) {
            e.preventDefault();
            if (this.are_links_disabled(link)) {
                return;
            }
            var deleteandemail = function() {
                    window.location = 'deletepost.php' + '?p=' + link.postid + M.mod_forumng.cloneparam +
                    '&delete=1' + '&ajax=1' + '&email=1'
                    };
            var deleteonly = function() {
                    var cfg = {
                            method: 'POST',
                            data: 'p=' + link.postid + M.mod_forumng.cloneparam +
                                    '&delete=' + (undelete ? 0 : 1) + '&ajax=1',
                            timeout: 10000,
                            context: M.mod_forumng,
                            arguments: link,
                            on: {
                                success: M.mod_forumng.delete_ok,
                                failure: M.mod_forumng.delete_error
                            }
                        };
                        M.mod_forumng.Y.io('deletepost.php', cfg);
                        M.mod_forumng.links_disable(link.post);
                        link.loader = document.createElement('img');
                        link.loader.alt = '';
                        link.loader.src = M.mod_forumng.loaderpix;
                        link.loader.style.position = 'absolute';
                        link.loader = M.mod_forumng.Y.one(link.loader);
                        link.get('parentNode').appendChild(link.loader);
                        var linkregion = M.mod_forumng.Y.DOM.region(M.mod_forumng.Y.Node.getDOMNode(link));
                        link.loader.setXY([linkregion.right + 3, linkregion.top]);
                    };
            if (currentuser) {
                var deletebuttons = new Array(M.str.forumng.deletepostbutton);
                var deleteoptions = new Array(deleteonly, '');
            } else {
                var deletebuttons = new Array(M.str.forumng.deleteemailpostbutton,M.str.forumng.deletepostbutton);
                var deleteoptions = new Array(deleteandemail, deleteonly, '');
            }
            this.confirm(
                    undelete ? M.str.forumng.confirmundelete : M.str.forumng.confirmdelete,
                    undelete ? M.str.forumng.undeletepostbutton : deletebuttons,
                    M.str.moodle.cancel, link.post,
                    undelete ? deleteonly : deleteoptions
                    );
        }, this);
    },

    /**
     * AJAX response: Delete completes OK / Set rating completes OK.
     * @param transactionid YUI transaction id
     * @param o YUI response object
     * @param link Link (for delete) or div (for rating), which contains a '.post' variable
     *   for the post object
     */
    delete_ok : function(transactionid, o, link) {
        var newDiv = document.createElement('div');
        newDiv.innerHTML = o.responseText;
        var newpost = newDiv.firstChild;
        // Post may be blank when deleting (if not admin)
        if (newpost) {
            newpost = this.Y.one(newpost);
            link.post.get('parentNode').insertBefore(newpost, link.post);
        }
        this.links_enable(link.post);
        link.post.remove();
        if (newpost) {
            this.init_content(newpost);
        }
    },

    /**
     * AJAX response: Delete fails / Set rating fails.
     * @param transactionid YUI transaction id
     * @param o YUI response object
     * @param link Link (for delete) or div (for rating), which contains a '.post' variable
     *   for the post object
     */
    delete_error : function(transactionid, o, link) {
        if (link.loader) {
            link.loader.remove();
        }
        this.links_enable(link.post);
        alert(M.str.forumng.jserr_alter);
    },

    /**
     * Displays a fancy dialog box on a faded-out background in the middle of the
     * screen.
     * @param message Message to display (may include html; if heading is included,
     *     we recommend h4)
     * @param actiontext Name for action button(s). May be a single string or
     *     array if you need multiple buttons
     * @param canceltext Name for cancel button
     * @param highlight HTML element that should be highlighted (with an orange
     *     box), used e.g. to indicate which post is being deleted
     * @param action Function that gets run if user clicks the action button
     *     (if there are multiple action buttons, this too must be an array)
     */
    confirm : function(message, actiontext, canceltext, highlight, action) {
        if (typeof actiontext == 'string') {
            // There is only one action (text and functions); make it look like an array
            actiontext = [actiontext];
            action = [action];
        }

        var fadepanel = document.createElement('div');
        fadepanel.className = 'forumng-fadepanel';
        document.body.appendChild(fadepanel);
        fadepanel.style.position = 'absolute';
        fadepanel.style.top = '0';
        fadepanel.style.left = '0';
        fadepanel.style.width = this.Y.DOM.docWidth() + "px";
        fadepanel.style.height = this.Y.DOM.docHeight() + "px";
        fadepanel.style.zIndex = 10;
        fadepanel = this.Y.one(fadepanel);
        fadepanel.setStyle('opacity', '0.0');

        var anim = new this.Y.Anim({
            node: fadepanel,
            from: {'opacity': 0.0},
            to: {'opacity': 0.5},
            duration: 0.25,
            easing : this.Y.Easing.easeNone});
        anim.run();

        var highlightdiv = null;
        if (highlight) {
            var highlightregion = highlight.get('region');

            highlightdiv = document.createElement('div');
            highlightdiv.className = 'forumng-highlightbox';
            document.body.appendChild(highlightdiv);
            highlightdiv.style.position = 'absolute';
            highlightdiv.style.top = highlightregion.top + 'px';
            highlightdiv.style.left = highlightregion.left + 'px';
            highlightdiv.style.zIndex = 15;
            highlightdiv = this.Y.one(highlightdiv);
            var height = highlightregion.bottom - highlightregion.top -
                this.remove_px(highlightdiv.getStyle('borderTopWidth')) -
                this.remove_px(highlightdiv.getStyle('borderBottomWidth')) -
                this.remove_px(highlightdiv.getStyle('paddingTop')) -
                this.remove_px(highlightdiv.getStyle('paddingBottom'));
            var width = highlightregion.right - highlightregion.left -
                this.remove_px(highlightdiv.getStyle('borderLeftWidth')) -
                this.remove_px(highlightdiv.getStyle('borderRightWidth')) -
                this.remove_px(highlightdiv.getStyle('paddingLeft')) -
                this.remove_px(highlightdiv.getStyle('paddingRight'));

            highlightdiv.setStyle('height', height + 'px');
            highlightdiv.setStyle('width', width + 'px');
        }

        var dialog = document.createElement('div');
        document.body.appendChild(dialog);
        dialog.className = 'forumng-confirmdialog';

        dialog.style.position = 'absolute';
        dialog.style.zIndex = 20;

        dialog = this.Y.one(dialog);

        var region = this.Y.DOM.viewportRegion();
        region.height = region.bottom - region.top;
        region.width = region.right - region.left;
        dialog.setStyle('top', (region.top + region.height / 3) + "px");

        // Set the dialog box width according to the browser window size.
        var page = this.Y.DOM.region(Y.one("#page"));
        var pixelsWidth = page.right - page.left;
        var leftAdjuster = 0;
        var requiredBoxWidth = 630;
        if (pixelsWidth < 700) {
            requiredBoxWidth = pixelsWidth - 40;
            leftAdjuster = 5;
        }
        dialog.setStyle('width', (requiredBoxWidth - 10) + "px");
        // Centre Dialog box.
        var leftValue = region.left + region.width / 2 - (requiredBoxWidth / 2) - leftAdjuster;
        // Ensure that the left property is not less than 0.
        if ((leftValue) < 1) {
            leftValue = 0;
        }
        dialog.setStyle('left', (leftValue) + "px");

        var messagediv = document.createElement('div');
        messagediv.className = 'forumng-message';
        messagediv.tabIndex = -1;
        messagediv.innerHTML = message;
        messagediv = this.Y.one(messagediv);
        dialog.appendChild(messagediv);

        var buttondiv = document.createElement('div');
        buttondiv.className = 'forumng-buttons';
        buttondiv = this.Y.one(buttondiv);
        dialog.appendChild(buttondiv);

        var cancel = document.createElement('input');
        cancel.type = 'button';
        cancel.value = canceltext;
        cancel = this.Y.one(cancel);
        cancel.on('click', function() {
            dialog.remove();
            fadepanel.remove();
            if (highlightdiv) {
                highlightdiv.remove();
            }
        }, this);

        for (var i = 0; i < actiontext.length; i++) {
            buttondiv.appendChild(this.confirm_make_button(actiontext[i], action[i], cancel, false));
        }

        this.focus(messagediv);
        buttondiv.appendChild(cancel);
    },

    /**
     * Makes a button for the confirm dialog.
     * @param actiontext Text
     * @param action Function to call if button is clicked
     * @param cancel Cancel button (which is automatically clicked before calling action)
     * @param focus True if this should be focused
     */
    confirm_make_button : function(actiontext, action, cancel, focus) {
        var yes = document.createElement('input');
        yes.type = 'button';
        yes.value = actiontext;
        yes = this.Y.one(yes);
        yes.on('click', function() {
            M.mod_forumng.simulate_click(cancel);
            action();
        }, this);
        if (focus) {
            this.focus(yes);
        }
        return yes;
    },

    /**
     * Logs data using Firebug if installed.
     * @param thing Thing to log
     */
    log : function(thing) {
        if (typeof(console) !== 'undefined') {
            console.debug(thing);
        }
    },

    /**
     * Initialises forum main page JavaScript.
     */
    init_view : function() {
        // Set the focus on the sort links when clicked
        this.focus_sort_links();

        // Set up all the draft links to use discuss.php instead of non-JS version
        this.Y.all('.forumng-main a').each(function(link, index, list) {
            var match = link.get('className').match(/^forumng-draftreply-([0-9]+)-([0-9]+)$/);
            if (match) {
                var linkmatch = link.get('href').match(/draft=([0-9]+)(&clone=[0-9]+)?$/);
                link.set('href', 'discuss.php?d=' + match[1] + this.cloneparam +
                        '&draft=' + linkmatch[1] + '#p' + match[2]);
            }
        }, this);

        // Set up flag icons
        this.init_flags(this.Y.one(document.body));

        // Change selected buttons into links with text in brackets
        this.Y.all('input.forumng-button-to-link').each(function(specialbutton, index, list) {
            this.turn_button_into_link(specialbutton);
        }, this);

        // Init feature buttons
        this.init_feature_buttons(true);
    },

    /**
     * Focuses sort links after reloading page when a sort link was clicked.
     */
    focus_sort_links : function() {
        var url = window.location.href;
        var searchindex = url.search(/&sortlink=/);
        if (searchindex != -1) {
            var sortlinkid = "sortlink_" + url.substr(searchindex + 10, 1);
            this.focus(this.Y.one('#' + sortlinkid));
        }
    },

    /**
     * On the main page, turns a button into a link with text in brackets (this is so we
     * can have commands which are POST requests but look like links).
     * @param button Button node
     */
    turn_button_into_link : function(button) {
        var span = this.Y.one(document.createElement('span'));
        span.appendChild(document.createTextNode('('));
        var link = document.createElement('a');
        link.appendChild(document.createTextNode(button.get('value')));
        link.href = '#';
        link = this.Y.one(link);
        link.on('click', function(e) {
            e.preventDefault();
            M.mod_forumng.simulate_click(button);
        }, this);
        span.appendChild(link);
        span.appendChild(document.createTextNode(') '));
        button.get('parentNode').insertBefore(span, button);
        button.setStyle('display', 'none');
    },

    /**
     * Initialises the post selector feature, switching the whole page into post select mode.
     * @param target Target button that indicates where the resulting selection will be posted,
     *   or null to cancel select mode
     */
    select_init : function(target) {
        this.select.on = target ? true : false;

        var posts = this.Y.all('div.forumng-post');
        var confirm = this.Y.Node.create('<input type="submit"/>');

        var extraneousDisplay = this.select.on ? 'none' : 'block';
        this.Y.one('#forumng-expandall').setStyle('display', extraneousDisplay);
        this.Y.one('#forumng-features').setStyle('display', extraneousDisplay);
        var subscribeOptions = this.Y.one('#forumng-subscribe-options');
        if (subscribeOptions) {
            subscribeOptions.setStyle('display', extraneousDisplay);
        }

        var main = this.Y.one('#forumng-main');
        if (this.select.on) {
            // Make form around main elements
            var form = this.Y.Node.create("<form method='post'/>");
            this.select.form = form;
            form.set('action', target.get('form').get('action'));
            main.addClass('forumng-selectmode');

            form.inputs = this.Y.Node.create("<div/>");
            form.appendChild(form.inputs);
            var field = this.Y.Node.create('<input type="hidden" name="d"/>');
            field.set('value', this.discussionid);
            form.inputs.appendChild(field);
            field = this.Y.Node.create('<input type="hidden" name="fromselect" value="1"/>');
            form.inputs.appendChild(field);
            if (this.cloneid) {
                field = this.Y.Node.create('<input type="hidden" name="clone"/>');
                field.set('value', this.cloneid);
                form.inputs.appendChild(field);
            }

            // Make intro
            form.intro = this.Y.Node.create('<div class="forumng-selectintro"/>');
            main.get('parentNode').insertBefore(form.intro, main);
            var introText = this.Y.Node.create('<p>' + M.str.forumng.selectintro + '</p>');
            form.intro.appendChild(introText);

            // Make buttons to select all/none
            var selectButtons = this.Y.Node.create('<div class="forumng-selectbuttons"/>');
            form.intro.appendChild(selectButtons);
            var all = this.Y.Node.create('<input type="button"/>');
            selectButtons.appendChild(all);
            all.set('value', M.str.moodle.selectall);
            all.on('click', function() {
                // update the posts oject so it works after expanding a post
                var posts = this.Y.all('div.forumng-post');
                for (var i=0; i<posts.size(); i++) {
                    if (!posts.item(i).check.get('checked')) {
                        M.mod_forumng.simulate_click(posts.item(i).check);
                    }
                }
                all.set('disabled', true);
                none.set('disabled', false);
            }, this);
            selectButtons.appendChild(document.createTextNode(' '));
            var none = this.Y.Node.create('<input type="button"/>');
            selectButtons.appendChild(none);
            none.set('value', M.str.moodle.deselectall);
            none.on('click', function() {
                // update the posts oject so it works after expanding a post
                var posts = this.Y.all('div.forumng-post');
                for (var i=0; i<posts.size(); i++) {
                    if (posts.item(i).check.get('checked')) {
                        M.mod_forumng.simulate_click(posts.item(i).check);
                    }
                }
                all.set('disabled', false);
                none.set('disabled', true);
            }, this);

            main.appendChild(form);

            // Make outro
            form.outro = this.Y.Node.create('<div class="forumng-selectoutro" />');
            form.appendChild(form.outro);

            confirm.set('value', M.str.forumng.confirmselection);
            form.outro.appendChild(confirm);

            form.outro.appendChild(document.createTextNode(' '));

            var cancel = this.Y.Node.create('<input type="button" id="forumng-cancel-select"/>');
            cancel.set('value', M.str.moodle.cancel);
            form.outro.appendChild(cancel);
            cancel.on('click', function() {
                this.select_init(null);
            }, this);

            this.scroll_page(form.intro, null);
        } else {
            var form = this.select.form;
            form.remove();
            form.intro.remove();
            form.outro.remove();
            main.removeClass('forumng-selectmode');
            this.select.form = null;
        }

        window.forumng_select_changed = function() {
            // update the posts oject so it works after expanding a post
            var posts = M.mod_forumng.Y.all('div.forumng-post');
            var ok = false;
            var checkcount = 0;
            for (var i=0; i<posts.size(); i++) {
                if (posts.item(i).check.get('checked')) {
                    ok = true;
                    checkcount++;
                }
            }
            none.set('disabled', !ok);
            confirm.set('disabled', !ok);
            all.set('disabled', checkcount == posts.size());
        };
        for (var i=0; i<posts.size(); i++) {
            this.select_init_post(posts.item(i), this.select.on);
        }
        if (this.select.on) {
            window.forumng_select_changed();
        }
    },

    /**
     * Initialises a single post within select mode.
     * @param post Post div
     * @param on True if select is being turned on, false if it's being turned off
     */
    select_init_post : function(post, on) {
        if (on) {
            var info = post.one('div.forumng-info');
            if (!info) {
                return;
            }
            var span = this.Y.Node.create('<span/>');
            var spanseparator = this.Y.Node.create('<span/>');
            info.appendChild(span);
            post.extraSpan = span;
            post.addClass('forumng-deselected');
            var postid = post.one('>a').get('id');

            spanseparator.addClass('forumng-separator');
            spanseparator.appendChild(document.createTextNode(' \u2022 '));
            span.appendChild(spanseparator);
            var check = this.Y.Node.create('<input type="checkbox"/>');
            check.set('id', 'check' + postid);
            post.check = check;
            span.appendChild(check);
            var label = this.Y.Node.create('<label class="accesshide"/>');
            label.setAttribute('for', check.get('id'));
            span.appendChild(label);
            label.appendChild(document.createTextNode(M.str.forumng.selectlabel));
            this.links_disable(document.body);

            var hidden = this.Y.one("input[name='select" + postid + "']");
            if (!hidden) {
                hidden = this.Y.Node.create('<input type="hidden" value="0"/>');
                hidden.set('name', 'select' + postid);
                this.select.form.appendChild(hidden);
            }
            post.forumng_hidden = hidden;

            check.on('click', function() {
                if (check.get('checked')) {
                    post.removeClass('forumng-deselected');
                    post.forumng_hidden.set('value', 1);
                } else {
                    post.addClass('forumng-deselected');
                    post.forumng_hidden.set('value', 0);
                }
                window.forumng_select_changed();
            }, this);
        } else {
            if (post.extraSpan) {
                post.extraSpan.remove();
            }
            post.removeClass('forumng-deselected');
            this.links_enable(document.body);
        }
    },

    /**
     * Initialises the discussion selector feature, switching the whole page into select mode.
     * @param target Target button that indicates where the resulting selection will be posted,
     *   or null to cancel select mode
     */
    select_discuss_init : function(target, includes, excludes) {
        this.select.on = target ? true : false;

        var discussions = this.Y.all('.forumng-discussionlist tr');
        if (discussions.size() == 0) {
            return;
        }
        var confirm = this.Y.Node.create('<input type="submit"/>');

        var extraneousDisplay = this.select.on ? 'none' : 'block';

        var main = this.Y.one('table.forumng-discussionlist');
        if (this.select.on) {
            // Make form around main elements.
            var form = this.Y.Node.create("<form method='post'/>");
            this.select.form = form;
            form.set('action', target.get('form').get('action'));
            main.addClass('forumng-selectmode');

            form.inputs = this.Y.Node.create("<div/>");
            form.appendChild(form.inputs);
            field = this.Y.Node.create('<input type="hidden" name="fromselect" value="1"/>');
            form.inputs.appendChild(field);
            if (this.cloneid) {
                field = this.Y.Node.create('<input type="hidden" name="clone"/>');
                field.set('value', this.cloneid);
                form.inputs.appendChild(field);
            }
            target.get('form').get('children').item(0).all('input').each(
                    function (node) {
                        if (node.get('type') == 'hidden') {
                            field = node.cloneNode(true);
                            form.inputs.appendChild(field);
                        }
                    }
            );

            // Make intro.
            form.intro = this.Y.Node.create('<div class="forumng-selectintro"/>');
            main.get('parentNode').insertBefore(form.intro, main);
            var introText = this.Y.Node.create('<p>' + M.str.forumng.selectdiscintro + '</p>');
            form.intro.appendChild(introText);

            // Make buttons to select all/none.
            var selectButtons = this.Y.Node.create('<div class="forumng-selectbuttons"/>');
            form.intro.appendChild(selectButtons);
            var all = this.Y.Node.create('<input type="button"/>');
            selectButtons.appendChild(all);
            all.set('value', M.str.moodle.selectall);
            all.on('click', function() {
                for (var i = 1; i < discussions.size(); i++) {
                    if (discussions.item(i).check && !discussions.item(i).check.get('checked')) {
                        M.mod_forumng.simulate_click(discussions.item(i).check);
                    }
                }
                all.set('disabled', true);
                none.set('disabled', false);
            }, this);
            selectButtons.appendChild(document.createTextNode(' '));
            var none = this.Y.Node.create('<input type="button"/>');
            selectButtons.appendChild(none);
            none.set('value', M.str.moodle.deselectall);
            none.on('click', function() {
                for (var i = 1; i < discussions.size(); i++) {
                    if (discussions.item(i).check && discussions.item(i).check.get('checked')) {
                        M.mod_forumng.simulate_click(discussions.item(i).check);
                    }
                }
                all.set('disabled', false);
                none.set('disabled', true);
            }, this);

            main.get('parentNode').insertBefore(form, this.Y.one('#forumng-features'));

            // Make outro.
            form.outro = this.Y.Node.create('<div class="forumng-selectoutro" />');
            form.appendChild(form.outro);

            confirm.set('value', M.str.forumng.confirmselection);
            form.outro.appendChild(confirm);

            form.outro.appendChild(document.createTextNode(' '));

            var cancel = this.Y.Node.create('<input type="button" id="forumng-cancel-select"/>');
            cancel.set('value', M.str.moodle.cancel);
            form.outro.appendChild(cancel);
            cancel.on('click', function() {
                this.select_discuss_init(null);
            }, this);

            this.scroll_page(form.intro, null);
            // Disable all discussion select buttons.
            this.Y.all('.forumng-dselectorbutton input').each(
                    function(node) {
                        node.set('disabled', 'disabled');
                    });
        } else {
            var form = this.select.form;
            form.remove();
            form.intro.remove();
            form.outro.remove();
            main.removeClass('forumng-selectmode');
            this.select.form = null;
            // Eanble all discussion select buttons.
            this.Y.all('.forumng-dselectorbutton input').each(
                    function(node) {
                        node.set('disabled', '');
                    });
        }

        for (var i = 0, len = discussions.size(); i < len; i++) {
            if (discussions.item(i).hasClass('forumng-discussion-short')) {
                var useid = discussions.item(i).get('id');
                useid = useid.replace('discrow_', '');
                // Check we interact with this discussion.
                var include = true;
                if (this.select.on) {
                    if (includes.length > 0) {
                        include = false;
                        for (var a = 0; a < includes.length; a++) {
                            if (discussions.item(i).hasClass(includes[a])) {
                                include = true;
                                break;
                            }
                        }
                    }
                    if (excludes.length > 0) {
                        for (var a = 0; a < excludes.length; a++) {
                            if (discussions.item(i).hasClass(excludes[a])) {
                                include = false;
                                break;
                            }
                        }
                    }
                }
                if (include) {
                    this.select_init_discuss(discussions.item(i), this.select.on, useid);
                }
            }
        }
        window.forumng_select_changed = function() {
            var ok = false;
            var checkcount = 0;
            var availcount = 0;
            for (var i = 0; i < discussions.size(); i++) {
                if (discussions.item(i).check) {
                    availcount++;
                    if (discussions.item(i).check.get('checked')) {
                        ok = true;
                        checkcount++;
                    }
                }
            }
            none.set('disabled', !ok);
            confirm.set('disabled', !ok);
            all.set('disabled', checkcount == availcount);
        };
        if (this.select.on) {
            window.forumng_select_changed();
        }
    },

    /**
     * Initialises a discussion row within select mode.
     * @param post Discussion row
     * @param on True if select is being turned on, false if it's being turned off
     * @param number Order number of discussion in list
     */
    select_init_discuss : function(post, on, number) {
        if (on) {
            var info = post.one('td.cell.c0');
            var span = this.Y.Node.create('<span class="dselectorcheck" />');
            var spanseparator = this.Y.Node.create('<span/>');
            info.prepend(span);
            post.extraSpan = span;
            post.addClass('forumng-deselected');
            var postid = number;

            spanseparator.addClass('forumng-separator');
            spanseparator.appendChild(document.createTextNode(' \u2022 '));
            var check = this.Y.Node.create('<input type="checkbox"/>');
            check.set('id', 'check' + postid);
            post.check = check;
            span.appendChild(check);
            var label = this.Y.Node.create('<label class="accesshide"/>');
            label.setAttribute('for', check.get('id'));
            span.appendChild(label);
            label.appendChild(document.createTextNode(M.str.forumng.selectlabel.replace('{$a}', number)));
            span.appendChild(spanseparator);
            this.links_disable(document.body);

            var hidden = this.Y.one("input[name='select" + postid + "']");
            if (!hidden) {
                hidden = this.Y.Node.create('<input type="hidden" value="0"/>');
                hidden.set('name', 'selectd' + postid);
                this.select.form.appendChild(hidden);
            }
            post.forumng_hidden = hidden;

            check.on('click', function() {
                if (check.get('checked')) {
                    post.removeClass('forumng-deselected');
                    post.forumng_hidden.set('value', 1);
                } else {
                    post.addClass('forumng-deselected');
                    post.forumng_hidden.set('value', 0);
                }
                window.forumng_select_changed();
            }, this);
        } else {
            if (post.extraSpan) {
                post.extraSpan.remove();
            }
            post.removeClass('forumng-deselected');
            this.links_enable(document.body);
        }
    },

    /**
     * Initialises rating div, either by turning the standard dropdown into an AJAX one with
     * its own button, or else by turning it into a number of stars.
     * @param div Rating div
     */
    init_rating : function(div) {
        div.addClass('forumng-ratings-jsenabled');
        div.post = div.ancestor('.forumng-post');
        div.ratingcount = 0;

        // If there is a select (=user allowed to edit rating), get it and use it to get detail
        // about current rating.
        var selects = div.all('select');
        if (selects.size() > 0) {
            div.selector = selects.item(0);
            div.postid = parseInt(div.selector.get('name').replace(/^rating/, ''));
            div.userrating = div.selector.get('value');
            div.canrate = true;
            div.hasuserrating = div.userrating != 999;
        }
        // If there is information about the current average rating (in a strong tag), get this
        // (there might not be, if rating is not displayed)
        var strongs = div.all('strong');
        if (strongs.size() > 0) {
            var strong = strongs.item(0);
            div.publicratingvalue = strong.get('firstChild').get('nodeValue');
            div.publicrating = parseInt(div.publicratingvalue.replace(/\s*\/.*$/,''));
            div.postid = parseInt(strong.get('id').replace(/^rating_for_/, ''));
            div.haspublicrating = true;
            div.ratingcount = parseInt(strong.get('parentNode').one('span').
                    get('firstChild').get('nodeValue'));
        }
        // Can user view rating?
        div.canview = div.hasClass('forumng-canview');

        if (this.ratingstars) {
            // Get rid of everything inside the area and replace it with magic stars
            div.get('childNodes').each(function(node, index, list) {node.remove();});
            div.starspan = this.Y.Node.create('<span/>');
            div.appendChild(div.starspan);
            div.stars = [];
            for (var i=0; i<=this.ratingstars; i++) {
                var star = this.Y.Node.create('<img width="16" height="16"/>');
                star.rating = i;
                star.set('alt', i);
                if (div.canrate) {
                    this.star_init_events(div, star);
                }
                div.starspan.appendChild(star);
                div.stars[i] = star;
            }

            // Set up number of votes
            div.countspan = this.Y.Node.create('<span/>');
            div.appendChild(div.countspan);

            // Set star initial value
            this.set_stars(div);
        } else {
            // No stars, just add AJAX to dropdown
            if (!div.selector) {
                return;
            }

            var newbutton = this.Y.Node.create('<input type="button"/>');
            newbutton.set('value', M.str.forumng.rate);
            div.selector.get('parentNode').insertBefore(newbutton, div.selector.get('nextSibling'));

            newbutton.on('click', function() {
                newbutton.disabled = true;

                var cfg = {
                    method: 'POST',
                    data: 'p=' + div.postid  + M.mod_forumng.cloneparam + '&rating=' +
                        div.selector.get('value') + '&ajax=1',
                    timeout: 10000,
                    context: this,
                    arguments: div,
                    on: {
                        success: this.delete_ok, // Not a delete but it does the same thing
                        failure: this.delete_error
                    }
                };
                this.Y.io('rate.php', cfg);
                this.links_disable(div.post);

                div.loader = this.Y.Node.create('<img alt="" style="position:absolute"/>');
                div.loader.set('src', this.loaderpix);
                div.get('parentNode').appendChild(div.loader);
                var byregion = this.Y.DOM.region(newbutton.getDOMNode());
                div.loader.setXY([byregion.right + 3, byregion.top + 2]);
            }, this);
        }
    },

    /**
     * Initialises events on a single star.
     * @param div Main rating div
     * @param star This star
     */
    star_init_events : function(div, star) {
        star.set('tabIndex', 0);

        star.clickfunction = function(e) {
            div.newrating = star.rating;
            if (div.hasuserrating && div.userrating == div.newrating) {
                div.newrating = 999;
            }

            var cfg = {
                method: 'POST',
                data: 'p=' + div.postid  + M.mod_forumng.cloneparam + '&rating=' +
                    div.newrating + '&ajax=1',
                timeout: 10000,
                context: M.mod_forumng,
                arguments: div,
                on: {
                    success: M.mod_forumng.star_ok,
                    failure: M.mod_forumng.delete_error
                }
            };
            M.mod_forumng.Y.io('rate.php', cfg);
            M.mod_forumng.links_disable(div.post);

            // Use the current star as a loader icon place
            star.set('src', M.mod_forumng.loaderpix);
        };

        star.on('click', star.clickfunction, this);
        this.Y.on('key', star.clickfunction, star, 'press:13,32', this);

        star.on('focus', function() {
            star.addClass('forumng-starfocus');
        }, this);
        star.on('blur', function() {
            star.removeClass('forumng-starfocus');
        }, this);

        star.on('mouseover', function() {
            div.hastemprating = true;
            div.temprating = star.rating;
            this.set_stars(div);
        }, this);
        star.on('mouseout', function() {
            div.hastemprating = false;
            this.set_stars(div);
        }, this);

    },

    /**
     * Updates stars according to current rating values.
     * @param div Main ratings div
     */
    set_stars : function(div) {
        var userpos, publicpos;
        var clearing = false;
        if (div.hastemprating) {
            if (div.hasuserrating && div.temprating == div.userrating) {
                clearing = true;
                userpos = -1;
            } else {
                userpos = div.temprating;
            }
        } else {
            userpos = div.hasuserrating ? div.userrating : -1;
        }
        publicpos = div.haspublicrating ? div.publicrating : -1;

        for (var i=0; i< div.stars.length; i++) {
            var user = i==userpos, pub = i<=publicpos;
            var key = (i==0 ? 'circle-' : 'star-') + (user ? 'y' : 'n') + "-" + (pub ? 'y' : 'n');
            div.stars[i].set('src', this.starpix[key]);
        }

        if (div.ratingcount) {
            var newtext = ' ' + (div.ratingcount == 1 ? M.str.forumng.js_nratings1 :
                    M.str.forumng.js_nratings.replace(/#/, div.ratingcount));
            var existing = div.countspan.get('firstChild');
            if (existing) {
                if (existing.get('nodeValue') != newtext) {
                    existing.remove();
                    existing = null;
                }
            }
            if (!existing) {
                div.countspan.appendChild(document.createTextNode(newtext));
            }
        }

        var title = clearing ? M.str.forumng.js_clicktoclearrating :
            div.temprating==1 ? M.str.forumng.js_clicktosetrating1 :
            M.str.forumng.js_clicktosetrating.replace(/#/, div.temprating);
        if (div.canview) {
            if (!div.haspublicrating) {
                title += ' ' + M.str.forumng.js_nopublicrating;
            } else {
                title += ' ' + M.str.forumng.js_publicrating.replace(/#/, div.publicrating);
            }
        }
        if (div.canrate) {
            if (!div.hasuserrating) {
                title += ' ' + M.str.forumng.js_nouserrating;
            } else {
                title += ' ' + M.str.forumng.js_userrating.replace(/#/, div.userrating);
            }
        }
        title += ' ' + M.str.forumng.js_outof.replace(/#/, this.ratingstars);

        for (var i=0; i<div.stars.length; i++) {
            div.stars[i].set('title', title.replace(/^\s*/, ''));
        }
    },

    /**
     * AJAX response: A star-click was handled OK.
     * @param transactionid YUI transaction id
     * @param o YUI response object
     * @param div Ratings div
     */
    star_ok : function(transactionid, o, div) {
        this.links_enable(div.post);
        div.userrating = div.newrating;
        div.hasuserrating = div.newrating != 999;
        var re = /<strong id="rating_for_[0-9]+">([0-9]+) \//;
        var match = re.exec(o.responseText);
        if (match) {
            div.publicrating = match[1];
            div.haspublicrating = true;
        } else {
            div.haspublicrating = false;
            div.ratingcount = 0;
        }
        var re = /<span class="forumng-count">([0-9]+)<\/span>/;
        var match = re.exec(o.responseText);
        if (match) {
            div.ratingcount = parseInt(match[1]);
        }
        this.set_stars(div);
    },

    /**
     * Sets up certain selects (currently just the 'Move discussion' one) so that their
     * accompanying button is disabled when the select is set to 0.
     * @param submit Submit button that goes with select
     */
    zero_disable : function(submit) {
        var select = submit.previous();
        if (!select || select.get('nodeName').toLowerCase() != 'select') {
            M.mod_forumng.log('Warning: Zero-disable feature incorrectly applied.');
            return;
        }
        var update = function() {
            if (submit.hasClass('forumng-zero-disable')) {
                submit.set('disabled', select.get('value') == 0);
            }
        };
        update();
        select.on('change', update, this);
    },

    /**
     * Asks browser to print the page.
     */
    print_page : function() {
        window.print();
    },

    /**
     * Initialises the subscriber list page JavaScript.
     */
    init_subscribers : function() {
        var buttonsDiv = this.Y.one('#forumng-buttons');
        var selectAll = this.Y.Node.create('<input type="button"/>');
        selectAll.set('value', M.str.moodle.selectall);
        buttonsDiv.appendChild(document.createTextNode(' '));
        buttonsDiv.appendChild(selectAll);
        var deselectAll = this.Y.Node.create('<input type="button"/>');
        deselectAll.set('value', M.str.moodle.deselectall);
        buttonsDiv.appendChild(document.createTextNode(' '));
        buttonsDiv.appendChild(deselectAll);

        var unsubscribe;
        var inputs = this.Y.one('#forumng-subscription-list').all('input');
        var all = [];
        for (var i=0; i<inputs.size(); i++) {
            var input = inputs.item(i);
            if (input.get('name').indexOf('user')==0) {
                all.push(input);
            }
            if (input.get('name') == 'unsubscribe') {
                unsubscribe = input;
            }
        }

        var update = function() {
            var allSelected=true, noneSelected=true;
            for (var i=0; i<all.length; i++) {
                if (all[i].get('checked')) {
                    noneSelected = false;
                } else {
                    allSelected = false;
                }
            }
            selectAll.set('disabled', allSelected);
            deselectAll.set('disabled', noneSelected);
            unsubscribe.set('disabled', noneSelected);
        };
        update();

        for (var i=0; i<all.length; i++) {
            all[i].on('click', update, this);
        };

        selectAll.on('click', function() {
            for (var i=0; i<all.length; i++) {
                all[i].set('checked', true);
            }
            update();
        }, this);

        deselectAll.on('click', function() {
            for (var i=0; i<all.length; i++) {
                all[i].set('checked', false);
            }
            update();
        }, this);
    }
};

/**
 * Expands an object. Construct with original object (to determine the initial
 * size) then add something into it or replace it, then call go() with the
 * new object.
 * @param originalobj Original object
 * @return void
 */
function forumng_expander(originalobj) {
    this.shrinkheight = originalobj==null ? 0 : M.mod_forumng.remove_px(
            originalobj.getComputedStyle('height'));
    this.lastheight = -1;

    /**
     * Starts expand animation.
     */
    this.go = function(newobj) {
        // Some browsers don't return current actual height, which means this
        // logic fails to work, so don't do the effect.
        if (isNaN(this.shrinkheight)) {
            return;
        }
        newobj.setStyle('maxHeight', this.shrinkheight + 'px');
        newobj.setStyle('overflow', 'hidden');
        var outer = this;

        var timeoutid = setInterval(function() {
            var currentheight = newobj.get('offsetHeight');
            if (outer.lastheight == currentheight) {
                newobj.setStyle('maxHeight', '');
                newobj.setStyle('overflow', 'visible');
                clearInterval(timeoutid);
                return;
            }
            outer.lastheight = currentheight;
            outer.shrinkheight += 20;
            newobj.setStyle('maxHeight', outer.shrinkheight + 'px');
        }, 20);
    }
}
