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
      | user_attribute              | uid                             | auth_ldap_syncplus |
      | field_map_firstname         | givenName                       | auth_ldap_syncplus |
      | field_updatelocal_firstname | onlogin                         | auth_ldap_syncplus |
      | field_map_lastname          | sn                              | auth_ldap_syncplus |
      | field_updatelocal_lastname  | onlogin                         | auth_ldap_syncplus |
      | field_map_email             | mail                            | auth_ldap_syncplus |
      | field_updatelocal_email     | onlogin                         | auth_ldap_syncplus |

  Scenario: All additional LDAP server (Sync Plus) settings should be there
    When I log in as "admin"
    And I navigate to "Plugins > Authentication > Manage authentication" in site administration
    And I click on "Settings" "link" in the "LDAP server (Sync Plus)" "table_row"
    Then I should see "LDAP server (Sync Plus)" in the "#region-main .settingsform" "css_element"
    And the "Removed ext user" select box should contain "Suspend internal and fully delete internal after grace period"
    And I should see "Fully deleting grace period" in the "#admin-removeuser_graceperiod" "css_element"
    And I should see "Add new users" in the "#admin-sync_script_createuser_enabled" "css_element"

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
      | user01   | User      | 01       | user01@example.com | ldap_syncplus |
      | user03   | User      | 03       | user03@example.com | ldap_syncplus |
    When I log in as "admin"
    And I navigate to "Users > Accounts > Browse list of users" in site administration
    And I should see "User 01" in the "#users" "css_element"
    And I should see "User 03" in the "#users" "css_element"
    And ".usersuspended" "css_element" should not exist in the "User 01" "table_row"
    And ".usersuspended" "css_element" should not exist in the "User 03" "table_row"
    And I run the scheduled task "\auth_ldap_syncplus\task\sync_task"
    And I reload the page
    Then I should see "User 01" in the "#users" "css_element"
    And I should see "User 03" in the "#users" "css_element"
    And ".usersuspended" "css_element" should not exist in the "User 01" "table_row"
    And ".usersuspended" "css_element" should exist in the "User 03" "table_row"
    And I pretend the suspended user "user03" was suspended "3" days ago
    And I run the scheduled task "\auth_ldap_syncplus\task\sync_task"
    And I reload the page
    Then I should see "User 01" in the "#users" "css_element"
    And I should not see "User 03" in the "#users" "css_element"
    And ".usersuspended" "css_element" should not exist in the "User 01" "table_row"

  Scenario: The LDAP synchronization task should revive suspended users who have re-appeared in LDAP within the grace period
    Given the following config values are set as admin:
      | config                 | value | plugin             |
      | removeuser             | 3     | auth_ldap_syncplus |
      | removeuser_graceperiod | 2     | auth_ldap_syncplus |
    And the following "users" exist:
      # user01 and user02 exist in the LDAP server.
      | username | firstname | lastname | email              | auth          |
      | user01   | User      | 01       | user01@example.com | ldap_syncplus |
      | user02   | User      | 02       | user02@example.com | ldap_syncplus |
    When I log in as "admin"
    And I pretend the suspended user "user02" was suspended "1" days ago
    And I navigate to "Users > Accounts > Browse list of users" in site administration
    And I should see "User 01" in the "#users" "css_element"
    And I should see "User 02" in the "#users" "css_element"
    And ".usersuspended" "css_element" should not exist in the "User 01" "table_row"
    And ".usersuspended" "css_element" should exist in the "User 02" "table_row"
    And I run the scheduled task "\auth_ldap_syncplus\task\sync_task"
    And I reload the page
    Then I should see "User 01" in the "#users" "css_element"
    And I should see "User 02" in the "#users" "css_element"
    And ".usersuspended" "css_element" should not exist in the "User 01" "table_row"
    And ".usersuspended" "css_element" should not exist in the "User 02" "table_row"

  Scenario: The LDAP synchronization task should suspend users who have disappeared in LDAP (Countercheck / Moodle core behaviour)
    Given the following config values are set as admin:
      | config                 | value | plugin             |
      | removeuser             | 1     | auth_ldap_syncplus |
    # user01 exists in the LDAP server, user03 does not.
    And the following "users" exist:
      | username | firstname | lastname | email              | auth          |
      | user01   | User      | 01       | user01@example.com | ldap_syncplus |
      | user03   | User      | 03       | user03@example.com | ldap_syncplus |
    When I log in as "admin"
    And I navigate to "Users > Accounts > Browse list of users" in site administration
    And I should see "User 01" in the "#users" "css_element"
    And I should see "User 03" in the "#users" "css_element"
    And ".usersuspended" "css_element" should not exist in the "User 01" "table_row"
    And ".usersuspended" "css_element" should not exist in the "User 03" "table_row"
    And I run the scheduled task "\auth_ldap_syncplus\task\sync_task"
    And I reload the page
    Then I should see "User 01" in the "#users" "css_element"
    And I should see "User 03" in the "#users" "css_element"
    And ".usersuspended" "css_element" should not exist in the "User 01" "table_row"
    And ".usersuspended" "css_element" should exist in the "User 03" "table_row"

  Scenario: The LDAP synchronization task should revive suspended users who have re-appeared in LDAP after they have been suspended
    Given the following config values are set as admin:
      | config                 | value | plugin             |
      | removeuser             | 1     | auth_ldap_syncplus |
    And the following "users" exist:
      # user01 and user02 exist in the LDAP server.
      | username | firstname | lastname | email              | auth          |
      | user01   | User      | 01       | user01@example.com | ldap_syncplus |
      | user02   | User      | 02       | user02@example.com | ldap_syncplus |
    When I log in as "admin"
    And I pretend the suspended user "user02" was suspended "1" days ago
    And I navigate to "Users > Accounts > Browse list of users" in site administration
    And I should see "User 01" in the "#users" "css_element"
    And I should see "User 02" in the "#users" "css_element"
    And ".usersuspended" "css_element" should not exist in the "User 01" "table_row"
    And ".usersuspended" "css_element" should exist in the "User 02" "table_row"
    And I run the scheduled task "\auth_ldap_syncplus\task\sync_task"
    And I reload the page
    Then I should see "User 01" in the "#users" "css_element"
    And I should see "User 02" in the "#users" "css_element"
    And ".usersuspended" "css_element" should not exist in the "User 01" "table_row"
    And ".usersuspended" "css_element" should not exist in the "User 02" "table_row"

  Scenario: The LDAP synchronization task should delete users who have disappeared in LDAP (Countercheck / Moodle core behaviour)
    Given the following config values are set as admin:
      | config                 | value | plugin             |
      | removeuser             | 2     | auth_ldap_syncplus |
    # user01 exists in the LDAP server, user03 does not.
    And the following "users" exist:
      | username | firstname | lastname | email              | auth          |
      | user01   | User      | 01       | user01@example.com | ldap_syncplus |
      | user03   | User      | 03       | user03@example.com | ldap_syncplus |
    When I log in as "admin"
    And I navigate to "Users > Accounts > Browse list of users" in site administration
    And I should see "User 01" in the "#users" "css_element"
    And I should see "User 03" in the "#users" "css_element"
    And ".usersuspended" "css_element" should not exist in the "User 01" "table_row"
    And ".usersuspended" "css_element" should not exist in the "User 03" "table_row"
    And I run the scheduled task "\auth_ldap_syncplus\task\sync_task"
    And I reload the page
    Then I should see "User 01" in the "#users" "css_element"
    And I should not see "User 03" in the "#users" "css_element"
    And ".usersuspended" "css_element" should not exist in the "User 01" "table_row"

  Scenario: The LDAP synchronization task should not create Moodle accounts for all LDAP users
    Given the following config values are set as admin:
      | config                         | value | plugin             |
      | sync_script_createuser_enabled | 0     | auth_ldap_syncplus |
    When I log in as "admin"
    And I navigate to "Users > Accounts > Browse list of users" in site administration
    And I should not see "User 01" in the "#users" "css_element"
    And I run the scheduled task "\auth_ldap_syncplus\task\sync_task"
    And I reload the page
    Then I should not see "User 01" in the "#users" "css_element"

  Scenario: The LDAP synchronization task should create Moodle accounts for all LDAP users (Countercheck / Moodle core behaviour)
    Given the following config values are set as admin:
      | config                         | value | plugin             |
      | sync_script_createuser_enabled | 1     | auth_ldap_syncplus |
    When I log in as "admin"
    And I navigate to "Users > Accounts > Browse list of users" in site administration
    And I should not see "User 01" in the "#users" "css_element"
    And I run the scheduled task "\auth_ldap_syncplus\task\sync_task"
    And I reload the page
    Then I should see "User 01" in the "#users" "css_element"

  Scenario: On manual user creation, user details should be fetched from LDAP
    When I log in as "admin"
    And I navigate to "Users > Accounts > Add a new user" in site administration
    And I set the following fields to these values:
      | Username                        | user01        |
      | Choose an authentication method | ldap_syncplus |
      | First name                      | Foo           |
      | Surname                         | Bar           |
      | Email address                   | foo@bar.com   |
      | New password                    | Hello!123     |
    And I press "Create user"
    And I navigate to "Users > Accounts > Browse list of users" in site administration
    Then I should see "User 01" in the "#users" "css_element"
    And I click on "Edit" "link" in the "User 01" "table_row"
    And the field "Username" matches value "user01"
    And the field "First name" matches value "User"
    And the field "Surname" matches value "01"
    And the field "Email address" matches value "user01@example.com"

  Scenario: First login via email should be possible without an existing Moodle account
    Given the following config values are set as admin:
      | config            | value |
      | authloginviaemail | 1     |
    When I follow "Log in"
    And I set the field "Username" to "user01@example.com"
    And I set the field "Password" to "password1"
    And I press "Log in"
    Then I should see "Welcome, User"
    And I should not see "Invalid login"

  Scenario: First login via username should be possible without an existing Moodle account (Countercheck / Moodle core behaviour)
    When I follow "Log in"
    And I set the field "Username" to "user01"
    And I set the field "Password" to "password1"
    And I press "Log in"
    Then I should see "Welcome, User"
    And I should not see "Invalid login"

  Scenario: Update user profile fields from LDAP (Moodle core behaviour)
    Given the following "users" exist:
      # user01 exists in the LDAP server.
      | username | firstname | lastname | email              | auth          |
      | user01   | User      | 01       | user01@example.com | ldap_syncplus |
    When I log in as "admin"
    And I navigate to "Users > Accounts > Browse list of users" in site administration
    And I should see "User 01" in the "#users" "css_element"
    And I should not see "Foo Bar" in the "#users" "css_element"
    And I click on "Edit" "link" in the "User 01" "table_row"
    And I set the following fields to these values:
      | First name    | Foo         |
      | Surname       | Bar         |
      | Email address | foo@bar.com |
    And I press "Update profile"
    And I navigate to "Users > Accounts > Browse list of users" in site administration
    And I should not see "User 01" in the "#users" "css_element"
    And I should see "Foo Bar" in the "#users" "css_element"
    And I run the scheduled task "\auth_ldap_syncplus\task\sync_task"
    And I reload the page
    Then I should see "User 01" in the "#users" "css_element"
    And I should not see "Foo Bar" in the "#users" "css_element"
    And I click on "Edit" "link" in the "User 01" "table_row"
    And the field "Username" matches value "user01"
    And the field "First name" matches value "User"
    And the field "Surname" matches value "01"
    And the field "Email address" matches value "user01@example.com"
