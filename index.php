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
 * Unlocker report — main entry point.
 *
 * @package    report_unlocker
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/formslib.php');
require_once(__DIR__ . '/locallib.php');

use report_unlocker\ai\service as ai_service;

$id = required_param('id', PARAM_INT);

$course  = get_course($id);
$context = context_course::instance($id);

require_login($course);
require_capability('report/unlocker:view', $context);

$PAGE->set_url('/report/unlocker/index.php', ['id' => $id]);
$PAGE->set_context($context);
$PAGE->set_title(get_string('pluginname', 'report_unlocker'));
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('report');
$PAGE->requires->js_call_amd('report_unlocker/filters', 'init');

$aiavailable = has_capability('report/unlocker:editconditions', $context) && ai_service::has_ai();
if ($aiavailable) {
    $PAGE->requires->js_call_amd('report_unlocker/ai_chat', 'init', [
        $id,
        sesskey(),
        $CFG->wwwroot,
    ]);
}

$moduleentries  = report_unlocker_get_module_conditions($id);
$sectionentries = report_unlocker_get_section_conditions($id);
$groups         = report_unlocker_get_groups($id);
$groupings      = report_unlocker_get_groupings($id);
$gradeitems     = report_unlocker_get_grade_items($id);
$completioncms  = report_unlocker_get_cms_with_completion($id);
$profilefields  = report_unlocker_get_profile_fields();
$playerhuddata  = report_unlocker_get_playerhud_data($id);

$mform = new \report_unlocker\form\conditions_form(null, [
    'courseid'      => $id,
    'modules'       => $moduleentries,
    'sections'      => $sectionentries,
    'groups'        => $groups,
    'groupings'     => $groupings,
    'gradeitems'    => $gradeitems,
    'completioncms' => $completioncms,
    'profilefields' => $profilefields,
    'playerhuddata' => $playerhuddata,
]);

if ($fromform = $mform->get_data()) {
    require_capability('report/unlocker:editconditions', $context);

    $moduleupdates = [];
    foreach ($moduleentries as $mc) {
        $updates      = [];
        $removals     = [];
        $showcupdates = [];

        foreach ($mc['conditions'] as $cond) {
            $index      = $cond['index'];
            $type       = $cond['type'];
            $removename = 'mod_' . $mc['id'] . '_' . $index . '_remove';

            if (!empty($fromform->$removename)) {
                $removals[] = $index;
                continue;
            }

            switch ($type) {
                case 'date':
                    $fieldname = 'mod_' . $mc['id'] . '_' . $index;
                    if (property_exists($fromform, $fieldname)) {
                        $updates[$index] = ['t' => (int) $fromform->$fieldname];
                    }
                    break;

                case 'group':
                    $fieldname = 'mod_' . $mc['id'] . '_' . $index . '_group';
                    if (property_exists($fromform, $fieldname)) {
                        $groupid = (int) $fromform->$fieldname;
                        $updates[$index] = ['id' => $groupid > 0 ? $groupid : null];
                    }
                    break;

                case 'grouping':
                    $fieldname = 'mod_' . $mc['id'] . '_' . $index . '_grouping';
                    if (property_exists($fromform, $fieldname)) {
                        $updates[$index] = ['id' => (int) $fromform->$fieldname];
                    }
                    break;

                case 'grade':
                    $fid  = 'mod_' . $mc['id'] . '_' . $index . '_grade';
                    $fmin = 'mod_' . $mc['id'] . '_' . $index . '_grade_min';
                    $fmax = 'mod_' . $mc['id'] . '_' . $index . '_grade_max';
                    if (property_exists($fromform, $fid)) {
                        $gradeupdate = ['id' => (int) $fromform->$fid];
                        if (property_exists($fromform, $fmin)) {
                            $minraw = trim((string) $fromform->$fmin);
                            $gradeupdate['min'] = $minraw !== '' ? (float) $minraw : null;
                        }
                        if (property_exists($fromform, $fmax)) {
                            $maxraw = trim((string) $fromform->$fmax);
                            $gradeupdate['max'] = $maxraw !== '' ? (float) $maxraw : null;
                        }
                        $updates[$index] = $gradeupdate;
                    }
                    break;

                case 'completion':
                    $fcm = 'mod_' . $mc['id'] . '_' . $index . '_completion';
                    $fe  = 'mod_' . $mc['id'] . '_' . $index . '_completion_e';
                    if (property_exists($fromform, $fcm)) {
                        $updates[$index] = [
                            'cm' => (int) $fromform->$fcm,
                            'e'  => property_exists($fromform, $fe) ? (int) $fromform->$fe : 1,
                        ];
                    }
                    break;

                case 'profile':
                    $ffield = 'mod_' . $mc['id'] . '_' . $index . '_profile_field';
                    $fop    = 'mod_' . $mc['id'] . '_' . $index . '_profile_op';
                    $fval   = 'mod_' . $mc['id'] . '_' . $index . '_profile_val';
                    if (property_exists($fromform, $ffield) && property_exists($fromform, $fop)) {
                        $fieldval = (string) $fromform->$ffield;
                        $op       = (string) $fromform->$fop;
                        $validops = [
                            'contains', 'doesnotcontain', 'isequalto',
                            'startswith', 'endswith', 'isempty', 'isnotempty',
                        ];
                        if (
                            !in_array($op, $validops, true)
                            || !preg_match('/^(sf|cf):[a-z0-9_]+$/i', $fieldval)
                        ) {
                            break;
                        }
                        $profupdate = ['op' => $op];
                        if (str_starts_with($fieldval, 'sf:')) {
                            $profupdate['sf'] = substr($fieldval, 3);
                            $profupdate['cf'] = null;
                        } else {
                            $profupdate['cf'] = substr($fieldval, 3);
                            $profupdate['sf'] = null;
                        }
                        $emptyops = ['isempty', 'isnotempty'];
                        if (!in_array($op, $emptyops, true) && property_exists($fromform, $fval)) {
                            $profupdate['v'] = (string) $fromform->$fval;
                        } else {
                            $profupdate['v'] = null;
                        }
                        $updates[$index] = $profupdate;
                    }
                    break;

                case 'playerhud':
                    $phsubtype = $cond['data']['subtype'] ?? '';
                    switch ($phsubtype) {
                        case 'level':
                            $flevel = 'mod_' . $mc['id'] . '_' . $index . '_ph_level';
                            if (property_exists($fromform, $flevel)) {
                                $updates[$index] = ['levelval' => max(1, (int) $fromform->$flevel)];
                            }
                            break;

                        case 'item':
                            $fitemid = 'mod_' . $mc['id'] . '_' . $index . '_ph_itemid';
                            $fqty    = 'mod_' . $mc['id'] . '_' . $index . '_ph_itemqty';
                            $fop     = 'mod_' . $mc['id'] . '_' . $index . '_ph_itemop';
                            if (property_exists($fromform, $fitemid)) {
                                $itemops = ['>=', '>', '<', '='];
                                $op = property_exists($fromform, $fop) ? (string) $fromform->$fop : '>=';
                                if (!in_array($op, $itemops, true)) {
                                    $op = '>=';
                                }
                                $updates[$index] = [
                                    'itemid'  => (int) $fromform->$fitemid,
                                    'itemqty' => property_exists($fromform, $fqty)
                                        ? max(1, (int) $fromform->$fqty) : 1,
                                    'itemop'  => $op,
                                ];
                            }
                            break;

                        case 'class':
                            $fclassid = 'mod_' . $mc['id'] . '_' . $index . '_ph_classid';
                            if (property_exists($fromform, $fclassid)) {
                                $updates[$index] = ['classid' => (int) $fromform->$fclassid];
                            }
                            break;
                    }
                    break;
            }

            $showcname = 'mod_' . $mc['id'] . '_' . $index . '_showc';
            if (property_exists($fromform, $showcname)) {
                $showcupdates[$index] = (bool) $fromform->$showcname;
            }
        }

        $opname   = 'mod_' . $mc['id'] . '_op';
        $validops = ['&', '|', '!&', '!|'];
        $opvalue  = property_exists($fromform, $opname) ? (string) $fromform->$opname : null;
        if ($opvalue !== null && !in_array($opvalue, $validops, true)) {
            $opvalue = null;
        }

        $showname    = 'mod_' . $mc['id'] . '_show';
        $currentshow = (bool) ($mc['show'] ?? true);
        $submitshow  = property_exists($fromform, $showname) ? (bool) $fromform->$showname : $currentshow;
        $showvalue   = $submitshow !== $currentshow ? $submitshow : null;

        if (!empty($updates) || !empty($removals) || $opvalue !== null || !empty($showcupdates) || $showvalue !== null) {
            $moduleupdates[] = [
                'cmid'         => $mc['id'],
                'updates'      => $updates,
                'removals'     => $removals,
                'op'           => $opvalue,
                'showcupdates' => $showcupdates,
                'show'         => $showvalue,
            ];
        }
    }

    $sectionupdates = [];
    foreach ($sectionentries as $sc) {
        $updates      = [];
        $removals     = [];
        $showcupdates = [];

        foreach ($sc['conditions'] as $cond) {
            $index      = $cond['index'];
            $type       = $cond['type'];
            $removename = 'sec_' . $sc['id'] . '_' . $index . '_remove';

            if (!empty($fromform->$removename)) {
                $removals[] = $index;
                continue;
            }

            switch ($type) {
                case 'date':
                    $fieldname = 'sec_' . $sc['id'] . '_' . $index;
                    if (property_exists($fromform, $fieldname)) {
                        $updates[$index] = ['t' => (int) $fromform->$fieldname];
                    }
                    break;

                case 'group':
                    $fieldname = 'sec_' . $sc['id'] . '_' . $index . '_group';
                    if (property_exists($fromform, $fieldname)) {
                        $groupid = (int) $fromform->$fieldname;
                        $updates[$index] = ['id' => $groupid > 0 ? $groupid : null];
                    }
                    break;

                case 'grouping':
                    $fieldname = 'sec_' . $sc['id'] . '_' . $index . '_grouping';
                    if (property_exists($fromform, $fieldname)) {
                        $updates[$index] = ['id' => (int) $fromform->$fieldname];
                    }
                    break;

                case 'grade':
                    $fid  = 'sec_' . $sc['id'] . '_' . $index . '_grade';
                    $fmin = 'sec_' . $sc['id'] . '_' . $index . '_grade_min';
                    $fmax = 'sec_' . $sc['id'] . '_' . $index . '_grade_max';
                    if (property_exists($fromform, $fid)) {
                        $gradeupdate = ['id' => (int) $fromform->$fid];
                        if (property_exists($fromform, $fmin)) {
                            $minraw = trim((string) $fromform->$fmin);
                            $gradeupdate['min'] = $minraw !== '' ? (float) $minraw : null;
                        }
                        if (property_exists($fromform, $fmax)) {
                            $maxraw = trim((string) $fromform->$fmax);
                            $gradeupdate['max'] = $maxraw !== '' ? (float) $maxraw : null;
                        }
                        $updates[$index] = $gradeupdate;
                    }
                    break;

                case 'completion':
                    $fcm = 'sec_' . $sc['id'] . '_' . $index . '_completion';
                    $fe  = 'sec_' . $sc['id'] . '_' . $index . '_completion_e';
                    if (property_exists($fromform, $fcm)) {
                        $updates[$index] = [
                            'cm' => (int) $fromform->$fcm,
                            'e'  => property_exists($fromform, $fe) ? (int) $fromform->$fe : 1,
                        ];
                    }
                    break;

                case 'profile':
                    $ffield = 'sec_' . $sc['id'] . '_' . $index . '_profile_field';
                    $fop    = 'sec_' . $sc['id'] . '_' . $index . '_profile_op';
                    $fval   = 'sec_' . $sc['id'] . '_' . $index . '_profile_val';
                    if (property_exists($fromform, $ffield) && property_exists($fromform, $fop)) {
                        $fieldval = (string) $fromform->$ffield;
                        $op       = (string) $fromform->$fop;
                        $validops = [
                            'contains', 'doesnotcontain', 'isequalto',
                            'startswith', 'endswith', 'isempty', 'isnotempty',
                        ];
                        if (
                            !in_array($op, $validops, true)
                            || !preg_match('/^(sf|cf):[a-z0-9_]+$/i', $fieldval)
                        ) {
                            break;
                        }
                        $profupdate = ['op' => $op];
                        if (str_starts_with($fieldval, 'sf:')) {
                            $profupdate['sf'] = substr($fieldval, 3);
                            $profupdate['cf'] = null;
                        } else {
                            $profupdate['cf'] = substr($fieldval, 3);
                            $profupdate['sf'] = null;
                        }
                        $emptyops = ['isempty', 'isnotempty'];
                        if (!in_array($op, $emptyops, true) && property_exists($fromform, $fval)) {
                            $profupdate['v'] = (string) $fromform->$fval;
                        } else {
                            $profupdate['v'] = null;
                        }
                        $updates[$index] = $profupdate;
                    }
                    break;

                case 'playerhud':
                    $phsubtype = $cond['data']['subtype'] ?? '';
                    switch ($phsubtype) {
                        case 'level':
                            $flevel = 'sec_' . $sc['id'] . '_' . $index . '_ph_level';
                            if (property_exists($fromform, $flevel)) {
                                $updates[$index] = ['levelval' => max(1, (int) $fromform->$flevel)];
                            }
                            break;

                        case 'item':
                            $fitemid = 'sec_' . $sc['id'] . '_' . $index . '_ph_itemid';
                            $fqty    = 'sec_' . $sc['id'] . '_' . $index . '_ph_itemqty';
                            $fop     = 'sec_' . $sc['id'] . '_' . $index . '_ph_itemop';
                            if (property_exists($fromform, $fitemid)) {
                                $itemops = ['>=', '>', '<', '='];
                                $op = property_exists($fromform, $fop) ? (string) $fromform->$fop : '>=';
                                if (!in_array($op, $itemops, true)) {
                                    $op = '>=';
                                }
                                $updates[$index] = [
                                    'itemid'  => (int) $fromform->$fitemid,
                                    'itemqty' => property_exists($fromform, $fqty)
                                        ? max(1, (int) $fromform->$fqty) : 1,
                                    'itemop'  => $op,
                                ];
                            }
                            break;

                        case 'class':
                            $fclassid = 'sec_' . $sc['id'] . '_' . $index . '_ph_classid';
                            if (property_exists($fromform, $fclassid)) {
                                $updates[$index] = ['classid' => (int) $fromform->$fclassid];
                            }
                            break;
                    }
                    break;
            }

            $showcname = 'sec_' . $sc['id'] . '_' . $index . '_showc';
            if (property_exists($fromform, $showcname)) {
                $showcupdates[$index] = (bool) $fromform->$showcname;
            }
        }

        $opname   = 'sec_' . $sc['id'] . '_op';
        $validops = ['&', '|', '!&', '!|'];
        $opvalue  = property_exists($fromform, $opname) ? (string) $fromform->$opname : null;
        if ($opvalue !== null && !in_array($opvalue, $validops, true)) {
            $opvalue = null;
        }

        $showname    = 'sec_' . $sc['id'] . '_show';
        $currentshow = (bool) ($sc['show'] ?? true);
        $submitshow  = property_exists($fromform, $showname) ? (bool) $fromform->$showname : $currentshow;
        $showvalue   = $submitshow !== $currentshow ? $submitshow : null;

        if (!empty($updates) || !empty($removals) || $opvalue !== null || !empty($showcupdates) || $showvalue !== null) {
            $sectionupdates[] = [
                'sectionid'    => $sc['id'],
                'updates'      => $updates,
                'removals'     => $removals,
                'op'           => $opvalue,
                'showcupdates' => $showcupdates,
                'show'         => $showvalue,
            ];
        }
    }

    report_unlocker_save_module_conditions($id, $moduleupdates);
    report_unlocker_save_section_conditions($id, $sectionupdates);

    redirect(
        $PAGE->url,
        get_string('savedsuccessfully', 'report_unlocker'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

$presenttypes = [];
foreach (array_merge($moduleentries, $sectionentries) as $entry) {
    foreach ($entry['types'] as $type) {
        if (!isset($presenttypes[$type])) {
            $strkey = 'conditiontype_' . $type;
            $presenttypes[$type] = get_string_manager()->string_exists($strkey, 'report_unlocker')
                ? get_string($strkey, 'report_unlocker')
                : ucfirst($type);
        }
    }
}
asort($presenttypes);

$conditiontypes = [];
foreach ($presenttypes as $type => $label) {
    $conditiontypes[] = ['type' => $type, 'label' => $label];
}

$filterdata = [
    'sections'                      => report_unlocker_get_filter_sections($id),
    'conditiontypes'                => $conditiontypes,
    'str_allsections'               => get_string('allsections', 'report_unlocker'),
    'str_alltypes'                  => get_string('alltypes', 'report_unlocker'),
    'str_clearfilters'              => get_string('clearfilters', 'report_unlocker'),
    'str_filterbysection'           => get_string('filterbysection', 'report_unlocker'),
    'str_filterbytype'              => get_string('filterbytype', 'report_unlocker'),
    'str_nodateconditions_filtered' => get_string('nodateconditions_filtered', 'report_unlocker'),
    'str_removeallvisible'          => get_string('removeallvisible', 'report_unlocker'),
    'str_removeallvisibleconfirm'   => get_string('removeallvisibleconfirm', 'report_unlocker'),
    'str_search'                    => get_string('search', 'report_unlocker'),
    'str_searchplaceholder'         => get_string('searchplaceholder', 'report_unlocker'),
];

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'report_unlocker'));

if ($aiavailable) {
    echo html_writer::tag(
        'div',
        html_writer::tag(
            'button',
            html_writer::tag('i', '', ['class' => 'fa fa-robot me-2', 'aria-hidden' => 'true']) .
            get_string('ai_assistant', 'report_unlocker'),
            [
                'id'    => 'report-unlocker-ai-btn',
                'class' => 'btn btn-outline-primary mb-3',
                'type'  => 'button',
            ]
        ),
        ['class' => 'd-flex justify-content-end']
    );
    echo $OUTPUT->render_from_template('report_unlocker/ai_chat_modal', [
        'str_title'        => get_string('ai_modal_title', 'report_unlocker'),
        'str_close'        => get_string('close', 'report_unlocker'),
        'str_placeholder'  => get_string('ai_input_placeholder', 'report_unlocker'),
        'str_send'         => get_string('ai_send', 'report_unlocker'),
        'str_preview_title' => get_string('ai_preview_title', 'report_unlocker'),
        'str_activity'     => get_string('ai_col_activity', 'report_unlocker'),
        'str_section'      => get_string('section', 'report_unlocker'),
        'str_condtype'     => get_string('ai_col_condtype', 'report_unlocker'),
        'str_condition'    => get_string('ai_col_condition', 'report_unlocker'),
        'str_action'       => get_string('ai_col_action', 'report_unlocker'),
        'str_confirm'      => get_string('ai_confirm', 'report_unlocker'),
        'str_cancel'       => get_string('cancel', 'core'),
    ]);
}

if (empty($moduleentries) && empty($sectionentries)) {
    echo $OUTPUT->notification(get_string('nodateconditions', 'report_unlocker'), 'info');
} else {
    echo $OUTPUT->render_from_template('report_unlocker/filter_form', $filterdata);
    $mform->display();
}

echo $OUTPUT->footer();
