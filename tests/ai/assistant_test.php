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
 * Tests for the AI restriction assistant.
 *
 * @package    report_unlocker
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_unlocker\ai;

/**
 * Tests for report_unlocker\ai\assistant.
 *
 * @covers \report_unlocker\ai\assistant
 */
final class assistant_test extends \advanced_testcase {
    /**
     * The assistant constructor loads module and section conditions; it must
     * resolve the migrated conditions class without raising a fatal error.
     */
    public function test_constructor_loads_conditions_without_error(): void {
        $this->resetAfterTest();
        global $DB;

        $course = $this->getDataGenerator()->create_course(['numsections' => 1]);
        $mod    = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $DB->set_field(
            'course_modules',
            'availability',
            json_encode(['op' => '&', 'c' => [['type' => 'date', 'd' => '>=', 't' => 1000000]], 'showc' => [true]]),
            ['id' => $mod->cmid]
        );
        $section = $DB->get_record('course_sections', ['course' => $course->id, 'section' => 1]);
        $DB->set_field(
            'course_sections',
            'availability',
            json_encode(['op' => '&', 'c' => [['type' => 'group', 'id' => 1]], 'showc' => [true]]),
            ['id' => $section->id]
        );
        rebuild_course_cache($course->id, true);

        $assistant = new assistant($course->id);

        $this->assertInstanceOf(assistant::class, $assistant);
    }
}
