YUI.add('moodle-forumngfeature_usage-usagegraph', function(Y) {
    M.mod_forumng = M.mod_forumng || {};
    M.mod_forumng.forumngfeature_usage_chart = M.mod_forumng.forumngfeature_usage_chart || {
        // Output a YUI chart - you must supply data and options inc render.
        output : function(dataarray, axesarray, options) {
                options.dataProvider = dataarray;
                options.axes = axesarray;
                new Y.Chart(options);
            },
        };
    }, '@VERSION@', {requires: ['node', 'charts']}
);
