@codegenerator
Feature: Check generated entity in entity management

  Scenario: Login to backoffice
    Given I login as administrator

  Scenario: Check new entities in Entity Management grid
    When I go to System / Entities / Entity Management
    And I filter Name as contains "Entity"
    Then I should see following grid containing rows:
      | Label               | Name              |
      | Entity One          | EntityOne         |
      | Entity Two          | EntityTwo         |
      | Entity Without Crud | EntityWithoutCrud |

  Scenario: Check fields in View "Entity One" entity
    When I click "View" on row "Entity One" in grid
    Then I should see following grid containing rows:
      | Name                       | Data Type       | Label                          | Auditable |
      | bigintField                | BigInt          | Bigint Field                   | Yes       |
      | booleanField               | Boolean         | Boolean Field                  | Yes       |
      | createdAt                  | DateTime        | Created At                     | No        |
      | dateField                  | Date            | Date Field                     | Yes       |
      | datetimeField              | DateTime        | Datetime Field                 | Yes       |
      | decimalField               | Decimal         | Decimal Field                  | Yes       |
      | entityWithoutCruds         | System relation | Entity Without Cruds           | No        |
      | enum_field                 | Select          | Enum Field                     | Yes       |
      | floatField                 | Float           | Float Field                    | Yes       |
      | htmlField                  | Text            | Html Field                     | Yes       |
      | id                         | Integer         | ID                             | No        |
      | image_field                | Image           | Image Field                    |           |
      | integerField               | Integer         | Integer Field                  | Yes       |
      | manyToManyInternalRelation | System relation | Many To Many Internal Relation | Yes       |
      | manyToOneExternalRelation  | System relation | Many To One External Relation  | Yes       |
      | manyToManyExternalRelation | System relation | Many To Many External Relation | Yes       |
      | manyToOneInternalRelation  | System relation | Many To One Internal Relation  | Yes       |
      | multienum_field            | Multi-Select    | Multienum Field                | Yes       |
      | organization               | System relation | Organization                   | Yes       |
      | owner                      | System relation | Owner                          | Yes       |
      | smallintField              | SmallInt        | Smallint Field                 | Yes       |
      | stringField                | String          | String Field                   | Yes       |
      | textField                  | Text            | Text Field                     | No        |
      | updatedAt                  | DateTime        | Updated At                     | No        |
      | wysiwygField               | WYSIWYG         | Wysiwyg Field                  | Yes       |

  Scenario: Check fields in View "Entity Two" entity
    When I go to System / Entities / Entity Management
    And I filter Name as contains "Entity"
    And I click "View" on row "Entity Two" in grid
    Then I should see following grid containing rows:
      | Name                                 | Data Type       | Label                                      |
      | createdAt                            | DateTime        | Created At                                 |
      | emailField                           | String          | Email Field                                |
      | firstName                            | String          | First Name                                 |
      | id                                   | Integer         | ID                                         |
      | lastName                             | String          | Last Name                                  |
      | manyToManyInternalRelationEntityOnes | System relation | Many To Many Internal Relation Entity Ones |
      | manyToOneInternalRelationEntityOnes  | System relation | Many To One Internal Relation Entity Ones  |
      | middleName                           | String          | Middle Name                                |
      | namePrefix                           | String          | Name Prefix                                |
      | nameSuffix                           | String          | Name Suffix                                |
      | organization                         | System relation | Organization                               |
      | owner                                | System relation | Owner                                      |
      | phoneField                           | String          | Phone Field                                |
      | relatedUser                          | System relation | Related User                               |
      | updatedAt                            | DateTime        | Updated At                                 |

  Scenario: Check fields in View "Entity Without Crud" entity
    When I go to System / Entities / Entity Management
    And I filter Name as contains "Entity"
    And I click "View" on row "Entity Without Crud" in grid
    Then I should see following grid containing rows:
      | Name         | Data Type       | Label         |
      | createdAt    | DateTime        | Created At    |
      | customer     | System relation | Customer      |
      | customerUser | System relation | Customer User |
      | entityOne    | System relation | Entity One    |
      | id           | Integer         | ID            |
      | organization | System relation | Organization  |
      | percentField | Float           | Percent Field |
      | textField    | Text            | Text Field    |
      | updatedAt    | DateTime        | Updated At    |

  Scenario: Check new field one_to_many_external_relation in Product in Entity Management
    When I go to System / Entities / Entity Management
    And I filter Name as is equal to "Product"
    And I click "View" on row "Product" in grid
    Then I should see following grid containing rows:
      | Name                    | Data Type   | Label      |
      | acme_example_entity_one | Many to one | Entity One |
