@mod @mod_forumng @ou @ou_vle @forumngfeature @forumngfeature_move
Feature: Move discussions
  In order to move discussions
  As a user
  I use the move feature

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "groups" exist:
      | name | course | idnumber |
      | group1 | C1 | g1 |
      | group2 | C1 | g2 |
      | group3 | C1 | g3 |
      | group4 | C1 | g4 |
    And the following "groupings" exist:
      | name | course | idnumber |
      | G1   | C1     | GI1      |
      | G2   | C1     | GI2      |
      | G3   | C1     | GI3      |
    And the following "grouping groups" exist:
      | grouping | group |
      | GI1       | g1 |
      | GI1       | g2 |
      | GI2       | g3 |
      | GI2       | g4 |
      | GI3       | g1 |
      | GI3       | g3 |
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I add a "ForumNG" to section "1" and I fill the form with:
      | Forum name | No groups |
      | Forum introduction | No group forum |
    And I add a "ForumNG" to section "1" and I fill the form with:
      | Forum name | No groups 2 |
      | Forum introduction | Empty no group forum |
    And I add a "ForumNG" to section "1" and I fill the form with:
      | Forum name | G1 grouping |
      | Forum introduction | G1 grouping forum |
      | Group mode | Separate groups |
      | Grouping | G1 |
    And I add a "ForumNG" to section "1" and I fill the form with:
      | Forum name | G2 grouping |
      | Forum introduction | G2 grouping forum |
      | Group mode | Separate groups |
      | Grouping | G2 |
    And I add a "ForumNG" to section "1" and I fill the form with:
      | Forum name | G3 grouping |
      | Forum introduction | G3 grouping forum |
      | Group mode | Separate groups |
      | Grouping | G3 |
    And I follow "No groups"
    And I add a discussion with the following data:
      | Subject | Discussion 1 |
      | Message | abc |
    And I am on "Course 1" course homepage
    And I follow "G1 grouping"
    And I set the field "Separate groups" to "group1"
    And I press "Go"
    And I add a discussion with the following data:
      | Subject | Discussion 2 |
      | Message | abc |
    And I follow "G1 grouping"
    And I set the field "Separate groups" to "group2"
    And I press "Go"
    And I add a discussion with the following data:
      | Subject | Discussion 3 |
      | Message | abc |
    And I am on "Course 1" course homepage
    And I follow "G2 grouping"
    And I set the field "Separate groups" to "group3"
    And I press "Go"
    And I add a discussion with the following data:
      | Subject | Discussion 4 |
      | Message | abc |
    And I follow "G2 grouping"
    And I set the field "Separate groups" to "group4"
    And I press "Go"
    And I add a discussion with the following data:
      | Subject | Discussion 5 |
      | Message | abc |
    And I log out

  Scenario: Move discussion from in discussion option.
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "No groups"
    And I follow "Discussion 1"
    And I set the field "target" to "No groups 2"
    When I press "Move"
    Then I should see "Empty no group forum"
    And I should see "Discussion 1"
    Given I follow "Discussion 1"
    And I set the field "target" to "G3 grouping"
    When I press "Move"
    And I set the field "group" to "group1"
    And I press "Move"
    Then I should see "Discussion 1"
    And I should see "G3 grouping forum"
    Given I set the field "Separate groups" to "group1"
    When I press "Go"
    Then I should see "Discussion 1"
    Given I follow "Discussion 1"
    And I set the field "target" to "G1 grouping"
    When I press "Move"
    Then I should see "Discussion 1"
    And I should see "G1 grouping forum"
    Given I set the field "Separate groups" to "group1"
    When I press "Go"
    Then I should see "Discussion 1"
    Given I follow "Discussion 1"
    And I set the field "target" to "G2 grouping"
    When I press "Move"
    And I set the field "group" to "group3"
    And I press "Move"
    Then I should see "Discussion 1"
    And I should see "G2 grouping forum"
    Given I set the field "Separate groups" to "group3"
    When I press "Go"
    Then I should see "Discussion 1"

  Scenario: Move discussions from main forum page.
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "No groups"
    When I press "Move"
    And I press "All discussions shown"
    And I set the field "id_forum" to "No groups 2"
    And I press "Move discussions"
    Then I should see "Empty no group forum"
    And I should see "Discussion 1"
    Given I press "Move"
    And I press "All discussions shown"
    And I set the field "id_forum" to "G3 grouping"
    And I press "Move discussions"
    And I set the field "chosengroup" to "group1"
    When I press "Move"
    Then I should see "Discussion 1"
    And I should see "G3 grouping forum"
    Given I set the field "Separate groups" to "group1"
    When I press "Go"
    Then I should see "Discussion 1"
    Given I set the field "Separate groups" to "All participants"
    And I press "Go"
    When I press "Move"
    And I press "All discussions shown"
    And I set the field "id_forum" to "G1 grouping"
    And I press "Move discussions"
    Then I should see "Discussion 1"
    And I should see "G1 grouping forum"
    Given I set the field "Separate groups" to "group1"
    When I press "Go"
    Then I should see "Discussion 1"
    Given I press "Move"
    And I press "All discussions shown"
    And I set the field "id_forum" to "G2 grouping"
    And I press "Move discussions"
    And I set the field "chosengroup" to "group3"
    And I press "Move discussions"
    Then I should see "Discussion 1"
    And I should see "Discussion 2"
    And I should see "G2 grouping forum"
    Given I set the field "Separate groups" to "group3"
    When I press "Go"
    Then I should see "Discussion 4"
    And I set the field "Separate groups" to "All participants"
    And I press "Go"
    When I press "Move"
    And I press "All discussions shown"
    And I set the field "id_forum" to "G3 grouping"
    And I press "Move discussions"
    Then I should see "You appear to have moved discussions to a forum that has groups for which some"
    Given I press "Continue"
    And I should see "G3 grouping forum"
    And I should see "Discussion 5"
    When I set the field "Separate groups" to "group1"
    And I press "Go"
    Then I should see "Discussion 5"
    And I should not see "Discussion 1"
