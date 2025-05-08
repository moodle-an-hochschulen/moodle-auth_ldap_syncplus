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

// phpcs:disable
// Let codechecker ignore this file. This code mostly re-used from auth_ldap and the problems are already there and not made by us.

global $CFG;

require_once($CFG->libdir.'/authlib.php');
require_once($CFG->libdir.'/ldaplib.php');
require_once($CFG->dirroot.'/user/lib.php');
require_once($CFG->dirroot.'/auth/ldap/locallib.php');
require_once(__DIR__.'/../ldap/auth.php');
require_once(__DIR__.'/locallib.php');

/**
 * Auth plugin "LDAP SyncPlus" - Auth class
 *
 * @package    auth_ldap_syncplus
 * @copyright  2014 Alexander Bias, Ulm University <alexander.bias@uni-ulm.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
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
     * Reads user information from ldap and returns it in array()
     *
     * Function should return all information available. If you are saving
     * this information to moodle user-table you should honor syncronization flags
     *
     * @param string $username username
     *
     * @return mixed array with no magic quotes or false on error
     */
    function get_userinfo($username) {
        $extusername = core_text::convert($username, 'utf-8', $this->config->ldapencoding);

        // Remove the scope from the username if configured.
        // This must be done here and not in the calling code as this function is also called by update_user_record()
        // which is part of lib/authlib.php and which is not overritten by this plugin.
        $extusername = $this->strip_scope_from_username($extusername);

        $ldapconnection = $this->ldap_connect();

        if(!($user_dn = $this->ldap_find_userdn($ldapconnection, $extusername))) {
            $this->ldap_close();
            return false;
        }

        $search_attribs = array();
        $attrmap = $this->ldap_attributes();
        foreach ($attrmap as $key => $values) {
            if (!is_array($values)) {
                $values = array($values);
            }
            foreach ($values as $value) {
                if (!in_array($value, $search_attribs)) {
                    array_push($search_attribs, $value);
                }
            }
        }

        if (!$user_info_result = ldap_read($ldapconnection, $user_dn, '(objectClass=*)', $search_attribs)) {
            $this->ldap_close();
            return false; // error!
        }

        $user_entry = ldap_get_entries_moodle($ldapconnection, $user_info_result);
        if (empty($user_entry)) {
            $this->ldap_close();
            return false; // entry not found
        }

        $result = array();
        foreach ($attrmap as $key => $values) {
            if (!is_array($values)) {
                $values = array($values);
            }
            $ldapval = NULL;
            foreach ($values as $value) {
                $entry = $user_entry[0];
                if (($value == 'dn') || ($value == 'distinguishedname')) {
                    $result[$key] = $user_dn;
                    continue;
                }
                if (!array_key_exists($value, $entry)) {
                    continue; // wrong data mapping!
                }
                if (is_array($entry[$value])) {
                    $newval = core_text::convert($entry[$value][0], $this->config->ldapencoding, 'utf-8');
                } else {
                    $newval = core_text::convert($entry[$value], $this->config->ldapencoding, 'utf-8');
                }
                if (!empty($newval)) { // favour ldap entries that are set
                    $ldapval = $newval;
                }
            }
            if (!is_null($ldapval)) {
                $result[$key] = $ldapval;
            }
        }

        $this->ldap_close();
        return $result;
    }

    /**
     * Synchronise users from the external LDAP server to Moodle's user table.
     *
     * Calls sync_users_update_callback() with default callback if appropriate.
     *
     * @param bool $doupdates will do pull in data updates from LDAP if relevant
     * @return bool success
     */
    public function sync_users($doupdates = true) {
        return $this->sync_users_update_callback($doupdates ? [$this, 'update_users'] : null);
    }

    /**
     * Synchronise users from the external LDAP server to Moodle's user table (callback).
     *
     * Sync is now using username attribute.
     *
     * Syncing users removes or suspends users that dont exists anymore in external LDAP.
     * Creates new users and updates coursecreator status of users.
     *
     * @param callable|null $updatecallback will do pull in data updates from LDAP if relevant
     * @return bool success
     */
    public function sync_users_update_callback(?callable $updatecallback = null): bool {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/user/profile/lib.php');

        mtrace(get_string('connectingldap', 'auth_ldap'));
        $ldapconnection = $this->ldap_connect();

        $dbman = $DB->get_manager();

        // Define table user to be created.
        $table = new xmldb_table('tmp_extuser');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('username', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('mnethostid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_index('username', XMLDB_INDEX_UNIQUE, array('mnethostid', 'username'));

        mtrace(get_string('creatingtemptable', 'auth_ldap', 'tmp_extuser'));
        $dbman->create_temp_table($table);

        // Get user's list from ldap to sql in a scalable fashion.
        // Prepare some data we'll need.

        // Get the custom LDAP sync filter.
        $filter = $this->get_ldap_sync_filter();
        // But if it's empty, use the default filter.
        if (empty($filter)) {
            $filter = '(&('.$this->config->user_attribute.'=*)'.$this->config->objectclass.')';
        }

        $servercontrols = array();

        $contexts = explode(';', $this->config->contexts);

        if (!empty($this->config->create_context)) {
            array_push($contexts, $this->config->create_context);
        }

        $ldappagedresults = ldap_paged_results_supported($this->config->ldap_version, $ldapconnection);
        $ldapcookie = '';
        foreach ($contexts as $context) {
            $context = trim($context);
            if (empty($context)) {
                continue;
            }

            do {
                if ($ldappagedresults) {
                    $servercontrols = array(array(
                        'oid' => LDAP_CONTROL_PAGEDRESULTS, 'value' => array(
                            'size' => $this->config->pagesize, 'cookie' => $ldapcookie)));
                }
                if ($this->config->search_sub) {
                    // Use ldap_search to find first user from subtree.
                    $ldapresult = ldap_search($ldapconnection, $context, $filter, array($this->config->user_attribute),
                        0, -1, -1, LDAP_DEREF_NEVER, $servercontrols);
                } else {
                    // Search only in this context.
                    $ldapresult = ldap_list($ldapconnection, $context, $filter, array($this->config->user_attribute),
                        0, -1, -1, LDAP_DEREF_NEVER, $servercontrols);
                }
                if(!$ldapresult) {
                    continue;
                }
                if ($ldappagedresults) {
                    // Get next server cookie to know if we'll need to continue searching.
                    $ldapcookie = '';
                    // Get next cookie from controls.
                    ldap_parse_result($ldapconnection, $ldapresult, $errcode, $matcheddn,
                        $errmsg, $referrals, $controls);
                    if (isset($controls[LDAP_CONTROL_PAGEDRESULTS]['value']['cookie'])) {
                        $ldapcookie = $controls[LDAP_CONTROL_PAGEDRESULTS]['value']['cookie'];
                    }
                }
                if ($entry = @ldap_first_entry($ldapconnection, $ldapresult)) {
                    do {
                        $value = ldap_get_values_len($ldapconnection, $entry, $this->config->user_attribute);
                        $value = core_text::convert($value[0], $this->config->ldapencoding, 'utf-8');
                        $value = trim($value);
                        $this->ldap_bulk_insert($value);
                    } while ($entry = ldap_next_entry($ldapconnection, $entry));
                }
                unset($ldapresult); // Free mem.
            } while ($ldappagedresults && $ldapcookie !== null && $ldapcookie != '');
        }

        // If LDAP paged results were used, the current connection must be completely
        // closed and a new one created, to work without paged results from here on.
        if ($ldappagedresults) {
            $this->ldap_close(true);
            $ldapconnection = $this->ldap_connect();
        }

        // Preserve our user database.
        // If the temp table is empty, it probably means that something went wrong, exit
        // so as to avoid mass deletion of users; which is hard to undo.
        $count = $DB->count_records_sql('SELECT COUNT(username) AS count, 1 FROM {tmp_extuser}');
        if ($count < 1) {
            mtrace(get_string('didntgetusersfromldap', 'auth_ldap'));
            $dbman->drop_table($table);
            $this->ldap_close();
            return false;
        } else {
            mtrace(get_string('gotcountrecordsfromldap', 'auth_ldap', $count));
        }


        // Non Grace Period Synchronisation.
        if ($this->config->removeuser != AUTH_REMOVEUSER_DELETEWITHGRACEPERIOD) {

            // User removal.
            // Find users in DB that aren't in ldap -- to be removed!
            // this is still not as scalable (but how often do we mass delete?).

            if ($this->config->removeuser == AUTH_REMOVEUSER_FULLDELETE) {
                $sql = "SELECT u.*
                          FROM {user} u
                     LEFT JOIN {tmp_extuser} e ON (u.username = (".$this->get_sync_scope_sql_joinon_snippet('e.username').")
                            AND u.mnethostid = e.mnethostid)
                         WHERE u.auth = :auth
                               AND u.deleted = 0
                               AND e.username IS NULL";
                $remove_users = $DB->get_records_sql($sql, array('auth'=>$this->config->sync_authtype));

                if (!empty($remove_users)) {
                    mtrace(get_string('userentriestoremove', 'auth_ldap', count($remove_users)));

                    foreach ($remove_users as $user) {
                        if (delete_user($user)) {
                            mtrace("\t".get_string('auth_dbdeleteuser', 'auth_db', array('name'=>$user->username, 'id'=>$user->id)));
                        } else {
                            mtrace("\t".get_string('auth_dbdeleteusererror', 'auth_db', $user->username));
                        }
                    }

                    mtrace(get_string('userentriestoremovedone', 'auth_ldap_syncplus', count($remove_users)));
                } else {
                    mtrace(get_string('nouserentriestoremove', 'auth_ldap'));
                }
                unset($remove_users); // Free mem!

            } else if ($this->config->removeuser == AUTH_REMOVEUSER_SUSPEND) {
                $sql = "SELECT u.*
                          FROM {user} u
                     LEFT JOIN {tmp_extuser} e ON (u.username = (".$this->get_sync_scope_sql_joinon_snippet('e.username').")
                            AND u.mnethostid = e.mnethostid)
                         WHERE u.auth = :auth
                               AND u.deleted = 0
                               AND u.suspended = 0
                               AND e.username IS NULL";
                $remove_users = $DB->get_records_sql($sql, array('auth'=>$this->config->sync_authtype));

                if (!empty($remove_users)) {
                    mtrace(get_string('userentriestoremove', 'auth_ldap', count($remove_users)));

                    foreach ($remove_users as $user) {
                        $updateuser = new stdClass();
                        $updateuser->id = $user->id;
                        $updateuser->suspended = 1;
                        user_update_user($updateuser, false);
                        mtrace("\t".get_string('auth_dbsuspenduser', 'auth_db', array('name'=>$user->username, 'id'=>$user->id)));
                        \core\session\manager::destroy_user_sessions($user->id);
                    }

                    mtrace(get_string('userentriestoremovedone', 'auth_ldap_syncplus', count($remove_users)));
                } else {
                    mtrace(get_string('nouserentriestoremove', 'auth_ldap'));
                }
                unset($remove_users); // Free mem!
            }

            // Revive suspended users.
            if (!empty($this->config->removeuser) and $this->config->removeuser == AUTH_REMOVEUSER_SUSPEND) {
                $sql = "SELECT u.id, u.username
                          FROM {user} u
                          JOIN {tmp_extuser} e ON (u.username = (".$this->get_sync_scope_sql_joinon_snippet('e.username').")
                                AND u.mnethostid = e.mnethostid)
                         WHERE (u.auth = 'nologin' OR (u.auth = ? AND u.suspended = 1)) AND u.deleted = 0";
                // Note: 'nologin' is there for backwards compatibility.
                $revive_users = $DB->get_records_sql($sql, array($this->config->sync_authtype));

                if (!empty($revive_users)) {
                    mtrace(get_string('userentriestorevive', 'auth_ldap', count($revive_users)));

                    foreach ($revive_users as $user) {
                        $updateuser = new stdClass();
                        $updateuser->id = $user->id;
                        $updateuser->auth = $this->config->sync_authtype;
                        $updateuser->suspended = 0;
                        user_update_user($updateuser, false);
                        mtrace("\t".get_string('auth_dbreviveduser', 'auth_db', array('name'=>$user->username, 'id'=>$user->id)));
                    }

                    mtrace(get_string('userentriestorevivedone', 'auth_ldap_syncplus', count($revive_users)));
                } else {
                    mtrace(get_string('nouserentriestorevive', 'auth_ldap'));
                }

                unset($revive_users);
            }
        }

        // Grace Period Synchronisation.
        else if (!empty($this->config->removeuser) and $this->config->removeuser == AUTH_REMOVEUSER_DELETEWITHGRACEPERIOD) {

            // Revive suspended users.
            $sql = "SELECT u.id, u.username
                      FROM {user} u
                      JOIN {tmp_extuser} e ON (u.username = (".$this->get_sync_scope_sql_joinon_snippet('e.username').")
                            AND u.mnethostid = e.mnethostid)
                     WHERE (u.auth = 'nologin' OR (u.auth = ? AND u.suspended = 1)) AND u.deleted = 0";
            // Note: 'nologin' is there for backwards compatibility.
            $revive_users = $DB->get_records_sql($sql, array($this->config->sync_authtype));

            if (!empty($revive_users)) {
                mtrace(get_string('userentriestorevive', 'auth_ldap', count($revive_users)));

                foreach ($revive_users as $user) {
                    $updateuser = new stdClass();
                    $updateuser->id = $user->id;
                    $updateuser->auth = $this->config->sync_authtype;
                    $updateuser->suspended = 0;
                    user_update_user($updateuser, false);
                    mtrace("\t".get_string('auth_dbreviveduser', 'auth_db', array('name'=>$user->username, 'id'=>$user->id)));
                }

                mtrace(get_string('userentriestorevivedone', 'auth_ldap_syncplus', count($revive_users)));
            } else {
                mtrace(get_string('nouserentriestorevive', 'auth_ldap'));
            }
            unset($revive_users);

            // User temporary suspending.
            $sql = "SELECT u.*
                      FROM {user} u
                 LEFT JOIN {tmp_extuser} e ON (u.username = (".$this->get_sync_scope_sql_joinon_snippet('e.username').")
                        AND u.mnethostid = e.mnethostid)
                     WHERE u.auth = :auth
                           AND u.deleted = 0
                           AND u.suspended = 0
                           AND e.username IS NULL";
            $remove_users = $DB->get_records_sql($sql, array('auth'=>$this->config->sync_authtype));

            if (!empty($remove_users)) {
                mtrace(get_string('userentriestosuspend', 'auth_ldap_syncplus', count($remove_users)));

                foreach ($remove_users as $user) {
                    $updateuser = new stdClass();
                    $updateuser->id = $user->id;
                    $updateuser->suspended = 1;
                    $updateuser->timemodified = time(); // Remember suspend time, abuse timemodified column for this.
                    user_update_user($updateuser, false);
                    mtrace("\t".get_string('auth_dbsuspenduser', 'auth_db', array('name'=>$user->username, 'id'=>$user->id)));
                    \core\session\manager::destroy_user_sessions($user->id);
                }

                mtrace(get_string('userentriestosuspenddone', 'auth_ldap_syncplus', count($remove_users)));
            } else {
                mtrace(get_string('nouserentriestosuspend', 'auth_ldap_syncplus'));
            }
            unset($remove_users); // Free mem!

            // User complete removal.
            $sql = "SELECT u.*
                      FROM {user} u
                 LEFT JOIN {tmp_extuser} e ON (u.username = (".$this->get_sync_scope_sql_joinon_snippet('e.username').")
                        AND u.mnethostid = e.mnethostid)
                     WHERE u.auth = :auth
                           AND u.deleted = 0
                           AND e.username IS NULL";
            $remove_users = $DB->get_records_sql($sql, array('auth'=>$this->config->sync_authtype));

            if (!empty($remove_users)) {
                mtrace(get_string('userentriestoremove', 'auth_ldap', count($remove_users)));

                foreach ($remove_users as $user) {
                    // Do only if user was suspended before grace period.
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

                mtrace(get_string('userentriestoremovedone', 'auth_ldap_syncplus', count($remove_users)));
            } else {
                mtrace(get_string('nouserentriestoremove', 'auth_ldap'));
            }
            unset($remove_users); // Free mem!
        }

        // User Updates - time-consuming (optional).
        if ($updatecallback && $updatekeys = $this->get_profile_keys()) { // Run updates only if relevant.
            $users = $DB->get_records_sql('SELECT u.username, u.id
                                             FROM {user} u
                                            WHERE u.deleted = 0 AND u.auth = ? AND u.mnethostid = ?',
                                          array($this->config->sync_authtype, $CFG->mnet_localhost_id));
            if (!empty($users)) {
                // Update users in chunks as specified in sync_updateuserchunk.
                if (!empty($this->config->sync_updateuserchunk)) {
                    foreach (array_chunk($users, $this->config->sync_updateuserchunk) as $chunk) {
                        call_user_func($updatecallback, $chunk, $updatekeys);
                    }
                } else {
                    call_user_func($updatecallback, $users, $updatekeys);
                }
                unset($users); // Free mem.
            }
        } else {
            mtrace(get_string('noupdatestobedone', 'auth_ldap'));
        }

        // User Additions.
        // Find users missing in DB that are in LDAP
        // and gives me a nifty object I don't want.
        // note: we do not care about deleted accounts anymore, this feature was replaced by suspending to nologin auth plugin.
        if (!empty($this->config->sync_script_createuser_enabled) and $this->config->sync_script_createuser_enabled == 1) {
            $sql = 'SELECT e.id, e.username
                      FROM {tmp_extuser} e
                      LEFT JOIN {user} u ON (('.$this->get_sync_scope_sql_joinon_snippet('e.username').') = u.username
                            AND e.mnethostid = u.mnethostid)
                     WHERE u.id IS NULL';
            $add_users = $DB->get_records_sql($sql);

            if (!empty($add_users)) {
                mtrace(get_string('userentriestoadd', 'auth_ldap', count($add_users)));
                $errors = 0;

                foreach ($add_users as $user) {
                    $transaction = $DB->start_delegated_transaction();

                    // Get the user.
                    $user = $this->get_userinfo_asobj($user->username);

                    // Prep a few params.
                    $user->modified   = time();
                    $user->confirmed  = 1;
                    $user->auth       = $this->config->sync_authtype;
                    $user->mnethostid = $CFG->mnet_localhost_id;
                    // get_userinfo_asobj() might have replaced $user->username with the value
                    // from the LDAP server (which can be mixed-case). Make sure it's lowercase.
                    $user->username = trim(core_text::strtolower($user->username));
                    // It isn't possible to just rely on the configured suspension attribute since
                    // things like active directory use bit masks, other things using LDAP might
                    // do different stuff as well.
                    //
                    // The cast to int is a workaround for MDL-53959.
                    $user->suspended = (int)$this->is_user_suspended($user);

                    if (empty($user->calendartype)) {
                        $user->calendartype = $CFG->calendartype;
                    }

                    // Add the scope to the username for adding the user in the DB.
                    $user->username = $this->add_scope_to_username($user->username);

                    // $id = user_create_user($user, false);
                    try {
                        $id = user_create_user($user, false);
                    } catch (Exception $e) {
                        mtrace(get_string('invaliduserexception', 'auth_ldap', print_r($user, true) .  $e->getMessage()));
                        $errors++;
                        $transaction->allow_commit();
                        continue;
                    }
                    mtrace("\t".get_string('auth_dbinsertuser', 'auth_db', array('name'=>$user->username, 'id'=>$id)));
                    $euser = $DB->get_record('user', array('id' => $id));

                    if (!empty($this->config->forcechangepassword)) {
                        set_user_preference('auth_forcepasswordchange', 1, $id);
                    }

                    // Save custom profile fields.
                    $this->update_user_record($user->username, $this->get_profile_keys(true), false);

                    // Add roles if needed.
                    $this->sync_roles($euser);
                    $transaction->allow_commit();
                }

                // Display number of user creation errors, if any.
                if ($errors) {
                    mtrace(get_string('invalidusererrors', 'auth_ldap', $errors));
                }

                mtrace(get_string('userentriestoadddone', 'auth_ldap_syncplus', count($add_users)));

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
     * Update users from the external LDAP server into Moodle's user table.
     *
     * Sync helper
     *
     * @param array $users chunk of users to update
     * @param array $updatekeys fields to update
     */
    public function update_users(array $users, array $updatekeys): void {
        global $DB;

        mtrace(get_string('userentriestoupdate', 'auth_ldap', count($users)));

        foreach ($users as $user) {
            $transaction = $DB->start_delegated_transaction();
            echo "\t";
            print_string('auth_dbupdatinguser', 'auth_db', ['name' => $user->username, 'id' => $user->id]);
            $userinfo = $this->get_userinfo($user->username);
            if (!$this->update_user_record($user->username, $updatekeys, true,
                    $this->is_user_suspended((object) $userinfo))) {
                echo ' - '.get_string('skipped');
            }
            echo "\n";

            // Update system roles, if needed.
            $this->sync_roles($user);
            $transaction->allow_commit();
        }

        mtrace(get_string('userentriestoupdatedone', 'auth_ldap_syncplus', count($users)));
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

        // Clean username parameter to make sure that its an email address.
        $email = clean_param($frm->username, PARAM_EMAIL);

        // If we don't have an email adress, there's nothing to do, call parent hook and return.
        if ($email == '' || strpos($email, '@') == false) {
            parent::loginpage_hook(); // Call parent function to retain its functionality.
            return;
        }

        // If there is an existing useraccount with this email adress as email address (then a Moodle account already exists and
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

            // If there is no LDAP result or if the user was not found in this context, continue with next context.
            if (!$ldapresult || ldap_count_entries($ldapconnection, $ldapresult) == 0) {
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

    /**
     * Helper function to get the SQL snippet for the sync scope JOIN ON clause, if configured.
     *
     * @param string $userfield The userfield name
     * @return string
     */
    private function get_sync_scope_sql_joinon_snippet($userfield): string {
        global $DB;

        // If the sync auth method is still LDAP SyncPlus or if no sync scope is configured, return the userfield name as is.
        if ($this->config->sync_authtype == 'ldap_syncplus' || empty($this->config->sync_scope)) {
            return $userfield;
        }

        // If a sync scope is configured, we need to concat it to the userfield.
        $scopesql = $DB->sql_concat($userfield, "'".$this->config->sync_scope."'");

        // Return the SQL fragment.
        return $scopesql;
    }

    /**
     * Helper function to strip the sync scope from the username.
     *
     * @param string $username
     * @return string
     */
    private function strip_scope_from_username(string $username): string {
        // If the sync auth method is still LDAP SyncPlus or if no sync scope is configured, return the username as is.
        if ($this->config->sync_authtype == 'ldap_syncplus' || empty($this->config->sync_scope)) {
            return $username;
        }

        // If a sync scope is configured, we need to strip it from the username.
        return str_replace($this->config->sync_scope, '', $username);
    }

    /**
     * Helper function to add the sync scope to the username, if configured.
     *
     * @param string $username
     * @return string
     */
    private function add_scope_to_username(string $username): string {
        // If the sync auth method is still LDAP SyncPlus or if no sync scope is configured, return the username as is.
        if ($this->config->sync_authtype == 'ldap_syncplus' || empty($this->config->sync_scope)) {
            return $username;
        }

        // If a sync scope is configured, we need to add it to the username.
        return $username . $this->config->sync_scope;
    }

    /**
     * Helper function to determine the custom LDAP sync filter, if configured.
     *
     * @return string
     */
    private function get_ldap_sync_filter(): string {

        // If the sync auth method is still LDAP SyncPlus or if no sync filter is configured, return an empty string.
        if ($this->config->sync_authtype == 'ldap_syncplus' || empty($this->config->sync_filter)) {
            return '';
        }

        // If a sync filter is configured, return it.
        return $this->config->sync_filter;
    }
}
