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
 * Unit tests for report_unlocker_apply_condition_changes().
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
 * Tests for report_unlocker_apply_condition_changes().
 *
 * @covers ::report_unlocker_apply_condition_changes
 */
final class locallib_apply_test extends \basic_testcase {
    /**
     * Builds a compact availability JSON string via json_encode so the
     * encoding format matches what the function produces internally.
     *
     * @param array $conditions
     * @param array $showc
     * @return string
     */
    private function make_json(array $conditions, array $showc = []): string {
        if (empty($showc)) {
            $showc = array_fill(0, count($conditions), true);
        }
        return json_encode(['c' => $conditions, 'showc' => $showc]);
    }

    public function test_null_json_returns_null_and_no_change(): void {
        [$newjson, $changed] = report_unlocker_apply_condition_changes(null, [], []);
        $this->assertNull($newjson);
        $this->assertFalse($changed);
    }

    public function test_empty_string_returns_null_and_no_change(): void {
        [$newjson, $changed] = report_unlocker_apply_condition_changes('', [], []);
        $this->assertNull($newjson);
        $this->assertFalse($changed);
    }

    public function test_malformed_json_returns_original_and_no_change(): void {
        $raw = '{not valid json}';
        [$newjson, $changed] = report_unlocker_apply_condition_changes($raw, [], []);
        $this->assertSame($raw, $newjson);
        $this->assertFalse($changed);
    }

    public function test_json_without_c_key_returns_original_and_no_change(): void {
        $raw = '{"op":"&"}';
        [$newjson, $changed] = report_unlocker_apply_condition_changes($raw, [], []);
        $this->assertSame($raw, $newjson);
        $this->assertFalse($changed);
    }

    public function test_no_updates_no_removals_returns_false_changed(): void {
        $raw = $this->make_json([['type' => 'date', 'd' => '>=', 't' => 1000]]);
        [$newjson, $changed] = report_unlocker_apply_condition_changes($raw, [], []);
        $this->assertFalse($changed);
        $this->assertSame($raw, $newjson);
    }

    public function test_update_to_same_value_returns_false_changed(): void {
        $raw = $this->make_json([['type' => 'date', 'd' => '>=', 't' => 1234567890]]);
        [$newjson, $changed] = report_unlocker_apply_condition_changes($raw, [0 => ['t' => 1234567890]], []);
        $this->assertFalse($changed);
    }

    public function test_remove_single_condition_from_two(): void {
        $raw = $this->make_json([
            ['type' => 'group', 'id' => 1],
            ['type' => 'date', 'd' => '>=', 't' => 9000],
        ]);

        [$newjson, $changed] = report_unlocker_apply_condition_changes($raw, [], [0]);

        $this->assertTrue($changed);
        $decoded = json_decode($newjson, true);
        $this->assertCount(1, $decoded['c']);
        $this->assertSame('date', $decoded['c'][0]['type']);
    }

    public function test_remove_all_conditions_returns_null_and_changed(): void {
        $raw = $this->make_json([
            ['type' => 'group', 'id' => 1],
            ['type' => 'group', 'id' => 2],
        ]);

        [$newjson, $changed] = report_unlocker_apply_condition_changes($raw, [], [0, 1]);

        $this->assertNull($newjson);
        $this->assertTrue($changed);
    }

    public function test_remove_middle_condition_preserves_remaining_order(): void {
        $raw = $this->make_json([
            ['type' => 'group', 'id' => 10],
            ['type' => 'completion', 'cm' => 5, 'e' => 1],
            ['type' => 'date', 'd' => '<', 't' => 2000],
        ]);

        [$newjson, $changed] = report_unlocker_apply_condition_changes($raw, [], [1]);

        $this->assertTrue($changed);
        $decoded = json_decode($newjson, true);
        $this->assertCount(2, $decoded['c']);
        $this->assertSame('group', $decoded['c'][0]['type']);
        $this->assertSame('date', $decoded['c'][1]['type']);
    }

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
        [$newjson, $changed] = report_unlocker_apply_condition_changes($raw, [], [1]);

        $this->assertTrue($changed);
        $decoded = json_decode($newjson, true);
        $this->assertSame([true, true], $decoded['showc']);
    }

    public function test_showc_defaults_to_true_when_missing(): void {
        // JSON without showc values for every index.
        $raw = json_encode(['c' => [['type' => 'group', 'id' => 1]]]);

        [$newjson, $changed] = report_unlocker_apply_condition_changes($raw, [], []);

        // No change, but the round-trip should not error.
        $this->assertFalse($changed);
    }

    public function test_update_date_timestamp(): void {
        $raw = $this->make_json([['type' => 'date', 'd' => '>=', 't' => 1000000]]);

        [$newjson, $changed] = report_unlocker_apply_condition_changes($raw, [0 => ['t' => 9999999]], []);

        $this->assertTrue($changed);
        $decoded = json_decode($newjson, true);
        $this->assertSame(9999999, $decoded['c'][0]['t']);
        $this->assertSame('>=', $decoded['c'][0]['d']);
    }

    public function test_update_group_id(): void {
        $raw = $this->make_json([['type' => 'group', 'id' => 5]]);

        [$newjson, $changed] = report_unlocker_apply_condition_changes($raw, [0 => ['id' => 99]], []);

        $this->assertTrue($changed);
        $decoded = json_decode($newjson, true);
        $this->assertSame(99, $decoded['c'][0]['id']);
    }

    public function test_update_grade_min_and_max(): void {
        $raw = $this->make_json([['type' => 'grade', 'id' => 10, 'min' => 20.0, 'max' => 80.0]]);

        [$newjson, $changed] = report_unlocker_apply_condition_changes(
            $raw,
            [0 => ['min' => 50.0, 'max' => 100.0]],
            []
        );

        $this->assertTrue($changed);
        $decoded = json_decode($newjson, true);
        $this->assertEqualsWithDelta(50.0, $decoded['c'][0]['min'], 0.001);
        $this->assertEqualsWithDelta(100.0, $decoded['c'][0]['max'], 0.001);
    }

    public function test_unset_field_by_passing_null_value(): void {
        $raw = $this->make_json([['type' => 'grade', 'id' => 10, 'min' => 30.0, 'max' => 90.0]]);

        // Setting max to null should remove the key from the condition.
        [$newjson, $changed] = report_unlocker_apply_condition_changes($raw, [0 => ['max' => null]], []);

        $this->assertTrue($changed);
        $decoded = json_decode($newjson, true);
        $this->assertArrayNotHasKey('max', $decoded['c'][0]);
        $this->assertArrayHasKey('min', $decoded['c'][0]);
    }

    public function test_update_multiple_conditions_independently(): void {
        $raw = $this->make_json([
            ['type' => 'date', 'd' => '>=', 't' => 1000],
            ['type' => 'group', 'id' => 3],
            ['type' => 'date', 'd' => '<', 't' => 5000],
        ]);

        [$newjson, $changed] = report_unlocker_apply_condition_changes(
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

    public function test_remove_one_and_update_another_simultaneously(): void {
        $raw = $this->make_json([
            ['type' => 'date', 'd' => '>=', 't' => 1000],
            ['type' => 'group', 'id' => 5],
            ['type' => 'date', 'd' => '<', 't' => 8000],
        ]);

        // Remove index 1 (group); update index 2 (date).
        [$newjson, $changed] = report_unlocker_apply_condition_changes(
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

    public function test_update_profile_operator_and_value(): void {
        $raw = $this->make_json([
            ['type' => 'profile', 'sf' => 'email', 'op' => 'contains', 'v' => '@old.com'],
        ]);

        [$newjson, $changed] = report_unlocker_apply_condition_changes(
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
}
