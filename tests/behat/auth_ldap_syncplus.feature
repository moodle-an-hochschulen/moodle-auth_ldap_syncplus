@auth @auth_ldap_syncplus
Feature: Checking that all settings are shown
  In order to be able to configure the auth_ldap_syncplus plugin
  As admin
  I need to be able to see the equivalent settings

  # This is the only check that is possible to do with Behat tests. The functionality behind cannot be tested with Behat tests.
  Scenario: Check if all LDAP server (Sync Plus) settings are there
    Given I log in as "admin"
    And I navigate to "Plugins > Authentication >  Manage authentication" in site administration
    And I click on "Settings" "link" in the "LDAP server (Sync Plus)" "table_row"
    Then I should see "LDAP server (Sync Plus)" in the "#region-main .settingsform" "css_element"
    And the "Removed ext user" select box should contain "Suspend internal and fully delete internal after grace period"
    And I should see "Fully deleting grace period" in the "#admin-removeuser_graceperiod" "css_element"
    And I should see "Add new users" in the "#admin-sync_script_createuser_enabled" "css_element"
