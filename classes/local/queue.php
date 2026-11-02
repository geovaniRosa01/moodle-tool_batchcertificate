<?php
namespace tool_batchcertificate\local;

defined('MOODLE_INTERNAL') || die();

final class queue {
    public static function add(int $cmid, int $userid, string $adapter): bool {
        global $DB;
        if ($DB->record_exists('tool_batchcertificate_queue', ['cmid' => $cmid, 'userid' => $userid])) {
            return false;
        }
        $now = time();
        $record = (object)[
            'cmid' => $cmid, 'userid' => $userid, 'adapter' => $adapter,
            'status' => 'pending', 'timecreated' => $now, 'timemodified' => $now,
        ];
        $DB->insert_record('tool_batchcertificate_queue', $record);
        return true;
    }

    public static function process(int $limit = 50): array {
        global $DB;
        $items = $DB->get_records('tool_batchcertificate_queue', ['status' => 'pending'], 'id ASC', '*', 0, $limit);
        $manager = new provider_manager();
        $result = ['success' => 0, 'skipped' => 0, 'failed' => 0];
        foreach ($items as $item) {
            $item->status = 'processing';
            $item->timemodified = time();
            $DB->update_record('tool_batchcertificate_queue', $item);
            try {
                $cm = get_fast_modinfo($DB->get_field('course_modules', 'course', ['id' => $item->cmid]))->get_cm($item->cmid);
                $provider = $manager->get_provider($cm);
                if (!$provider) {
                    throw new \moodle_exception('unsupported', 'tool_batchcertificate');
                }
                $existing = $provider->find_issue($cm, (int)$item->userid);
                if ($existing) {
                    $item->status = 'skipped';
                    $item->issueid = (int)($existing->id ?? 0);
                    $item->message = 'Existing issue';
                    $result['skipped']++;
                } else {
                    $item->issueid = $provider->issue($cm, (int)$item->userid);
                    $item->status = 'success';
                    $item->message = 'Certificate issued';
                    $result['success']++;
                }
            } catch (\Throwable $exception) {
                $item->status = 'failed';
                $item->message = $exception->getMessage();
                $result['failed']++;
            }
            $item->timemodified = time();
            $DB->update_record('tool_batchcertificate_queue', $item);
        }
        return $result;
    }
}
