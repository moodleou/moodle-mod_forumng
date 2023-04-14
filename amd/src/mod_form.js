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

/*
 * A javascript module to enhance the mod form.
 *
 * @package mod_forumng
 * @copyright 2023 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

export const init = (formId) => {
    const form = document.getElementById(formId);
    completionFormMinMax(form);
};

/**
 * Listens when the user changes the require conditions in completion
 * and toggle wordcount min max option.
 *
 * @param {HTMLElement} formElement The root form element.
 */
const completionFormMinMax = (formElement) => {
    const forumngCompletionRequire = formElement.querySelectorAll('.forumng-completion-require:checked');
    if (!forumngCompletionRequire.length) {
        toggleHiddenClassWordcount(true);
    }

    formElement.querySelectorAll('input.forumng-completion-require').forEach(item => {
        item.addEventListener('change', function() {
            const forumngCompletionRequire = formElement.querySelectorAll('.forumng-completion-require:checked');
            if (forumngCompletionRequire.length > 0) {
                toggleHiddenClassWordcount(false);
            } else {
                toggleHiddenClassWordcount(true);
            }
        });
    });
};


/**
 * Show/hide wordcount group.
 *
 * @param {Boolean} status hidden status.
 */
const toggleHiddenClassWordcount = (status) => {
    const fgroupWordcountMin = document.querySelector('#fgroup_id_completionwordcountmingroup');
    const fgroupWordcountMax = document.querySelector('#fgroup_id_completionwordcountmaxgroup');
    if (status) {
        fgroupWordcountMin.classList.add('hidden');
        fgroupWordcountMax.classList.add('hidden');
    } else {
        fgroupWordcountMin.classList.remove('hidden');
        fgroupWordcountMax.classList.remove('hidden');
    }
};
