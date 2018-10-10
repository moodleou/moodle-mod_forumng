(function(t) {
    t.loadMorePosts = function(infiniteScrollEvent) {
        // Gather data.
        var total = t.CONTENT_OTHERDATA.totalposts;
        var current = t.CONTENT_OTHERDATA.replies.length;
        var discussionid = t.CONTENT_OTHERDATA.discussionid;
        // Are there any more posts to be displayed?
        if (current < total) {
            // AJAX to get the next batch of posts.
            t.CoreSitesProvider.getCurrentSite().read(
                'mod_forumng_get_more_posts', {discussionid: discussionid, from: current}
            ).then(function (response) {
                response.forEach(function (reply) {
                    t.CONTENT_OTHERDATA.replies.push(reply);
                });
                infiniteScrollEvent.complete();
            });
        } else {
            infiniteScrollEvent.complete();
        }
    };
})(this);
