@mod @mod_forumng @ou @ou_vle @forumngfeature_flagdiscussion
Feature: View flagged discussions
  In order to flag a discussion
  As a student
  I need to flag discussions

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
      | activity | name       | course | section |
      | forumng  | Test forum | C1     | 1       |
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I follow "Test forum"
    And I add a discussion with the following data:
      | Subject | D1 |
      | Message | abc |
    And I press "Star discussion"
    And I follow "Test forum"
    And I add a discussion with the following data:
      | Subject | D2 |
      | Message | 123 |
    And I press "Delete"
    And I press "Delete"
    And I log out
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test forum"
    And I add a discussion with the following data:
      | Subject | D3 |
      | Message | 456 |
    And I log out

  Scenario: View flagged discussions
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test forum"
    And I follow "D1"
    When I press "Star discussion"
    Then "Remove star" "button" should exist
    Given I follow "Test forum"
    When I follow "D3"
    Then "Star discussion" "button" should exist
    # Check flagged display.
    Given I follow "Test forum"
    Then I should see "Skip to starred discussions"
    And "form.forumng-flag" "css_element" should exist
    Given I click on "form.forumng-flag input[type=image]" "css_element"
    Then I should not see "Skip to starred discussions"
    And "form.forumng-flag" "css_element" should not exist
    And I log out
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test forum"
    Then I should see "Skip to starred discussions"
    Given I follow "D2"
    Then "Star discussion" "button" should not exist
