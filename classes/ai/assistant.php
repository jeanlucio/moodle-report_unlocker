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
 * AI assistant for bulk restriction management in report_unlocker.
 *
 * @package    report_unlocker
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_unlocker\ai;

use report_unlocker\local\conditions;

/**
 * Processes a natural-language teacher request, calls the AI provider,
 * and returns a structured preview of proposed condition changes.
 *
 * @package    report_unlocker
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assistant {
    /** @var int The course ID. */
    private int $courseid;

    /** @var array Module entries from conditions::get_module_conditions(). */
    private array $modules;

    /** @var array Section entries from conditions::get_section_conditions(). */
    private array $sections;

    /**
     * Initialises the assistant for the given course.
     *
     * @param int $courseid Course ID.
     */
    public function __construct(int $courseid) {
        $this->courseid = $courseid;
        $this->modules  = conditions::get_module_conditions($courseid);
        $this->sections = conditions::get_section_conditions($courseid);
    }

    /**
     * Processes the teacher's message and returns a preview or an error.
     *
     * @param string $message The teacher's natural-language request.
     * @return array With keys: success (bool), summary (string), preview (array), changes (array), error (string).
     */
    public function process(string $message): array {
        $context = $this->build_context();

        if (empty($context)) {
            return [
                'success'   => false,
                'errorcode' => 'ai_no_conditions',
                'summary'   => '',
                'preview'   => [],
                'changes'   => [],
            ];
        }

        $prompt = $this->build_prompt($message, $context);
        $coursename = get_course($this->courseid)->shortname;
        $description = get_string('ai_usage', 'report_unlocker', $coursename);
        $result = service::send($prompt, $description);

        if (!$result['success']) {
            return [
                'success'   => false,
                'errorcode' => 'ai_request_failed',
                'summary'   => '',
                'preview'   => [],
                'changes'   => [],
            ];
        }

        try {
            [$summary, $changes] = $this->parse_response($result['data']);
        } catch (\Throwable $e) {
            return [
                'success'   => false,
                'errorcode' => 'ai_parse_failed',
                'summary'   => '',
                'preview'   => [],
                'changes'   => [],
            ];
        }

        $preview = $this->build_preview($changes);

        return [
            'success'  => true,
            'summary'  => $summary,
            'preview'  => $preview,
            'changes'  => $changes,
            'error'    => '',
        ];
    }

    /**
     * Builds a compact JSON-serialisable context of all course restrictions.
     *
     * @return array
     */
    private function build_context(): array {
        $items = [];

        foreach ($this->modules as $mod) {
            $item = [
                'target'     => 'module',
                'id'         => $mod['id'],
                'name'       => $mod['name'],
                'section'    => $mod['sectionnum'],
                'conditions' => [],
            ];
            foreach ($mod['conditions'] as $cond) {
                $item['conditions'][] = $this->compact_condition($cond);
            }
            $items[] = $item;
        }

        foreach ($this->sections as $sec) {
            $item = [
                'target'     => 'section',
                'id'         => $sec['id'],
                'name'       => $sec['name'],
                'section'    => $sec['sectionnum'],
                'conditions' => [],
            ];
            foreach ($sec['conditions'] as $cond) {
                $item['conditions'][] = $this->compact_condition($cond);
            }
            $items[] = $item;
        }

        return $items;
    }

    /**
     * Returns a compact array representation of a single condition for the AI context.
     *
     * @param array $cond Condition entry from conditions::parse_all().
     * @return array
     */
    private function compact_condition(array $cond): array {
        $compact = [
            'index' => $cond['index'],
            'type'  => $cond['type'],
        ];

        $data = $cond['data'];

        switch ($cond['type']) {
            case 'date':
                $compact['direction'] = $data['d'] ?? 'from';
                $compact['timestamp'] = $data['t'] ?? 0;
                $compact['readable']  = userdate((int) ($data['t'] ?? 0), get_string('strftimedatetimeshort', 'langconfig'));
                break;

            case 'group':
                $compact['group_id'] = $data['id'] ?? 0;
                break;

            case 'grouping':
                $compact['grouping_id'] = $data['id'] ?? 0;
                break;

            case 'grade':
                $compact['grade_item_id'] = $data['id'] ?? 0;
                if (isset($data['min'])) {
                    $compact['min'] = $data['min'];
                }
                if (isset($data['max'])) {
                    $compact['max'] = $data['max'];
                }
                break;

            case 'completion':
                $compact['cm_id']  = $data['cm'] ?? 0;
                $compact['status'] = $data['e'] ?? 1;
                break;

            case 'profile':
                $compact['field']    = isset($data['sf']) ? 'sf:' . $data['sf'] : 'cf:' . ($data['cf'] ?? '');
                $compact['operator'] = $data['op'] ?? '';
                $compact['value']    = $data['v'] ?? '';
                break;

            case 'playerhud':
                $compact['subtype'] = $data['subtype'] ?? '';
                if (isset($data['levelval'])) {
                    $compact['levelval'] = $data['levelval'];
                }
                if (isset($data['itemid'])) {
                    $compact['item_id'] = $data['itemid'];
                    $compact['item_qty'] = $data['itemqty'] ?? 1;
                    $compact['item_op']  = $data['itemop'] ?? '>=';
                }
                if (isset($data['classid'])) {
                    $compact['class_id'] = $data['classid'];
                }
                break;

            case 'nested':
                $compact['child_count'] = count($data['c'] ?? []);
                break;
        }

        return $compact;
    }

    /**
     * Builds the full prompt to send to the AI.
     *
     * @param string $message Teacher's request.
     * @param array $context Compact context array.
     * @return string
     */
    private function build_prompt(string $message, array $context): string {
        $contextjson = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $outputformat = json_encode([
            'summary' => 'Short human-readable summary of what will change (in the same language as the teacher request)',
            'changes' => [
                ['target' => 'module or section', 'id' => 'integer id', 'condition_index' => 'integer', 'action' => 'delete'],
                [
                    'target' => 'module',
                    'id' => 45,
                    'condition_index' => 1,
                    'action' => 'update',
                    'updates' => ['datetime' => '2026-06-30 15:40'],
                ],
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $now = new \DateTime('now', \core_date::get_user_timezone_object());
        $nowline = 'Current date and time: ' . $now->format('Y-m-d H:i')
            . ' (timezone ' . \core_date::get_user_timezone() . ').'
            . ' Resolve every date the teacher mentions relative to this moment, in this timezone.';

        $rules = implode("\n", [
            '- Only propose changes to conditions that exist in the context.',
            '- Use "delete" to remove a condition entirely.',
            '- Use "update" with an "updates" object to change specific fields of a condition.',
            '- For date conditions, set "datetime" to the new local date and time as "YYYY-MM-DD HH:MM"'
                . ' (24-hour). Never output a Unix timestamp. Field "d" is "from" or "until".',
            '- For grade conditions: fields are "id" (grade item id), "min" (float or null), "max" (float or null).',
            '- For group conditions: field "id" is the group id.',
            '- For grouping conditions: field "id" is the grouping id.',
            '- For completion conditions: field "cm" is the course module id, field "e" is the status integer.',
            '- Do NOT propose changes to "nested" type conditions — they cannot be edited here.',
            '- If the teacher request is ambiguous, propose nothing and explain in "summary".',
            '- Reply ONLY with a valid JSON object. No markdown, no code fences, no extra text.',
        ]);

        return implode("\n\n", [
            'You are a Moodle course assistant helping a teacher manage activity access restrictions.',
            'Below is the full list of current restrictions in the course (JSON):',
            $contextjson,
            $nowline,
            'Teacher request: "' . $message . '"',
            'Reply ONLY with a valid JSON object in this exact format:',
            $outputformat,
            'Rules:',
            $rules,
        ]);
    }

    /**
     * Parses the AI text response into a summary string and a changes array.
     *
     * @param string $responsetext Raw text from the AI provider.
     * @return array [string $summary, array $changes]
     * @throws \moodle_exception If the response cannot be parsed.
     */
    private function parse_response(string $responsetext): array {
        $cleaned = preg_replace('/^\x60\x60\x60(?:json)?\s*/im', '', $responsetext);
        $cleaned = preg_replace('/\x60\x60\x60\s*$/m', '', $cleaned);
        $cleaned = trim($cleaned);

        $decoded = json_decode($cleaned, true);
        if ($decoded === null || !is_array($decoded)) {
            throw new \moodle_exception('ai_parse_failed', 'report_unlocker');
        }

        $summary = (string) ($decoded['summary'] ?? '');
        $changes = [];

        foreach (($decoded['changes'] ?? []) as $raw) {
            $target = (string) ($raw['target'] ?? '');
            $id     = (int) ($raw['id'] ?? 0);
            $index  = (int) ($raw['condition_index'] ?? 0);
            $action = (string) ($raw['action'] ?? '');

            if (!in_array($target, ['module', 'section'], true)) {
                continue;
            }
            if ($id <= 0) {
                continue;
            }
            if (!in_array($action, ['delete', 'update'], true)) {
                continue;
            }
            if ($action === 'update' && empty($raw['updates'])) {
                continue;
            }

            $change = [
                'target'          => $target,
                'id'              => $id,
                'condition_index' => $index,
                'action'          => $action,
            ];
            if ($action === 'update') {
                $updates = (array) $raw['updates'];
                // Convert the AI's human date into a Unix timestamp server-side, so the
                // model never has to compute an epoch. An unparseable date drops the change.
                if (isset($updates['datetime'])) {
                    $timestamp = $this->datetime_to_timestamp((string) $updates['datetime']);
                    unset($updates['datetime']);
                    if ($timestamp === null) {
                        continue;
                    }
                    $updates['t'] = $timestamp;
                }
                $change['updates'] = $updates;
            }
            $changes[] = $change;
        }

        return [$summary, $changes];
    }

    /**
     * Converts an "YYYY-MM-DD HH:MM" local date string into a Unix timestamp.
     *
     * The value is interpreted in the current user's timezone. Date-only values
     * ("YYYY-MM-DD") are accepted and treated as midnight. Anything that does not
     * match exactly returns null so a malformed AI date is never persisted.
     *
     * @param string $value The local date string produced by the AI.
     * @return int|null The Unix timestamp, or null when the value is invalid.
     */
    private function datetime_to_timestamp(string $value): ?int {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $timezone = \core_date::get_user_timezone_object();
        foreach (['Y-m-d H:i', 'Y-m-d H:i:s', 'Y-m-d'] as $format) {
            $datetime = \DateTime::createFromFormat('!' . $format, $value, $timezone);
            if ($datetime !== false && $datetime->format($format) === $value) {
                return $datetime->getTimestamp();
            }
        }
        return null;
    }

    /**
     * Builds the preview rows shown to the teacher before confirmation.
     *
     * Each row contains display information about what will change.
     *
     * @param array $changes Parsed changes array from parse_response().
     * @return array List of preview rows.
     */
    private function build_preview(array $changes): array {
        $modindex = [];
        foreach ($this->modules as $mod) {
            $modindex[$mod['id']] = $mod;
        }
        $secindex = [];
        foreach ($this->sections as $sec) {
            $secindex[$sec['id']] = $sec;
        }

        $modinfo = get_fast_modinfo($this->courseid);
        $course  = $modinfo->get_course();
        $sectionnames = [];
        foreach ($modinfo->get_section_info_all() as $section) {
            $sectionnames[(int) $section->section] = get_section_name($course, $section);
        }

        $rows = [];
        foreach ($changes as $change) {
            $target = $change['target'];
            $id     = $change['id'];
            $index  = $change['condition_index'];

            $entry = $target === 'module' ? ($modindex[$id] ?? null) : ($secindex[$id] ?? null);
            if ($entry === null) {
                continue;
            }

            $condentry = null;
            foreach ($entry['conditions'] as $c) {
                if ($c['index'] === $index) {
                    $condentry = $c;
                    break;
                }
            }
            if ($condentry === null) {
                continue;
            }

            $sectionnum  = $entry['sectionnum'];
            $sectionname = $sectionnames[$sectionnum]
                ?? get_string('section', 'report_unlocker', $sectionnum);

            $rows[] = [
                'target'          => $target,
                'id'              => $id,
                'condition_index' => $index,
                'action'          => $change['action'],
                'entry_name'      => $entry['name'],
                'section_num'     => $sectionnum,
                'section_name'    => $sectionname,
                'cond_type'       => $condentry['type'],
                'cond_summary'    => $this->describe_condition($condentry),
                'new_summary'     => $change['action'] === 'update'
                    ? $this->describe_updates($condentry, $change['updates'] ?? [])
                    : '',
                'updates'         => $change['updates'] ?? [],
            ];
        }

        return $rows;
    }

    /**
     * Returns a short human-readable description of a condition for the preview table.
     *
     * @param array $cond Condition entry.
     * @return string
     */
    private function describe_condition(array $cond): string {
        $data = $cond['data'];

        switch ($cond['type']) {
            case 'date':
                $dir = ($data['d'] ?? 'from') === 'from'
                    ? get_string('dateafter', 'report_unlocker')
                    : get_string('datebefore', 'report_unlocker');
                $date = userdate((int) ($data['t'] ?? 0), get_string('strftimedatetimeshort', 'langconfig'));
                return $dir . ': ' . $date;

            case 'group':
                return get_string('conditiontype_group', 'report_unlocker') . ' #' . ($data['id'] ?? '?');

            case 'grouping':
                return get_string('conditiontype_grouping', 'report_unlocker') . ' #' . ($data['id'] ?? '?');

            case 'grade':
                $desc = get_string('conditiontype_grade', 'report_unlocker') . ' #' . ($data['id'] ?? '?');
                if (isset($data['min'])) {
                    $desc .= ' ≥' . $data['min'] . '%';
                }
                if (isset($data['max'])) {
                    $desc .= ' ≤' . $data['max'] . '%';
                }
                return $desc;

            case 'completion':
                return get_string('conditiontype_completion', 'report_unlocker') . ' CM#' . ($data['cm'] ?? '?');

            case 'profile':
                $field = isset($data['sf']) ? $data['sf'] : ($data['cf'] ?? '?');
                return get_string('conditiontype_profile', 'report_unlocker') . ': ' . $field;

            case 'playerhud':
                return get_string('conditiontype_playerhud', 'report_unlocker')
                    . ' (' . ($data['subtype'] ?? '?') . ')';

            case 'nested':
                return get_string('conditiontype_nested', 'report_unlocker');

            default:
                return $cond['type'];
        }
    }

    /**
     * Returns a short human-readable description of the proposed new value for an update.
     *
     * Date conditions render the resulting date/time; other fields are listed as
     * "field=value" pairs so the teacher can verify the change before applying.
     *
     * @param array $cond The current condition entry.
     * @param array $updates The proposed updates (already normalised, dates as "t").
     * @return string
     */
    private function describe_updates(array $cond, array $updates): string {
        if (empty($updates)) {
            return '';
        }

        if ($cond['type'] === 'date' && isset($updates['t'])) {
            return userdate((int) $updates['t'], get_string('strftimedatetimeshort', 'langconfig'));
        }

        $bits = [];
        foreach ($updates as $field => $value) {
            if (is_bool($value)) {
                $value = $value ? '1' : '0';
            } else if (is_null($value)) {
                $value = '∅';
            }
            $bits[] = $field . '=' . $value;
        }
        return implode(', ', $bits);
    }
}
