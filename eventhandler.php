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
 * @package     auth
 * @subpackage  auth_ldap_syncplus
 * @copyright   2014 Alexander Bias, University of Ulm <alexander.bias@uni-ulm.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

function update_user_onevent($eventdata) {
    // Do only if user has ldap_syncplus authentication and
    // do only if username is enclosed in $eventdata - this event handler might be called twice when creating an user, so we have to handle this fact
    if (isset($eventdata->auth) && $eventdata->auth == 'ldap_syncplus' && isset($eventdata->username) && is_string($eventdata->username)) {
        // Get LDAP Plugin
        $authplugin = get_auth_plugin('ldap_syncplus');

        // Update user
        $authplugin->update_user_record($eventdata->username);
    }
}
