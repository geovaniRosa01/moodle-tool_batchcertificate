<?php
namespace tool_batchcertificate\task;

defined('MOODLE_INTERNAL') || die();

class process_queue extends \core\task\scheduled_task {
    public function get_name(): string {
        return get_string('pluginname', 'tool_batchcertificate');
    }

    public function execute(): void {
        \tool_batchcertificate\local\queue::process(100);
    }
}
