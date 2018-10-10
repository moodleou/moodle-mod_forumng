(function(t) {
    t.loadMoreDiscussions = function(infiniteScrollEvent) {
        // Gather data.
        var total = t.CONTENT_OTHERDATA.totaldiscussions;
        var current = t.CONTENT_OTHERDATA.discussions.length;
        var groupid = t.CONTENT_OTHERDATA.defaultgroup;
        var cmid = t.module.id;
        var page = t.CONTENT_OTHERDATA.page + 1;
        // Are there any more discussions to be displayed?
        if (current < total) {
            // AJAX to get the next page of discussions.
            t.CoreSitesProvider.getCurrentSite().read(
                'mod_forumng_get_more_discussions', {cmid: cmid, groupid: groupid, pageno: page}
            ).then(function (response) {
                response.forEach(function (discussion) {
                    // Is'nt Angular/Ionic wonderful. This does all the work for us.
                    t.CONTENT_OTHERDATA.discussions.push(discussion);
                });
                t.CONTENT_OTHERDATA.page = page;
                infiniteScrollEvent.complete();
            });
        } else {
            infiniteScrollEvent.complete();
        }
    };
})(this);
