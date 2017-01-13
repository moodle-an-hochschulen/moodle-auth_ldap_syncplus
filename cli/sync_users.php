<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Auth plugin "LDAP SyncPlus" - CLI Script
 *
 * @package    auth_ldap_syncplus
 * @copyright  2014 Alexander Bias, Ulm University <alexander.bias@uni-ulm.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__.'/../../../config.php'); // global moodle config file.
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->libdir.'/clilib.php');

// Ensure errors are well explained
set_debugging(DEBUG_DEVELOPER, true);

if (!is_enabled_auth('ldap_syncplus')) {
    error_log('[AUTH LDAP SYNCPLUS] '.get_string('pluginnotenabled', 'auth_ldap'));
    die;
}

cli_problem('[AUTH LDAP SYNCPLUS] The users sync cron has been deprecated. Please use the scheduled task instead.');

// Abort execution of the CLI script if the auth_ldap_syncplus\task\sync_task is enabled.
$taskdisabled = \core\task\manager::get_scheduled_task('auth_ldap_syncplus\task\sync_task');
if (!$taskdisabled->get_disabled()) {
    cli_error('[AUTH LDAP SYNCPLUS] The scheduled task sync_task is enabled, the cron execution has been aborted.');
}

$ldapauth = get_auth_plugin('ldap_syncplus');
$ldapauth->sync_users(true);

