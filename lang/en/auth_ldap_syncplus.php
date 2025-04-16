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
 * Auth plugin "LDAP SyncPlus" - Language pack
 *
 * @package    auth_ldap_syncplus
 * @copyright  2014 Alexander Bias, Ulm University <alexander.bias@uni-ulm.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['auth_ldap_syncplusdescription'] = 'This method provides authentication against an external LDAP server.
                                  If the given username and password are valid, Moodle creates a new user
                                  entry in its database. This module can read user attributes from LDAP and prefill
                                  wanted fields in Moodle.  For following logins only the username and
                                  password are checked.';
$string['auth_remove_deletewithgraceperiod'] = 'Suspend internal and fully delete internal after grace period';
$string['dangerzone'] = 'Danger zone';
$string['dangerzone_desc'] = 'In most cases, you will not want to change any of the settings here unless you want to risk to break things.';
$string['nouserentriestosuspend'] = 'No user entries to be suspended';
$string['pluginname'] = 'LDAP server (Sync Plus)';
$string['privacy:metadata'] = 'The LDAP server (Sync Plus) authentication plugin does not store any personal data.';
$string['removeuser_graceperiod'] = 'Fully deleting grace period';
$string['removeuser_graceperiod_desc'] = 'After suspending a user internally, the synchronization script will wait for this number of days until the user will be fully deleted internal. If the user re-appears in LDAP within this grace period, the user will be reactivated. Note: This setting is only used if "Removed ext user" is set to "Suspend internal and fully delete internal after grace period"';
$string['sync_authtype'] = 'Moodle authentication type when synchronizing users';
$string['sync_authtype_desc'] = 'There may be Moodle setups where users are not authenticated with LDAP, but LDAP Sync Plus should be used for provisioning and deprovisioning Moodle users. An example would be the provisioning and deprovisioning of Shibboleth users. Shibboleth does not necessarily have a way to provision and deprovision users, but a Shibboleth IDP might be able to additionally expose its user base via LDAP which would then allow you to use the LDAP Sync Plus scheduled tasks to do these synchronizations. In such a setup, you need to change this setting to control the Moodle authentication type for user accounts which are created / removed / updated by the synchronization task.';
$string['sync_filter'] = 'LDAP filter when synchronizing users';
$string['sync_filter_desc'] = 'If you are using LDAP Sync Plus to sync users with another authentication type than LDAP, you can configure a custom LDAP filter for searching for the users in LDAP here. This can become necessary if the LDAP server which holds the users to sync contains more users than you want to sync and if you can\'t realize a appropriate filter with the user_attribute and objectclass settings (which are combined by default in a hardcoded way to the LDAP filter <code>{$a}</code>). As soon as you set this setting to a non-empty string, the setting will be used as LDAP filter for determining the LDAP users which should be created / updated / deleted by the synchronisation task. Please double-check that the configured query returns exactly the users which you want to sync, not more and not less.';
$string['sync_scope'] = 'Username scope when synchronizing users';
$string['sync_scope_desc'] = 'If you are using LDAP Sync Plus to sync users with another authentication type than LDAP, you can configure an additional username scope here. This can become necessary if the user attribute in LDAP does not fully match the username in moodle, especially if the Moodle username is the eduPersonPrincipalName and the username in LDAP is just the uid. The scope is then appended to the LDAP username when syncing the users. Additionally, users who do not have the scope in their Moodle username are ignored during the synchronization. A typical example for a scope would be "@example.org".';
$string['sync_scope_note'] = 'Please note: Depending on your particular setup, it can be perfectly fine to leave the username scope empty. However, if your particular setup would require a scope and you just forget to set it, you will risk that all of your users will be deprovisioned from Moodle due to the username mismatch between Moodle and LDAP. Please make sure that the configured scope is really correct and test the synchronization thoroughly before you enable it in production.';
$string['sync_script_createuser_enabled'] = 'If enabled (default), the synchronization script will create Moodle accounts for all LDAP users if they have never logged into Moodle before. If disabled, the synchronization script will not create Moodle accounts for all LDAP users.';
$string['sync_script_createuser_enabled_key'] = 'Add new users';
$string['syncroles'] = 'LDAP roles sync job (Sync Plus)';
$string['synctask'] = 'LDAP users sync job (Sync Plus)';
$string['userentriestoadddone'] = 'Transaction complete – User entries added: {$a}';
$string['userentriestoremovedone'] = 'Transaction complete – User entries removed: {$a}';
$string['userentriestorevivedone'] = 'Transaction complete – User entries revived: {$a}';
$string['userentriestosuspend'] = 'User entries to be suspended: {$a}';
$string['userentriestosuspenddone'] = 'Transaction complete – User entries suspended: {$a}';
$string['userentriestoupdatedone'] = 'Transaction complete – User entries updated: {$a}';
$string['waitinginremovalqueue'] = 'Waiting in removal queue for {$a->days} day grace period: {$a->name} ID {$a->id}';
