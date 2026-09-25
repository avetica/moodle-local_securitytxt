@local @local_securitytxt
Feature: Switching how security.txt is published
  In order to never take a working security.txt offline by mistake
  As a manager
  I need a switch of publication method to only take effect when the new method is complete

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | manager1 | Manager   | One      | manager1@example.com |
    And the following "system role assigns" exist:
      | user     | role    | contextlevel | reference |
      | manager1 | manager | System       |           |
    And the following config values are set as admin:
      | config      | value                       | plugin            |
      | mode        | fields                      | local_securitytxt |
      | contact     | mailto:security@example.org | local_securitytxt |
      | expires     | 2099-12-31                  | local_securitytxt |
      | redirecturl |                             | local_securitytxt |
    And I log in as "manager1"
    And I visit "/admin/settings.php?section=local_securitytxt"

  # Test scenario 8.4, sad path: the switch is refused and the fields stay published.
  Scenario: Switching to redirect without an address keeps the fields mode
    When I set the field "How to publish security.txt" to "Redirect to an existing security.txt"
    And I set the field "Address of the existing security.txt" to ""
    And I press "Save changes"
    Then I should see "The publication method has not been changed"
    And I should see "Enter the address of your existing security.txt."
    And I visit "/admin/settings.php?section=local_securitytxt"
    And the field "How to publish security.txt" matches value "Fill in the fields below"

  # Test scenario 8.4, sad path: an insecure address is refused the same way.
  Scenario: Switching to redirect with a plain http address keeps the fields mode
    When I set the field "How to publish security.txt" to "Redirect to an existing security.txt"
    And I set the field "Address of the existing security.txt" to "http://www.example.org/.well-known/security.txt"
    And I press "Save changes"
    Then I should see "The publication method has not been changed"
    And I should see "Enter a complete address starting with https://."
    And I visit "/admin/settings.php?section=local_securitytxt"
    And the field "How to publish security.txt" matches value "Fill in the fields below"

  # Test scenario 8.4, happy path.
  Scenario: Switching to redirect with a valid address is saved
    When I set the field "How to publish security.txt" to "Redirect to an existing security.txt"
    And I set the field "Address of the existing security.txt" to "https://www.example.org/.well-known/security.txt"
    And I press "Save changes"
    Then I should see "Changes saved"
    And I should not see "The publication method has not been changed"
    And I visit "/admin/settings.php?section=local_securitytxt"
    And the field "How to publish security.txt" matches value "Redirect to an existing security.txt"
    And the field "Address of the existing security.txt" matches value "https://www.example.org/.well-known/security.txt"
