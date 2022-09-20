@mod @mod_forumng @ou @ou_vle @forumngfeature_copy
Feature: Copy discussions to another forum
  In order to make a copy of a discussion
  As a teacher
  I need to copy and paste discussions between forums

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
      | Course 2 | C2        | 0        |
    And the following "groups" exist:
      | name    | course | idnumber |
      | Group 1 | C1     | G1       |
      | Group 2 | C1     | G2       |
    And the following "activities" exist:
      | activity | name              | course | section | groupmode |
      | forumng  | Test group forum  | C1     | 1       | 2         |
      | forumng  | Test course forum | C2     | 1       |           |

  Scenario: Copy discussion to another group on same forum
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I am on the "Test group forum" "forumng activity" page
    And I set the field "Visible groups" to "Group 1"
    And I press "Go"
    And I add a discussion with the following data:
      | Subject | To be copied |
      | Message | abc |
    And I reply to post "1" with the following data:
      | Message | def |
    When I press "Copy"
    Then I should see "Copy discussion"
    Given I press "Cancel"
    Then I should see "abc"
    Given I press "Copy"
    When I press "Begin copy"
    Then "Paste discussion" "button" should not exist
    And "Cancel paste" "button" should exist
    And I should see "To be copied"
    Given I press "Cancel paste"
    Then "Cancel paste" "button" should not exist
    Given I follow "To be copied"
    When I press "Copy"
    And I press "Begin copy"
    And I set the field "Visible groups" to "Group 2"
    And I press "Go"
    Then I should not see "To be copied"
    And "Cancel" "button" should exist
    Given I press "Paste discussion"
    Then I should see "To be copied"
    Given I follow "To be copied"
    Then I should see "abc"
    And I should see "def"
    And I edit post "1" with the following data:
      | Subject | To be copied again |
    Given I press "Copy"
    And I press "Begin copy"
    And I am on the "Test group forum" "forumng activity" page
    And I set the field "Visible groups" to "Group 1"
    And I press "Go"
    When I press "Paste discussion"
    Then I should see "To be copied again"

  Scenario: Copy discussion from group to another course forum
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test group forum"
    And I set the field "Visible groups" to "Group 1"
    And I press "Go"
    And I add a discussion with the following data:
      | Subject | To be copied |
      | Message | abc |
    And I reply to post "1" with the following data:
      | Message | def |
    When I press "Copy"
    And I press "Begin copy"
    And I am on "Course 2" course homepage
    And I follow "Test course forum"
    Then "Paste discussion" "button" should exist
    Given I press "Paste discussion"
    Then I should see "To be copied"
    Given I follow "To be copied"
    Then I should see "abc"
    And I should see "def"
    And I should see "Test course forum"

  @javascript
  Scenario: Copy discussion to clone course forum
    Given the following config values are set as admin:
      | forumng_enableadvanced | 1 |
    And the following "activities" exist:
      | activity | name              | course | section | groupmode | idnumber | shared |
      | forumng  | Test shared forum | C1     | 1       |           | SHARED   | 1      |
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I add a "ForumNG" to section "2" and I fill the form with:
      | name | Will change |
      | usesharedgroup[useshared] | 1 |
      | usesharedgroup[originalcmidnumber] | SHARED |
    And I am on "Course 1" course homepage
    And I am on the "Test group forum" "forumng activity" page
    And I set the field "Visible groups" to "Group 1"
    And I add a discussion with the following data:
      | Subject | To be copied |
      | Message | abc |
    And I reply to post "1" with the following data:
      | Message | def |
    When I press "Copy"
    Then I should see "Copy discussion"
    When I press "Begin copy"
    And I am on "Course 1" course homepage
    And I click on "Test shared forum" "link" in the "Topic 2" "section"
    And I press "Paste discussion"
    Then I should see "To be copied"
    And I press "Open course index"
    Then I should see "Topic 2"
