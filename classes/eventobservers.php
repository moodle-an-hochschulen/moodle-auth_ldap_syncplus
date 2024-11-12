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
 * Auth plugin "LDAP SyncPlus" - Event observers
 *
 * @package    auth_ldap_syncplus
 * @copyright  2014 Alexander Bias, Ulm University <alexander.bias@uni-ulm.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_ldap_syncplus;

use moodle_exception;

/**
 * Observer class containing methods monitoring various events.
 *
 * @package    auth_ldap_syncplus
 * @copyright  2014 Alexander Bias, Ulm University <alexander.bias@uni-ulm.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class eventobservers {
    /**
     * User created event observer.
     *
     * @param \core\event\base $event The event that triggered the handler.
     */
    public static function user_created(\core\event\base $event) {
        global $DB;

        // Do only if user id is enclosed in $eventdata.
        if (!empty($event->relateduserid)) {

            // Get user data.
            $user = $DB->get_record('user', ['id' => $event->relateduserid]);

            // Do if user was found.
            if (!empty($user->username)) {

                // Do only if user has ldap_syncplus authentication.
                if (isset($user->auth) && $user->auth == 'ldap_syncplus') {

                    // Update user.
                    // Actually, we would want to call auth_plugin_base::update_user_record()
                    // which is lighter, but this function is unfortunately protected since Moodle 3.5.
                    update_user_record($user->username);
                }
            }
        }
    }
}
