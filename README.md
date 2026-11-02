# Batch certificate issuer for Moodle

`tool_batchcertificate` is an admin tool that queues certificate issuance for all active enrolled users in a course. It does not replace or modify certificate activities: it delegates issuance to the installed activity module and records an auditable result for every user.

## Supported modules

- `mod_customcert`: current service API (`mod_customcert\service\issue_repository`) with a compatibility fallback for older releases.
- `mod_simplecertificate`: uses the module's `simplecertificate::get_issue()` and `get_issue_file()` APIs.
- Other activities: implement the following callbacks in the activity's `lib.php`:

```php
function mymodule_batchcertificate_find_issue(cm_info $cm, int $userid): ?stdClass;
function mymodule_batchcertificate_issue(cm_info $cm, int $userid): int;
```

The first callback must return the existing issue object or `null`. The second must issue the certificate through the activity's own supported API and return its issue ID. The plugin never invokes arbitrary callbacks from request input; it builds the function name from the installed module component after validating the course module.

## Install

1. Copy the `batchcertificate` directory to `admin/tool/batchcertificate`.
2. Visit Site administration. Moodle will run the upgrade.
3. Give the `tool/batchcertificate:issue` capability to the manager role.
4. Open Site administration > Plugins > Admin tools > Batch certificate issuer.
5. Select a course and a supported certificate activity, then queue the batch.
6. Run Moodle cron, or run `php admin/tool/batchcertificate/cli/process.php --limit=100`.

## Report and exports

The administration page also contains an audit report for all records in the batch queue. It supports combined filters for:

- free text across learner name, email, username, ID number and course;
- category, course, certificate activity and module type;
- processing status, enrolment status and issued/not issued state;
- creation and update date ranges;
- ascending or descending ordering by update, creation, learner, course, activity or status.

The report is paginated, includes an action to open/download each issued certificate when the activity exposes a supported URL, and exports the current filtered result set as UTF-8 CSV.

Issued rows can also be selected individually, selected all at once, and downloaded together as a ZIP archive. The ZIP generator uses the native certificate APIs and currently handles `customcert` and `simplecertificate` issue files.

## Design and safety

The tool requires a system capability and `moodle/course:update` in the selected course. It skips existing issues, uses Moodle's sesskey on queue submission, limits processing per task/CLI invocation, and stores only the minimum audit data required to retry and investigate failures.

This plugin does not assume that every Moodle certificate module has the same database schema. A universal fallback is impossible without a module-level issuance contract; unsupported modules are intentionally not altered.

## Marketplace checklist

Before submission, run Moodle's plugin validation, PHP linting, PHPUnit tests, and the full developer-debugging test pass on every supported Moodle branch. Publish the source repository, bug tracker, documentation, screenshots, supported versions, dependencies, and GPL v3+ license in the Marketplace entry.
