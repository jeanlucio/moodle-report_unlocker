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
 * External function definitions for report_unlocker.
 *
 * @package    report_unlocker
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'report_unlocker_send_message' => [
        'classname'     => 'report_unlocker\\external\\ai_chat',
        'description'   => 'Sends a teacher message to the AI assistant and returns a preview of proposed changes.',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true,
    ],
    'report_unlocker_apply_changes' => [
        'classname'     => 'report_unlocker\\external\\ai_apply',
        'description'   => 'Applies confirmed AI-proposed condition changes to the course.',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true,
    ],
];
