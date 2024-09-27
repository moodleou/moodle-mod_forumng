@mod @mod_forumng @ou @ou_vle @forumngfeature_forward
Feature: Testing for forumng foward post
  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "activities" exist:
      | activity | name              | introduction            | course |
      | forumng  | Test forum name 1 | Test forum introduction | C1     |
    And the following "mod_forumng > discussions" exist:
      | forum             | subject     | user  |
      | Test forum name 1 | discussion1 | admin |
    And the following "mod_forumng > posts" exist:
      | discussion  | message | user  |
      | discussion1 | reply1  | admin |
      | discussion1 | reply2  | admin |
      | discussion1 | reply3  | admin |

  @javascript
  Scenario: Check fowarding interface
    Given I am on the "Test forum name 1" "forumng activity" page logged in as "admin"
    And I follow "discussion1"
    When I press "Forward by email"
    And I press "Selected posts"
    And I press "Select all"
    And I press "Confirm selection"
    Then I should see "reply1"
    And I should see "reply2"
    And I should see "reply3"
    When I press "Cancel"
    Then I should see "discussion1"
    Given I press "Forward by email"
    And I click on "Discussion" "button" in the ".forumng-confirmdialog" "css_element"
    Then I should see "This discussion will be emailed to"
    And I should not see "reply1"
