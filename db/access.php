<?php
defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'tool/batchcertificate:issue' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],
];
