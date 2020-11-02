@mod @mod_forumng @ou @ou_vle @forumng_basic
Feature: Testing for forumng discussion listing
  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email            |
      | student1 | Student   | 1        | student1@asd.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |

  @javascript
  Scenario: Show more introduction in forumng listing page
    Given the following "activities" exist:
    | activity | name              | introduction                                  | course | idnumber |
    | forumng  | Test forum name 1 | Test forum introduction within 200 characters | C1     | forumng2 |
    | forumng  | Test forum name 2 | Test forum introduction without 200 characters. Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Sit amet est placerat in. Volutpat maecenas volutpat blandit aliquam etiam. Elementum integer enim neque volutpat ac tincidunt vitae semper. Blandit volutpat maecenas volutpat blandit aliquam. | C1     | forumng2 |
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test forum name 1"
    And I should not see "Show more"
    And I am on "Course 1" course homepage
    And I follow "Test forum name 2"
    And I should see "Show more"
    And I click on ".toggle_showmore" "css_element"
    And I should see "Show less"
    And I click on ".toggle_showless" "css_element"
    Then I should see "Show more"
