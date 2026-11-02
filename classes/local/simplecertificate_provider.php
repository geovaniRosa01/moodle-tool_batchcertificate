<?php
namespace tool_batchcertificate\local;

defined('MOODLE_INTERNAL') || die();

final class simplecertificate_provider implements provider {
    public function get_name(): string {
        return 'simplecertificate';
    }

    public function supports(\cm_info $cm): bool {
        return $cm->modname === 'simplecertificate' && \core_component::get_component_directory('mod_simplecertificate') !== null;
    }

    public function find_issue(\cm_info $cm, int $userid): ?\stdClass {
        global $DB;
        return $DB->get_record('simplecertificate_issues', [
            'certificateid' => $cm->instance,
            'userid' => $userid,
            'timedeleted' => null,
        ]) ?: null;
    }

    public function issue(\cm_info $cm, int $userid): int {
        global $CFG;
        require_once($CFG->dirroot . '/mod/simplecertificate/locallib.php');
        $certificate = new \simplecertificate($cm->context, $cm, $cm->get_course());
        $issue = $certificate->get_issue($userid, true);
        if (!$issue || empty($issue->id)) {
            throw new \moodle_exception('unsupported', 'tool_batchcertificate');
        }
        // Force generation of the PDF now, so the queue means “ready to download”.
        $certificate->get_issue_file($issue);
        return (int)$issue->id;
    }
}
