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
 * External function: sends a teacher message to the AI assistant.
 *
 * @package    report_unlocker
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_unlocker\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * Processes a teacher message and returns an AI-generated preview of proposed changes.
 *
 * @package    report_unlocker
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ai_chat extends external_api {
    /**
     * Returns parameter definitions.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
            'message'  => new external_value(PARAM_TEXT, 'Teacher message'),
        ]);
    }

    /**
     * Processes the teacher message and returns a preview of proposed condition changes.
     *
     * @param int $courseid Course ID.
     * @param string $message Teacher's natural-language request.
     * @return array
     */
    public static function execute(int $courseid, string $message): array {
        global $CFG;

        [
            'courseid' => $courseid,
            'message'  => $message,
        ] = self::validate_parameters(
            self::execute_parameters(),
            ['courseid' => $courseid, 'message' => $message]
        );

        $context = \context_course::instance($courseid);
        self::validate_context($context);
        require_capability('report/unlocker:editconditions', $context);

        require_once($CFG->dirroot . '/report/unlocker/locallib.php');

        $message = trim($message);
        if ($message === '') {
            throw new \moodle_exception('ai_empty_message', 'report_unlocker');
        }

        $assistant = new \report_unlocker\ai\assistant($courseid);
        $result    = $assistant->process($message);

        if (!$result['success']) {
            throw new \moodle_exception($result['errorcode'], 'report_unlocker');
        }

        $preview = array_map(static function (array $row): array {
            return [
                'entry_name'   => $row['entry_name'],
                'section_name' => $row['section_name'],
                'cond_type'    => $row['cond_type'],
                'cond_summary' => $row['cond_summary'],
                'action'       => $row['action'],
            ];
        }, $result['preview']);

        return [
            'summary'     => $result['summary'],
            'preview'     => $preview,
            'changesjson' => json_encode($result['changes']),
        ];
    }

    /**
     * Returns the external function return value definition.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'summary'     => new external_value(PARAM_TEXT, 'Human-readable summary of proposed changes'),
            'preview'     => new external_multiple_structure(
                new external_single_structure([
                    'entry_name'   => new external_value(PARAM_TEXT, 'Activity or section name'),
                    'section_name' => new external_value(PARAM_TEXT, 'Section name'),
                    'cond_type'    => new external_value(PARAM_ALPHANUMEXT, 'Condition type'),
                    'cond_summary' => new external_value(PARAM_TEXT, 'Condition description'),
                    'action'       => new external_value(PARAM_ALPHA, 'Action: delete or update'),
                ]),
                'Preview rows'
            ),
            'changesjson' => new external_value(PARAM_RAW, 'Changes encoded as JSON for re-submission'),
        ]);
    }
}
