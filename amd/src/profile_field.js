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
 * Toggles the value input row for profile conditions based on the operator selection.
 *
 * The 'isempty' and 'isnotempty' operators do not require a comparison value,
 * so the value row is hidden when either of those operators is selected.
 *
 * @module     report_unlocker/profile_field
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const EMPTY_OPS = ['isempty', 'isnotempty'];

/**
 * Shows or hides the value row inside the given condition div based on the operator.
 *
 * @param {HTMLSelectElement} select The operator select element.
 */
const toggle = (select) => {
    const condDiv = select.closest('.report-unlocker-condition');
    if (!condDiv) {
        return;
    }
    const valRow = condDiv.querySelector('.report-unlocker-profile-valrow');
    if (!valRow) {
        return;
    }
    valRow.hidden = EMPTY_OPS.includes(select.value);
};

export const init = () => {
    document.querySelectorAll('.report-unlocker-profile-op').forEach(select => {
        toggle(select);
        select.addEventListener('change', () => toggle(select));
    });
};
