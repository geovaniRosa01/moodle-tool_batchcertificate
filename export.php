<?php
require_once(__DIR__ . '/../../../config.php');
require_login();
require_capability('tool/batchcertificate:issue', context_system::instance());
require_sesskey();

$filters = \tool_batchcertificate\local\report::filters();
$rows = \tool_batchcertificate\local\report::get_rows($filters, 0, 1000000);
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="relatorio-certificados-' . date('Y-m-d-His') . '.csv"');
$output = fopen('php://output', 'w');
fprintf($output, "\xEF\xBB\xBF");
fputcsv($output, ['ID', 'Aluno', 'E-mail', 'Usuário', 'Curso', 'Categoria', 'Atividade', 'Módulo', 'Status', 'Inscrição', 'ID da emissão', 'Criado em', 'Atualizado em', 'Mensagem'], ';');
foreach ($rows as $row) {
    fputcsv($output, [$row->id, fullname($row), $row->email, $row->username, $row->coursename,
        $row->categoryname, $row->activityname, $row->modulename, $row->status,
        is_null($row->enrolstatus) ? 'Não inscrito' : ($row->enrolstatus ? 'Suspensa' : 'Ativa'),
        $row->issueid, userdate($row->timecreated), userdate($row->timemodified), $row->message], ';');
}
fclose($output);
exit;
