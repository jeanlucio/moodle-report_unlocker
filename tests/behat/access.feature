@report @report_unlocker @javascript
Feature: Access control for the Unlocker report
  In order to keep restriction data private and prevent unauthorised changes
  As a site administrator
  I need the report to be gated by the view capability

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
    When I navigate to "Reports > Unlocker — Access Restrictions" in current page administration
    Then I should see "Unlocker — Access Restrictions"

  Scenario: Non-editing teacher sees the Unlocker link in course administration
    Given I am on the "Course 1" course page logged in as "nonedit1"
    When I navigate to "Reports > Unlocker — Access Restrictions" in current page administration
    Then I should see "Unlocker — Access Restrictions"

  Scenario: Student does not see the Unlocker link in course navigation
    Given I am on the "Course 1" course page logged in as "student1"
    Then "Unlocker — Access Restrictions" "link" should not exist
