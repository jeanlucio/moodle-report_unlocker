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
 * Integration tests for report_unlocker read functions that require DB access.
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
 * Integration tests for get_groups, get_groupings, get_filter_sections,
 * get_module_conditions, get_section_conditions, get_cms_with_completion,
 * and get_profile_fields.
 *
 * @covers ::report_unlocker_get_groups
 * @covers ::report_unlocker_get_groupings
 * @covers ::report_unlocker_get_filter_sections
 * @covers ::report_unlocker_get_module_conditions
 * @covers ::report_unlocker_get_section_conditions
 * @covers ::report_unlocker_get_cms_with_completion
 * @covers ::report_unlocker_get_profile_fields
 */
final class locallib_integration_test extends \advanced_testcase {
    /**
     * Returns a minimal availability JSON string with one condition.
     *
     * @param array $condition Single condition array.
     * @return string
     */
    private function avail(array $condition): string {
        return json_encode(['c' => [$condition], 'showc' => [true]]);
    }


    public function test_get_groups_returns_empty_for_course_without_groups(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $this->assertSame([], report_unlocker_get_groups($course->id));
    }

    public function test_get_groups_returns_correct_id_name_map(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $g1 = $this->getDataGenerator()->create_group(['courseid' => $course->id, 'name' => 'Alpha']);
        $g2 = $this->getDataGenerator()->create_group(['courseid' => $course->id, 'name' => 'Beta']);

        $result = report_unlocker_get_groups($course->id);

        $this->assertArrayHasKey($g1->id, $result);
        $this->assertArrayHasKey($g2->id, $result);
        $this->assertSame('Alpha', $result[$g1->id]);
        $this->assertSame('Beta', $result[$g2->id]);
    }

    public function test_get_groups_does_not_return_groups_from_other_courses(): void {
        $this->resetAfterTest();
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->create_group(['courseid' => $course2->id, 'name' => 'Foreign']);

        $result = report_unlocker_get_groups($course1->id);

        $this->assertSame([], $result);
    }


    public function test_get_groupings_returns_empty_for_course_without_groupings(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $this->assertSame([], report_unlocker_get_groupings($course->id));
    }

    public function test_get_groupings_returns_correct_id_name_map(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $gr1 = $this->getDataGenerator()->create_grouping(['courseid' => $course->id, 'name' => 'Set A']);
        $gr2 = $this->getDataGenerator()->create_grouping(['courseid' => $course->id, 'name' => 'Set B']);

        $result = report_unlocker_get_groupings($course->id);

        $this->assertSame('Set A', $result[$gr1->id]);
        $this->assertSame('Set B', $result[$gr2->id]);
    }


    public function test_get_filter_sections_returns_one_entry_per_section(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['numsections' => 3]);

        $result = report_unlocker_get_filter_sections($course->id);

        // Section 0 (General) + 3 topic sections = 4 total.
        $this->assertCount(4, $result);
        foreach ($result as $section) {
            $this->assertArrayHasKey('num', $section);
            $this->assertArrayHasKey('name', $section);
        }
    }

    public function test_get_filter_sections_section_nums_are_sequential(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['numsections' => 2]);

        $result = report_unlocker_get_filter_sections($course->id);
        $nums   = array_column($result, 'num');

        $this->assertSame([0, 1, 2], $nums);
    }


    public function test_get_module_conditions_returns_empty_when_no_restrictions(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);

        $this->assertSame([], report_unlocker_get_module_conditions($course->id));
    }

    public function test_get_module_conditions_returns_entry_when_availability_set(): void {
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
        rebuild_course_cache($course->id, true);

        $result = report_unlocker_get_module_conditions($course->id);

        $this->assertCount(1, $result);
        $this->assertSame('module', $result[0]['type']);
        $this->assertSame($mod->cmid, $result[0]['id']);
        $this->assertSame(['date'], $result[0]['types']);
        $this->assertCount(1, $result[0]['conditions']);
    }

    public function test_get_module_conditions_returns_multiple_entries(): void {
        $this->resetAfterTest();
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $mod1   = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $mod2   = $this->getDataGenerator()->create_module('forum', ['course' => $course->id]);

        $avail = $this->avail(['type' => 'group', 'id' => 1]);
        $DB->set_field('course_modules', 'availability', $avail, ['id' => $mod1->cmid]);
        $DB->set_field('course_modules', 'availability', $avail, ['id' => $mod2->cmid]);
        rebuild_course_cache($course->id, true);

        $result = report_unlocker_get_module_conditions($course->id);

        $this->assertCount(2, $result);
    }

    public function test_get_module_conditions_deduplicates_types(): void {
        $this->resetAfterTest();
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $mod    = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $json   = json_encode([
            'c' => [
                ['type' => 'date', 'd' => '>=', 't' => 1000],
                ['type' => 'date', 'd' => '<', 't' => 9000],
            ],
            'showc' => [true, true],
        ]);
        $DB->set_field('course_modules', 'availability', $json, ['id' => $mod->cmid]);
        rebuild_course_cache($course->id, true);

        $result = report_unlocker_get_module_conditions($course->id);

        // Two date conditions but types array must be deduplicated.
        $this->assertSame(['date'], $result[0]['types']);
        $this->assertCount(2, $result[0]['conditions']);
    }


    public function test_get_section_conditions_returns_empty_for_unrestricted_course(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['numsections' => 2]);
        $this->assertSame([], report_unlocker_get_section_conditions($course->id));
    }

    public function test_get_section_conditions_detects_restricted_section(): void {
        $this->resetAfterTest();
        global $DB;

        $course = $this->getDataGenerator()->create_course(['numsections' => 2]);
        $section = $DB->get_record('course_sections', ['course' => $course->id, 'section' => 1]);
        $DB->set_field(
            'course_sections',
            'availability',
            $this->avail(['type' => 'group', 'id' => 2]),
            ['id' => $section->id]
        );

        $result = report_unlocker_get_section_conditions($course->id);

        $this->assertCount(1, $result);
        $this->assertSame('section', $result[0]['type']);
        $this->assertSame((int) $section->id, $result[0]['id']);
        $this->assertSame(1, $result[0]['sectionnum']);
    }

    public function test_get_section_conditions_uses_section_number_as_name_fallback(): void {
        $this->resetAfterTest();
        global $DB;

        $course  = $this->getDataGenerator()->create_course(['numsections' => 1]);
        $section = $DB->get_record('course_sections', ['course' => $course->id, 'section' => 1]);

        // Ensure name is null/empty so the fallback is triggered.
        $DB->set_field('course_sections', 'name', null, ['id' => $section->id]);
        $DB->set_field(
            'course_sections',
            'availability',
            $this->avail(['type' => 'date', 'd' => '>=', 't' => 1000]),
            ['id' => $section->id]
        );

        $result = report_unlocker_get_section_conditions($course->id);

        $this->assertCount(1, $result);
        $this->assertNotEmpty($result[0]['name']);
    }


    public function test_get_cms_with_completion_excludes_modules_without_tracking(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $this->getDataGenerator()->create_module('assign', [
            'course'     => $course->id,
            'completion' => 0,
        ]);

        $this->assertSame([], report_unlocker_get_cms_with_completion($course->id));
    }

    public function test_get_cms_with_completion_includes_modules_with_tracking(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $mod    = $this->getDataGenerator()->create_module('assign', [
            'course'     => $course->id,
            'completion' => 1,
        ]);

        $result = report_unlocker_get_cms_with_completion($course->id);

        $this->assertArrayHasKey($mod->cmid, $result);
    }


    public function test_get_profile_fields_always_contains_standard_fields(): void {
        $this->resetAfterTest();

        $result = report_unlocker_get_profile_fields();

        $this->assertIsArray($result);
        // Must have at least one group.
        $this->assertNotEmpty($result);

        // Flatten all keys across groups.
        $allkeys = [];
        foreach ($result as $options) {
            $allkeys = array_merge($allkeys, array_keys($options));
        }

        // Standard email field must be present.
        $this->assertContains('sf:email', $allkeys);
        $this->assertContains('sf:username', $allkeys);
    }

    public function test_get_profile_fields_includes_custom_fields_when_present(): void {
        $this->resetAfterTest();
        global $DB;

        $DB->insert_record('user_info_field', (object) [
            'shortname'    => 'studentid',
            'name'         => 'Student ID',
            'datatype'     => 'text',
            'description'  => '',
            'descriptionformat' => 0,
            'categoryid'   => 1,
            'sortorder'    => 1,
            'required'     => 0,
            'locked'       => 0,
            'visible'      => 2,
            'forceunique'  => 0,
            'signup'       => 0,
            'defaultdata'  => '',
            'defaultdataformat' => 0,
            'param1'       => '',
            'param2'       => '',
            'param3'       => '',
            'param4'       => '',
            'param5'       => '',
        ]);

        $result = report_unlocker_get_profile_fields();

        $allkeys = [];
        foreach ($result as $options) {
            $allkeys = array_merge($allkeys, array_keys($options));
        }
        $this->assertContains('cf:studentid', $allkeys);
    }

    public function test_get_profile_fields_returns_single_group_when_no_custom_fields(): void {
        $this->resetAfterTest();

        $result = report_unlocker_get_profile_fields();

        // With no custom fields, the result should have exactly one optgroup.
        $this->assertCount(1, $result);
    }
}
