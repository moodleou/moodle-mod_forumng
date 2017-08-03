@mod @mod_forumng @ou @ou_vle @forumngtype_clone
Feature: Clone forum
  In order to use the same forum in multiple places
  As a admin
  I need to clone the forum

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
      | Course 2 | C2        | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | student1 | Student | 1 | student1@asd.com |
      | student2 | Student | 2 | student1@asd.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | student1 | C1 | student |
      | student2 | C2 | student |
    Given the following config values are set as admin:
      | forumng_enableadvanced | 1 |
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I add a "ForumNG" to section "1" and I fill the form with:
      | Forum name | Test forum |
      | cmidnumber | TF1 |
      | id_shared | 1 |
    And I follow "Test forum"
    And I add a discussion with the following data:
      | Subject | D1 |
      | Message | abc |
    And I am on "Course 2" course homepage
    And I add a "ForumNG" to section "1" and I fill the form with:
      | Forum name | Test clone forum |
      | id_usesharedgroup_useshared | 1 |
      | id_usesharedgroup_originalcmidnumber | TF1 |
    And I log out

  Scenario: Create and view shared forum
    Given I log in as "student2"
    And I am on "Course 2" course homepage
    And I follow "Test forum"
    Then I should see "D1"
    Given I add a discussion with the following data:
      | Subject | D2 |
      | Message | abc |
    And I log out
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test forum"
    Then I should see "D1"
    And I should see "D2"
    And I should see "TF1" in the ".forumng-shareinfo" "css_element"
    And I should see "C2" in the ".forumng-shareinfo a" "css_element"
    Given I am on "Course 2" course homepage
    And I follow "Test forum"
    Then I should see "This is a shared forum"
    And I should see "C1" in the ".forumng-shareinfo" "css_element"
    Given I follow "Edit settings"
    Then I should not see "Forum introduction"
    And I should see "This is a shared forum"
    When I press "Save and display"
    And I follow "original forum"
    Then I should see "C1"
    And I log out
