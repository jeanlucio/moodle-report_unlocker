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
 * Tests for the report_unlocker Privacy API provider.
 *
 * @package    report_unlocker
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_unlocker\privacy;

use core_privacy\local\metadata\null_provider;
use core_privacy\tests\provider_testcase;

/**
 * Tests for report_unlocker\privacy\provider.
 *
 * @covers \report_unlocker\privacy\provider
 */
final class provider_test extends provider_testcase {
    public function test_provider_implements_null_provider_interface(): void {
        $this->assertInstanceOf(
            null_provider::class,
            new provider()
        );
    }

    public function test_get_reason_returns_non_empty_string_key(): void {
        $reason = provider::get_reason();
        $this->assertIsString($reason);
        $this->assertNotEmpty($reason);
    }

    public function test_get_reason_key_resolves_to_valid_string(): void {
        $string = get_string(provider::get_reason(), 'report_unlocker');
        $this->assertIsString($string);
        $this->assertNotEmpty($string);
    }
}
