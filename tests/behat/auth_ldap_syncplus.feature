@auth @auth_ldap_syncplus
Feature: Checking that all LDAP (Sync Plus) specific settings are working
  In order to be able to use the auth_ldap_syncplus plugin properly
  As admin
  I need to be sure that LDAP (Sync Plus) provides working additional features over the LDAP plugin from Moodle core.

  Background:
    Given the following config values are set as admin:
      | config                     | value                |
      | auth                       | manual,ldap_syncplus |
      | authpreventaccountcreation | 0                    |
    And the following config values are set as admin:
      | config                      | value                           | plugin             |
      | host_url                    | ldap://localhost:1389           | auth_ldap_syncplus |
      | start_tls                   | 0                               | auth_ldap_syncplus |
      | bind_dn                     | cn=admin,dc=example,dc=org      | auth_ldap_syncplus |
      | bind_pw                     | adminpassword                   | auth_ldap_syncplus |
      | contexts                    | ou=department,dc=example,dc=org | auth_ldap_syncplus |
      | search_sub                  | 1                               | auth_ldap_syncplus |
      | user_attribute              | uid                             | auth_ldap_syncplus |
      | field_map_firstname         | givenName                       | auth_ldap_syncplus |
      | field_updatelocal_firstname | onlogin                         | auth_ldap_syncplus |
      | field_map_lastname          | sn                              | auth_ldap_syncplus |
      | field_updatelocal_lastname  | onlogin                         | auth_ldap_syncplus |
      | field_map_email             | mail                            | auth_ldap_syncplus |
      | field_updatelocal_email     | onlogin                         | auth_ldap_syncplus |

  Scenario: All additional LDAP server (Sync Plus) settings should be there
    Given the following config values are set as admin:
      | config        | value      | plugin             |
      | sync_authtype | shibboleth | auth_ldap_syncplus |
    When I log in as "admin"
    And I navigate to "Plugins > Authentication > Manage authentication" in site administration
    And I click on "Settings" "link" in the "LDAP server (Sync Plus)" "table_row"
    Then I should see "LDAP server (Sync Plus)" in the "#region-main .settingsform" "css_element"
    And the "Removed ext user" select box should contain "Suspend internal and fully delete internal after grace period"
    And I should see "Fully deleting grace period" in the "#admin-removeuser_graceperiod" "css_element"
    And I should see "Add new users" in the "#admin-sync_script_createuser_enabled" "css_element"
    And I should see "Moodle authentication type when synchronizing users" in the "#region-main .settingsform" "css_element"
    And I should see "Username scope when synchronizing users" in the "#region-main .settingsform" "css_element"
    And I should see "LDAP filter when synchronizing users" in the "#region-main .settingsform" "css_element"
    And I should see "Ignore users outside the configured username scope" in the "#region-main .settingsform" "css_element"

  Scenario: The LDAP connection should work
    When I log in as "admin"
    And I navigate to "Plugins > Authentication > Manage authentication" in site administration
    And I click on "Test settings" "link" in the "LDAP server (Sync Plus)" "table_row"
    And I should see "Connecting to your LDAP server was successful"

  Scenario: The LDAP synchronization task should suspend users who have disappeared in LDAP and delete them after a configurable grace period
    Given the following config values are set as admin:
      | config                 | value | plugin             |
      | removeuser             | 3     | auth_ldap_syncplus |
      | removeuser_graceperiod | 2     | auth_ldap_syncplus |
    # user01 exists in the LDAP server, user03 does not.
    And the following "users" exist:
      | username | firstname | lastname | email              | auth          |
      | user01   | User      | 01       | user01@example.org | ldap_syncplus |
      | user03   | User      | 03       | user03@example.org | ldap_syncplus |
    When I log in as "admin"
    And I navigate to "Users > Accounts > Browse list of users" in site administration
    And I should see "User 01" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I should see "User 03" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I should not see "Suspended" in the "User 01" "table_row"
    And I should not see "Suspended" in the "User 03" "table_row"
    And I run the scheduled task "\auth_ldap_syncplus\task\sync_task"
    And I reload the page
    Then I should see "User 01" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I should see "User 03" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I should not see "Suspended" in the "User 01" "table_row"
    And I should see "Suspended" in the "User 03" "table_row"
    And I pretend the suspended user "user03" was suspended "3" days ago
    And I run the scheduled task "\auth_ldap_syncplus\task\sync_task"
    And I reload the page
    Then I should see "User 01" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I should not see "User 03" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I should not see "Suspended" in the "User 01" "table_row"

  Scenario: The LDAP synchronization task should revive suspended users who have re-appeared in LDAP within the grace period
    Given the following config values are set as admin:
      | config                 | value | plugin             |
      | removeuser             | 3     | auth_ldap_syncplus |
      | removeuser_graceperiod | 2     | auth_ldap_syncplus |
    And the following "users" exist:
      # user01 and user02 exist in the LDAP server.
      | username | firstname | lastname | email              | auth          |
      | user01   | User      | 01       | user01@example.org | ldap_syncplus |
      | user02   | User      | 02       | user02@example.org | ldap_syncplus |
    When I log in as "admin"
    And I pretend the suspended user "user02" was suspended "1" days ago
    And I navigate to "Users > Accounts > Browse list of users" in site administration
    And I should see "User 01" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I should see "User 02" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I should not see "Suspended" in the "User 01" "table_row"
    And I should see "Suspended" in the "User 02" "table_row"
    And I run the scheduled task "\auth_ldap_syncplus\task\sync_task"
    And I reload the page
    Then I should see "User 01" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I should see "User 02" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I should not see "Suspended" in the "User 01" "table_row"
    And I should not see "Suspended" in the "User 02" "table_row"

  Scenario: The LDAP synchronization task should suspend users who have disappeared in LDAP (Countercheck / Moodle core behaviour)
    Given the following config values are set as admin:
      | config                 | value | plugin             |
      | removeuser             | 1     | auth_ldap_syncplus |
    # user01 exists in the LDAP server, user03 does not.
    And the following "users" exist:
      | username | firstname | lastname | email              | auth          |
      | user01   | User      | 01       | user01@example.org | ldap_syncplus |
      | user03   | User      | 03       | user03@example.org | ldap_syncplus |
    When I log in as "admin"
    And I navigate to "Users > Accounts > Browse list of users" in site administration
    And I should see "User 01" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I should see "User 03" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I should not see "Suspended" in the "User 01" "table_row"
    And I should not see "Suspended" in the "User 03" "table_row"
    And I run the scheduled task "\auth_ldap_syncplus\task\sync_task"
    And I reload the page
    Then I should see "User 01" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I should see "User 03" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I should not see "Suspended" in the "User 01" "table_row"
    And I should see "Suspended" in the "User 03" "table_row"

  Scenario: The LDAP synchronization task should revive suspended users who have re-appeared in LDAP after they have been suspended
    Given the following config values are set as admin:
      | config                 | value | plugin             |
      | removeuser             | 1     | auth_ldap_syncplus |
    And the following "users" exist:
      # user01 and user02 exist in the LDAP server.
      | username | firstname | lastname | email              | auth          |
      | user01   | User      | 01       | user01@example.org | ldap_syncplus |
      | user02   | User      | 02       | user02@example.org | ldap_syncplus |
    When I log in as "admin"
    And I pretend the suspended user "user02" was suspended "1" days ago
    And I navigate to "Users > Accounts > Browse list of users" in site administration
    And I should see "User 01" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I should see "User 02" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I should not see "Suspended" in the "User 01" "table_row"
    And I should see "Suspended" in the "User 02" "table_row"
    And I run the scheduled task "\auth_ldap_syncplus\task\sync_task"
    And I reload the page
    Then I should see "User 01" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I should see "User 02" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I should not see "Suspended" in the "User 01" "table_row"
    And I should not see "Suspended" in the "User 02" "table_row"

  Scenario: The LDAP synchronization task should delete users who have disappeared in LDAP (Countercheck / Moodle core behaviour)
    Given the following config values are set as admin:
      | config                 | value | plugin             |
      | removeuser             | 2     | auth_ldap_syncplus |
    # user01 exists in the LDAP server, user03 does not.
    And the following "users" exist:
      | username | firstname | lastname | email              | auth          |
      | user01   | User      | 01       | user01@example.org | ldap_syncplus |
      | user03   | User      | 03       | user03@example.org | ldap_syncplus |
    When I log in as "admin"
    And I navigate to "Users > Accounts > Browse list of users" in site administration
    And I should see "User 01" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I should see "User 03" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I should not see "Suspended" in the "User 01" "table_row"
    And I should not see "Suspended" in the "User 03" "table_row"
    And I run the scheduled task "\auth_ldap_syncplus\task\sync_task"
    And I reload the page
    Then I should see "User 01" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I should not see "User 03" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I should not see "Suspended" in the "User 01" "table_row"

  Scenario: The LDAP synchronization task should not create Moodle accounts for all LDAP users
    Given the following config values are set as admin:
      | config                         | value | plugin             |
      | sync_script_createuser_enabled | 0     | auth_ldap_syncplus |
    When I log in as "admin"
    And I navigate to "Users > Accounts > Browse list of users" in site administration
    And I should not see "User 01" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I run the scheduled task "\auth_ldap_syncplus\task\sync_task"
    And I reload the page
    Then I should not see "User 01" in the "[data-region='report-user-list-wrapper']" "css_element"

  Scenario: The LDAP synchronization task should create Moodle accounts for all LDAP users (Countercheck / Moodle core behaviour)
    Given the following config values are set as admin:
      | config                         | value | plugin             |
      | sync_script_createuser_enabled | 1     | auth_ldap_syncplus |
    When I log in as "admin"
    And I navigate to "Users > Accounts > Browse list of users" in site administration
    And I should not see "User 01" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I run the scheduled task "\auth_ldap_syncplus\task\sync_task"
    And I reload the page
    Then I should see "User 01" in the "[data-region='report-user-list-wrapper']" "css_element"

  Scenario: On manual user creation, user details should be fetched from LDAP
    When I log in as "admin"
    And I navigate to "Users > Accounts > Add a new user" in site administration
    And I set the following fields to these values:
      | Username                        | user01        |
      | Choose an authentication method | ldap_syncplus |
      | First name                      | Foo           |
      | Last name                       | Bar           |
      | Email address                   | foo@bar.org   |
      | New password                    | Hello!123     |
    And I press "Create user"
    And I navigate to "Users > Accounts > Browse list of users" in site administration
    Then I should see "User 01" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I click on "Edit" "link" in the "User 01" "table_row"
    And the field "Username" matches value "user01"
    And the field "First name" matches value "User"
    And the field "Last name" matches value "01"
    And the field "Email address" matches value "user01@example.org"

  Scenario: First login via email should be possible without an existing Moodle account
    Given the following config values are set as admin:
      | config            | value |
      | authloginviaemail | 1     |
    # Navigate to the frontpage which redirects to the login page as forcelogin is enabled
    When I am on site homepage
    And I set the field "Username" to "user01@example.org"
    And I set the field "Password" to "password1"
    And I press "Log in"
    Then I should see "Welcome, User"
    And I should not see "Invalid login"

  Scenario: First login via username should be possible without an existing Moodle account (Countercheck / Moodle core behaviour)
    # Navigate to the frontpage which redirects to the login page as forcelogin is enabled
    When I am on site homepage
    And I set the field "Username" to "user01"
    And I set the field "Password" to "password1"
    And I press "Log in"
    Then I should see "Welcome, User"
    And I should not see "Invalid login"

  Scenario: Update user profile fields from LDAP (Moodle core behaviour)
    Given the following "users" exist:
      # user01 exists in the LDAP server.
      | username | firstname | lastname | email              | auth          |
      | user01   | User      | 01       | user01@example.org | ldap_syncplus |
    When I log in as "admin"
    And I navigate to "Users > Accounts > Browse list of users" in site administration
    And I should see "User 01" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I should not see "Foo Bar" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I click on "Edit" "link" in the "User 01" "table_row"
    And I set the following fields to these values:
      | First name    | Foo         |
      | Last name     | Bar         |
      | Email address | foo@bar.org |
    And I press "Update profile"
    And I navigate to "Users > Accounts > Browse list of users" in site administration
    And I should not see "User 01" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I should see "Foo Bar" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I run the scheduled task "\auth_ldap_syncplus\task\sync_task"
    And I run all adhoc tasks
    And I reload the page
    Then I should see "User 01" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I should not see "Foo Bar" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I click on "Edit" "link" in the "User 01" "table_row"
    And the field "Username" matches value "user01"
    And the field "First name" matches value "User"
    And the field "Last name" matches value "01"
    And the field "Email address" matches value "user01@example.org"

  # From here on, we are testing the LDAP synchronization task with non-LDAP users.

  Scenario Outline: The LDAP synchronization task can be re-used for creating non-LDAP users
    Given the following config values are set as admin:
      | config        | value                          | plugin             |
      | contexts      | ou=<context>,dc=example,dc=org | auth_ldap_syncplus |
      | sync_authtype | <authtype>                     | auth_ldap_syncplus |
      | sync_scope    | <scope>                        | auth_ldap_syncplus |
      | sync_filter   | <filter>                       | auth_ldap_syncplus |
    When I log in as "admin"
    And I navigate to "Users > Accounts > Browse list of users" in site administration
    And I should not see "User <user1number>" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I run the scheduled task "\auth_ldap_syncplus\task\sync_task"
    And I reload the page
    Then I should see "User <user1number>" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I press "Edit" action in the "User <user1number>" report row
    And the field "Choose an authentication method" matches value "<authlabel>"
    And the field "Username" matches value "user<user1number><scope>"

    Examples:
      | authtype      | authlabel               | context    | scope        | filter  | user1number |
      | ldap_syncplus | LDAP server (Sync Plus) | department |              |         | 01          |
      | shibboleth    | Shibboleth              | department |              |         | 01          |
      | shibboleth    | Shibboleth              | scoped     |              |         | 11          |
      | shibboleth    | Shibboleth              | scoped     | @example.org |         | 11          |
      | shibboleth    | Shibboleth              | department |              | (uid=*) | 01          |
      | shibboleth    | Shibboleth              | scoped     |              | (uid=*) | 11          |
      | shibboleth    | Shibboleth              | scoped     | @example.org | (uid=*) | 11          |

  Scenario Outline: The LDAP synchronization task can be re-used for deleting non-LDAP users
    Given the following config values are set as admin:
      | config        | value                          | plugin             |
      | contexts      | ou=<context>,dc=example,dc=org | auth_ldap_syncplus |
      | removeuser    | 2                              | auth_ldap_syncplus |
      | sync_authtype | <authtype>                     | auth_ldap_syncplus |
      | sync_scope    | <scope>                        | auth_ldap_syncplus |
      | sync_filter   | <filter>                       | auth_ldap_syncplus |
    And the following "users" exist:
      | username      | firstname | lastname | email              | auth       |
      | user03<scope> | User      | 03       | user03@example.org | <authtype> |
    When I log in as "admin"
    And I navigate to "Users > Accounts > Browse list of users" in site administration
    And I should see "User 03" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I run the scheduled task "\auth_ldap_syncplus\task\sync_task"
    And I reload the page
    Then I should not see "User 03" in the "[data-region='report-user-list-wrapper']" "css_element"

    Examples:
      | authtype      | authlabel               | context    | scope        | filter  |
      | ldap_syncplus | LDAP server (Sync Plus) | department |              |         |
      | shibboleth    | Shibboleth              | department |              |         |
      | shibboleth    | Shibboleth              | scoped     |              |         |
      | shibboleth    | Shibboleth              | scoped     | @example.org |         |
      | shibboleth    | Shibboleth              | department |              | (uid=*) |
      | shibboleth    | Shibboleth              | scoped     |              | (uid=*) |
      | shibboleth    | Shibboleth              | scoped     | @example.org | (uid=*) |

  Scenario Outline: The LDAP synchronization task can be re-used for suspending non-LDAP users
    Given the following config values are set as admin:
      | config        | value                          | plugin             |
      | contexts      | ou=<context>,dc=example,dc=org | auth_ldap_syncplus |
      | removeuser    | 1                              | auth_ldap_syncplus |
      | sync_authtype | <authtype>                     | auth_ldap_syncplus |
      | sync_scope    | <scope>                        | auth_ldap_syncplus |
      | sync_filter   | <filter>                       | auth_ldap_syncplus |
    And the following "users" exist:
      | username      | firstname | lastname | email              | auth       |
      | user03<scope> | User      | 03       | user03@example.org | <authtype> |
    When I log in as "admin"
    And I navigate to "Users > Accounts > Browse list of users" in site administration
    And I should see "User 03" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I should not see "Suspended" in the "User 03" "table_row"
    And I run the scheduled task "\auth_ldap_syncplus\task\sync_task"
    And I reload the page
    Then I should see "User 03" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I should see "Suspended" in the "User 03" "table_row"

    Examples:
      | authtype      | authlabel               | context    | scope        | filter  |
      | ldap_syncplus | LDAP server (Sync Plus) | department |              |         |
      | shibboleth    | Shibboleth              | department |              |         |
      | shibboleth    | Shibboleth              | scoped     |              |         |
      | shibboleth    | Shibboleth              | scoped     | @example.org |         |
      | shibboleth    | Shibboleth              | department |              | (uid=*) |
      | shibboleth    | Shibboleth              | scoped     |              | (uid=*) |
      | shibboleth    | Shibboleth              | scoped     | @example.org | (uid=*) |

  Scenario Outline: The LDAP synchronization task can be re-used for un-suspending non-LDAP users
    Given the following config values are set as admin:
      | config                         | value                          | plugin             |
      | contexts                       | ou=<context>,dc=example,dc=org | auth_ldap_syncplus |
      | removeuser                     | 1                              | auth_ldap_syncplus |
      | sync_script_createuser_enabled | 0                              | auth_ldap_syncplus |
      | sync_authtype                  | <authtype>                     | auth_ldap_syncplus |
      | sync_scope                     | <scope>                        | auth_ldap_syncplus |
      | sync_filter                    | <filter>                       | auth_ldap_syncplus |
    And the following "users" exist:
      | username                 | firstname | lastname      | email                         | auth       |
      | user<user1number><scope> | User      | <user1number> | user<user1number>@example.org | <authtype> |
    When I log in as "admin"
    And I pretend the suspended user "user<user1number><scope>" was suspended "1" days ago
    And I navigate to "Users > Accounts > Browse list of users" in site administration
    And I should see "User <user1number>" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I should see "Suspended" in the "User <user1number>" "table_row"
    And I run the scheduled task "\auth_ldap_syncplus\task\sync_task"
    And I reload the page
    Then I should see "User <user1number>" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I should not see "Suspended" in the "User <user1number>" "table_row"

    Examples:
      | authtype      | authlabel               | context    | scope        | filter  | user1number |
      | ldap_syncplus | LDAP server (Sync Plus) | department |              |         | 01          |
      | shibboleth    | Shibboleth              | department |              |         | 01          |
      | shibboleth    | Shibboleth              | scoped     |              |         | 11          |
      | shibboleth    | Shibboleth              | scoped     | @example.org |         | 11          |
      | shibboleth    | Shibboleth              | department |              | (uid=*) | 01          |
      | shibboleth    | Shibboleth              | scoped     |              | (uid=*) | 11          |
      | shibboleth    | Shibboleth              | scoped     | @example.org | (uid=*) | 11          |

  Scenario Outline: The LDAP synchronization task can be re-used for deleting non-LDAP users after the grace period
    Given the following config values are set as admin:
      | config                 | value                          | plugin             |
      | contexts               | ou=<context>,dc=example,dc=org | auth_ldap_syncplus |
      | removeuser             | 3                              | auth_ldap_syncplus |
      | removeuser_graceperiod | 2                              | auth_ldap_syncplus |
      | sync_authtype          | <authtype>                     | auth_ldap_syncplus |
      | sync_scope             | <scope>                        | auth_ldap_syncplus |
      | sync_filter            | <filter>                       | auth_ldap_syncplus |
    And the following "users" exist:
      | username      | firstname | lastname | email              | auth       |
      | user03<scope> | User      | 03       | user01@example.org | <authtype> |
    When I log in as "admin"
    And I navigate to "Users > Accounts > Browse list of users" in site administration
    And I should see "User 03" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I should not see "Suspended" in the "User 03" "table_row"
    And I run the scheduled task "\auth_ldap_syncplus\task\sync_task"
    And I reload the page
    Then I should see "User 03" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I should see "Suspended" in the "User 03" "table_row"
    And I pretend the suspended user "user03<scope>" was suspended "3" days ago
    And I run the scheduled task "\auth_ldap_syncplus\task\sync_task"
    And I reload the page
    Then I should not see "User 03" in the "[data-region='report-user-list-wrapper']" "css_element"

    Examples:
      | authtype      | authlabel               | context    | scope        | filter  |
      | ldap_syncplus | LDAP server (Sync Plus) | department |              |         |
      | shibboleth    | Shibboleth              | department |              |         |
      | shibboleth    | Shibboleth              | scoped     |              |         |
      | shibboleth    | Shibboleth              | scoped     | @example.org |         |
      | shibboleth    | Shibboleth              | department |              | (uid=*) |
      | shibboleth    | Shibboleth              | scoped     |              | (uid=*) |
      | shibboleth    | Shibboleth              | scoped     | @example.org | (uid=*) |

  Scenario Outline: The LDAP synchronization task can be re-used for reviving non-LDAP users within the grace period
    Given the following config values are set as admin:
      | config                         | value                          | plugin             |
      | contexts                       | ou=<context>,dc=example,dc=org | auth_ldap_syncplus |
      | removeuser                     | 3                              | auth_ldap_syncplus |
      | removeuser_graceperiod         | 2                              | auth_ldap_syncplus |
      | sync_script_createuser_enabled | 0                              | auth_ldap_syncplus |
      | sync_authtype                  | <authtype>                     | auth_ldap_syncplus |
      | sync_scope                     | <scope>                        | auth_ldap_syncplus |
      | sync_filter                    | <filter>                       | auth_ldap_syncplus |
    And the following "users" exist:
      | username                 | firstname | lastname      | email                         | auth       |
      | user<user1number><scope> | User      | <user1number> | user<user1number>@example.org | <authtype> |
    When I log in as "admin"
    And I pretend the suspended user "user<user1number><scope>" was suspended "1" days ago
    And I navigate to "Users > Accounts > Browse list of users" in site administration
    And I should see "User <user1number>" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I should see "Suspended" in the "User <user1number>" "table_row"
    And I run the scheduled task "\auth_ldap_syncplus\task\sync_task"
    And I reload the page
    Then I should see "User <user1number>" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I should not see "Suspended" in the "User <user1number>" "table_row"

    Examples:
      | authtype      | authlabel               | context    | scope        | filter  | user1number |
      | ldap_syncplus | LDAP server (Sync Plus) | department |              |         | 01          |
      | shibboleth    | Shibboleth              | department |              |         | 01          |
      | shibboleth    | Shibboleth              | scoped     |              |         | 11          |
      | shibboleth    | Shibboleth              | scoped     | @example.org |         | 11          |
      | shibboleth    | Shibboleth              | department |              | (uid=*) | 01          |
      | shibboleth    | Shibboleth              | scoped     |              | (uid=*) | 11          |
      | shibboleth    | Shibboleth              | scoped     | @example.org | (uid=*) | 11          |

  Scenario Outline: The LDAP synchronization task can be re-used for updating non-LDAP users
    Given the following config values are set as admin:
      | config        | value                          | plugin             |
      | contexts      | ou=<context>,dc=example,dc=org | auth_ldap_syncplus |
      | sync_authtype | <authtype>                     | auth_ldap_syncplus |
      | sync_scope    | <scope>                        | auth_ldap_syncplus |
      | sync_filter   | <filter>                       | auth_ldap_syncplus |
    And I run the scheduled task "\auth_ldap_syncplus\task\sync_task"
    When I log in as "admin"
    And I navigate to "Users > Accounts > Browse list of users" in site administration
    And I should see "User <user1number>" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I press "Edit" action in the "User <user1number>" report row
    And I set the field "First name" to "Foo"
    And I set the field "Last name" to "Bar"
    And I press "Update profile"
    And I should see "Foo Bar" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I run the scheduled task "\auth_ldap_syncplus\task\sync_task"
    And I run all adhoc tasks
    And I reload the page
    Then I should see "User <user1number>" in the "[data-region='report-user-list-wrapper']" "css_element"

    Examples:
      | authtype      | authlabel               | context    | scope        | filter  | user1number |
      | ldap_syncplus | LDAP server (Sync Plus) | department |              |         | 01          |
      | shibboleth    | Shibboleth              | department |              |         | 01          |
      | shibboleth    | Shibboleth              | scoped     |              |         | 11          |
      | shibboleth    | Shibboleth              | scoped     | @example.org |         | 11          |
      | shibboleth    | Shibboleth              | department |              | (uid=*) | 01          |
      | shibboleth    | Shibboleth              | scoped     |              | (uid=*) | 11          |
      | shibboleth    | Shibboleth              | scoped     | @example.org | (uid=*) | 11          |

  Scenario Outline: The LDAP synchronization task respects ignore_other_scopes when deleting non-LDAP users outside sync_scope
    Given the following config values are set as admin:
      | config                | value                       | plugin             |
      | contexts              | ou=scoped,dc=example,dc=org | auth_ldap_syncplus |
      | removeuser            | 2                           | auth_ldap_syncplus |
      | sync_authtype         | shibboleth                  | auth_ldap_syncplus |
      | sync_scope            | @example.org                | auth_ldap_syncplus |
      | sync_filter           | <filter>                    | auth_ldap_syncplus |
      | ignore_other_scopes   | <ignore_other_scopes>       | auth_ldap_syncplus |
    And the following "users" exist:
      | username           | firstname | lastname | email              | auth       |
      | user03@example.org | User      | 03       | user03@example.org | shibboleth |
      | user04@example.net | User      | 04net    | user04@example.net | shibboleth |
    When I log in as "admin"
    And I navigate to "Users > Accounts > Browse list of users" in site administration
    And I should see "User 03" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I should see "User 04net" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I run the scheduled task "\auth_ldap_syncplus\task\sync_task"
    And I reload the page
    Then I should not see "User 03" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I <shouldornot> see "User 04net" in the "[data-region='report-user-list-wrapper']" "css_element"

    Examples:
      | filter  | ignore_other_scopes | shouldornot |
      |         | 0                   | should not  |
      |         | 1                   | should      |
      | (uid=*) | 0                   | should not  |
      | (uid=*) | 1                   | should      |

  Scenario Outline: The LDAP synchronization task respects ignore_other_scopes when suspending non-LDAP users outside sync_scope
    Given the following config values are set as admin:
      | config                | value                       | plugin             |
      | contexts              | ou=scoped,dc=example,dc=org | auth_ldap_syncplus |
      | removeuser            | 1                           | auth_ldap_syncplus |
      | sync_authtype         | shibboleth                  | auth_ldap_syncplus |
      | sync_scope            | @example.org                | auth_ldap_syncplus |
      | sync_filter           | <filter>                    | auth_ldap_syncplus |
      | ignore_other_scopes   | <ignore_other_scopes>       | auth_ldap_syncplus |
    And the following "users" exist:
      | username           | firstname | lastname | email              | auth       |
      | user03@example.org | User      | 03       | user03@example.org | shibboleth |
      | user04@example.net | User      | 04net    | user04@example.net | shibboleth |
    When I log in as "admin"
    And I navigate to "Users > Accounts > Browse list of users" in site administration
    And I should see "User 03" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I should see "User 04net" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I should not see "Suspended" in the "User 03" "table_row"
    And I should not see "Suspended" in the "User 04net" "table_row"
    And I run the scheduled task "\auth_ldap_syncplus\task\sync_task"
    And I reload the page
    Then I should see "User 03" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I should see "Suspended" in the "User 03" "table_row"
    And I should see "User 04net" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I <shouldornot> see "Suspended" in the "User 04net" "table_row"

    Examples:
      | filter  | ignore_other_scopes | shouldornot |
      |         | 0                   | should      |
      |         | 1                   | should not  |
      | (uid=*) | 0                   | should      |
      | (uid=*) | 1                   | should not  |

  Scenario Outline: The LDAP synchronization task respects ignore_other_scopes (set to yes) when deleting non-LDAP users after the grace period
    Given the following config values are set as admin:
      | config                         | value                       | plugin             |
      | contexts                       | ou=scoped,dc=example,dc=org | auth_ldap_syncplus |
      | removeuser                     | 3                           | auth_ldap_syncplus |
      | removeuser_graceperiod         | 2                           | auth_ldap_syncplus |
      | sync_script_createuser_enabled | 0                           | auth_ldap_syncplus |
      | sync_authtype                  | shibboleth                  | auth_ldap_syncplus |
      | sync_scope                     | @example.org                | auth_ldap_syncplus |
      | sync_filter                    | <filter>                    | auth_ldap_syncplus |
      | ignore_other_scopes            | 1                           | auth_ldap_syncplus |
    And the following "users" exist:
      | username           | firstname | lastname | email              | auth       |
      | user03@example.org | User      | 03       | user03@example.org | shibboleth |
      | user04@example.net | User      | 04net    | user04@example.net | shibboleth |
    When I log in as "admin"
    And I navigate to "Users > Accounts > Browse list of users" in site administration
    And I should see "User 03" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I should see "User 04net" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I should not see "Suspended" in the "User 03" "table_row"
    And I should not see "Suspended" in the "User 04net" "table_row"
    And I run the scheduled task "\auth_ldap_syncplus\task\sync_task"
    And I reload the page
    Then I should see "User 03" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I should see "Suspended" in the "User 03" "table_row"
    And I should see "User 04net" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I should not see "Suspended" in the "User 04net" "table_row"
    And I pretend the suspended user "user03@example.org" was suspended "3" days ago
    And I run the scheduled task "\auth_ldap_syncplus\task\sync_task"
    And I reload the page
    Then I should not see "User 03" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I should see "User 04net" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I should not see "Suspended" in the "User 04net" "table_row"

    Examples:
      | filter  |
      |         |
      | (uid=*) |

  Scenario Outline: The LDAP synchronization task respects ignore_other_scopes (set to no) when deleting non-LDAP users after the grace period
    Given the following config values are set as admin:
      | config                         | value                       | plugin             |
      | contexts                       | ou=scoped,dc=example,dc=org | auth_ldap_syncplus |
      | removeuser                     | 3                           | auth_ldap_syncplus |
      | removeuser_graceperiod         | 2                           | auth_ldap_syncplus |
      | sync_script_createuser_enabled | 0                           | auth_ldap_syncplus |
      | sync_authtype                  | shibboleth                  | auth_ldap_syncplus |
      | sync_scope                     | @example.org                | auth_ldap_syncplus |
      | sync_filter                    | <filter>                    | auth_ldap_syncplus |
      | ignore_other_scopes            | 0                           | auth_ldap_syncplus |
    And the following "users" exist:
      | username           | firstname | lastname | email              | auth       |
      | user03@example.org | User      | 03       | user03@example.org | shibboleth |
      | user04@example.net | User      | 04net    | user04@example.net | shibboleth |
    When I log in as "admin"
    And I navigate to "Users > Accounts > Browse list of users" in site administration
    And I should see "User 03" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I should see "User 04net" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I should not see "Suspended" in the "User 03" "table_row"
    And I should not see "Suspended" in the "User 04net" "table_row"
    And I run the scheduled task "\auth_ldap_syncplus\task\sync_task"
    And I reload the page
    Then I should see "User 03" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I should see "Suspended" in the "User 03" "table_row"
    And I should see "User 04net" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I should see "Suspended" in the "User 04net" "table_row"
    And I pretend the suspended user "user03@example.org" was suspended "3" days ago
    And I pretend the suspended user "user04@example.net" was suspended "3" days ago
    And I run the scheduled task "\auth_ldap_syncplus\task\sync_task"
    And I reload the page
    Then I should not see "User 03" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I should not see "User 04net" in the "[data-region='report-user-list-wrapper']" "css_element"

    Examples:
      | filter  |
      |         |
      | (uid=*) |

  Scenario Outline: The LDAP synchronization task respects ignore_other_scopes when updating non-LDAP users outside sync_scope
    Given the following config values are set as admin:
      | config                | value                       | plugin             |
      | contexts              | ou=scoped,dc=example,dc=org | auth_ldap_syncplus |
      | sync_authtype         | shibboleth                  | auth_ldap_syncplus |
      | sync_scope            | @example.org                | auth_ldap_syncplus |
      | sync_filter           | <filter>                    | auth_ldap_syncplus |
      | ignore_other_scopes   | 1                           | auth_ldap_syncplus |
    And I run the scheduled task "\auth_ldap_syncplus\task\sync_task"
    And the following "users" exist:
      | username           | firstname | lastname | email              | auth       |
      | user04@example.net | User      | 04net    | user04@example.net | shibboleth |
    When I log in as "admin"
    And I navigate to "Users > Accounts > Browse list of users" in site administration
    And I should see "User 11" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I should see "User 04net" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I press "Edit" action in the "User 11" report row
    And I set the field "First name" to "Foo"
    And I set the field "Last name" to "Bar"
    And I press "Update profile"
    And I press "Edit" action in the "User 04net" report row
    And I set the field "First name" to "OtherScope"
    And I set the field "Last name" to "Edited"
    And I press "Update profile"
    And I should see "Foo Bar" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I should see "OtherScope Edited" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I run the scheduled task "\auth_ldap_syncplus\task\sync_task"
    And I run all adhoc tasks
    And I reload the page
    Then I should see "User 11" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I should not see "Foo Bar" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I should see "OtherScope Edited" in the "[data-region='report-user-list-wrapper']" "css_element"

    Examples:
      | filter  |
      |         |
      | (uid=*) |

  Scenario Outline: The LDAP synchronization task can be limited to a custom LDAP filter which excludes certain users
    Given the following config values are set as admin:
      | config        | value      | plugin             |
      | contexts      | <context>  | auth_ldap_syncplus |
      | sync_authtype | <authtype> | auth_ldap_syncplus |
      | sync_scope    | <scope>    | auth_ldap_syncplus |
      | sync_filter   | <filter>   | auth_ldap_syncplus |
    When I log in as "admin"
    And I navigate to "Users > Accounts > Browse list of users" in site administration
    And I should not see "User <user1number>" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I should not see "User <user2number>" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I run the scheduled task "\auth_ldap_syncplus\task\sync_task"
    And I reload the page
    Then I <user1shouldornot> see "User <user1number>" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I <user2shouldornot> see "User <user2number>" in the "[data-region='report-user-list-wrapper']" "css_element"

    Examples:
      | authtype   | authlabel  | context                         | scope        | filter                              | user1number | user1shouldornot | user2number | user2shouldornot |
      | shibboleth | Shibboleth | ou=department,dc=example,dc=org |              |                                     | 01          | should           | 02          | should           |
      | shibboleth | Shibboleth | ou=scoped,dc=example,dc=org     |              |                                     | 11          | should           | 12          | should           |
      | shibboleth | Shibboleth | ou=scoped,dc=example,dc=org     | @example.org |                                     | 11          | should           | 12          | should           |
      | shibboleth | Shibboleth | ou=department,dc=example,dc=org |              | (uid=*)                             | 01          | should           | 02          | should           |
      | shibboleth | Shibboleth | ou=scoped,dc=example,dc=org     |              | (uid=*)                             | 11          | should           | 12          | should           |
      | shibboleth | Shibboleth | ou=scoped,dc=example,dc=org     | @example.org | (uid=*)                             | 11          | should           | 12          | should           |
      | shibboleth | Shibboleth | ou=department,dc=example,dc=org |              | (&(objectClass=person)(!(uid=*02))) | 01          | should           | 02          | should not       |
      | shibboleth | Shibboleth | ou=scoped,dc=example,dc=org     |              | (&(objectClass=person)(!(uid=*12))) | 11          | should           | 12          | should not       |
      | shibboleth | Shibboleth | ou=scoped,dc=example,dc=org     | @example.org | (&(objectClass=person)(!(uid=*12))) | 11          | should           | 12          | should not       |
      | shibboleth | Shibboleth | dc=example,dc=org               |              | (&(!(ou:dn:=scoped))(uid=*))        | 01          | should           | 11          | should not       |
      | shibboleth | Shibboleth | dc=example,dc=org               |              | (&(!(ou:dn:=scoped))(uid=*))        | 01          | should           | 11          | should not       |
      | shibboleth | Shibboleth | dc=example,dc=org               | @example.org | (&(!(ou:dn:=scoped))(uid=*))        | 01          | should           | 11          | should not       |

  # The following scenarios verify that a sync scope with a single quote is bound as SQL parameter and does not break
  # the sync queries. If any sync query broke, the scheduled task would fail and so would the scenario.
  # The Moodle user in each example has the realistic scope @example.org, so no username with a single quote is needed.
  # As the configured scope differs from the user's scope, the user does not match any LDAP user from the sync's point
  # of view. User creation is disabled in the first three scenario outlines, the user addition query is covered by the
  # last scenario.
  Scenario Outline: The LDAP synchronization task does not break if the sync scope contains a single quote (active users)
    Given the following config values are set as admin:
      | config                         | value                       | plugin             |
      | contexts                       | ou=scoped,dc=example,dc=org | auth_ldap_syncplus |
      | removeuser                     | <removeuser>                | auth_ldap_syncplus |
      | removeuser_graceperiod         | 2                           | auth_ldap_syncplus |
      | sync_script_createuser_enabled | 0                           | auth_ldap_syncplus |
      | sync_authtype                  | shibboleth                  | auth_ldap_syncplus |
      | sync_scope                     | @o'brien.example.org        | auth_ldap_syncplus |
      | ignore_other_scopes            | <ignoreotherscopes>         | auth_ldap_syncplus |
    And the following "users" exist:
      | username           | firstname | lastname | email              | auth       |
      | user03@example.org | User      | 03       | user03@example.org | shibboleth |
    When I log in as "admin"
    And I run the scheduled task "\auth_ldap_syncplus\task\sync_task"
    And I navigate to "Users > Accounts > Browse list of users" in site administration
    Then I should see "User 03" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I <suspendedshouldornot> see "Suspended" in the "User 03" "table_row"

    # Example 1: Suspend query - the user does not exist in LDAP with the configured scope, so the user is suspended.
    # Example 2: Grace period suspend query - the user does not exist in LDAP with the configured scope,
    #            so the user is suspended.
    # Example 3: Full delete query with the ignore_other_scopes LIKE filter - the user has another scope than
    #            the configured scope, so the user is neither deleted nor suspended.
    Examples:
      | removeuser | ignoreotherscopes | suspendedshouldornot |
      | 1          | 0                 | should               |
      | 3          | 0                 | should               |
      | 2          | 1                 | should not           |

  Scenario Outline: The LDAP synchronization task does not break if the sync scope contains a single quote (suspended users)
    Given the following config values are set as admin:
      | config                         | value                       | plugin             |
      | contexts                       | ou=scoped,dc=example,dc=org | auth_ldap_syncplus |
      | removeuser                     | <removeuser>                | auth_ldap_syncplus |
      | removeuser_graceperiod         | 2                           | auth_ldap_syncplus |
      | sync_script_createuser_enabled | 0                           | auth_ldap_syncplus |
      | sync_authtype                  | shibboleth                  | auth_ldap_syncplus |
      | sync_scope                     | @o'brien.example.org        | auth_ldap_syncplus |
      | ignore_other_scopes            | 0                           | auth_ldap_syncplus |
    And the following "users" exist:
      | username           | firstname | lastname | email              | auth       |
      | user11@example.org | User      | 11       | user11@example.org | shibboleth |
    When I log in as "admin"
    And I pretend the suspended user "user11@example.org" was suspended "1" days ago
    And I run the scheduled task "\auth_ldap_syncplus\task\sync_task"
    And I navigate to "Users > Accounts > Browse list of users" in site administration
    Then I should see "User 11" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I should see "Suspended" in the "User 11" "table_row"

    # Example 1: Revive query - user11 exists in LDAP, but it does not match the Moodle user due to the configured scope,
    #            so the user is not revived and stays suspended.
    # Example 2: Grace period revive query - same as example 1. Additionally, the grace period has not passed yet,
    #            so the user is not deleted either.
    Examples:
      | removeuser |
      | 1          |
      | 3          |

  Scenario Outline: The LDAP synchronization task does not break if the sync scope contains a single quote (deleted users)
    Given the following config values are set as admin:
      | config                         | value                       | plugin             |
      | contexts                       | ou=scoped,dc=example,dc=org | auth_ldap_syncplus |
      | removeuser                     | <removeuser>                | auth_ldap_syncplus |
      | removeuser_graceperiod         | 2                           | auth_ldap_syncplus |
      | sync_script_createuser_enabled | 0                           | auth_ldap_syncplus |
      | sync_authtype                  | shibboleth                  | auth_ldap_syncplus |
      | sync_scope                     | @o'brien.example.org        | auth_ldap_syncplus |
      | ignore_other_scopes            | 0                           | auth_ldap_syncplus |
    And the following "users" exist:
      | username           | firstname | lastname | email              | auth       |
      | user03@example.org | User      | 03       | user03@example.org | shibboleth |
    When I log in as "admin"
    And I pretend the suspended user "user03@example.org" was suspended "<suspendeddaysago>" days ago
    And I navigate to "Users > Accounts > Browse list of users" in site administration
    And I should see "User 03" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I run the scheduled task "\auth_ldap_syncplus\task\sync_task"
    And I reload the page
    Then I should not see "User 03" in the "[data-region='report-user-list-wrapper']" "css_element"

    # Example 1: Full delete query - the user does not exist in LDAP with the configured scope, so the user is deleted.
    #            The full delete query deletes suspended users as well, so the previous suspension does not matter here.
    # Example 2: Grace period delete query - the user does not exist in LDAP with the configured scope and
    #            the grace period has passed, so the user is deleted.
    Examples:
      | removeuser | suspendeddaysago |
      | 2          | 1                |
      | 3          | 3                |

  # User addition query - user11 and user12 exist in LDAP, but not in Moodle, so the query finds them and the sync
  # tries to create them with the configured scope appended to their usernames. Moodle rejects these usernames as
  # they contain a single quote. The sync catches this error per user, so the scheduled task does not fail,
  # but no user is created.
  Scenario: The LDAP synchronization task does not break if the sync scope contains a single quote (user addition)
    Given the following config values are set as admin:
      | config                         | value                       | plugin             |
      | contexts                       | ou=scoped,dc=example,dc=org | auth_ldap_syncplus |
      | sync_script_createuser_enabled | 1                           | auth_ldap_syncplus |
      | sync_authtype                  | shibboleth                  | auth_ldap_syncplus |
      | sync_scope                     | @o'brien.example.org        | auth_ldap_syncplus |
    When I log in as "admin"
    And I run the scheduled task "\auth_ldap_syncplus\task\sync_task"
    And I navigate to "Users > Accounts > Browse list of users" in site administration
    Then I should not see "User 11" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I should not see "User 12" in the "[data-region='report-user-list-wrapper']" "css_element"

  # The guest user and the admin user are manual users who do not exist in LDAP, just like user03.
  # If the sync task is (mis)configured to sync manual users, user03 is suspended / deleted as expected,
  # but the guest user and the internal site admin must be skipped.
  # The checks are done directly in the database as the guest user is not shown in the user list.
  Scenario Outline: The LDAP synchronization task does not suspend or delete the guest user and internal site admins when it is re-used for manual users
    Given the following config values are set as admin:
      | config                         | value        | plugin             |
      | removeuser                     | <removeuser> | auth_ldap_syncplus |
      | sync_script_createuser_enabled | 0            | auth_ldap_syncplus |
      | sync_authtype                  | manual       | auth_ldap_syncplus |
    And the following "users" exist:
      | username | firstname | lastname | email              | auth   |
      | user03   | User      | 03       | user03@example.org | manual |
    When I run the scheduled task "\auth_ldap_syncplus\task\sync_task"
    Then the user "user03" should be <user03state>
    And the user "admin" should not be deleted
    And the user "admin" should not be suspended
    And the user "guest" should not be deleted
    And the user "guest" should not be suspended

    # Example 1: Suspend query - user03 is suspended, the guest user and the admin user are skipped.
    # Example 2: Full delete query - user03 is deleted, the guest user and the admin user are skipped.
    Examples:
      | removeuser | user03state |
      | 1          | suspended   |
      | 2          | deleted     |

  # This is the same check as in the previous scenario, but for the grace period mode.
  # In the first run, the grace period suspend query suspends user03 and skips the guest user and the admin user.
  # The grace period delete query of the first run finds the guest user and the admin user as well and, as they
  # have not been modified within the grace period, would try to delete them, so they must be skipped there, too.
  # In the second run, user03 is deleted after the grace period has passed.
  Scenario: The LDAP synchronization task does not suspend or delete the guest user and internal site admins when it is re-used for manual users (with grace period)
    Given the following config values are set as admin:
      | config                         | value  | plugin             |
      | removeuser                     | 3      | auth_ldap_syncplus |
      | removeuser_graceperiod         | 2      | auth_ldap_syncplus |
      | sync_script_createuser_enabled | 0      | auth_ldap_syncplus |
      | sync_authtype                  | manual | auth_ldap_syncplus |
    And the following "users" exist:
      | username | firstname | lastname | email              | auth   |
      | user03   | User      | 03       | user03@example.org | manual |
    And I pretend the user "admin" was last modified "3" days ago
    And I pretend the user "guest" was last modified "3" days ago
    When I run the scheduled task "\auth_ldap_syncplus\task\sync_task"
    Then the user "user03" should be suspended
    And the user "admin" should not be deleted
    And the user "admin" should not be suspended
    And the user "guest" should not be deleted
    And the user "guest" should not be suspended
    And I pretend the suspended user "user03" was suspended "3" days ago
    And I run the scheduled task "\auth_ldap_syncplus\task\sync_task"
    And the user "user03" should be deleted
    And the user "admin" should not be deleted
    And the user "admin" should not be suspended
    And the user "guest" should not be deleted
    And the user "guest" should not be suspended

  # Countercheck for the previous scenarios: Only site admins who authenticate with the manual auth plugin are protected.
  # A site admin with any other auth type who has disappeared from LDAP has to be deprovisioned just like any other user.
  # The admin user is a manual user and is not covered by the sync here, so the admin user must remain untouched.
  Scenario Outline: The LDAP synchronization task suspends or deletes site admins who do not authenticate with the manual auth plugin
    Given the following config values are set as admin:
      | config                         | value        | plugin             |
      | removeuser                     | <removeuser> | auth_ldap_syncplus |
      | sync_script_createuser_enabled | 0            | auth_ldap_syncplus |
      | sync_authtype                  | <authtype>   | auth_ldap_syncplus |
    And the following "users" exist:
      | username | firstname | lastname | email              | auth       |
      | user03   | User      | 03       | user03@example.org | <authtype> |
    And the user "user03" is a site admin
    When I run the scheduled task "\auth_ldap_syncplus\task\sync_task"
    Then the user "user03" should be <user03state>
    And the user "admin" should not be deleted
    And the user "admin" should not be suspended

    # Example 1 and 3: Suspend query - user03 is suspended although being a site admin.
    # Example 2 and 4: Full delete query - user03 is deleted although being a site admin.
    Examples:
      | authtype      | removeuser | user03state |
      | ldap_syncplus | 1          | suspended   |
      | ldap_syncplus | 2          | deleted     |
      | shibboleth    | 1          | suspended   |
      | shibboleth    | 2          | deleted     |

  # This is the same countercheck as in the previous scenario, but for the grace period mode.
  # In the first run, the grace period suspend query suspends user03 although being a site admin.
  # In the second run, user03 is deleted after the grace period has passed.
  Scenario Outline: The LDAP synchronization task suspends and deletes site admins who do not authenticate with the manual auth plugin (with grace period)
    Given the following config values are set as admin:
      | config                         | value      | plugin             |
      | removeuser                     | 3          | auth_ldap_syncplus |
      | removeuser_graceperiod         | 2          | auth_ldap_syncplus |
      | sync_script_createuser_enabled | 0          | auth_ldap_syncplus |
      | sync_authtype                  | <authtype> | auth_ldap_syncplus |
    And the following "users" exist:
      | username | firstname | lastname | email              | auth       |
      | user03   | User      | 03       | user03@example.org | <authtype> |
    And the user "user03" is a site admin
    When I run the scheduled task "\auth_ldap_syncplus\task\sync_task"
    Then the user "user03" should be suspended
    And the user "admin" should not be deleted
    And the user "admin" should not be suspended
    And I pretend the suspended user "user03" was suspended "3" days ago
    And I run the scheduled task "\auth_ldap_syncplus\task\sync_task"
    And the user "user03" should be deleted
    And the user "admin" should not be deleted
    And the user "admin" should not be suspended

    Examples:
      | authtype      |
      | ldap_syncplus |
      | shibboleth    |

  # In LDAP, group01 contains the first user of each context (user01 / user11) and group02 contains the second user
  # of each context (user02 / user12).
  # In the first run, the manager role is mapped to group01, so the first user gets the role and the second user does not.
  # In the second run, the manager role is mapped to group02, so the role is removed from the first user again and is
  # assigned to the second user.
  Scenario Outline: The LDAP roles synchronization task assigns and unassigns system roles for LDAP users and can be re-used for non-LDAP users
    Given the following config values are set as admin:
      | config               | value                                  | plugin             |
      | contexts             | ou=<context>,dc=example,dc=org         | auth_ldap_syncplus |
      | sync_authtype        | <authtype>                             | auth_ldap_syncplus |
      | sync_scope           | <scope>                                | auth_ldap_syncplus |
      | memberattribute      | member                                 | auth_ldap_syncplus |
      | memberattribute_isdn | 1                                      | auth_ldap_syncplus |
      | managercontext       | cn=group01,ou=groups,dc=example,dc=org | auth_ldap_syncplus |
    And the following "users" exist:
      | username                 | firstname | lastname      | email                         | auth       |
      | user<user1number><scope> | User      | <user1number> | user<user1number>@example.org | <authtype> |
      | user<user2number><scope> | User      | <user2number> | user<user2number>@example.org | <authtype> |
    When I log in as "admin"
    And I run the scheduled task "\auth_ldap_syncplus\task\sync_roles"
    And I navigate to "Users > Permissions > Assign system roles" in site administration
    Then I should see "User <user1number>" in the "Manager" "table_row"
    And I should not see "User <user2number>" in the "Manager" "table_row"
    And the following config values are set as admin:
      | config         | value                                  | plugin             |
      | managercontext | cn=group02,ou=groups,dc=example,dc=org | auth_ldap_syncplus |
    And I run the scheduled task "\auth_ldap_syncplus\task\sync_roles"
    And I reload the page
    And I should not see "User <user1number>" in the "Manager" "table_row"
    And I should see "User <user2number>" in the "Manager" "table_row"

    Examples:
      | authtype      | context    | scope        | user1number | user2number |
      | ldap_syncplus | department |              | 01          | 02          |
      | shibboleth    | department |              | 01          | 02          |
      | shibboleth    | scoped     |              | 11          | 12          |
      | shibboleth    | scoped     | @example.org | 11          | 12          |

  # Countercheck for the previous scenario: The roles synchronization task only handles the users of the configured
  # authentication type. The users exist in LDAP and are members of the mapped group, but as they are manual users,
  # they must not get the role.
  Scenario Outline: The LDAP roles synchronization task does not assign system roles to users of other authentication types
    Given the following config values are set as admin:
      | config               | value                                  | plugin             |
      | sync_authtype        | <authtype>                             | auth_ldap_syncplus |
      | memberattribute      | member                                 | auth_ldap_syncplus |
      | memberattribute_isdn | 1                                      | auth_ldap_syncplus |
      | managercontext       | cn=group01,ou=groups,dc=example,dc=org | auth_ldap_syncplus |
    And the following "users" exist:
      | username | firstname | lastname | email              | auth   |
      | user01   | User      | 01       | user01@example.org | manual |
    When I log in as "admin"
    And I run the scheduled task "\auth_ldap_syncplus\task\sync_roles"
    And I navigate to "Users > Permissions > Assign system roles" in site administration
    Then I should not see "User 01" in the "Manager" "table_row"

    Examples:
      | authtype      |
      | ldap_syncplus |
      | shibboleth    |

  # user11@example.org is inside the configured scope, user11@example.net is outside of it.
  # If the scope is removed from both usernames, both users would match user11 in LDAP who is a member of the mapped group.
  # As the scope is only removed from usernames which contain it, user11@example.net is not found in LDAP and does not
  # get the role, regardless of the ignore_other_scopes setting.
  Scenario Outline: The LDAP roles synchronization task does not assign system roles to non-LDAP users outside sync_scope
    Given the following config values are set as admin:
      | config               | value                                  | plugin             |
      | contexts             | ou=scoped,dc=example,dc=org            | auth_ldap_syncplus |
      | sync_authtype        | shibboleth                             | auth_ldap_syncplus |
      | sync_scope           | @example.org                           | auth_ldap_syncplus |
      | ignore_other_scopes  | <ignore_other_scopes>                  | auth_ldap_syncplus |
      | memberattribute      | member                                 | auth_ldap_syncplus |
      | memberattribute_isdn | 1                                      | auth_ldap_syncplus |
      | managercontext       | cn=group01,ou=groups,dc=example,dc=org | auth_ldap_syncplus |
    And the following "users" exist:
      | username           | firstname | lastname | email              | auth       |
      | user11@example.org | User      | 11       | user11@example.org | shibboleth |
      | user11@example.net | User      | 11net    | user11@example.net | shibboleth |
    When I log in as "admin"
    And I run the scheduled task "\auth_ldap_syncplus\task\sync_roles"
    And I navigate to "Users > Permissions > Assign system roles" in site administration
    Then I should see "User 11" in the "Manager" "table_row"
    And I should not see "User 11net" in the "Manager" "table_row"

    Examples:
      | ignore_other_scopes |
      | 0                   |
      | 1                   |

  # The scope is a suffix and must only be stripped from the end of the username when a user is looked up in LDAP.
  # The tricky user contains the scope string in the middle of the username or twice. If the scope string was stripped
  # everywhere, the tricky user would be looked up as user11 in LDAP and would wrongly be updated with the profile
  # fields of user11.
  # user11@example.org is the countercheck: This user is looked up as user11 in LDAP and is updated.
  Scenario Outline: The LDAP synchronization task strips the sync scope only from the end of the username when updating non-LDAP users
    Given the following config values are set as admin:
      | config                         | value                       | plugin             |
      | contexts                       | ou=scoped,dc=example,dc=org | auth_ldap_syncplus |
      | sync_script_createuser_enabled | 0                           | auth_ldap_syncplus |
      | sync_authtype                  | shibboleth                  | auth_ldap_syncplus |
      | sync_scope                     | @example.org                | auth_ldap_syncplus |
      | ignore_other_scopes            | <ignore_other_scopes>       | auth_ldap_syncplus |
    And the following "users" exist:
      | username           | firstname | lastname | email              | auth       |
      | user11@example.org | Foo       | Bar      | user11@example.org | shibboleth |
      | <trickyusername>   | Tricky    | Scope    | tricky@example.org | shibboleth |
    When I log in as "admin"
    And I run the scheduled task "\auth_ldap_syncplus\task\sync_task"
    And I run all adhoc tasks
    And I navigate to "Users > Accounts > Browse list of users" in site administration
    Then I should see "User 11" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I should not see "Foo Bar" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I should see "Tricky Scope" in the "[data-region='report-user-list-wrapper']" "css_element"

    # Example 1: The scope string is in the middle of the username and users outside the scope are not ignored,
    #            so the user is processed, but is not found in LDAP.
    # Example 2: The scope string is in the middle of the username and users outside the scope are ignored,
    #            so the user is not processed at all.
    # Example 3 and 4: The username contains the scope string twice and ends with it, so the user is inside the scope
    #            and is processed regardless of the ignore_other_scopes setting, but is not found in LDAP.
    Examples:
      | trickyusername                 | ignore_other_scopes |
      | user@example.org11             | 0                   |
      | user@example.org11             | 1                   |
      | user@example.org11@example.org | 0                   |
      | user@example.org11@example.org | 1                   |

  # This is the same check as in the previous scenario, but for the roles synchronization task.
  # If the scope string was stripped everywhere, the tricky user would be looked up as user11 in LDAP, who is a member
  # of the mapped group, and would wrongly get the role.
  # user11@example.org is the countercheck: This user is looked up as user11 in LDAP and gets the role.
  Scenario Outline: The LDAP roles synchronization task strips the sync scope only from the end of the username
    Given the following config values are set as admin:
      | config               | value                                  | plugin             |
      | contexts             | ou=scoped,dc=example,dc=org            | auth_ldap_syncplus |
      | sync_authtype        | shibboleth                             | auth_ldap_syncplus |
      | sync_scope           | @example.org                           | auth_ldap_syncplus |
      | ignore_other_scopes  | <ignore_other_scopes>                  | auth_ldap_syncplus |
      | memberattribute      | member                                 | auth_ldap_syncplus |
      | memberattribute_isdn | 1                                      | auth_ldap_syncplus |
      | managercontext       | cn=group01,ou=groups,dc=example,dc=org | auth_ldap_syncplus |
    And the following "users" exist:
      | username           | firstname | lastname | email              | auth       |
      | user11@example.org | User      | 11       | user11@example.org | shibboleth |
      | <trickyusername>   | Tricky    | Scope    | tricky@example.org | shibboleth |
    When I log in as "admin"
    And I run the scheduled task "\auth_ldap_syncplus\task\sync_roles"
    And I navigate to "Users > Permissions > Assign system roles" in site administration
    Then I should see "User 11" in the "Manager" "table_row"
    And I should not see "Tricky Scope" in the "Manager" "table_row"

    # See the previous scenario for the explanation of the examples.
    Examples:
      | trickyusername                 | ignore_other_scopes |
      | user@example.org11             | 0                   |
      | user@example.org11             | 1                   |
      | user@example.org11@example.org | 0                   |
      | user@example.org11@example.org | 1                   |

  # The custom sync filter returns <user1> and <user2> from LDAP, so both are considered as users to be added.
  # But when a user is added, the user is looked up in LDAP again and this lookup respects the configured object class
  # which is (mis)used here as a filter which only matches <user2>. So <user1> can't be read from LDAP.
  # <user1> must be skipped then and the synchronization task must continue and add <user2> instead of being aborted.
  Scenario Outline: The LDAP synchronization task skips users who can't be read from LDAP and continues to add the remaining users
    Given the following config values are set as admin:
      | config        | value                          | plugin             |
      | contexts      | ou=<context>,dc=example,dc=org | auth_ldap_syncplus |
      | sync_authtype | shibboleth                     | auth_ldap_syncplus |
      | sync_filter   | (uid=*)                        | auth_ldap_syncplus |
      | objectclass   | (cn=User <user2number>)        | auth_ldap_syncplus |
    When I log in as "admin"
    And I run the scheduled task "\auth_ldap_syncplus\task\sync_task"
    And I navigate to "Users > Accounts > Browse list of users" in site administration
    Then I should not see "User <user1number>" in the "[data-region='report-user-list-wrapper']" "css_element"
    And I should see "User <user2number>" in the "[data-region='report-user-list-wrapper']" "css_element"

    Examples:
      | context    | user1number | user2number |
      | department | 01          | 02          |
      | scoped     | 11          | 12          |
