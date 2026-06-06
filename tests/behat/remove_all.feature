@report @report_unlocker @javascript
Feature: Remove all visible restrictions in the Unlocker report
  In order to bulk-remove access restrictions efficiently
  As a teacher
  I need to confirm removal of all visible conditions and see the result immediately

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |

  Scenario: Remove all visible removes the single condition and shows success message
    Given the following "activities" exist:
      | activity | name     | course | section | availability                                                            |
      | page     | Page One | C1     | 1       | {"op":"&","c":[{"type":"date","d":">=","t":1893456000}],"showc":[true]} |
    And I am on the "Course 1" course page logged in as "teacher1"
    And I navigate to "Reports > Unlocker (Mass Availability)" in current page administration
    When I click on "Remove all visible" "button"
    Then I should see "Mark 1 visible restriction(s) for removal and save all changes?" in the "Remove all visible" "dialogue"
    When I click on "Yes" "button" in the "Remove all visible" "dialogue"
    Then I should see "Access conditions updated successfully."
    And I should see "No access restrictions were found in this course."
    And I should not see "Page One"

  Scenario: Modal body shows the correct count of visible conditions
    Given the following "activities" exist:
      | activity | name       | course | section | availability                                                            |
      | page     | Page Alpha | C1     | 1       | {"op":"&","c":[{"type":"date","d":">=","t":1893456000}],"showc":[true]} |
      | page     | Page Beta  | C1     | 1       | {"op":"&","c":[{"type":"date","d":">=","t":1893456000}],"showc":[true]} |
    And I am on the "Course 1" course page logged in as "teacher1"
    And I navigate to "Reports > Unlocker (Mass Availability)" in current page administration
    When I click on "Remove all visible" "button"
    Then I should see "Mark 2 visible restriction(s) for removal and save all changes?" in the "Remove all visible" "dialogue"

  Scenario: Cancelling the modal leaves all conditions untouched
    Given the following "activities" exist:
      | activity | name     | course | section | availability                                                            |
      | page     | Page One | C1     | 1       | {"op":"&","c":[{"type":"date","d":">=","t":1893456000}],"showc":[true]} |
    And I am on the "Course 1" course page logged in as "teacher1"
    And I navigate to "Reports > Unlocker (Mass Availability)" in current page administration
    When I click on "Remove all visible" "button"
    And I click on "Cancel" "button" in the "Remove all visible" "dialogue"
    Then I should see "Page One"
    And I should see "Allow from"
    And I should not see "Access conditions updated successfully."

  Scenario: Remove all visible respects the active section filter
    Given the following "activities" exist:
      | activity | name       | course | section | availability                                                            |
      | page     | Page Alpha | C1     | 1       | {"op":"&","c":[{"type":"date","d":">=","t":1893456000}],"showc":[true]} |
      | page     | Page Beta  | C1     | 2       | {"op":"&","c":[{"type":"date","d":">=","t":1893456000}],"showc":[true]} |
    And I am on the "Course 1" course page logged in as "teacher1"
    And I navigate to "Reports > Unlocker (Mass Availability)" in current page administration
    When I set the field "Filter by section" to "Topic 1"
    And I click on "Remove all visible" "button"
    Then I should see "Mark 1 visible restriction(s) for removal and save all changes?" in the "Remove all visible" "dialogue"
    When I click on "Yes" "button" in the "Remove all visible" "dialogue"
    Then I should see "Access conditions updated successfully."
    And I should see "Page Beta"
    And I should not see "Page Alpha"

  Scenario: Remove all visible respects the active type filter
    Given the following "activities" exist:
      | activity | name       | course | section | availability                                                            |
      | page     | Page Alpha | C1     | 1       | {"op":"&","c":[{"type":"date","d":">=","t":1893456000}],"showc":[true]} |
      | page     | Page Beta  | C1     | 1       | {"op":"&","c":[{"type":"date","d":">=","t":1893456000}],"showc":[true]} |
    And I am on the "Course 1" course page logged in as "teacher1"
    And I navigate to "Reports > Unlocker (Mass Availability)" in current page administration
    When I set the field "Filter by type" to "Date"
    And I click on "Remove all visible" "button"
    Then I should see "Mark 2 visible restriction(s) for removal and save all changes?" in the "Remove all visible" "dialogue"
    When I click on "Yes" "button" in the "Remove all visible" "dialogue"
    Then I should see "Access conditions updated successfully."
    And I should see "No access restrictions were found in this course."
