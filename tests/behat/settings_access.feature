@local @local_securitytxt
Feature: Restricted access to the Security.txt settings
  In order to keep a customer's disclosure policy under control
  As Avetica
  I need the settings page to be limited to users holding local/securitytxt:manage

  # Test scenario 6.1 (a user without the capability is refused) is not scriptable here: Moodle
  # answers with an access-denied exception, which fails any Behat step outright rather than
  # rendering a page to assert against. settings_access_test.php covers it directly instead, by
  # asserting that the settings page's own check_access() returns false for such a user.

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | manager1 | Manager   | One      | manager1@example.com |
    And the following "system role assigns" exist:
      | user     | role    | contextlevel | reference |
      | manager1 | manager | System       |           |

  Scenario: A manager holding the capability can open the settings
    Given I log in as "manager1"
    When I visit "/admin/settings.php?section=local_securitytxt"
    Then I should see "Preferred languages"
