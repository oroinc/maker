@codegenerator
@codegenerator-min
@fixture-OroUserBundle:user.yml

Feature: Check generated code minimal

  Scenario: Login to backoffice
    Given I login as administrator

  Scenario: Check menu
    When I am on dashboard
    And I click "Acme"
    Then I should see "Entity Minimals"

  Scenario: Check grid Entity before creation items
    When I go to Acme/Example/Entity Minimals
    Then there is no records in grid

  Scenario: Create new Entity Minimal
    When I go to Acme/Example/Entity Minimals
    And I click "Create Entity Minimal"
    And I fill form with:
      | Title | TestTitle |
    And I save and close form
    Then I should see "Entity Minimal has been saved" flash message
    And I should see "TestTitle"

  Scenario: View Entity Two in grid
    When I go to Acme/Example/Entity Minimals
    Then I should see following grid:
      | Title     |
      | TestTitle |

  Scenario: Edit Entity Minimal
    When I go to Acme/Example/Entity Minimals
    And I click "Edit" on row "TestTitle" in grid
    And I fill form with:
      | Title | TestTitleUP |
    And I save and close form
    Then I should see "Entity Minimal has been saved" flash message
    And I should see "TestTitleUP"

  Scenario: Full list of shortcuts
    When I click "Shortcuts"
    And I follow "See full list"
    Then I should see "Create new Entity Minimal"
    And I should see "Show Entity Minimals"

  Scenario: Search in main org
    When I click "Search"
    And type "TestTitleUP" in "search"
    Then I should see 1 search suggestions
    When I click "Search Submit"
    Then I should be on Search Result page
    And I should see following search entity types:
      | Type            | N | isSelected |
      | All             | 1 | yes        |
      | Entity Minimals | 1 |            |
    And number of records should be 1
    And I should see following search results:
      | Title       | Type           |
      | TestTitleUP | Entity Minimal |

  Scenario: Check exported and imported Entity Minimal
    Given I go to Acme/Example/Entity Minimals

    When I click "Export"
    Then I should see "Export started successfully. You will receive email notification upon completion." flash message
    And Email should contains the following "Export performed successfully. 1 entity minimals were exported." text
    When take the link from email and download the file from this link
    Then the downloaded file from email contains at least the following data:
      | Title       |
      | TestTitleUP |

    When fill import file with data:
      | id | Title              |
      |    | NewMin             |
      | 1  | TestTitleUP_import |
    And I import file
    Then Email should contains the following "Errors: 0 processed: 2, read: 2, added: 1, updated: 0, replaced: 1" text
    When I reload the page
    And I should see following grid containing rows:
      | Title              |
      | NewMin             |
      | TestTitleUP_import |

  Scenario: Delete Entity One
    And I click "Delete" on row "TestTitleUP_import" in grid
    And I confirm deletion
    Then I should see "Entity Minimal deleted" flash message
