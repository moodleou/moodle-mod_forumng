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
 * Register a link handler to open mod/forumng/view.php links in the app
 *
 * @package    mod_forumng
 * @copyright  2018 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
(function (t) {
    t.mod_forumng = {};

    /* Register a link handler to open mod/forumng/view.php links anywhere in the app. */
    function AddonModForumngLinkHandler() {
        t.CoreContentLinksModuleIndexHandler.call(this, t.CoreCourseHelperProvider, 'mmaModForumng', 'forumng');
        this.name = "AddonModForumngLinkHandler";
    }
    AddonModForumngLinkHandler.prototype = Object.create(t.CoreContentLinksModuleIndexHandler.prototype);
    AddonModForumngLinkHandler.prototype.constructor = AddonModForumngLinkHandler;
    t.CoreContentLinksDelegate.registerHandler(new AddonModForumngLinkHandler());

    /**
     * Handles a request for more discussions, getting the next chunk and displaying.
     *
     * @param {object} that The this object when calling this function.
     * @param {object} infiniteScrollEvent
     */
    t.mod_forumng.loadMoreDiscussions = function(that, infiniteScrollEvent) {
        // Gather data.
        var total = that.CONTENT_OTHERDATA.totaldiscussions;
        var current = that.CONTENT_OTHERDATA.discussions.length;
        var groupid = that.CONTENT_OTHERDATA.defaultgroup;
        var cmid = that.module.id;
        var page = that.CONTENT_OTHERDATA.page + 1;
        // Are there any more discussions to be displayed?
        if (current < total) {
            // AJAX to get the next page of discussions.
            that.CoreSitesProvider.getCurrentSite().read(
                'mod_forumng_get_more_discussions', {cmid: cmid, groupid: groupid, pageno: page}
            ).then(function (response) {
                response.forEach(function (discussion) {
                    // Is'nt Angular/Ionic wonderful. This does all the work for us.
                    that.CONTENT_OTHERDATA.discussions.push(discussion);
                });
                that.CONTENT_OTHERDATA.page = page;
                infiniteScrollEvent.complete();
            });
        } else {
            infiniteScrollEvent.complete();
        }
    };

    /**
     * Handles a request for more posts, getting the next chunk and displaying.
     *
     * @param {object} that The this object when calling this function.
     * @param {object} infiniteScrollEvent
     */
    t.mod_forumng.loadMorePosts = function(that, infiniteScrollEvent) {
        var total = that.CONTENT_OTHERDATA.totalposts;
        var current = that.CONTENT_OTHERDATA.replies.length;
        var discussionid = that.CONTENT_OTHERDATA.discussionid;
        if (current < total) {
            that.CoreSitesProvider.getCurrentSite().read(
                'mod_forumng_get_more_posts', {discussionid: discussionid, from: current}
            ).then(function (response) {
                response.forEach(function (reply) {
                    that.CONTENT_OTHERDATA.replies.push(reply);
                });
                infiniteScrollEvent.complete();
            });
        } else {
            infiniteScrollEvent.complete();
        }
    };

    /**
     * Add a new discussion.
     *
     * This will support editing an existing discussion and offline with a little more development.
     *
     * @param {object} that The this object when calling this function.
     */
    t.mod_forumng.addDiscussion = function(that) {
        var subject = that.subject;
        var message = that.message;
        var groupId = that.CONTENT_OTHERDATA.group; // No group selection in this form; it is done on previous page.
        var discussionId = that.CONTENT_OTHERDATA.discussion; // This is 0 until we add editing.
        var forumngId = that.CONTENT_OTHERDATA.forumng;
        var attachments = that.CONTENT_OTHERDATA.files; // Type [FileEntry].
        //var discTimecreated = Date.now(); //TODO part of offline - that.timeCreated || Date.now();
        var saveOffline = false;
        var modal;
        var promise;

        if (!subject) {
            that.CoreUtilsProvider.domUtils.showErrorModal('addon.mod_forumng.erroremptysubject', true);
            return;
        }
        if (!message) {
            that.CoreUtilsProvider.domUtils.showErrorModal('addon.mod_forumng.erroremptymessage', true);
            return;
        }
        message = that.CoreTextUtilsProvider.formatHtmlLines(message);

        modal = that.CoreUtilsProvider.domUtils.showModalLoading('core.sending', true);

        // Upload attachments first if any.
        if (attachments.length) {
            promise = that.CoreFileUploaderProvider.uploadOrReuploadFiles(attachments, 'mod_forumng', forumngId)
                    .catch(function() {
                // Cannot upload them in online, save them in offline.
                return Promise.reject('Offline not yet enabled');
                //TODO switch below to our own offline functionality.
                // saveOffline = true;
                // return that.AddonModForumHelperProvider.uploadOrStoreNewDiscussionFiles(
                //         forumngId, discTimecreated, attachments, saveOffline);
            });
        } else {
            promise = Promise.resolve(1);
        }

        promise.then(function(draftAreaId) {
            if (saveOffline) {
                // Save discussion in offline.
                //TODO switch below to our own offline functionality.
                // return that.AddonModForumOfflineProvider.addNewDiscussion(forumngId, forumName, courseId, subject,
                //     message, options, groupId, discTimecreated).then(function() {
                //     // Don't return anything.
                // });
            } else {
                // Try to send it to server.
                var site = that.CoreSitesProvider.getCurrentSite();
                var params = {
                    forum: forumngId,
                    discussion: discussionId,
                    group: groupId,
                    subject: subject,
                    message: message,
                    draftarea: draftAreaId // Note this will be 1 if there are no files.
                };
                return site.write('mod_forumng_add_discussion', params).then(function(response) {
                    // Other errors ocurring.
                    if (!response || !response.discussion) {
                        return Promise.reject(that.CoreWSProvider.createFakeWSError(response.errormsg));
                    } else {
                        return response.discussion;
                    }
                });
                // Don't allow offline if there are attachments since they were uploaded fine.
                //TODO switch below to use our own offline functionality.
                // return that.AddonModForumProvider.addNewDiscussion(forumngId, forumName, courseId, subject, message, options,
                //    groupId, undefined, discTimecreated, !attachments.length);
            }
        }).then(function(discussionId) {
            if (discussionId) {
                // Data sent to server, delete stored files (if any).
                //TODO switch below to our own offline functionality.
                //that.AddonModForumHelperProvider.deleteNewDiscussionStoredFiles(this.forumId, discTimecreated);
                //TODO trigger new discussion event or similar?
            }
            //TODO check all functionality in core forum (new-discussion.ts) returnToDiscussions(discussionId) is covered.
            // Navigate back to the discussions page and refresh to show new discussion.
            t.mod_forumng.viewSubscribe =
                that.CoreAppProvider.appCtrl.viewDidEnter.subscribe(t.mod_forumng.forumngRefreshContent);
            that.NavController.pop();
        }).catch(function(msg) {
            that.CoreUtilsProvider.domUtils.showErrorModalDefault(msg, 'addon.mod_forum.cannotcreatediscussion', true);
        }).finally(function() {
            modal.dismiss();
        });
    };

    /**
     * Allows refreshing content after creating a new discussion.
     *
     * @param {object} view Object returned by subscription to viewDidEnter.
     */
    t.mod_forumng.forumngRefreshContent = function(view) {
        if (view.name === 'CoreSitePluginsModuleIndexPage') {
            t.mod_forumng.viewSubscribe.unsubscribe();
            delete t.mod_forumng.viewSubscribe;
            t.mod_forumng.currentDiscussionsPage.refreshContent();
        }
    };

    /**
     * Add a reply.
     *
     * This will support editing an existing post and offline with a little more development.
     *
     * @param {object} that The this object when calling this function.
     */
    t.mod_forumng.reply = function(that) {
        var subject = that.subject; // Can be empty or undefined - probably usually is!
        var message = that.message;
        var replyto = that.CONTENT_OTHERDATA.replyto;
        var forumngId = that.CONTENT_OTHERDATA.forumng;
        var attachments = that.CONTENT_OTHERDATA.files; // Type [FileEntry].
        //var discTimecreated = Date.now(); //TODO part of offline - that.timeCreated || Date.now();
        var saveOffline = false;
        var modal;
        var promise;

        if (!message) {
            that.CoreUtilsProvider.domUtils.showErrorModal('addon.mod_forumng.erroremptymessage', true);
            return;
        }
        message = that.CoreTextUtilsProvider.formatHtmlLines(message);

        modal = that.CoreUtilsProvider.domUtils.showModalLoading('core.sending', true);

        // Upload attachments first if any.
        if (attachments.length) {
            promise = that.CoreFileUploaderProvider.uploadOrReuploadFiles(attachments, 'mod_forumng', forumngId)
                    .catch(function() {
                // Cannot upload them in online, save them in offline.
                return Promise.reject('Offline not yet enabled');
                //TODO switch below to our own offline functionality.
                // saveOffline = true;
                // return that.AddonModForumHelperProvider.uploadOrStoreNewDiscussionFiles(
                //         forumngId, discTimecreated, attachments, saveOffline);
            });
        } else {
            promise = Promise.resolve(1);
        }

        promise.then(function(draftAreaId) {
            if (saveOffline) {
                // Save discussion in offline.
                //TODO switch below to our own offline functionality.
                // return that.AddonModForumOfflineProvider.addNewDiscussion(forumngId, forumName, courseId, subject,
                //     message, options, groupId, discTimecreated).then(function() {
                //     // Don't return anything.
                // });
            } else {
                // Try to send it to server.
                var site = that.CoreSitesProvider.getCurrentSite();
                var params = {
                    replyto: replyto,
                    message: message,
                    draftarea: draftAreaId
                };
                if (!(subject === undefined || subject === '')) {
                    params.subject = subject;
                }
                return site.write('mod_forumng_reply', params).then(function(response) {
                    if (!response || !response.post) {
                        return Promise.reject(that.CoreWSProvider.createFakeWSError(response.errormsg));
                    } else {
                        return response.post;
                    }
                });
                // Don't allow offline if there are attachments since they were uploaded fine.
                //TODO switch below to use our own offline functionality.
                // return that.AddonModForumProvider.addNewDiscussion(forumngId, forumName, courseId, subject, message, options,
                //    groupId, undefined, discTimecreated, !attachments.length);
            }
        }).then(function(postId) {
            if (postId) {
                // Data sent to server, delete stored files (if any).
                //TODO switch below to our own offline functionality.
                //that.AddonModForumHelperProvider.deleteNewDiscussionStoredFiles(this.forumId, discTimecreated);
                //TODO trigger new discussion event or similar?
            }
            //TODO check all functionality in core forum (new-discussion.ts) returnToDiscussions(discussionId) is covered.
            // Navigate back to the posts page and refresh to show new post.
            t.mod_forumng.viewPostsSubscribe =
                    that.CoreAppProvider.appCtrl.viewDidEnter.subscribe(t.mod_forumng.forumngRefreshPostsContent);
            that.NavController.pop();
        }).catch(function(msg) {
            that.CoreUtilsProvider.domUtils.showErrorModalDefault(msg, 'addon.mod_forum.cannotcreatereply', true);
        }).finally(function() {
            modal.dismiss();
        });
    };

    /**
     * Allows refreshing content after creating a reply.
     *
     * @param {object} view Object returned by subscription to viewDidEnter.
     */
    t.mod_forumng.forumngRefreshPostsContent = function(view) {
        if (view.name === 'CoreSitePluginsPluginPage') {
            t.mod_forumng.viewPostsSubscribe.unsubscribe();
            delete t.mod_forumng.viewPostsSubscribe;
            t.mod_forumng.currentPostsPage.refreshContent();
        }
    };

    // Following functions are called during page initialisation and allow adding new functionality
    // to the main component (coreCompileHtmlFakeComponent) as outerThis.

    /**
     * Initialisation for the discussions page.
     *
     * @param {object} outerThis The main component.
     */
    window.forumngDiscussionsPageInit = function(outerThis) {
        // Make loadMoreDiscussion available from the template.
        outerThis.loadMoreDiscussions = function(infiniteScrollEvent) {
            t.mod_forumng.loadMoreDiscussions(outerThis, infiniteScrollEvent);
        };
        // Same for isOnline.
        outerThis.isOnline = function() {
            return outerThis.CoreAppProvider.isOnline();
        };
        // Outerthis has the refreshContent function, so get a link to it here.
        t.mod_forumng.currentDiscussionsPage = outerThis;
    };

    /**
     * Initialisation for the posts page.
     *
     * @param {object} outerThis The main component.
     */
    window.forumngPostsPageInit = function(outerThis) {
        outerThis.loadMorePosts = function(infiniteScrollEvent) {
            t.mod_forumng.loadMorePosts(outerThis, infiniteScrollEvent);
        };
        outerThis.isOnline = function() {
            return outerThis.CoreAppProvider.isOnline();
        };
        t.mod_forumng.currentPostsPage = outerThis;
    };

    /**
     * Initialisation for the add discussion page.
     *
     * @param {object} outerThis The main component.
     */
    window.forumngAddDiscussionInit = function(outerThis) {
        outerThis.addDiscussion = function() {
            t.mod_forumng.addDiscussion(outerThis);
        };
        // Network online check that disables the submission button if the app is offline.
        if (!t.mod_forumng.subscription) {
            t.mod_forumng.subscription = outerThis.CoreAppProvider.network.onchange().subscribe(function(online) {
                if (!document.getElementById('mma-forumng-add-discussion-button')) {
                    t.mod_forumng.subscription.unsubscribe();
                    delete t.mod_forumng.subscription;
                    return;
                }
                // Disable the add discusion button if the device goes offline.
                document.getElementById('mma-forumng-add-discussion-button').disabled = !t.CoreAppProvider.isOnline();
            });
        }
    };

    /**
     * Initialisation for the reply page.
     *
     * @param {object} outerThis The main component.
     */
    window.forumngReplyInit = function(outerThis) {
        outerThis.reply = function() {
            t.mod_forumng.reply(outerThis);
        };
        if (!t.mod_forumng.replySubscription) {
            t.mod_forumng.replySubscription = outerThis.CoreAppProvider.network.onchange().subscribe(function(online) {
                if (!document.getElementById('mma-forumng-reply-button')) {
                    t.mod_forumng.replySubscription.unsubscribe();
                    delete t.mod_forumng.replySubscription;
                    return;
                }
                document.getElementById('mma-forumng-reply-button').disabled = !t.CoreAppProvider.isOnline();
            });
        }
    };
})(this);
