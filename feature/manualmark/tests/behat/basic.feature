@mod @mod_forumng @ou @ou_vle @forumngfeature_manualmark
Feature: Manually mark discussions read
  In order to mark discussions read
  As a student
  I need to be able to use both features that make discussions marked as read

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | student1 | Student | 1 | student1@asd.com |
      | student2 | Student | 2 | student2@asd.com |
      | teacher1 | Teacher | 1 | teacher1@asd.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | teacher |
      | student1 | C1 | student |
      | student2 | C1 | student |
    And the following "activities" exist:
      | activity | name                    | introduction                   | course | idnumber |
      | forumng  | Test forum name marking | Test forum marking description | C1     | forumng1 |
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I am on "Course 1" course homepage
    And I am on the "Test forum name marking" "forumng activity" page
    And I add a discussion with the following data:
      | Subject | Discussion 1 |
      | Message | Discussion 1 |
    And I am on the "Test forum name marking" "forumng activity" page
    And I add a discussion with the following data:
      | Subject | Discussion 2 |
      | Message | Discussion 2 |
    And I am on the "Test forum name marking" "forumng activity" page
    And I log out

  Scenario: Testing the 'Mark as read' options
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    Then I should see "(Unread posts)"
    And I follow "Test forum name marking"
    Then I should see "Test forum marking description"

    # Check existing discussions default un-read state
    And "Discussion 2" "table_row" should appear before "Discussion 1" "table_row"
    And ".forumng-discussion-unread .forumng-unreadcount .iconsmall" "css_element" should exist in the "Discussion 2" "table_row"
    And ".forumng-discussion-unread .forumng-unreadcount .iconsmall" "css_element" should exist in the "Discussion 1" "table_row"

    # Default state of discussion marking
    And I should see "Automatically mark as read"
    And "Change" "button" should exist

    # Change state of discussion marking
    Given I click on "Change" "button"
    Then I should see "Manually mark as read"

    # Change order of discussions
    And I follow "Discussion 2"
    Then "Mark discussion read" "button" should exist
    And "Mark discussions not read" "button" should not exist
    When I press "Mark discussion read"
    And I am on the "Test forum name marking" "forumng activity" page
    # Check discussions read state
    Then ".forumng-discussion-unread .forumng-unreadcount .iconsmall" "css_element" should not exist in the "Discussion 2" "table_row"
    And ".forumng-discussion-unread .forumng-unreadcount .iconsmall" "css_element" should exist in the "Discussion 1" "table_row"

  @javascript
  Scenario: Testing manual post marking javascript
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    Then I should see "(Unread posts)"
    And I follow "Test forum name marking"
    Then I should see "Test forum marking description"

    # Default state of discussion marking
    And I should see "Automatically mark as read"
    And "Change" "button" should exist

    # Change state of discussion marking
    Given I click on "Change" "link"
    Then I should see "Manually mark as read"

    Given I follow "Discussion 2"
    Then "Mark post read" "link" should exist
    When I click on "Mark post read" "link"
    Then "div.forumng-read" "css_element" should exist
    And ".forumng-markread a.disabled" "css_element" should exist
