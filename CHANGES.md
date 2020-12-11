moodle-auth_ldap_syncplus
=========================

Changes
-------

### v3.10-r1

* 2020-12-11 - Adopt code changes from Moodle 3.10 core auth_ldap.
* 2020-12-11 - Prepare compatibility for Moodle 3.10.
* 2020-12-10 - Change in Moodle release support:
               For the time being, this plugin is maintained for the most recent LTS release of Moodle as well as the most recent major release of Moodle.
               Bugfixes are backported to the LTS release. However, new features and improvements are not necessarily backported to the LTS release.
* 2020-12-10 - Improvement: Declare which major stable version of Moodle this plugin supports (see MDL-59562 for details).

### v3.9-r1

* 2020-09-18 - Prepare compatibility for Moodle 3.9.
* 2020-02-26 - Added Behat tests.

### v3.8-r1

* 2020-02-19 - Adopt code changes Moodle 3.8 core auth_ldap.
* 2020-02-19 - Prepare compatibility for Moodle 3.8.

### v3.7-r1

* 2019-08-15 - Make codechecker happy.
* 2019-08-15 - Prepare compatibility for Moodle 3.7.

### v3.6-r1

* 2019-01-29 - Check compatibility for Moodle 3.6, no functionality change.

### v3.5-r2

* 2019-01-29 - Adopt code changes Moodle 3.5 core auth_ldap (MDL-63887).
* 2018-12-05 - Changed travis.yml due to upstream changes.

### v3.5-r1

* 2018-06-25 - Bugfix: Creating users and first logins resulted in a fatal error in 3.5 because of a visibility change of update_user_record() in Moodle core.
* 2018-06-25 - Check compatibility for Moodle 3.5, no functionality change.

### v3.4-r4

* 2018-05-16 - Implement Privacy API.

### v3.4-r3

* 2018-02-07 - Bugfix: Login via email for first-time LDAP logins did not work if multiple LDAP contexts were configured; Credits to derhelge.

### v3.4-r2

* 2018-02-07 - Add forgotten sync_roles task definition

### v3.4-r1

* 2018-02-07 - Adopt code changes in Moodle 3.4 core auth_ldap: Assign arbitrary system roles via LDAP sync.
* 2018-02-06 - Check compatibility for Moodle 3.4, no functionality change.

### v3.3-r1

* 2018-02-02 - Adopt code changes in Moodle 3.3 core auth_ldap: Sync user profile fields
* 2018-02-02 - Adopt code changes in Moodle 3.3 core auth_ldap: Convert auth plugins to use settings.php. Please double-check your plugin settings after upgrading to this version.
* 2017-12-12 - Prepare compatibility for Moodle 3.3, no functionality change.
* 2017-12-05 - Added Workaround to travis.yml for fixing Behat tests with TravisCI.
* 2017-11-08 - Updated travis.yml to use newer node version for fixing TravisCI error.

### v3.2-r4

* 2017-05-29 - Add Travis CI support

### v3.2-r3

* 2017-05-05 - Improve README.md

### v3.2-r2

* 2017-03-03 - Adopt code changes in Moodle 3.2 core auth_ldap

### v3.2-r1

* 2017-01-13 - Check compatibility for Moodle 3.2, no functionality change
* 2017-01-13 - Adopt code changes in Moodle 3.2 core auth_ldap
* 2017-01-12 - Move Changelog from README.md to CHANGES.md

### v3.1-r1

* 2016-07-19 - Adopt code changes in Moodle core auth_ldap, adding the possibility to sync the "suspended" attribute
* 2016-07-19 - Check compatibility for Moodle 3.1, no functionality change

### Changes before v3.1

* 2016-03-20 - Edit README to reflect the current naming of the User account syncronisation setting, no functionality change
* 2016-02-10 - Change plugin version and release scheme to the scheme promoted by moodle.org, no functionality change
* 2016-01-01 - Adopt code changes in Moodle core auth_ldap, including the new scheduled task feature. If you have used a LDAP syncronization cron job before, please use the LDAP syncronisation scheduled task from now on (for details, see "Configuring LDAP synchronization task" section below)
* 2016-01-01 - Check compatibility for Moodle 3.0, no functionality change
* 2015-08-18 - Check compatibility for Moodle 2.9, no functionality change
* 2015-08-18 - Adopt a code change in Moodle core auth_ldap
* 2015-01-29 - Check compatibility for Moodle 2.8, no functionality change
* 2015-01-23 - Adopt a code change in Moodle core auth_ldap
* 2014-10-08 - Adopt a code change in Moodle core auth_ldap
* 2014-09-12 - Bugfix: Fetching user details from LDAP on manual user creation didn't work in some circumstances
* 2014-09-02 - Bugfix: Check if LDAP auth is really used on manual user creation
* 2014-08-29 - Support login via email for first-time LDAP logins (MDL-46638)
* 2014-08-29 - Update version.php
* 2014-08-29 - Update README file
* 2014-08-27 - Change line breaks to mtrace() (MDL-30589)
* 2014-08-25 - Support new event API, remove legacy event handling
* 2014-07-31 - Add event handler for "user_created" event (see "Fetching user details from LDAP on manual user creation" below for details - MDL-47029)
* 2014-06-30 - Check compatibility for Moodle 2.7, no functionality change
* 2014-03-12 - Initial version
