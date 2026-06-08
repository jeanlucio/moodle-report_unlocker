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
 * AJAX endpoint: receives a teacher message and returns an AI-generated preview.
 *
 * @package    report_unlocker
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/report/unlocker/locallib.php');

$courseid = required_param('courseid', PARAM_INT);
$message  = required_param('message', PARAM_TEXT);

$course  = get_course($courseid);
$context = context_course::instance($courseid);

require_login($course);
require_capability('report/unlocker:editconditions', $context);
require_sesskey();

$message = trim($message);
if ($message === '') {
    echo json_encode(['success' => false, 'error' => get_string('ai_empty_message', 'report_unlocker')]);
    die;
}

$assistant = new \report_unlocker\ai\assistant($courseid);
$result    = $assistant->process($message);

header('Content-Type: application/json');
echo json_encode($result);
