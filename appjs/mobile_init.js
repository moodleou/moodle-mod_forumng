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
    t.removeEvent = true;

    /* Register a link handler to open mod/forumng/view.php links anywhere in the app. */
    function AddonModForumngLinkHandler() {
        t.CoreContentLinksModuleIndexHandler.call(this, t.CoreCourseHelperProvider, 'mmaModForumng', 'forumng');
        this.name = "AddonModForumngLinkHandler";
    }
    AddonModForumngLinkHandler.prototype = Object.create(t.CoreContentLinksModuleIndexHandler.prototype);
    AddonModForumngLinkHandler.prototype.constructor = AddonModForumngLinkHandler;
    t.CoreContentLinksDelegate.registerHandler(new AddonModForumngLinkHandler());

    /* Register a link handler to open mod/forumng/discuss links anywhere in the app. */
    function AddonModForumNGModuleDiscussionLinkToPageHandler() {
        this.pattern = new RegExp('\/mod\/forumng\/discuss\\.php\\?d=(\\d+)#p(\\d+)');
        this.name = "AddonModForumNGModuleDiscussionLinkToPageHandler";
        this.priority = 0;
    }
    AddonModForumNGModuleDiscussionLinkToPageHandler.prototype = Object.create(t.CoreContentLinksHandlerBase.prototype);
    AddonModForumNGModuleDiscussionLinkToPageHandler.prototype.constructor = AddonModForumNGModuleDiscussionLinkToPageHandler;
    AddonModForumNGModuleDiscussionLinkToPageHandler.prototype.getActions = function(siteIds, url, params) {
        var action = {
            action: function(siteId, navCtrl) {
                t.CoreSitesProvider.getSite(siteId).then(function(site) {
                    site.read('mod_forumng_get_discussion', {discussionid: parseInt(params.d, 10)}).then(function(result) {
                        if (!result) {
                            return Promise.reject(that.CoreWSProvider.createFakeWSError(response.errormsg));
                        } else {
                            var pageParams = {
                                title: result.subject,
                                component: 'mod_forumng',
                                method: 'posts_view',
                                args: {discussionid: params.d},
                                initResult: {},
                            };
                            t.CoreContentLinksHelperProvider.goInSite(navCtrl, 'CoreSitePluginsPluginPage', pageParams, siteId);
                        }
                    });
                });
            }
        };
        return [action];
    };
    t.CoreContentLinksDelegate.registerHandler(new AddonModForumNGModuleDiscussionLinkToPageHandler());

/* Prefetch handler (download). */
    function AddonModForumngModulePrefetchHandler() {
        t.CoreCourseActivityPrefetchHandlerBase.call(this, t.TranslateService, t.CoreAppProvider, t.CoreUtilsProvider,
            t.CoreCourseProvider, t.CoreFilepoolProvider, t.CoreSitesProvider, t.CoreDomUtilsProvider,
            t.CoreFilterHelperProvider, t.CorePluginFileDelegate, t.CoreUrlUtilsProvider, t.CoreFileProvider, t.CoreConfigConstants);
        this.name = "AddonModForumngModulePrefetchHandler";
        this.modName = "forumng";
        this.component = "mod_forumng";
        this.updatesNames = /^configuration$|^.*files$/;
    }

    AddonModForumngModulePrefetchHandler.prototype = Object.create(t.CoreCourseActivityPrefetchHandlerBase.prototype);
    AddonModForumngModulePrefetchHandler.prototype.constructor = AddonModForumngModulePrefetchHandler;
    t.CoreCourseModulePrefetchDelegate.registerHandler(new AddonModForumngModulePrefetchHandler());

   t.newDiscussion = {
       subject: '',
       message: '',
       files: [],
       sticky: 0,
       date: 0,
       postas: 0,
    };
    t.currentReply = {
        subject: '',
        message: '',
        files: [],
        important: 0,
        postas: 0,
    };

    t.originalEdit = {
        subject: '',
        message: '',
        files: [],
        important: 0,
        postas: 0,
    };

    t.isReply = 0;
    t.isEdit = 0;
    t.editTimeout = 0;
    t.isrunningInterval = 0;
    t.showfrom = 0;
    t.showsticky = 0;

   t.editDraft = {
       subject: '',
       message: '',
       files: [],
       sticky: 0,
       date: 0,
       postas: 0,
       important: 0,
       draftexists: '',
   };
   t.isAddDraft = 0;
   t.isEditDraft = 0;
   t.isAddDiscussion = 0;

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
            }).catch(function() {
                infiniteScrollEvent.complete();
            });
        } else {
            infiniteScrollEvent.complete();
        }
    };

    t.mod_forumng.checkDuplicatedFiles = function(attachments) {
        var showError = false;
        var errorFileName = '';
        if (attachments && attachments.length) {
            for (var i = 0; i < attachments.length; i++) {
                for (var j = i + 1; j < attachments.length; j++) {
                    if (attachments[j].name == attachments[i].name) {
                        showError = true;
                        errorFileName = attachments[j].name;
                        break;
                    }
                }
                if (showError) {
                    break;
                }
            }
        }
        if (errorFileName) {
            return ' ' + errorFileName;
        } else {
            return '';
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
        var site = that.CoreSitesProvider.getCurrentSite();
        var cmid = that.CONTENT_OTHERDATA.cmid;
        var userid = site.getUserId();
        var draftid = that.CONTENT_OTHERDATA.draftid ? that.CONTENT_OTHERDATA.draftid : 0;
        var subject = that.subject;
        var message = that.message;
        var groupId = that.CONTENT_OTHERDATA.group; // No group selection in this form; it is done on previous page.
        var discussionId = that.CONTENT_OTHERDATA.discussion; // This is 0 until we add editing.
        var forumngId = that.CONTENT_OTHERDATA.forumng;
        var attachments = that.CONTENT_OTHERDATA.files; // Type [FileEntry].
        var showsticky = that.CONTENT_OTHERDATA.showsticky;
        var showfrom = that.CONTENT_OTHERDATA.showfrom;
        var postas = that.CONTENT_OTHERDATA.postas;
        showfrom = showfrom !== 0 ? Date.parse(showfrom) / 1000 : 0;
        //var discTimecreated = Date.now(); //TODO part of offline - that.timeCreated || Date.now();
        var saveOffline = false;
        var modal;
        var promise;
        var regexp = /\S+/;
        if (!subject || !subject.match(regexp)) {
            that.CoreUtilsProvider.domUtils.showErrorModal('plugin.mod_forumng.erroremptysubject', true);
            return;
        }
        if (subject && subject.length > 255) {
            that.CoreUtilsProvider.domUtils.showErrorModal('plugin.mod_forumng.errormaximumsubjectcharacter', true);
            return;
        }
        // Check text in the message.
        var div = document.createElement('div');
        div.innerHTML = message;
        var messagetext = div.textContent;
        if (!message || !messagetext.match(regexp)) {
            that.CoreUtilsProvider.domUtils.showErrorModal('plugin.mod_forumng.erroremptymessage', true);
            return;
        }
        message = that.CoreTextUtilsProvider.formatHtmlLines(message);

        modal = that.CoreUtilsProvider.domUtils.showModalLoading('core.sending', true);
        // Upload attachments first if any.
        var error = t.mod_forumng.checkDuplicatedFiles(attachments);
        var duplicatedmessage = that.TranslateService.instant('plugin.mod_forumng.cannotuploadfile');
        if (!error) {
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
                        draftarea: draftAreaId, // Note this will be 1 if there are no files.
                        showsticky: showsticky,
                        showfrom: showfrom,
                        postas: postas,
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
                // If there's a draft, delete it
                if (draftid) {
                    t.mod_forumng.deleteDraft(that, draftid, false);
                }

                t.mod_forumng.setNeedUpdate(cmid, 1, userid);
                //TODO check all functionality in core forum (new-discussion.ts) returnToDiscussions(discussionId) is covered.
                // Navigate back to the discussions page and refresh to show new discussion.
                t.mod_forumng.viewSubscribe =
                    that.CoreAppProvider.appCtrl.viewDidEnter.subscribe(t.mod_forumng.forumngRefreshContent);
                that.subject = '';
                that.message = '';
                that.CONTENT_OTHERDATA.files = []; // Type [FileEntry].
                that.CONTENT_OTHERDATA.showsticky = 0;
                that.CONTENT_OTHERDATA.showfrom = 0;
                that.CONTENT_OTHERDATA.postas = 0;
                t.newDiscussion.date = 0;
                t.isAddDiscussion = 1;
                that.NavController.pop();
            }).catch(function(msg) {
                that.CoreUtilsProvider.domUtils.showErrorModalDefault(msg, 'plugin.mod_forumng.cannotcreatediscussion', true);
            }).finally(function() {
                modal.dismiss();
            });
        } else {
            modal.dismiss();
            that.CoreUtilsProvider.domUtils.showErrorModalDefault(duplicatedmessage + error, 'plugin.mod_forumng.cannotcreatediscussion', true);
        }

    };

    /**
     * Add a new draft.
     *
     * This will support editing an existing draft and offline with a little more development.
     *
     * @param {object} that The this object when calling this function.
     */
    t.mod_forumng.addDraft = function(that) {
        var subject = that.subject;
        var message = that.message;
        var draftid = that.CONTENT_OTHERDATA.draftid ? that.CONTENT_OTHERDATA.draftid : 0;
        var groupId = that.CONTENT_OTHERDATA.group; // No group selection in this form; it is done on previous page.
        var replytoId = that.CONTENT_OTHERDATA.currentReplyToId;
        var isreply = that.CONTENT_OTHERDATA.isReply;
        var forumngId = isreply ? that.CONTENT_OTHERDATA.forumngid : that.CONTENT_OTHERDATA.forumng;
        var attachments = that.CONTENT_OTHERDATA.files; // Type [FileEntry].
        var showsticky = that.CONTENT_OTHERDATA.showsticky;
        var showfrom = that.CONTENT_OTHERDATA.showfrom ? that.CONTENT_OTHERDATA.showfrom : 0;
        var postas = that.CONTENT_OTHERDATA.postas;
        var important = that.CONTENT_OTHERDATA.important;
        showfrom = showfrom !== 0 ? Date.parse(showfrom) / 1000 : 0;
        //var discTimecreated = Date.now(); //TODO part of offline - that.timeCreated || Date.now();
        var saveOffline = false;
        var modal;
        var promise;
        var regexp = /\S+/;
        if (!isreply && (!subject || !subject.match(regexp))) {
            that.CoreUtilsProvider.domUtils.showErrorModal('plugin.mod_forumng.erroremptysubject', true);
            return;
        }
        if (subject && subject.length > 255) {
            that.CoreUtilsProvider.domUtils.showErrorModal('plugin.mod_forumng.errormaximumsubjectcharacter', true);
            return;
        }
        // Check text in the message.
        var div = document.createElement('div');
        div.innerHTML = message;
        var messagetext = div.textContent;
        if (!message || !messagetext.match(regexp)) {
            that.CoreUtilsProvider.domUtils.showErrorModal('plugin.mod_forumng.erroremptymessage', true);
            return;
        }
        message = that.CoreTextUtilsProvider.formatHtmlLines(message);

        modal = that.CoreUtilsProvider.domUtils.showModalLoading('core.sending', true);

        // Upload attachments first if any.
        var error = t.mod_forumng.checkDuplicatedFiles(attachments);
        var duplicatedmessage = that.TranslateService.instant('plugin.mod_forumng.cannotuploadfile');
        if (!error) {
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
                        draft: draftid,
                        group: groupId,
                        replyto: replytoId,
                        subject: subject,
                        message: message,
                        draftarea: draftAreaId, // Note this will be 1 if there are no files.
                        showsticky: showsticky,
                        showfrom: showfrom,
                        postas: postas,
                        mailnow: false,
                        setimportant: important,
                    };
                    return site.write('mod_forumng_add_draft', params).then(function(response) {
                        // Other errors ocurring.
                        if (!response || !response.draft) {
                            return Promise.reject(that.CoreWSProvider.createFakeWSError(response.errormsg));
                        } else {
                            return {draftid: response.draft, successmsg: response.successmsg};
                        }
                    });
                    // Don't allow offline if there are attachments since they were uploaded fine.
                    //TODO switch below to use our own offline functionality.
                    // return that.AddonModForumProvider.addNewDiscussion(forumngId, forumName, courseId, subject, message, options,
                    //    groupId, undefined, discTimecreated, !attachments.length);
                }
            }).then(function(pageParams) {
                if (pageParams.draftid) {
                    // Data sent to server, delete stored files (if any).
                    //TODO switch below to our own offline functionality.
                    //that.AddonModForumHelperProvider.deleteNewDiscussionStoredFiles(this.forumId, discTimecreated);
                    //TODO trigger new discussion event or similar?
                    that.CONTENT_OTHERDATA.draftid = pageParams.draftid;
                    that.CONTENT_OTHERDATA.draftexists = pageParams.successmsg;
                    t.editDraft.subject = that.subject;
                    t.editDraft.files = that.CONTENT_OTHERDATA.files;
                    t.editDraft.postas = that.CONTENT_OTHERDATA.postas;
                    t.editDraft.draftexists = pageParams.successmsg;
                    if (isreply) {
                        t.editDraft.important = that.CONTENT_OTHERDATA.important;
                        t.editDraft.message = that.PostControl.value;
                    } else {
                        t.editDraft.sticky = that.CONTENT_OTHERDATA.showsticky;
                        t.editDraft.date = that.CONTENT_OTHERDATA.showfrom;
                        t.editDraft.message = that.addDiscussionControl.value;
                    }
                    setTimeout(function() {
                        if (isreply) {
                            document.getElementById('mma-forumng-draftexists-' + replytoId).scrollIntoView({
                                behavior: 'smooth'
                            });
                        } else {
                            document.getElementById('mma-forumng-draftexists').scrollIntoView({
                                behavior: 'smooth'
                            });
                        }
                    }, 100);
                    t.isAddDraft = 1;
                }
            }).catch(function(msg) {
                that.CoreUtilsProvider.domUtils.showErrorModalDefault(msg, 'plugin.mod_forumng.cannotcreatedraft', true);
            }).finally(function() {
                modal.dismiss();
            });
        } else {
            modal.dismiss();
            that.CoreUtilsProvider.domUtils.showErrorModalDefault(duplicatedmessage + error, 'plugin.mod_forumng.cannotcreatediscussion', true);
        }

    };

    /**
     * Delete draft
     *
     * This will delete a draft
     *
     * @param {object} that The this object when calling this function.
     * @param {int} draftid Draft ID.
     * @param (bool) refresh
     */
    t.mod_forumng.deleteDraft = function(that, draftid, refresh) {
        var site = that.CoreSitesProvider.getCurrentSite();
        site.write('mod_forumng_delete_draft', {'draftid': draftid}).then(function(result) {
            if (!result.errormsg) {
                if (refresh) {
                    t.mod_forumng.viewSubscribe = that.CoreAppProvider.appCtrl.viewDidEnter.subscribe(t.mod_forumng.forumngDraftRefreshContent);
                    that.NavController.pop();
                }
                // Don't show anything.
            } else {
                var alert = that.AlertController.create({
                    title: "Error",
                    subTitle: result.errormsg,
                });
                alert.present();
            }
        }).catch( function(error) {
            var alert = that.AlertController.create({
                title: "Error",
                subTitle: error,
            });
            alert.present();
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
            // Clear forum form content after refresh.
            t.newDiscussion.postas = 0;
            t.newDiscussion.message = '';
            t.newDiscussion.subject = '';
            t.newDiscussion.files = [];
            t.newDiscussion.date = 0;
            t.newDiscussion.showsticky = 0;
        }
    };

    t.mod_forumng.forumngDraftRefreshContent = function(view) {
        if (view.name === 'CoreSitePluginsModuleIndexPage') {
            t.mod_forumng.viewSubscribe.unsubscribe();
            delete t.mod_forumng.viewSubscribe;
            t.mod_forumng.currentDiscussionsPage.refreshContent();
        }
    };

    /**
     * Lock discussion.
     *
     * This will support editing an existing post and offline with a little more development.
     *
     * @param {object} that The this object when calling this function.
     */
    t.mod_forumng.lock_discussion = function(that) {
        var subject = that.subject;
        var message = that.message;
        var attachments = that.CONTENT_OTHERDATA.files;
        var postas = that.CONTENT_OTHERDATA.postas;
        var discussionid = that.CONTENT_OTHERDATA.discussionid;
        var forumngId = that.CONTENT_OTHERDATA.forumngid;
        var saveOffline = false;
        var modal;
        var promise;

        if (subject && subject.length > 255) {
            that.CoreUtilsProvider.domUtils.showErrorModal('plugin.mod_forumng.errormaximumsubjectcharacter', true);
            return;
        }
        if (!message) {
            that.CoreUtilsProvider.domUtils.showErrorModal('plugin.mod_forumng.erroremptymessage', true);
            return;
        }
        message = that.CoreTextUtilsProvider.formatHtmlLines(message);

        modal = that.CoreUtilsProvider.domUtils.showModalLoading('core.sending', true);

        // Upload attachments first if any.
        var error = t.mod_forumng.checkDuplicatedFiles(attachments);
        var duplicatedmessage = that.TranslateService.instant('plugin.mod_forumng.cannotuploadfile');
        if (!error) {
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
                        discussionid: discussionid,
                        cloneid: 0,
                        subject: subject,
                        message: message,
                        draftarea: draftAreaId,
                        postas: postas,
                    };
                    if (!(subject === undefined || subject === '')) {
                        params.subject = subject;
                    }

                    return site.write('mod_forumng_lock_discussion', params).then(function(response) {
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
                that.refreshContent();
            }).catch(function(msg) {
                that.CoreUtilsProvider.domUtils.showErrorModalDefault(msg, 'plugin.mod_forumng.cannotlockdiscussion', true);
            }).finally(function() {
                modal.dismiss();
            });
        } else {
            modal.dismiss();
            that.CoreUtilsProvider.domUtils.showErrorModalDefault(duplicatedmessage + error, 'plugin.mod_forumng.cannotcreatediscussion', true);
        }

    };

    /**
     * Check the attachment changes.
     *
     * @param {array} attachments The new attachments.
     * @param {array} fileexists  File exists.
     */
    t.mod_forumng.checkAttachments = function(attachments, fileexists) {
        var haveDifferentFiles = false;
        if (attachments && fileexists && attachments.length == fileexists) {
            if (attachments.length > 0) {
                for (var index in attachments) {
                    if (attachments[index].fullPath &&
                        attachments[index].fullPath != fileexists[index].fullPath) {
                        haveDifferentFiles = true;
                    } else if (attachments[index].url != fileexists[index].url) {
                        haveDifferentFiles = true;
                    }
                }
            }
        }
        return haveDifferentFiles;
    };

    // Following functions are called during page initialisation and allow adding new functionality
    // to the main component (coreCompileHtmlFakeComponent) as outerThis.

    /**
     * Initialisation for the discussions page.
     *
     * @param {object} outerThis The main component.
     */
    window.forumngDiscussionsPageInit = function(outerThis) {

        var site = outerThis.CoreSitesProvider.getCurrentSite();
        var cmid = outerThis.module.id;
        var userid = site.getUserId();
        var courseid = outerThis.courseId;
        var preSets = {updateFrequency: 0, getFromCache: false};
        var PopoverTransition = function() {
            var popover = document.querySelector('.popover-content');
            if (popover) {
                popover.style.right = 'calc(env(safe-area-inset-right) + 0px)';
                popover.style.left = null;
            }
        };
        var defaultarg = {
            'cmid': cmid,
            'courseid' : courseid,
            'group' : outerThis.CONTENT_OTHERDATA.defaultgroup,
            'sortid': outerThis.CONTENT_OTHERDATA.selectedsort,
            'isupdate': '1',
        };
        outerThis.CoreSitePluginsProvider.getContent('mod_forumng', 'forumng_view', defaultarg, preSets);

        if (t.removeEvent) {
            window.addEventListener("orientationchange", PopoverTransition);
            t.removeEvent = false;
        }
        outerThis.updateSortContent = function(args){
            outerThis.CoreSitePluginsProvider.getContent('mod_forumng', 'forumng_view', args, preSets);
            t.mod_forumng.setNeedUpdate(cmid, 1, userid);
            outerThis.updateContent(args, 'mod_forumng', 'forumng_view', true);
        };
        outerThis.updateGroupContent = function(args){
            outerThis.CoreSitePluginsProvider.getContent('mod_forumng', 'forumng_view', args, preSets);
            t.mod_forumng.setNeedUpdate(cmid, 1, userid);
            outerThis.updateContent(args, 'mod_forumng', 'forumng_view', true);
        };
        // Check and handle module completion feature.
        t.CoreCourseProvider.checkModuleCompletion(outerThis.courseId, outerThis.module.completiondata);
        // Make loadMoreDiscussion available from the template.
        outerThis.loadMoreDiscussions = function(infiniteScrollEvent) {
            t.mod_forumng.loadMoreDiscussions(outerThis, infiniteScrollEvent);
        };
        // Same for isOnline.
        outerThis.isOnline = function() {
            return outerThis.CoreAppProvider.isOnline();
        };

        outerThis.ionViewWillLeave = function() {
            var preSets = {updateFrequency: 0, getFromCache: false};
            var updatemainpageargs = {'cmid' : cmid, 'courseid': courseid};
            window.removeEventListener("orientationchange", PopoverTransition);
            t.mod_forumng.getNeedUpdate(cmid, userid).then(function(result) {
                // When we go the forum the agrs is only have {cmid, courseid} so we need to update the cache the newest version.
                if (typeof(result) != 'undefined' && result != null && result) {
                    t.mod_forumng.setNeedUpdate(cmid, null, userid);
                    outerThis.CoreSitePluginsProvider.getContent('mod_forumng', 'forumng_view', updatemainpageargs, preSets);
                }
            });
        };
        outerThis.showMessage = function (text) {
            var successalert = this.AlertController.create({
                title: '',
                subTitle: text,
                buttons: [this.TranslateService.instant('core.ok')]
            });
            successalert.present();
        };

        /**
         * Mark all post read.
         *
         */
        outerThis.MarkAllPostsRead = function() {
            t.mod_forumng.toMarkAllPostsRead(outerThis, site, cmid, courseid, userid, PopoverTransition, 1);
        };
        t.mod_forumng.currentDiscussionsPage = outerThis;
    };

    /**
     * Mark all post read.
     *
     */
    t.mod_forumng.toMarkAllPostsRead = function(outerThis, site, cmid, courseid, userid, PopoverTransition, forumView) {
        if (outerThis.isOnline()) {
            if (forumView) {
                site.write('mod_forumng_mark_all_post_read', {'cmid': cmid, 'cloneid' : 0, 'groupid' : outerThis.CONTENT_OTHERDATA.defaultgroup}).then(function(result) {
                    if (!result.errormsg) {
                        // We need to update the newest content because the cached page.
                        var args = {'cmid' : cmid, 'courseid': courseid, group: outerThis.CONTENT_OTHERDATA.defaultgroup,
                            sortid: outerThis.CONTENT_OTHERDATA.selectedsort, isupdate: 1};
                        var preSets = {updateFrequency: 0, getFromCache: false};
                        t.mod_forumng.setNeedUpdate(cmid, 1, userid);
                        outerThis.CoreSitePluginsProvider.getContent('mod_forumng', 'forumng_view', args, preSets);
                        window.removeEventListener("orientationchange", PopoverTransition);
                        outerThis.updateContent(args, 'mod_forumng', 'forumng_view', true);
                    } else {
                        var alert = outerThis.AlertController.create({
                            title: "Error",
                            subTitle: result.errormsg,
                        });
                        alert.present();
                    }
                }).catch( function(error) {
                    var alert = outerThis.AlertController.create({
                        title: "Error",
                        subTitle: error,
                    });
                    alert.present();
                });
            } else {
                // We should use default -1 not 0 for discussion.
                site.write('mod_forumng_mark_all_post_read', {'cmid': cmid, 'cloneid' : 0, 'groupid' : -1, 'discussionid' : outerThis.CONTENT_OTHERDATA.discussionid}).then(function(result) {
                    if (!result.errormsg) {
                        outerThis.refreshContent();
                    } else {
                        var alert = outerThis.AlertController.create({
                            title: "Error",
                            subTitle: result.errormsg,
                        });
                        alert.present();
                    }
                }).catch( function(error) {
                    var alert = outerThis.AlertController.create({
                        title: "Error",
                        subTitle: error,
                    });
                    alert.present();
                });
            }
        } else {
            var alert = outerThis.AlertController.create({
                title: "Error",
                subTitle: "Offline is not supported",
            });
            alert.present();
            //TODO switch below to our own offline functionality.
            // Will be implemented sync later.
        }
    };

    /**
     * Initialisation for the posts page.
     *
     * @param {object} outerThis The main component.
     */
    window.forumngPostsPageInit = function(outerThis) {
        // Function to get Unix time.
        var get_unix_time = function() {
            return Math.round((new Date()).getTime() / 1000);
        };
        // Calculate format to use. ion-datetime doesn't support escaping characters ([]), so we remove them.
        outerThis.dateFormat = outerThis.CoreTimeUtilsProvider.convertPHPToMoment('%d %B %Y')
            .replace(/[\[\]]/g, '');
        outerThis.resetData = function(t) {
            t.postas = 0;
            t.important = 0;
            t.files = [];
            t.message = '';
            t.subject = '';
            return t;
        };

        outerThis.PostControl = outerThis.FormBuilder.control();
        var regexp = /\S+/;
        t.removeEvent = true;
        setTimeout(function() {
            if (t.isReply) {
                outerThis.subject = t.currentReply.subject;
                outerThis.message = t.currentReply.message;
                outerThis.CONTENT_OTHERDATA.files = t.currentReply.files; // Type [FileEntry].
                outerThis.CONTENT_OTHERDATA.postas = t.currentReply.postas;
                outerThis.CONTENT_OTHERDATA.important = t.currentReply.important;
                outerThis.CONTENT_OTHERDATA.isReply = 1;
                outerThis.CONTENT_OTHERDATA.isEdit = 0;
                outerThis.CONTENT_OTHERDATA.currentReplyToId = t.isReply;
                outerThis.PostControl.setValue(t.currentReply.message);
            } else if (t.isEdit) {
                outerThis.CONTENT_OTHERDATA.edittimeout  = t.editTimeout;
                outerThis.subject = t.originalEdit.subject;
                outerThis.message = t.originalEdit.message;
                outerThis.CONTENT_OTHERDATA.files = t.originalEdit.files; // Type [FileEntry].
                outerThis.CONTENT_OTHERDATA.postas = t.originalEdit.postas;
                outerThis.CONTENT_OTHERDATA.important = t.originalEdit.important;
                outerThis.CONTENT_OTHERDATA.isReply = 0;
                outerThis.CONTENT_OTHERDATA.isEdit = 1;
                outerThis.CONTENT_OTHERDATA.currentEditedPostId = t.isEdit;
                outerThis.CONTENT_OTHERDATA.showfrom = t.showfrom ? t.showfrom : 0;
                outerThis.CONTENT_OTHERDATA.showsticky = t.showsticky ? t.showsticky : 0;
                outerThis.PostControl.setValue(t.originalEdit.message);
            } else if (t.isEditDraft) {
                outerThis.subject = t.editDraft.subject;
                outerThis.PostControl.setValue(t.editDraft.message);
                outerThis.CONTENT_OTHERDATA.files = t.editDraft.files;
                outerThis.CONTENT_OTHERDATA.important = t.editDraft.important;
                outerThis.CONTENT_OTHERDATA.postas = t.editDraft.postas;
                autoScrollToDraft(outerThis.CONTENT_OTHERDATA.replytoid);
            }
        }, 100);

        outerThis.isOnline = function() {
            return outerThis.CoreAppProvider.isOnline();
        };

        var PopoverTransition = function() {
            var popover = document.querySelector('.popover-content');
            if (popover) {
                popover.style.right = 'calc(env(safe-area-inset-right) + 10px)';
                popover.style.left = null;
            }
        };
        if (t.removeEvent) {
            window.addEventListener("orientationchange", PopoverTransition);
            t.removeEvent = false;
        }

        var autoScrollToDraft = function(replytoid) {
            (function expandPosts(postArr) {
                for(var ind in postArr) {
                    var reply = postArr[ind];
                    if(reply.postid === replytoid) {
                        reply.isexpanded = true;
                    } else {
                        if (reply.subreplies) {
                            expandPosts(reply.subreplies);
                        }
                    }
                }
            })(outerThis.CONTENT_OTHERDATA.replies);
            setTimeout(function() {
                outerThis.CONTENT_OTHERDATA.isReply = 1;
                outerThis.CONTENT_OTHERDATA.isEdit = 0;
                outerThis.CONTENT_OTHERDATA.currentReplyToId = replytoid;
                outerThis.CONTENT_OTHERDATA.currentEditedPostId = 0;
                setTimeout(function() {
                    document.getElementById('mma-forumng-draftexists-' + replytoid).scrollIntoView({
                        behavior: 'smooth',
                        block: "center"
                    });
                }, 100)
            }, 100);
        };

        // Set data when editing draft.
        if(outerThis.CONTENT_OTHERDATA.draftid && !t.isEditDraft) {
            var replytoid = outerThis.CONTENT_OTHERDATA.replytoid;
            t.isEditDraft = 1;
            autoScrollToDraft(replytoid);
            outerThis.subject = outerThis.CONTENT_OTHERDATA.draftsubject;
            outerThis.PostControl.value = outerThis.CONTENT_OTHERDATA.draftmessage;
            var $draftattachments = outerThis.CONTENT_OTHERDATA.attachmentsforform;
            if (typeof $draftattachments !== 'object') {
                $draftattachments = JSON.parse($draftattachments);
            }
            if ($draftattachments.length > 0) {
                for (var index in $draftattachments) {
                    $draftattachments[index].toURL = function() {
                        return '';
                    }
                }
            }
            outerThis.CONTENT_OTHERDATA.files = $draftattachments;
            t.editDraft.subject = outerThis.subject;
            t.editDraft.message = outerThis.PostControl.value;
            t.editDraft.files = outerThis.CONTENT_OTHERDATA.files;
            t.editDraft.important = outerThis.CONTENT_OTHERDATA.important;
            t.editDraft.postas = outerThis.CONTENT_OTHERDATA.postas;
        }
        if (t.isAddDraft && (t.editDraft.draftexists !== '')) {
            outerThis.CONTENT_OTHERDATA.draftexists = t.editDraft.draftexists;
        }

        outerThis.lock = function() {
            if (!outerThis.CONTENT_OTHERDATA.lock) {
                outerThis.CONTENT_OTHERDATA.lock = 1;
                outerThis.subject = outerThis.TranslateService.instant('plugin.mod_forumng.lockedtitle');
                setTimeout(function() {
                    document.getElementById('mma-forumng-form').scrollIntoViewIfNeeded();
                }, 100);
            }
        };

        // Refresh current content.
        outerThis.doRefresh = function() {
            outerThis.CONTENT_OTHERDATA.refreshicon = 'spinner';
            outerThis.refreshContent().finally(function() {
               outerThis.CONTENT_OTHERDATA.refreshicon = 'refresh';
            });
        };

        outerThis.ionViewCanLeave = function() {
            var message = outerThis.message;
            var subject = outerThis.subject;
            var attachments = outerThis.CONTENT_OTHERDATA.files; // Type [FileEntry].
            var postas = outerThis.CONTENT_OTHERDATA.postas;
            var important = outerThis.CONTENT_OTHERDATA.important;
            var showfrom = outerThis.CONTENT_OTHERDATA.showfrom;
            var showsticky = outerThis.CONTENT_OTHERDATA.showsticky;
            var draft = outerThis.CONTENT_OTHERDATA.draftid;
            var haveDifferentFiles = false;
            if (draft) {
                haveDifferentFiles = t.mod_forumng.checkAttachments(attachments, t.editDraft.files);
            } else {
                haveDifferentFiles = t.mod_forumng.checkAttachments(attachments, t.originalEdit.files);
            }

            var formChange = subject || message || (attachments && attachments.length > 0) || postas != 0 || important != 0;
            var formLockChange = message || attachments.length > 0 || postas != 0;
            var formDraftChange = t.editDraft.message != message ||  t.editDraft.subject != subject || t.editDraft.postas != postas ||
                t.editDraft.important != important || haveDifferentFiles;
            var formEditReplyChange = t.originalEdit.message != message ||  t.originalEdit.subject != subject || t.originalEdit.postas != postas ||
                t.originalEdit.important != important || haveDifferentFiles || showfrom != t.showfrom || showsticky != t.showsticky;
            if (!draft && !t.isAddDraft && outerThis.CONTENT_OTHERDATA.currentReplyToId && formChange) {
                return outerThis.CoreDomUtilsProvider.showConfirm(outerThis.TranslateService.instant('plugin.mod_forumng.leavemessage')).then(function() {
                    window.removeEventListener("orientationchange", PopoverTransition);
                    t.currentReply = outerThis.resetData(t.currentReply);
                    t.isReply = 0;
                    outerThis.CONTENT_OTHERDATA.currentReplyToId = 0;
                    if (attachments.length > 0) {
                        return outerThis.CoreFileUploaderProvider.clearTmpFiles(attachments);
                    }
                    t.mod_forumng.currentDiscussionsPage.refreshContent();
                });
            } else if (outerThis.CONTENT_OTHERDATA.currentEditedPostId && formEditReplyChange) {
                return outerThis.CoreDomUtilsProvider.showConfirm(outerThis.TranslateService.instant('plugin.mod_forumng.leavemessage')).then(function() {
                    window.removeEventListener("orientationchange", PopoverTransition);
                    t.originalEdit = outerThis.resetData(t.originalEdit);
                    t.isEdit = 0;
                    outerThis.CONTENT_OTHERDATA.currentEditedPostId = 0;
                    if (outerThis.CONTENT_OTHERDATA.currentEditedPostId == outerThis.CONTENT_OTHERDATA.rootpostid) {
                        outerThis.CONTENT_OTHERDATA.showsticky = '';
                        outerThis.showfrom = '';
                        t.showfrom = '';
                        t.showsticky = '';
                    }
                    if (attachments.length > 0) {
                        return outerThis.CoreFileUploaderProvider.clearTmpFiles(attachments);
                    }
                    t.mod_forumng.currentDiscussionsPage.refreshContent();
                });
            } else if (outerThis.CONTENT_OTHERDATA.lock && formLockChange) {
                return outerThis.CoreDomUtilsProvider.showConfirm(outerThis.TranslateService.instant('plugin.mod_forumng.leavemessage')).then(function() {
                    window.removeEventListener("orientationchange", PopoverTransition);
                    if (attachments.length > 0) {
                        return outerThis.CoreFileUploaderProvider.clearTmpFiles(attachments);
                    }
                    t.mod_forumng.currentDiscussionsPage.refreshContent();
                });
            } else if ((draft || t.isAddDraft) && formDraftChange) {
                return outerThis.CoreDomUtilsProvider.showConfirm(outerThis.TranslateService.instant('plugin.mod_forumng.leavemessage')).then(function() {
                    window.removeEventListener("orientationchange", PopoverTransition);
                    t.editDraft = outerThis.resetData(t.editDraft);
                    t.editDraft.draftexists = '';
                    t.isAddDraft = 0;
                    t.isEditDraft = 0;
                    t.isReply = 0;
                    outerThis.CONTENT_OTHERDATA.currentReplyToId = 0;
                    if (attachments.length > 0) {
                        return outerThis.CoreFileUploaderProvider.clearTmpFiles(attachments);
                    }
                    t.mod_forumng.currentDiscussionsPage.refreshContent();
                });
            } else if (t.mod_forumng.currentDiscussionsPage) {
                window.removeEventListener("orientationchange", PopoverTransition);
                t.mod_forumng.currentDiscussionsPage.refreshContent();
                t.originalEdit = outerThis.resetData(t.originalEdit);
                t.currentReply = outerThis.resetData(t.currentReply);
                t.editDraft = outerThis.resetData(t.editDraft);
                t.isEdit = 0;
                t.isReply = 0;
                t.isAddDraft = 0;
                t.isEditDraft = 0;
                t.editDraft.draftexists = '';
                outerThis.CONTENT_OTHERDATA.currentEditedPostId = 0;
                outerThis.CONTENT_OTHERDATA.currentReplyToId = 0;
            }
            return true;
        };

        outerThis.showDelete = function(postid) {
            outerThis.AlertController.create({
                message: outerThis.TranslateService.instant('plugin.mod_forumng.confirmdelete'),
                buttons: [
                    {
                        text: outerThis.TranslateService.instant('core.cancel'),
                        role: 'cancel'
                    },
                    {
                        text: outerThis.TranslateService.instant('plugin.mod_forumng.deletepostbutton'),
                        handler: function() {
                            outerThis.delete(postid);
                        }
                    }
                ]
            }).present();
        };

        outerThis.showUnDelete = function(postid) {
            outerThis.AlertController.create({
                message: outerThis.TranslateService.instant('plugin.mod_forumng.confirmundelete'),
                buttons: [
                    {
                        text: outerThis.TranslateService.instant('core.cancel'),
                        role: 'cancel'
                    },
                    {
                        text: outerThis.TranslateService.instant('plugin.mod_forumng.undeletepostbutton'),
                        handler: function() {
                            outerThis.undelete(postid);
                        }
                    }
                ]
            }).present();
        };

        outerThis.delete = function(postid) {
            var site = outerThis.CoreSitesProvider.getCurrentSite();
            var params = {
                postid: postid,
            };


            var modal = outerThis.CoreUtilsProvider.domUtils.showModalLoading('core.sending', true);

            site.write('mod_forumng_delete_post_mobile', params).then(function(response) {
                if (response.message) {
                    return Promise.reject(outerThis.CoreWSProvider.createFakeWSError(response.message));
                } else {
                    return response.postinfo;
                }
            }).then(function(postId) {
                outerThis.refreshContent();
            }).catch(function(msg) {
                outerThis.CoreUtilsProvider.domUtils.showErrorModalDefault(msg, 'addon.mod_forum.cannotcreatereply', true);
            }).finally(function() {
                modal.dismiss();
            });
        };

        outerThis.undelete = function(postid) {
            var site = outerThis.CoreSitesProvider.getCurrentSite();
            var params = {
                postid: postid,
            };
            var modal = outerThis.CoreUtilsProvider.domUtils.showModalLoading('core.sending', true);

            site.write('mod_forumng_undelete_post_mobile', params).then(function(response) {
                if (response.message) {
                    return Promise.reject(outerThis.CoreWSProvider.createFakeWSError(response.message));
                } else {
                    return response.postinfo;
                }
            }).then(function(postId) {
                outerThis.refreshContent();
            }).catch(function(msg) {
                outerThis.CoreUtilsProvider.domUtils.showErrorModalDefault(msg, 'addon.mod_forum.cannotcreatereply', true);
            }).finally(function() {
                modal.dismiss();
            });
        };

        outerThis.onSubjectChange = function() {
            t.currentReply.subject = outerThis.subject;
            if (outerThis.CONTENT_OTHERDATA.rootpostid == outerThis.CONTENT_OTHERDATA.currentEditedPostId && outerThis.subject == '') {
                outerThis.CONTENT_OTHERDATA.disable = true;
            } else {
                outerThis.CONTENT_OTHERDATA.disable = false;
            }
        };

        outerThis.onMessageChange = function (text) {
            // Check text in the message.
            var div = document.createElement('div');
            div.innerHTML = text;
            var messagetext = div.textContent;
            if (!messagetext.match(regexp)) {
                outerThis.message = '';
            }
            t.currentReply.message = text;
        };

        outerThis.onImportantChange = function () {
            t.currentReply.important = outerThis.CONTENT_OTHERDATA.important;
        };

        outerThis.PostAsChange = function () {
            t.currentReply.postas = outerThis.CONTENT_OTHERDATA.postas;
        };

        outerThis.showReply = function(replytoId) {
            outerThis.subject = '';
            outerThis.message = '';
            outerThis.CONTENT_OTHERDATA.isReply = 1;
            outerThis.CONTENT_OTHERDATA.isEdit = 0;
            outerThis.CONTENT_OTHERDATA.currentReplyToId = replytoId;
            outerThis.CONTENT_OTHERDATA.postas = 0;
            outerThis.CONTENT_OTHERDATA.currentEditedPostId = 0;
            outerThis.CONTENT_OTHERDATA.important = 0;
            t.currentReply.files = outerThis.CONTENT_OTHERDATA.files;
            t.isReply = replytoId;
        };

        outerThis.showEdit = function(postdata) {
            if (!t.isrunningInterval) {
                var interval = setInterval(function() {
                    if (outerThis.CONTENT_OTHERDATA.isEdit) {
                        if (outerThis.CONTENT_OTHERDATA.limittime) {
                            var countdown = outerThis.CONTENT_OTHERDATA.limittime - get_unix_time();
                            var eletimelimit = document.querySelector('.time-limit');
                            if (countdown <= 0) {
                                outerThis.CONTENT_OTHERDATA.disable = 1;
                                outerThis.CONTENT_OTHERDATA.edittimeout = 0;
                                t.editTimeout = 0;
                                clearInterval(interval);
                            } else if(countdown <= 30) {
                                outerThis.CONTENT_OTHERDATA.edittimeout = 1;
                                if (eletimelimit) {
                                    eletimelimit.classList.add('bold-text');
                                    eletimelimit.classList.add('red-text');
                                }
                            } else if(countdown <= 90) {
                                outerThis.CONTENT_OTHERDATA.edittimeout = 1;
                                if (eletimelimit) {
                                    eletimelimit.classList.add('bold-text');
                                }
                            }
                        }
                    }
                    t.isrunningInterval = 1;
                }, 1000);
            }

            var getMessage = function(replyarray) {
                if (replyarray && replyarray.length > 0) {
                    for (var index in replyarray) {
                        if (replyarray[index].postid === postdata.postid) {
                            outerThis.PostControl.setValue(replyarray[index].message);
                            outerThis.message = replyarray[index].message;
                            return;
                        } else {
                            if (replyarray[index].subreplies && replyarray[index].subreplies.length > 0) {
                                getMessage(replyarray[index].subreplies);
                            }
                        }
                    }
                } else {
                    return;
                }
            };
            outerThis.subject = postdata.subject;
            if (postdata.postid == outerThis.CONTENT_OTHERDATA.rootpostid) {
                outerThis.subject = outerThis.CONTENT_OTHERDATA.rootpostsubject;
                outerThis.CONTENT_OTHERDATA.showfrom = postdata.showfrom;
                outerThis.CONTENT_OTHERDATA.showsticky = postdata.sticky;
                t.showsticky = postdata.sticky;
                t.showfrom = postdata.showfrom;
                outerThis.PostControl.setValue(outerThis.CONTENT_OTHERDATA.originalrootpostmessage);
                outerThis.message = outerThis.CONTENT_OTHERDATA.originalrootpostmessage;
            } else {
                outerThis.CONTENT_OTHERDATA.showsticky = 0;
                outerThis.CONTENT_OTHERDATA.showfrom = 0;
                t.showfrom = 0;
                t.showsticky = 0;
                getMessage(outerThis.CONTENT_OTHERDATA.replies);
            }
            outerThis.CONTENT_OTHERDATA.limittime = postdata.timelimit;
            outerThis.CONTENT_OTHERDATA.isReply = 0;
            outerThis.CONTENT_OTHERDATA.isEdit = 1;
            outerThis.CONTENT_OTHERDATA.currentReplyToId = 0;
            outerThis.CONTENT_OTHERDATA.currentEditedPostId = postdata.postid;
            outerThis.CONTENT_OTHERDATA.postas = postdata.postas;
            outerThis.CONTENT_OTHERDATA.important = postdata.isimportant;
            outerThis.CONTENT_OTHERDATA.edittimeout = 1;
            t.editTimeout = 1;
            if (typeof postdata.attachmentsforform !== 'object') {
                postdata.attachmentsforform = JSON.parse(postdata.attachmentsforform);
            }
            if (postdata.attachmentsforform.length > 0) {
                for (var index in postdata.attachmentsforform) {
                    postdata.attachmentsforform[index].toURL = function() {
                        return '';
                    }
                }
            }
            outerThis.CONTENT_OTHERDATA.files = postdata.attachmentsforform;
            t.originalEdit.subject = outerThis.subject;
            t.originalEdit.message = outerThis.message;
            t.originalEdit.files = outerThis.CONTENT_OTHERDATA.files;
            t.originalEdit.important = outerThis.CONTENT_OTHERDATA.important;
            t.originalEdit.postas = outerThis.CONTENT_OTHERDATA.postas;
            t.isEdit = postdata.postid;
        };

        outerThis.edit = function() {
            if (outerThis.isOnline()) {
                var subject = outerThis.subject; // Can be empty or undefined - probably usually is!
                var message = outerThis.message;
                var isedit = outerThis.CONTENT_OTHERDATA.currentEditedPostId;
                var replyto = outerThis.CONTENT_OTHERDATA.currentEditedPostId ? outerThis.CONTENT_OTHERDATA.currentEditedPostId : outerThis.CONTENT_OTHERDATA.currentReplyToId;
                var forumngId = outerThis.CONTENT_OTHERDATA.forumngid;
                var attachments = outerThis.CONTENT_OTHERDATA.files; // Type [FileEntry].
                var postas = outerThis.CONTENT_OTHERDATA.postas;
                var important = outerThis.CONTENT_OTHERDATA.important;
                var sticky = outerThis.CONTENT_OTHERDATA.showsticky ? outerThis.CONTENT_OTHERDATA.showsticky : 0;
                var showfrom =  parseInt(outerThis.CONTENT_OTHERDATA.showfrom) > 0 ? Date.parse(outerThis.CONTENT_OTHERDATA.showfrom) / 1000 : 0;
                var isrootpost = parseInt(replyto) === parseInt(outerThis.CONTENT_OTHERDATA.rootpostid) ? true : false;
                var draftid = outerThis.CONTENT_OTHERDATA.draftid ? outerThis.CONTENT_OTHERDATA.draftid : 0;
                //var discTimecreated = Date.now(); //TODO part of offline - that.timeCreated || Date.now();
                var saveOffline = false;
                var modal;
                var promise;
                if (subject && subject.length > 255) {
                    outerThis.CoreUtilsProvider.domUtils.showErrorModal('plugin.mod_forumng.errormaximumsubjectcharacter', true);
                    return;
                }
                message = outerThis.CoreTextUtilsProvider.formatHtmlLines(message);

                modal = outerThis.CoreUtilsProvider.domUtils.showModalLoading('core.sending', true);
                // Upload attachments first if any.
                var error = t.mod_forumng.checkDuplicatedFiles(attachments);
                var duplicatedmessage = outerThis.TranslateService.instant('plugin.mod_forumng.cannotuploadfile');
                if (!error) {
                    if (attachments && attachments.length) {
                        promise = outerThis.CoreFileUploaderProvider.uploadOrReuploadFiles(attachments, 'mod_forumng', forumngId)
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
                            var site = outerThis.CoreSitesProvider.getCurrentSite();
                            var params = {
                                replyto: replyto,
                                message: message,
                                draftarea: draftAreaId,
                                postas: postas,
                                important: important,
                                editing: isedit ? 1 : 0,
                                isrootpost : isrootpost,
                                sticky: sticky,
                                showfrom: showfrom,
                            };
                            if (!(subject === undefined || subject === '')) {
                                params.subject = subject;
                            }
                            return site.write('mod_forumng_reply', params).then(function(response) {
                                if (!response || !response.post) {
                                    return Promise.reject(outerThis.CoreWSProvider.createFakeWSError(response.errormsg));
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
                        // Navigate back to the post.mod_forumng.replyts page and refresh to show new post.
                        // Clear all the form data.

                        // If there's a draft, delete it
                        if (draftid) {
                            t.mod_forumng.deleteDraft(outerThis, draftid, false);
                            outerThis.updateContent({'cmid': outerThis.CONTENT_OTHERDATA.cmid, 'discussionid' : outerThis.CONTENT_OTHERDATA.discussionid}, 'mod_forumng', 'posts_view', true);
                        }
                        t.originalEdit = outerThis.resetData(t.originalEdit);
                        t.currentReply = outerThis.resetData(t.currentReply);
                        t.editDraft = outerThis.resetData(t.editDraft);
                        t.isEdit = 0;
                        t.isReply = 0;
                        t.isAddDraft = 0;
                        if (isrootpost && isedit) {
                            var currentView = document.querySelector('[style="z-index: '+ outerThis.NavController.getActive()._zIndex + ';"]');
                            var currentTitle = currentView.querySelector('.toolbar-title');
                            currentTitle.textContent = subject;
                        }
                        outerThis.refreshContent();
                    }).catch(function(msg) {
                        outerThis.CoreUtilsProvider.domUtils.showErrorModalDefault(msg, 'addon.mod_forum.cannotcreatereply', true);
                    }).finally(function() {
                        modal.dismiss();
                    });
                } else {
                    modal.dismiss();
                    outerThis.CoreUtilsProvider.domUtils.showErrorModalDefault(duplicatedmessage + error, 'plugin.mod_forumng.cannotcreatediscussion', true);
                }
            } else {

            }
        };

        outerThis.cancel = function() {
            var subject = outerThis.subject;
            var message = outerThis.message;
            var attachments = outerThis.CONTENT_OTHERDATA.files; // Type [FileEntry].
            var postas = outerThis.CONTENT_OTHERDATA.postas;
            var important = outerThis.CONTENT_OTHERDATA.important;
            var draft = outerThis.CONTENT_OTHERDATA.draftid;
            var haveDifferentFiles = false;
            var showfrom = outerThis.CONTENT_OTHERDATA.showfrom;
            var showsticky = outerThis.CONTENT_OTHERDATA.showsticky;

            if (outerThis.CONTENT_OTHERDATA.disable) {
                t.originalEdit = outerThis.resetData(t.originalEdit);
                t.currentReply = outerThis.resetData(t.currentReply);
                t.isEdit = 0;
                t.isReply = 0;
                outerThis.CONTENT_OTHERDATA.files = [];
                outerThis.refreshContent();
                return;
            }

            if (draft) {
                haveDifferentFiles = t.mod_forumng.checkAttachments(attachments, t.editDraft.files);
            } else {
                haveDifferentFiles = t.mod_forumng.checkAttachments(attachments, t.originalEdit.files);
            }

            var formChange = subject || message || (attachments && attachments.length > 0) || postas != 0 || important != 0;
            var formLockChange = message || attachments.length > 0 || postas != 0;
            var formDraftChange = t.editDraft.message != message ||  t.editDraft.subject != subject || t.editDraft.postas != postas ||
                t.editDraft.important != important || haveDifferentFiles;
            var formEditReplyChange = t.originalEdit.message != message ||  t.originalEdit.subject != subject || t.originalEdit.postas != postas ||
                t.originalEdit.important != important || haveDifferentFiles || showsticky != t.showsticky || showfrom != t.showfrom;
            if (!draft && !t.isAddDraft && outerThis.CONTENT_OTHERDATA.currentReplyToId && formChange) {
                return outerThis.CoreDomUtilsProvider.showConfirm(outerThis.TranslateService.instant('plugin.mod_forumng.leavemessage')).then(function() {
                    t.currentReply = outerThis.resetData(t.currentReply);
                    outerThis.PostControl.setValue('');
                    outerThis.CONTENT_OTHERDATA.files = [];
                    t.isReply = 0;
                    outerThis.CONTENT_OTHERDATA.currentReplyToId = 0;
                    outerThis.CONTENT_OTHERDATA.currentEditedPostId = 0;
                    if (attachments.length > 0) {
                        return outerThis.CoreFileUploaderProvider.clearTmpFiles(attachments);
                    }
                });
            } else if (outerThis.CONTENT_OTHERDATA.currentEditedPostId && formEditReplyChange) {
                return outerThis.CoreDomUtilsProvider.showConfirm(outerThis.TranslateService.instant('plugin.mod_forumng.leavemessage')).then(function() {
                    t.originalEdit = outerThis.resetData(t.originalEdit);
                    outerThis.PostControl.setValue('');
                    outerThis.CONTENT_OTHERDATA.files = [];
                    t.isEdit = 0;
                    outerThis.CONTENT_OTHERDATA.currentReplyToId = 0;
                    outerThis.CONTENT_OTHERDATA.currentEditedPostId = 0;
                    if (outerThis.CONTENT_OTHERDATA.currentEditedPostId == outerThis.CONTENT_OTHERDATA.rootpostid) {
                        outerThis.CONTENT_OTHERDATA.showsticky = 0;
                        outerThis.showfrom = 0;
                        t.showfrom = 0;
                        t.showsticky = 0;
                    }
                    if (attachments.length > 0) {
                        return outerThis.CoreFileUploaderProvider.clearTmpFiles(attachments);
                    }
                });
            } else if(outerThis.CONTENT_OTHERDATA.lock && formLockChange) {
                return outerThis.CoreDomUtilsProvider.showConfirm(outerThis.TranslateService.instant('plugin.mod_forumng.leavemessage')).then(function() {
                    outerThis.subject = '';
                    outerThis.message = '';
                    outerThis.CONTENT_OTHERDATA.lock = 0;
                    outerThis.CONTENT_OTHERDATA.files = [];
                    outerThis.PostControl.setValue('');
                    outerThis.message = '';
                    if (attachments.length > 0) {
                        return outerThis.CoreFileUploaderProvider.clearTmpFiles(attachments);
                    }
                });
            } else if ((draft || t.isAddDraft) && formDraftChange) {
                return outerThis.CoreDomUtilsProvider.showConfirm(outerThis.TranslateService.instant('plugin.mod_forumng.leavemessage')).then(function() {
                    t.editDraft = outerThis.resetData(t.editDraft);
                    t.editDraft.draftexists = '';
                    t.isReply = 0;
                    t.isAddDraft = 0;
                    t.isEditDraft = 0;
                    outerThis.CONTENT_OTHERDATA.currentReplyToId = 0;
                    outerThis.CONTENT_OTHERDATA.currentEditedPostId = 0;
                    outerThis.CONTENT_OTHERDATA.draftid = 0;
                    outerThis.updateContent({
                        'cmid': outerThis.CONTENT_OTHERDATA.cmid,
                        'discussionid': outerThis.CONTENT_OTHERDATA.discussionid
                    }, 'mod_forumng', 'posts_view', true);
                    if (attachments.length > 0) {
                        return outerThis.CoreFileUploaderProvider.clearTmpFiles(attachments);
                    }
                });
            } else {
                outerThis.CONTENT_OTHERDATA.files = [];
                outerThis.CONTENT_OTHERDATA.lock = 0;
                outerThis.CONTENT_OTHERDATA.currentReplyToId = 0;
                outerThis.CONTENT_OTHERDATA.currentEditedPostId = 0;
                t.originalEdit = outerThis.resetData(t.originalEdit);
                t.currentReply = outerThis.resetData(t.currentReply);
                t.editDraft = outerThis.resetData(t.editDraft);
                outerThis.PostControl.setValue('');
                t.isEdit = 0;
                t.isReply = 0;
                t.isAddDraft = 0;
                t.isEditDraft = 0;
                if (draft || (t.editDraft.draftexists !== '')) {
                    t.editDraft.draftexists = '';
                    outerThis.CONTENT_OTHERDATA.draftid = 0;
                    outerThis.updateContent({
                        'cmid': outerThis.CONTENT_OTHERDATA.cmid,
                        'discussionid': outerThis.CONTENT_OTHERDATA.discussionid
                    }, 'mod_forumng', 'posts_view', true);
                }
            }
        };
        /**
         * Mark all post read.
         *
         */
        var site = outerThis.CoreSitesProvider.getCurrentSite();
        var cmid = outerThis.CONTENT_OTHERDATA.cmid;
        var userid = site.getUserId();
        var courseid = outerThis.courseId;
        outerThis.mark_all_post_read = function() {
            t.mod_forumng.toMarkAllPostsRead(outerThis, site, cmid, courseid, userid, PopoverTransition, 0);
        };

        outerThis.lock_discussion = function() {
            t.mod_forumng.lock_discussion(outerThis);
        };
        outerThis.expand_all_posts = function() {
            (function expandPosts(postArr) {
                for(var ind in postArr) {
                    var reply = postArr[ind];
                    if (!reply.isexpanded) {
                        reply.isexpanded = true;
                    }
                    if (reply.subreplies) {
                        expandPosts(reply.subreplies);
                    }
                }
            })(outerThis.CONTENT_OTHERDATA.replies);
            outerThis.CONTENT_OTHERDATA.isexpandall = true;
            outerThis.CONTENT_OTHERDATA.iscollapseall = false;
        };

        outerThis.collapse_all_posts = function() {
            (function expandPosts(postArr) {
                for(var ind in postArr) {
                    var reply = postArr[ind];
                    if (reply.isexpanded) {
                        reply.isexpanded = false;
                    }
                    if (reply.subreplies) {
                        expandPosts(reply.subreplies);
                    }
                }
            })(outerThis.CONTENT_OTHERDATA.replies);
            outerThis.CONTENT_OTHERDATA.isexpandall = false;
            outerThis.CONTENT_OTHERDATA.iscollapseall = true;
        };

        outerThis.addDraft = function() {
            t.mod_forumng.addDraft(outerThis);
        };

        t.mod_forumng.currentPostsPage = outerThis;

        setTimeout(function(){
            var el = document.querySelectorAll('.unread-post');
            if (el && el.length) {
                el[0].scrollIntoView();
            }
        }, 500);
    };

    window.forumngDraftPageInit = function(outerThis) {
        var currentOuterThis = outerThis;
        // Same for isOnline.
        outerThis.isOnline = function() {
            return outerThis.CoreAppProvider.isOnline();
        };
        outerThis.deleteDraftInPage = function(draftid) {
            t.mod_forumng.deleteDraft(currentOuterThis, draftid, true);
        };
        outerThis.cancelDeleteDraft = function() {
            outerThis.NavController.pop();
        };
        t.mod_forumng.currentDraftPage = outerThis;
    };

    /**
     * Initialisation for the add discussion page.
     *
     * @param {object} outerThis The main component.
     */
    window.forumngAddDiscussionInit = function(outerThis) {
        outerThis.addDiscussionControl = outerThis.FormBuilder.control();
        outerThis.subject = t.newDiscussion.subject ? t.newDiscussion.subject : outerThis.subject;
        outerThis.addDiscussionControl.value = t.newDiscussion.message ? t.newDiscussion.message : outerThis.message;
        outerThis.CONTENT_OTHERDATA.files = t.newDiscussion.files ? t.newDiscussion.files : outerThis.files;
        outerThis.CONTENT_OTHERDATA.showsticky = t.newDiscussion.sticky ? t.newDiscussion.sticky : outerThis.CONTENT_OTHERDATA.showsticky;
        outerThis.CONTENT_OTHERDATA.showfrom = t.newDiscussion.date ? t.newDiscussion.date : outerThis.CONTENT_OTHERDATA.showfrom;
        outerThis.CONTENT_OTHERDATA.postas = t.newDiscussion.postas ? t.newDiscussion.postas : outerThis.CONTENT_OTHERDATA.postas;
        var regexp = /\S+/;
        outerThis.addDiscussion = function() {
            t.mod_forumng.addDiscussion(outerThis);
        };

        outerThis.resetData = function(t) {
            t.postas = 0;
            t.sticky = 0;
            t.date = 0;
            t.files = [];
            t.message = '';
            t.subject = '';
            t.draftexists = '';
            return t;
        };

        setTimeout(function() {
            if(t.isEditDraft) {
                outerThis.subject = t.editDraft.subject;
                outerThis.addDiscussionControl.setValue(t.editDraft.message);
                outerThis.CONTENT_OTHERDATA.files = t.editDraft.files;
                outerThis.CONTENT_OTHERDATA.showsticky = t.editDraft.sticky;
                outerThis.CONTENT_OTHERDATA.showfrom = t.editDraft.date;
                outerThis.CONTENT_OTHERDATA.postas = t.editDraft.postas;
            }
        }, 100);

        // Set data when editing draft.
        if(outerThis.CONTENT_OTHERDATA.draftid && !t.isEditDraft) {
            t.isEditDraft = 1;
            outerThis.subject = outerThis.CONTENT_OTHERDATA.draftsubject;
            outerThis.addDiscussionControl.value = outerThis.CONTENT_OTHERDATA.draftmessage;
            var $draftattachments = outerThis.CONTENT_OTHERDATA.attachmentsforform;
            if (typeof $draftattachments !== 'object') {
                $draftattachments = JSON.parse($draftattachments);
            }
            if ($draftattachments.length > 0) {
                for (var index in $draftattachments) {
                    $draftattachments[index].toURL = function() {
                        return '';
                    }
                }
            }
            outerThis.CONTENT_OTHERDATA.files = $draftattachments;
            t.editDraft.subject = outerThis.subject;
            t.editDraft.message = outerThis.addDiscussionControl.value;
            t.editDraft.files = outerThis.CONTENT_OTHERDATA.files;
            t.editDraft.sticky = outerThis.CONTENT_OTHERDATA.showsticky;
            t.editDraft.date = outerThis.CONTENT_OTHERDATA.showfrom;
            t.editDraft.postas = outerThis.CONTENT_OTHERDATA.postas;
        }
        if (t.isAddDraft && (t.editDraft.draftexists !== '')) {
            outerThis.CONTENT_OTHERDATA.draftexists = t.editDraft.draftexists;
        }

        outerThis.addDraft = function() {
            t.mod_forumng.addDraft(outerThis);
        };

        outerThis.NewDiscussionCancel = function() {
            outerThis.NavController.pop();
        };

        outerThis.ionViewCanLeave = function() {
            var subject = outerThis.subject;
            var message = outerThis.message;
            var attachments = outerThis.CONTENT_OTHERDATA.files; // Type [FileEntry].
            var showsticky = outerThis.CONTENT_OTHERDATA.showsticky;
            var showfrom = outerThis.CONTENT_OTHERDATA.showfrom;
            var postas = outerThis.CONTENT_OTHERDATA.postas;
            var draft = outerThis.CONTENT_OTHERDATA.draftid;
            var haveDifferentFiles = false;
            if (draft) {
                haveDifferentFiles = t.mod_forumng.checkAttachments(attachments, t.editDraft.files);
            }
            t.editDraft.message = t.editDraft.message.replace('<br />', '<br>');
            var formChange = subject || message || attachments.length > 0 || showsticky != 0 || showfrom != 0 || postas != 0;
            var formDraftChange = t.editDraft.message != message ||  t.editDraft.subject != subject || t.editDraft.postas != postas ||
                t.editDraft.sticky != showsticky || t.editDraft.date != showfrom || haveDifferentFiles;
            if (!draft && !t.isAddDraft && formChange) {
                return outerThis.CoreDomUtilsProvider.showConfirm(outerThis.TranslateService.instant('plugin.mod_forumng.leavemessage')).then(function() {
                    t.newDiscussion =  outerThis.resetData(t.newDiscussion);
                    return outerThis.CoreFileUploaderProvider.clearTmpFiles(attachments);
                });
            } else if ((draft || t.isAddDraft) && !t.isAddDiscussion && formDraftChange) {
                return outerThis.CoreDomUtilsProvider.showConfirm(outerThis.TranslateService.instant('plugin.mod_forumng.leavemessage')).then(function() {
                    t.editDraft = outerThis.resetData(t.editDraft);
                    t.isAddDraft = 0;
                    t.isEditDraft = 0;
                    outerThis.addDiscussionControl.setValue('');
                    outerThis.CONTENT_OTHERDATA.draftid = 0;
                    if (attachments.length > 0) {
                        return outerThis.CoreFileUploaderProvider.clearTmpFiles(attachments);
                    }
                });
            } else {
                outerThis.subject = '';
                outerThis.message = '';
                outerThis.addDiscussionControl.setValue('');
                outerThis.CONTENT_OTHERDATA.files = []; // Type [FileEntry].
                outerThis.CONTENT_OTHERDATA.showsticky = 0;
                outerThis.CONTENT_OTHERDATA.showfrom = 0;
                outerThis.CONTENT_OTHERDATA.postas = 0;
                t.editDraft = outerThis.resetData(t.editDraft);
                t.isEditDraft = 0;
                if (t.isAddDraft || draft) {
                    t.isAddDraft = 0;
                    t.newDiscussion.files = [];
                    outerThis.CONTENT_OTHERDATA.draftid = 0;
                    t.mod_forumng.currentDiscussionsPage.refreshContent();
                }
            }
            return;
        };

        outerThis.onMessageChange = function (text) {
            // Check text in the message.
            var div = document.createElement('div');
            div.innerHTML = text;
            var messagetext = div.textContent;
            if (!messagetext.match(regexp)) {
                text = '';
                outerThis.message = '';
            }
            t.newDiscussion.message = text;
        };
        /**
         * Refresh the data.
         *
         * @param {any} refresher Refresher.
         */
        outerThis.onSubjectChange = function() {
            if (!outerThis.subject.match(regexp)) {
                outerThis.subject = '';
            }
            t.newDiscussion.subject = outerThis.subject;

        };

        outerThis.onFileChange = function() {
            t.newDiscussion.files = outerThis.CONTENT_OTHERDATA.files;
        };

        outerThis.onStickyChange = function() {
            t.newDiscussion.sticky = outerThis.CONTENT_OTHERDATA.showsticky;
        };

        outerThis.onDateChange = function() {
            t.newDiscussion.date = outerThis.CONTENT_OTHERDATA.showfrom;
        };

        outerThis.PostAsChange = function() {
            t.newDiscussion.postas = outerThis.CONTENT_OTHERDATA.postas;
        };

        // Calculate format to use. ion-datetime doesn't support escaping characters ([]), so we remove them.
        outerThis.dateFormat = outerThis.CoreTimeUtilsProvider.convertPHPToMoment('%d %B %Y')
            .replace(/[\[\]]/g, '');

        // Default current date. If we need it.
        // outerThis.startDate = outerThis.CoreTimeUtilsProvider.toDatetimeFormat();

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

    t.mod_forumng.settingsTable = 'mod_forumng_settings';
    t.mod_forumng.settingsTableSchema = {
        name: t.mod_forumng.settingsTable,
        columns: [
            {
                name: 'key',
                type: 'TEXT',
                primaryKey: true
            },
            {
                name: 'value',
                type: 'TEXT',
                notNull: true
            },
            {
                name: 'userid',
                type: 'TEXT',
            },
        ]
    };
    /**
     * Sets preference. If you set value to undefined, it is cleared.
     *
     * @param {string} key Key to set
     * @param {string} [value] Value to set - leave undefined to remove it
     * @return {Promise} Promise resolved when finished
     */
    t.mod_forumng.setPreference = function(key, value, userid) {
        // Create the table if it doesn't exist already, then set the value.
        var db = t.CoreSitesProvider.getCurrentSite().getDb();
        return db.createTableFromSchema(t.mod_forumng.settingsTableSchema).then(function() {
            return db.recordExists(t.mod_forumng.settingsTable, {key: key, userid: userid}).then(function() {
                if (value === undefined) {
                    return db.deleteRecords(t.mod_forumng.settingsTable, {key: key, userid: userid});
                } else {
                    return db.updateRecords(t.mod_forumng.settingsTable, {value: value}, {key: key, userid: userid});
                }
            }, function() {
                if (value !== undefined) {
                    return db.insertRecord(t.mod_forumng.settingsTable, {key: key, userid: userid, value: value});
                }
            });
        });
    };
    /**
     * Gets preference (as promise) - resolved to null if not found
     *
     * @param {string} key Key to read
     * @return {Promise} Promise which will be resolved once the preference has been read
     */
    t.mod_forumng.getPreference = function(key, userid) {
        var db = t.CoreSitesProvider.getCurrentSite().getDb();
        return db.createTableFromSchema(t.mod_forumng.settingsTableSchema).then(function() {
            return db.getRecord(t.mod_forumng.settingsTable, { key: key, userid: userid}).then(function(record) {
                return Promise.resolve(record.value);
            }, function() {
                return Promise.resolve(null);
            });
        });
    };

    t.mod_forumng.needUpdate = 'mod_forumng_needupdate';
    t.mod_forumng.needUpdateTableSchema = {
        name: t.mod_forumng.needUpdate,
        columns: [
            {
                name: 'cmid',
                type: 'TEXT',
                primaryKey: true
            },
            {
                name: 'userid',
                type: 'TEXT',
            },
            {
                name: 'needupdate',
                type: 'TEXT',
            },
        ]
    };
    /**
     * Set needupdate when we have a new update from the page.
     *
     * @param cmid
     * @param needupdate
     * @param userid
     * @returns {Promise} Promise resolved when finished
     */
    t.mod_forumng.setNeedUpdate = function(cmid, needupdate, userid) {
        // Create the table if it doesn't exist already, then set the value.
        var db = t.CoreSitesProvider.getCurrentSite().getDb();
        return db.createTableFromSchema(t.mod_forumng.needUpdateTableSchema).then(function() {
            return db.recordExists(t.mod_forumng.needUpdate, {cmid: cmid, userid: userid}).then(function() {
                if (needupdate === undefined) {
                    return db.deleteRecords(t.mod_forumng.needUpdate, {cmid: cmid, userid: userid});
                } else {
                    return db.updateRecords(t.mod_forumng.needUpdate, {needupdate: needupdate}, {cmid: cmid, userid: userid});
                }
            }, function() {
                if (needupdate !== undefined) {
                    return db.insertRecord(t.mod_forumng.needUpdate, {cmid: cmid, userid: userid, needupdate: needupdate});
                }
            });
        });
    };

    /**
     * Get needupdate when we have a new update from the page.
     *
     * @param cmid
     * @param userid
     * @returns {Promise} Promise resolved when finished
     */
    t.mod_forumng.getNeedUpdate = function(cmid, userid) {
        var db = t.CoreSitesProvider.getCurrentSite().getDb();
        return db.createTableFromSchema(t.mod_forumng.needUpdateTableSchema).then(function() {
            return db.getRecord(t.mod_forumng.needUpdate, {cmid: cmid, userid: userid}).then(function(record) {
                return Promise.resolve(record.needupdate);
            }, function() {
                return Promise.resolve(null);
            });
        });
    };
})(this);
