M.mod_forumng_form = {
    Y : null,
    finterval: null,
    rootpostid: '',
    /**
     * Main init function called from HTML.
     *
     * @param Y YUI object
     * @param rootpostid integer
     */
    init : function(Y, rootpostid) {
        this.Y = Y;
        this.rootpostid = rootpostid || '';
        Y.on("domready", this.dom_init, this);
        if (!String.prototype.trim) {
            String.prototype.trim = function() {
                return this.replace(/^\s+/, '') . replace(/\s+$/, '');
            };
        }
    },

    /**
     * Main initialisation done on DOM ready.
     */
    dom_init : function() {
        var t = this;

        // Function to get Unix time.
        var get_unix_time = function() {
            return Math.round((new Date()).getTime() / 1000);
        };

        // Check if there's a time limit.
        var timelimitfield = t.Y.one('input[name=timelimit]');
        var timelimit = 0;
        let timeLimitExpired = false;
        if (timelimitfield) {
            timelimit = Number(timelimitfield.get('value'));
            var timelimitinfo = t.Y.one('#id_editlimit');
            // Set timers to change the info text at specific points.
            var interval = setInterval(function() {
                var countdown = timelimit - get_unix_time();
                if (countdown <= 0) {
                    // Show message error in timeout.
                    timelimitinfo.setHTML('<strong>' + M.str.forumng.edit_timeout + '</strong>');
                    timelimitinfo.addClass('forumng-timeoutover');
                    // Disabled submit button.
                    timeLimitExpired = true;
                    var buttonsubmit = t.Y.one('#id_submitbutton');
                    if (buttonsubmit) {
                        buttonsubmit.set('disabled', 'disabled');
                    }
                    // Reload page after.
                    t.Y.one('#id_cancel').on('click', function() {
                        window.top.location.reload();
                    });
                    clearInterval(interval);
                } else if (countdown <= 30) {
                    // Test goes red 30 seconds before timeout.
                    timelimitinfo.setHTML('<strong>' + timelimitinfo.getHTML() + '</strong>');
                    timelimitinfo.addClass('forumng-timeoutover');
                } else if (countdown <= 90) {
                    // Text goes bold 90 seconds before timeout.
                    timelimitinfo.setHTML('<strong>' + timelimitinfo.getHTML() + '</strong>');
                }
            }, 1000);
        }
        // Periodic processing to enable/disable the submit button.
        let isUploading = false;
        document.addEventListener('core_form/uploadStarted', function (e) {
            e.preventDefault();
            isUploading = true;
        });
        document.addEventListener('core_form/uploadCompleted', function (e) {
            e.preventDefault();
            isUploading = false;
            // The editing time is over, we need to disable submit buttons.
            if (timeLimitExpired) {
                let buttonSaveChanges = document.getElementById('id_submitbutton');
                let cancel = document.getElementById('id_cancel');
                buttonSaveChanges.setAttribute('disabled', true);
                cancel.removeAttribute('disabled');
                e.stopImmediatePropagation();
            }
        });
        t.finterval = setInterval(function()
        {
            // Collect data for disabling buttons.
            var submit = t.Y.one('#id_submitbutton');
            var savedraft = t.Y.one('#id_savedraft');
            var textarea = t.Y.one('#id_message' + t.rootpostid);
            if (textarea) {
                var sourcetext = textarea.get('value');
                if (textarea.getStyle('display') == 'none' && window.tinyMCE &&
                        tinyMCE.activeEditor) {
                    sourcetext = tinyMCE.activeEditor.getBody().innerHTML;
                }

                // Get rid of tags and nbsp as literal or entity, then trim.
                var mungevalue = sourcetext.replace(/<.*?>/g, '').replace(
                    /&(nbsp|#160|#xa0);/g, '') . replace(
                        new RegExp(String.fromCharCode(160), 'g'), ' ') .
                    replace(/\s+/, ' ') . trim();

                // Allow an image even if no text.
                if (sourcetext.indexOf('<img ') != -1) {
                    mungevalue = 'gotimage';
                }

                // We will disable the button if there is no text.
                var disable = mungevalue === '';

                // When editing discussion first post, subject must also be not blank.
                if (!disable && t.Y.one('#fitem_id_subject.required')) {
                    if (t.Y.one('#id_subject').get('value').trim() === '') {
                        disable = true;
                    }
                }

                // When the editing time limit has expired, you cannot save.
                if (timelimit && get_unix_time() > timelimit-30) {
                    disable = true;
                }

                // Disable saving and also drafts.
                disable = isUploading || disable;
                submit.set('disabled', disable);
                if (savedraft) {
                    savedraft.set('disabled', disable);
                }
            }
        }, 250);
    }
};
