@mod @mod_forumng @ou @ou_vle @forumngfeature_deletedposts
Feature: View deleted discussions and posts
  In order to view deleted discussions and posts
  As a teacher
  I need to access the deletedposts feature pages

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | student1 | Student | 1 | student1@asd.com |
      | student2 | Student | 2 | student2@asd.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | student1 | C1 | student |
      | student2 | C1 | student |
    And the following "groups" exist:
      | name    | course | idnumber |
      | Group 1 | C1     | G1       |
      | Group 2 | C1     | G2       |
    And the following "group members" exist:
      | user | group |
      | student1 | G1 |
      | student2 | G2 |
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I add a "ForumNG" to section "1" and I fill the form with:
      | Forum name | Test group forum |
      | groupmode | Separate groups |
    And I log out

  @javascript
  Scenario: View deleted discussions
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test group forum"
    When I add a discussion with the following data:
      | Subject | G1 deleted |
      | Message | abc |
    And I follow "Test group forum"
    And I add a discussion with the following data:
      | Subject | G1 not deleted |
      | Message | 123 |
    And I follow "Test group forum"
    Then "View deleted" "button" should not exist
    And I log out
    Given I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "Test group forum"
    When I add a discussion with the following data:
      | Subject | G2 deleted |
      | Message | def |
    And I follow "Test group forum"
    And I add a discussion with the following data:
      | Subject | G2 not deleted |
      | Message | 456 |
    And I follow "Test group forum"
    Then "View deleted" "button" should not exist
    And I log out
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test group forum"
    When I press "View deleted"
    Then I should see "There are no deleted discussions for this forum"
    # Delete the 2 'deleted' discussions and check they show.
    Given I follow "Test group forum"
    And I follow "G1 deleted"
    Given I change window size to "large"
    And I press "Delete"
    And I click on "Delete" "button" in the ".forumng-confirmdialog .forumng-buttons" "css_element"
    And I press "Send and delete"
    Then ".forumng-deleted" "css_element" should exist
    Given I follow "G2 deleted"
    And I press "Delete"
    And I click on "Delete" "button" in the ".forumng-confirmdialog .forumng-buttons" "css_element"
    And I press "Send and delete"
    Given I change window size to "medium"
    Then "//tbody/tr[contains(@class, 'forumng-deleted')][2]" "xpath_element" should exist
    Given I press "View deleted"
    Then I should see "G1 deleted"
    And I should see "G2 deleted"
    Given I set the field "Separate groups" to "Group 1"
    Then I should see "G1 deleted"
    And I should not see "G2 deleted"
    Given I follow "Deleted posts"
    Then I should see "No deleted posts found that were created by Anyone and that were deleted by Anyone"

  Scenario: View deleted posts
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test group forum"
    When I add a discussion with the following data:
      | Subject | G1 discussion |
      | Message | abc |
    And I reply to post "1" with the following data:
      | Message | REPLY1 |
    And I expand post "2"
    When I follow "Delete"
    And I press "Delete"
    Then I should see "This post was deleted by"
    And I log out
    Given I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "Test group forum"
    When I add a discussion with the following data:
      | Subject | G2 discussion |
      | Message | 123 |
    And I reply to post "1" with the following data:
      | Message | REPLY2 |
    And I expand post "2"
    When I follow "Delete"
    And I press "Delete"
    Then I should see "This post was deleted by"
    And I log out
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test group forum"
    When I press "View deleted"
    Then I should see "There are no deleted discussions for this forum"
    Given I follow "Deleted posts"
    Then I should see "G1 discussion"
    And I should see "G2 discussion"
    And I should see "Student 1"
    And I should see "Student 2"
    And I should see "REPLY1"
    And I should see "REPLY2"
    And the "View deleted posts deleted by" select box should contain "Student 1"
    And the "View deleted posts deleted by" select box should contain "Student 2"
    And the "View deleted posts created by" select box should contain "Student 1"
    And the "View deleted posts created by" select box should contain "Student 2"
    And the "View deleted posts created by" select box should not contain "Admin User"
  # Check group and user views.
    Given I set the field "View deleted posts deleted by" to "Student 1"
    When I click on "/descendant::input[@value='Go'][2]" "xpath_element"
    Then I should see "REPLY1"
    And I should not see "REPLY2"
    Given I set the field "View deleted posts deleted by" to "Anyone"
    When I click on "/descendant::input[@value='Go'][2]" "xpath_element"
    Then I should see "REPLY1"
    And I should see "REPLY2"
    Given I set the field "View deleted posts created by" to "Student 2"
    When I click on "/descendant::input[@value='Go'][3]" "xpath_element"
    Then I should see "REPLY2"
    And I should not see "REPLY1"
    Given I set the field "Separate groups" to "Group 2"
    When I press "Go"
    Then I should see "G2 discussion"
    And I should see "REPLY2"
    And I should not see "REPLY1"
