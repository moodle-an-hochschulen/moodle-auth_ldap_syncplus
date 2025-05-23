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
 * Auth plugin "LDAP SyncPlus" - Settings
 *
 * @package    auth_ldap_syncplus
 * @copyright  2014 Alexander Bias, Ulm University <alexander.bias@uni-ulm.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\lang_string;

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    if (!function_exists('ldap_connect')) {
        $notify = new \core\output\notification(get_string('auth_ldap_noextension', 'auth_ldap'),
                \core\output\notification::NOTIFY_WARNING);
        $settings->add(new admin_setting_heading('auth_ldap_noextension', '', $OUTPUT->render($notify)));
    } else {

        // We use a couple of custom admin settings since we need to massage the data before it is inserted into the DB.
        require_once($CFG->dirroot.'/auth/ldap/classes/admin_setting_special_lowercase_configtext.php');
        require_once($CFG->dirroot.'/auth/ldap/classes/admin_setting_special_contexts_configtext.php');
        require_once($CFG->dirroot.'/auth/ldap/classes/admin_setting_special_ntlm_configtext.php');

        // We need to use some of the Moodle LDAP constants / functions to create the list of options.
        require_once($CFG->dirroot.'/auth/ldap/auth.php');

        // We need to use some of the Moodle LDAP Syncplus constants / functions to create the list of options.
        require_once($CFG->dirroot.'/auth/ldap_syncplus/locallib.php');

        // Introductory explanation.
        $settings->add(new admin_setting_heading('auth_ldap_syncplus/pluginname', '',
                new lang_string('auth_ldapdescription', 'auth_ldap')));

        // LDAP server settings.
        $settings->add(new admin_setting_heading('auth_ldap_syncplus/ldapserversettings',
                new lang_string('auth_ldap_server_settings', 'auth_ldap'), ''));

        // Host.
        $settings->add(new admin_setting_configtext('auth_ldap_syncplus/host_url',
                get_string('auth_ldap_host_url_key', 'auth_ldap'),
                get_string('auth_ldap_host_url', 'auth_ldap'), '', PARAM_RAW_TRIMMED));

        // Version.
        $versions = [];
        $versions[2] = '2';
        $versions[3] = '3';
        $settings->add(new admin_setting_configselect('auth_ldap_syncplus/ldap_version',
                new lang_string('auth_ldap_version_key', 'auth_ldap'),
                new lang_string('auth_ldap_version', 'auth_ldap'), 3, $versions));

        // Start TLS.
        $yesno = [
            new lang_string('no'),
            new lang_string('yes'),
        ];
        $settings->add(new admin_setting_configselect('auth_ldap_syncplus/start_tls',
                new lang_string('start_tls_key', 'auth_ldap'),
                new lang_string('start_tls', 'auth_ldap'), 0 , $yesno));


        // Encoding.
        $settings->add(new admin_setting_configtext('auth_ldap_syncplus/ldapencoding',
                get_string('auth_ldap_ldap_encoding_key', 'auth_ldap'),
                get_string('auth_ldap_ldap_encoding', 'auth_ldap'), 'utf-8', PARAM_RAW_TRIMMED));

        // Page Size. (Hide if not available).
        $settings->add(new admin_setting_configtext('auth_ldap_syncplus/pagesize',
                get_string('pagesize_key', 'auth_ldap'),
                get_string('pagesize', 'auth_ldap'), '250', PARAM_INT));

        // Bind settings.
        $settings->add(new admin_setting_heading('auth_ldap_syncplus/ldapbindsettings',
                new lang_string('auth_ldap_bind_settings', 'auth_ldap'), ''));

        // Store Password in DB.
        $settings->add(new admin_setting_configselect('auth_ldap_syncplus/preventpassindb',
                new lang_string('auth_ldap_preventpassindb_key', 'auth_ldap'),
                new lang_string('auth_ldap_preventpassindb', 'auth_ldap'), 0 , $yesno));

        // User ID.
        $settings->add(new admin_setting_configtext('auth_ldap_syncplus/bind_dn',
                get_string('auth_ldap_bind_dn_key', 'auth_ldap'),
                get_string('auth_ldap_bind_dn', 'auth_ldap'), '', PARAM_RAW_TRIMMED));

        // Password.
        $settings->add(new admin_setting_configpasswordunmask('auth_ldap_syncplus/bind_pw',
                get_string('auth_ldap_bind_pw_key', 'auth_ldap'),
                get_string('auth_ldap_bind_pw', 'auth_ldap'), ''));

        // User Lookup settings.
        $settings->add(new admin_setting_heading('auth_ldap_syncplus/ldapuserlookup',
                new lang_string('auth_ldap_user_settings', 'auth_ldap'), ''));

        // User Type.
        $settings->add(new admin_setting_configselect('auth_ldap_syncplus/user_type',
                new lang_string('auth_ldap_user_type_key', 'auth_ldap'),
                new lang_string('auth_ldap_user_type', 'auth_ldap'), 'default', ldap_supported_usertypes()));

        // Contexts.
        $settings->add(new auth_ldap_admin_setting_special_contexts_configtext('auth_ldap_syncplus/contexts',
                get_string('auth_ldap_contexts_key', 'auth_ldap'),
                get_string('auth_ldap_contexts', 'auth_ldap'), '', PARAM_RAW_TRIMMED));

        // Search subcontexts.
        $settings->add(new admin_setting_configselect('auth_ldap_syncplus/search_sub',
                new lang_string('auth_ldap_search_sub_key', 'auth_ldap'),
                new lang_string('auth_ldap_search_sub', 'auth_ldap'), 0 , $yesno));

        // Dereference aliases.
        $optderef = [];
        $optderef[LDAP_DEREF_NEVER] = get_string('no');
        $optderef[LDAP_DEREF_ALWAYS] = get_string('yes');

        $settings->add(new admin_setting_configselect('auth_ldap_syncplus/opt_deref',
                new lang_string('auth_ldap_opt_deref_key', 'auth_ldap'),
                new lang_string('auth_ldap_opt_deref', 'auth_ldap'), LDAP_DEREF_NEVER , $optderef));

        // User attribute.
        $settings->add(new auth_ldap_admin_setting_special_lowercase_configtext('auth_ldap_syncplus/user_attribute',
                get_string('auth_ldap_user_attribute_key', 'auth_ldap'),
                get_string('auth_ldap_user_attribute', 'auth_ldap'), '', PARAM_RAW));

        // Suspended attribute.
        $settings->add(new auth_ldap_admin_setting_special_lowercase_configtext('auth_ldap_syncplus/suspended_attribute',
                get_string('auth_ldap_suspended_attribute_key', 'auth_ldap'),
                get_string('auth_ldap_suspended_attribute', 'auth_ldap'), '', PARAM_RAW));

        // Member attribute.
        $settings->add(new auth_ldap_admin_setting_special_lowercase_configtext('auth_ldap_syncplus/memberattribute',
                get_string('auth_ldap_memberattribute_key', 'auth_ldap'),
                get_string('auth_ldap_memberattribute', 'auth_ldap'), '', PARAM_RAW));

        // Member attribute uses dn.
        $settings->add(new admin_setting_configselect('auth_ldap_syncplus/memberattribute_isdn',
                get_string('auth_ldap_memberattribute_isdn_key', 'auth_ldap'),
                get_string('auth_ldap_memberattribute_isdn', 'auth_ldap'), 0, $yesno));

        // Object class.
        $settings->add(new admin_setting_configtext('auth_ldap_syncplus/objectclass',
                get_string('auth_ldap_objectclass_key', 'auth_ldap'),
                get_string('auth_ldap_objectclass', 'auth_ldap'), '', PARAM_RAW_TRIMMED));

        // Force Password change Header.
        $settings->add(new admin_setting_heading('auth_ldap_syncplus/ldapforcepasswordchange',
                new lang_string('forcechangepassword', 'auth'), ''));

        // Force Password change.
        $settings->add(new admin_setting_configselect('auth_ldap_syncplus/forcechangepassword',
                new lang_string('forcechangepassword', 'auth'),
                new lang_string('forcechangepasswordfirst_help', 'auth'), 0 , $yesno));

        // Standard Password Change.
        $settings->add(new admin_setting_configselect('auth_ldap_syncplus/stdchangepassword',
                new lang_string('stdchangepassword', 'auth'), new lang_string('stdchangepassword_expl', 'auth') .' '.
                get_string('stdchangepassword_explldap', 'auth'), 0 , $yesno));

        // Password Type.
        $passtype = [];
        $passtype['plaintext'] = get_string('plaintext', 'auth');
        $passtype['md5'] = get_string('md5', 'auth');
        $passtype['sha1'] = get_string('sha1', 'auth');

        $settings->add(new admin_setting_configselect('auth_ldap_syncplus/passtype',
                new lang_string('auth_ldap_passtype_key', 'auth_ldap'),
                new lang_string('auth_ldap_passtype', 'auth_ldap'), 'plaintext', $passtype));

        // Password change URL.
        $settings->add(new admin_setting_configtext('auth_ldap_syncplus/changepasswordurl',
                get_string('auth_ldap_changepasswordurl_key', 'auth_ldap'),
                get_string('changepasswordhelp', 'auth'), '', PARAM_URL));

        // Password Expiration Header.
        $settings->add(new admin_setting_heading('auth_ldap_syncplus/passwordexpire',
                new lang_string('auth_ldap_passwdexpire_settings', 'auth_ldap'), ''));

        // Password Expiration.

        // Create the description lang_string object.
        $strno = get_string('no');
        $strldapserver = get_string('pluginname', 'auth_ldap');
        $langobject = new stdClass();
        $langobject->no = $strno;
        $langobject->ldapserver = $strldapserver;
        $description = new lang_string('auth_ldap_expiration_desc', 'auth_ldap', $langobject);

        // Now create the options.
        $expiration = [];
        $expiration['0'] = $strno;
        $expiration['1'] = $strldapserver;

        // Add the setting.
        $settings->add(new admin_setting_configselect('auth_ldap_syncplus/expiration',
                new lang_string('auth_ldap_expiration_key', 'auth_ldap'),
                $description, 0 , $expiration));

        // Password Expiration warning.
        $settings->add(new admin_setting_configtext('auth_ldap_syncplus/expiration_warning',
                get_string('auth_ldap_expiration_warning_key', 'auth_ldap'),
                get_string('auth_ldap_expiration_warning_desc', 'auth_ldap'), '', PARAM_RAW));

        // Password Expiration attribute.
        $settings->add(new auth_ldap_admin_setting_special_lowercase_configtext('auth_ldap_syncplus/expireattr',
                get_string('auth_ldap_expireattr_key', 'auth_ldap'),
                get_string('auth_ldap_expireattr_desc', 'auth_ldap'), '', PARAM_RAW));

        // Grace Logins.
        $settings->add(new admin_setting_configselect('auth_ldap_syncplus/gracelogins',
                new lang_string('auth_ldap_gracelogins_key', 'auth_ldap'),
                new lang_string('auth_ldap_gracelogins_desc', 'auth_ldap'), 0 , $yesno));

        // Grace logins attribute.
        $settings->add(new auth_ldap_admin_setting_special_lowercase_configtext('auth_ldap_syncplus/graceattr',
                get_string('auth_ldap_gracelogin_key', 'auth_ldap'),
                get_string('auth_ldap_graceattr_desc', 'auth_ldap'), '', PARAM_RAW));

        // User Creation.
        $settings->add(new admin_setting_heading('auth_ldap_syncplus/usercreation',
                new lang_string('auth_user_create', 'auth'), ''));

        // Create users externally.
        $settings->add(new admin_setting_configselect('auth_ldap_syncplus/auth_user_create',
                new lang_string('auth_ldap_auth_user_create_key', 'auth_ldap'),
                new lang_string('auth_user_creation', 'auth'), 0 , $yesno));

        // Context for new users.
        $settings->add(new admin_setting_configtext('auth_ldap_syncplus/create_context',
                get_string('auth_ldap_create_context_key', 'auth_ldap'),
                get_string('auth_ldap_create_context', 'auth_ldap'), '', PARAM_RAW_TRIMMED));

        // System roles mapping header.
        $settings->add(new admin_setting_heading('auth_ldap_syncplus/systemrolemapping',
                new lang_string('systemrolemapping', 'auth_ldap'), ''));

        // Create system role mapping field for each assignable system role.
        $roles = get_ldap_assignable_role_names();
        foreach ($roles as $role) {
            // Before we can add this setting we need to check a few things.
            // A) It does not exceed 100 characters otherwise it will break the DB as the 'name' field
            // in the 'config_plugins' table is a varchar(100).
            // B) The setting name does not contain hyphens. If it does then it will fail the check
            // in parse_setting_name() and everything will explode. Role short names are validated
            // against PARAM_ALPHANUMEXT which is similar to the regex used in parse_setting_name()
            // except it also allows hyphens.
            // Instead of shortening the name and removing/replacing the hyphens we are showing a warning.
            // If we were to manipulate the setting name by removing the hyphens we may get conflicts, eg
            // 'thisisashortname' and 'this-is-a-short-name'. The same applies for shortening the setting name.
            if (core_text::strlen($role['settingname']) > 100 || !preg_match('/^[a-zA-Z0-9_]+$/', $role['settingname'])) {
                $url = new \core\url('/admin/roles/define.php', ['action' => 'edit', 'roleid' => $role['id']]);
                $a = (object)['rolename' => $role['localname'], 'shortname' => $role['shortname'], 'charlimit' => 93,
                              'link' => $url->out(), ];
                $settings->add(new admin_setting_heading('auth_ldap_syncplus/role_not_mapped_' . sha1($role['settingname']), '',
                        get_string('cannotmaprole', 'auth_ldap', $a)));
            } else {
                $settings->add(new admin_setting_configtext('auth_ldap_syncplus/' . $role['settingname'],
                        get_string('auth_ldap_rolecontext', 'auth_ldap', $role),
                        get_string('auth_ldap_rolecontext_help', 'auth_ldap', $role), '', PARAM_RAW_TRIMMED));
            }
        }

        // User Account Sync.
        $settings->add(new admin_setting_heading('auth_ldap_syncplus/syncusers',
                new lang_string('auth_sync_script', 'auth'), ''));

        // Remove external user.
        $deleteopt = [];
        $deleteopt[AUTH_REMOVEUSER_KEEP] = get_string('auth_remove_keep', 'auth');
        $deleteopt[AUTH_REMOVEUSER_SUSPEND] = get_string('auth_remove_suspend', 'auth');
        $deleteopt[AUTH_REMOVEUSER_FULLDELETE] = get_string('auth_remove_delete', 'auth');
        $deleteopt[AUTH_REMOVEUSER_DELETEWITHGRACEPERIOD] = get_string('auth_remove_deletewithgraceperiod', 'auth_ldap_syncplus');

        $settings->add(new admin_setting_configselect('auth_ldap_syncplus/removeuser',
                new lang_string('auth_remove_user_key', 'auth'),
                new lang_string('auth_remove_user', 'auth'), AUTH_REMOVEUSER_KEEP, $deleteopt));

        // Remove external user grace period.
        $settings->add(new admin_setting_configtext('auth_ldap_syncplus/removeuser_graceperiod',
                new lang_string('removeuser_graceperiod', 'auth_ldap_syncplus'),
                new lang_string('removeuser_graceperiod_desc', 'auth_ldap_syncplus'), 10, PARAM_INT));

        // Create users.
        $settings->add(new admin_setting_configselect('auth_ldap_syncplus/sync_script_createuser_enabled',
                new lang_string('sync_script_createuser_enabled_key', 'auth_ldap_syncplus'),
                new lang_string('sync_script_createuser_enabled', 'auth_ldap_syncplus'), 1, $yesno));

        // Sync Suspension.
        $settings->add(new admin_setting_configselect('auth_ldap_syncplus/sync_suspended',
                new lang_string('auth_sync_suspended_key', 'auth'),
                new lang_string('auth_sync_suspended', 'auth'), 0 , $yesno));

        // Sync update users chunk size.
        $settings->add(new admin_setting_configtext('auth_ldap_syncplus/sync_updateuserchunk',
                new lang_string('sync_updateuserchunk_key', 'auth_ldap'),
                new lang_string('sync_updateuserchunk', 'auth_ldap'), 1000, PARAM_INT));

        // NTLM SSO Header.
        $settings->add(new admin_setting_heading('auth_ldap_syncplus/ntlm',
                new lang_string('auth_ntlmsso', 'auth_ldap'), ''));

        // Enable NTLM.
        $settings->add(new admin_setting_configselect('auth_ldap_syncplus/ntlmsso_enabled',
                new lang_string('auth_ntlmsso_enabled_key', 'auth_ldap'),
                new lang_string('auth_ntlmsso_enabled', 'auth_ldap'), 0 , $yesno));

        // Subnet.
        $settings->add(new admin_setting_configtext('auth_ldap_syncplus/ntlmsso_subnet',
                get_string('auth_ntlmsso_subnet_key', 'auth_ldap'),
                get_string('auth_ntlmsso_subnet', 'auth_ldap'), '', PARAM_RAW_TRIMMED));

        // NTLM Fast Path.
        $fastpathoptions = [];
        $fastpathoptions[AUTH_NTLM_FASTPATH_YESFORM] = get_string('auth_ntlmsso_ie_fastpath_yesform', 'auth_ldap');
        $fastpathoptions[AUTH_NTLM_FASTPATH_YESATTEMPT] = get_string('auth_ntlmsso_ie_fastpath_yesattempt', 'auth_ldap');
        $fastpathoptions[AUTH_NTLM_FASTPATH_ATTEMPT] = get_string('auth_ntlmsso_ie_fastpath_attempt', 'auth_ldap');

        $settings->add(new admin_setting_configselect('auth_ldap_syncplus/ntlmsso_ie_fastpath',
                new lang_string('auth_ntlmsso_ie_fastpath_key', 'auth_ldap'),
                new lang_string('auth_ntlmsso_ie_fastpath', 'auth_ldap'),
                AUTH_NTLM_FASTPATH_ATTEMPT, $fastpathoptions));

        // Authentication type.
        $types = [];
        $types['ntlm'] = 'NTLM';
        $types['kerberos'] = 'Kerberos';

        $settings->add(new admin_setting_configselect('auth_ldap_syncplus/ntlmsso_type',
                new lang_string('auth_ntlmsso_type_key', 'auth_ldap'),
                new lang_string('auth_ntlmsso_type', 'auth_ldap'), 'ntlm', $types));

        // Remote Username format.
        $settings->add(new auth_ldap_admin_setting_special_ntlm_configtext('auth_ldap_syncplus/ntlmsso_remoteuserformat',
                get_string('auth_ntlmsso_remoteuserformat_key', 'auth_ldap'),
                get_string('auth_ntlmsso_remoteuserformat', 'auth_ldap'), '', PARAM_RAW_TRIMMED));
    }

    // Display locking / mapping of profile fields.
    $authplugin = get_auth_plugin('ldap_syncplus');
    $help = get_string('auth_ldapextrafields', 'auth_ldap');
    $help .= get_string('auth_updatelocal_expl', 'auth');
    $help .= get_string('auth_fieldlock_expl', 'auth');
    $help .= get_string('auth_updateremote_expl', 'auth');
    $help .= '<hr />';
    $help .= get_string('auth_updateremote_ldap', 'auth');
    display_auth_lock_options($settings, $authplugin->authtype, $authplugin->userfields,
            $help, true, true, $authplugin->get_custom_user_profile_fields());
}
