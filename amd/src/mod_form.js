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

export const init = (formId, timeTracking) => {
    const form = document.getElementById(formId);
    const timeTrackingFrom = document.querySelector('#id_timetrackingfrom_enabled');
    const timeTrackingTo = document.querySelector('#id_timetrackingto_enabled');
    if (timeTracking.timetrackingfrom === '0') {
        timeTrackingFrom.checked = false;
    }
    if (timeTracking.timetrackingto === '0') {
        timeTrackingTo.checked = false;
    }
    completionForm(form);
};

/**
 * Listens when the user changes the required conditions in completion
 * and toggle wordcount min max option and enable/disable the tracking time.
 *
 * @param {HTMLElement} formElement The root form element.
 */
const completionForm = (formElement) => {
    const forumngCompletionRequire = formElement.querySelectorAll('.forumng-completion-require:checked');
    const allRequirements = document.querySelector('#id_completion_2, #id_completion_forumng_2');
    if (!forumngCompletionRequire.length) {
        toggleHiddenClassWordcount(true);
        allRequirements.addEventListener('change', function() {
            if (allRequirements.checked) {
                enableDisableTrackingTime(true);
            }
        });
    }

    formElement.querySelectorAll('input.forumng-completion-require').forEach(item => {
        item.addEventListener('change', function() {
            const forumngCompletionRequire = formElement.querySelectorAll('.forumng-completion-require:checked');
            if (forumngCompletionRequire.length > 0) {
                toggleHiddenClassWordcount(false);
                enableDisableTrackingTime(false);
            } else {
                toggleHiddenClassWordcount(true);
                enableDisableTrackingTime(true);
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
    const fgroupWordcountMin = document.querySelector('#fgroup_id_completionwordcountmingroup,' +
        '#fgroup_id_completionwordcountmingroup_forumng');
    const fgroupWordcountMax = document.querySelector('#fgroup_id_completionwordcountmaxgroup,' +
        '#fgroup_id_completionwordcountmaxgroup_forumng');
    if (status) {
        fgroupWordcountMin.classList.add('hidden');
        fgroupWordcountMax.classList.add('hidden');
    } else {
        fgroupWordcountMin.classList.remove('hidden');
        fgroupWordcountMax.classList.remove('hidden');
    }
};

/**
 * Enables or disables the "From" and "To" time tracking input fields.
 *
 * @param {Boolean} status
 */
const enableDisableTrackingTime = (status) => {
    const timeTrackingFrom = document.querySelector('#id_timetrackingfrom_enabled, #id_timetrackingfrom_forumng_enabled');
    const timeTrackingTo = document.querySelector('#id_timetrackingto_enabled, #id_timetrackingto_forumng_enabled');
    const timeTrackingSelects = document.querySelectorAll('select[id^="id_timetracking"]');
    if (status) {
        timeTrackingFrom.disabled = true;
        timeTrackingTo.disabled = true;
        timeTrackingFrom.checked = false;
        timeTrackingTo.checked = false;
        timeTrackingSelects.forEach(element => {
            if (element.disabled !== true) {
                element.disabled = true;
            }
        });
    } else {
        const timeTrackingSelectsFrom = document.querySelectorAll('select[id^="id_timetrackingfrom"]');
        const timeTrackingSelectsTo = document.querySelectorAll('select[id^="id_timetrackingto"]');
        // Since datetime selections are disabled when the status is true, we should check and enable them when false.
        timeTrackingFrom.addEventListener('change', function() {
            if (timeTrackingFrom.checked) {
                timeTrackingSelectsFrom.forEach(element => {
                    element.disabled = false;
                });
            }
        });
        timeTrackingTo.addEventListener('change', function() {
            if (timeTrackingTo.checked) {
                timeTrackingSelectsTo.forEach(element => {
                    element.disabled = false;
                });
            }
        });
        timeTrackingFrom.disabled = false;
        timeTrackingTo.disabled = false;
    }
};
