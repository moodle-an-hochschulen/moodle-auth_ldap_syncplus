moodle-auth_ldap_syncplus
=========================
Moodle authentication method which provides all functionality of auth_ldap, but supports advanced features for the LDAP synchronization script:

* It adds the possibility to the LDAP synchronization script to suspend users which have disappeared in LDAP for a configurable amount of days and delete them only after this grace period (the Moodle core LDAP synchronization script only provides you the option to suspend _or_ delete users which have disappeared in LDAP - MDL-47018).
* You can prevent the LDAP synchronization script from creating Moodle accounts for all LDAP users if they have never logged into Moodle before (the Moodle core LDAP synchronization script always creates Moodle accounts for all LDAP users - MDL-29249).
* You can fetch user details from LDAP on manual user creation (MDL-47029).
* It supports login via email for first-time LDAP logins (Moodle core only supports login via email for existing Moodle users - MDL-46638)
* It adds several line breaks to the output of the LDAP synchronization script to improve readability (MDL-30589).


Requirements
------------
This plugin requires Moodle 2.7+


Changes
-------
* 2014-09-02 - Bugfix: Check if LDAP auth is really used on manual user creation
* 2014-08-29 - Support login via email for first-time LDAP logins (MDL-46638)
* 2014-08-29 - Update version.php
* 2014-08-29 - Update README file
* 2014-08-27 - Change line breaks to mtrace() (MDL-30589)
* 2014-08-25 - Support new event API, remove legacy event handling
* 2014-07-31 - Add event handler for "user_created" event (see "Fetching user details from LDAP on manual user creation" below for details - MDL-47029)
* 2014-06-30 - Check compatibility for Moodle 2.7, no functionality change
* 2014-03-12 - Initial version


Installation
------------
Install the plugin like any other plugin to folder
/auth/ldap_syncplus

See http://docs.moodle.org/en/Installing_plugins for details on installing Moodle plugins


Usage & Settings
----------------
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
-----------------------------------
To leverage the additional LDAP synchronization features of auth_ldap_syncplus, you have to change your synchronization cronjob from /auth/ldap/cli/sync_users.php to /auth/ldap_syncplus/cli/sync_users.php.

If you don't know how to setup your synchronization cronjob, see http://docs.moodle.org/en/LDAP_authentication#Setting_up_regular_automatic_synchronisation_using_cron and the comments in /auth/ldap/cli/sync_users.php for details.


Fetching user details from LDAP on manual user creation
-------------------------------------------------------
Normally, when a new user logs into Moodle for the first time and a Moodle account is automatically created, Moodle pulls the user's details from LDAP and stores them in the Moodle user profile according to the LDAP plugin's settings.

auth_ldap_syncplus extends this behaviour of pulling user details from LDAP:
With auth_ldap_syncplus, you can create an user manually on Site Administration -> Users -> Accounts -> Add a new user. The only thing you have to specify correctly is the username (which corresponds to the username in LDAP). All other details like first name or email address can be filled with placeholder content. After you click the "Create user" button, Moodle pulls the other user's details from LDAP and creates the user account correctly with the details from LDAP.

This feature is enabled automatically and can be used as soon as you are using auth_ldap_syncplus as your LDAP authentication plugin like described above.


Themes
------
The auth_ldap_syncplus plugin acts behind the scenes, therefore it works with all moodle themes.


Further information
-------------------
Report a bug or suggest an improvement: https://github.com/moodleuulm/moodle-auth_ldap_syncplus/issues


Moodle release support
----------------------
Due to limited ressources, auth_ldap_syncplus is only maintained for the most recent major release of Moodle. However, previous versions of this plugin which work in legacy major releases of Moodle are still available as-is without any further updates in the Moodle Plugins repository.

There may be several weeks after a new major release of Moodle has been published until we can do a compatibility check and fix problems if necessary. If you encounter problems with a new major release of Moodle - or can confirm that auth_ldap_syncplus still works with a new major relase - please let us know on https://github.com/moodleuulm/moodle-auth_ldap_syncplus/issues


Right-to-left support
---------------------
This plugin has not been tested with Moodle's support for right-to-left (RTL) languages.
If you want to use this plugin with a RTL language and it doesn't work as-is, you are free to send me a pull request on
github with modifications.


Contribution to Moodle Core
---------------------------
There is a Moodle tracker ticket on https://tracker.moodle.org/browse/MDL-47030 which proposes to add the improved features of this plugin to Moodle core auth_ldap plugin.

Please vote for this ticket if you want to have this realized.


Copyright
---------
University of Ulm
kiz - Media Department
Team Web & Teaching Support
Alexander Bias
