// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * AI chat assistant modal for report_unlocker.
 *
 * @module     report_unlocker/ai_chat
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {getString, getStrings} from 'core/str';

/** @type {int} Course ID injected from PHP. */
let courseId = 0;

/** @type {string} Sesskey injected from PHP. */
let sesskey = '';

/** @type {string} Base URL for AJAX endpoints. */
let baseurl = '';

/** @type {array} Pending changes confirmed by the teacher. */
let pendingChanges = [];

/**
 * Initialises the AI chat assistant button and modal.
 *
 * @param {int} cid Course ID.
 * @param {string} sk Sesskey.
 * @param {string} url Base URL for AJAX calls.
 */
export const init = (cid, sk, url) => {
    courseId = cid;
    sesskey  = sk;
    baseurl  = url;

    const btn = document.getElementById('report-unlocker-ai-btn');
    if (!btn) {
        return;
    }

    btn.addEventListener('click', openModal);
};

/**
 * Opens the AI assistant modal.
 */
const openModal = () => {
    const modal = document.getElementById('report-unlocker-ai-modal');
    document.body.appendChild(modal);

    resetModal();

    require(['theme_boost/bootstrap/modal'], BootstrapModal => {
        new BootstrapModal(modal).show();
    });

    modal.querySelector('#ru-ai-send-btn').addEventListener('click', sendMessage);
    modal.querySelector('#ru-ai-input').addEventListener('keydown', e => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });
    modal.querySelector('#ru-ai-confirm-btn').addEventListener('click', applyChanges);
};

/**
 * Resets the modal to its initial state.
 */
const resetModal = () => {
    const modal = document.getElementById('report-unlocker-ai-modal');
    modal.querySelector('#ru-ai-input').value = '';
    modal.querySelector('#ru-ai-error').classList.add('d-none');
    modal.querySelector('#ru-ai-error').textContent = '';
    modal.querySelector('#ru-ai-preview-section').classList.add('d-none');
    modal.querySelector('#ru-ai-preview-body').innerHTML = '';
    modal.querySelector('#ru-ai-summary').textContent = '';
    modal.querySelector('#ru-ai-confirm-btn').disabled = true;
    pendingChanges = [];
};

/**
 * Sends the teacher message to the AJAX endpoint and renders the preview.
 */
const sendMessage = async() => {
    const modal   = document.getElementById('report-unlocker-ai-modal');
    const input   = modal.querySelector('#ru-ai-input');
    const message = input.value.trim();

    if (message === '') {
        return;
    }

    setLoading(true);
    hideError();
    hidePreview();

    try {
        const params = new URLSearchParams({
            courseid: courseId,
            message:  message,
            sesskey:  sesskey,
        });

        const response = await fetch(`${baseurl}/report/unlocker/ajax/ai_chat.php`, {
            method:  'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body:    params.toString(),
        });

        const data = await response.json();

        if (!data.success) {
            showError(data.error || await getString('ai_request_failed', 'report_unlocker'));
            return;
        }

        if (!data.preview || data.preview.length === 0) {
            showError(data.summary || await getString('ai_no_changes', 'report_unlocker'));
            return;
        }

        pendingChanges = data.changes;
        renderPreview(data.summary, data.preview);

    } catch (err) {
        showError(await getString('ai_request_failed', 'report_unlocker'));
    } finally {
        setLoading(false);
    }
};

/**
 * Sends confirmed changes to the apply endpoint and reloads on success.
 */
const applyChanges = async() => {
    if (pendingChanges.length === 0) {
        return;
    }

    const modal = document.getElementById('report-unlocker-ai-modal');
    modal.querySelector('#ru-ai-confirm-btn').disabled = true;
    setLoading(true);
    hideError();

    try {
        const params = new URLSearchParams({
            courseid: courseId,
            changes:  JSON.stringify(pendingChanges),
            sesskey:  sesskey,
        });

        const response = await fetch(`${baseurl}/report/unlocker/ajax/ai_apply.php`, {
            method:  'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body:    params.toString(),
        });

        const data = await response.json();

        if (!data.success) {
            showError(data.error || await getString('ai_request_failed', 'report_unlocker'));
            return;
        }

        window.location.reload();

    } catch (err) {
        showError(await getString('ai_request_failed', 'report_unlocker'));
        modal.querySelector('#ru-ai-confirm-btn').disabled = false;
    } finally {
        setLoading(false);
    }
};

/**
 * Renders the preview table with proposed changes.
 *
 * @param {string} summary Human-readable summary from the AI.
 * @param {array}  preview Array of preview rows.
 */
const renderPreview = async(summary, preview) => {
    const modal   = document.getElementById('report-unlocker-ai-modal');
    const section = modal.querySelector('#ru-ai-preview-section');
    const body    = modal.querySelector('#ru-ai-preview-body');
    const summaryEl = modal.querySelector('#ru-ai-summary');

    const [strDelete, strUpdate, strSection] = await getStrings([
        {key: 'ai_action_delete', component: 'report_unlocker'},
        {key: 'ai_action_update', component: 'report_unlocker'},
        {key: 'section', component: 'report_unlocker'},
    ]);

    summaryEl.textContent = summary;
    body.innerHTML = '';

    preview.forEach(row => {
        const tr = document.createElement('tr');
        const actionLabel = row.action === 'delete' ? strDelete : strUpdate;
        const actionClass = row.action === 'delete' ? 'text-danger' : 'text-warning';

        tr.innerHTML = `
            <td>${escapeHtml(row.entry_name)}</td>
            <td>${escapeHtml(strSection.replace('{$a}', row.section_num))}</td>
            <td>${escapeHtml(row.cond_type)}</td>
            <td>${escapeHtml(row.cond_summary)}</td>
            <td class="${actionClass} fw-bold">${escapeHtml(actionLabel)}</td>
        `;
        body.appendChild(tr);
    });

    section.classList.remove('d-none');
    modal.querySelector('#ru-ai-confirm-btn').disabled = false;
};

/**
 * Shows or hides the loading state on the send button.
 *
 * @param {boolean} loading
 */
const setLoading = loading => {
    const modal   = document.getElementById('report-unlocker-ai-modal');
    const sendBtn = modal.querySelector('#ru-ai-send-btn');
    const spinner = modal.querySelector('#ru-ai-spinner');

    sendBtn.disabled = loading;
    if (loading) {
        spinner.classList.remove('d-none');
    } else {
        spinner.classList.add('d-none');
    }
};

/**
 * Shows an error message in the modal.
 *
 * @param {string} message
 */
const showError = message => {
    const modal = document.getElementById('report-unlocker-ai-modal');
    const el    = modal.querySelector('#ru-ai-error');
    el.textContent = message;
    el.classList.remove('d-none');
};

/**
 * Hides the error message.
 */
const hideError = () => {
    const modal = document.getElementById('report-unlocker-ai-modal');
    const el    = modal.querySelector('#ru-ai-error');
    el.classList.add('d-none');
    el.textContent = '';
};

/**
 * Hides the preview section.
 */
const hidePreview = () => {
    const modal = document.getElementById('report-unlocker-ai-modal');
    modal.querySelector('#ru-ai-preview-section').classList.add('d-none');
    modal.querySelector('#ru-ai-preview-body').innerHTML = '';
    modal.querySelector('#ru-ai-summary').textContent = '';
    modal.querySelector('#ru-ai-confirm-btn').disabled = true;
    pendingChanges = [];
};

/**
 * Escapes HTML special characters.
 *
 * @param {string} str
 * @returns {string}
 */
const escapeHtml = str => {
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(String(str)));
    return div.innerHTML;
};
