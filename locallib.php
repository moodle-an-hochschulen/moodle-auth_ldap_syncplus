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
 * Auth plugin "LDAP SyncPlus" - Local library
 *
 * @package    auth_ldap_syncplus
 * @copyright  2014 Alexander Bias, Ulm University <alexander.bias@uni-ulm.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AUTH_REMOVEUSER_DELETEWITHGRACEPERIOD', 3);

/**
 * Helper function to check if the plugin's scheduled tasks should be used to sync users with another auth type plugin.
 *
 * @return bool
 */
function auth_ldap_syncplus_sync_with_other_auth() {
    // Do the check.
    $otherauth = (get_config('auth_ldap_syncplus', 'sync_authtype') != 'ldap_syncplus');

    return $otherauth;
}
