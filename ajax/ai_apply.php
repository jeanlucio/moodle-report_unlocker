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
 * AJAX endpoint: applies confirmed AI-proposed condition changes to the course.
 *
 * @package    report_unlocker
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/report/unlocker/locallib.php');

$courseid   = required_param('courseid', PARAM_INT);
$changesraw = required_param('changes', PARAM_RAW);

$course  = get_course($courseid);
$context = context_course::instance($courseid);

require_login($course);
require_capability('report/unlocker:editconditions', $context);
require_sesskey();

$changes = json_decode($changesraw, true);
if (!is_array($changes)) {
    echo json_encode(['success' => false, 'error' => get_string('ai_invalid_changes', 'report_unlocker')]);
    die;
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

    if (!in_array($target, $validtargets, true) || $id <= 0 || !in_array($action, $validactions, true)) {
        continue;
    }

    if ($action === 'delete') {
        $removals = [$index];
        $updates  = [];
    } else {
        $removals = [];
        $rawupdates = (array) ($change['updates'] ?? []);

        // Sanitise: only scalar values allowed; cast to expected types.
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

header('Content-Type: application/json');
echo json_encode(['success' => true]);
