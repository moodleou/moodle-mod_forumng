/**
 * Javascript module loading in forumng data.
 *
 * @module      mod_forumng/savecheck
 * @copyright   2024 Will Wise
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Modal from 'core/modal';
import {getString} from 'core/str';
import * as FormChangeChecker from 'core_form/changechecker';
import Pending from 'core/pending';

/**
 * Initializes the save check functionality.
 *
 * @param {number} contextid - The context ID.
 */
export const init = async (contextid) => {
    const button = document.getElementById('id_submitbutton');
    if (button) {
        button.onclick = async (e) => {
            e.preventDefault();
            const pendingPromise = new Pending('mod/forumng:savecheck');
            try {
                const response = await fetch(`confirmloggedin.php?sesskey=${M.cfg.sesskey}&contextid=${contextid}`, {
                    method: 'POST',
                });
                const data = await response.text();
                if (data !== 'ok') {
                    await savefail('savefailnetwork', data);
                    pendingPromise.resolve();
                } else {
                    pendingPromise.resolve();
                    FormChangeChecker.disableAllChecks();
                    if (e.target.form.requestSubmit) {
                        e.target.form.requestSubmit(e.target);
                    } else {
                        e.target.form.submit();
                    }
                }
            } catch (error) {
                await savefail('savefailnetwork', error);
                pendingPromise.resolve();
            }
        };
    }
};

/**
 * Handles save failure scenarios.
 *
 * @param {string} stringname - The name of the string to display.
 * @param {string} info - Additional information about the failure.
 */
const savefail = async (stringname, info) => {
    // Save failed, alert of network or session issue.
    let content = await getString('savefailtext', 'forumng', await getString(stringname, 'forumng'));
    content += `[${info}]`;
    const modal = await Modal.create({
        title: await getString('savefailtitle', 'forumng'),
        body: content,
    });
    await modal.show();
    document.getElementById('id_submitbutton').disabled = true;
    const cancel = document.getElementById('id_cancel');
    cancel.disabled = true;
    // Trap cancel and make it a GET - so works with login.
    cancel.addEventListener('click', () => {
        const form = document.querySelector('#region-main .mform');
        const text = document.querySelector('#fitem_id_message');
        const attach = document.querySelector('#fitem_id_attachments');
        text.remove();
        attach.remove();
        form.setAttribute('method', 'get');
    });
};
