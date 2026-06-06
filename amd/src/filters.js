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
 * Client-side filter for the Unlocker report.
 *
 * Section and name filters operate at entry level (show/hide activities).
 * The condition-type filter operates at condition level: individual
 * .report-unlocker-condition rows are shown or hidden by data-condtype,
 * and an entry is hidden when none of its conditions remain visible.
 *
 * @module     report_unlocker/filters
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import ModalSaveCancel from 'core/modal_save_cancel';
import ModalEvents from 'core/modal_events';
import {get_string as getString} from 'core/str';

const DEBOUNCE_MS = 400;

/**
 * Applies all three filters and updates section header / no-results visibility.
 *
 * @param {number} sectionNum Active section number; -1 means show all.
 * @param {string} searchTerm Substring matched against data-name (case-insensitive).
 * @param {string} conditionType Condition type matched against data-condtype; '' = all.
 */
const applyFilter = (sectionNum, searchTerm, conditionType) => {
    const lowerSearch = searchTerm.toLowerCase().trim();
    const entries = document.querySelectorAll('.report-unlocker-entry');

    entries.forEach(entry => {
        const sectionMatch = sectionNum < 0 || parseInt(entry.dataset.section, 10) === sectionNum;
        const nameMatch = !lowerSearch || entry.dataset.name.includes(lowerSearch);

        // Show or hide individual condition rows by type.
        const conditions = entry.querySelectorAll('.report-unlocker-condition');
        conditions.forEach(cond => {
            cond.hidden = !!conditionType && cond.dataset.condtype !== conditionType;
        });

        // Hide the entry when the entry-level filters fail OR when the type
        // filter leaves no visible conditions inside.
        const hasVisibleCondition = [...conditions].some(c => !c.hidden);
        entry.hidden = !(sectionMatch && nameMatch && hasVisibleCondition);
    });

    // Hide each group fieldset when none of its entries are visible.
    ['id_modules_header', 'id_sections_header'].forEach(id => {
        const fieldset = document.getElementById(id);
        if (!fieldset) {
            return;
        }
        fieldset.hidden = ![...fieldset.querySelectorAll('.report-unlocker-entry')].some(e => !e.hidden);
    });

    // Show the "no results" message only when every entry is hidden.
    const noResults = document.getElementById('unlocker-no-results');
    if (noResults) {
        noResults.hidden = [...entries].some(e => !e.hidden);
    }

    // Show the clear button only when at least one filter is active.
    const clearBtn = document.getElementById('unlocker-clear-filters');
    if (clearBtn) {
        clearBtn.hidden = sectionNum < 0 && !lowerSearch && !conditionType;
    }
};

export const init = () => {
    const select = document.getElementById('unlocker-section-filter');
    const typesel = document.getElementById('unlocker-type-filter');
    const search = document.getElementById('unlocker-search');
    const clearBtn = document.getElementById('unlocker-clear-filters');
    const removeAllBtn = document.getElementById('unlocker-remove-all');

    let currentSection = -1;
    let currentSearch = '';
    let currentType = '';

    if (select) {
        select.addEventListener('change', () => {
            currentSection = parseInt(select.value, 10);
            applyFilter(currentSection, currentSearch, currentType);
        });
    }

    if (typesel) {
        typesel.addEventListener('change', () => {
            currentType = typesel.value;
            applyFilter(currentSection, currentSearch, currentType);
        });
    }

    if (search) {
        let timer;
        search.addEventListener('input', () => {
            clearTimeout(timer);
            timer = setTimeout(() => {
                currentSearch = search.value;
                applyFilter(currentSection, currentSearch, currentType);
            }, DEBOUNCE_MS);
        });
    }

    if (removeAllBtn) {
        removeAllBtn.addEventListener('click', () => {
            const visibleConds = [...document.querySelectorAll('.report-unlocker-condition')]
                .filter(c => !c.hidden && !c.closest('.report-unlocker-entry').hidden);
            if (!visibleConds.length) {
                return;
            }
            const title = removeAllBtn.dataset.title || '';
            const body = (removeAllBtn.dataset.confirm || '').replace('{count}', visibleConds.length);

            (async() => {
                try {
                    const [modal, yesStr] = await Promise.all([
                        ModalSaveCancel.create({title, body, removeOnClose: true}),
                        getString('yes', 'core'),
                    ]);
                    modal.setSaveButtonText(yesStr);
                    modal.getRoot().on(ModalEvents.save, e => {
                        e.preventDefault();
                        visibleConds.forEach(cond => {
                            const checkbox = cond.querySelector('input[type="checkbox"]');
                            if (checkbox) {
                                checkbox.checked = true;
                            }
                        });
                        modal.destroy();
                        const form = document.querySelector('form.mform');
                        if (form) {
                            form.submit();
                        }
                    });
                    modal.show();
                } catch (e) {
                    window.console.error(e);
                }
            })();
        });
    }

    if (clearBtn) {
        clearBtn.hidden = true;
        clearBtn.addEventListener('click', () => {
            if (select) {
                select.value = '-1';
                currentSection = -1;
            }
            if (typesel) {
                typesel.value = '';
                currentType = '';
            }
            if (search) {
                search.value = '';
                currentSearch = '';
            }
            applyFilter(-1, '', '');
        });
    }
};
