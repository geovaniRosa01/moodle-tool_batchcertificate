<?php
define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

[$options] = cli_get_params(['limit' => 100], ['h' => 'help']);
if (isset($options['help'])) {
    cli_writeln("Process pending batch certificate issues.\n--limit=100");
    exit(0);
}
$result = \tool_batchcertificate\local\queue::process(max(1, min(1000, (int)$options['limit'])));
cli_writeln(json_encode($result, JSON_UNESCAPED_UNICODE));
