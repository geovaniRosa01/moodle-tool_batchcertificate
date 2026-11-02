<?php
namespace tool_batchcertificate\local;

defined('MOODLE_INTERNAL') || die();

final class customcert_provider implements provider {
    public function get_name(): string {
        return 'customcert';
    }

    public function supports(\cm_info $cm): bool {
        return $cm->modname === 'customcert' && \core_component::get_component_directory('mod_customcert') !== null;
    }

    public function find_issue(\cm_info $cm, int $userid): ?\stdClass {
        global $DB;
        return $DB->get_record('customcert_issues', ['customcertid' => $cm->instance, 'userid' => $userid]) ?: null;
    }

    public function issue(\cm_info $cm, int $userid): int {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/customcert/lib.php');
        if (is_callable(['\\mod_customcert\\certificate', 'issue_certificate'])) {
            return (int)\mod_customcert\certificate::issue_certificate((int)$cm->instance, $userid);
        }
        if (class_exists('\\mod_customcert\\service\\issue_repository')) {
            $repository = new \mod_customcert\service\issue_repository();
            return $repository->create((int)$cm->instance, $userid);
        }
        if (class_exists('\\mod_customcert\\certificate')) {
            $certificate = new \mod_customcert\certificate($cm->context, $cm, $cm->get_course());
            if (method_exists($certificate, 'get_issue')) {
                $issue = $certificate->get_issue($userid);
                return (int)$issue->id;
            }
        }
        throw new \moodle_exception('unsupported', 'tool_batchcertificate');
    }
}
