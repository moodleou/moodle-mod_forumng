YUI.add('moodle-forumngfeature_usage-usageloader', function(Y) {
    M.mod_forumng = M.mod_forumng || {};
    M.mod_forumng.forumngfeature_usage_loader = M.mod_forumng.forumngfeature_usage_loader || {
        // Dynamically load data into a div.
        init : function(name, params) {
                var container = Y.one('.forumngusageshow' + name);
                if (container) {
                    var cfg = {
                            method: 'GET',
                            on: {
                                start: function() {
                                    // Add an ajax 'spinner'.
                                    container.append('<div class="ajaxworking" />');
                                },
                                success: function(transactionid, o) {
                                    if (o.responseText) {
                                        // Process the JSON data returned from the server.
                                        try {
                                            var response = Y.JSON.parse(o.responseText);
                                            if (response.error) {
                                                usageloader_killspinner(true);
                                                return;
                                            }
                                            if (response.content) {
                                                usageloader_killspinner(false);
                                                container.set('innerHTML', response.content);
                                            }
                                        } catch (e) {
                                            usageloader_killspinner(true);
                                            return;
                                        }
                                    } else {
                                        usageloader_killspinner(true);
                                        return;
                                    }
                                },
                                failure: function() {
                                    usageloader_killspinner(true);
                                }
                            },
                            data: params
                    };
                    var uri = M.cfg.wwwroot + '/mod/forumng/feature/usage/usage_ajax.php';
                    Y.io(uri, cfg);
                    var usageloader_killspinner = function(failed) {
                        var spinner = container.one('.ajaxworking');
                        if (spinner) {
                            spinner.remove(true);
                        }
                        if (failed) {
                            // Show noajax load link.
                            var noscript = container.one('.forumngusage_loader_noscript');
                            if (noscript) {
                                noscript.setStyle('display', 'block');
                            }
                        }
                    };
                }
            },
        };
    }, '@VERSION@', {requires: ['node', 'io', 'json-parse']}
);
