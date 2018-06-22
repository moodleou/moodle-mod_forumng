@mod @mod_forumng @ou @ou_vle @forumngfeature
Feature: Delete discussions
  In order to delete discussions
  As a user
  I need to have access to the delete feature

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | student1 | Student | 1 | student1@asd.com |
      | student2 | Student | 2 | student2@asd.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user | course | role |
      | student1 | C1 | student |
      | student2 | C1 | student |
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I add a "ForumNG" to section "1" and I fill the form with:
      | Forum name | Test forum name |
      | Forum introduction | Test forum description |
    And I log out

  Scenario: Delete discussion from forum
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    When I add a discussion with the following data:
      | Subject | Discussion 1 |
      | Message | abc |
    Then "Delete" "button" should exist
    Given I reply to post "1" with the following data:
      | Message | def |
    Then "Delete" "button" should not exist in the "#forumng-features" "css_element"
    Given I follow "Test forum name"
    When I add a discussion with the following data:
      | Subject | Discussion 1a |
      | Message | abcdef |
    Given I press "Delete"
    When I press "Cancel"
    Then I should see "abcdef"
    Given I press "Delete"
    When I press "Delete"
    Then I should not see "Discussion 1a"
    Given I log out
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    And I follow "Discussion 1a"
    When I press "Undelete"
    And I press "Cancel"
    Then I should see "abcdef"
    Given I press "Undelete"
    And I press "Undelete"
    Then "Delete" "button" should exist
    Given I press "Delete"
    When I press "Delete and email"
    Then I should see "Delete and email author"
    And I should not see "Notify other contributors"
    And I press "Cancel"
    And I should see "abcdef"
    Given I follow "Test forum name"
    And I follow "Discussion 1"
    And I reply to post "1" with the following data:
      | Message | ghi |
    When I press "Delete"
    And I press "Delete and email"
    Then I should see "Notify other contributors"

  @javascript
  Scenario: Javascript tests
    Given I log in as "admin"
    Given I navigate to "Site policies" node in "Site administration > Security"
    And I set the field "Maximum time to edit posts" to "1 minutes"
    And I press "Save changes"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    When I add a discussion with the following data:
      | Subject | Discussion 1 |
      | Message | abc |
    And I log out
    Given I log in as "student2"
    And I am on "Course 1" course homepage
    Given I follow "Test forum name"
    When I add a discussion with the following data:
      | Subject | Discussion 2 |
      | Message | abc |
    Then "Delete" "button" should exist
    Given I follow "Test forum name"
    When I follow "Discussion 1"
    And I reply to post "1" with the following data:
      | Message | reply |
    Then "Delete" "button" should not exist
    Given I log out
    And I log in as "admin"
    And I am on "Course 1" course homepage
    Given I follow "Test forum name"
    And I follow "Discussion 2"
    Given I change window size to "large"
    When I press "Delete"
    And I click on "Delete" "button" in the ".forumng-confirmdialog .forumng-buttons" "css_element"
    Then I should see "Delete and email author"
    And I should not see "Notify other contributors"
    Given I press "Cancel"
    When I press "Delete"
    And I click on "Cancel" "button" in the ".forumng-confirmdialog .forumng-buttons" "css_element"
    Then ".forumng-confirmdialog" "css_element" should not exist
    Given I follow "Test forum name"
    And I follow "Discussion 1"
    When I click on "Delete" "button" in the "#forumng-features" "css_element"
    Then "Delete" "button" should exist in the ".forumng-confirmdialog .forumng-buttons" "css_element"
    And "Delete and email" "button" should exist in the ".forumng-confirmdialog .forumng-buttons" "css_element"
    And I should see "Delete discussion" in the ".forumng-confirmdialog" "css_element"
    Given I click on "Delete and email" "button" in the ".forumng-confirmdialog .forumng-buttons" "css_element"
    Then I should see "Delete and email author"
    And I should see "Notify other contributors"
    And I press "Cancel"
    Given I change window size to "medium"
    Given I log out
    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    # Test editing timeout: arbitrary 30 secs as set to 1 min but other steps undertaken take time.
    And I wait "30" seconds
    And I follow "Discussion 2"
    Then "Delete" "button" should not exist
