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

import {linksDisable, linksEnable, scrollPage, simulateClick} from 'mod_forumng/common';

/**
 * JavaScript to handle select discussion.
 *
 * @module mod_forumng/selectdiscussion
 * @copyright 2024 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
export class SelectDiscussion {
    /**
     * Class constructor
     *
     * @param {object} options Options for ajax request
     */
    constructor(options) {
        this.cloneID = options.cloneID;
        this.stringList = options.stringList;
        this.select = options.select;
    }

    /**
     * Initialises the discussion selector feature, switching the whole page into select mode.
     *
     * @param {HTMLElement} target Target button that indicates where the resulting selection will be posted,
     * or null to cancel select mode
     * @param {Array} includes Array of classes to include in selection
     * @param {Array} excludes Array of classes to exclude from selection
     */
    selectDiscussInit(target, includes, excludes) {
        this.select.on = !!target;

        const discussions = document.querySelectorAll('.forumng-discussionlist tr');
        if (discussions.length == 0) {
            return;
        }
        const confirm = document.createElement('input');
        confirm.setAttribute('type', 'submit');

        const main = document.querySelector('table.forumng-discussionlist');
        const all = document.createElement('input');
        const none = document.createElement('input');
        if (this.select.on) {
            // Make form around main elements.
            const form = document.createElement('form');
            form.setAttribute('method', 'post');
            this.select.form = form;
            form.setAttribute('action', target.form.getAttribute('action'));
            main.classList.add('forumng-selectmode');

            form.inputs = document.createElement('div');
            form.appendChild(form.inputs);
            let field = document.createElement('input');
            field.setAttribute('type', 'hidden');
            field.setAttribute('name', 'fromselect');
            field.setAttribute('value', '1');
            form.inputs.appendChild(field);
            if (this.cloneID) {
                field = document.createElement('input');
                field.setAttribute('type', 'hidden');
                field.setAttribute('name', 'clone');
                field.setAttribute('value', this.cloneID);
                form.inputs.appendChild(field);
            }
            target.form.children[0].querySelectorAll('input').forEach(node => {
                if (node.getAttribute('type') == 'hidden') {
                    const field = node.cloneNode(true);
                    form.inputs.appendChild(field);
                }
            });

            // Make intro.
            form.intro = document.createElement('div');
            form.intro.classList.add('forumng-selectintro');
            main.parentNode.insertBefore(form.intro, main);
            const introText = document.createElement('p');
            introText.textContent = this.stringList.selectdiscintro;
            form.intro.appendChild(introText);

            // Make buttons to select all/none.
            const selectButtons = document.createElement('div');
            selectButtons.classList.add('forumng-selectbuttons');
            form.intro.appendChild(selectButtons);

            all.setAttribute('type', 'button');
            all.setAttribute('value', this.stringList.selectall);
            selectButtons.appendChild(all);
            all.addEventListener('click', () => {
                for (let i = 1; i < discussions.length; i++) {
                    if (discussions[i].check && !discussions[i].check.checked) {
                        simulateClick(discussions[i].check);
                    }
                }
                all.disabled = true;
                none.disabled = false;
            });
            selectButtons.appendChild(document.createTextNode(' '));

            none.setAttribute('type', 'button');
            none.setAttribute('value', this.stringList.deselectall);
            selectButtons.appendChild(none);
            none.addEventListener('click', () => {
                for (let i = 1; i < discussions.length; i++) {
                    if (discussions[i].check && discussions[i].check.checked) {
                        simulateClick(discussions[i].check);
                    }
                }
                all.disabled = false;
                none.disabled = true;
            });

            const forumngFeatures = document.querySelector('#forumng-features');
            if (forumngFeatures && main.closest('.forumng-main').contains(forumngFeatures)) {
                main.closest('.forumng-main').insertBefore(form, forumngFeatures);
            } else {
                main.parentNode.appendChild(form);
            }

            // Make outro.
            form.outro = document.createElement('div');
            form.outro.classList.add('forumng-selectoutro');
            form.appendChild(form.outro);

            confirm.setAttribute('value', this.stringList.confirmselection);
            form.outro.appendChild(confirm);

            form.outro.appendChild(document.createTextNode(' '));

            const cancel = document.createElement('input');
            cancel.setAttribute('type', 'button');
            cancel.setAttribute('id', 'forumng-cancel-select');
            cancel.setAttribute('value', this.stringList.cancel);
            form.outro.appendChild(cancel);
            cancel.addEventListener('click', () => {
                this.selectDiscussInit(null);
            });

            scrollPage(form.intro, null);
            // Disable all discussion select buttons.
            document.querySelectorAll('.forumng-dselectorbutton input').forEach(node => {
                node.disabled = true;
            });
        } else {
            const form = this.select.form;
            form.remove();
            form.intro.remove();
            form.outro.remove();
            main.classList.remove('forumng-selectmode');
            this.select.form = null;
            // Enable all discussion select buttons.
            document.querySelectorAll('.forumng-dselectorbutton input').forEach(node => {
                node.disabled = false;
            });
        }

        discussions.forEach((discussion, index) => {
            if (index > 0 && discussion.classList.contains('forumng-discussion-short')) {
                let useid = discussion.id;
                useid = useid.replace('discrow_', '');
                // Check we interact with this discussion.
                let include = true;
                if (this.select.on) {
                    if (includes.length > 0) {
                        include = false;
                        for (let a = 0; a < includes.length; a++) {
                            if (discussion.classList.contains(includes[a])) {
                                include = true;
                                break;
                            }
                        }
                    }
                    if (excludes.length > 0) {
                        for (let a = 0; a < excludes.length; a++) {
                            if (discussion.classList.contains(excludes[a])) {
                                include = false;
                                break;
                            }
                        }
                    }
                }
                if (include) {
                    this.selectInitDiscuss(discussion, this.select.on, useid);
                }
            }
        });

        window.forumng_select_changed = () => {
            let ok = false;
            let checkcount = 0;
            let availcount = 0;
            discussions.forEach(discussion => {
                if (discussion.check) {
                    availcount++;
                    if (discussion.check.checked) {
                        ok = true;
                        checkcount++;
                    }
                }
            });
            none.disabled = !ok;
            confirm.disabled = !ok;
            all.disabled = checkcount == availcount;
        };

        if (this.select.on) {
            window.forumng_select_changed();
        }
    }

    /**
     * Initialises a discussion row within select mode.
     *
     * @param {HTMLElement} discussion Discussion row
     * @param {boolean} on True if select is being turned on, false if it's being turned off
     * @param {number} number Order number of discussion in list
     */
    selectInitDiscuss(discussion, on, number) {
        if (on) {
            const info = discussion.querySelector('td.cell.c0');
            const span = document.createElement('span');
            span.classList.add('dselectorcheck');
            const spanSeparator = document.createElement('span');
            info.prepend(span);
            discussion.extraSpan = span;
            discussion.classList.add('forumng-deselected');
            const discussionId = number;

            spanSeparator.classList.add('forumng-separator');
            spanSeparator.appendChild(document.createTextNode(' \u2022 '));
            const check = document.createElement('input');
            check.setAttribute('type', 'checkbox');
            check.setAttribute('id', 'check' + discussionId);
            discussion.check = check;
            span.appendChild(check);
            const label = document.createElement('label');
            label.classList.add('accesshide');
            label.setAttribute('for', check.getAttribute('id'));
            span.appendChild(label);
            label.appendChild(document.createTextNode(this.stringList.selectlabel.replace('{$a}', number)));
            span.appendChild(spanSeparator);
            linksDisable(document.body);

            let hidden = document.querySelector("input[name='select" + discussionId + "']");
            if (!hidden) {
                hidden = document.createElement('input');
                hidden.setAttribute('type', 'hidden');
                hidden.setAttribute('value', '0');
                hidden.setAttribute('name', 'selectd' + discussionId);
                this.select.form.appendChild(hidden);
            }
            discussion.forumng_hidden = hidden;

            check.addEventListener('click', () => {
                if (check.checked) {
                    discussion.classList.remove('forumng-deselected');
                    discussion.forumng_hidden.setAttribute('value', '1');
                } else {
                    discussion.classList.add('forumng-deselected');
                    discussion.forumng_hidden.setAttribute('value', '0');
                }
                window.forumng_select_changed();
            });
        } else {
            if (discussion.extraSpan) {
                discussion.extraSpan.remove();
            }
            discussion.classList.remove('forumng-deselected');
            linksEnable(document.body);
        }
    }
}
