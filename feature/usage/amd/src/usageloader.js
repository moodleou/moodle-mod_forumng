/**
 * Javascript module loading in forumng data.
 *
 * @module      forumngfeature_usage/usageloader
 * @copyright   2024 Will Wise
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';

/**
 * Initializes the usage loader, pulls in content.
 *
 * @param {string} name - The name of the usage type.
 * @param {Object} params - The parameters for the usage request.
 */
export const init = (name, params) => {
    const container = document.querySelector('.forumngusageshow' + name);
    if (!container) {
        return;
    }
    let div = document.createElement('div');
    div.classList.add('ajaxworking');
    container.append(div);
    Ajax.call([{
        methodname: 'forumngfeature_usage_get_usage',
        args: {
            type: name,
            param: params
        },
        done: function(o) {
            if (o.responseText) {
                // Process the JSON data returned from the server.
                try {
                    let response = JSON.parse(o.responseText);
                    if (response.error) {
                        usageloader_killspinner(true, container);
                        return;
                    }
                    if (response.content) {
                        usageloader_killspinner(false, container);
                        container.innerHTML = response.content;
                    }
                } catch (e) {
                    usageloader_killspinner(true, container);
                }
            } else {
                usageloader_killspinner(true, container);
            }
        },
        fail: function () {
            usageloader_killspinner(true, container);
        }
    }]);
};

/**
 * Removes the spinner and optionally shows the noajax load link.
 *
 * @param {boolean} failed - Indicates if the request failed.
 * @param {HTMLElement} container - The container element.
 */
const usageloader_killspinner = (failed, container) => {
    const spinner = container.querySelector('.ajaxworking');
    if (spinner) {
        spinner.remove();
    }
    if (failed) {
        // Show noajax load link.
        const noscript = container.querySelector('.forumngusage_loader_noscript');
        if (noscript) {
            noscript.style.display = 'block';
        }
    }
};
