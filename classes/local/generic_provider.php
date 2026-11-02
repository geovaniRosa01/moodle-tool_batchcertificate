<?php
namespace tool_batchcertificate\local;

defined('MOODLE_INTERNAL') || die();

final class generic_provider implements provider {
    public function get_name(): string {
        return 'generic';
    }

    public function supports(\cm_info $cm): bool {
        if ($cm->modname === 'customcert' || $cm->modname === 'simplecertificate') {
            return false;
        }
        $this->load_module_lib($cm->modname);
        return function_exists($cm->modname . '_batchcertificate_issue')
            && function_exists($cm->modname . '_batchcertificate_find_issue');
    }

    public function find_issue(\cm_info $cm, int $userid): ?\stdClass {
        $function = $cm->modname . '_batchcertificate_find_issue';
        $issue = call_user_func($function, $cm, $userid);
        return $issue instanceof \stdClass ? $issue : null;
    }

    public function issue(\cm_info $cm, int $userid): int {
        $function = $cm->modname . '_batchcertificate_issue';
        $issueid = call_user_func($function, $cm, $userid);
        if (!is_int($issueid) && !ctype_digit((string)$issueid)) {
            throw new \moodle_exception('unsupported', 'tool_batchcertificate');
        }
        return (int)$issueid;
    }

    private function load_module_lib(string $modname): void {
        global $CFG;
        $path = $CFG->dirroot . '/mod/' . clean_param($modname, PARAM_COMPONENT) . '/lib.php';
        if (is_readable($path)) {
            require_once($path);
        }
    }
}
