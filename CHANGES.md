moodle-auth_ldap_syncplus
=========================

Changes
-------

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
