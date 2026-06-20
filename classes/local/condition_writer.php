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
 * Applies and persists changes to course availability conditions.
 *
 * @package    report_unlocker
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_unlocker\local;

/**
 * Writes batched availability condition changes back to modules and sections.
 *
 * @package    report_unlocker
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class condition_writer {
    /**
     * Applies field updates and removals to a raw availability JSON string.
     *
     * Returns the new JSON (or null if all conditions were removed) and a boolean
     * indicating whether anything actually changed.
     *
     * @param string|null $rawjson Original JSON.
     * @param array $updates Map of conditionindex => [field => value|null] (null = unset field).
     * @param array $removals List of condition indices to remove.
     * @param string|null $opchange New operator value ('&', '|', '!&', '!|') or null to keep existing.
     * @param array $showcupdates Map of conditionindex => bool for showc overrides.
     * @param bool|null $showchange New global show value (only for op '|' or '!&'), null to keep existing.
     * @return array [string|null $newjson, bool $changed].
     */
    public static function apply_changes(
        ?string $rawjson,
        array $updates,
        array $removals,
        ?string $opchange = null,
        array $showcupdates = [],
        ?bool $showchange = null
    ): array {
        if (empty($rawjson)) {
            return [null, false];
        }

        if (
            empty($updates) && empty($removals) &&
            $opchange === null && empty($showcupdates) && $showchange === null
        ) {
            return [$rawjson, false];
        }

        $availability = json_decode($rawjson, true);
        if (!is_array($availability) || !isset($availability['c'])) {
            return [$rawjson, false];
        }

        $newc     = [];
        $newshowc = [];
        foreach ($availability['c'] as $index => $cond) {
            if (in_array($index, $removals, true)) {
                continue;
            }
            if (isset($updates[$index])) {
                foreach ($updates[$index] as $field => $value) {
                    if ($value === null) {
                        unset($cond[$field]);
                    } else {
                        $cond[$field] = $value;
                    }
                }
            }
            $newc[]     = $cond;
            $newshowc[] = isset($showcupdates[$index])
                ? (bool) $showcupdates[$index]
                : ($availability['showc'][$index] ?? true);
        }

        if (empty($newc)) {
            return [null, true];
        }

        $finalop        = $opchange ?? ($availability['op'] ?? '&');
        $usesglobalshow = in_array($finalop, ['|', '!&'], true);

        if ($opchange !== null) {
            $availability['op'] = $opchange;
        }
        $availability['c'] = $newc;

        if ($usesglobalshow) {
            $availability['show'] = $showchange !== null ? $showchange : ($availability['show'] ?? true);
            unset($availability['showc']);
        } else {
            $availability['showc'] = $newshowc;
            unset($availability['show']);
        }

        $newjson = json_encode($availability);
        return [$newjson, $newjson !== $rawjson];
    }

    /**
     * Applies updates and removals to the conditions of a batch of course modules.
     *
     * Each entry in $moduleupdates must contain:
     *   - 'cmid'     => int
     *   - 'updates'  => [conditionindex => [field => value|null]]
     *   - 'removals' => [conditionindex, ...]
     *
     * Pre-loads all records in a single query. Cache is rebuilt only if anything changed.
     *
     * @param int $courseid Course ID used to validate ownership and rebuild cache.
     * @param array $moduleupdates Batch of module condition changes.
     * @return void
     */
    public static function save_module(int $courseid, array $moduleupdates): void {
        global $DB;

        if (empty($moduleupdates)) {
            return;
        }

        $cmids = array_column($moduleupdates, 'cmid');
        [$insql, $inparams] = $DB->get_in_or_equal($cmids, SQL_PARAMS_NAMED, 'cmid');
        $inparams['course'] = $courseid;

        $cms = $DB->get_records_sql(
            "SELECT id, availability FROM {course_modules} WHERE course = :course AND id $insql",
            $inparams
        );

        $anythingchanged = false;

        foreach ($moduleupdates as $update) {
            $cmid = (int) $update['cmid'];
            if (!isset($cms[$cmid])) {
                continue;
            }

            [$newjson, $changed] = self::apply_changes(
                $cms[$cmid]->availability,
                $update['updates'] ?? [],
                $update['removals'] ?? [],
                $update['op'] ?? null,
                $update['showcupdates'] ?? [],
                isset($update['show']) ? (bool) $update['show'] : null
            );

            if (!$changed) {
                continue;
            }

            $DB->update_record('course_modules', (object) [
                'id'           => $cmid,
                'availability' => $newjson,
            ]);
            $anythingchanged = true;
        }

        if ($anythingchanged) {
            rebuild_course_cache($courseid, true);
        }
    }

    /**
     * Applies updates and removals to the conditions of a batch of course sections.
     *
     * Each entry in $sectionupdates must contain:
     *   - 'sectionid' => int
     *   - 'updates'   => [conditionindex => [field => value|null]]
     *   - 'removals'  => [conditionindex, ...]
     *
     * Pre-loads all records in a single query. Cache is rebuilt only if anything changed.
     *
     * @param int $courseid Course ID used to validate ownership and rebuild cache.
     * @param array $sectionupdates Batch of section condition changes.
     * @return void
     */
    public static function save_section(int $courseid, array $sectionupdates): void {
        global $DB;

        if (empty($sectionupdates)) {
            return;
        }

        $sectionids = array_column($sectionupdates, 'sectionid');
        [$insql, $inparams] = $DB->get_in_or_equal($sectionids, SQL_PARAMS_NAMED, 'sid');
        $inparams['course'] = $courseid;

        $sections = $DB->get_records_sql(
            "SELECT id, availability FROM {course_sections} WHERE course = :course AND id $insql",
            $inparams
        );

        $anythingchanged = false;

        foreach ($sectionupdates as $update) {
            $sectionid = (int) $update['sectionid'];
            if (!isset($sections[$sectionid])) {
                continue;
            }

            [$newjson, $changed] = self::apply_changes(
                $sections[$sectionid]->availability,
                $update['updates'] ?? [],
                $update['removals'] ?? [],
                $update['op'] ?? null,
                $update['showcupdates'] ?? [],
                isset($update['show']) ? (bool) $update['show'] : null
            );

            if (!$changed) {
                continue;
            }

            $DB->update_record('course_sections', (object) [
                'id'           => $sectionid,
                'availability' => $newjson,
            ]);
            $anythingchanged = true;
        }

        if ($anythingchanged) {
            rebuild_course_cache($courseid, true);
        }
    }
}
