<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $ADMIN->add('tools', new admin_externalpage(
        'tool_batchcertificate',
        get_string('pluginname', 'tool_batchcertificate'),
        new moodle_url('/admin/tool/batchcertificate/index.php'),
        'tool/batchcertificate:issue'
    ));
}
