@report @report_unlocker @javascript
Feature: Display of access restrictions in the Unlocker report
  In order to manage course restrictions efficiently
  As a teacher
  I need the Unlocker report to display all access conditions correctly

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

  Scenario: Report shows a message when the course has no restrictions
    Given I am on the "Course 1" course page logged in as "teacher1"
    When I navigate to "Reports > Unlocker (Mass Availability)" in current page administration
    Then I should see "No access restrictions were found in this course."
    And I should not see "Activities and Resources"
    And "Save all changes" "button" should not exist
    And I should not see "Filter by section"

  Scenario: Report lists an activity that has a date allow-from condition
    Given the following "activities" exist:
      | activity | name     | course | section | availability                                                            |
      | page     | Page One | C1     | 1       | {"op":"&","c":[{"type":"date","d":">=","t":1893456000}],"showc":[true]} |
    And I am on the "Course 1" course page logged in as "teacher1"
    When I navigate to "Reports > Unlocker (Mass Availability)" in current page administration
    Then I should see "Activities and Resources"
    And I should see "Page One"
    And I should see "Allow from"
    And I should see "Remove this restriction"
    And "Save all changes" "button" should exist

  Scenario: Report shows Restrict until label for a before-date condition
    Given the following "activities" exist:
      | activity | name     | course | section | availability                                                           |
      | page     | Page One | C1     | 1       | {"op":"&","c":[{"type":"date","d":"<","t":1893456000}],"showc":[true]} |
    And I am on the "Course 1" course page logged in as "teacher1"
    When I navigate to "Reports > Unlocker (Mass Availability)" in current page administration
    Then I should see "Restrict until"
    And I should not see "Allow from"

  Scenario: Filter controls appear only when restrictions exist
    Given the following "activities" exist:
      | activity | name     | course | section | availability                                                            |
      | page     | Page One | C1     | 1       | {"op":"&","c":[{"type":"date","d":">=","t":1893456000}],"showc":[true]} |
    And I am on the "Course 1" course page logged in as "teacher1"
    When I navigate to "Reports > Unlocker (Mass Availability)" in current page administration
    Then I should see "Filter by section"
    And I should see "Filter by type"
    And I should see "Search"
    And I should see "Remove all visible"

  Scenario: Report lists multiple activities with conditions
    Given the following "activities" exist:
      | activity | name       | course | section | availability                                                            |
      | page     | Page Alpha | C1     | 1       | {"op":"&","c":[{"type":"date","d":">=","t":1893456000}],"showc":[true]} |
      | page     | Page Beta  | C1     | 2       | {"op":"&","c":[{"type":"date","d":"<","t":1893456000}],"showc":[true]}  |
    And I am on the "Course 1" course page logged in as "teacher1"
    When I navigate to "Reports > Unlocker (Mass Availability)" in current page administration
    Then I should see "Page Alpha"
    And I should see "Page Beta"
    And I should see "Allow from"
    And I should see "Restrict until"
