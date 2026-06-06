@report @report_unlocker @javascript
Feature: Editing and removing access conditions via the Unlocker report
  In order to manage access restrictions without opening each activity individually
  As a teacher
  I need to remove conditions and save changes directly from the report

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

  Scenario: Saving without changes shows the success message and preserves all conditions
    Given the following "activities" exist:
      | activity | name     | course | section | availability                                                            |
      | page     | Page One | C1     | 1       | {"op":"&","c":[{"type":"date","d":">=","t":1893456000}],"showc":[true]} |
    And I am on the "Course 1" course page logged in as "teacher1"
    And I navigate to "Reports > Unlocker (Mass Availability)" in current page administration
    When I press "Save all changes"
    Then I should see "Access conditions updated successfully."
    And I should see "Page One"
    And I should see "Allow from"

  Scenario: Checking the remove checkbox and saving removes the condition
    Given the following "activities" exist:
      | activity | name     | course | section | availability                                                            |
      | page     | Page One | C1     | 1       | {"op":"&","c":[{"type":"date","d":">=","t":1893456000}],"showc":[true]} |
    And I am on the "Course 1" course page logged in as "teacher1"
    And I navigate to "Reports > Unlocker (Mass Availability)" in current page administration
    When I set the field "Remove this restriction" to "1"
    And I press "Save all changes"
    Then I should see "Access conditions updated successfully."
    And I should see "No access restrictions were found in this course."
    And I should not see "Page One"

  Scenario: Removing one activity's condition leaves the other activity untouched
    Given the following "activities" exist:
      | activity | name       | course | section | availability                                                            |
      | page     | Page Alpha | C1     | 1       | {"op":"&","c":[{"type":"date","d":">=","t":1893456000}],"showc":[true]} |
      | page     | Page Beta  | C1     | 1       | {"op":"&","c":[{"type":"date","d":"<","t":1956528000}],"showc":[true]}  |
    And I am on the "Course 1" course page logged in as "teacher1"
    And I navigate to "Reports > Unlocker (Mass Availability)" in current page administration
    When I click on "Remove this restriction" "field" in the "[data-name='page alpha']" "css_element"
    And I press "Save all changes"
    Then I should see "Access conditions updated successfully."
    And I should see "Page Beta"
    And I should see "Restrict until"
    And I should not see "Page Alpha"
    And I should not see "Allow from"

  Scenario: Removing one of two conditions on the same activity leaves the other intact
    Given the following "activities" exist:
      | activity | name     | course | section | availability                                                                                                                                                                      |
      | page     | Page One | C1     | 1       | {"op":"&","c":[{"type":"date","d":">=","t":1893456000},{"type":"date","d":"<","t":1956528000}],"showc":[true,true]} |
    And I am on the "Course 1" course page logged in as "teacher1"
    And I navigate to "Reports > Unlocker (Mass Availability)" in current page administration
    Then I should see "Allow from"
    And I should see "Restrict until"
    When I click on "Remove this restriction" "field" in the ".report-unlocker-condition[data-condtype='date']:first-child" "css_element"
    And I press "Save all changes"
    Then I should see "Access conditions updated successfully."
    And I should see "Page One"
    And I should see "Restrict until"

  Scenario: Submitting the form without the editconditions capability is denied
    Given the following "activities" exist:
      | activity | name     | course | section | availability                                                            |
      | page     | Page One | C1     | 1       | {"op":"&","c":[{"type":"date","d":">=","t":1893456000}],"showc":[true]} |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | nonedit1 | NonEdit   | One      | nonedit1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | nonedit1 | C1     | teacher |
    And I am on the "Course 1" course page logged in as "nonedit1"
    And I navigate to "Reports > Unlocker (Mass Availability)" in current page administration
    When I press "Save all changes"
    Then I should see "Sorry, but you do not currently have permissions to do this"

  Scenario: Section conditions are shown under the Sections heading
    Given the following "course sections" exist:
      | course | section | name         | availability                                                            |
      | C1     | 1       | Locked Topic | {"op":"&","c":[{"type":"date","d":">=","t":1893456000}],"showc":[true]} |
    And I am on the "Course 1" course page logged in as "teacher1"
    And I navigate to "Reports > Unlocker (Mass Availability)" in current page administration
    Then I should see "Sections"
    And I should see "Locked Topic"
    And I should see "Allow from"

  Scenario: Removing a section condition saves successfully
    Given the following "course sections" exist:
      | course | section | name         | availability                                                            |
      | C1     | 1       | Locked Topic | {"op":"&","c":[{"type":"date","d":">=","t":1893456000}],"showc":[true]} |
    And I am on the "Course 1" course page logged in as "teacher1"
    And I navigate to "Reports > Unlocker (Mass Availability)" in current page administration
    When I set the field "Remove this restriction" to "1"
    And I press "Save all changes"
    Then I should see "Access conditions updated successfully."
    And I should see "No access restrictions were found in this course."
