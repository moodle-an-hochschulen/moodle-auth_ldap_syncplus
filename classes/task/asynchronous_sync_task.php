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
 * Auth plugin "LDAP SyncPlus" - Ad-hoc task definition
 *
 * @package    auth_ldap_syncplus
 * @copyright  2024 Alexander Bias, ssystems GmbH <abias@ssystems.de>
 *             based on code by Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_ldap_syncplus\task;

use core\task\adhoc_task;

/**
 * The auth_ldap_syncplus ad-hoc task class for LDAP user update
 *
 * @package    auth_ldap_syncplus
 * @copyright  2024 Alexander Bias, ssystems GmbH <abias@ssystems.de>
 *             based on code by Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class asynchronous_sync_task extends adhoc_task {
    /** @var string Message prefix for mtrace */
    protected const MTRACE_MSG = 'Synced LDAP (Sync Plus) users';

    /**
     * Constructor
     */
    public function __construct() {
        $this->set_component('auth_ldap_syncplus');
    }

    /**
     * Run users sync.
     */
    public function execute() {
        $data = $this->get_custom_data();

        /** @var auth_plugin_ldap $auth */
        $auth = get_auth_plugin('ldap_syncplus');
        $auth->update_users($data->users, $data->updatekeys);

        mtrace(sprintf(" %s (%d)", self::MTRACE_MSG, count($data->users)));
    }
}
