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
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I add a "ForumNG" to section "1" and I fill the form with:
      | Forum name | Test forum |
    And I follow "Test forum"
    And I add a discussion with the following data:
      | Subject | D1 |
      | Message | abc |
    And I follow "Test forum"
    And I add a discussion with the following data:
      | Subject | D2 |
      | Message | 123 |
    And I press "Delete"
    And I press "Delete"
    And I follow "Test forum"
    And I add a discussion with the following data:
      | Subject | D3 |
      | Message | def |
    And I log out
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test forum"
    Then "Lock discussions" "button" should not exist
    Given I add a discussion with the following data:
      | Subject | D4 |
      | Message | 456 |
    And I log out

  Scenario: Lock discussions
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test forum"
    When I press "Lock discussions"
    And I press "Cancel"
    Then "Lock discussions" "button" should exist
    Given I press "Lock discussions"
    And I press "Selected discussions"
    And I set the field "Select discussion" to "1"
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
