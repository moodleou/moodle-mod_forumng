@mod @mod_forumng @ou @ou_vle @forumngfeature_markallread
Feature: Mark all discussions as read
  In order to mark all discussions as read
  As a student
  I need to use the forumng feature that marks all discussions as read

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
    And I follow "Test forum name marking"
    And I add a discussion with the following data:
      | Subject | Discussion 1 |
      | Message | Discussion 1 |
    And I follow "Test forum name marking"
    And I add a discussion with the following data:
      | Subject | Discussion 2 |
      | Message | Discussion 2 |
    And I follow "Test forum name marking"
    And I log out

  # JS required for 'Discussion 2' links
  @javascript
  Scenario: Testing the 'Mark as read' option
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    Then I should see "(Unread posts)"
    Given I follow "Test forum name marking"
    Then I should see "Test forum marking description"

    # Check discussions default un-read state marked with unread classes
    And "Discussion 2" "table_row" should appear before "Discussion 1" "table_row"
    And ".forumng-discussion-unread .forumng-unreadcount .iconsmall" "css_element" should exist in the "Discussion 2" "table_row"
    And ".forumng-discussion-unread .forumng-unreadcount .iconsmall" "css_element" should exist in the "Discussion 1" "table_row"

    # The facility we're testing should be available
    And "Mark all posts read" "button" should exist
    # Default state of discussion marking
    And I should see "Automatically mark as read"
    And "Change" "link" should exist

    # Toggle state of discussion marking
    Given I click on "Change" "link"
    Then I should see "Manually mark as read"

    # Check read state of both discussions
    # The discussions should have an 'unread background' class
    And I follow "Discussion 2"
    And ".forumng-unread.forumng-p1" "css_element" should exist
    And "Mark post read" "link" should exist
    And "Mark discussion read" "button" should exist
    Given I follow "Test forum name marking"
    And I follow "Discussion 1"
    And ".forumng-unread.forumng-p1" "css_element" should exist
    And "Mark post read" "link" should exist
    And "Mark discussion read" "button" should exist
    Given I follow "Test forum name marking"

    # Change read state for all discussions
    When I press "Mark all posts read"
    # Confirm discussions read state changes in the table list
    Then ".forumng-discussion-unread .forumng-unreadcount .iconsmall" "css_element" should not exist in the "Discussion 2" "table_row"
    And ".forumng-discussion-unread .forumng-unreadcount .iconsmall" "css_element" should not exist in the "Discussion 1" "table_row"

    # Confirm discussions read marking state
    # The discussions should not have an unread background class or marking button
    And I follow "Discussion 2"
    And ".forumng-unread.forumng-p1" "css_element" should not exist
    And "Mark post read" "link" should not exist
    And "Mark discussion read" "button" should not exist
    Given I follow "Test forum name marking"
    And I follow "Discussion 1"
    And ".forumng-unread.forumng-p1" "css_element" should not exist
    And "Mark post read" "link" should not exist
    And "Mark discussion read" "button" should not exist
    And I log out
