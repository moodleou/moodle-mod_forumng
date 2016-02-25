YUI.add('moodle-mod_forumng-savecheck', function (Y, NAME) {

M.mod_forumng = M.mod_forumng || {};
M.mod_forumng.savecheck = {
    /**
     * Adds save (session) checking functionality to submit buttons.
     */
    init: function(contextid) {
        // Trap edit saving and test server is up.
        var btns = Y.all('#id_submitbutton, #id_savedraft');
        btns.on('click', function(e) {
            function savefail() {
                // Save failed, alert of network or session issue.
                var content = M.util.get_string('savefailnetwork', 'forumng');
                var config = {
                        title: M.util.get_string('savefailtitle', 'forumng'),
                        message: content,
                        plugins: [Y.Plugin.Drag],
                        modal: true
                    };
                var winWidth = Y.one('body').get('winWidth');
                if (winWidth < 450) {
                    config.width = winWidth - 50;
                }
                var panel = new M.core.alert(config);
                panel.show();
                e.preventDefault();
                if (M.mod_forumng_form.finterval) {
                    // Stop form interval as this resets buttons.
                    clearInterval(M.mod_forumng_form.finterval);
                }
                btns.set('disabled', 'disabled');
                // Trap cancel and make it a GET - so works with login.
                var cancel = Y.one('#id_cancel');
                cancel.on('click', function(e) {
                    var form = Y.one('#region-main #mform1');
                    var text = form.one('#fitem_id_message');
                    var attach = form.one('#fitem_id_attachments');
                    text.remove();
                    attach.remove();
                    form.set('method', 'get');
                });
            }
            function checksave(transactionid, response, args) {
                // Check response OK.
                if (response.responseText != 'ok') {
                    // Send save failed due to login/session error.
                    savefail();
                }
            }
            var cfg = {
                method: 'POST',
                data: 'sesskey=' + M.cfg.sesskey + '&contextid=' + contextid,
                on: {
                    success: checksave,
                    failure: savefail
                },
                sync: true,// Wait for result so we can cancel submit.
                timeout: 10000
            };
            Y.io('confirmloggedin.php', cfg);
        });
    }
};


}, '@VERSION@', {"requires": ["base", "node", "io", "moodle-core-notification-alert"]});
