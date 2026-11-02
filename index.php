<?php
require_once(__DIR__ . '/../../../config.php');
require_login();
require_capability('tool/batchcertificate:issue', context_system::instance());

$courseid = optional_param('courseid', 0, PARAM_INT);
$cmid = optional_param('cmid', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$filters = \tool_batchcertificate\local\report::filters();
$courseid = $courseid ?: $filters['courseid'];
$PAGE->set_url(new moodle_url('/admin/tool/batchcertificate/index.php', ['courseid' => $courseid]));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('heading', 'tool_batchcertificate'));
$PAGE->set_heading(get_string('heading', 'tool_batchcertificate'));

if ($action === 'queue' && $cmid) {
    require_sesskey();
    $cm = get_coursemodule_from_id('', $cmid, 0, false, MUST_EXIST);
    require_capability('moodle/course:update', context_course::instance($cm->course));
    $cm = get_fast_modinfo($cm->course)->get_cm($cmid);
    $provider = (new \tool_batchcertificate\local\provider_manager())->get_provider($cm);
    if (!$provider) { throw new moodle_exception('unsupported', 'tool_batchcertificate'); }
    $users = get_enrolled_users(context_course::instance($cm->course), '', 0, 'u.id', 'u.lastname,u.firstname', 0, 0, true);
    $count = 0;
    foreach ($users as $user) {
        if ($filters['search'] !== '' && stripos(fullname($user) . ' ' . $user->email . ' ' . $user->username,
                $filters['search']) === false) { continue; }
        if (in_array($cm->modname, ['customcert', 'simplecertificate'], true) &&
                has_capability('mod/' . $cm->modname . ':manage', context_module::instance($cmid), $user->id)) { continue; }
        if (\tool_batchcertificate\local\queue::add($cmid, (int)$user->id, $provider->get_name())) { $count++; }
    }
    redirect(new moodle_url('/admin/tool/batchcertificate/index.php', ['courseid' => $cm->course]),
        get_string('queued', 'tool_batchcertificate', $count), 2);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('heading', 'tool_batchcertificate'));
echo $OUTPUT->notification(get_string('intro', 'tool_batchcertificate'), 'info');

$baseurl = new moodle_url('/admin/tool/batchcertificate/index.php');
$courses = enrol_get_my_courses(['id', 'fullname', 'category'], 'fullname ASC');
$categories = $DB->get_records_menu('course_categories', null, 'name ASC', 'id,name');
$modules = $DB->get_records_menu('modules', ['visible' => 1], 'name ASC', 'name,name');
echo html_writer::start_tag('form', ['method' => 'get', 'action' => $baseurl, 'class' => 'mb-4']);
echo html_writer::start_div('card');
echo html_writer::start_div('card-body');
echo html_writer::tag('h3', 'Filtros do relatório', ['class' => 'h5']);
echo html_writer::start_div('row');
$fields = [
    ['search', 'Aluno, e-mail, usuário, curso ou atividade', $filters['search'], 'text'],
    ['filtercategory', 'Categoria', $filters['categoryid'], 'select', [0 => 'Todas'] + $categories],
    ['filtercourse', 'Curso', $filters['courseid'], 'select', [0 => 'Todos'] + array_map(fn($c) => format_string($c->fullname), $courses)],
    ['filtermodule', 'Tipo de módulo', $filters['module'], 'select', [0 => 'Todos'] + $modules],
    ['filterstatus', 'Status', $filters['status'], 'select', ['' => 'Todos', 'pending' => 'Pendente', 'processing' => 'Processando', 'success' => 'Emitido', 'skipped' => 'Já existia', 'failed' => 'Falhou']],
    ['filterenrol', 'Inscrição', $filters['enrolstatus'], 'select', ['' => 'Todas', 'active' => 'Ativa', 'suspended' => 'Suspensa', 'unenrolled' => 'Não inscrito']],
    ['filterissued', 'Certificado', $filters['issued'], 'select', ['' => 'Todos', 'yes' => 'Emitido', 'no' => 'Não emitido']],
    ['createdfrom', 'Criado a partir de', $filters['createdfrom'], 'date'], ['createdto', 'Criado até', $filters['createdto'], 'date'],
    ['updatedfrom', 'Atualizado a partir de', $filters['updatedfrom'], 'date'], ['updatedto', 'Atualizado até', $filters['updatedto'], 'date'],
    ['sort', 'Ordenar por', $filters['sort'], 'select', ['timemodified' => 'Atualização', 'created' => 'Criação', 'name' => 'Aluno', 'course' => 'Curso', 'activity' => 'Atividade', 'status' => 'Status']],
    ['dir', 'Direção', $filters['dir'], 'select', ['DESC' => 'Decrescente', 'ASC' => 'Crescente']],
];
foreach ($fields as $field) {
    [$name, $label, $value, $type] = array_pad($field, 4, null);
    echo html_writer::start_div('col-md-3 mb-3');
    echo html_writer::label($label, $name, false, ['class' => 'd-block']);
    if ($type === 'select') {
        echo html_writer::select($field[4], $name, $value, null, ['class' => 'custom-select w-100', 'id' => $name]);
    } else {
        echo html_writer::empty_tag('input', ['type' => $type, 'name' => $name, 'id' => $name, 'value' => $value, 'class' => 'form-control']);
    }
    echo html_writer::end_div();
}
echo html_writer::end_div();
echo html_writer::empty_tag('input', ['type' => 'submit', 'value' => 'Aplicar filtros', 'class' => 'btn btn-primary mr-2']);
echo html_writer::link($baseurl, 'Limpar', ['class' => 'btn btn-secondary']);
echo html_writer::end_div();
echo html_writer::end_div();

if ($courseid) {
    $options = [0 => get_string('choosedots')];
    $manager = new \tool_batchcertificate\local\provider_manager();
    foreach (get_fast_modinfo($courseid)->get_cms() as $cm) {
        if (!$cm->uservisible) { continue; }
        $provider = $manager->get_provider($cm);
        if ($provider) { $options[$cm->id] = format_string($cm->name) . ' (' . $provider->get_name() . ')'; }
    }
    if (count($options) > 1) {
        $url = new moodle_url('/admin/tool/batchcertificate/index.php', ['courseid' => $courseid]);
        echo html_writer::start_tag('form', ['method' => 'post', 'action' => $url, 'class' => 'mb-4']);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'queue']);
        echo html_writer::label('Certificado para emitir', 'cmid');
        echo html_writer::select($options, 'cmid', $cmid, null, ['id' => 'cmid', 'class' => 'custom-select mr-2']);
        echo html_writer::empty_tag('input', ['type' => 'submit', 'value' => get_string('queue', 'tool_batchcertificate'), 'class' => 'btn btn-primary']);
        echo html_writer::end_tag('form');
    }
}

$page = max(0, optional_param('page', 0, PARAM_INT));
$perpage = min(200, max(10, optional_param('perpage', 50, PARAM_INT)));
$total = \tool_batchcertificate\local\report::get_count($filters);
$rows = \tool_batchcertificate\local\report::get_rows($filters, $page, $perpage);
$exportparams = array_filter($filters, fn($v) => $v !== '' && $v !== 0);
$exportparams['sesskey'] = sesskey();
echo html_writer::start_div('d-flex justify-content-between align-items-center mb-2');
echo html_writer::tag('strong', 'Relatório: ' . $total . ' registro(s)');
echo html_writer::link(new moodle_url('/admin/tool/batchcertificate/export.php', $exportparams), 'Exportar CSV', ['class' => 'btn btn-success']);
echo html_writer::end_div();
echo html_writer::start_tag('form', ['method' => 'post', 'action' => new moodle_url('/admin/tool/batchcertificate/download.php')]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
foreach ($filters as $filtername => $filtervalue) {
    if ($filtername === 'cmid' || $filtervalue === '' || $filtervalue === 0) {
        continue;
    }
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => $filtername, 'value' => $filtervalue]);
}
$table = new html_table();
$table->head = [html_writer::empty_tag('input', ['type' => 'checkbox', 'id' => 'batchcertificate-select-all', 'title' => 'Selecionar todos']),
    'Aluno', 'Curso / atividade', 'Módulo', 'Status', 'Inscrição', 'Criado', 'Atualizado', 'Ações'];
$table->attributes['class'] = 'generaltable flexible table table-striped';
foreach ($rows as $row) {
    $status = get_string($row->status, 'tool_batchcertificate');
    $enrol = is_null($row->enrolstatus) ? 'Não inscrito' : ($row->enrolstatus ? 'Suspensa' : 'Ativa');
    $actions = '-';
    if ($row->issueid && in_array($row->status, ['success', 'skipped'], true)) {
        $viewurl = new moodle_url('/mod/' . $row->modulename . '/view.php', ['id' => $row->cmid]);
        if (in_array($row->modulename, ['customcert', 'simplecertificate'], true)) { $viewurl->param('downloadissue', $row->issueid); }
        $actions = html_writer::link($viewurl, 'Baixar / abrir', ['class' => 'btn btn-sm btn-outline-primary']);
    }
    $selector = '';
    if ($row->issueid && in_array($row->status, ['success', 'skipped'], true)) {
        $selector = html_writer::empty_tag('input', ['type' => 'checkbox', 'name' => 'selected[]',
            'value' => $row->id, 'class' => 'batchcertificate-select', 'aria-label' => 'Selecionar certificado']);
    }
    $table->data[] = [$selector, fullname($row) . html_writer::tag('small', s($row->email), ['class' => 'd-block text-muted']),
        s($row->coursename) . html_writer::tag('small', s($row->activityname), ['class' => 'd-block text-muted']),
        s($row->modulename), s($status), s($enrol), userdate($row->timecreated), userdate($row->timemodified), $actions];
}
echo html_writer::table($table);
echo html_writer::empty_tag('input', ['type' => 'submit', 'value' => 'Baixar certificados selecionados (ZIP)',
    'class' => 'btn btn-primary']);
echo html_writer::empty_tag('input', ['type' => 'submit', 'name' => 'downloadall', 'value' => 'Baixar todos os certificados exibidos (ZIP)',
    'class' => 'btn btn-outline-primary ml-2']);
echo html_writer::end_tag('form');
echo html_writer::script("document.getElementById('batchcertificate-select-all')?.addEventListener('change', function() { document.querySelectorAll('.batchcertificate-select').forEach(function(box) { box.checked = this.checked; }, this); });");
$pagingparams = array_filter($filters, fn($v) => $v !== '' && $v !== 0);
echo $OUTPUT->paging_bar($total, $page, $perpage, new moodle_url('/admin/tool/batchcertificate/index.php', $pagingparams));
echo $OUTPUT->footer();
