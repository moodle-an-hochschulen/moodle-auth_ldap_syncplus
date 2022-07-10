Upgrading this plugin
=====================

This is an internal documentation for plugin developers with some notes what has to be considered when updating this plugin to a new Moodle major version.

General
-------

* Generally, this is a quite simple plugin with just one purpose.
* It does not rely on any fluctuating library functions and should remain quite stable between Moodle major versions.
* However, as it deals with the communication to a backend system, things are slightly more complicated. 
* Thus, the upgrading effort is medium.


Upstream changes
----------------

* This plugin is built on top of auth_ldap from Moodle core. It inherits the codebase from auth_ldap and overwrites and extends some functions. Doing this, code duplication couldn't be avoided. If there are any upstream changes in auth_ldap, you should check if they should be adopted to this plugin as well.


Automated tests
---------------

* The plugin has a coverage of Behat tests which test if the admin settings widgets for the plugin's extended functions are shown in the admin settings. They do _not_ test any communication with a LDAP server.


Manual tests
------------

* As the plugin deals with the communication to a backend system, manual tests should be carried out to see if the extended functionality still works with a real LDAP server.
