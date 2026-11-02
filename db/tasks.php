<?php
defined('MOODLE_INTERNAL') || die();

$tasks = [
    [
        'classname' => '\\tool_batchcertificate\\task\\process_queue',
        'blocking' => 0,
        'minute' => '*/5',
        'hour' => '*',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
    ],
];
