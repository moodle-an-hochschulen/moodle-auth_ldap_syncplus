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
require_once(__DIR__ . '/../../locallib.php');

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

        // Get the ID of the given user.
        $userid = $DB->get_field('user', 'id', ['username' => $username], MUST_EXIST);

        // Update the suspended field in the user record.
        $DB->set_field('user', 'suspended', 1, ['id' => $userid]);

        // Set the grace period start time of the user.
        set_user_preference(AUTH_LDAP_SYNCPLUS_SUSPENDEDAT_PREFERENCE, time() - $days * DAYSECS, $userid);
    }

    /**
     * The step makes sure that a given user appears to have a grace period which has started some days ago,
     * without changing the suspended status.
     * This is needed to check that a user is not deleted even though the grace period of this plugin has passed.
     *
     * @When /^I pretend the grace period of user "(?P<username_string>(?:[^"]|\\")*)" started "(?P<days_number>\d+)" days ago$/
     * @param string $username
     * @param int $days
     * @return void
     */
    public function i_pretend_that_the_grace_period_of_the_user_has_started_days_ago($username, $days) {
        global $DB;

        // Get the ID of the given user.
        $userid = $DB->get_field('user', 'id', ['username' => $username], MUST_EXIST);

        // Set the grace period start time of the user.
        set_user_preference(AUTH_LDAP_SYNCPLUS_SUSPENDEDAT_PREFERENCE, time() - $days * DAYSECS, $userid);
    }

    /**
     * The step makes sure that a given user appears to be modified some days ago, without changing the suspended status.
     * This is needed to check that the grace period of this plugin does not depend on the last modification of a user.
     *
     * @When /^I pretend the user "(?P<username_string>(?:[^"]|\\")*)" was last modified "(?P<days_number>\d+)" days ago$/
     * @param string $username
     * @param int $days
     * @return void
     */
    public function i_pretend_that_the_user_was_last_modified_days_ago($username, $days) {
        global $DB;

        // Update the timemodified field in the user record.
        $DB->set_field('user', 'timemodified', time() - $days * DAYSECS, ['username' => $username]);
    }

    /**
     * The step makes a given user a site admin, in addition to the existing site admins.
     *
     * @Given /^the user "(?P<username_string>(?:[^"]|\\")*)" is a site admin$/
     * @param string $username
     * @return void
     */
    public function the_user_is_a_site_admin($username) {
        global $CFG, $DB;

        // Get the ID of the given user.
        $userid = $DB->get_field('user', 'id', ['username' => $username, 'deleted' => 0], MUST_EXIST);

        // Add the user to the list of site admins, if necessary.
        $admins = array_filter(explode(',', $CFG->siteadmins));
        if (!in_array($userid, $admins)) {
            $admins[] = $userid;
            set_config('siteadmins', implode(',', $admins));
        }
    }

    /**
     * The step checks in the database if a given user is suspended or not.
     * This is especially needed for the guest user who is not shown in the user list.
     *
     * @Then /^the user "(?P<username_string>(?:[^"]|\\")*)" should (?P<not_string>not |)be suspended$/
     * @param string $username
     * @param string $not
     * @return void
     */
    public function the_user_should_be_suspended($username, $not) {
        global $DB;

        // Get the (non-deleted) user record of the given user.
        $user = $DB->get_record('user', ['username' => $username, 'deleted' => 0], 'id, suspended');
        if ($user === false) {
            throw new \Behat\Mink\Exception\ExpectationException(
                'The user "' . $username . '" does not exist or is deleted',
                $this->getSession()
            );
        }

        // Check the suspended status.
        $expected = ($not === '') ? 1 : 0;
        if ((int) $user->suspended !== $expected) {
            throw new \Behat\Mink\Exception\ExpectationException(
                'The user "' . $username . '" is ' . (($expected === 1) ? 'not ' : '') . 'suspended',
                $this->getSession()
            );
        }
    }

    /**
     * The step checks in the database if a grace period start time is stored for a given user or not.
     *
     * @Then /^the user "(?P<username_string>(?:[^"]|\\")*)" should (?P<not_string>not |)have a grace period start time$/
     * @param string $username
     * @param string $not
     * @return void
     */
    public function the_user_should_have_a_grace_period_start_time($username, $not) {
        global $DB;

        // Get the ID of the given (non-deleted) user.
        $userid = $DB->get_field('user', 'id', ['username' => $username, 'deleted' => 0], MUST_EXIST);

        // Check directly in the database if the user preference exists.
        $exists = $DB->record_exists(
            'user_preferences',
            ['userid' => $userid, 'name' => AUTH_LDAP_SYNCPLUS_SUSPENDEDAT_PREFERENCE]
        );

        // Check the expectation.
        $expected = ($not === '');
        if ($exists !== $expected) {
            throw new \Behat\Mink\Exception\ExpectationException(
                'The user "' . $username . '" does ' . (($expected) ? 'not ' : '') . 'have a grace period start time',
                $this->getSession()
            );
        }
    }

    /**
     * The step checks in the database if a given user is deleted or not.
     * This is especially needed for the guest user who is not shown in the user list.
     *
     * @Then /^the user "(?P<username_string>(?:[^"]|\\")*)" should (?P<not_string>not |)be deleted$/
     * @param string $username
     * @param string $not
     * @return void
     */
    public function the_user_should_be_deleted($username, $not) {
        global $DB;

        // Check if there is a non-deleted user record of the given user.
        // Please note: When a user is deleted, the username is replaced, so the username can't be found anymore then.
        $exists = $DB->record_exists('user', ['username' => $username, 'deleted' => 0]);

        // Check the deleted status.
        $expecteddeleted = ($not === '');
        if ($exists === $expecteddeleted) {
            throw new \Behat\Mink\Exception\ExpectationException(
                'The user "' . $username . '" is ' . (($expecteddeleted) ? 'not ' : '') . 'deleted',
                $this->getSession()
            );
        }
    }
}
