<?php
namespace tool_batchcertificate\local;

defined('MOODLE_INTERNAL') || die();

/** Query object for the certificate issuance audit report. */
final class report {
    public static function filters(): array {
        return [
            'search' => trim(optional_param('search', '', PARAM_RAW_TRIMMED)),
            'courseid' => optional_param('filtercourse', 0, PARAM_INT),
            'categoryid' => optional_param('filtercategory', 0, PARAM_INT),
            'cmid' => optional_param('filtercm', 0, PARAM_INT),
            'module' => clean_param(optional_param('filtermodule', '', PARAM_ALPHANUMEXT), PARAM_COMPONENT),
            'status' => optional_param('filterstatus', '', PARAM_ALPHA),
            'enrolstatus' => optional_param('filterenrol', '', PARAM_ALPHA),
            'issued' => optional_param('filterissued', '', PARAM_ALPHA),
            'createdfrom' => optional_param('createdfrom', '', PARAM_TEXT),
            'createdto' => optional_param('createdto', '', PARAM_TEXT),
            'updatedfrom' => optional_param('updatedfrom', '', PARAM_TEXT),
            'updatedto' => optional_param('updatedto', '', PARAM_TEXT),
            'sort' => optional_param('sort', 'timemodified', PARAM_ALPHANUMEXT),
            'dir' => strtolower(optional_param('dir', 'DESC', PARAM_ALPHA)) === 'ASC' ? 'ASC' : 'DESC',
        ];
    }

    public static function query(array $filters, bool $count = false): array {
        global $DB;
        $params = [];
        $where = ['1=1'];
        $joins = ' JOIN {user} u ON u.id = q.userid
            JOIN {course_modules} cm ON cm.id = q.cmid
            JOIN {course} c ON c.id = cm.course
            JOIN {modules} m ON m.id = cm.module
            JOIN {course_categories} cat ON cat.id = c.category
            LEFT JOIN (SELECT ue.userid, e.courseid, MIN(ue.status) AS enrolstatus,
                MAX(ue.timeend) AS enroltimeend FROM {user_enrolments} ue
                JOIN {enrol} e ON e.id = ue.enrolid GROUP BY ue.userid, e.courseid) en
                ON en.userid = q.userid AND en.courseid = c.id';
        if ($filters['search'] !== '') {
            $like = '%' . $DB->sql_like_escape($filters['search']) . '%';
            $where[] = '((' . $DB->sql_like("CONCAT(u.firstname, ' ', u.lastname)", ':searchname', false) .
                ' OR ' . $DB->sql_like('u.email', ':searchemail', false) .
                ' OR ' . $DB->sql_like('u.username', ':searchusername', false) .
                ' OR ' . $DB->sql_like('u.idnumber', ':searchidnumber', false) .
                ' OR ' . $DB->sql_like('c.fullname', ':searchcourse', false) . '))';
            $params += ['searchname' => $like, 'searchemail' => $like, 'searchusername' => $like,
                'searchidnumber' => $like, 'searchcourse' => $like];
        }
        if ($filters['courseid']) { $where[] = 'c.id = :courseid'; $params['courseid'] = $filters['courseid']; }
        if ($filters['categoryid']) { $where[] = 'c.category = :categoryid'; $params['categoryid'] = $filters['categoryid']; }
        if ($filters['cmid']) { $where[] = 'cm.id = :cmid'; $params['cmid'] = $filters['cmid']; }
        if ($filters['module'] !== '') { $where[] = 'm.name = :modulename'; $params['modulename'] = $filters['module']; }
        if ($filters['status'] !== '') { $where[] = 'q.status = :qstatus'; $params['qstatus'] = $filters['status']; }
        if ($filters['enrolstatus'] === 'active') { $where[] = 'en.enrolstatus = 0'; }
        if ($filters['enrolstatus'] === 'suspended') { $where[] = 'en.enrolstatus = 1'; }
        if ($filters['enrolstatus'] === 'unenrolled') { $where[] = 'en.userid IS NULL'; }
        if ($filters['issued'] === 'yes') { $where[] = "q.status IN ('success', 'skipped')"; }
        if ($filters['issued'] === 'no') { $where[] = "q.status NOT IN ('success', 'skipped')"; }
        self::date_condition($where, $params, 'q.timecreated', 'createdfrom', $filters['createdfrom'], true);
        self::date_condition($where, $params, 'q.timecreated', 'createdto', $filters['createdto'], false);
        self::date_condition($where, $params, 'q.timemodified', 'updatedfrom', $filters['updatedfrom'], true);
        self::date_condition($where, $params, 'q.timemodified', 'updatedto', $filters['updatedto'], false);
        if ($count) {
            return ['SELECT COUNT(*) FROM {tool_batchcertificate_queue} q ' . $joins .
                ' WHERE ' . implode(' AND ', $where), $params];
        }
        $sortmap = ['name' => 'u.lastname', 'course' => 'c.fullname', 'activity' => 'cm.id',
            'status' => 'q.status', 'created' => 'q.timecreated', 'timemodified' => 'q.timemodified'];
        $sort = $sortmap[$filters['sort']] ?? 'q.timemodified';
        return ['SELECT q.*, cm.course AS courseid, u.firstname, u.lastname, u.email, u.username, u.idnumber AS useridnumber,
                c.fullname AS coursename, c.shortname AS courseshortname, cat.name AS categoryname,
                m.name AS modulename, en.enrolstatus, en.enroltimeend
                FROM {tool_batchcertificate_queue} q ' . $joins . ' WHERE ' . implode(' AND ', $where) .
                ' ORDER BY ' . $sort . ' ' . $filters['dir'] . ', q.id DESC', $params];
    }

    public static function get_rows(array $filters, int $page = 0, int $perpage = 50): array {
        global $DB;
        [$sql, $params] = self::query($filters);
        $rows = $DB->get_records_sql($sql, $params, $page * $perpage, $perpage);
        foreach ($rows as $row) {
            try {
                $row->activityname = get_fast_modinfo((int)$row->courseid)->get_cm((int)$row->cmid)->name;
            } catch (\Throwable $exception) {
                $row->activityname = get_string('unknown');
            }
        }
        return $rows;
    }

    public static function get_count(array $filters): int {
        global $DB;
        [$sql, $params] = self::query($filters, true);
        return (int)$DB->count_records_sql($sql, $params);
    }

    private static function date_condition(array &$where, array &$params, string $field, string $key,
            string $value, bool $from): void {
        if ($value === '') { return; }
        $timestamp = strtotime($value . ($from ? ' 00:00:00' : ' 23:59:59'));
        if ($timestamp === false) { return; }
        $where[] = $field . ($from ? ' >= ' : ' <= ') . ':' . $key;
        $params[$key] = $timestamp;
    }
}
