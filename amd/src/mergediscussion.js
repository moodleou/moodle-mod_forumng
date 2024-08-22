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
 * Merge discussion for mod_forumng.
 *
 * @module mod_forumng/mergediscussion
 * @copyright 2024 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import MergeModal from 'mod_forumng/mergemodal';
import * as FocusLockManager from 'core/local/aria/focuslock';
import ModalEvents from 'core/modal_events';

export class MergeDiscussion {

    constructor(options) {
        this.discussionid = options.discussionid;
    }

    /**
     * Disables the table row with the specified discussion ID.
     *
     * @param {string} discussionId - The ID of the discussion to disable.
     */
    disableRow(discussionId) {
        const row = document.getElementById(`discrow_${discussionId}`);
        if (row) {
            // Disable the row by adding a "disabled" class or setting attributes.
            row.classList.add('disabled');
            row.style.pointerEvents = 'none';
            row.style.opacity = '0.5';

            // Prevent tabbing into child elements.
            const childElements = row.querySelectorAll('*');
            childElements.forEach(child => {
                child.setAttribute('tabindex', '-1');
            });
        }
    }

    /**
     * Disable other discussion options when in the merging process, only displaying the 'merge here' option.
     */
    disableInputs() {
        // Query all divs with class starting with "forumngfeature_dis_" but excluding "forumngfeature_dis_merge".
        const divs = document.querySelectorAll('div[class^="forumngfeature_dis_"]:not(.forumngfeature_dis_merge)');

        divs.forEach(div => {
            const inputs = div.querySelectorAll('input, select');
            // Disable each input element.
            inputs.forEach(input => {
                input.disabled = true;
            });
        });
    }

    /**
     * Initial function.
     */
    initializer() {
        this.disableRow(this.discussionid);
        this.disableInputs();
    }

}

export const init = (options) => {
    showMergeDialogue();
    if (options && options.discussionid !== 0) {
        const merge = new MergeDiscussion(options);
        merge.initializer();
    }
};

/**
 * Displays a merging instruction dialogue.
 */
const showMergeDialogue = async() => {
    const mergeform = document.querySelector('.forumngfeature_dis_merge form.merge-form');
    // Don't show the dialog in case the user preferences is set.
    if (mergeform && mergeform.getAttribute('action').trim() !== '') {
        return;
    }

    const mergeButton = mergeform.querySelector('input[type="submit"]');

    if (!mergeButton) {
        return;
    }

    mergeButton.addEventListener('click', async(e) => {
        e.preventDefault();
        mergeButton.blur();
        const modal = await MergeModal.create({});
        const $root = await modal.getRoot();
        const root = $root[0];
        const currentForm = root.querySelector('form');

        const urlParams = new URLSearchParams(window.location.search);
        const discussionId = urlParams.get('d');

        modal.show();

        // Lock tab control inside modal.
        FocusLockManager.trapFocus(document.querySelector('.merging-modal'));
        $root.on(ModalEvents.hidden, () => {
            modal.destroy();
            FocusLockManager.untrapFocus();
        });

        $root.on(ModalEvents.save, () => {
            if (discussionId) {
                currentForm.action = `feature/merge/merge.php?d=${discussionId}`;
            }
            currentForm.submit();
        });
    });
};
