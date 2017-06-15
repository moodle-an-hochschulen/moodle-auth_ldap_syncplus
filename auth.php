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
 * Auth plugin "LDAP SyncPlus"
 *
 * @package    auth_ldap_syncplus
 * @copyright  2014 Alexander Bias, Ulm University <alexander.bias@uni-ulm.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

global $CFG;

require_once($CFG->libdir.'/authlib.php');
require_once($CFG->libdir.'/ldaplib.php');
require_once($CFG->dirroot.'/user/lib.php');
require_once(dirname(dirname(__FILE__)).'/ldap/auth.php');
require_once(dirname(__FILE__).'/locallib.php');

class auth_plugin_ldap_syncplus extends auth_plugin_ldap {

    /**
     * Constructor with initialisation.
     */
    public function __construct() {
        $this->authtype = 'ldap_syncplus';
        $this->roleauth = 'auth_ldap';
        $this->errorlogtag = '[AUTH LDAP SYNCPLUS] ';
        $this->init_plugin($this->authtype);
    }

    /**
     * Old syntax of class constructor. Deprecated in PHP7.
     *
     * @deprecated since Moodle 3.1
     */
    public function auth_plugin_ldap_syncplus() {
        debugging('Use of class name as constructor is deprecated', DEBUG_DEVELOPER);
        self::__construct();
    }


    /**
     * Syncronizes user fron external LDAP server to moodle user table
     *
     * Sync is now using username attribute.
     *
     * Syncing users removes or suspends users that dont exists anymore in external LDAP.
     * Creates new users and updates coursecreator status of users.
     *
     * @param bool $do_updates will do pull in data updates from LDAP if relevant
     */
    function sync_users($do_updates=true) {
        global $CFG, $DB;

        mtrace(get_string('connectingldap', 'auth_ldap'));
        $ldapconnection = $this->ldap_connect();

        $dbman = $DB->get_manager();

        /// Define table user to be created
        $table = new xmldb_table('tmp_extuser');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('username', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('mnethostid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_index('username', XMLDB_INDEX_UNIQUE, array('mnethostid', 'username'));

        mtrace(get_string('creatingtemptable', 'auth_ldap', 'tmp_extuser'));
        $dbman->create_temp_table($table);

        ////
        //// get user's list from ldap to sql in a scalable fashion
        ////
        // prepare some data we'll need
        $filter = '(&('.$this->config->user_attribute.'=*)'.$this->config->objectclass.')';

        $contexts = explode(';', $this->config->contexts);

        if (!empty($this->config->create_context)) {
            array_push($contexts, $this->config->create_context);
        }

        $ldap_pagedresults = ldap_paged_results_supported($this->config->ldap_version, $ldapconnection);
        $ldap_cookie = '';
        foreach ($contexts as $context) {
            $context = trim($context);
            if (empty($context)) {
                continue;
            }

            do {
                if ($ldap_pagedresults) {
                    ldap_control_paged_result($ldapconnection, $this->config->pagesize, true, $ldap_cookie);
                }
                if ($this->config->search_sub) {
                    // Use ldap_search to find first user from subtree.
                    $ldap_result = ldap_search($ldapconnection, $context, $filter, array($this->config->user_attribute));
                } else {
                    // Search only in this context.
                    $ldap_result = ldap_list($ldapconnection, $context, $filter, array($this->config->user_attribute));
                }
                if(!$ldap_result) {
                    continue;
                }
                if ($ldap_pagedresults) {
                    ldap_control_paged_result_response($ldapconnection, $ldap_result, $ldap_cookie);
                }
                if ($entry = @ldap_first_entry($ldapconnection, $ldap_result)) {
                    do {
                        $value = ldap_get_values_len($ldapconnection, $entry, $this->config->user_attribute);
                        $value = core_text::convert($value[0], $this->config->ldapencoding, 'utf-8');
                        $value = trim($value);
                        $this->ldap_bulk_insert($value);
                    } while ($entry = ldap_next_entry($ldapconnection, $entry));
                }
                unset($ldap_result); // Free mem.
            } while ($ldap_pagedresults && $ldap_cookie !== null && $ldap_cookie != '');
        }

        // If LDAP paged results were used, the current connection must be completely
        // closed and a new one created, to work without paged results from here on.
        if ($ldap_pagedresults) {
            $this->ldap_close(true);
            $ldapconnection = $this->ldap_connect();
        }

        /// preserve our user database
        /// if the temp table is empty, it probably means that something went wrong, exit
        /// so as to avoid mass deletion of users; which is hard to undo
        $count = $DB->count_records_sql('SELECT COUNT(username) AS count, 1 FROM {tmp_extuser}');
        if ($count < 1) {
            mtrace(get_string('didntgetusersfromldap', 'auth_ldap'));
            exit;
        } else {
            mtrace(get_string('gotcountrecordsfromldap', 'auth_ldap', $count));
        }


        /// Non Grace Period Synchronisation
        if ($this->config->removeuser != AUTH_REMOVEUSER_DELETEWITHGRACEPERIOD) {

            /// User removal
            // Find users in DB that aren't in ldap -- to be removed!
            // this is still not as scalable (but how often do we mass delete?)

            if ($this->config->removeuser == AUTH_REMOVEUSER_FULLDELETE) {
                $sql = "SELECT u.*
                          FROM {user} u
                     LEFT JOIN {tmp_extuser} e ON (u.username = e.username AND u.mnethostid = e.mnethostid)
                         WHERE u.auth = :auth
                               AND u.deleted = 0
                               AND e.username IS NULL";
                $remove_users = $DB->get_records_sql($sql, array('auth'=>$this->authtype));

                if (!empty($remove_users)) {
                    mtrace(get_string('userentriestoremove', 'auth_ldap', count($remove_users)));

                    foreach ($remove_users as $user) {
                        if (delete_user($user)) {
                            mtrace("\t".get_string('auth_dbdeleteuser', 'auth_db', array('name'=>$user->username, 'id'=>$user->id)));
                        } else {
                            mtrace("\t".get_string('auth_dbdeleteusererror', 'auth_db', $user->username));
                        }
                    }
                } else {
                    mtrace(get_string('nouserentriestoremove', 'auth_ldap'));
                }
                unset($remove_users); // Free mem!

            } else if ($this->config->removeuser == AUTH_REMOVEUSER_SUSPEND) {
                $sql = "SELECT u.*
                          FROM {user} u
                     LEFT JOIN {tmp_extuser} e ON (u.username = e.username AND u.mnethostid = e.mnethostid)
                         WHERE u.auth = :auth
                               AND u.deleted = 0
                               AND u.suspended = 0
                               AND e.username IS NULL";
                $remove_users = $DB->get_records_sql($sql, array('auth'=>$this->authtype));

                if (!empty($remove_users)) {
                    mtrace(get_string('userentriestoremove', 'auth_ldap', count($remove_users)));

                    foreach ($remove_users as $user) {
                        $updateuser = new stdClass();
                        $updateuser->id = $user->id;
                        $updateuser->suspended = 1;
                        user_update_user($updateuser, false);
                        mtrace("\t".get_string('auth_dbsuspenduser', 'auth_db', array('name'=>$user->username, 'id'=>$user->id)));
                        \core\session\manager::kill_user_sessions($user->id);
                    }
                } else {
                    mtrace(get_string('nouserentriestoremove', 'auth_ldap'));
                }
                unset($remove_users); // Free mem!
            }

            /// Revive suspended users
            if (!empty($this->config->removeuser) and $this->config->removeuser == AUTH_REMOVEUSER_SUSPEND) {
                $sql = "SELECT u.id, u.username
                          FROM {user} u
                          JOIN {tmp_extuser} e ON (u.username = e.username AND u.mnethostid = e.mnethostid)
                         WHERE (u.auth = 'nologin' OR (u.auth = ? AND u.suspended = 1)) AND u.deleted = 0";
                // Note: 'nologin' is there for backwards compatibility.
                $revive_users = $DB->get_records_sql($sql, array($this->authtype));

                if (!empty($revive_users)) {
                    mtrace(get_string('userentriestorevive', 'auth_ldap', count($revive_users)));

                    foreach ($revive_users as $user) {
                        $updateuser = new stdClass();
                        $updateuser->id = $user->id;
                        $updateuser->auth = $this->authtype;
                        $updateuser->suspended = 0;
                        user_update_user($updateuser, false);
                        mtrace("\t".get_string('auth_dbreviveduser', 'auth_db', array('name'=>$user->username, 'id'=>$user->id)));
                    }
                } else {
                    mtrace(get_string('nouserentriestorevive', 'auth_ldap'));
                }

                unset($revive_users);
            }
        }

        /// Grace Period Synchronisation
        else if (!empty($this->config->removeuser) and $this->config->removeuser == AUTH_REMOVEUSER_DELETEWITHGRACEPERIOD) {

            /// Revive suspended users
            $sql = "SELECT u.id, u.username
                      FROM {user} u
                      JOIN {tmp_extuser} e ON (u.username = e.username AND u.mnethostid = e.mnethostid)
                     WHERE (u.auth = 'nologin' OR (u.auth = ? AND u.suspended = 1)) AND u.deleted = 0";
            // Note: 'nologin' is there for backwards compatibility.
            $revive_users = $DB->get_records_sql($sql, array($this->authtype));

            if (!empty($revive_users)) {
                mtrace(get_string('userentriestorevive', 'auth_ldap', count($revive_users)));

                foreach ($revive_users as $user) {
                    $updateuser = new stdClass();
                    $updateuser->id = $user->id;
                    $updateuser->auth = $this->authtype;
                    $updateuser->suspended = 0;
                    user_update_user($updateuser, false);
                    mtrace("\t".get_string('auth_dbreviveduser', 'auth_db', array('name'=>$user->username, 'id'=>$user->id)));
                }
            } else {
                mtrace(get_string('nouserentriestorevive', 'auth_ldap'));
            }
            unset($revive_users);

            /// User temporary suspending
            $sql = "SELECT u.*
                      FROM {user} u
                 LEFT JOIN {tmp_extuser} e ON (u.username = e.username AND u.mnethostid = e.mnethostid)
                     WHERE u.auth = :auth
                           AND u.deleted = 0
                           AND u.suspended = 0
                           AND e.username IS NULL";
            $remove_users = $DB->get_records_sql($sql, array('auth'=>$this->authtype));

            if (!empty($remove_users)) {
                mtrace(get_string('userentriestosuspend', 'auth_ldap_syncplus', count($remove_users)));

                foreach ($remove_users as $user) {
                    $updateuser = new stdClass();
                    $updateuser->id = $user->id;
                    $updateuser->suspended = 1;
                    $updateuser->timemodified = time(); // Remember suspend time, abuse timemodified column for this.
                    user_update_user($updateuser, false);
                    mtrace("\t".get_string('auth_dbsuspenduser', 'auth_db', array('name'=>$user->username, 'id'=>$user->id)));
                    \core\session\manager::kill_user_sessions($user->id);
                }
            } else {
                mtrace(get_string('nouserentriestosuspend', 'auth_ldap_syncplus'));
            }
            unset($remove_users); // Free mem!

            /// User complete removal
            $sql = "SELECT u.*
                      FROM {user} u
                 LEFT JOIN {tmp_extuser} e ON (u.username = e.username AND u.mnethostid = e.mnethostid)
                     WHERE u.auth = :auth
                           AND u.deleted = 0
                           AND e.username IS NULL";
            $remove_users = $DB->get_records_sql($sql, array('auth'=>$this->authtype));

            if (!empty($remove_users)) {
                mtrace(get_string('userentriestoremove', 'auth_ldap', count($remove_users)));

                foreach ($remove_users as $user) {
                    // Do only if user was suspended before grace period
                    $graceperiod = max(intval($this->config->removeuser_graceperiod), 0);
                            // Fix problems if grace period setting was negative or no number.
                    if (time() - $user->timemodified >= $graceperiod * 24 * 3600) {
                        if (delete_user($user)) {
                            mtrace("\t".get_string('auth_dbdeleteuser', 'auth_db', array('name'=>$user->username, 'id'=>$user->id)));
                        } else {
                            mtrace("\t".get_string('auth_dbdeleteusererror', 'auth_db', $user->username));
                        }
                        // Otherwise inform about ongoing grace period.
                    } else {
                        mtrace("\t".get_string('waitinginremovalqueue', 'auth_ldap_syncplus', array('days'=>$graceperiod, 'name'=>$user->username, 'id'=>$user->id)));
                    }
                }
            } else {
                mtrace(get_string('nouserentriestoremove', 'auth_ldap'));
            }
            unset($remove_users); // Free mem!
        }

        /// User Updates - time-consuming (optional)
        if ($do_updates) {
            // Narrow down what fields we need to update
            $all_keys = array_keys(get_object_vars($this->config));
            $updatekeys = array();
            foreach ($all_keys as $key) {
                if (preg_match('/^field_updatelocal_(.+)$/', $key, $match)) {
                    // If we have a field to update it from
                    // and it must be updated 'onlogin' we
                    // update it on cron
                    if (!empty($this->config->{'field_map_'.$match[1]})
                         and $this->config->{$match[0]} === 'onlogin') {
                        array_push($updatekeys, $match[1]); // the actual key name
                    }
                }
            }
            if ($this->config->suspended_attribute && $this->config->sync_suspended) {
                $updatekeys[] = 'suspended';
            }
            unset($all_keys); unset($key);
        } else {
            mtrace(get_string('noupdatestobedone', 'auth_ldap'));
        }
        if ($do_updates and !empty($updatekeys)) { // run updates only if relevant
            $users = $DB->get_records_sql('SELECT u.username, u.id
                                             FROM {user} u
                                            WHERE u.deleted = 0 AND u.auth = ? AND u.mnethostid = ?',
                                          array($this->authtype, $CFG->mnet_localhost_id));
            if (!empty($users)) {
                mtrace(get_string('userentriestoupdate', 'auth_ldap', count($users)));

                $sitecontext = context_system::instance();
                if (!empty($this->config->creators) and !empty($this->config->memberattribute)
                  and $roles = get_archetype_roles('coursecreator')) {
                    $creatorrole = array_shift($roles);      // We can only use one, let's use the first one
                } else {
                    $creatorrole = false;
                }

                $transaction = $DB->start_delegated_transaction();
                $xcount = 0;
                $maxxcount = 100;

                foreach ($users as $user) {
                    if (!$this->update_user_record($user->username, $updatekeys, true)) {
                        $skipped = ' - '.get_string('skipped');
                    }
                    else {
                        $skipped = '';
                    }
                    mtrace("\t".get_string('auth_dbupdatinguser', 'auth_db', array('name'=>$user->username, 'id'=>$user->id)).$skipped);
                    $xcount++;

                    // Update course creators if needed
                    if ($creatorrole !== false) {
                        if ($this->iscreator($user->username)) {
                            role_assign($creatorrole->id, $user->id, $sitecontext->id, $this->roleauth);
                        } else {
                            role_unassign($creatorrole->id, $user->id, $sitecontext->id, $this->roleauth);
                        }
                    }
                }
                $transaction->allow_commit();
                unset($users); // free mem
            }
        } else { // end do updates
            mtrace(get_string('noupdatestobedone', 'auth_ldap'));
        }

        /// User Additions
        // Find users missing in DB that are in LDAP
        // and gives me a nifty object I don't want.
        // note: we do not care about deleted accounts anymore, this feature was replaced by suspending to nologin auth plugin
        if (!empty($this->config->sync_script_createuser_enabled) and $this->config->sync_script_createuser_enabled == 1) {
            $sql = 'SELECT e.id, e.username
                      FROM {tmp_extuser} e
                      LEFT JOIN {user} u ON (e.username = u.username AND e.mnethostid = u.mnethostid)
                     WHERE u.id IS NULL';
            $add_users = $DB->get_records_sql($sql);

            if (!empty($add_users)) {
                mtrace(get_string('userentriestoadd', 'auth_ldap', count($add_users)));

                $sitecontext = context_system::instance();
                if (!empty($this->config->creators) and !empty($this->config->memberattribute)
                  and $roles = get_archetype_roles('coursecreator')) {
                    $creatorrole = array_shift($roles);      // We can only use one, let's use the first one
                } else {
                    $creatorrole = false;
                }

                $transaction = $DB->start_delegated_transaction();
                foreach ($add_users as $user) {
                    $user = $this->get_userinfo_asobj($user->username);

                    // Prep a few params
                    $user->modified   = time();
                    $user->confirmed  = 1;
                    $user->auth       = $this->authtype;
                    $user->mnethostid = $CFG->mnet_localhost_id;
                    // get_userinfo_asobj() might have replaced $user->username with the value
                    // from the LDAP server (which can be mixed-case). Make sure it's lowercase
                    $user->username = trim(core_text::strtolower($user->username));
                    // It isn't possible to just rely on the configured suspension attribute since
                    // things like active directory use bit masks, other things using LDAP might
                    // do different stuff as well.
                    //
                    // The cast to int is a workaround for MDL-53959.
                    $user->suspended = (int)$this->is_user_suspended($user);
                    if (empty($user->lang)) {
                        $user->lang = $CFG->lang;
                    }
                    if (empty($user->calendartype)) {
                        $user->calendartype = $CFG->calendartype;
                    }

                    $id = user_create_user($user, false);
                    mtrace("\t".get_string('auth_dbinsertuser', 'auth_db', array('name'=>$user->username, 'id'=>$id)));
                    $euser = $DB->get_record('user', array('id' => $id));

                    if (!empty($this->config->forcechangepassword)) {
                        set_user_preference('auth_forcepasswordchange', 1, $id);
                    }

                    // Add course creators if needed
                    if ($creatorrole !== false and $this->iscreator($user->username)) {
                        role_assign($creatorrole->id, $id, $sitecontext->id, $this->roleauth);
                    }

                }
                $transaction->allow_commit();
                unset($add_users); // free mem
            } else {
                mtrace(get_string('nouserstobeadded', 'auth_ldap'));
            }
        } else {
            mtrace(get_string('nouserstobeadded', 'auth_ldap'));
        }

        $dbman->drop_table($table);
        $this->ldap_close();

        return true;
    }


    /**
     * Prints a form for configuring this authentication plugin.
     *
     * This function is called from admin/auth.php, and outputs a full page with
     * a form for configuring this plugin.
     *
     * @param array $page An object containing all the data for this page.
     */
    function config_form($config, $err, $user_fields) {
        global $CFG, $OUTPUT;

        if (!function_exists('ldap_connect')) { // Is php-ldap really there?
            echo $OUTPUT->notification(get_string('auth_ldap_noextension', 'auth_ldap'));
            return;
        }

        include($CFG->dirroot.'/auth/ldap_syncplus/config.html');
    }

    /**
     * Processes and stores configuration data for this authentication plugin.
     */
    function process_config($config) {
        // Set to defaults if undefined
        if (!isset($config->host_url)) {
             $config->host_url = '';
        }
        if (!isset($config->start_tls)) {
             $config->start_tls = false;
        }
        if (empty($config->ldapencoding)) {
         $config->ldapencoding = 'utf-8';
        }
        if (!isset($config->pagesize)) {
            $config->pagesize = LDAP_DEFAULT_PAGESIZE;
        }
        if (!isset($config->contexts)) {
             $config->contexts = '';
        }
        if (!isset($config->user_type)) {
             $config->user_type = 'default';
        }
        if (!isset($config->user_attribute)) {
             $config->user_attribute = '';
        }
        if (!isset($config->suspended_attribute)) {
            $config->suspended_attribute = '';
        }
        if (!isset($config->sync_suspended)) {
            $config->sync_suspended = false;
        }
        if (!isset($config->search_sub)) {
             $config->search_sub = '';
        }
        if (!isset($config->opt_deref)) {
             $config->opt_deref = LDAP_DEREF_NEVER;
        }
        if (!isset($config->preventpassindb)) {
             $config->preventpassindb = 0;
        }
        if (!isset($config->bind_dn)) {
            $config->bind_dn = '';
        }
        if (!isset($config->bind_pw)) {
            $config->bind_pw = '';
        }
        if (!isset($config->ldap_version)) {
            $config->ldap_version = '3';
        }
        if (!isset($config->objectclass)) {
            $config->objectclass = '';
        }
        if (!isset($config->memberattribute)) {
            $config->memberattribute = '';
        }
        if (!isset($config->memberattribute_isdn)) {
            $config->memberattribute_isdn = '';
        }
        if (!isset($config->creators)) {
            $config->creators = '';
        }
        if (!isset($config->create_context)) {
            $config->create_context = '';
        }
        if (!isset($config->expiration)) {
            $config->expiration = '';
        }
        if (!isset($config->expiration_warning)) {
            $config->expiration_warning = '10';
        }
        if (!isset($config->expireattr)) {
            $config->expireattr = '';
        }
        if (!isset($config->gracelogins)) {
            $config->gracelogins = '';
        }
        if (!isset($config->graceattr)) {
            $config->graceattr = '';
        }
        if (!isset($config->auth_user_create)) {
            $config->auth_user_create = '';
        }
        if (!isset($config->forcechangepassword)) {
            $config->forcechangepassword = 0;
        }
        if (!isset($config->stdchangepassword)) {
            $config->stdchangepassword = 0;
        }
        if (!isset($config->passtype)) {
            $config->passtype = 'plaintext';
        }
        if (!isset($config->changepasswordurl)) {
            $config->changepasswordurl = '';
        }
        if (!isset($config->removeuser)) {
            $config->removeuser = AUTH_REMOVEUSER_KEEP;
        }
        if (!isset($config->removeuser_graceperiod)) {
            $config->removeuser_graceperiod = '10';
        }
        if (!isset($config->sync_script_createuser_enabled)) {
            $config->sync_script_createuser_enabled = 1;
        }
        if (!isset($config->ntlmsso_enabled)) {
            $config->ntlmsso_enabled = 0;
        }
        if (!isset($config->ntlmsso_subnet)) {
            $config->ntlmsso_subnet = '';
        }
        if (!isset($config->ntlmsso_ie_fastpath)) {
            $config->ntlmsso_ie_fastpath = 0;
        }
        if (!isset($config->ntlmsso_type)) {
            $config->ntlmsso_type = 'ntlm';
        }
        if (!isset($config->ntlmsso_remoteuserformat)) {
            $config->ntlmsso_remoteuserformat = '';
        }

        // Try to remove duplicates before storing the contexts (to avoid problems in sync_users()).
        $config->contexts = explode(';', $config->contexts);
        $config->contexts = array_map(create_function('$x', 'return core_text::strtolower(trim($x));'),
                                      $config->contexts);
        $config->contexts = implode(';', array_unique($config->contexts));

        // Save settings
        set_config('host_url', trim($config->host_url), $this->pluginconfig);
        set_config('start_tls', $config->start_tls, $this->pluginconfig);
        set_config('ldapencoding', trim($config->ldapencoding), $this->pluginconfig);
        set_config('pagesize', (int)trim($config->pagesize), $this->pluginconfig);
        set_config('contexts', $config->contexts, $this->pluginconfig);
        set_config('user_type', core_text::strtolower(trim($config->user_type)), $this->pluginconfig);
        set_config('user_attribute', core_text::strtolower(trim($config->user_attribute)), $this->pluginconfig);
        set_config('suspended_attribute', core_text::strtolower(trim($config->suspended_attribute)), $this->pluginconfig);
        set_config('sync_suspended', $config->sync_suspended, $this->pluginconfig);
        set_config('search_sub', $config->search_sub, $this->pluginconfig);
        set_config('opt_deref', $config->opt_deref, $this->pluginconfig);
        set_config('preventpassindb', $config->preventpassindb, $this->pluginconfig);
        set_config('bind_dn', trim($config->bind_dn), $this->pluginconfig);
        set_config('bind_pw', $config->bind_pw, $this->pluginconfig);
        set_config('ldap_version', $config->ldap_version, $this->pluginconfig);
        set_config('objectclass', trim($config->objectclass), $this->pluginconfig);
        set_config('memberattribute', core_text::strtolower(trim($config->memberattribute)), $this->pluginconfig);
        set_config('memberattribute_isdn', $config->memberattribute_isdn, $this->pluginconfig);
        set_config('creators', trim($config->creators), $this->pluginconfig);
        set_config('create_context', trim($config->create_context), $this->pluginconfig);
        set_config('expiration', $config->expiration, $this->pluginconfig);
        set_config('expiration_warning', trim($config->expiration_warning), $this->pluginconfig);
        set_config('expireattr', core_text::strtolower(trim($config->expireattr)), $this->pluginconfig);
        set_config('gracelogins', $config->gracelogins, $this->pluginconfig);
        set_config('graceattr', core_text::strtolower(trim($config->graceattr)), $this->pluginconfig);
        set_config('auth_user_create', $config->auth_user_create, $this->pluginconfig);
        set_config('forcechangepassword', $config->forcechangepassword, $this->pluginconfig);
        set_config('stdchangepassword', $config->stdchangepassword, $this->pluginconfig);
        set_config('passtype', $config->passtype, $this->pluginconfig);
        set_config('changepasswordurl', trim($config->changepasswordurl), $this->pluginconfig);
        set_config('removeuser', $config->removeuser, $this->pluginconfig);
        set_config('removeuser_graceperiod', $config->removeuser_graceperiod, $this->pluginconfig);
        set_config('sync_script_createuser_enabled', $config->sync_script_createuser_enabled, $this->pluginconfig);
        set_config('ntlmsso_enabled', (int)$config->ntlmsso_enabled, $this->pluginconfig);
        set_config('ntlmsso_subnet', trim($config->ntlmsso_subnet), $this->pluginconfig);
        set_config('ntlmsso_ie_fastpath', (int)$config->ntlmsso_ie_fastpath, $this->pluginconfig);
        set_config('ntlmsso_type', $config->ntlmsso_type, 'auth/ldap');
        set_config('ntlmsso_remoteuserformat', trim($config->ntlmsso_remoteuserformat), 'auth/ldap');

        return true;
    }


    /**
     * Support login via email ($CFG->authloginviaemail) for first-time LDAP logins
     * @return void
     */
    public function loginpage_hook() {
        global $CFG, $frm, $DB;

        // If $CFG->authloginviaemail is not set, users don't want to login by mail, call parent hook and return.
        if ($CFG->authloginviaemail != 1) {
            parent::loginpage_hook(); // Call parent function to retain its functionality.
            return;
        }

        // Get submitted form data.
        $frm = data_submitted();

        // If there is no username submitted, there's nothing to do, call parent hook and return.
        if (empty($frm->username)) {
            parent::loginpage_hook(); // Call parent function to retain its functionality.
            return;
        }

        // Clean username parameter to make sure that its an email adress.
        $email = clean_param($frm->username, PARAM_EMAIL);

        // If we don't have an email adress, there's nothing to do, call parent hook and return.
        if ($email == '' || strpos($email, '@') == false) {
            parent::loginpage_hook(); // Call parent function to retain its functionality.
            return;
        }

        // If there is an existing useraccount with this email adress as email adress (then a Moodle account already exists and
        // the standard mechanism of $CFG->authloginviaemail will kick in automatically) or if there is an existing useraccount
        // with this email adress as username (which is not forbidden, so this useraccount has to be used), call parent hook and
        // return.
        if ($DB->count_records_select('user', '(username = :p1 OR email = :p2) AND deleted = 0',
                                        array('p1' => $email, 'p2' => $email)) > 0) {
            parent::loginpage_hook(); // Call parent function to retain its functionality.
            return;
        }

        // Get auth plugin.
        $authplugin = get_auth_plugin('ldap_syncplus');

        // If there is no email field mapping configured, we don't know where we can find the email adress in LDAP,
        // call parent hook and return.
        if (empty($authplugin->config->field_map_email)) {
            parent::loginpage_hook(); // Call parent function to retain its functionality.
            return;
        }

        // Prepare LDAP search.
        $contexts = explode(';', $authplugin->config->contexts);
        $filter = '(&('.$authplugin->config->field_map_email.'='.ldap_filter_addslashes($email).')'.
                $authplugin->config->objectclass.')';

        // Connect to LDAP.
        $ldapconnection = $authplugin->ldap_connect();

        // Array for saving the user's ids which are found in the configured LDAP contexts.
        $uidsfound = array();

        // Look for users matching the given email adress in LDAP.
        foreach ($contexts as $context) {
            // Verify that the given context is valid.
            $context = trim($context);
            if (empty($context)) {
                continue;
            }

            // Search LDAP.
            if ($authplugin->config->search_sub) {
                // Use ldap_search to find first user from subtree.
                $ldapresult = ldap_search($ldapconnection, $context, $filter, array($authplugin->config->user_attribute));
            } else {
                // Search only in this context.
                $ldapresult = ldap_list($ldapconnection, $context, $filter, array($authplugin->config->user_attribute));
            }

            // If there is no LDAP result, continue with next context.
            if (!$ldapresult) {
                continue;
            }

            // If there is not exactly one matching user, we can't continue, call parent hook and return.
            if (ldap_count_entries($ldapconnection, $ldapresult) != 1) {
                parent::loginpage_hook(); // Call parent function to retain its functionality.
                return;
            }

            // Get this one matching user entry.
            if (!$ldapentry = ldap_first_entry($ldapconnection, $ldapresult)) {
                parent::loginpage_hook(); // Call parent function to retain its functionality.
                return;
            }

            // Get the uid attribute's value(s) from this user entry.
            $values = ldap_get_values($ldapconnection, $ldapentry, $authplugin->config->user_attribute);

            // If there is not exactly one copy of the uid attribute in the LDAP user entry, we don't know which one to use,
            // call parent hook and return.
            if ($values['count'] != 1) {
                parent::loginpage_hook(); // Call parent function to retain its functionality.
                return;
            }

            // Remember this one user's uid attribute.
            $uidsfound[] = $values[0];

            unset($ldapresult); // Free mem!
        }

        // After we have checked all contexts, verify that we have found only one user in total.
        // If not, we can't continue, call parent hook and return.
        if (count($uidsfound) != 1) {
            parent::loginpage_hook(); // Call parent function to retain its functionality.
            return;

            // Success!
            // Replace the form data's username with the user attribute from LDAP, it will be held in the global $frm variable.
        } else {
            $frm->username = $uidsfound[0];
            parent::loginpage_hook(); // Call parent function to retain its functionality.
            return;
        }
    }
}
