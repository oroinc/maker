@codegenerator
@fixture-OroUserBundle:user.yml

Feature: Check generated code full

  Scenario: Login to backoffice
    Given I login as administrator

  Scenario: Check menu
    When I am on dashboard
    And I click "Acme"
    Then I should see "Entity Ones"
    And I should see "Entity Twos"
    And I should not see "Entity Without Crud"

  Scenario: Check grid Entity before creation items
    When I go to Acme/Example/Entity Twos
    Then there is no records in grid
    When I go to Acme/Example/Entity Ones
    Then there is no records in grid

  Scenario Outline: Create new Entity Two
    When I go to Acme/Example/Entity Twos
    And I click "Create Entity Two"
    And I fill "Entity Two Form" with:
      | Name Prefix | NamePrefix<Count> |
      | First Name  | FirstName<Count>  |
      | Middle Name | MiddleName<Count> |
      | Last Name   | LastName<Count>   |
      | Name Suffix | NameSuffix<Count> |
      | Email Field | EmailField<Count> |
      | Phone Field | PhoneField<Count> |
    And I save and close form
    Then I should see "Entity Two has been saved" flash message
    And page has "FirstName<Count> MiddleName<Count> LastName<Count> NameSuffix<Count>" header
    And I should see entity_two with:
      | Name Prefix | NamePrefix<Count> |
      | First Name  | FirstName<Count>  |
      | Middle Name | MiddleName<Count> |
      | Last Name   | LastName<Count>   |
      | Name Suffix | NameSuffix<Count> |
      | Email Field | EmailField<Count> |
      | Phone Field | PhoneField<Count> |
    Examples:
      | Count |
      | 1     |
      | 2     |
      | 3     |
      | 4     |
      | 5     |

  Scenario: View Entity Two in grid
    When I go to Acme/Example/Entity Twos
    Then I should see following grid:
      | Name Prefix | First Name | Middle Name | Last Name | Name Suffix | Email Field | Phone Field |
      | NamePrefix1 | FirstName1 | MiddleName1 | LastName1 | NameSuffix1 | EmailField1 | PhoneField1 |
      | NamePrefix2 | FirstName2 | MiddleName2 | LastName2 | NameSuffix2 | EmailField2 | PhoneField2 |
      | NamePrefix3 | FirstName3 | MiddleName3 | LastName3 | NameSuffix3 | EmailField3 | PhoneField3 |
      | NamePrefix4 | FirstName4 | MiddleName4 | LastName4 | NameSuffix4 | EmailField4 | PhoneField4 |
      | NamePrefix5 | FirstName5 | MiddleName5 | LastName5 | NameSuffix5 | EmailField5 | PhoneField5 |

  Scenario: Edit Entity Two
    When I go to Acme/Example/Entity Twos
    And I click "Edit" on row "NamePrefix5" in grid
    And I fill "Entity Two Form" with:
      | Name Prefix | NamePrefixUpdate |
      | First Name  | FirstNameUpdate  |
      | Middle Name | MiddleNameUpdate |
      | Last Name   | LastNameUpdate   |
      | Name Suffix | NameSuffixUpdate |
      | Email Field | EmailFieldUpdate |
      | Phone Field | PhoneFieldUpdate |
    And I save and close form
    Then I should see "Entity Two has been saved" flash message
    And I should see entity_two with:
      | Name Prefix | NamePrefixUpdate |
      | First Name  | FirstNameUpdate  |
      | Middle Name | MiddleNameUpdate |
      | Last Name   | LastNameUpdate   |
      | Name Suffix | NameSuffixUpdate |
      | Email Field | EmailFieldUpdate |
      | Phone Field | PhoneFieldUpdate |

  Scenario: Validation "String Field" in Entity One
    When I go to Acme/Example/Entity Ones
    And I click "Create Entity One"
    And I save and close form
    Then I should see validation errors:
      | String Field | This value should not be blank. |
    And I fill "Entity One Form" with:
      | String Field | a |
    And I should see "This value is too short. It should have 2 characters or more."
    And I fill "Entity One Form" with:
      | String Field | check max length |
    And I should see "This value is too long. It should have 10 characters or less."
    And I click "Cancel"

  Scenario Outline: Create new Entity One
    When I go to Acme/Example/Entity Ones
    And I click "Create Entity One"
    And I fill "Entity One Form" with:
      | Integer Field                 | 10                             |
      | Float Field                   | 10.1                           |
      | Decimal Field                 | 20                             |
      | Smallint Field                | 1                              |
      | Bigint Field                  | 2                              |
      | String Field                  | String                         |
      | Text Field                    | Text<Count>                    |
      | Enum Field                    | 1                              |
      | Image Field                   | cat1.jpg                       |
      | Multienum Field               | [Furniture]                    |
      | Date Field                    | <Date:Jul 1, 2018>             |
      | DateTime Field                | <DateTime:2018-07-01 11:00 AM> |
      | Many To One Internal Relation | NamePrefix<Count>              |
      | Many To One External Relation | <MTOER>                        |
      | Wysiwyg Field                 | TWYSIWYG                       |
      | Html Field                    | Html Field Text                |
    And I save and close form
    Then I should see "Entity One has been saved" flash message
    And I should see entity_one with:
      | Integer Field                 | 10                                                                                     |
      | Float Field                   | 10.1                                                                                   |
      | Decimal Field                 | 20                                                                                     |
      | Smallint Field                | 1                                                                                      |
      | Bigint Field                  | 2                                                                                      |
      | String Field                  | String                                                                                 |
      | Text Field                    | Text<Count>                                                                            |
      | Enum Field                    | 1                                                                                      |
      | Multienum Field               | Furniture                                                                              |
      | Date Field                    | Jul 1, 2018                                                                            |
      | Datetime Field                | Jul 1, 2018, 11:00 AM                                                                  |
      | Many To One External Relation | <MTOER>                                                                                |
      | Boolean Field                 | No                                                                                     |
      | Many To One Internal Relation | NamePrefix<Count> FirstName<Count> MiddleName<Count> LastName<Count> NameSuffix<Count> |
      | Html Field                    | Html Field Text                                                                        |
    Examples:
      | Count  | MTOER         |
      | 1      | John Doe      |
      | 2      | John Doe      |
      | 3      | John Doe      |
      | 4      | John Doe      |
      | Update | Charlie Sheen |

  Scenario: View Entity One in grid
    When I go to Acme/Example/Entity Ones
    Then I should see following grid:
      | Integer Field | Float Field | Decimal Field | Smallint Field | Bigint Field | String Field | Enum Field | Date Field  | DateTime Field        | Boolean Field | MTOIR            | MTOER         | Owner    |
      | 10            | 10.1        | 20            | 1              | 2            | String       | 1          | Jul 1, 2018 | Jul 1, 2018, 11:00 AM | No            | NamePrefix1      | John Doe      | John Doe |
      | 10            | 10.1        | 20            | 1              | 2            | String       | 1          | Jul 1, 2018 | Jul 1, 2018, 11:00 AM | No            | NamePrefix2      | John Doe      | John Doe |
      | 10            | 10.1        | 20            | 1              | 2            | String       | 1          | Jul 1, 2018 | Jul 1, 2018, 11:00 AM | No            | NamePrefix3      | John Doe      | John Doe |
      | 10            | 10.1        | 20            | 1              | 2            | String       | 1          | Jul 1, 2018 | Jul 1, 2018, 11:00 AM | No            | NamePrefix4      | John Doe      | John Doe |
      | 10            | 10.1        | 20            | 1              | 2            | String       | 1          | Jul 1, 2018 | Jul 1, 2018, 11:00 AM | No            | NamePrefixUpdate | Charlie Sheen | John Doe |

  Scenario: Edit Entity One
    When I go to Acme/Example/Entity Ones
    And I click "Edit" on row "NamePrefixUpdate" in grid
    And I fill "Entity One Form" with:
      | Integer Field                 | -10                    |
      | Float Field                   | -10.1                  |
      | Decimal Field                 | -200                   |
      | Smallint Field                | -2                     |
      | Bigint Field                  | -2000                  |
      | String Field                  | String upd             |
      | Text Field                    | Text update            |
      | Enum Field                    | More than 2            |
      | Image Field                   | cat2.jpg               |
      | Multienum Field               | [Refrigerator,TV]      |
      | Date Field                    | <Date:Jul 1, 2022>     |
      | Boolean Field                 | True                   |
      | Many To One Internal Relation | NamePrefix1            |
      | Wysiwyg Field                 | TWYSIWYG update        |
      | Html Field                    | Html Field Text update |
    And I save and close form
    Then I should see "Entity One has been saved" flash message
    And I should see entity_one with:
      | Integer Field                 | -10                                                      |
      | Float Field                   | -10.1                                                    |
      | Decimal Field                 | -200                                                     |
      | Smallint Field                | -2                                                       |
      | Bigint Field                  | -2000                                                    |
      | String Field                  | String upd                                               |
      | Text Field                    | Text update                                              |
      | Enum Field                    | More than 2                                              |
      | Multienum Field               | Refrigerator, TV                                         |
      | Date Field                    | Jul 1, 2022                                              |
      | Many To One External Relation | Charlie Sheen                                            |
      | Boolean Field                 | Yes                                                      |
      | Many To One Internal Relation | NamePrefix1 FirstName1 MiddleName1 LastName1 NameSuffix1 |
      | Html Field                    | Html Field Text update                                   |

  Scenario: Full list of shortcuts
    When I click "Shortcuts"
    And I follow "See full list"
    Then I should see "Create new Entity One"
    And I should see "Show Entity Ones"
    And I should see "Create new Entity Two"
    And I should see "Show Entity Twos"

  Scenario: Search in main org
    When I click "Search"
    And type "NamePrefix3" in "search"
    Then I should see 2 search suggestions
    When I click "Search Submit"
    Then I should be on Search Result page
    And I should see following search entity types:
      | Type        | N | isSelected |
      | All         | 2 | yes        |
      | Entity Ones | 1 |            |
      | Entity Twos | 1 |            |
    And number of records should be 2
    And I should see following search results:
      | Title                                                    | Type       |
      | furniture String                                         | Entity One |
      | NamePrefix3 FirstName3 MiddleName3 LastName3 NameSuffix3 | Entity Two |

  Scenario: Search by Entity One
    When I click "Search"
    And I select "Entity One" from search types
    And type "NamePrefix3" in "search"
    Then I should see 1 search suggestions
    When I click "Search Submit"
    Then I should be on Search Result page
    And I should see following search entity types:
      | Type        | N | isSelected |
      | All         | 2 |            |
      | Entity Ones | 1 | yes        |
      | Entity Twos | 1 |            |
    And number of records should be 1
    And I should see following search results:
      | Title            | Type       |
      | furniture String | Entity One |
    When I click "furniture String"
    Then I should see entity_one with:
      | Integer Field                 | 10                                                       |
      | Float Field                   | 10.1                                                     |
      | Decimal Field                 | 20                                                       |
      | Smallint Field                | 1                                                        |
      | Bigint Field                  | 2                                                        |
      | String Field                  | String                                                   |
      | Text Field                    | Text3                                                    |
      | Enum Field                    | 1                                                        |
      | Multienum Field               | Furniture                                                |
      | Date Field                    | Jul 1, 2018                                              |
      | Datetime Field                | Jul 1, 2018, 11:00 AM                                    |
      | Many To One External Relation | John Doe                                                 |
      | Boolean Field                 | No                                                       |
      | Many To One Internal Relation | NamePrefix3 FirstName3 MiddleName3 LastName3 NameSuffix3 |
      | Html Field                    | Html Field Text                                          |

  Scenario: Search by Entity Two
    When I click "Search"
    And I select "Entity Two" from search types
    And type "NamePrefix3" in "search"
    Then I should see 1 search suggestions
    When I click "Search Submit"
    Then I should be on Search Result page
    And I should see following search entity types:
      | Type        | N | isSelected |
      | All         | 2 |            |
      | Entity Ones | 1 |            |
      | Entity Twos | 1 | yes        |
    And number of records should be 1
    And I should see following search results:
      | Title                                                    | Type       |
      | NamePrefix3 FirstName3 MiddleName3 LastName3 NameSuffix3 | Entity Two |
    When I click "NamePrefix3 FirstName3 MiddleName3 LastName3 NameSuffix3"
    Then I should see entity_two with:
      | Name Prefix | NamePrefix3 |
      | First Name  | FirstName3  |
      | Middle Name | MiddleName3 |
      | Last Name   | LastName3   |
      | Name Suffix | NameSuffix3 |
      | Email Field | EmailField3 |
      | Phone Field | PhoneField3 |

  Scenario: Attach Many To One External Relation - User
    When I go to System/ User Management/ Users
    Then click View admin@example.com in grid
    When I click "Attach Entity Two"
    And I fill form with:
      | Entity Two | NamePrefixUpdate |
    And I click "Submit"
    Then I should see following "UserRelationEntityTwoGrid" grid:
      | Name Prefix      | First Name      | Middle Name      | Last Name      | Name Suffix      | Email Field      |
      | NamePrefixUpdate | FirstNameUpdate | MiddleNameUpdate | LastNameUpdate | NameSuffixUpdate | EmailFieldUpdate |

    When I click "Attach Entity Two"
    And I fill form with:
      | Entity Two | NamePrefix2 |
    And I click "Submit"
    Then I should see following "UserRelationEntityTwoGrid" grid:
      | Name Prefix      | First Name      | Middle Name      | Last Name      | Name Suffix      | Email Field      |
      | NamePrefix2      | FirstName2      | MiddleName2      | LastName2      | NameSuffix2      | EmailField2      |
      | NamePrefixUpdate | FirstNameUpdate | MiddleNameUpdate | LastNameUpdate | NameSuffixUpdate | EmailFieldUpdate |

  Scenario: Detach Many To One External Relation - User
    When I go to System/ User Management/ Users
    Then click View admin@example.com in grid

    When I click "Detach" on row "NamePrefix2" in grid "UserRelationEntityTwoGrid"
    And I confirm deletion
    Then I should see "Item deleted" flash message

  Scenario: Check in Entity Two attached user relation
    When I go to Acme/Example/Entity Twos
    And I click "View" on row "FirstNameUpdate" in grid
    Then I should see following "UserEntityTwoMMRelationGrid" grid:
      | First Name | Last Name | Username |
      | John       | Doe       | admin    |

    When I go to Acme/Example/Entity Twos
    And I click "View" on row "FirstName2" in grid
    Then there is no records in "UserEntityTwoMMRelationGrid"

  Scenario: Many To One External Relation in User
    When I go to System/ User Management/ Users
    And click View admin@example.com in grid
    Then I should see following "UserEntityOneMORelationGrid" grid:
      | Integer Field | Float Field | Decimal Field | Smallint Field | Bigint Field | String Field | Enum Field | Date Field  | DateTime Field        | Boolean Field | MTOIR       | MTOER    |
      | 10            | 10.1        | 20            | 1              | 2            | String       | 1          | Jul 1, 2018 | Jul 1, 2018, 11:00 AM | No            | NamePrefix1 | John Doe |
      | 10            | 10.1        | 20            | 1              | 2            | String       | 1          | Jul 1, 2018 | Jul 1, 2018, 11:00 AM | No            | NamePrefix2 | John Doe |
      | 10            | 10.1        | 20            | 1              | 2            | String       | 1          | Jul 1, 2018 | Jul 1, 2018, 11:00 AM | No            | NamePrefix3 | John Doe |
      | 10            | 10.1        | 20            | 1              | 2            | String       | 1          | Jul 1, 2018 | Jul 1, 2018, 11:00 AM | No            | NamePrefix4 | John Doe |

    When I go to System/ User Management/ Users
    And click View charlie@example.com in grid
    Then I should see following "UserEntityOneMORelationGrid" grid:
      | Integer Field | Float Field | Decimal Field | Smallint Field | Bigint Field | String Field | Enum Field  | Date Field  | DateTime Field        | Boolean Field | MTOIR       | MTOER         |
      | -10           | -10.1       | -200          | -2             | -2,000       | String upd   | More than 2 | Jul 1, 2022 | Jul 1, 2018, 11:00 AM | Yes           | NamePrefix1 | Charlie Sheen |

  Scenario: Many To One External Relation View Link
    When I go to Acme/Example/Entity Ones
    And I click "View" on row "NamePrefix1" in grid
    And I click on "ManyToOneExternalRelationViewLink"
    Then page has "John Doe" header

  Scenario: Many To One Internal Relation View Link
    When I go to Acme/Example/Entity Ones
    And I click "View" on row "NamePrefix1" in grid
    And I click "NamePrefix1 FirstName1 MiddleName1 LastName1 NameSuffix1"
    Then page has "FirstName1 MiddleName1 LastName1 NameSuffix1" header

  Scenario: Add Entity Without Crud in Entity One
    When I go to Acme/Example/Entity Ones
    And I click "View" on row "NamePrefix3" in grid
    And I click "Add Entity Without Crud"
    And I click "Submit"
    And I fill form with:
      | Text Field        | Text Field NamePrefix3 |
      | Percent Field (%) | 17                     |
    And I click "Submit"
    Then I should see "Entity Without Crud has been saved" flash message
    And I should see following "EntityWithoutCrudGrid" grid:
      | Text Field             | Percent Field |
      | Text Field NamePrefix3 | 17%           |

    When I go to Acme/Example/Entity Ones
    And I click "View" on row "NamePrefix2" in grid
    And I click "Add Entity Without Crud"
    And I click "Submit"
    Then I should see validation errors:
      | Text Field | This value should not be blank. |

    When I fill form with:
      | Text Field        | Text Field in Entity Without Crud |
      | Percent Field (%) | t                                 |
    And I click "Submit"
    Then I should see validation errors:
      | Percent Field (%) | Please enter a percentage value. |
    When I fill form with:
      | Percent Field (%) | 15 |
    And I click "Submit"
    Then I should see "Entity Without Crud has been saved" flash message
    And I should see following "EntityWithoutCrudGrid" grid:
      | Text Field                        | Percent Field |
      | Text Field in Entity Without Crud | 15%           |

    When I click "Add Entity Without Crud"
    And I fill form with:
      | Text Field        | Text Field Test |
      | Percent Field (%) | 50              |
    And I click "Submit"
    Then I should see "Entity Without Crud has been saved" flash message
    And I should see following "EntityWithoutCrudGrid" grid:
      | Text Field                        | Percent Field |
      | Text Field in Entity Without Crud | 15%           |
      | Text Field Test                   | 50%           |

  Scenario: Edit Entity Without Crud in Entity One
    When I click "Edit" on row "Text Field in Entity Without Crud" in grid "EntityWithoutCrudGrid"
    And I fill form with:
      | Text Field        | New Field |
      | Percent Field (%) | 25        |
    And I click "Submit"
    Then I should see "Entity Without Crud has been saved" flash message
    And I should see following "EntityWithoutCrudGrid" grid:
      | Text Field      | Percent Field |
      | New Field       | 25%           |
      | Text Field Test | 50%           |

  Scenario: Filter Entity Without Crud
    When I filter Percent Field as equals "50%"
    Then I should see following "EntityWithoutCrudGrid" grid:
      | Text Field      | Percent Field |
      | Text Field Test | 50%           |
    And I reset Percent Field filter

    When I filter Text Field as contains "New"
    Then I should see following "EntityWithoutCrudGrid" grid:
      | Text Field | Percent Field |
      | New Field  | 25%           |
    And I reset Text Field filter

  Scenario: Sorting Entity Without Crud
    When sort grid by Percent Field
    Then 25% must be first record
    But when I sort grid by Percent Field again
    Then 50% must be first record

    When sort grid by Text Field
    Then New Field must be first record
    But when I sort grid by Text Field again
    Then Text Field Test must be first record

  Scenario: Check exported and imported Entity Without Crud
    When I click "Export"
    Then I should see "Export started successfully. You will receive email notification upon completion." flash message
    And Email should contains the following "Export performed successfully. 2 entity without cruds were exported." text
    When take the link from email and download the file from this link
    Then the downloaded file from email contains at least the following data:
      | Percent Field | Text Field      | Organization Name |
      | 0.25          | New Field       | ORO               |
      | 0.5           | Text Field Test | ORO               |

    When fill import file with data:
      | id | Percent Field | Text Field           | Organization Name |
      |    | 0.35          | Text Field in import | ORO               |
      | 3  | 0.27          | Change Text Field    | ORO               |
    And I import file
    Then Email should contains the following "Errors: 0 processed: 2, read: 2, added: 1, updated: 0, replaced: 1" text
    And I reload the page
    And I should see following "EntityWithoutCrudGrid" grid containing rows:
      | Text Field           | Percent Field |
      | Change Text Field    | 27%           |
      | New Field            | 25%           |
      | Text Field in import | 35%           |

  Scenario: Delete Entity Without Crud in Entity One
    When I click "Delete" on row "New Field" in grid "EntityWithoutCrudGrid"
    And I confirm deletion
    Then I should see "Entity Without Crud deleted" flash message
    And sort grid by "Percent Field"
    And I should see following "EntityWithoutCrudGrid" grid:
      | Text Field           | Percent Field |
      | Change Text Field    | 27%           |
      | Text Field in import | 35%           |

  Scenario: Attach Many To Many Internal Relation in Entity One
    When I go to Acme/Example/Entity Ones
    Then I click "View" on row "NamePrefix2" in grid

    When I click "Attach Many To Many Internal Relation"
    And I fill form with:
      | Entity Two | NamePrefixUpdate |
    And I click "Submit"
    Then I should see "Many To Many Internal Relation attached" flash message
    And I should see following "EntityTwoCrudGrid" grid:
      | Name Prefix      | First Name      | Middle Name      | Last Name      | Name Suffix      | Email Field      |
      | NamePrefixUpdate | FirstNameUpdate | MiddleNameUpdate | LastNameUpdate | NameSuffixUpdate | EmailFieldUpdate |

    When I click "Attach Many To Many Internal Relation"
    And I open select entity popup for field "OperationEntity" in form "EntityOperationForm"
    Then I should see following grid containing rows:
      | Name Prefix      | First Name      | Middle Name      | Last Name      | Name Suffix      |
      | NamePrefix1      | FirstName1      | MiddleName1      | LastName1      | NameSuffix1      |
      | NamePrefix2      | FirstName2      | MiddleName2      | LastName2      | NameSuffix2      |
      | NamePrefix3      | FirstName3      | MiddleName3      | LastName3      | NameSuffix3      |
      | NamePrefix4      | FirstName4      | MiddleName4      | LastName4      | NameSuffix4      |
      | NamePrefixUpdate | FirstNameUpdate | MiddleNameUpdate | LastNameUpdate | NameSuffixUpdate |
    When click on NamePrefix2 in grid
    And I click "Submit"
    Then I should see "Many To Many Internal Relation attached" flash message
    And I should see following "EntityTwoCrudGrid" grid:
      | Name Prefix      | First Name      | Middle Name      | Last Name      | Name Suffix      | Email Field      |
      | NamePrefix2      | FirstName2      | MiddleName2      | LastName2      | NameSuffix2      | EmailField2      |
      | NamePrefixUpdate | FirstNameUpdate | MiddleNameUpdate | LastNameUpdate | NameSuffixUpdate | EmailFieldUpdate |

#    When I click "Attach Many To Many Internal Relation"
#    And I open create entity popup for field "OperationEntity" in form "EntityOperationForm"
#    Then I fill form with:
#      | Name Prefix | NamePrefixTest |
#      | First Name  | FirstNameTest  |
#      | Middle Name | MiddleNameTest |
#      | Last Name   | LastNameTest   |
#      | Name Suffix | NameSuffixTest |
#      | Email Field | EmailFieldTest |
#      | Phone Field | PhoneFieldTest |
#    And I click "Save"
#    When I click "Submit"
#    Then I should see "Many To Many Internal Relation attached" flash message
#    And I should see following "EntityTwoCrudGrid" grid:
#      | Name Prefix      | First Name      | Middle Name      | Last Name      | Name Suffix      | Email Field      |
#      | NamePrefix2      | FirstName2      | MiddleName2      | LastName2      | NameSuffix2      | EmailField2      |
#      | NamePrefixUpdate | FirstNameUpdate | MiddleNameUpdate | LastNameUpdate | NameSuffixUpdate | EmailFieldUpdate |
#      | NamePrefixTest   | FirstNameTest   | MiddleNameTest   | LastNameTest   | NameSuffixTest   | EmailFieldTest   |

    When I click "Detach" on row "NamePrefixUpdate" in grid "EntityTwoCrudGrid"
    And I confirm deletion
    Then I should see "Item deleted" flash message
    And I should see following "EntityTwoCrudGrid" grid:
      | Name Prefix    | First Name    | Middle Name    | Last Name    | Name Suffix    | Email Field    |
      | NamePrefix2    | FirstName2    | MiddleName2    | LastName2    | NameSuffix2    | EmailField2    |
#      | NamePrefixTest | FirstNameTest | MiddleNameTest | LastNameTest | NameSuffixTest | EmailFieldTest |

  Scenario: Check in Entity Two attached entity ones relation
    When I go to Acme/Example/Entity Twos
    And I click "View" on row "NamePrefix2" in grid
    Then I should see following "EntityTwoEntityOneMMrelation" grid:
      | Integer Field | Float Field | Decimal Field | Smallint Field | Bigint Field | String Field | Enum Field | Date Field  | DateTime Field        | Boolean Field | MTOIR       | MTOER    |
      | 10            | 10.1        | 20            | 1              | 2            | String       | 1          | Jul 1, 2018 | Jul 1, 2018, 11:00 AM | No            | NamePrefix2 | John Doe |

  Scenario: Create customer
    When I go to Customers / Customers
    And I click "Create Customer"
    And I fill in "Name" with "Batman"
    And I save and close form
    Then I should see "Customer has been saved" flash message

  Scenario: Attach Many To Many External Relation in Entity One
    When I go to Acme/Example/Entity Ones
    And I click "View" on row "NamePrefix2" in grid
    And I click "Attach Many To Many External Relation"
    And fill form with:
      | Customer | Batman |
    And I click "Submit"
    Then I should see "Many To Many External Relation attached" flash message
    And I should see following "EntityOneCustomerMMRelationGrid" grid:
      | Name   |
      | Batman |

    When I go to Acme/Example/Entity Ones
    And I click "View" on row "NamePrefix3" in grid
    And I click "Attach Many To Many External Relation"
    And fill form with:
      | Customer | Batman |
    And I click "Submit"
    Then I should see "Many To Many External Relation attached" flash message
    And I should see following "EntityOneCustomerMMRelationGrid" grid:
      | Name   |
      | Batman |

  Scenario: Check in Customer attached entity ones relation
    When I go to Customers / Customers
    And I click "View" on row "Batman" in grid
    Then I should see following "CustomerEntityOneMMRelationGrid" grid:
      | Integer Field | Float Field | Decimal Field | Smallint Field | Bigint Field | String Field | Enum Field | Date Field  | DateTime Field        | Boolean Field | MTOIR       | MTOER    |
      | 10            | 10.1        | 20            | 1              | 2            | String       | 1          | Jul 1, 2018 | Jul 1, 2018, 11:00 AM | No            | NamePrefix2 | John Doe |
      | 10            | 10.1        | 20            | 1              | 2            | String       | 1          | Jul 1, 2018 | Jul 1, 2018, 11:00 AM | No            | NamePrefix3 | John Doe |

  Scenario: Detach Many To Many External Relation in Entity One
    When I go to Acme/Example/Entity Ones
    And I click "View" on row "NamePrefix2" in grid
    And I click "Detach" on row "Batman" in grid "EntityOneCustomerMMRelationGrid"
    And I confirm deletion
    Then I should see "Item deleted" flash message

  Scenario: Check in Customer detached entity ones relation
    When I go to Customers / Customers
    And I click "View" on row "Batman" in grid
    Then I should see following "CustomerEntityOneMMRelationGrid" grid:
      | Integer Field | Float Field | Decimal Field | Smallint Field | Bigint Field | String Field | Enum Field | Date Field  | DateTime Field        | Boolean Field | MTOIR       | MTOER    |
      | 10            | 10.1        | 20            | 1              | 2            | String       | 1          | Jul 1, 2018 | Jul 1, 2018, 11:00 AM | No            | NamePrefix3 | John Doe |

  Scenario: Creating a product with external relation one_to_many Entity One
    When go to Products/ Products
    And click "Create Product"
    And fill form with:
      | Type | Simple |
    And click "Continue"
    And fill "Create Product Form" with:
      | SKU               | Lenovo_Vibe_sku           |
      | Name              | Lenovo Vibe               |
      | Status            | Enable                    |
      | Unit Of Quantity  | item                      |
      | Description       | Product Description       |
      | Short Description | Product_Short_Description |
    And I fill form with:
      | Entity One | String upd |
    And save and close form
    Then should see "Product has been saved" flash message
    And I should see product with:
      | Entity One | String upd |

    When I click "Edit Product"
    And I open select entity popup for field "Entity One"
    Then I should see following "EntityOneGrid" grid containing rows:
      | Integer Field | Float Field | Decimal Field | Smallint Field | Bigint Field | String Field | Enum Field  | Date Field  | DateTime Field        | Boolean Field | MTOIR       | MTOER         |
      | 10            | 10.1        | 20            | 1              | 2            | String       | 1           | Jul 1, 2018 | Jul 1, 2018, 11:00 AM | No            | NamePrefix1 | John Doe      |
      | 10            | 10.1        | 20            | 1              | 2            | String       | 1           | Jul 1, 2018 | Jul 1, 2018, 11:00 AM | No            | NamePrefix2 | John Doe      |
      | 10            | 10.1        | 20            | 1              | 2            | String       | 1           | Jul 1, 2018 | Jul 1, 2018, 11:00 AM | No            | NamePrefix3 | John Doe      |
      | 10            | 10.1        | 20            | 1              | 2            | String       | 1           | Jul 1, 2018 | Jul 1, 2018, 11:00 AM | No            | NamePrefix4 | John Doe      |
      | -10           | -10.1       | -200          | -2             | -2,000       | String upd   | More than 2 | Jul 1, 2022 | Jul 1, 2018, 11:00 AM | Yes           | NamePrefix1 | Charlie Sheen |
    When click on NamePrefix2 in grid
    And save and close form
    Then should see "Product has been saved" flash message
    And I should see product with:
      | Entity One | String |

  Scenario: Check in Entity Ones attached products relation
    When I go to Acme/Example/Entity Ones
    And I click "View" on row "NamePrefix2" in grid
    Then I should see following "EntityOneproductOMrelation" grid containing rows:
      | SKU             | Name        | Status  |
      | Lenovo_Vibe_sku | Lenovo Vibe | Enabled |

  Scenario: Sorting Entity One grid
    Given I go to Acme/Example/Entity Ones

    When sort grid by Integer Field
    Then -10 must be first record
    But when I sort grid by Integer Field again
    Then 10 must be first record

    When sort grid by Float Field
    Then -10.1 must be first record
    But when I sort grid by Float Field again
    Then 10.1 must be first record

    When sort grid by Decimal Field
    Then -200 must be first record
    But when I sort grid by Decimal Field again
    Then 20 must be first record

    When sort grid by Smallint Field
    Then -2 must be first record
    But when I sort grid by Smallint Field again
    Then 1 must be first record

    When sort grid by Bigint Field
    Then -2,000 must be first record
    But when I sort grid by Bigint Field again
    Then 2 must be first record

    When sort grid by String Field
    Then String must be first record
    But when I sort grid by String Field again
    Then String upd must be first record

    When sort grid by Enum Field
    Then 1 must be first record
    But when I sort grid by Enum Field again
    Then More than 2 must be first record

    When sort grid by Date Field
    Then Jul 1, 2018 must be first record
    But when I sort grid by Date Field again
    Then Jul 1, 2022 must be first record

    When sort grid by MTOER
    Then Charlie Sheen must be first record
    But when I sort grid by MTOER again
    Then John Doe must be first record

    When sort grid by MTOIR
    Then NamePrefix1 must be first record
    But when I sort grid by MTOIR again
    Then NamePrefix4 must be first record

  Scenario: Filter Entity One grid
    Given I go to Acme/Example/Entity Ones
    And sort grid by "MTOIR"

    When I check "NamePrefix1" in Many To One Internal Relation filter
    Then I should see following grid:
      | Integer Field | Float Field | Decimal Field | Smallint Field | Bigint Field | String Field | Enum Field  | Date Field  | DateTime Field        | Boolean Field | MTOIR       | MTOER         |
      | 10            | 10.1        | 20            | 1              | 2            | String       | 1           | Jul 1, 2018 | Jul 1, 2018, 11:00 AM | No            | NamePrefix1 | John Doe      |
      | -10           | -10.1       | -200          | -2             | -2,000       | String upd   | More than 2 | Jul 1, 2022 | Jul 1, 2018, 11:00 AM | Yes           | NamePrefix1 | Charlie Sheen |
    And I reset Many To One Internal Relation filter

    When I filter Integer Field as equals "10"
    Then I should see following grid containing rows:
      | Integer Field | Float Field | Decimal Field | Smallint Field | Bigint Field | String Field | Enum Field | Date Field  | DateTime Field        | Boolean Field | MTOIR       | MTOER    |
      | 10            | 10.1        | 20            | 1              | 2            | String       | 1          | Jul 1, 2018 | Jul 1, 2018, 11:00 AM | No            | NamePrefix1 | John Doe |
      | 10            | 10.1        | 20            | 1              | 2            | String       | 1          | Jul 1, 2018 | Jul 1, 2018, 11:00 AM | No            | NamePrefix2 | John Doe |
      | 10            | 10.1        | 20            | 1              | 2            | String       | 1          | Jul 1, 2018 | Jul 1, 2018, 11:00 AM | No            | NamePrefix3 | John Doe |
      | 10            | 10.1        | 20            | 1              | 2            | String       | 1          | Jul 1, 2018 | Jul 1, 2018, 11:00 AM | No            | NamePrefix4 | John Doe |
    And I reset Integer Field filter

    When I filter Float Field as equals "10.1"
    And I filter Enum Field as is any of "1"
    And I filter Bigint Field as equals "2"
    Then I should see following grid:
      | Integer Field | Float Field | Decimal Field | Smallint Field | Bigint Field | String Field | Enum Field | Date Field  | DateTime Field        | Boolean Field | MTOIR       | MTOER    |
      | 10            | 10.1        | 20            | 1              | 2            | String       | 1          | Jul 1, 2018 | Jul 1, 2018, 11:00 AM | No            | NamePrefix1 | John Doe |
      | 10            | 10.1        | 20            | 1              | 2            | String       | 1          | Jul 1, 2018 | Jul 1, 2018, 11:00 AM | No            | NamePrefix2 | John Doe |
      | 10            | 10.1        | 20            | 1              | 2            | String       | 1          | Jul 1, 2018 | Jul 1, 2018, 11:00 AM | No            | NamePrefix3 | John Doe |
      | 10            | 10.1        | 20            | 1              | 2            | String       | 1          | Jul 1, 2018 | Jul 1, 2018, 11:00 AM | No            | NamePrefix4 | John Doe |
    And I reset Float Field filter
    And I reset Enum Field filter
    And I reset Bigint Field filter

    When I filter Decimal Field as equals "-200"
    And I filter Smallint Field as equals "-2"
    And I filter String Field as is equal to "String upd"
    Then I should see following grid containing rows:
      | Integer Field | Float Field | Decimal Field | Smallint Field | Bigint Field | String Field | Enum Field  | Date Field  | DateTime Field        | Boolean Field | MTOIR       | MTOER         |
      | -10           | -10.1       | -200          | -2             | -2,000       | String upd   | More than 2 | Jul 1, 2022 | Jul 1, 2018, 11:00 AM | Yes           | NamePrefix1 | Charlie Sheen |
    And I reset Decimal Field filter
    And I reset Smallint Field filter
    And I reset String Field filter

    When I check "Yes" in Boolean Field filter
    And I check "Charlie Sheen" in Many To One External Relation filter
    And I filter "Date Field" as between "Jan 1, 2000" and "Dec 31, 2023"
    And I filter "DateTime Field" as between "Jan 1, 2000 11:30 AM" and "Dec 31, 2023 11:30 AM"
    Then I should see following grid:
      | Integer Field | Float Field | Decimal Field | Smallint Field | Bigint Field | String Field | Enum Field  | Date Field  | DateTime Field        | Boolean Field | MTOIR       | MTOER         |
      | -10           | -10.1       | -200          | -2             | -2,000       | String upd   | More than 2 | Jul 1, 2022 | Jul 1, 2018, 11:00 AM | Yes           | NamePrefix1 | Charlie Sheen |
    And I reset Boolean Field filter
    And I reset Many To One External Relation filter
    And I reset Date Field filter
    And I reset DateTime Field filter

  Scenario: Filter Entity Two grid
    Given I go to Acme/Example/Entity Twos

    When I filter Name Prefix as is equal to "NamePrefix1"
    Then I should see following grid:
      | Name Prefix | First Name | Middle Name | Last Name | Name Suffix | Email Field | Phone Field |
      | NamePrefix1 | FirstName1 | MiddleName1 | LastName1 | NameSuffix1 | EmailField1 | PhoneField1 |
    And I reset Name Prefix filter

    When I filter First Name as is equal to "FirstName2"
    Then I should see following grid:
      | Name Prefix | First Name | Middle Name | Last Name | Name Suffix | Email Field | Phone Field |
      | NamePrefix2 | FirstName2 | MiddleName2 | LastName2 | NameSuffix2 | EmailField2 | PhoneField2 |
    And I reset First Name filter

    When I filter Middle Name as is equal to "MiddleName3"
    Then I should see following grid:
      | Name Prefix | First Name | Middle Name | Last Name | Name Suffix | Email Field | Phone Field |
      | NamePrefix3 | FirstName3 | MiddleName3 | LastName3 | NameSuffix3 | EmailField3 | PhoneField3 |
    And I reset Middle Name filter

    When I filter Last Name as is equal to "LastName4"
    Then I should see following grid:
      | Name Prefix | First Name | Middle Name | Last Name | Name Suffix | Email Field | Phone Field |
      | NamePrefix4 | FirstName4 | MiddleName4 | LastName4 | NameSuffix4 | EmailField4 | PhoneField4 |
    And I reset Last Name filter

    When I filter Name Suffix as is equal to "NameSuffix1"
    And I filter Email Field as is equal to "EmailField1"
    And I filter Phone Field as is equal to "PhoneField1"
    Then I should see following grid:
      | Name Prefix | First Name | Middle Name | Last Name | Name Suffix | Email Field | Phone Field |
      | NamePrefix1 | FirstName1 | MiddleName1 | LastName1 | NameSuffix1 | EmailField1 | PhoneField1 |
    And I reset Name Suffix filter
    And I reset Email Field filter
    And I reset Phone Field filter

  Scenario: Sorting Entity Two grid
    Given I go to Acme/Example/Entity Twos

    When sort grid by Name Prefix
    Then NamePrefix1 must be first record
    But when I sort grid by Name Prefix again
    Then NamePrefixUpdate must be first record

    When sort grid by First Name
    Then FirstName1 must be first record
    But when I sort grid by First Name again
    Then FirstNameUpdate must be first record

    And sort grid by Middle Name
    Then MiddleName1 must be first record
    But when I sort grid by Middle Name again
    Then MiddleNameUpdate must be first record

    When sort grid by Last Name
    Then LastName1 must be first record
    But when I sort grid by Last Name again
    Then LastNameUpdate must be first record

    When sort grid by Name Suffix
    Then NameSuffix1 must be first record
    But when I sort grid by Name Suffix again
    Then NameSuffixUpdate must be first record

    When sort grid by Email Field
    Then EmailField1 must be first record
    But when I sort grid by Email Field again
    Then EmailFieldUpdate must be first record

    When sort grid by Phone Field
    Then PhoneField1 must be first record
    But when I sort grid by Phone Field again
    Then PhoneFieldUpdate must be first record

  Scenario: Check exported and imported Entity Two
    Given I go to Acme/Example/Entity Twos

    When I click "Export"
    Then I should see "Export started successfully. You will receive email notification upon completion." flash message
    And Email should contains the following "Export performed successfully. 5 entity twos were exported." text
    When take the link from email and download the file from this link
    Then the downloaded file from email contains at least the following data:
      | Name Prefix      | First Name      | Middle Name      | Last Name      | Name Suffix      | Email Field      | Phone Field      |
      | NamePrefix1      | FirstName1      | MiddleName1      | LastName1      | NameSuffix1      | EmailField1      | PhoneField1      |
      | NamePrefix2      | FirstName2      | MiddleName2      | LastName2      | NameSuffix2      | EmailField2      | PhoneField2      |
      | NamePrefix3      | FirstName3      | MiddleName3      | LastName3      | NameSuffix3      | EmailField3      | PhoneField3      |
      | NamePrefix4      | FirstName4      | MiddleName4      | LastName4      | NameSuffix4      | EmailField4      | PhoneField4      |
      | NamePrefixUpdate | FirstNameUpdate | MiddleNameUpdate | LastNameUpdate | NameSuffixUpdate | EmailFieldUpdate | PhoneFieldUpdate |
#      | NamePrefixTest   | FirstNameTest   | MiddleNameTest   | LastNameTest   | NameSuffixTest   | EmailFieldTest   | PhoneFieldTest   |

    When fill import file with data:
      | id | Name Prefix   | First Name   | Middle Name   | Last Name   | Name Suffix   | Email Field   | Phone Field   |
      |    | NamePrefix10  | FirstName10  | MiddleName10  | LastName10  | NameSuffix10  | EmailField10  | PhoneField10  |
      |    | NamePrefix20  | FirstName20  | MiddleName20  | LastName20  | NameSuffix20  | EmailField20  | PhoneField20  |
      | 1  | NamePrefix100 | FirstName100 | MiddleName100 | LastName100 | NameSuffix100 | EmailField100 | PhoneField100 |
    And I import file
    Then Email should contains the following "Errors: 0 processed: 3, read: 3, added: 2, updated: 0, replaced: 1" text
    When I reload the page
    And I should see following grid containing rows:
      | Name Prefix      | First Name      | Middle Name      | Last Name      | Name Suffix      | Email Field      | Phone Field      |
      | NamePrefix100    | FirstName100    | MiddleName100    | LastName100    | NameSuffix100    | EmailField100    | PhoneField100    |
      | NamePrefix2      | FirstName2      | MiddleName2      | LastName2      | NameSuffix2      | EmailField2      | PhoneField2      |
      | NamePrefix3      | FirstName3      | MiddleName3      | LastName3      | NameSuffix3      | EmailField3      | PhoneField3      |
      | NamePrefix4      | FirstName4      | MiddleName4      | LastName4      | NameSuffix4      | EmailField4      | PhoneField4      |
      | NamePrefix10     | FirstName10     | MiddleName10     | LastName10     | NameSuffix10     | EmailField10     | PhoneField10     |
      | NamePrefix20     | FirstName20     | MiddleName20     | LastName20     | NameSuffix20     | EmailField20     | PhoneField20     |
      | NamePrefixUpdate | FirstNameUpdate | MiddleNameUpdate | LastNameUpdate | NameSuffixUpdate | EmailFieldUpdate | PhoneFieldUpdate |
#      | NamePrefixTest   | FirstNameTest   | MiddleNameTest   | LastNameTest   | NameSuffixTest   | EmailFieldTest   | PhoneFieldTest   |

  Scenario: Check exported and imported Entity One
    Given I go to Acme/Example/Entity Ones

    When I click "Export"
    Then I should see "Export started successfully. You will receive email notification upon completion." flash message
    And Email should contains the following "Export performed successfully. 5 entity ones were exported." text
    When take the link from email and download the file from this link
    Then the downloaded file from email contains at least the following data:
      | Integer Field | Float Field | Html Field             | Decimal Field | Smallint Field | Wysiwyg Field   | Bigint Field | String Field | Enum Field Id | Multienum Field 1 Id | Multienum Field 2 Id | Date Field | Datetime Field      | Boolean Field | Owner Username |
      | 10            | 10.1        | Html Field Text        | 20            | 1              | TWYSIWYG        | 2            | String       | 1             | furniture            |                      | 07/01/2018 | 07/01/2018 11:00:00 | 0             | admin          |
      | 10            | 10.1        | Html Field Text        | 20            | 1              | TWYSIWYG        | 2            | String       | 1             | furniture            |                      | 07/01/2018 | 07/01/2018 11:00:00 | 0             | admin          |
      | 10            | 10.1        | Html Field Text        | 20            | 1              | TWYSIWYG        | 2            | String       | 1             | furniture            |                      | 07/01/2018 | 07/01/2018 11:00:00 | 0             | admin          |
      | 10            | 10.1        | Html Field Text        | 20            | 1              | TWYSIWYG        | 2            | String       | 1             | furniture            |                      | 07/01/2018 | 07/01/2018 11:00:00 | 0             | admin          |
      | -10           | -10.1       | Html Field Text update | -200          | -2             | TWYSIWYG update | -2000        | String upd   | more_than_2   | refrigerator         | tv                   | 07/01/2022 | 07/01/2018 11:00:00 | 1             | admin          |

    When fill import file with data:
      | id | Integer Field | Float Field | Html Field             | Decimal Field | Smallint Field | Wysiwyg Field | Bigint Field | String Field | Enum Field Id | Date Field | Datetime Field      | Boolean Field | Owner Username |
      |    | 7             | 17.7        | <p>Html Field Text</p> | 200           | 1              | TWYSIWYG      | 2            | String       | 1             | 07/01/2028 | 07/01/2018 11:00:00 | 0             | admin          |
      | 1  | 100           | 20.1        | <p>Html Field New</p>  | 205           | 7              |               | 225          | String new   | 1             | 07/01/2018 | 07/01/2018 11:00:00 | 1             | admin          |
    And I import file
    Then Email should contains the following "Errors: 0 processed: 2, read: 2, added: 1, updated: 0, replaced: 1" text
    When I reload the page
    Then I should see following grid containing rows:
      | Integer Field | Float Field | Decimal Field | Smallint Field | Bigint Field | String Field | Enum Field  | Date Field  | DateTime Field        | Boolean Field | MTOIR         | MTOER         | Owner    |
      | 100           | 20.1        | 205           | 7              | 225          | String new   | 1           | Jul 1, 2018 | Jul 1, 2018, 11:00 AM | Yes           | NamePrefix100 | John Doe      | John Doe |
      | 10            | 10.1        | 20            | 1              | 2            | String       | 1           | Jul 1, 2018 | Jul 1, 2018, 11:00 AM | No            | NamePrefix2   | John Doe      | John Doe |
      | 10            | 10.1        | 20            | 1              | 2            | String       | 1           | Jul 1, 2018 | Jul 1, 2018, 11:00 AM | No            | NamePrefix3   | John Doe      | John Doe |
      | 10            | 10.1        | 20            | 1              | 2            | String       | 1           | Jul 1, 2018 | Jul 1, 2018, 11:00 AM | No            | NamePrefix4   | John Doe      | John Doe |
      | -10           | -10.1       | -200          | -2             | -2,000       | String upd   | More than 2 | Jul 1, 2022 | Jul 1, 2018, 11:00 AM | Yes           | NamePrefix100 | Charlie Sheen | John Doe |
      | 7             | 17.7        | 200           | 1              | 2            | String       | 1           | Jul 1, 2028 | Jul 1, 2018, 11:00 AM | No            |               |               | John Doe |

  Scenario: Delete Entity One
    When I go to Acme/Example/Entity Ones
    And I click "Delete" on row "NamePrefix1" in grid
    And I confirm deletion
    Then I should see "Entity One deleted" flash message

  Scenario: Delete Entity Two
    When I go to Acme/Example/Entity Twos
    And I click "Delete" on row "NamePrefix100" in grid
    And I confirm deletion
    Then I should see "Entity Two deleted" flash message
