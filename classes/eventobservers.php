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
 * @copyright  2026 Alexander Bias <bias@alexanderbias.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_ldap_syncplus;

use moodle_exception;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/auth/ldap_syncplus/locallib.php');

/**
 * Observer class containing methods monitoring various events.
 *
 * @package    auth_ldap_syncplus
 * @copyright  2014 Alexander Bias, Ulm University <alexander.bias@uni-ulm.de>
 * @copyright  2026 Alexander Bias <bias@alexanderbias.de>
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

    /**
     * User updated event observer.
     *
     * This observer forgets the grace period start time of a user as soon as the user is not suspended anymore, for example
     * because an admin has unsuspended the user manually. Otherwise, an outdated start time would remain on an active user
     * and could be picked up again if the user is suspended by other means than the grace period mode later.
     *
     * Please note: The event does not tell which fields of the user were changed, so we check the current status of the user.
     * And the synchronization task itself updates users without triggering this event, so it removes the start time
     * on its own when it revives a user.
     *
     * @param \core\event\base $event The event that triggered the handler.
     */
    public static function user_updated(\core\event\base $event) {
        global $DB;

        // Do only if user id is enclosed in $eventdata.
        if (empty($event->relateduserid)) {
            return;
        }

        // Check with a single query if the user has a grace period start time but is not suspended (anymore).
        $sql = "SELECT up.id
                  FROM {user_preferences} up
                  JOIN {user} u ON u.id = up.userid
                 WHERE up.userid = :userid
                       AND up.name = :name
                       AND u.suspended = 0";
        $params = ['userid' => $event->relateduserid, 'name' => AUTH_LDAP_SYNCPLUS_SUSPENDEDAT_PREFERENCE];
        if ($DB->record_exists_sql($sql, $params)) {
            // Forget the grace period start time.
            unset_user_preference(AUTH_LDAP_SYNCPLUS_SUSPENDEDAT_PREFERENCE, $event->relateduserid);
        }
    }
}
