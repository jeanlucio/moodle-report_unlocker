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
 * English strings for report_unlocker.
 *
 * @package    report_unlocker
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
// phpcs:disable moodle.Files.LineLength

$string['activitynotfound'] = 'Activity not found (#{$a})';
$string['ai_action_delete'] = 'Remove';
$string['ai_action_update'] = 'Update';
$string['ai_assistant'] = 'AI Assistant';
$string['ai_col_action'] = 'Action';
$string['ai_col_activity'] = 'Activity / Section';
$string['ai_col_condition'] = 'Current condition';
$string['ai_col_condtype'] = 'Type';
$string['ai_confirm'] = 'Apply changes';
$string['ai_empty_message'] = 'Please enter a message before sending.';
$string['ai_input_placeholder'] = 'Describe the changes you want to make to the restrictions...';
$string['ai_invalid_changes'] = 'Invalid change data received.';
$string['ai_modal_title'] = 'AI Restriction Assistant';
$string['ai_no_changes'] = 'The AI did not identify any changes to propose.';
$string['ai_no_conditions'] = 'This course has no restrictions to manage.';
$string['ai_parse_failed'] = 'The AI response could not be interpreted. Please try again.';
$string['ai_preview_title'] = 'Proposed changes';
$string['ai_request_failed'] = 'The AI request failed. Please try again.';
$string['ai_send'] = 'Send';
$string['allsections'] = 'All sections';
$string['alltypes'] = 'All types';
$string['anygroup'] = 'Any group';
$string['clearfilters'] = 'Clear filters';
$string['close'] = 'Close';
$string['completionstate'] = 'Expected state';
$string['completionstate_complete'] = 'Completed';
$string['completionstate_complete_fail'] = 'Completed with failing grade';
$string['completionstate_complete_pass'] = 'Completed with passing grade';
$string['completionstate_incomplete'] = 'Not completed';
$string['conditionreadonly'] = 'This restriction type cannot be edited here. Use the activity or section settings to modify it.';
$string['conditiontype_completion'] = 'Completion';
$string['conditiontype_date'] = 'Date';
$string['conditiontype_grade'] = 'Grade';
$string['conditiontype_group'] = 'Group';
$string['conditiontype_grouping'] = 'Grouping';
$string['conditiontype_nested'] = 'Nested restriction group';
$string['conditiontype_playerhud'] = 'PlayerHUD';
$string['conditiontype_profile'] = 'User profile';
$string['conditiontype_unknown'] = 'Unknown';
$string['dateafter'] = 'Allow from';
$string['datebefore'] = 'Restrict until';
$string['entryop_and'] = 'must match all';
$string['entryop_label'] = 'Student';
$string['entryop_nand'] = 'must not match all';
$string['entryop_nor'] = 'must not match any';
$string['entryop_or'] = 'must match any';
$string['entryop_suffix'] = 'of the following:';
$string['filter'] = 'Filter';
$string['filterbysection'] = 'Filter by section';
$string['filterbytype'] = 'Filter by type';
$string['gradecategory'] = 'Grade category';
$string['grademax'] = 'Maximum (%)';
$string['grademin'] = 'Minimum (%)';
$string['gradetotalcourse'] = 'Course total';
$string['heading_modules'] = 'Activities and Resources';
$string['heading_sections'] = 'Sections';
$string['itemop_eq'] = 'Exactly (=)';
$string['itemop_gt'] = 'More than (>)';
$string['itemop_gte'] = 'At least (≥)';
$string['itemop_lt'] = 'Less than (<)';
$string['nestedgroup_editlink'] = 'Edit in activity settings ↗';
$string['nocompletionactivities'] = 'No activities with completion tracking';
$string['nodateconditions'] = 'No access restrictions were found in this course.';
$string['nodateconditions_filtered'] = 'No access restrictions match the current filter.';
$string['nogradeitems'] = 'No grade items available';
$string['op_contains'] = 'Contains';
$string['op_doesnotcontain'] = 'Does not contain';
$string['op_endswith'] = 'Ends with';
$string['op_isempty'] = 'Is empty';
$string['op_isequalto'] = 'Is equal to';
$string['op_isnotempty'] = 'Is not empty';
$string['op_startswith'] = 'Starts with';
$string['playerhudclass'] = 'PlayerHUD (RPG class)';
$string['playerhudgamification'] = 'Gamification enabled';
$string['playerhuditem'] = 'PlayerHUD (Item)';
$string['playerhuditemqty'] = 'Quantity';
$string['playerhudlevel'] = 'PlayerHUD (Minimum level)';
$string['playerhudnoclasses'] = 'No classes available';
$string['playerhudnoitems'] = 'No items available';
$string['pluginname'] = 'Unlocker — Access Restrictions';
$string['privacy:metadata'] = 'The Unlocker report does not store any personal data. It only reads and updates access conditions stored in existing course structure tables.';
$string['profilecustomfields'] = 'Custom profile fields';
$string['profilestandardfields'] = 'Standard user fields';
$string['profilevalue'] = 'Value';
$string['removeallvisible'] = 'Remove all visible';
$string['removeallvisibleconfirm'] = 'Mark {count} visible restriction(s) for removal and save all changes?';
$string['removecondition'] = 'Remove this restriction';
$string['saveall'] = 'Save all changes';
$string['savedsuccessfully'] = 'Access conditions updated successfully.';
$string['search'] = 'Search';
$string['searchplaceholder'] = 'Search by activity name...';
$string['section'] = 'Section {$a}';
$string['showcondition'] = 'Show to student';
$string['unlocker:editconditions'] = 'Edit access conditions in bulk';
$string['unlocker:view'] = 'View the Unlocker report';
