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
 * Form for bulk editing of access conditions.
 *
 * @package    report_unlocker
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_unlocker\form;

use html_writer;
use moodleform;

/**
 * Renders edit controls and a remove checkbox for every access condition in the course.
 *
 * Each activity/section entry is wrapped in a div with data-section, data-name
 * and data-types attributes so the client-side AMD filter can show/hide entries
 * without server round-trips.
 */
class conditions_form extends moodleform {
    /**
     * Builds the form elements dynamically from the module and section lists
     * passed in via $customdata.
     *
     * @return void
     */
    protected function definition(): void {
        $mform = $this->_form;

        $courseid       = (int) $this->_customdata['courseid'];
        $moduleentries  = $this->_customdata['modules'];
        $sectionentries = $this->_customdata['sections'];
        $groups         = $this->_customdata['groups'];
        $groupings      = $this->_customdata['groupings'];
        $gradeitems     = $this->_customdata['gradeitems'];
        $completioncms  = $this->_customdata['completioncms'];
        $profilefields  = $this->_customdata['profilefields'];
        $playerhuddata  = $this->_customdata['playerhuddata'];

        $groupoptions    = [0 => get_string('anygroup', 'report_unlocker')] + $groups;
        $groupingoptions = $groupings;

        $mform->addElement('hidden', 'id', $courseid);
        $mform->setType('id', PARAM_INT);

        if (!empty($moduleentries)) {
            $mform->addElement('header', 'modules_header', get_string('heading_modules', 'report_unlocker'));
            $mform->setExpanded('modules_header');

            foreach ($moduleentries as $mc) {
                $mform->addElement('html', html_writer::start_tag('div', [
                    'class'        => 'report-unlocker-entry',
                    'data-section' => (string) $mc['sectionnum'],
                    'data-name'    => strtolower(strip_tags(format_string($mc['name']))),
                    'data-types'   => implode(',', $mc['types']),
                ]));
                $mform->addElement(
                    'html',
                    html_writer::tag('h5', format_string($mc['name']), ['class' => 'report-unlocker-entry-title'])
                );

                foreach ($mc['conditions'] as $cond) {
                    $this->render_condition(
                        'mod_' . $mc['id'],
                        $cond,
                        $groupoptions,
                        $groupingoptions,
                        $gradeitems,
                        $completioncms,
                        $profilefields,
                        $playerhuddata
                    );
                }

                $mform->addElement('html', html_writer::end_tag('div'));
            }
        }

        if (!empty($sectionentries)) {
            $mform->addElement('header', 'sections_header', get_string('heading_sections', 'report_unlocker'));
            $mform->setExpanded('sections_header');

            foreach ($sectionentries as $sc) {
                $mform->addElement('html', html_writer::start_tag('div', [
                    'class'        => 'report-unlocker-entry',
                    'data-section' => (string) $sc['sectionnum'],
                    'data-name'    => strtolower(strip_tags(format_string($sc['name']))),
                    'data-types'   => implode(',', $sc['types']),
                ]));
                $mform->addElement(
                    'html',
                    html_writer::tag('h5', format_string($sc['name']), ['class' => 'report-unlocker-entry-title'])
                );

                foreach ($sc['conditions'] as $cond) {
                    $this->render_condition(
                        'sec_' . $sc['id'],
                        $cond,
                        $groupoptions,
                        $groupingoptions,
                        $gradeitems,
                        $completioncms,
                        $profilefields,
                        $playerhuddata
                    );
                }

                $mform->addElement('html', html_writer::end_tag('div'));
            }
        }

        $this->add_action_buttons(false, get_string('saveall', 'report_unlocker'));
    }

    /**
     * Renders a single condition as one or more addGroup() rows with an inline remove checkbox.
     *
     * Using addGroup() with $appendName = false keeps each element's original name,
     * so the existing index.php save logic reads $fromform->fieldname correctly.
     *
     * @param string $prefix Element name prefix: 'mod_{cmid}' or 'sec_{sectionid}'.
     * @param array $cond Condition entry with keys: index, type, data.
     * @param array $groupoptions Select options for group conditions (id => name).
     * @param array $groupingoptions Select options for grouping conditions (id => name).
     * @param array $gradeitems Grade item options (id => name).
     * @param array $completioncms Course module options with completion tracking (id => name).
     * @param array $profilefields Profile field options (nested: optgroup => [value => label]).
     * @param array $playerhuddata PlayerHUD items and classes (['items' => [...], 'classes' => [...]]).
     * @return void
     */
    private function render_condition(
        string $prefix,
        array $cond,
        array $groupoptions,
        array $groupingoptions,
        array $gradeitems,
        array $completioncms,
        array $profilefields,
        array $playerhuddata
    ): void {
        $mform      = $this->_form;
        $index      = $cond['index'];
        $type       = $cond['type'];
        $data       = $cond['data'];
        $removename = $prefix . '_' . $index . '_remove';

        $removeelem = $mform->createElement(
            'advcheckbox',
            $removename,
            '',
            get_string('removecondition', 'report_unlocker')
        );

        $mform->addElement('html', html_writer::start_tag('div', [
            'class'         => 'report-unlocker-condition',
            'data-condtype' => $type,
        ]));

        switch ($type) {
            case 'date':
                $fieldname = $prefix . '_' . $index;
                $label     = ($data['d'] ?? '>=') === '>='
                    ? get_string('dateafter', 'report_unlocker')
                    : get_string('datebefore', 'report_unlocker');
                $dtelem    = $mform->createElement('date_time_selector', $fieldname, '', ['optional' => false]);
                $mform->addGroup([$dtelem, $removeelem], 'grp_' . $fieldname, $label, ['&nbsp;&nbsp;'], false);
                $mform->setDefault($fieldname, $data['t'] ?? 0);
                break;

            case 'group':
                $fieldname  = $prefix . '_' . $index . '_group';
                $selectelem = $mform->createElement('select', $fieldname, '', $groupoptions);
                $mform->addGroup(
                    [$selectelem, $removeelem],
                    'grp_' . $fieldname,
                    get_string('conditiontype_group', 'report_unlocker'),
                    ['&nbsp;&nbsp;'],
                    false
                );
                $mform->setType($fieldname, PARAM_INT);
                $mform->setDefault($fieldname, $data['id'] ?? 0);
                break;

            case 'grouping':
                $fieldname  = $prefix . '_' . $index . '_grouping';
                $selectelem = $mform->createElement('select', $fieldname, '', $groupingoptions);
                $mform->addGroup(
                    [$selectelem, $removeelem],
                    'grp_' . $fieldname,
                    get_string('conditiontype_grouping', 'report_unlocker'),
                    ['&nbsp;&nbsp;'],
                    false
                );
                $mform->setType($fieldname, PARAM_INT);
                $mform->setDefault($fieldname, $data['id'] ?? 0);
                break;

            case 'grade':
                $this->render_grade_condition($prefix, $index, $data, $gradeitems, $removeelem);
                break;

            case 'completion':
                $this->render_completion_condition($prefix, $index, $data, $completioncms, $removeelem);
                break;

            case 'profile':
                $this->render_profile_condition($prefix, $index, $data, $profilefields, $removeelem);
                break;

            case 'playerhud':
                $this->render_playerhud_condition($prefix, $index, $data, $playerhuddata, $removeelem);
                break;

            default:
                $strmanager = get_string_manager();
                $typekey    = 'conditiontype_' . $type;
                $typelabel  = $strmanager->string_exists($typekey, 'report_unlocker')
                    ? get_string($typekey, 'report_unlocker')
                    : get_string('conditiontype_unknown', 'report_unlocker') . ' (' . s($type) . ')';
                $staticelem = $mform->createElement(
                    'static',
                    'readonly_' . $prefix . '_' . $index,
                    '',
                    get_string('conditionreadonly', 'report_unlocker')
                );
                $mform->addGroup(
                    [$staticelem, $removeelem],
                    'grp_readonly_' . $prefix . '_' . $index,
                    $typelabel,
                    ['&nbsp;&nbsp;'],
                    false
                );
                break;
        }

        $mform->setDefault($removename, 0);
        $mform->setType($removename, PARAM_INT);

        $mform->addElement('html', html_writer::end_tag('div'));
    }

    /**
     * Renders the editable fields for a grade condition.
     *
     * Row 1: grade item select + remove checkbox.
     * Row 2: optional minimum % and maximum % text inputs.
     *
     * @param string $prefix Element name prefix.
     * @param int $index Condition index within the availability array.
     * @param array $data Raw condition data from the availability JSON.
     * @param array $gradeitems Grade item options (id => name).
     * @param object $removeelem The pre-created advcheckbox element for removal.
     * @return void
     */
    private function render_grade_condition(
        string $prefix,
        int $index,
        array $data,
        array $gradeitems,
        object $removeelem
    ): void {
        $mform    = $this->_form;
        $fnameid  = $prefix . '_' . $index . '_grade';
        $fnamemin = $prefix . '_' . $index . '_grade_min';
        $fnamemax = $prefix . '_' . $index . '_grade_max';

        $gradeopts  = empty($gradeitems)
            ? [0 => get_string('nogradeitems', 'report_unlocker')]
            : $gradeitems;
        $selectelem = $mform->createElement('select', $fnameid, '', $gradeopts);

        $mform->addGroup(
            [$selectelem, $removeelem],
            'grp_' . $fnameid,
            get_string('conditiontype_grade', 'report_unlocker'),
            ['&nbsp;&nbsp;'],
            false
        );
        $mform->setType($fnameid, PARAM_INT);
        $mform->setDefault($fnameid, $data['id'] ?? 0);

        $minlabel = $mform->createElement(
            'static',
            'lbl_gmin_' . $prefix . '_' . $index,
            '',
            get_string('grademin', 'report_unlocker') . ':&nbsp;'
        );
        $minelem  = $mform->createElement('text', $fnamemin, '', ['size' => '5']);
        $maxlabel = $mform->createElement(
            'static',
            'lbl_gmax_' . $prefix . '_' . $index,
            '',
            '&nbsp;&nbsp;' . get_string('grademax', 'report_unlocker') . ':&nbsp;'
        );
        $maxelem  = $mform->createElement('text', $fnamemax, '', ['size' => '5']);

        $mform->addGroup(
            [$minlabel, $minelem, $maxlabel, $maxelem],
            'grp_' . $fnamemin,
            '',
            ['', '', ''],
            false
        );
        $mform->setType($fnamemin, PARAM_TEXT);
        $mform->setDefault($fnamemin, isset($data['min']) ? (string) $data['min'] : '');
        $mform->setType($fnamemax, PARAM_TEXT);
        $mform->setDefault($fnamemax, isset($data['max']) ? (string) $data['max'] : '');
    }

    /**
     * Renders the editable fields for a completion condition.
     *
     * Single row: activity select + expected state select + remove checkbox.
     *
     * @param string $prefix Element name prefix.
     * @param int $index Condition index within the availability array.
     * @param array $data Raw condition data from the availability JSON.
     * @param array $completioncms Course module options (id => name).
     * @param object $removeelem The pre-created advcheckbox element for removal.
     * @return void
     */
    private function render_completion_condition(
        string $prefix,
        int $index,
        array $data,
        array $completioncms,
        object $removeelem
    ): void {
        $mform   = $this->_form;
        $fnamecm = $prefix . '_' . $index . '_completion';
        $fnamee  = $prefix . '_' . $index . '_completion_e';

        $stateoptions = [
            0 => get_string('completionstate_incomplete', 'report_unlocker'),
            1 => get_string('completionstate_complete', 'report_unlocker'),
            2 => get_string('completionstate_complete_pass', 'report_unlocker'),
            3 => get_string('completionstate_complete_fail', 'report_unlocker'),
        ];

        $cmopts = empty($completioncms)
            ? [0 => get_string('nocompletionactivities', 'report_unlocker')]
            : $completioncms;

        // Add orphan entry if the referenced CM no longer has completion tracking.
        if (!empty($data['cm']) && !isset($cmopts[$data['cm']])) {
            $cmopts[$data['cm']] = get_string('activitynotfound', 'report_unlocker', $data['cm']);
        }

        $cmselect    = $mform->createElement('select', $fnamecm, '', $cmopts);
        $statelabel  = $mform->createElement(
            'static',
            'lbl_cst_' . $prefix . '_' . $index,
            '',
            '&nbsp;&nbsp;' . get_string('completionstate', 'report_unlocker') . ':&nbsp;'
        );
        $stateselect = $mform->createElement('select', $fnamee, '', $stateoptions);

        $mform->addGroup(
            [$cmselect, $statelabel, $stateselect, $removeelem],
            'grp_' . $fnamecm,
            get_string('conditiontype_completion', 'report_unlocker'),
            ['', '', '&nbsp;&nbsp;'],
            false
        );
        $mform->setType($fnamecm, PARAM_INT);
        $mform->setDefault($fnamecm, $data['cm'] ?? 0);
        $mform->setType($fnamee, PARAM_INT);
        $mform->setDefault($fnamee, $data['e'] ?? 1);
    }

    /**
     * Renders the editable fields for a user-profile condition.
     *
     * Row 1: field select + operator select + remove checkbox.
     * Row 2: value text input, hidden when operator is 'isempty' or 'isnotempty'.
     *
     * The operator select carries the CSS class 'report-unlocker-profile-op' so
     * the profile_field AMD module can toggle row 2 on change.
     *
     * @param string $prefix Element name prefix.
     * @param int $index Condition index within the availability array.
     * @param array $data Raw condition data from the availability JSON.
     * @param array $profilefields Nested options for the field select (optgroups).
     * @param object $removeelem The pre-created advcheckbox element for removal.
     * @return void
     */
    private function render_profile_condition(
        string $prefix,
        int $index,
        array $data,
        array $profilefields,
        object $removeelem
    ): void {
        $mform       = $this->_form;
        $fnamefield  = $prefix . '_' . $index . '_profile_field';
        $fnameop     = $prefix . '_' . $index . '_profile_op';
        $fnameval    = $prefix . '_' . $index . '_profile_val';

        $operators = [
            'contains'       => get_string('op_contains', 'report_unlocker'),
            'doesnotcontain' => get_string('op_doesnotcontain', 'report_unlocker'),
            'isequalto'      => get_string('op_isequalto', 'report_unlocker'),
            'startswith'     => get_string('op_startswith', 'report_unlocker'),
            'endswith'       => get_string('op_endswith', 'report_unlocker'),
            'isempty'        => get_string('op_isempty', 'report_unlocker'),
            'isnotempty'     => get_string('op_isnotempty', 'report_unlocker'),
        ];

        $currentfield = !empty($data['sf']) ? 'sf:' . $data['sf'] : (!empty($data['cf']) ? 'cf:' . $data['cf'] : '');
        $currentop    = $data['op'] ?? 'contains';

        $fieldselect = $mform->createElement('select', $fnamefield, '', $profilefields);
        $oplabel     = $mform->createElement(
            'static',
            'lbl_op_' . $prefix . '_' . $index,
            '',
            '&nbsp;&nbsp;'
        );
        $opselect = $mform->createElement(
            'select',
            $fnameop,
            '',
            $operators,
            ['class' => 'report-unlocker-profile-op']
        );

        $mform->addGroup(
            [$fieldselect, $oplabel, $opselect, $removeelem],
            'grp_' . $fnamefield,
            get_string('conditiontype_profile', 'report_unlocker'),
            ['', '', '&nbsp;&nbsp;'],
            false
        );
        $mform->setType($fnamefield, PARAM_TEXT);
        $mform->setDefault($fnamefield, $currentfield);
        $mform->setType($fnameop, PARAM_ALPHA);
        $mform->setDefault($fnameop, $currentop);

        $isemptyop  = in_array($currentop, ['isempty', 'isnotempty'], true);
        $valrowattrs = ['class' => 'report-unlocker-profile-valrow'];
        if ($isemptyop) {
            $valrowattrs['hidden'] = 'hidden';
        }

        $mform->addElement('html', html_writer::start_tag('div', $valrowattrs));
        $valelem = $mform->createElement('text', $fnameval, '', ['size' => '30']);
        $mform->addGroup(
            [$valelem],
            'grp_' . $fnameval,
            get_string('profilevalue', 'report_unlocker'),
            [],
            false
        );
        $mform->setType($fnameval, PARAM_TEXT);
        $mform->setDefault($fnameval, $data['v'] ?? '');
        $mform->addElement('html', html_writer::end_tag('div'));
    }

    /**
     * Renders the editable fields for a PlayerHUD condition.
     *
     * Dispatches by subtype:
     *   - level: numeric input for the minimum level.
     *   - item: item select + quantity input + operator select.
     *   - class: RPG class select.
     *   - gamification: read-only label (no editable fields).
     *
     * The subtype itself is never editable and is read from the original JSON.
     *
     * @param string $prefix Element name prefix.
     * @param int $index Condition index within the availability array.
     * @param array $data Raw condition data from the availability JSON.
     * @param array $playerhuddata Items and classes: ['items' => [id => name], 'classes' => [id => name]].
     * @param object $removeelem The pre-created advcheckbox element for removal.
     * @return void
     */
    private function render_playerhud_condition(
        string $prefix,
        int $index,
        array $data,
        array $playerhuddata,
        object $removeelem
    ): void {
        $mform   = $this->_form;
        $subtype = $data['subtype'] ?? '';
        $items   = $playerhuddata['items'];
        $classes = $playerhuddata['classes'];

        switch ($subtype) {
            case 'level':
                $fnamelevel = $prefix . '_' . $index . '_ph_level';
                $levelelem  = $mform->createElement('text', $fnamelevel, '', ['size' => '5']);
                $mform->addGroup(
                    [$levelelem, $removeelem],
                    'grp_' . $fnamelevel,
                    get_string('playerhudlevel', 'report_unlocker'),
                    ['&nbsp;&nbsp;'],
                    false
                );
                $mform->setType($fnamelevel, PARAM_INT);
                $mform->setDefault($fnamelevel, $data['levelval'] ?? 1);
                break;

            case 'item':
                $fnameitemid = $prefix . '_' . $index . '_ph_itemid';
                $fnameqty    = $prefix . '_' . $index . '_ph_itemqty';
                $fnameop     = $prefix . '_' . $index . '_ph_itemop';

                $itemopts = empty($items)
                    ? [0 => get_string('playerhudnoitems', 'report_unlocker')]
                    : $items;

                if (!empty($data['itemid']) && !isset($itemopts[$data['itemid']])) {
                    $itemopts[$data['itemid']] = get_string(
                        'activitynotfound',
                        'report_unlocker',
                        $data['itemid']
                    );
                }

                $itopts = [
                    '>=' => get_string('itemop_gte', 'report_unlocker'),
                    '>'  => get_string('itemop_gt', 'report_unlocker'),
                    '<'  => get_string('itemop_lt', 'report_unlocker'),
                    '='  => get_string('itemop_eq', 'report_unlocker'),
                ];

                $itemselect = $mform->createElement('select', $fnameitemid, '', $itemopts);
                $qtylabel   = $mform->createElement(
                    'static',
                    'lbl_qty_' . $prefix . '_' . $index,
                    '',
                    '&nbsp;&nbsp;' . get_string('playerhuditemqty', 'report_unlocker') . ':&nbsp;'
                );
                $qtyelem    = $mform->createElement('text', $fnameqty, '', ['size' => '4']);
                $opselect   = $mform->createElement('select', $fnameop, '', $itopts);

                $mform->addGroup(
                    [$itemselect, $qtylabel, $qtyelem, $opselect, $removeelem],
                    'grp_' . $fnameitemid,
                    get_string('playerhuditem', 'report_unlocker'),
                    ['', '', '&nbsp;&nbsp;', '&nbsp;&nbsp;'],
                    false
                );
                $mform->setType($fnameitemid, PARAM_INT);
                $mform->setDefault($fnameitemid, $data['itemid'] ?? 0);
                $mform->setType($fnameqty, PARAM_INT);
                $mform->setDefault($fnameqty, $data['itemqty'] ?? 1);
                $mform->setType($fnameop, PARAM_RAW);
                $mform->setDefault($fnameop, $data['itemop'] ?? '>=');
                break;

            case 'class':
                $fnameclassid = $prefix . '_' . $index . '_ph_classid';

                $classopts = empty($classes)
                    ? [0 => get_string('playerhudnoclasses', 'report_unlocker')]
                    : $classes;

                if (!empty($data['classid']) && !isset($classopts[$data['classid']])) {
                    $classopts[$data['classid']] = get_string(
                        'activitynotfound',
                        'report_unlocker',
                        $data['classid']
                    );
                }

                $classselect = $mform->createElement('select', $fnameclassid, '', $classopts);
                $mform->addGroup(
                    [$classselect, $removeelem],
                    'grp_' . $fnameclassid,
                    get_string('playerhudclass', 'report_unlocker'),
                    ['&nbsp;&nbsp;'],
                    false
                );
                $mform->setType($fnameclassid, PARAM_INT);
                $mform->setDefault($fnameclassid, $data['classid'] ?? 0);
                break;

            case 'gamification':
            default:
                $staticelem = $mform->createElement(
                    'static',
                    'ph_info_' . $prefix . '_' . $index,
                    '',
                    get_string('playerhudgamification', 'report_unlocker')
                );
                $mform->addGroup(
                    [$staticelem, $removeelem],
                    'grp_phinfo_' . $prefix . '_' . $index,
                    get_string('conditiontype_playerhud', 'report_unlocker'),
                    ['&nbsp;&nbsp;'],
                    false
                );
                break;
        }
    }
}
