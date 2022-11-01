@codegenerator
Feature: Check marketing list

  Scenario: Successful creating a marketing list based on generated entity
    Given I login as administrator
    When I go to Marketing/ Marketing Lists
    And I click "Create Marketing List"
    And I fill form with:
      | Name   | Test Marketing List |
      | Entity | Entity Two          |
      | Type   | Dynamic             |
    Then I should see "Important: At least one column with contact information must be selected. Available contact information fields: Email Field Phone Field"
    And I add the following columns:
      | Email Field |
      | Phone Field |
    And I save and close form
    Then I should see "Marketing List saved" flash message
