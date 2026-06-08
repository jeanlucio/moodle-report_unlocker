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
 * Strings in Brazilian Portuguese for report_unlocker.
 *
 * @package    report_unlocker
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
// phpcs:disable moodle.Files.LineLength

$string['activitynotfound'] = 'Atividade não encontrada (#{$a})';
$string['allsections'] = 'Todas as seções';
$string['alltypes'] = 'Todos os tipos';
$string['anygroup'] = 'Qualquer grupo';
$string['clearfilters'] = 'Limpar filtros';
$string['completionstate'] = 'Estado esperado';
$string['completionstate_complete'] = 'Concluída';
$string['completionstate_complete_fail'] = 'Concluída com reprovação';
$string['completionstate_complete_pass'] = 'Concluída com aprovação';
$string['completionstate_incomplete'] = 'Não concluída';
$string['conditionreadonly'] = 'Este tipo de restrição não pode ser editado aqui. Use as configurações da atividade ou seção para modificá-lo.';
$string['conditiontype_completion'] = 'Conclusão de atividade';
$string['conditiontype_date'] = 'Data';
$string['conditiontype_grade'] = 'Nota';
$string['conditiontype_group'] = 'Grupo';
$string['conditiontype_grouping'] = 'Agrupamento';
$string['conditiontype_nested'] = 'Grupo de restrição aninhado';
$string['conditiontype_playerhud'] = 'PlayerHUD';
$string['conditiontype_profile'] = 'Perfil do usuário';
$string['conditiontype_unknown'] = 'Desconhecido';
$string['dateafter'] = 'Liberar a partir de';
$string['datebefore'] = 'Restringir até';
$string['entryop_and'] = 'deve combinar tudo';
$string['entryop_label'] = 'Estudante';
$string['entryop_nand'] = 'não deve combinar tudo';
$string['entryop_nor'] = 'não deve combinar qualquer';
$string['entryop_or'] = 'deve combinar qualquer';
$string['entryop_suffix'] = 'do seguinte:';
$string['filter'] = 'Filtrar';
$string['filterbysection'] = 'Filtrar por seção';
$string['filterbytype'] = 'Filtrar por tipo';
$string['gradecategory'] = 'Categoria de notas';
$string['grademax'] = 'Máximo (%)';
$string['grademin'] = 'Mínimo (%)';
$string['gradetotalcourse'] = 'Nota total do curso';
$string['heading_modules'] = 'Atividades e Recursos';
$string['heading_sections'] = 'Seções';
$string['itemop_eq'] = 'Exatamente (=)';
$string['itemop_gt'] = 'Mais de (>)';
$string['itemop_gte'] = 'Pelo menos (≥)';
$string['itemop_lt'] = 'Menos de (<)';
$string['nestedgroup_editlink'] = 'Editar nas configurações da atividade ↗';
$string['nocompletionactivities'] = 'Nenhuma atividade com acompanhamento de conclusão';
$string['nodateconditions'] = 'Nenhuma restrição de acesso foi encontrada neste curso.';
$string['nodateconditions_filtered'] = 'Nenhuma restrição de acesso corresponde ao filtro atual.';
$string['nogradeitems'] = 'Nenhum item de avaliação disponível';
$string['op_contains'] = 'Contém';
$string['op_doesnotcontain'] = 'Não contém';
$string['op_endswith'] = 'Termina com';
$string['op_isempty'] = 'Está vazio';
$string['op_isequalto'] = 'É igual a';
$string['op_isnotempty'] = 'Não está vazio';
$string['op_startswith'] = 'Começa com';
$string['playerhudclass'] = 'PlayerHUD (Classe RPG)';
$string['playerhudgamification'] = 'Gamificação habilitada';
$string['playerhuditem'] = 'PlayerHUD (Item)';
$string['playerhuditemqty'] = 'Quantidade';
$string['playerhudlevel'] = 'PlayerHUD (Nível mínimo)';
$string['playerhudnoclasses'] = 'Nenhuma classe disponível';
$string['playerhudnoitems'] = 'Nenhum item disponível';
$string['pluginname'] = 'Unlocker — Restrições de Acesso';
$string['privacy:metadata'] = 'O relatório Unlocker não armazena dados pessoais. Ele apenas lê e atualiza as condições de acesso nas tabelas de estrutura do curso já existentes.';
$string['profilecustomfields'] = 'Campos de perfil personalizados';
$string['profilestandardfields'] = 'Campos padrão do estudante';
$string['profilevalue'] = 'Valor';
$string['removeallvisible'] = 'Excluir todas as visíveis';
$string['removeallvisibleconfirm'] = 'Marcar {count} restrição(ões) visível(is) para remoção e salvar todas as alterações?';
$string['removecondition'] = 'Remover esta restrição';
$string['saveall'] = 'Salvar todas as alterações';
$string['savedsuccessfully'] = 'Condições de acesso atualizadas com sucesso.';
$string['search'] = 'Pesquisar';
$string['searchplaceholder'] = 'Pesquisar pelo nome da atividade...';
$string['section'] = 'Seção {$a}';
$string['showcondition'] = 'Exibir ao estudante';
$string['unlocker:editconditions'] = 'Editar condições de acesso em massa';
$string['unlocker:view'] = 'Visualizar relatório Unlocker';
