<?php
namespace tool_batchcertificate\local;

defined('MOODLE_INTERNAL') || die();

interface provider {
    public function get_name(): string;
    public function supports(\cm_info $cm): bool;
    public function find_issue(\cm_info $cm, int $userid): ?\stdClass;
    public function issue(\cm_info $cm, int $userid): int;
}
