moodle-auth_ldap_syncplus
=========================

[![Build Status](https://travis-ci.com/moodleuulm/moodle-auth_ldap_syncplus.svg?branch=master)](https://travis-ci.com/moodleuulm/moodle-auth_ldap_syncplus)

Moodle authentication plugin which provides all functionality of auth_ldap, but supports advanced features for the LDAP synchronization task and LDAP authentication.


Requirements
------------

This plugin requires Moodle 3.10+


Motivation for this plugin
--------------------------

Moodle core's auth_ldap authentication plugin is a great basis for authenticating users in Moodle. However, as Moodle core's auth_ldap is somehow limited in several aspects and there is no prospect to have it improved in Moodle core, we have implemented an extended version for LDAP authentication with these key features:

* The most important part: All functions from auth_ldap are still working if you use this authentication plugin.

* The plugin adds the possibility to the LDAP synchronization task to suspend users which have disappeared in LDAP for a configurable amount of days and delete them only after this grace period (the Moodle core LDAP synchronization task only provides you the option to suspend _or_ delete users which have disappeared in LDAP - MDL-47018).

* You can prevent the LDAP synchronization task from creating Moodle accounts for all LDAP users if they have never logged into Moodle before (the Moodle core LDAP synchronization task always creates Moodle accounts for all LDAP users - MDL-29249).

* You can fetch user details from LDAP on manual user creation (MDL-47029).

* It supports login via email for first-time LDAP logins (Moodle core only supports login via email for existing Moodle users - MDL-46638)

* It adds several line breaks to the output of the LDAP synchronization task to improve readability (MDL-30589).


Installation
------------

Install the plugin like any other plugin to folder
/auth/ldap_syncplus

See http://docs.moodle.org/en/Installing_plugins for details on installing Moodle plugins


Usage & Settings
----------------

After installing the plugin, it does not do anything to Moodle yet.

To configure the plugin and its behaviour, please visit:
Site administration -> Plugins -> Authentication -> Manage authentication -> LDAP server (Sync Plus)

There, you configure the plugin with the same settings like you would configure the Moodle core LDAP authentication method.

Please note that there are additional setting items in settings section "User account synchronisation" compared to the Moodle core LDAP authentication method:

### 1. Removed ext user

The setting "Removed ext user" has an additional option called "Suspend internal and fully delete internal after grace period". If you select this option, the synchronization task will suspend users which have disappeared in LDAP for a configurable amount of days and delete them only after this grace period. If the user reappears in LDAP within the grace period, his Moodle account is revived and he can login again into Moodle as he did before.

### 2. Fully deleting grace period

With the setting "Fully deleting grace period" (Default: 10 days), you can control the length of the grace period until a user account is fully deleted after it has disappeared from LDAP.

### 3. Add new users

With the setting "Add new users" (Default: yes), you can prevent the synchronization task from creating Moodle accounts for all LDAP users if they have never logged into Moodle before.

After configuring the LDAP server (Sync Plus) authentication method, you have to activate the plugin on Site administration -> Plugins -> Authentication -> Manage authentication so that users can be authenticated with this authentication method. Afterwards, you can deactivate the Moodle core LDAP authentication method as it is not needed anymore actively.


Configuring LDAP User account synchronisation
---------------------------------------------

To leverage the additional LDAP synchronization features of auth_ldap_syncplus, you have to disable the scheduled task of the Moodle core auth_ldap plugin and activate and configure the scheduled task of auth_ldap_syncplus. This is done on Site administration -> Server -> Scheduled tasks.

If you don't know how to setup LDAP User account synchronisation at all, see https://docs.moodle.org/en/LDAP_authentication#Enabling_the_LDAP_users_sync_job.


Configuring LDAP User role synchronisation
------------------------------------------

In addition to the LDAP user account synchronisation, there is a LDAP user role synchronisation. LDAP user role synchronisation task in auth_ldap_syncplus does not provide any benefits over the LDAP user role synchronisation in Moodle core auth_ldap yet. However, to keep things in one place and if you want to synchronize LDAP user roles, you should activate and configure the scheduled task of auth_ldap_syncplus instead of auth_ldap. This is done on Site administration -> Server -> Scheduled tasks.

If you don't know about the LDAP user role synchronisation at all, see https://docs.moodle.org/en/LDAP_authentication#Assign_system_roles.


Migrating from auth_ldap to auth_ldap_syncplus
----------------------------------------------

If you already have users in your Moodle installation who authenticate using the auth_ldap authentication method and want to switch them to auth_ldap_syncplus, proceed this way:

* Configure auth_ldap_syncplus as an _additional_ authentication method while keeping auth_ldap activated.

* Create a test user and set his authentication method to auth_ldap_syncplus. Test if this user is able to log into Moodle properly.

* Switch all existing users to the auth_ldap_syncplus authentication method by running the following SQL command in your Moodle database:
`UPDATE mdl_user SET auth='ldap_syncplus' WHERE auth='ldap'`

* Disable auth_ldap authentication method.


Fetching user details from LDAP on manual user creation
-------------------------------------------------------

Normally, when a new user logs into Moodle for the first time and a Moodle account is automatically created, Moodle pulls the user's details from LDAP and stores them in the Moodle user profile according to the LDAP plugin's settings.

auth_ldap_syncplus extends this behaviour of pulling user details from LDAP:
With auth_ldap_syncplus, you can create an user manually on Site administration -> Users -> Accounts -> Add a new user. The only thing you have to specify correctly is the username (which corresponds to the username in LDAP). All other details like first name or email address can be filled with placeholder content. After you click the "Create user" button, Moodle pulls the other user's details from LDAP and creates the user account correctly with the details from LDAP.

This feature is enabled automatically and can be used as soon as you are using auth_ldap_syncplus as your LDAP authentication plugin like described above.


How this plugin works
---------------------

This plugin is implemented with minimal code duplication in mind. It inherits / requires as much code as possible from auth_ldap and only implements the extended functionalities.


Theme support
-------------

This plugin acts behind the scenes, therefore it should work with all Moodle themes.
This plugin is developed and tested on Moodle Core's Boost theme.
It should also work with Boost child themes, including Moodle Core's Classic theme. However, we can't support any other theme than Boost.

Plugin repositories
-------------------

This plugin is published and regularly updated in the Moodle plugins repository:
http://moodle.org/plugins/view/auth_ldap_syncplus

The latest development version can be found on Github:
https://github.com/moodleuulm/moodle-auth_ldap_syncplus


Bug and problem reports / Support requests
------------------------------------------

This plugin is carefully developed and thoroughly tested, but bugs and problems can always appear.

Please report bugs and problems on Github:
https://github.com/moodleuulm/moodle-auth_ldap_syncplus/issues

We will do our best to solve your problems, but please note that due to limited resources we can't always provide per-case support.


Feature proposals
-----------------

Due to limited resources, the functionality of this plugin is primarily implemented for our own local needs and published as-is to the community. We are aware that members of the community will have other needs and would love to see them solved by this plugin.

Please issue feature proposals on Github:
https://github.com/moodleuulm/moodle-auth_ldap_syncplus/issues

Please create pull requests on Github:
https://github.com/moodleuulm/moodle-auth_ldap_syncplus/pulls

We are always interested to read about your feature proposals or even get a pull request from you, but please accept that we can handle your issues only as feature _proposals_ and not as feature _requests_.


Moodle release support
----------------------

Due to limited resources, this plugin is only maintained for the most recent major release of Moodle as well as the most recent LTS release of Moodle. Bugfixes are backported to the LTS release. However, new features and improvements are not necessarily backported to the LTS release.

Apart from these maintained releases, previous versions of this plugin which work in legacy major releases of Moodle are still available as-is without any further updates in the Moodle Plugins repository.

There may be several weeks after a new major release of Moodle has been published until we can do a compatibility check and fix problems if necessary. If you encounter problems with a new major release of Moodle - or can confirm that this plugin still works with a new major release - please let us know on Github.

If you are running a legacy version of Moodle, but want or need to run the latest version of this plugin, you can get the latest version of the plugin, remove the line starting with $plugin->requires from version.php and use this latest plugin version then on your legacy Moodle. However, please note that you will run this setup completely at your own risk. We can't support this approach in any way and there is an undeniable risk for erratic behavior.


Translating this plugin
-----------------------

This Moodle plugin is shipped with an english language pack only. All translations into other languages must be managed through AMOS (https://lang.moodle.org) by what they will become part of Moodle's official language pack.

As the plugin creator, we manage the translation into german for our own local needs on AMOS. Please contribute your translation into all other languages in AMOS where they will be reviewed by the official language pack maintainers for Moodle.


Right-to-left support
---------------------

This plugin has not been tested with Moodle's support for right-to-left (RTL) languages.
If you want to use this plugin with a RTL language and it doesn't work as-is, you are free to send us a pull request on Github with modifications.


Contribution to Moodle Core
---------------------------

There is a Moodle tracker ticket on https://tracker.moodle.org/browse/MDL-47030 which proposes to add the improved features of this plugin to Moodle core auth_ldap plugin.

Please vote for this ticket if you want to have this realized.


PHP7 Support
------------

Since Moodle 3.4 core, PHP7 is mandatory. We are developing and testing this plugin for PHP7 only.


Copyright
---------

Ulm University
Communication and Information Centre (kiz)
Alexander Bias
