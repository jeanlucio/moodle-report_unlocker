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
 * Unit and integration tests for the condition writer.
 *
 * @package    report_unlocker
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_unlocker\local;

/**
 * Tests for report_unlocker\local\condition_writer.
 *
 * @covers \report_unlocker\local\condition_writer
 */
final class condition_writer_test extends \advanced_testcase {
    /**
     * Builds a compact availability JSON string via json_encode so the
     * encoding format matches what the writer produces internally.
     *
     * @param array $conditions
     * @param array $showc
     * @param string $op
     * @return string
     */
    private function make_json(array $conditions, array $showc = [], string $op = '&'): string {
        if (empty($showc)) {
            $showc = array_fill(0, count($conditions), true);
        }
        return json_encode(['op' => $op, 'c' => $conditions, 'showc' => $showc]);
    }

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

    /**
     * Null JSON yields null output and no change.
     */
    public function test_null_json_returns_null_and_no_change(): void {
        [$newjson, $changed] = condition_writer::apply_changes(null, [], []);
        $this->assertNull($newjson);
        $this->assertFalse($changed);
    }

    /**
     * An empty string yields null output and no change.
     */
    public function test_empty_string_returns_null_and_no_change(): void {
        [$newjson, $changed] = condition_writer::apply_changes('', [], []);
        $this->assertNull($newjson);
        $this->assertFalse($changed);
    }

    /**
     * Malformed JSON is returned unchanged.
     */
    public function test_malformed_json_returns_original_and_no_change(): void {
        $raw = '{not valid json}';
        [$newjson, $changed] = condition_writer::apply_changes($raw, [], []);
        $this->assertSame($raw, $newjson);
        $this->assertFalse($changed);
    }

    /**
     * JSON without a 'c' key is returned unchanged.
     */
    public function test_json_without_c_key_returns_original_and_no_change(): void {
        $raw = '{"op":"&"}';
        [$newjson, $changed] = condition_writer::apply_changes($raw, [], []);
        $this->assertSame($raw, $newjson);
        $this->assertFalse($changed);
    }

    /**
     * No updates and no removals reports no change.
     */
    public function test_no_updates_no_removals_returns_false_changed(): void {
        $raw = $this->make_json([['type' => 'date', 'd' => '>=', 't' => 1000]]);
        [$newjson, $changed] = condition_writer::apply_changes($raw, [], []);
        $this->assertFalse($changed);
        $this->assertSame($raw, $newjson);
    }

    /**
     * Updating a field to its existing value reports no change.
     */
    public function test_update_to_same_value_returns_false_changed(): void {
        $raw = $this->make_json([['type' => 'date', 'd' => '>=', 't' => 1234567890]]);
        [$newjson, $changed] = condition_writer::apply_changes($raw, [0 => ['t' => 1234567890]], []);
        $this->assertFalse($changed);
    }

    /**
     * Removing one of two conditions keeps the other.
     */
    public function test_remove_single_condition_from_two(): void {
        $raw = $this->make_json([
            ['type' => 'group', 'id' => 1],
            ['type' => 'date', 'd' => '>=', 't' => 9000],
        ]);

        [$newjson, $changed] = condition_writer::apply_changes($raw, [], [0]);

        $this->assertTrue($changed);
        $decoded = json_decode($newjson, true);
        $this->assertCount(1, $decoded['c']);
        $this->assertSame('date', $decoded['c'][0]['type']);
    }

    /**
     * Removing all conditions yields null and a change.
     */
    public function test_remove_all_conditions_returns_null_and_changed(): void {
        $raw = $this->make_json([
            ['type' => 'group', 'id' => 1],
            ['type' => 'group', 'id' => 2],
        ]);

        [$newjson, $changed] = condition_writer::apply_changes($raw, [], [0, 1]);

        $this->assertNull($newjson);
        $this->assertTrue($changed);
    }

    /**
     * Removing the middle condition preserves the order of the rest.
     */
    public function test_remove_middle_condition_preserves_remaining_order(): void {
        $raw = $this->make_json([
            ['type' => 'group', 'id' => 10],
            ['type' => 'completion', 'cm' => 5, 'e' => 1],
            ['type' => 'date', 'd' => '<', 't' => 2000],
        ]);

        [$newjson, $changed] = condition_writer::apply_changes($raw, [], [1]);

        $this->assertTrue($changed);
        $decoded = json_decode($newjson, true);
        $this->assertCount(2, $decoded['c']);
        $this->assertSame('group', $decoded['c'][0]['type']);
        $this->assertSame('date', $decoded['c'][1]['type']);
    }

    /**
     * The showc array is reindexed after a removal.
     */
    public function test_showc_array_reindexed_after_removal(): void {
        $raw = $this->make_json(
            [
                ['type' => 'group', 'id' => 1],
                ['type' => 'date', 'd' => '>=', 't' => 5000],
                ['type' => 'group', 'id' => 2],
            ],
            [true, false, true]
        );

        // Remove the middle (index 1, showc=false).
        [$newjson, $changed] = condition_writer::apply_changes($raw, [], [1]);

        $this->assertTrue($changed);
        $decoded = json_decode($newjson, true);
        $this->assertSame([true, true], $decoded['showc']);
    }

    /**
     * A missing showc round-trips without error and reports no change.
     */
    public function test_showc_defaults_to_true_when_missing(): void {
        // JSON without showc values for every index.
        $raw = json_encode(['c' => [['type' => 'group', 'id' => 1]]]);

        [$newjson, $changed] = condition_writer::apply_changes($raw, [], []);

        // No change, but the round-trip should not error.
        $this->assertFalse($changed);
    }

    /**
     * A date timestamp is updated.
     */
    public function test_update_date_timestamp(): void {
        $raw = $this->make_json([['type' => 'date', 'd' => '>=', 't' => 1000000]]);

        [$newjson, $changed] = condition_writer::apply_changes($raw, [0 => ['t' => 9999999]], []);

        $this->assertTrue($changed);
        $decoded = json_decode($newjson, true);
        $this->assertSame(9999999, $decoded['c'][0]['t']);
        $this->assertSame('>=', $decoded['c'][0]['d']);
    }

    /**
     * A group id is updated.
     */
    public function test_update_group_id(): void {
        $raw = $this->make_json([['type' => 'group', 'id' => 5]]);

        [$newjson, $changed] = condition_writer::apply_changes($raw, [0 => ['id' => 99]], []);

        $this->assertTrue($changed);
        $decoded = json_decode($newjson, true);
        $this->assertSame(99, $decoded['c'][0]['id']);
    }

    /**
     * Grade min and max are updated together.
     */
    public function test_update_grade_min_and_max(): void {
        $raw = $this->make_json([['type' => 'grade', 'id' => 10, 'min' => 20.0, 'max' => 80.0]]);

        [$newjson, $changed] = condition_writer::apply_changes(
            $raw,
            [0 => ['min' => 50.0, 'max' => 100.0]],
            []
        );

        $this->assertTrue($changed);
        $decoded = json_decode($newjson, true);
        $this->assertEqualsWithDelta(50.0, $decoded['c'][0]['min'], 0.001);
        $this->assertEqualsWithDelta(100.0, $decoded['c'][0]['max'], 0.001);
    }

    /**
     * Passing null for a field removes that field from the condition.
     */
    public function test_unset_field_by_passing_null_value(): void {
        $raw = $this->make_json([['type' => 'grade', 'id' => 10, 'min' => 30.0, 'max' => 90.0]]);

        // Setting max to null should remove the key from the condition.
        [$newjson, $changed] = condition_writer::apply_changes($raw, [0 => ['max' => null]], []);

        $this->assertTrue($changed);
        $decoded = json_decode($newjson, true);
        $this->assertArrayNotHasKey('max', $decoded['c'][0]);
        $this->assertArrayHasKey('min', $decoded['c'][0]);
    }

    /**
     * Multiple conditions are updated independently.
     */
    public function test_update_multiple_conditions_independently(): void {
        $raw = $this->make_json([
            ['type' => 'date', 'd' => '>=', 't' => 1000],
            ['type' => 'group', 'id' => 3],
            ['type' => 'date', 'd' => '<', 't' => 5000],
        ]);

        [$newjson, $changed] = condition_writer::apply_changes(
            $raw,
            [0 => ['t' => 2000], 2 => ['t' => 6000]],
            []
        );

        $this->assertTrue($changed);
        $decoded = json_decode($newjson, true);
        $this->assertSame(2000, $decoded['c'][0]['t']);
        $this->assertSame(3, $decoded['c'][1]['id']); // Untouched.
        $this->assertSame(6000, $decoded['c'][2]['t']);
    }

    /**
     * Removing one condition while updating another works simultaneously.
     */
    public function test_remove_one_and_update_another_simultaneously(): void {
        $raw = $this->make_json([
            ['type' => 'date', 'd' => '>=', 't' => 1000],
            ['type' => 'group', 'id' => 5],
            ['type' => 'date', 'd' => '<', 't' => 8000],
        ]);

        // Remove index 1 (group); update index 2 (date).
        [$newjson, $changed] = condition_writer::apply_changes(
            $raw,
            [2 => ['t' => 9999]],
            [1]
        );

        $this->assertTrue($changed);
        $decoded = json_decode($newjson, true);
        $this->assertCount(2, $decoded['c']);
        $this->assertSame('date', $decoded['c'][0]['type']);
        $this->assertSame(1000, $decoded['c'][0]['t']);
        $this->assertSame('date', $decoded['c'][1]['type']);
        $this->assertSame(9999, $decoded['c'][1]['t']);
    }

    /**
     * A profile condition operator and value are updated.
     */
    public function test_update_profile_operator_and_value(): void {
        $raw = $this->make_json([
            ['type' => 'profile', 'sf' => 'email', 'op' => 'contains', 'v' => '@old.com'],
        ]);

        [$newjson, $changed] = condition_writer::apply_changes(
            $raw,
            [0 => ['op' => 'endswith', 'v' => '@new.com']],
            []
        );

        $this->assertTrue($changed);
        $decoded = json_decode($newjson, true);
        $this->assertSame('endswith', $decoded['c'][0]['op']);
        $this->assertSame('@new.com', $decoded['c'][0]['v']);
        $this->assertSame('email', $decoded['c'][0]['sf']);
    }

    /**
     * An operator change updates the op field.
     */
    public function test_opchange_updates_op_field(): void {
        $raw = $this->make_json([['type' => 'date', 'd' => '>=', 't' => 1000]], [], '&');

        [$newjson, $changed] = condition_writer::apply_changes($raw, [], [], '|');

        $this->assertTrue($changed);
        $decoded = json_decode($newjson, true);
        $this->assertSame('|', $decoded['op']);
    }

    /**
     * A null operator change preserves the existing operator.
     */
    public function test_opchange_null_preserves_existing_op(): void {
        $raw = $this->make_json([['type' => 'date', 'd' => '>=', 't' => 1000]], [], '!&');

        [$newjson, $changed] = condition_writer::apply_changes($raw, [], []);

        $this->assertFalse($changed);
        $decoded = json_decode($raw, true);
        $this->assertSame('!&', $decoded['op']);
    }

    /**
     * Changing the operator to its current value reports no change.
     */
    public function test_opchange_to_same_value_returns_false_changed(): void {
        $raw = $this->make_json([['type' => 'date', 'd' => '>=', 't' => 1000]], [], '&');

        [$newjson, $changed] = condition_writer::apply_changes($raw, [], [], '&');

        $this->assertFalse($changed);
    }

    /**
     * A per-condition visibility flag is flipped.
     */
    public function test_showcupdates_flips_single_condition_visibility(): void {
        $raw = $this->make_json(
            [['type' => 'group', 'id' => 1], ['type' => 'date', 'd' => '>=', 't' => 5000]],
            [true, true]
        );

        [$newjson, $changed] = condition_writer::apply_changes($raw, [], [], null, [1 => false]);

        $this->assertTrue($changed);
        $decoded = json_decode($newjson, true);
        $this->assertTrue($decoded['showc'][0]);
        $this->assertFalse($decoded['showc'][1]);
    }

    /**
     * showc overrides are reindexed after a removal.
     */
    public function test_showcupdates_reindexed_after_removal(): void {
        $raw = $this->make_json(
            [
                ['type' => 'group', 'id' => 1],
                ['type' => 'date', 'd' => '>=', 't' => 5000],
                ['type' => 'group', 'id' => 2],
            ],
            [false, true, false]
        );

        // Remove index 1; showcupdates override index 0 to true.
        [$newjson, $changed] = condition_writer::apply_changes($raw, [], [1], null, [0 => true]);

        $this->assertTrue($changed);
        $decoded = json_decode($newjson, true);
        $this->assertCount(2, $decoded['c']);
        $this->assertSame([true, false], $decoded['showc']);
    }

    /**
     * A global show change writes show and removes showc.
     */
    public function test_showchange_writes_global_show_and_removes_showc(): void {
        $raw = $this->make_json([['type' => 'group', 'id' => 1]], [true], '|');

        [$newjson, $changed] = condition_writer::apply_changes($raw, [], [], null, [], false);

        $this->assertTrue($changed);
        $decoded = json_decode($newjson, true);
        $this->assertFalse($decoded['show']);
        $this->assertArrayNotHasKey('showc', $decoded);
    }

    /**
     * A null show change preserves the existing show value.
     */
    public function test_showchange_null_preserves_existing_show(): void {
        $raw = json_encode(['op' => '|', 'c' => [['type' => 'group', 'id' => 1]], 'show' => false]);

        [$newjson, $changed] = condition_writer::apply_changes($raw, [], []);

        $this->assertFalse($changed);
        $decoded = json_decode($raw, true);
        $this->assertFalse($decoded['show']);
    }

    /**
     * Switching from and to or replaces showc with a global show.
     */
    public function test_op_transition_and_to_or_switches_show_mechanism(): void {
        $raw = $this->make_json(
            [['type' => 'group', 'id' => 1], ['type' => 'date', 'd' => '>=', 't' => 1000]],
            [true, false],
            '&'
        );

        [$newjson, $changed] = condition_writer::apply_changes($raw, [], [], '|', [], true);

        $this->assertTrue($changed);
        $decoded = json_decode($newjson, true);
        $this->assertSame('|', $decoded['op']);
        $this->assertTrue($decoded['show']);
        $this->assertArrayNotHasKey('showc', $decoded);
    }

    /**
     * Switching from or to and replaces the global show with showc.
     */
    public function test_op_transition_or_to_and_switches_show_mechanism(): void {
        $raw = json_encode(['op' => '|', 'c' => [['type' => 'group', 'id' => 1]], 'show' => false]);

        [$newjson, $changed] = condition_writer::apply_changes(
            $raw,
            [],
            [],
            '&',
            [0 => true]
        );

        $this->assertTrue($changed);
        $decoded = json_decode($newjson, true);
        $this->assertSame('&', $decoded['op']);
        $this->assertSame([true], $decoded['showc']);
        $this->assertArrayNotHasKey('show', $decoded);
    }

    /**
     * All four operators are accepted; only a real change is reported.
     */
    public function test_all_four_operators_accepted(): void {
        foreach (['&', '|', '!&', '!|'] as $op) {
            $raw = $this->make_json([['type' => 'group', 'id' => 1]], [], '&');
            [, $changed] = condition_writer::apply_changes($raw, [], [], $op);
            if ($op === '&') {
                $this->assertFalse($changed, "op '$op' identical, should not change");
            } else {
                $this->assertTrue($changed, "op '$op' differs, should change");
            }
        }
    }

    /**
     * An empty module update list leaves records untouched.
     */
    public function test_save_module_conditions_does_nothing_when_list_empty(): void {
        $this->resetAfterTest();
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $mod    = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $avail  = $this->avail(['type' => 'date', 'd' => '>=', 't' => 1000000]);
        $DB->set_field('course_modules', 'availability', $avail, ['id' => $mod->cmid]);

        condition_writer::save_module($course->id, []);

        $this->assertSame($avail, $this->get_cm_availability($mod->cmid));
    }

    /**
     * Saving updates a module date timestamp.
     */
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

        condition_writer::save_module($course->id, [[
            'cmid'     => $mod->cmid,
            'updates'  => [0 => ['t' => 9999999]],
            'removals' => [],
        ]]);

        $decoded = json_decode($this->get_cm_availability($mod->cmid), true);
        $this->assertSame(9999999, $decoded['c'][0]['t']);
    }

    /**
     * Saving updates a module group id.
     */
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

        condition_writer::save_module($course->id, [[
            'cmid'     => $mod->cmid,
            'updates'  => [0 => ['id' => 42]],
            'removals' => [],
        ]]);

        $decoded = json_decode($this->get_cm_availability($mod->cmid), true);
        $this->assertSame(42, $decoded['c'][0]['id']);
    }

    /**
     * Saving removes a single module condition.
     */
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

        condition_writer::save_module($course->id, [[
            'cmid'     => $mod->cmid,
            'updates'  => [],
            'removals' => [0],
        ]]);

        $decoded = json_decode($this->get_cm_availability($mod->cmid), true);
        $this->assertCount(1, $decoded['c']);
        $this->assertSame('date', $decoded['c'][0]['type']);
    }

    /**
     * Removing all module conditions sets availability to null.
     */
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

        condition_writer::save_module($course->id, [[
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
        condition_writer::save_module($course1->id, [[
            'cmid'     => $mod2->cmid,
            'updates'  => [0 => ['t' => 0]],
            'removals' => [],
        ]]);

        // The record in course2 must remain untouched.
        $this->assertSame($original, $this->get_cm_availability($mod2->cmid));
    }

    /**
     * A batch updates each module independently.
     */
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

        condition_writer::save_module($course->id, [
            ['cmid' => $mod1->cmid, 'updates' => [0 => ['id' => 10]], 'removals' => []],
            ['cmid' => $mod2->cmid, 'updates' => [0 => ['id' => 20]], 'removals' => []],
        ]);

        $dec1 = json_decode($this->get_cm_availability($mod1->cmid), true);
        $dec2 = json_decode($this->get_cm_availability($mod2->cmid), true);
        $this->assertSame(10, $dec1['c'][0]['id']);
        $this->assertSame(20, $dec2['c'][0]['id']);
    }

    /**
     * Saving updates a section date timestamp.
     */
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

        condition_writer::save_section($course->id, [[
            'sectionid' => $section->id,
            'updates'   => [0 => ['t' => 7777777]],
            'removals'  => [],
        ]]);

        $decoded = json_decode($this->get_section_availability((int) $section->id), true);
        $this->assertSame(7777777, $decoded['c'][0]['t']);
    }

    /**
     * Saving removes a section condition.
     */
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

        condition_writer::save_section($course->id, [[
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
        condition_writer::save_section($course1->id, [[
            'sectionid' => $section2->id,
            'updates'   => [],
            'removals'  => [0],
        ]]);

        $this->assertSame($original, $this->get_section_availability((int) $section2->id));
    }

    /**
     * Saving updates a module operator.
     */
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

        condition_writer::save_module($course->id, [[
            'cmid'     => $mod->cmid,
            'updates'  => [],
            'removals' => [],
            'op'       => '|',
        ]]);

        $decoded = json_decode($this->get_cm_availability($mod->cmid), true);
        $this->assertSame('|', $decoded['op']);
    }

    /**
     * Saving updates a section operator.
     */
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

        condition_writer::save_section($course->id, [[
            'sectionid' => $section->id,
            'updates'   => [],
            'removals'  => [],
            'op'        => '!&',
        ]]);

        $decoded = json_decode($this->get_section_availability((int) $section->id), true);
        $this->assertSame('!&', $decoded['op']);
    }

    /**
     * Saving updates the per-condition showc visibility.
     */
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

        condition_writer::save_module($course->id, [[
            'cmid'         => $mod->cmid,
            'updates'      => [],
            'removals'     => [],
            'showcupdates' => [1 => false],
        ]]);

        $decoded = json_decode($this->get_cm_availability($mod->cmid), true);
        $this->assertTrue($decoded['showc'][0]);
        $this->assertFalse($decoded['showc'][1]);
    }

    /**
     * Saving updates the global show flag and removes showc.
     */
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

        condition_writer::save_module($course->id, [[
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

    /**
     * Saving an and-to-or transition rewrites the show mechanism.
     */
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

        condition_writer::save_module($course->id, [[
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
