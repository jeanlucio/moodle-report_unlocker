<?php
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
 * External function: applies confirmed AI-proposed condition changes to the course.
 *
 * @package    report_unlocker
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_unlocker\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * Applies a set of AI-proposed condition changes to course modules and sections.
 *
 * @package    report_unlocker
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ai_apply extends external_api {

    /**
     * Returns parameter definitions.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid'    => new external_value(PARAM_INT, 'Course ID'),
            'changesjson' => new external_value(PARAM_RAW, 'Changes encoded as JSON'),
        ]);
    }

    /**
     * Applies the confirmed changes to course restrictions.
     *
     * @param int $courseid Course ID.
     * @param string $changesjson JSON-encoded array of change instructions.
     * @return array
     */
    public static function execute(int $courseid, string $changesjson): array {
        global $CFG;

        [
            'courseid'    => $courseid,
            'changesjson' => $changesjson,
        ] = self::validate_parameters(
            self::execute_parameters(),
            ['courseid' => $courseid, 'changesjson' => $changesjson]
        );

        $context = \context_course::instance($courseid);
        self::validate_context($context);
        require_capability('report/unlocker:editconditions', $context);

        require_once($CFG->dirroot . '/report/unlocker/locallib.php');

        $changes = json_decode($changesjson, true);
        if (!is_array($changes)) {
            throw new \moodle_exception('ai_invalid_changes', 'report_unlocker');
        }

        $validtargets = ['module', 'section'];
        $validactions = ['delete', 'update'];

        $moduleupdates  = [];
        $sectionupdates = [];

        foreach ($changes as $change) {
            $target = (string) ($change['target'] ?? '');
            $id     = (int) ($change['id'] ?? 0);
            $index  = (int) ($change['condition_index'] ?? 0);
            $action = (string) ($change['action'] ?? '');

            if (
                !in_array($target, $validtargets, true)
                || $id <= 0
                || !in_array($action, $validactions, true)
            ) {
                continue;
            }

            if ($action === 'delete') {
                $removals = [$index];
                $updates  = [];
            } else {
                $removals   = [];
                $rawupdates = (array) ($change['updates'] ?? []);

                $updates = [];
                foreach ($rawupdates as $field => $value) {
                    $field = (string) $field;
                    if (!preg_match('/^[a-z_]{1,20}$/i', $field)) {
                        continue;
                    }
                    if (is_null($value)) {
                        $updates[$index][$field] = null;
                    } else if (is_float($value) || (is_string($value) && strpos($value, '.') !== false)) {
                        $updates[$index][$field] = (float) $value;
                    } else if (is_bool($value)) {
                        $updates[$index][$field] = (bool) $value;
                    } else {
                        $updates[$index][$field] = (int) $value;
                    }
                }
            }

            if ($target === 'module') {
                $moduleupdates[] = [
                    'cmid'         => $id,
                    'updates'      => $updates,
                    'removals'     => $removals,
                    'op'           => null,
                    'showcupdates' => [],
                    'show'         => null,
                ];
            } else {
                $sectionupdates[] = [
                    'sectionid'    => $id,
                    'updates'      => $updates,
                    'removals'     => $removals,
                    'op'           => null,
                    'showcupdates' => [],
                    'show'         => null,
                ];
            }
        }

        report_unlocker_save_module_conditions($courseid, $moduleupdates);
        report_unlocker_save_section_conditions($courseid, $sectionupdates);

        return ['applied' => true];
    }

    /**
     * Returns the external function return value definition.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'applied' => new external_value(PARAM_BOOL, 'Whether changes were applied successfully'),
        ]);
    }
}
