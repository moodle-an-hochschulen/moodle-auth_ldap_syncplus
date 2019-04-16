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

$string['pluginname'] = 'LDAP server (Sync Plus)';
$string['auth_ldap_syncplusdescription'] = 'This method provides authentication against an external LDAP server.
                                  If the given username and password are valid, Moodle creates a new user
                                  entry in its database. This module can read user attributes from LDAP and prefill
                                  wanted fields in Moodle.  For following logins only the username and
                                  password are checked.';
$string['auth_remove_deletewithgraceperiod'] = 'Suspend internal and fully delete internal after grace period';
$string['custom_filter_key'] = 'Custom LDAP filter';
$string['custom_filter'] = 'Optional: Custom LDAP filter; <tt>%u</tt> is substituted by the submitted username (PARAM_ALPHANUM) (<tt>%e</tt> for the email-address (PARAM_EMAIL)) in the login-form. Examples: <tt>(&(|(objectclass=person)(objectclass=user))(|(mail=%e)(userPrincipalName=%e)(userPrincipalName=%u@example.com)(otherIpPhone=%e)))</tt><br />Or for a nested group lookup:<tt>(&(&(|(objectclass=person)(objectclass=user))(|(memberof:1.2.840.113556.1.4.1941:=CN=group_with_subgroups,OU=groups,DC=example,DC=com)))(|(userPrincipalName=%e)(mail=%e)(userPrincipalName=%u@stud.thga.de)))</tt>';
$string['custom_filter_enabled_key'] = 'Enable custom LDAP filter';
$string['custom_filter_enabled'] = 'Optional: Enable the custom LDAP filter. <strong>Warning:</strong> Do not use a custom filter when you have no idea what you are doing...<br />';
$string['nouserentriestosuspend'] = 'No user entries to be suspended';
$string['privacy:metadata'] = 'The LDAP server (Sync Plus) authentication plugin does not store any personal data.';
$string['removeuser_graceperiod'] = 'Fully deleting grace period';
$string['removeuser_graceperiod_desc'] = 'After suspending a user internally, the synchronization script will wait for this number of days until the user will be fully deleted internal. If the user re-appears in LDAP within this grace period, the user will be reactivated. Note: This setting is only used if "Removed ext user" is set to "Suspend internal and fully delete internal after grace period"';
$string['sync_script_createuser_enabled'] = 'If enabled (default), the synchronization script will create Moodle accounts for all LDAP users if they have never logged into Moodle before. If disabled, the synchronization script will not create Moodle accounts for all LDAP users.';
$string['sync_script_createuser_enabled_key'] = 'Add new users';
$string['syncroles'] = 'LDAP roles sync job (Sync Plus)';
$string['synctask'] = 'LDAP users sync job (Sync Plus)';
$string['userentriestosuspend'] = 'User entries to be suspended: {$a}';
$string['waitinginremovalqueue'] = 'Waiting in removal queue for {$a->days} day grace period: {$a->name} ID {$a->id}';
