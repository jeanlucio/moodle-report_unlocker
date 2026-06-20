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
 * Reads course availability conditions and the option data used by the report UI.
 *
 * @package    report_unlocker
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_unlocker\local;

/**
 * Collects availability conditions and the lookup data shown in the report filters.
 *
 * @package    report_unlocker
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class conditions {
    /**
     * Parses an availability JSON string and returns all recognized condition entries.
     * Nested restriction sets (objects without a 'type' key) are silently skipped.
     *
     * @param string|null $availability JSON string from course_modules or course_sections.
     * @return array List of conditions, each with keys: index, type, data.
     */
    public static function parse_all(?string $availability): array {
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
                if (isset($condition['op']) && is_array($condition['c'] ?? null)) {
                    $conditions[] = [
                        'index' => $index,
                        'type'  => 'nested',
                        'data'  => $condition,
                        'showc' => $data['showc'][$index] ?? true,
                    ];
                }
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
    public static function get_filter_sections(int $courseid): array {
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
    public static function get_groups(int $courseid): array {
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
    public static function get_groupings(int $courseid): array {
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
    public static function get_grade_items(int $courseid): array {
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
    public static function get_cms_with_completion(int $courseid): array {
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
    public static function get_profile_fields(): array {
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
    public static function get_playerhud_data(int $courseid): array {
        global $DB;

        $context = \context_course::instance($courseid);

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
    public static function get_module_conditions(int $courseid): array {
        $modinfo = get_fast_modinfo($courseid);
        $results = [];

        foreach ($modinfo->cms as $cm) {
            $conditions = self::parse_all($cm->availability);
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
    public static function get_section_conditions(int $courseid): array {
        global $DB;

        $sections = $DB->get_records(
            'course_sections',
            ['course' => $courseid],
            'section',
            'id, section, name, availability'
        );

        $results = [];
        foreach ($sections as $section) {
            $conditions = self::parse_all($section->availability);
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
}
