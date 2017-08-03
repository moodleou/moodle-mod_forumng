@mod @mod_forumng @ou @ou_vle @forumngfeature_copy
Feature: Copy discussions from main forum page
  In order to copy discussions
  As a teacher
  I use the copy feature

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
      | Course 2 | C2        | 0        |
    And the following "groups" exist:
      | name    | course | idnumber |
      | Group 1 | C1     | G1       |
      | Group 2 | C1     | G2       |
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I add a "ForumNG" to section "1" and I fill the form with:
      | Forum name | Test group forum |
      | groupmode | Visible groups |
    And I follow "Test group forum"
    And I set the field "Visible groups" to "Group 1"
    And I press "Go"
    And I add a discussion with the following data:
      | Subject | To be copied 1 |
      | Message | message 1 |
    And I reply to post "1" with the following data:
      | Message | re message 1 |
    And I follow "Test group forum"
    And I set the field "Visible groups" to "Group 1"
    And I press "Go"
    And I add a discussion with the following data:
      | Subject | To be copied 2 |
      | Message | message 2 |
    And I reply to post "1" with the following data:
      | Message | re message 2 |
    And I am on "Course 2" course homepage
    And I add a "ForumNG" to section "1" and I fill the form with:
      | Forum name | Test course forum |
    And I log out

  Scenario: Copy discussions to another group on same forum
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test group forum"
    And I set the field "Visible groups" to "Group 1"
    When I press "Go"
    Then I should see "To be copied 1"
    And I should see "To be copied 2"
    And I press "Copy"
    And I press "All discussions shown"
    When I press "Begin copy"
    Then "Paste discussions" "button" should not exist
    And I should see "Test group forum"
    When I press "Cancel paste"
    Then "Cancel paste" "button" should not exist
    And I press "Copy"
    And I press "All discussions shown"
    When I press "Begin copy"
    And I set the field "Visible groups" to "Group 2"
    When I press "Go"
    Then I should not see "To be copied 1"
    And I should not see "To be copied 2"
    And "Cancel" "button" should exist
    When I press "Paste discussions"
    Then I should see "To be copied 1"
    And I should see "To be copied 2"
    When I follow "To be copied 1"
    Then I should see "message 1"
    And I should see "re message 1"

  Scenario: Copy discussions from group to another course forum
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test group forum"
    And I set the field "Visible groups" to "Group 1"
    When I press "Go"
    And I press "Copy"
    And I press "All discussions shown"
    And I press "Begin copy"
    And I am on "Course 2" course homepage
    And I follow "Test course forum"
    Then "Paste discussions" "button" should exist
    And I press "Paste discussions"
    Then I should see "To be copied 1"
    Then I should see "To be copied 2"
    And I follow "To be copied 1"
    Then I should see "message 1"
    Then I should see "re message 1"
