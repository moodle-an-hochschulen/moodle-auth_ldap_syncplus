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
 * Auth plugin "LDAP SyncPlus" - Event handler
 *
 * @package    auth_ldap_syncplus
 * @copyright  2014 Alexander Bias, Ulm University <alexander.bias@uni-ulm.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Event handler function.
 *
 * @param object $eventdata Event data
 * @return void
 */
function update_user_onevent($eventdata) {
    global $DB;

    // Do only if user id is enclosed in $eventdata.
    if (!empty($eventdata->relateduserid)) {

        // Get user data.
        $user = $DB->get_record('user', array('id' => $eventdata->relateduserid));

        // Do if user was found.
        if (!empty($user->username)) {

            // Do only if user has ldap_syncplus authentication.
            if (isset($user->auth) && $user->auth == 'ldap_syncplus') {

                // Get LDAP Plugin.
                $authplugin = get_auth_plugin('ldap_syncplus');

                // Update user.
                $authplugin->update_user_record($user->username);
            }
        }
    }
}
