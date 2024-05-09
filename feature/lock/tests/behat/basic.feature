@mod @mod_forumng @ou @ou_vle @forumngfeature_lock
Feature: Lock multiple discussions
  In order to lock discussions
  As a teacher
  I need to lock multiple discussions using the discussion selector

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | student1 | Student | 1 | student1@asd.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | student1 | C1 | student |
    And the following "activities" exist:
      | activity | name       | course |
      | forumng  | Test forum | C1     |
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I am on the "Test forum" "forumng activity" page
    And I add a discussion with the following data:
      | Subject | D1 |
      | Message | abc |
    And I am on the "Test forum" "forumng activity" page
    And I add a discussion with the following data:
      | Subject | D2 |
      | Message | 123 |
    And I press "Delete"
    And I press "Delete"
    And I am on the "Test forum" "forumng activity" page
    And I add a discussion with the following data:
      | Subject | D3 |
      | Message | def |
    And I log out
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    And I am on the "Test forum" "forumng activity" page
    Then "Lock discussions" "button" should not exist
    Given I add a discussion with the following data:
      | Subject | D4 |
      | Message | 456 |
    And I log out

  @javascript
  Scenario: Lock discussions
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I am on the "Test forum" "forumng activity" page
    When I press "Lock discussions"
    And I click on "Cancel" "button" in the ".forumng-confirmdialog" "css_element"
    Then "Lock discussions" "button" should exist
    Given I press "Lock discussions"
    And I click on "Selected discussions" "button" in the ".forumng-confirmdialog" "css_element"
    And I click on "//table[contains(@class, 'generaltable')]//tbody//tr[position()=1]//td[position()=1]//input" "xpath_element"
    When I press "Confirm selection"
    Then "Lock discussion" "button" should exist
    Given I set the field "Message" to "now locked"
    When I press "Lock discussion"
    Then "Lock discussions" "button" should exist
    And ".forumng-locked" "css_element" should exist
    Given I press "Lock discussions"
    And I press "All discussions shown"
    And I set the field "Message" to "now locked"
    When I press "Lock discussion"
    Then ".forumng-locked.forumng-deleted" "css_element" should not exist
    And ".forumng-locked" "css_element" should exist
    And I should see "D1"
    And I should see "D2"
    And I should see "D3"
    And I should see "D4"
