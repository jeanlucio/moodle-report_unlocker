@report @report_unlocker @javascript
Feature: Access control for the Unlocker report
  In order to keep restriction data private and prevent unauthorised changes
  As a site administrator
  I need the report to be gated by the view and editconditions capabilities

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
      | nonedit1 | NonEdit   | One      | nonedit1@example.com |
      | student1 | Student   | One      | student1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | nonedit1 | C1     | teacher        |
      | student1 | C1     | student        |

  Scenario: Editing teacher sees the Unlocker link in course administration
    Given I am on the "Course 1" course page logged in as "teacher1"
    When I navigate to "Reports > Unlocker (Mass Availability)" in current page administration
    Then I should see "Unlocker (Mass Availability)"

  Scenario: Non-editing teacher sees the Unlocker link in course administration
    Given I am on the "Course 1" course page logged in as "nonedit1"
    When I navigate to "Reports > Unlocker (Mass Availability)" in current page administration
    Then I should see "Unlocker (Mass Availability)"

  Scenario: Student does not see the Unlocker link in course navigation
    Given I am on the "Course 1" course page logged in as "student1"
    Then "Unlocker (Mass Availability)" "link" should not exist

  Scenario: Non-editing teacher cannot save changes without the editconditions capability
    Given the following "activities" exist:
      | activity | name     | course | section | availability                                                            |
      | page     | Page One | C1     | 1       | {"op":"&","c":[{"type":"date","d":">=","t":1893456000}],"showc":[true]} |
    And I am on the "Course 1" course page logged in as "nonedit1"
    When I navigate to "Reports > Unlocker (Mass Availability)" in current page administration
    And I press "Save all changes"
    Then I should see "Sorry, but you do not currently have permissions to do this"
