@mod @mod_forumng @ou @ou_vle @forumngfeature @forumngfeature_move
Feature: Move discussions permissions
  In order to move discussions
  As a user
  I must have the right permissions on the target form

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email      |
      | teacher1 | teacher   | 1        | t1@asd.com |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | teacher1 | C1     | teacher |
    And the following "activities" exist:
      | activity | name    | introduction           | course | idnumber | visible |
      | forumng  | forum1  | Test forum description | C1     | forumng1 | 1       |
      | forumng  | forum2  | Test forum description | C1     | forumng2 | 1       |
      | forumng  | forum3  | Test forum description | C1     | forumng3 | 1       |
      | forumng  | forum4  | Test forum description | C1     | forumng4 | 0       |
    And the following "permission overrides" exist:
      | capability                         | permission | role    | contextlevel    | reference |
      | mod/forumng:movediscussions        | Prevent    | teacher | Activity module | forumng3  |
      | moodle/course:viewhiddenactivities | Prevent    | teacher | Activity module | forumng4  |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "forum1"
    And I add a discussion with the following data:
      | Subject | Discussion 1 |
      | Message | abc          |

  Scenario: Move discussion from in discussion page.
    Then the "menutarget" select box should not contain "forum1"
    And the "menutarget" select box should not contain "forum3"
    And the "menutarget" select box should not contain "forum4"
    And the "menutarget" select box should contain "forum2"

  Scenario: Move discussion from main page.
    Given I follow "forum1"
    When I press "Move"
    And I press "All discussions shown"
    Then the "forum" select box should not contain "forum1"
    And the "forum" select box should not contain "forum3"
    And the "forum" select box should not contain "forum4"
    # Note bug in behat stops checking select box with single value contains the value.
    Given I press "Move discussions"
    Then I should see "Discussion 1"
