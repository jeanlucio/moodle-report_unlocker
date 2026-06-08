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
 * Unit tests for report_unlocker_parse_all_conditions().
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
 * Tests for report_unlocker_parse_all_conditions().
 *
 * @covers ::report_unlocker_parse_all_conditions
 */
final class locallib_parse_test extends \basic_testcase {
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
        $this->assertSame([], report_unlocker_parse_all_conditions($input));
    }

    public function test_single_date_condition_parsed_correctly(): void {
        $json = json_encode([
            'c'     => [['type' => 'date', 'd' => '>=', 't' => 1234567890]],
            'showc' => [true],
        ]);

        $result = report_unlocker_parse_all_conditions($json);

        $this->assertCount(1, $result);
        $this->assertSame(0, $result[0]['index']);
        $this->assertSame('date', $result[0]['type']);
        $this->assertSame('>=', $result[0]['data']['d']);
        $this->assertSame(1234567890, $result[0]['data']['t']);
    }

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

        $result = report_unlocker_parse_all_conditions($json);

        $this->assertCount(5, $result);
        $this->assertSame('date', $result[0]['type']);
        $this->assertSame('group', $result[1]['type']);
        $this->assertSame('grouping', $result[2]['type']);
        $this->assertSame('grade', $result[3]['type']);
        $this->assertSame('completion', $result[4]['type']);
    }

    public function test_nested_set_without_type_is_skipped(): void {
        $json = json_encode([
            'c' => [
                ['op' => '&', 'c' => [['type' => 'date', 'd' => '>=', 't' => 1000]]],
            ],
            'showc' => [true],
        ]);

        $this->assertSame([], report_unlocker_parse_all_conditions($json));
    }

    /**
     * When a nested set precedes typed conditions, indices must reflect
     * original positions in the 'c' array so apply_condition_changes
     * can address the right slot.
     */
    public function test_indices_reflect_original_array_positions(): void {
        $json = json_encode([
            'c' => [
                // Index 0: nested set, skipped by the parser.
                ['op' => '&', 'c' => [['type' => 'date', 'd' => '>=', 't' => 1000]]],
                // Index 1: date condition.
                ['type' => 'date', 'd' => '>=', 't' => 1234567890],
                // Index 2: group condition.
                ['type' => 'group', 'id' => 7],
            ],
            'showc' => [true, true, true],
        ]);

        $result = report_unlocker_parse_all_conditions($json);

        $this->assertCount(2, $result);
        $this->assertSame(1, $result[0]['index']);
        $this->assertSame(2, $result[1]['index']);
    }

    public function test_all_condition_fields_preserved_in_data_key(): void {
        $condition = ['type' => 'grade', 'id' => 42, 'min' => 30.5, 'max' => 90];
        $json = json_encode(['c' => [$condition], 'showc' => [true]]);

        $result = report_unlocker_parse_all_conditions($json);

        $this->assertSame($condition, $result[0]['data']);
    }

    public function test_profile_condition_with_sf_field(): void {
        $json = json_encode([
            'c' => [['type' => 'profile', 'sf' => 'email', 'op' => 'contains', 'v' => '@example.com']],
            'showc' => [true],
        ]);

        $result = report_unlocker_parse_all_conditions($json);

        $this->assertCount(1, $result);
        $this->assertSame('profile', $result[0]['type']);
        $this->assertSame('email', $result[0]['data']['sf']);
    }

    public function test_mixed_typed_and_nested_only_typed_returned(): void {
        $json = json_encode([
            'c' => [
                ['type' => 'group', 'id' => 1],
                ['op' => '|', 'c' => [['type' => 'date', 'd' => '<', 't' => 9999]]],
                ['type' => 'completion', 'cm' => 5, 'e' => 1],
            ],
            'showc' => [true, true, true],
        ]);

        $result = report_unlocker_parse_all_conditions($json);

        $this->assertCount(2, $result);
        $this->assertSame(0, $result[0]['index']);
        $this->assertSame('group', $result[0]['type']);
        $this->assertSame(2, $result[1]['index']);
        $this->assertSame('completion', $result[1]['type']);
    }

    public function test_showc_value_returned_per_condition(): void {
        $json = json_encode([
            'c'     => [
                ['type' => 'group', 'id' => 1],
                ['type' => 'date', 'd' => '>=', 't' => 1000],
            ],
            'showc' => [false, true],
        ]);

        $result = report_unlocker_parse_all_conditions($json);

        $this->assertCount(2, $result);
        $this->assertFalse($result[0]['showc']);
        $this->assertTrue($result[1]['showc']);
    }

    public function test_showc_defaults_to_true_when_missing(): void {
        $json = json_encode([
            'c' => [['type' => 'group', 'id' => 1]],
        ]);

        $result = report_unlocker_parse_all_conditions($json);

        $this->assertCount(1, $result);
        $this->assertTrue($result[0]['showc']);
    }

    public function test_showc_key_present_in_every_returned_entry(): void {
        $json = json_encode([
            'c'     => [
                ['type' => 'completion', 'cm' => 5, 'e' => 1],
                ['type' => 'grade', 'id' => 10],
                ['type' => 'date', 'd' => '>=', 't' => 0],
            ],
            'showc' => [true, false, true],
        ]);

        $result = report_unlocker_parse_all_conditions($json);

        foreach ($result as $cond) {
            $this->assertArrayHasKey('showc', $cond);
        }
        $this->assertSame([true, false, true], array_column($result, 'showc'));
    }
}
