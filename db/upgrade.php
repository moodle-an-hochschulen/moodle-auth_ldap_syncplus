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

defined('MOODLE_INTERNAL') || die;

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

    return true;
}
