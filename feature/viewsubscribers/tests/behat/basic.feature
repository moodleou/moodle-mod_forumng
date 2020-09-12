@mod @mod_forumng @ou @ou_vle @forumng_basic @forumng_feature_viewsubscribers
Feature: Testing for View Subscribers button

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
    And the following "activities" exist:
      | activity | name            | introduction         | course | idnumber |
      | forumng  | ForumNG testing | ForumNG introduction | C1     | forumng1 |

  @javascript
  Scenario: Basic scenario for view subscribers button
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "ForumNG testing"
    And I should see "Subscribe to forum"
    And I press "Subscribe to forum"
    Then I log out
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "ForumNG testing"
    And I press "Subscribe to forum"
    And I press "View subscribers"
    And I should see "Admin User"
    And I should see "Student 1"
