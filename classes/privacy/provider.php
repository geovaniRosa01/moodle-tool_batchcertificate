<?php
namespace tool_batchcertificate\privacy;

defined('MOODLE_INTERNAL') || die();

class provider implements \core_privacy\local\metadata\provider, \core_privacy\local\request\core_userlist_provider,
        \core_privacy\local\request\plugin\provider {
    public static function get_metadata(\core_privacy\local\metadata\collection $collection): \core_privacy\local\metadata\collection {
        $collection->add_database_table('tool_batchcertificate_queue', [
            'userid' => 'privacy:metadata:queue:userid',
            'cmid' => 'privacy:metadata:queue:cmid',
        ], 'privacy:metadata:queue');
        return $collection;
    }

    public static function get_users_in_context(\core_privacy\local\request\userlist $userlist): void {
        $context = $userlist->get_context();
        if ($context->contextlevel !== CONTEXT_SYSTEM) {
            return;
        }
        global $DB;
        $userids = $DB->get_fieldset_select('tool_batchcertificate_queue', 'userid', 'userid IS NOT NULL');
        $userlist->add_users($userids);
    }

    public static function delete_data_for_users(\core_privacy\local\request\approved_userlist $userlist): void {
        global $DB;
        foreach ($userlist->get_userids() as $userid) {
            $DB->delete_records('tool_batchcertificate_queue', ['userid' => $userid]);
        }
    }

    public static function get_contexts_for_userid(int $userid): \core_privacy\local\request\contextlist {
        $contextlist = new \core_privacy\local\request\contextlist();
        global $DB;
        if ($DB->record_exists('tool_batchcertificate_queue', ['userid' => $userid])) {
            $contextlist->add_system_context();
        }
        return $contextlist;
    }

    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;
        if ($context->contextlevel === CONTEXT_SYSTEM) {
            $DB->delete_records('tool_batchcertificate_queue');
        }
    }

    public static function delete_data_for_user(\core_privacy\local\request\approved_contextlist $contextlist): void {
        global $DB;
        foreach ($contextlist->get_userids() as $userid) {
            $DB->delete_records('tool_batchcertificate_queue', ['userid' => $userid]);
        }
    }

    public static function export_user_data(\core_privacy\local\request\approved_contextlist $contextlist): void {
        global $DB;
        foreach ($contextlist->get_userids() as $userid) {
            $records = $DB->get_records('tool_batchcertificate_queue', ['userid' => $userid]);
            if ($records) {
                \core_privacy\local\request\writer::with_context($contextlist->get_context())->export_data(
                    [get_string('pluginname', 'tool_batchcertificate')], (object)['records' => array_values($records)]
                );
            }
        }
    }
}
