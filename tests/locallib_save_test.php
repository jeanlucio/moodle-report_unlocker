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
 * Integration tests for save functions and cross-course security boundary.
 *
 * @package    report_unlocker
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_unlocker;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/report/unlocker/locallib.php');

/**
 * Tests for report_unlocker_save_module_conditions() and
 * report_unlocker_save_section_conditions().
 *
 * @covers ::report_unlocker_save_module_conditions
 * @covers ::report_unlocker_save_section_conditions
 */
final class locallib_save_test extends \advanced_testcase {
    /**
     * Returns a minimal availability JSON string with one condition.
     *
     * @param array $condition
     * @return string
     */
    private function avail(array $condition): string {
        return json_encode(['op' => '&', 'c' => [$condition], 'showc' => [true]]);
    }

    /**
     * Returns the current availability JSON for a course module.
     *
     * @param int $cmid
     * @return string|null
     */
    private function get_cm_availability(int $cmid): ?string {
        global $DB;
        return $DB->get_field('course_modules', 'availability', ['id' => $cmid]) ?: null;
    }

    /**
     * Returns the current availability JSON for a course section.
     *
     * @param int $sectionid
     * @return string|null
     */
    private function get_section_availability(int $sectionid): ?string {
        global $DB;
        return $DB->get_field('course_sections', 'availability', ['id' => $sectionid]) ?: null;
    }


    public function test_save_module_conditions_does_nothing_when_list_empty(): void {
        $this->resetAfterTest();
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $mod    = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $avail  = $this->avail(['type' => 'date', 'd' => '>=', 't' => 1000000]);
        $DB->set_field('course_modules', 'availability', $avail, ['id' => $mod->cmid]);

        report_unlocker_save_module_conditions($course->id, []);

        $this->assertSame($avail, $this->get_cm_availability($mod->cmid));
    }


    public function test_save_module_conditions_updates_date_timestamp(): void {
        $this->resetAfterTest();
        global $DB;

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $mod    = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $DB->set_field(
            'course_modules',
            'availability',
            $this->avail(['type' => 'date', 'd' => '>=', 't' => 1000000]),
            ['id' => $mod->cmid]
        );

        report_unlocker_save_module_conditions($course->id, [[
            'cmid'     => $mod->cmid,
            'updates'  => [0 => ['t' => 9999999]],
            'removals' => [],
        ]]);

        $decoded = json_decode($this->get_cm_availability($mod->cmid), true);
        $this->assertSame(9999999, $decoded['c'][0]['t']);
    }

    public function test_save_module_conditions_updates_group_id(): void {
        $this->resetAfterTest();
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $mod    = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $DB->set_field(
            'course_modules',
            'availability',
            $this->avail(['type' => 'group', 'id' => 5]),
            ['id' => $mod->cmid]
        );

        report_unlocker_save_module_conditions($course->id, [[
            'cmid'     => $mod->cmid,
            'updates'  => [0 => ['id' => 42]],
            'removals' => [],
        ]]);

        $decoded = json_decode($this->get_cm_availability($mod->cmid), true);
        $this->assertSame(42, $decoded['c'][0]['id']);
    }


    public function test_save_module_conditions_removes_single_condition(): void {
        $this->resetAfterTest();
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $mod    = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $json   = json_encode([
            'c' => [
                ['type' => 'group', 'id' => 1],
                ['type' => 'date', 'd' => '>=', 't' => 5000],
            ],
            'showc' => [true, true],
        ]);
        $DB->set_field('course_modules', 'availability', $json, ['id' => $mod->cmid]);

        report_unlocker_save_module_conditions($course->id, [[
            'cmid'     => $mod->cmid,
            'updates'  => [],
            'removals' => [0],
        ]]);

        $decoded = json_decode($this->get_cm_availability($mod->cmid), true);
        $this->assertCount(1, $decoded['c']);
        $this->assertSame('date', $decoded['c'][0]['type']);
    }

    public function test_save_module_conditions_removes_all_sets_null(): void {
        $this->resetAfterTest();
        global $DB;

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $mod    = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $DB->set_field(
            'course_modules',
            'availability',
            $this->avail(['type' => 'group', 'id' => 3]),
            ['id' => $mod->cmid]
        );

        report_unlocker_save_module_conditions($course->id, [[
            'cmid'     => $mod->cmid,
            'updates'  => [],
            'removals' => [0],
        ]]);

        $this->assertNull($this->get_cm_availability($mod->cmid));
    }


    /**
     * A cmid that belongs to a different course must NOT be modified, even
     * when the caller passes its correct cmid in the update list.
     */
    public function test_save_module_conditions_ignores_cmid_from_foreign_course(): void {
        $this->resetAfterTest();
        global $DB;

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $mod2    = $this->getDataGenerator()->create_module('assign', ['course' => $course2->id]);

        $original = $this->avail(['type' => 'date', 'd' => '>=', 't' => 1000000]);
        $DB->set_field('course_modules', 'availability', $original, ['id' => $mod2->cmid]);

        // Attempt to update mod2 while claiming it belongs to course1.
        report_unlocker_save_module_conditions($course1->id, [[
            'cmid'     => $mod2->cmid,
            'updates'  => [0 => ['t' => 0]],
            'removals' => [],
        ]]);

        // The record in course2 must remain untouched.
        $this->assertSame($original, $this->get_cm_availability($mod2->cmid));
    }


    public function test_save_module_conditions_batch_updates_independently(): void {
        $this->resetAfterTest();
        global $DB;

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $mod1   = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $mod2   = $this->getDataGenerator()->create_module('forum', ['course' => $course->id]);

        $DB->set_field(
            'course_modules',
            'availability',
            $this->avail(['type' => 'group', 'id' => 1]),
            ['id' => $mod1->cmid]
        );
        $DB->set_field(
            'course_modules',
            'availability',
            $this->avail(['type' => 'group', 'id' => 2]),
            ['id' => $mod2->cmid]
        );

        report_unlocker_save_module_conditions($course->id, [
            ['cmid' => $mod1->cmid, 'updates' => [0 => ['id' => 10]], 'removals' => []],
            ['cmid' => $mod2->cmid, 'updates' => [0 => ['id' => 20]], 'removals' => []],
        ]);

        $dec1 = json_decode($this->get_cm_availability($mod1->cmid), true);
        $dec2 = json_decode($this->get_cm_availability($mod2->cmid), true);
        $this->assertSame(10, $dec1['c'][0]['id']);
        $this->assertSame(20, $dec2['c'][0]['id']);
    }


    public function test_save_section_conditions_updates_date_timestamp(): void {
        $this->resetAfterTest();
        global $DB;

        $course  = $this->getDataGenerator()->create_course(['numsections' => 1]);
        $section = $DB->get_record('course_sections', ['course' => $course->id, 'section' => 1]);
        $DB->set_field(
            'course_sections',
            'availability',
            $this->avail(['type' => 'date', 'd' => '>=', 't' => 1000000]),
            ['id' => $section->id]
        );

        report_unlocker_save_section_conditions($course->id, [[
            'sectionid' => $section->id,
            'updates'   => [0 => ['t' => 7777777]],
            'removals'  => [],
        ]]);

        $decoded = json_decode($this->get_section_availability((int) $section->id), true);
        $this->assertSame(7777777, $decoded['c'][0]['t']);
    }


    public function test_save_section_conditions_removes_condition(): void {
        $this->resetAfterTest();
        global $DB;

        $course  = $this->getDataGenerator()->create_course(['numsections' => 1]);
        $section = $DB->get_record('course_sections', ['course' => $course->id, 'section' => 1]);
        $DB->set_field(
            'course_sections',
            'availability',
            $this->avail(['type' => 'group', 'id' => 4]),
            ['id' => $section->id]
        );

        report_unlocker_save_section_conditions($course->id, [[
            'sectionid' => $section->id,
            'updates'   => [],
            'removals'  => [0],
        ]]);

        $this->assertNull($this->get_section_availability((int) $section->id));
    }


    /**
     * A section that belongs to a different course must NOT be modified.
     */
    public function test_save_section_conditions_ignores_section_from_foreign_course(): void {
        $this->resetAfterTest();
        global $DB;

        $course1  = $this->getDataGenerator()->create_course(['numsections' => 1]);
        $course2  = $this->getDataGenerator()->create_course(['numsections' => 1]);
        $section2 = $DB->get_record('course_sections', ['course' => $course2->id, 'section' => 1]);

        $original = $this->avail(['type' => 'date', 'd' => '>=', 't' => 1000000]);
        $DB->set_field('course_sections', 'availability', $original, ['id' => $section2->id]);

        // Attempt to remove from section2 while claiming it belongs to course1.
        report_unlocker_save_section_conditions($course1->id, [[
            'sectionid' => $section2->id,
            'updates'   => [],
            'removals'  => [0],
        ]]);

        $this->assertSame($original, $this->get_section_availability((int) $section2->id));
    }

    // Op change tests.

    public function test_save_module_conditions_updates_op(): void {
        $this->resetAfterTest();
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $mod    = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $DB->set_field(
            'course_modules',
            'availability',
            $this->avail(['type' => 'group', 'id' => 1]),
            ['id' => $mod->cmid]
        );

        report_unlocker_save_module_conditions($course->id, [[
            'cmid'     => $mod->cmid,
            'updates'  => [],
            'removals' => [],
            'op'       => '|',
        ]]);

        $decoded = json_decode($this->get_cm_availability($mod->cmid), true);
        $this->assertSame('|', $decoded['op']);
    }

    public function test_save_section_conditions_updates_op(): void {
        $this->resetAfterTest();
        global $DB;

        $course  = $this->getDataGenerator()->create_course(['numsections' => 1]);
        $section = $DB->get_record('course_sections', ['course' => $course->id, 'section' => 1]);
        $DB->set_field(
            'course_sections',
            'availability',
            $this->avail(['type' => 'group', 'id' => 1]),
            ['id' => $section->id]
        );

        report_unlocker_save_section_conditions($course->id, [[
            'sectionid' => $section->id,
            'updates'   => [],
            'removals'  => [],
            'op'        => '!&',
        ]]);

        $decoded = json_decode($this->get_section_availability((int) $section->id), true);
        $this->assertSame('!&', $decoded['op']);
    }

    // Showcupdates tests: per-condition visibility, op and/not-any.

    public function test_save_module_conditions_updates_showc(): void {
        $this->resetAfterTest();
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $mod    = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $json   = json_encode([
            'op'    => '&',
            'c'     => [['type' => 'group', 'id' => 1], ['type' => 'group', 'id' => 2]],
            'showc' => [true, true],
        ]);
        $DB->set_field('course_modules', 'availability', $json, ['id' => $mod->cmid]);

        report_unlocker_save_module_conditions($course->id, [[
            'cmid'         => $mod->cmid,
            'updates'      => [],
            'removals'     => [],
            'showcupdates' => [1 => false],
        ]]);

        $decoded = json_decode($this->get_cm_availability($mod->cmid), true);
        $this->assertTrue($decoded['showc'][0]);
        $this->assertFalse($decoded['showc'][1]);
    }

    // Show tests: global visibility flag, op or/not-all.

    public function test_save_module_conditions_updates_global_show_and_removes_showc(): void {
        $this->resetAfterTest();
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $mod    = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $json   = json_encode([
            'op'    => '|',
            'c'     => [['type' => 'group', 'id' => 1]],
            'showc' => [true],
        ]);
        $DB->set_field('course_modules', 'availability', $json, ['id' => $mod->cmid]);

        report_unlocker_save_module_conditions($course->id, [[
            'cmid'     => $mod->cmid,
            'updates'  => [],
            'removals' => [],
            'op'       => '|',
            'show'     => false,
        ]]);

        $decoded = json_decode($this->get_cm_availability($mod->cmid), true);
        $this->assertFalse($decoded['show']);
        $this->assertArrayNotHasKey('showc', $decoded);
    }

    public function test_save_module_op_transition_and_to_or_rewrites_show_mechanism(): void {
        $this->resetAfterTest();
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $mod    = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $json   = json_encode([
            'op'    => '&',
            'c'     => [['type' => 'group', 'id' => 1]],
            'showc' => [false],
        ]);
        $DB->set_field('course_modules', 'availability', $json, ['id' => $mod->cmid]);

        report_unlocker_save_module_conditions($course->id, [[
            'cmid'     => $mod->cmid,
            'updates'  => [],
            'removals' => [],
            'op'       => '|',
            'show'     => true,
        ]]);

        $decoded = json_decode($this->get_cm_availability($mod->cmid), true);
        $this->assertSame('|', $decoded['op']);
        $this->assertTrue($decoded['show']);
        $this->assertArrayNotHasKey('showc', $decoded);
    }
}
