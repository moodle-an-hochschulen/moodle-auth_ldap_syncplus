moodle-auth_ldap_syncplus
=========================
Moodle authentication method which provides all functionality of auth_ldap, but supports advanced features for the LDAP synchronization script: On the one hand, it adds the possibility to the LDAP synchronization script to suspend users which have disappeared in LDAP for a configurable amount of days and delete them only after this grace period (the Moodle core LDAP synchronization script only provides you the option to suspend _or_ delete users which have disappeared in LDAP). On the other hand, you can prevent the LDAP synchronization script from creating Moodle accounts for all LDAP users if they have never logged into Moodle before (the Moodle core LDAP synchronization script always creates Moodle accounts for all LDAP users).


Requirements
============
This plugin requires Moodle 2.6+


Changes
=======
2014-03-12 - Initial version


Installation
============
Install the plugin like any other plugin to folder
/auth/ldap_syncplus

See http://docs.moodle.org/26/en/Installing_plugins for details on installing Moodle plugins


Usage & Settings
================
auth_ldap_syncplus is a plugin which inherits and reuses most of the code from the Moodle core auth_ldap plugin. All functions from auth_ldap are still working.

After installing auth_ldap_syncplus, you will find a new authentification method on the admin page /admin/settings.php?section=manageauths.

To make use of this plugin, you have to configure it on admin page /admin/auth_config.php?auth=ldap_syncplus with the same settings like you would configure the Moodle core LDAP authentication method.

Please note that there are additional setting items in settings section "Cron synchronization script" compared to the Moodle core LDAP authentication method:

1. Setting "Removed ext user" has an additional option called "Suspend internal and fully delete internal after grace period". If you select this option, the synchronization script will suspend users which have disappeared in LDAP for a configurable amount of days and delete them only after this grace period. If the user reappears in LDAP within the grace period, his Moodle account is revived and he can login again into Moodle as he did before.

2. Setting "Fully deleting grace period": With this setting (Default: 10 days), you can control the length of the grace period until a user account is fully deleted after it has disappeared from LDAP.

3. Setting "Add new users": With this setting (Default: yes), you can prevent the synchronization script from creating Moodle accounts for all LDAP users if they have never logged into Moodle before.

After configuring the LDAP SyncPlus authentication method, you should activate the plugin on admin page /admin/settings.php?section=manageauths so that users can be authenticated with this authentication method. Afterwars, you can deactivate the Moodle core LDAP authentication method as it is not needed anymore actively.

Note: If you already have users in your Moodle installation who authenticate using the core "ldap" authentication method, you should switch them to the new authentication method "ldap_syncplus" by running the following SQL command in your Moodle database:
UPDATE mdl_user SET auth='ldap_syncplus' WHERE auth='ldap'


Running LDAP synchronization script
===================================
To leverage the additional synchronization features of auth_ldap_syncplus, you have to change your synchronization cronjob from /auth/ldap/cli/sync_users.php to /auth/ldap_syncplus/cli/sync_users.php.

If you don't know how to setup your synchronization cronjob, see http://docs.moodle.org/26/en/LDAP_authentication#Setting_up_regular_automatic_synchronisation_using_cron and the comments in /auth/ldap/cli/sync_users.php for details.


Themes
======
The auth_ldap_syncplus plugin acts behind the scenes, therefore it works with all moodle themes.


Further information
===================
Report a bug or suggest an improvement: https://github.com/moodleuulm/moodle-auth_ldap_syncplus/issues


Moodle release support
======================
Due to limited ressources, auth_ldap_syncplus is only maintained for the most recent major release of Moodle. However, previous versions of this plugin which work in legacy major releases of Moodle are still available as-is without any further updates in the Moodle Plugins repository.

There may be several weeks after a new major release of Moodle has been published until I can do a compatibility check and fix problems if necessary. If you encounter problems with a new major release of Moodle - or can confirm that auth_ldap_syncplus still works with a new major relase - please let me know on https://github.com/moodleuulm/moodle-auth_ldap_syncplus/issues


Right-to-left support
=====================
This plugin has not been tested with Moodle's support for right-to-left (RTL) languages.
If you want to use this plugin with a RTL language and it doesn't work as-is, you are free to send me a pull request on
github with modifications.


Copyright
=========
Alexander Bias, University of Ulm
