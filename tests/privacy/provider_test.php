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
 * Auth plugin "LDAP SyncPlus" - Privacy provider tests
 *
 * @package    auth_ldap_syncplus
 * @category   test
 * @copyright  2026 Alexander Bias <bias@alexanderbias.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_ldap_syncplus\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\metadata\types\user_preference;
use core_privacy\local\request\transform;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/auth/ldap_syncplus/locallib.php');

/**
 * Privacy provider tests.
 *
 * @package    auth_ldap_syncplus
 * @category   test
 * @copyright  2026 Alexander Bias <bias@alexanderbias.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \auth_ldap_syncplus\privacy\provider
 */
final class provider_test extends \core_privacy\tests\provider_testcase {
    /**
     * Test that the provider declares the grace period start time user preference.
     */
    public function test_get_metadata(): void {
        $collection = provider::get_metadata(new collection('auth_ldap_syncplus'));
        $items = $collection->get_collection();

        $this->assertCount(1, $items);
        $item = reset($items);
        $this->assertInstanceOf(user_preference::class, $item);
        $this->assertEquals(AUTH_LDAP_SYNCPLUS_SUSPENDEDAT_PREFERENCE, $item->get_name());
        $this->assertEquals('privacy:metadata:preference:suspendedat', $item->get_summary());
    }

    /**
     * Test that nothing is exported if the user does not have a grace period start time.
     */
    public function test_export_user_preferences_no_pref(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();

        provider::export_user_preferences($user->id);

        $writer = writer::with_context(\context_system::instance());
        $this->assertFalse($writer->has_any_data());
    }

    /**
     * Test that the grace period start time of a user is exported.
     */
    public function test_export_user_preferences(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user(['suspended' => 1]);
        $suspendedat = time() - DAYSECS;
        set_user_preference(AUTH_LDAP_SYNCPLUS_SUSPENDEDAT_PREFERENCE, $suspendedat, $user->id);

        provider::export_user_preferences($user->id);

        $writer = writer::with_context(\context_system::instance());
        $this->assertTrue($writer->has_any_data());
        $preferences = $writer->get_user_preferences('auth_ldap_syncplus');
        $this->assertEquals(
            transform::datetime($suspendedat),
            $preferences->{AUTH_LDAP_SYNCPLUS_SUSPENDEDAT_PREFERENCE}->value
        );
        $this->assertEquals(
            get_string('privacy:metadata:preference:suspendedat', 'auth_ldap_syncplus'),
            $preferences->{AUTH_LDAP_SYNCPLUS_SUSPENDEDAT_PREFERENCE}->description
        );
    }
}
