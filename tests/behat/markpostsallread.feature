@mod @mod_forumng @ou @ou_vle @forumng_markpostsallread @app
Feature: Add forumng activity and test mark post all read in the app.
  In order to discuss topics with other users
  As a student
  I need to see unread post and mark unread posts.

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
      | activity | name            | introduction           | course | idnumber |
      | forumng  | Test forum name | Test forum description | C1     | forumng1 |

  @javascript
  Scenario: Add discussions and posts to check mark all post to read
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    And I add a discussion with the following data:
      | Subject | Discussion 1 |
      | Message | abc          |
    And I follow "Test forum name"
    Then I should see "Discussion 1" in the ".forumng-subject" "css_element"
    And I log out
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    And I should see "Automatically mark as read"
    When I enter the app
    And I log in as "student1"
    Then I should see "Course 1"
    And I press "Course 1" near "Recently accessed courses" in the app
    And I press "Test forum name" in the app
    And I should not see "Mark all as read"
    # We can't use press page menu in the app because there are two core context menu #
    And I click on "//page-core-site-plugins-module-index//core-context-menu[not(contains(@class, 'mma-forumng-discussion-sort'))]//button" "xpath_element"
    When I press "Manually mark posts as read" in the app
    And ".mma-forumng-read-only" "css_element" should exist
    And I should see "Mark all as read"
    When I press "Mark all as read" in the app
    And ".mma-forumng-read-only" "css_element" should not exist in the ".mma-forumng-discussion-item" "css_element"
    And I am on site homepage
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    And I click on ".mobileshow" "css_element"
    And I should see "Manually mark as read"
