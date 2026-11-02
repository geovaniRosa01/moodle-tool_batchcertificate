<?php
defined('MOODLE_INTERNAL') || die();

function xmldb_tool_batchcertificate_upgrade(int $oldversion): bool {
    if ($oldversion < 2026110201) {
        upgrade_plugin_savepoint(true, 2026110201, 'tool', 'batchcertificate');
    }
    if ($oldversion < 2026110202) {
        upgrade_plugin_savepoint(true, 2026110202, 'tool', 'batchcertificate');
    }
    if ($oldversion < 2026110203) {
        upgrade_plugin_savepoint(true, 2026110203, 'tool', 'batchcertificate');
    }
    if ($oldversion < 2026110204) {
        upgrade_plugin_savepoint(true, 2026110204, 'tool', 'batchcertificate');
    }
    return true;
}
