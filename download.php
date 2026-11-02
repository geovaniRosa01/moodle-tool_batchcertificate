<?php
require_once(__DIR__ . '/../../../config.php');
require_login();
require_capability('tool/batchcertificate:issue', context_system::instance());
require_sesskey();
require_once($CFG->libdir . '/filestorage/zip_archive.php');

$downloadall = optional_param('downloadall', 0, PARAM_BOOL);
$selected = optional_param_array('selected', [], PARAM_INT);
if ($downloadall) {
    $filters = \tool_batchcertificate\local\report::filters();
    $selected = array_map(static function($record) {
        return (int)$record->id;
    }, \tool_batchcertificate\local\report::get_rows($filters, 0, 1000000));
}
$selected = array_values(array_filter(array_unique($selected)));
if (!$selected) {
    throw new moodle_exception('invalidrequest');
}

$records = $DB->get_records_list('tool_batchcertificate_queue', 'id', $selected);
$zipdir = make_request_directory(false);
if (!$zipdir) {
    throw new moodle_exception('errorcreatingdirectory', 'tool_batchcertificate');
}
$filename = 'certificados-selecionados-' . date('Y-m-d-His') . '.zip';
$zippath = $zipdir . DIRECTORY_SEPARATOR . $filename;
$zip = new zip_archive();
if (!$zip->open($zippath)) {
    throw new moodle_exception('errorcreatingzip', 'tool_batchcertificate');
}

$added = 0;
foreach ($records as $record) {
    if (!in_array($record->status, ['success', 'skipped'], true) || empty($record->issueid)) {
        continue;
    }
    try {
        $courseid = $DB->get_field('course_modules', 'course', ['id' => $record->cmid], MUST_EXIST);
        $cm = get_fast_modinfo($courseid)->get_cm($record->cmid);
        $contents = null;
        if ($cm->modname === 'customcert') {
            require_once($CFG->dirroot . '/mod/customcert/classes/template.php');
            $customcert = $DB->get_record('customcert', ['id' => $cm->instance], '*', MUST_EXIST);
            $templatedata = $DB->get_record('customcert_templates', ['id' => $customcert->templateid], '*', MUST_EXIST);
            $template = new \mod_customcert\template($templatedata);
            $contents = $template->generate_pdf(false, (int)$record->userid, true);
        } else if ($cm->modname === 'simplecertificate') {
            require_once($CFG->dirroot . '/mod/simplecertificate/locallib.php');
            $certificate = new \simplecertificate($cm->context, $cm, $cm->get_course());
            $issue = $DB->get_record('simplecertificate_issues', ['id' => $record->issueid], '*', MUST_EXIST);
            $file = $certificate->get_issue_file($issue);
            if ($file && method_exists($file, 'get_content')) {
                $contents = $file->get_content();
            } else if (is_string($file)) {
                $contents = $file;
            }
        }
        if ($contents === null || $contents === false || $contents === '') {
            continue;
        }
        $user = core_user::get_user((int)$record->userid, '*', MUST_EXIST);
        $name = clean_filename(fullname($user) . '-' . $record->id . '.pdf');
        $zip->add_file_from_string($name, $contents);
        $added++;
    } catch (\Throwable $exception) {
        debugging('Could not add certificate ' . $record->id . ': ' . $exception->getMessage(), DEBUG_DEVELOPER);
    }
}
$zip->close();
if (!$added) {
    @unlink($zippath);
    throw new moodle_exception('nodownloadablecertificates', 'tool_batchcertificate');
}
send_file($zippath, $filename);
