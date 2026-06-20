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
 * Unit and integration tests for the conditions reader.
 *
 * @package    report_unlocker
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_unlocker\local;

/**
 * Tests for report_unlocker\local\conditions.
 *
 * @covers \report_unlocker\local\conditions
 */
final class conditions_test extends \advanced_testcase {
    /**
     * Returns a minimal availability JSON string with one condition.
     *
     * @param array $condition Single condition array.
     * @return string
     */
    private function avail(array $condition): string {
        return json_encode(['op' => '&', 'c' => [$condition], 'showc' => [true]]);
    }

    /**
     * Inputs that must always return an empty array.
     *
     * @return array
     */
    public static function provider_empty_returns(): array {
        return [
            'null'               => [null],
            'empty string'       => [''],
            'malformed json'     => ['{not json}'],
            'array without c'    => ['{"op":"&"}'],
            'empty c array'      => ['{"c":[],"showc":[]}'],
            'c is not an array'  => ['{"c":"string","showc":[]}'],
        ];
    }

    /**
     * Null, empty, and invalid JSON must always return an empty array.
     *
     * @dataProvider provider_empty_returns
     * @param string|null $input
     */
    public function test_empty_returns(?string $input): void {
        $this->assertSame([], conditions::parse_all($input));
    }

    /**
     * A single date condition is parsed with its fields preserved.
     */
    public function test_single_date_condition_parsed_correctly(): void {
        $json = json_encode([
            'c'     => [['type' => 'date', 'd' => '>=', 't' => 1234567890]],
            'showc' => [true],
        ]);

        $result = conditions::parse_all($json);

        $this->assertCount(1, $result);
        $this->assertSame(0, $result[0]['index']);
        $this->assertSame('date', $result[0]['type']);
        $this->assertSame('>=', $result[0]['data']['d']);
        $this->assertSame(1234567890, $result[0]['data']['t']);
    }

    /**
     * Every typed condition in the set is returned in order.
     */
    public function test_multiple_typed_conditions_all_returned(): void {
        $json = json_encode([
            'c' => [
                ['type' => 'date', 'd' => '>=', 't' => 1000],
                ['type' => 'group', 'id' => 5],
                ['type' => 'grouping', 'id' => 3],
                ['type' => 'grade', 'id' => 10, 'min' => 50.0],
                ['type' => 'completion', 'cm' => 20, 'e' => 1],
            ],
            'showc' => [true, true, true, true, true],
        ]);

        $result = conditions::parse_all($json);

        $this->assertCount(5, $result);
        $this->assertSame('date', $result[0]['type']);
        $this->assertSame('group', $result[1]['type']);
        $this->assertSame('grouping', $result[2]['type']);
        $this->assertSame('grade', $result[3]['type']);
        $this->assertSame('completion', $result[4]['type']);
    }

    /**
     * A nested restriction set is returned as a 'nested' type entry.
     */
    public function test_nested_set_returned_as_nested_type(): void {
        $json = json_encode([
            'c' => [
                ['op' => '&', 'c' => [['type' => 'date', 'd' => '>=', 't' => 1000]]],
            ],
            'showc' => [true],
        ]);

        $result = conditions::parse_all($json);

        $this->assertCount(1, $result);
        $this->assertSame(0, $result[0]['index']);
        $this->assertSame('nested', $result[0]['type']);
        $this->assertSame('&', $result[0]['data']['op']);
        $this->assertTrue($result[0]['showc']);
    }

    /**
     * Nested sets are included as 'nested' type entries.
     * Indices must reflect original positions in the 'c' array so
     * condition_writer::apply_changes can address the right slot.
     */
    public function test_indices_reflect_original_array_positions(): void {
        $json = json_encode([
            'c' => [
                // Index 0: nested set.
                ['op' => '&', 'c' => [['type' => 'date', 'd' => '>=', 't' => 1000]]],
                // Index 1: date condition.
                ['type' => 'date', 'd' => '>=', 't' => 1234567890],
                // Index 2: group condition.
                ['type' => 'group', 'id' => 7],
            ],
            'showc' => [true, true, true],
        ]);

        $result = conditions::parse_all($json);

        $this->assertCount(3, $result);
        $this->assertSame(0, $result[0]['index']);
        $this->assertSame('nested', $result[0]['type']);
        $this->assertSame(1, $result[1]['index']);
        $this->assertSame('date', $result[1]['type']);
        $this->assertSame(2, $result[2]['index']);
        $this->assertSame('group', $result[2]['type']);
    }

    /**
     * All condition fields are preserved under the 'data' key.
     */
    public function test_all_condition_fields_preserved_in_data_key(): void {
        $condition = ['type' => 'grade', 'id' => 42, 'min' => 30.5, 'max' => 90];
        $json = json_encode(['c' => [$condition], 'showc' => [true]]);

        $result = conditions::parse_all($json);

        $this->assertSame($condition, $result[0]['data']);
    }

    /**
     * A profile condition keyed by a standard field is parsed correctly.
     */
    public function test_profile_condition_with_sf_field(): void {
        $json = json_encode([
            'c' => [['type' => 'profile', 'sf' => 'email', 'op' => 'contains', 'v' => '@example.com']],
            'showc' => [true],
        ]);

        $result = conditions::parse_all($json);

        $this->assertCount(1, $result);
        $this->assertSame('profile', $result[0]['type']);
        $this->assertSame('email', $result[0]['data']['sf']);
    }

    /**
     * A mix of typed and nested entries is fully returned.
     */
    public function test_mixed_typed_and_nested_all_returned(): void {
        $json = json_encode([
            'c' => [
                ['type' => 'group', 'id' => 1],
                ['op' => '|', 'c' => [['type' => 'date', 'd' => '<', 't' => 9999]]],
                ['type' => 'completion', 'cm' => 5, 'e' => 1],
            ],
            'showc' => [true, true, true],
        ]);

        $result = conditions::parse_all($json);

        $this->assertCount(3, $result);
        $this->assertSame(0, $result[0]['index']);
        $this->assertSame('group', $result[0]['type']);
        $this->assertSame(1, $result[1]['index']);
        $this->assertSame('nested', $result[1]['type']);
        $this->assertSame(2, $result[2]['index']);
        $this->assertSame('completion', $result[2]['type']);
    }

    /**
     * The per-condition showc value is returned for each entry.
     */
    public function test_showc_value_returned_per_condition(): void {
        $json = json_encode([
            'c'     => [
                ['type' => 'group', 'id' => 1],
                ['type' => 'date', 'd' => '>=', 't' => 1000],
            ],
            'showc' => [false, true],
        ]);

        $result = conditions::parse_all($json);

        $this->assertCount(2, $result);
        $this->assertFalse($result[0]['showc']);
        $this->assertTrue($result[1]['showc']);
    }

    /**
     * A missing showc value defaults to true.
     */
    public function test_showc_defaults_to_true_when_missing(): void {
        $json = json_encode([
            'c' => [['type' => 'group', 'id' => 1]],
        ]);

        $result = conditions::parse_all($json);

        $this->assertCount(1, $result);
        $this->assertTrue($result[0]['showc']);
    }

    /**
     * Every returned entry carries a showc key.
     */
    public function test_showc_key_present_in_every_returned_entry(): void {
        $json = json_encode([
            'c'     => [
                ['type' => 'completion', 'cm' => 5, 'e' => 1],
                ['type' => 'grade', 'id' => 10],
                ['type' => 'date', 'd' => '>=', 't' => 0],
            ],
            'showc' => [true, false, true],
        ]);

        $result = conditions::parse_all($json);

        foreach ($result as $cond) {
            $this->assertArrayHasKey('showc', $cond);
        }
        $this->assertSame([true, false, true], array_column($result, 'showc'));
    }

    /**
     * A course without groups returns an empty map.
     */
    public function test_get_groups_returns_empty_for_course_without_groups(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $this->assertSame([], conditions::get_groups($course->id));
    }

    /**
     * Groups are returned as an id => name map.
     */
    public function test_get_groups_returns_correct_id_name_map(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $g1 = $this->getDataGenerator()->create_group(['courseid' => $course->id, 'name' => 'Alpha']);
        $g2 = $this->getDataGenerator()->create_group(['courseid' => $course->id, 'name' => 'Beta']);

        $result = conditions::get_groups($course->id);

        $this->assertArrayHasKey($g1->id, $result);
        $this->assertArrayHasKey($g2->id, $result);
        $this->assertSame('Alpha', $result[$g1->id]);
        $this->assertSame('Beta', $result[$g2->id]);
    }

    /**
     * Groups from other courses are not returned.
     */
    public function test_get_groups_does_not_return_groups_from_other_courses(): void {
        $this->resetAfterTest();
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->create_group(['courseid' => $course2->id, 'name' => 'Foreign']);

        $result = conditions::get_groups($course1->id);

        $this->assertSame([], $result);
    }

    /**
     * A course without groupings returns an empty map.
     */
    public function test_get_groupings_returns_empty_for_course_without_groupings(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $this->assertSame([], conditions::get_groupings($course->id));
    }

    /**
     * Groupings are returned as an id => name map.
     */
    public function test_get_groupings_returns_correct_id_name_map(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $gr1 = $this->getDataGenerator()->create_grouping(['courseid' => $course->id, 'name' => 'Set A']);
        $gr2 = $this->getDataGenerator()->create_grouping(['courseid' => $course->id, 'name' => 'Set B']);

        $result = conditions::get_groupings($course->id);

        $this->assertSame('Set A', $result[$gr1->id]);
        $this->assertSame('Set B', $result[$gr2->id]);
    }

    /**
     * One entry is returned per course section.
     */
    public function test_get_filter_sections_returns_one_entry_per_section(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['numsections' => 3]);

        $result = conditions::get_filter_sections($course->id);

        // Section 0 (General) + 3 topic sections = 4 total.
        $this->assertCount(4, $result);
        foreach ($result as $section) {
            $this->assertArrayHasKey('num', $section);
            $this->assertArrayHasKey('name', $section);
        }
    }

    /**
     * Section numbers are returned sequentially.
     */
    public function test_get_filter_sections_section_nums_are_sequential(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['numsections' => 2]);

        $result = conditions::get_filter_sections($course->id);
        $nums   = array_column($result, 'num');

        $this->assertSame([0, 1, 2], $nums);
    }

    /**
     * Modules without restrictions yield no entries.
     */
    public function test_get_module_conditions_returns_empty_when_no_restrictions(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);

        $this->assertSame([], conditions::get_module_conditions($course->id));
    }

    /**
     * A module with an availability restriction yields one entry.
     */
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

        $result = conditions::get_module_conditions($course->id);

        $this->assertCount(1, $result);
        $this->assertSame('module', $result[0]['type']);
        $this->assertSame($mod->cmid, $result[0]['id']);
        $this->assertSame(['date'], $result[0]['types']);
        $this->assertCount(1, $result[0]['conditions']);
    }

    /**
     * Multiple restricted modules yield multiple entries.
     */
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

        $result = conditions::get_module_conditions($course->id);

        $this->assertCount(2, $result);
    }

    /**
     * The types array deduplicates repeated condition types.
     */
    public function test_get_module_conditions_deduplicates_types(): void {
        $this->resetAfterTest();
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $mod    = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $json   = json_encode([
            'op'   => '&',
            'c'    => [
                ['type' => 'date', 'd' => '>=', 't' => 1000],
                ['type' => 'date', 'd' => '<', 't' => 9000],
            ],
            'showc' => [true, true],
        ]);
        $DB->set_field('course_modules', 'availability', $json, ['id' => $mod->cmid]);
        rebuild_course_cache($course->id, true);

        $result = conditions::get_module_conditions($course->id);

        // Two date conditions but types array must be deduplicated.
        $this->assertSame(['date'], $result[0]['types']);
        $this->assertCount(2, $result[0]['conditions']);
    }

    /**
     * An unrestricted course yields no section entries.
     */
    public function test_get_section_conditions_returns_empty_for_unrestricted_course(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['numsections' => 2]);
        $this->assertSame([], conditions::get_section_conditions($course->id));
    }

    /**
     * A restricted section is detected and described.
     */
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

        $result = conditions::get_section_conditions($course->id);

        $this->assertCount(1, $result);
        $this->assertSame('section', $result[0]['type']);
        $this->assertSame((int) $section->id, $result[0]['id']);
        $this->assertSame(1, $result[0]['sectionnum']);
    }

    /**
     * A nameless section falls back to a generated display name.
     */
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

        $result = conditions::get_section_conditions($course->id);

        $this->assertCount(1, $result);
        $this->assertNotEmpty($result[0]['name']);
    }

    /**
     * Modules without completion tracking are excluded.
     */
    public function test_get_cms_with_completion_excludes_modules_without_tracking(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $this->getDataGenerator()->create_module('assign', [
            'course'     => $course->id,
            'completion' => 0,
        ]);

        $this->assertSame([], conditions::get_cms_with_completion($course->id));
    }

    /**
     * Modules with completion tracking are included.
     */
    public function test_get_cms_with_completion_includes_modules_with_tracking(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $mod    = $this->getDataGenerator()->create_module('assign', [
            'course'     => $course->id,
            'completion' => 1,
        ]);

        $result = conditions::get_cms_with_completion($course->id);

        $this->assertArrayHasKey($mod->cmid, $result);
    }

    /**
     * Standard profile fields are always present.
     */
    public function test_get_profile_fields_always_contains_standard_fields(): void {
        $this->resetAfterTest();

        $result = conditions::get_profile_fields();

        $this->assertIsArray($result);
        // Must have at least one group.
        $this->assertNotEmpty($result);

        // Flatten all keys across groups.
        $allkeys = [];
        foreach ($result as $options) {
            $allkeys = array_merge($allkeys, array_keys($options));
        }

        // Standard fields supported by core availability_profile must be present.
        $this->assertContains('sf:email', $allkeys);
        $this->assertContains('sf:firstname', $allkeys);
    }

    /**
     * Custom profile fields are included when present.
     */
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

        $result = conditions::get_profile_fields();

        $allkeys = [];
        foreach ($result as $options) {
            $allkeys = array_merge($allkeys, array_keys($options));
        }
        $this->assertContains('cf:studentid', $allkeys);
    }

    /**
     * Only one optgroup is returned when there are no custom fields.
     */
    public function test_get_profile_fields_returns_single_group_when_no_custom_fields(): void {
        $this->resetAfterTest();

        $result = conditions::get_profile_fields();

        // With no custom fields, the result should have exactly one optgroup.
        $this->assertCount(1, $result);
    }
}
