<?php
defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Batch certificate issuer';
$string['batchcertificate:issue'] = 'Issue certificates in batch';
$string['heading'] = 'Batch certificate issuer';
$string['intro'] = 'Select a certificate activity and queue certificates for active enrolled users. Existing certificates are skipped.';
$string['course'] = 'Course';
$string['activity'] = 'Certificate activity';
$string['queue'] = 'Queue certificates';
$string['queued'] = '{$a} certificate(s) queued for processing.';
$string['noactivities'] = 'No supported certificate activity was found in this course.';
$string['noqueue'] = 'There are no pending certificates.';
$string['status'] = 'Queue status';
$string['pending'] = 'Pending';
$string['processing'] = 'Processing';
$string['success'] = 'Issued';
$string['skipped'] = 'Skipped';
$string['failed'] = 'Failed';
$string['unsupported'] = 'This activity does not expose the generic batch certificate contract.';
$string['genericcontract'] = 'Other modules are supported when they implement the callbacks documented in README.md.';
$string['privacy:metadata'] = 'The plugin stores the certificate module, user, processing status and issue identifier for audit and retry purposes.';
$string['privacy:metadata:queue'] = 'Batch certificate queue records.';
$string['privacy:metadata:queue:userid'] = 'The user receiving the certificate.';
$string['privacy:metadata:queue:cmid'] = 'The certificate activity course-module ID.';
$string['errorcreatingdirectory'] = 'Could not create the temporary download directory.';
$string['errorcreatingzip'] = 'Could not create the ZIP archive.';
$string['nodownloadablecertificates'] = 'None of the selected certificates is available for download.';
