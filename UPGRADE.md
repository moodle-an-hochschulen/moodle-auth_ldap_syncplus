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

* The plugin has a good coverage with Behat tests which test most of the plugin's user stories.
* To run the automated tests, a running LDAP server is necessary. This is realized in the Github actions workflow. If you want to run the automated tests locally, you have to adapt the tests to a local LDAP server yourself.
If you do not have a running LDAP server at hand, you can try to spin up the Bitnami LDAP server which is used in Github actions with this docker-compose command:
```
docker-compose -p ldap -f auth/ldap_syncplus/tests/fixtures/bitnami-openldap-docker-compose.yaml up
```


Manual tests
------------

* Even though there are automated tests, as the plugin deals with the communication to a backend system, manual tests should be carried out to see if the plugin's functionality really works with a real LDAP server.
* Additionally, if you look at the Behat feature file, you will see that there are some scenarios still commented out. If you have time, you should test them manually or write a Behat test for it.


Visual checks
-------------

* There aren't any additional visual checks in the Moodle GUI needed to upgrade this plugin.
