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
 * Internal functions for the Unlocker report.
 *
 * @package    report_unlocker
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Parses an availability JSON string and returns all recognized condition entries.
 * Nested restriction sets (objects without a 'type' key) are silently skipped.
 *
 * @param string|null $availability JSON string from course_modules or course_sections.
 * @return array List of conditions, each with keys: index, type, data.
 */
function report_unlocker_parse_all_conditions(?string $availability): array {
    if (empty($availability)) {
        return [];
    }

    $data = json_decode($availability, true);
    if (!is_array($data) || !is_array($data['c'] ?? null)) {
        return [];
    }

    $conditions = [];
    foreach ($data['c'] as $index => $condition) {
        if (!isset($condition['type'])) {
            continue;
        }
        $conditions[] = [
            'index' => $index,
            'type'  => $condition['type'],
            'data'  => $condition,
            'showc' => $data['showc'][$index] ?? true,
        ];
    }

    return $conditions;
}

/**
 * Returns all course sections for the section filter dropdown.
 *
 * @param int $courseid The course ID.
 * @return array List of sections with keys: num, name.
 */
function report_unlocker_get_filter_sections(int $courseid): array {
    $modinfo = get_fast_modinfo($courseid);
    $course  = $modinfo->get_course();
    $results = [];

    foreach ($modinfo->get_section_info_all() as $section) {
        $results[] = [
            'num'  => $section->section,
            'name' => get_section_name($course, $section),
        ];
    }

    return $results;
}

/**
 * Returns all groups available in the course, keyed by group ID.
 *
 * @param int $courseid The course ID.
 * @return array Map of group id => group name.
 */
function report_unlocker_get_groups(int $courseid): array {
    $groups = groups_get_all_groups($courseid);
    $result = [];
    foreach ($groups as $group) {
        $result[$group->id] = $group->name;
    }
    return $result;
}

/**
 * Returns all groupings available in the course, keyed by grouping ID.
 *
 * @param int $courseid The course ID.
 * @return array Map of grouping id => grouping name.
 */
function report_unlocker_get_groupings(int $courseid): array {
    $groupings = groups_get_all_groupings($courseid);
    $result = [];
    foreach ($groupings as $grouping) {
        $result[$grouping->id] = $grouping->name;
    }
    return $result;
}

/**
 * Returns all grade items available in the course, keyed by grade item ID.
 *
 * Skips secondary grade items (itemnumber > 0). For module-type items with no
 * explicit itemname, the activity name is resolved via modinfo.
 *
 * @param int $courseid The course ID.
 * @return array Map of grade_item id => display name.
 */
function report_unlocker_get_grade_items(int $courseid): array {
    global $DB;

    $sql = "SELECT gi.id, gi.itemtype, gi.itemname, gi.itemmodule,
                   gi.iteminstance, gi.itemnumber,
                   gc.fullname AS catname
              FROM {grade_items} gi
         LEFT JOIN {grade_categories} gc
                ON gi.itemtype = 'category' AND gc.id = gi.iteminstance
             WHERE gi.courseid = :courseid
               AND gi.itemnumber = 0
          ORDER BY gi.sortorder";

    $items   = $DB->get_records_sql($sql, ['courseid' => $courseid]);
    $modinfo = get_fast_modinfo($courseid);
    $result  = [];

    foreach ($items as $item) {
        switch ($item->itemtype) {
            case 'course':
                $name = get_string('gradetotalcourse', 'report_unlocker');
                break;

            case 'category':
                $name = $item->catname
                    ?: get_string('gradecategory', 'report_unlocker') . ' #' . $item->id;
                break;

            case 'mod':
                if (!empty($item->itemname)) {
                    $name = $item->itemname;
                } else {
                    // Resolve the CM whose instance matches this grade item.
                    $name = null;
                    foreach ($modinfo->cms as $cm) {
                        if ($cm->modname === $item->itemmodule && (int) $cm->instance === (int) $item->iteminstance) {
                            $name = $cm->name;
                            break;
                        }
                    }
                    $name = $name ?? (ucfirst((string) $item->itemmodule) . ' #' . $item->iteminstance);
                }
                break;

            default:
                $name = $item->itemname ?: null;
                break;
        }

        if ($name !== null) {
            $result[$item->id] = $name;
        }
    }

    return $result;
}

/**
 * Returns all course modules that have completion tracking enabled, keyed by CM ID.
 *
 * @param int $courseid The course ID.
 * @return array Map of cm id => activity name.
 */
function report_unlocker_get_cms_with_completion(int $courseid): array {
    $modinfo = get_fast_modinfo($courseid);
    $result  = [];

    foreach ($modinfo->cms as $cm) {
        if ((int) $cm->completion === 0) {
            continue;
        }
        if (!empty($cm->deletioninprogress)) {
            continue;
        }
        $result[$cm->id] = $cm->name;
    }

    return $result;
}

/**
 * Returns all profile fields (standard + custom) available for profile conditions.
 *
 * The array keys use a type-prefix format ('sf:fieldname' for standard user
 * fields, 'cf:shortname' for custom profile fields) so the form and save logic
 * can determine which JSON key (sf vs cf) to write without a separate field type
 * selector.  Returns a nested array suitable for Moodle select optgroups.
 *
 * @return array Nested array: [optgroup_label => [value => label]].
 */
function report_unlocker_get_profile_fields(): array {
    global $DB;

    // Source the standard field list from the core availability_profile
    // condition so the report offers exactly the fields core can restrict on
    // (and uses the same display names). Avoids offering unsupported fields or
    // missing supported ones such as firstname/lastname.
    $stdfields = [];
    foreach (\availability_profile\condition::get_standard_profile_fields() as $shortname => $label) {
        $stdfields['sf:' . $shortname] = $label;
    }

    $customrows = $DB->get_records('user_info_field', null, 'sortorder, name', 'id, shortname, name');
    $cffields   = [];
    foreach ($customrows as $cf) {
        $cffields['cf:' . $cf->shortname] = $cf->name;
    }

    $stdfieldslabel  = get_string('profilestandardfields', 'report_unlocker');
    $customfieldslabel = get_string('profilecustomfields', 'report_unlocker');

    if (!empty($cffields)) {
        return [
            $stdfieldslabel    => $stdfields,
            $customfieldslabel => $cffields,
        ];
    }

    return [$stdfieldslabel => $stdfields];
}

/**
 * Returns items and classes from the PlayerHUD block instance linked to the given course.
 *
 * Locates the first PlayerHUD block whose parent context is the course context.
 * If no block is found, both lists are empty. Only enabled items are included.
 *
 * @param int $courseid The course ID.
 * @return array Associative array with keys 'items' ([id => name]) and 'classes' ([id => name]).
 */
function report_unlocker_get_playerhud_data(int $courseid): array {
    global $DB;

    $context = context_course::instance($courseid);

    $sql = "SELECT bi.id
              FROM {block_instances} bi
             WHERE bi.blockname = 'playerhud'
               AND bi.parentcontextid = :ctxid";

    $block = $DB->get_record_sql($sql, ['ctxid' => $context->id], IGNORE_MULTIPLE);

    if (!$block) {
        return ['items' => [], 'classes' => []];
    }

    $blockid = (int) $block->id;

    $itemrows = $DB->get_records(
        'block_playerhud_items',
        ['blockinstanceid' => $blockid, 'enabled' => 1],
        'name',
        'id, name'
    );
    $items = [];
    foreach ($itemrows as $item) {
        $items[$item->id] = $item->name;
    }

    $classrows = $DB->get_records(
        'block_playerhud_classes',
        ['blockinstanceid' => $blockid],
        'name',
        'id, name'
    );
    $classes = [];
    foreach ($classrows as $cls) {
        $classes[$cls->id] = $cls->name;
    }

    return ['items' => $items, 'classes' => $classes];
}

/**
 * Returns all course modules that have at least one access condition.
 *
 * @param int $courseid The course ID.
 * @return array List of module data, each with keys: type, id, name, modname, sectionnum, conditions, types.
 */
function report_unlocker_get_module_conditions(int $courseid): array {
    $modinfo = get_fast_modinfo($courseid);
    $results = [];

    foreach ($modinfo->cms as $cm) {
        $conditions = report_unlocker_parse_all_conditions($cm->availability);
        if (empty($conditions)) {
            continue;
        }
        $availdata = json_decode($cm->availability ?? '', true);
        $results[] = [
            'type'       => 'module',
            'id'         => (int) $cm->id,
            'name'       => $cm->name,
            'modname'    => $cm->modname,
            'sectionnum' => (int) $cm->sectionnum,
            'op'         => $availdata['op'] ?? '&',
            'show'       => $availdata['show'] ?? true,
            'conditions' => $conditions,
            'types'      => array_values(array_unique(array_column($conditions, 'type'))),
        ];
    }

    return $results;
}

/**
 * Returns all course sections that have at least one access condition.
 *
 * @param int $courseid The course ID.
 * @return array List of section data, each with keys: type, id, name, sectionnum, conditions, types.
 */
function report_unlocker_get_section_conditions(int $courseid): array {
    global $DB;

    $sections = $DB->get_records(
        'course_sections',
        ['course' => $courseid],
        'section',
        'id, section, name, availability'
    );

    $results = [];
    foreach ($sections as $section) {
        $conditions = report_unlocker_parse_all_conditions($section->availability);
        if (empty($conditions)) {
            continue;
        }
        $availdata   = json_decode($section->availability ?? '', true);
        $sectionname = $section->name
            ?: get_string('section', 'report_unlocker', $section->section);
        $results[] = [
            'type'       => 'section',
            'id'         => (int) $section->id,
            'name'       => $sectionname,
            'sectionnum' => (int) $section->section,
            'op'         => $availdata['op'] ?? '&',
            'show'       => $availdata['show'] ?? true,
            'conditions' => $conditions,
            'types'      => array_values(array_unique(array_column($conditions, 'type'))),
        ];
    }

    return $results;
}

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
function report_unlocker_apply_condition_changes(
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
function report_unlocker_save_module_conditions(int $courseid, array $moduleupdates): void {
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

        [$newjson, $changed] = report_unlocker_apply_condition_changes(
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
function report_unlocker_save_section_conditions(int $courseid, array $sectionupdates): void {
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

        [$newjson, $changed] = report_unlocker_apply_condition_changes(
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
