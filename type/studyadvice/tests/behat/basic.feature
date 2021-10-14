@mod @mod_forumng @ou @ou_vle @forumngtype_studyadvice
Feature: Study advice discussions
  In order to use forum for study advice
  As a student
  I need to post discussions that others cannot see

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | student1 | Student | 1 | student1@asd.com |
      | student2 | Student | 2 | student1@asd.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | student1 | C1 | student |
      | student2 | C1 | student |
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I add a "ForumNG" to section "1" and I fill the form with:
      | Forum name | Test forum |
      | Forum type | studyadvice |
    And I follow "Test forum"
    And I add a discussion with the following data:
      | Subject | D1 |
      | Message | abc |
    And I log out

  Scenario: Test study advice forum
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test forum"
    Then I should not see "D1"
    Given I add a discussion with the following data:
      | Subject | D2 |
      | Message | abc |
    And I log out
    Given I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "Test forum"
    Then I should not see "D2"
    Given I add a discussion with the following data:
      | Subject | D3 |
      | Message | abc |
    And I log out
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test forum"
    Then I should see "D1"
    And I should see "D2"
    And I should see "D3"
    And I log out
