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
 * Auth plugin "LDAP SyncPlus" - Privacy provider
 *
 * @package    auth_ldap_syncplus
 * @copyright  2018 Alexander Bias, Ulm University <alexander.bias@uni-ulm.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_ldap_syncplus\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\transform;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/auth/ldap_syncplus/locallib.php');

/**
 * Privacy Subsystem implementing a metadata provider and a user preference provider.
 *
 * @package    auth_ldap_syncplus
 * @copyright  2018 Alexander Bias, Ulm University <alexander.bias@uni-ulm.de>
 * @copyright  2026 Alexander Bias <bias@alexanderbias.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\user_preference_provider {
    /**
     * Returns meta data about this plugin.
     *
     * @param collection $collection The initialised collection to add items to.
     * @return collection A listing of user data stored through this plugin.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_user_preference(
            AUTH_LDAP_SYNCPLUS_SUSPENDEDAT_PREFERENCE,
            'privacy:metadata:preference:suspendedat'
        );

        return $collection;
    }

    /**
     * Export all user preferences for the plugin.
     *
     * @param int $userid The userid of the user whose data is to be exported.
     */
    public static function export_user_preferences(int $userid) {
        $suspendedat = get_user_preferences(AUTH_LDAP_SYNCPLUS_SUSPENDEDAT_PREFERENCE, null, $userid);
        if ($suspendedat !== null) {
            writer::export_user_preference(
                'auth_ldap_syncplus',
                AUTH_LDAP_SYNCPLUS_SUSPENDEDAT_PREFERENCE,
                transform::datetime($suspendedat),
                get_string('privacy:metadata:preference:suspendedat', 'auth_ldap_syncplus')
            );
        }
    }
}
