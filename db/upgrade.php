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
 * Auth plugin "LDAP SyncPlus" - Upgrade script
 *
 * @package    auth_ldap_syncplus
 * @copyright  2014 Alexander Bias, Ulm University <alexander.bias@uni-ulm.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Function to upgrade auth_ldap_syncplus.
 * @param int $oldversion the version we are upgrading from
 * @return bool result
 */
function xmldb_auth_ldap_syncplus_upgrade($oldversion) {
    global $DB;

    if ($oldversion < 2018020200) {
        // Convert info in config plugins from auth/ldap_syncplus to auth_ldap_syncplus.
        upgrade_fix_config_auth_plugin_names('ldap_syncplus');
        upgrade_fix_config_auth_plugin_defaults('ldap_syncplus');
        upgrade_plugin_savepoint(true, 2018020200, 'auth', 'ldap_syncplus');
    }

    if ($oldversion < 2018020601) {
        // The "auth_ldap_syncplus/coursecreators" setting was replaced with "auth_ldap_syncplus/coursecreatorcontext" (created
        // dynamically from system-assignable roles) - so migrate any existing value to the first new slot.
        if ($ldapcontext = get_config('auth_ldap_syncplus', 'creators')) {
            // Get info about the role that the old coursecreators setting would apply.
            $creatorrole = get_archetype_roles('coursecreator');
            $creatorrole = array_shift($creatorrole); // We can only use one, let's use the first.
            // Create new setting.
            set_config($creatorrole->shortname . 'context', $ldapcontext, 'auth_ldap_syncplus');
            // Delete old setting.
            set_config('creators', null, 'auth_ldap_syncplus');
            upgrade_plugin_savepoint(true, 2018020601, 'auth', 'ldap_syncplus');
        }
    }

    if ($oldversion < 2021072003) {
        // Normalize the memberattribute_isdn plugin config.
        set_config(
            'memberattribute_isdn',
            !empty(get_config('auth_ldap_syncplus', 'memberattribute_isdn')),
            'auth_ldap_syncplus'
        );

        upgrade_plugin_savepoint(true, 2021072003, 'auth', 'ldap_syncplus');
    }

    if ($oldversion < 2026100501) {
        // Normalize the memberattribute_isdn plugin config again.
        // The 2021072003 upgrade step wrote the normalized value to auth_ldap instead of auth_ldap_syncplus,
        // so sites which ran it still might have a non-normalized value in this plugin's config.
        set_config(
            'memberattribute_isdn',
            !empty(get_config('auth_ldap_syncplus', 'memberattribute_isdn')),
            'auth_ldap_syncplus'
        );

        upgrade_plugin_savepoint(true, 2026100501, 'auth', 'ldap_syncplus');
    }

    if ($oldversion < 2026100503) {
        // The start of the grace period of a suspended user is now stored in the auth_ldap_syncplus_suspendedat user
        // preference instead of being derived from the timemodified column of the user.
        // If the grace period mode (removeuser = 3) is in use, give all users who are currently suspended a fresh grace
        // period which starts now. We do not adopt the timemodified value as it does not tell us reliably when and by whom
        // the user was suspended.
        if (get_config('auth_ldap_syncplus', 'removeuser') == 3) {
            // Get the auth type of the users who are synchronized, falling back to the default.
            $authtype = get_config('auth_ldap_syncplus', 'sync_authtype');
            if (empty($authtype)) {
                $authtype = 'ldap_syncplus';
            }

            // Get all suspended users with this auth type who do not have a grace period start time yet.
            // The setting value and the preference name are hardcoded on purpose as an upgrade step must not rely on constants.
            $sql = "SELECT u.id
                      FROM {user} u
                 LEFT JOIN {user_preferences} up ON (up.userid = u.id AND up.name = :name)
                     WHERE u.auth = :auth
                           AND u.suspended = 1
                           AND u.deleted = 0
                           AND up.id IS NULL";
            $userids = $DB->get_fieldset_sql($sql, ['name' => 'auth_ldap_syncplus_suspendedat', 'auth' => $authtype]);

            // Store the current time as grace period start time for these users, in chunks to limit the memory usage.
            $now = time();
            foreach (array_chunk($userids, 1000) as $chunk) {
                // Build the user preference records of this chunk.
                $records = [];
                foreach ($chunk as $userid) {
                    $records[] = (object) ['userid' => $userid, 'name' => 'auth_ldap_syncplus_suspendedat', 'value' => $now];
                }

                // Insert the records directly instead of calling set_user_preference() for each single user.
                $DB->insert_records('user_preferences', $records);
            }
        }

        // Remember upgrade savepoint.
        upgrade_plugin_savepoint(true, 2026100503, 'auth', 'ldap_syncplus');
    }

    return true;
}
