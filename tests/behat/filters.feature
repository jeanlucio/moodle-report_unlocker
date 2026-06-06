@report @report_unlocker @javascript
Feature: Client-side filters in the Unlocker report
  In order to quickly find specific restrictions in a large course
  As a teacher
  I need to filter the report by section, restriction type, and activity name

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

  Scenario: Type filter shows only conditions matching the selected type
    Given the following "activities" exist:
      | activity | name     | course | section | availability                                                            |
      | page     | Page One | C1     | 1       | {"op":"&","c":[{"type":"date","d":">=","t":1893456000}],"showc":[true]} |
    And I am on the "Course 1" course page logged in as "teacher1"
    And I navigate to "Reports > Unlocker (Mass Availability)" in current page administration
    When I set the field "Filter by type" to "Date"
    Then I should see "Page One"
    And I should see "Allow from"

  Scenario: No-results message appears when section filter matches no restricted activities
    Given the following "activities" exist:
      | activity | name     | course | section | availability                                                            |
      | page     | Page One | C1     | 1       | {"op":"&","c":[{"type":"date","d":">=","t":1893456000}],"showc":[true]} |
    And I am on the "Course 1" course page logged in as "teacher1"
    And I navigate to "Reports > Unlocker (Mass Availability)" in current page administration
    When I set the field "Filter by section" to "0"
    Then I should see "No access restrictions match the current filter."

  Scenario: Section filter shows only entries from the selected section
    Given the following "activities" exist:
      | activity | name       | course | section | availability                                                            |
      | page     | Page Alpha | C1     | 1       | {"op":"&","c":[{"type":"date","d":">=","t":1893456000}],"showc":[true]} |
      | page     | Page Beta  | C1     | 2       | {"op":"&","c":[{"type":"date","d":">=","t":1893456000}],"showc":[true]} |
    And I am on the "Course 1" course page logged in as "teacher1"
    And I navigate to "Reports > Unlocker (Mass Availability)" in current page administration
    When I set the field "Filter by section" to "1"
    Then I should see "Page Alpha"
    And ".report-unlocker-entry[data-name='page beta']" "css_element" should not be visible
    When I set the field "Filter by section" to "2"
    Then I should see "Page Beta"
    And ".report-unlocker-entry[data-name='page alpha']" "css_element" should not be visible

  Scenario: Search filter narrows entries by activity name substring
    Given the following "activities" exist:
      | activity | name       | course | section | availability                                                            |
      | page     | Page Alpha | C1     | 1       | {"op":"&","c":[{"type":"date","d":">=","t":1893456000}],"showc":[true]} |
      | page     | Page Beta  | C1     | 1       | {"op":"&","c":[{"type":"date","d":">=","t":1893456000}],"showc":[true]} |
    And I am on the "Course 1" course page logged in as "teacher1"
    And I navigate to "Reports > Unlocker (Mass Availability)" in current page administration
    When I set the field "Search" to "alpha"
    And I wait "1" seconds
    Then I should see "Page Alpha"
    And ".report-unlocker-entry[data-name='page beta']" "css_element" should not be visible

  Scenario: Search filter is case-insensitive
    Given the following "activities" exist:
      | activity | name       | course | section | availability                                                            |
      | page     | Page Alpha | C1     | 1       | {"op":"&","c":[{"type":"date","d":">=","t":1893456000}],"showc":[true]} |
      | page     | Page Beta  | C1     | 1       | {"op":"&","c":[{"type":"date","d":">=","t":1893456000}],"showc":[true]} |
    And I am on the "Course 1" course page logged in as "teacher1"
    And I navigate to "Reports > Unlocker (Mass Availability)" in current page administration
    When I set the field "Search" to "BETA"
    And I wait "1" seconds
    Then I should see "Page Beta"
    And ".report-unlocker-entry[data-name='page alpha']" "css_element" should not be visible

  Scenario: Clear filters button is hidden initially and becomes visible after a filter is applied
    Given the following "activities" exist:
      | activity | name     | course | section | availability                                                            |
      | page     | Page One | C1     | 1       | {"op":"&","c":[{"type":"date","d":">=","t":1893456000}],"showc":[true]} |
    And I am on the "Course 1" course page logged in as "teacher1"
    And I navigate to "Reports > Unlocker (Mass Availability)" in current page administration
    Then "#unlocker-clear-filters" "css_element" should not be visible
    When I set the field "Filter by section" to "1"
    Then "#unlocker-clear-filters" "css_element" should be visible

  Scenario: Clear filters button resets all active filters
    Given the following "activities" exist:
      | activity | name       | course | section | availability                                                            |
      | page     | Page Alpha | C1     | 1       | {"op":"&","c":[{"type":"date","d":">=","t":1893456000}],"showc":[true]} |
      | page     | Page Beta  | C1     | 2       | {"op":"&","c":[{"type":"date","d":">=","t":1893456000}],"showc":[true]} |
    And I am on the "Course 1" course page logged in as "teacher1"
    And I navigate to "Reports > Unlocker (Mass Availability)" in current page administration
    When I set the field "Filter by section" to "1"
    And ".report-unlocker-entry[data-name='page beta']" "css_element" should not be visible
    And I click on "Clear filters" "button"
    Then I should see "Page Alpha"
    And I should see "Page Beta"
    And "#unlocker-clear-filters" "css_element" should not be visible

  Scenario: Combining section and type filters narrows results to their intersection
    Given the following "activities" exist:
      | activity | name       | course | section | availability                                                            |
      | page     | Page Alpha | C1     | 1       | {"op":"&","c":[{"type":"date","d":">=","t":1893456000}],"showc":[true]} |
      | page     | Page Beta  | C1     | 2       | {"op":"&","c":[{"type":"date","d":">=","t":1893456000}],"showc":[true]} |
    And I am on the "Course 1" course page logged in as "teacher1"
    And I navigate to "Reports > Unlocker (Mass Availability)" in current page administration
    When I set the field "Filter by section" to "1"
    And I set the field "Filter by type" to "Date"
    Then I should see "Page Alpha"
    And ".report-unlocker-entry[data-name='page beta']" "css_element" should not be visible
