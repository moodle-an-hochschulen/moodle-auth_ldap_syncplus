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
 * Auth plugin "LDAP SyncPlus" - Steps definitions
 *
 * @package    auth_ldap_syncplus
 * @copyright  2022 Alexander Bias <bias@alexanderbias.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

/**
 * Step definitions.
 *
 * @package    auth_ldap_syncplus
 * @copyright  2022 Alexander Bias <bias@alexanderbias.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_auth_ldap_syncplus extends behat_base {

    /**
     * The step makes sure that a given user appears to be suspended already some days ago to test the grace period setting
     * of this plugin.
     *
     * @When /^I pretend the suspended user "(?P<username_string>(?:[^"]|\\")*)" was suspended "(?P<days_number>\d+)" days ago/
     * @param string $username
     * @param int $days
     * @return void
     */
    public function i_pretend_that_the_suspended_user_was_already_suspended_days_ago($username, $days) {
        global $DB;

        // Get the user record of the given user.
        $user = $DB->get_record('user', array('username' => $username));

        // Update the suspended field in the user record.
        $user->suspended = 1;

        // Update the timemodified field in the user record.
        $user->timemodified = time() - $days * DAYSECS;

        // Update the user record in the database.
        $DB->update_record('user', $user);
    }
}
