<?php
defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Emissão de certificados em lote';
$string['batchcertificate:issue'] = 'Emitir certificados em lote';
$string['heading'] = 'Emissão de certificados em lote';
$string['intro'] = 'Selecione uma atividade de certificado para colocar certificados na fila para os usuários ativos inscritos. Certificados existentes são ignorados.';
$string['course'] = 'Curso';
$string['activity'] = 'Atividade de certificado';
$string['queue'] = 'Colocar certificados na fila';
$string['queued'] = '{$a} certificado(s) colocado(s) na fila para processamento.';
$string['noactivities'] = 'Nenhuma atividade de certificado compatível foi encontrada neste curso.';
$string['noqueue'] = 'Não há certificados pendentes.';
$string['status'] = 'Status da fila';
$string['pending'] = 'Pendente';
$string['processing'] = 'Processando';
$string['success'] = 'Emitido';
$string['skipped'] = 'Ignorado';
$string['failed'] = 'Falhou';
$string['unsupported'] = 'Esta atividade não expõe o contrato genérico de emissão em lote.';
$string['genericcontract'] = 'Outros módulos são suportados quando implementam os callbacks documentados no README.md.';
$string['privacy:metadata'] = 'O plugin armazena o módulo de certificado, usuário, status de processamento e identificador da emissão para auditoria e novas tentativas.';
$string['privacy:metadata:queue'] = 'Registros da fila de certificados em lote.';
$string['privacy:metadata:queue:userid'] = 'O usuário que receberá o certificado.';
$string['privacy:metadata:queue:cmid'] = 'O ID do módulo da atividade de certificado.';
