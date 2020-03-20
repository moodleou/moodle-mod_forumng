@mod @mod_forumng @ou @ou_vle @forumng_create_discussion @app @javascript
Feature:  Add discussion in forumng and test app can view discussion listing page
  In order to see discussion listing page
  As a student
  I can see discussion listing page

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email            |
      | student1 | Student   | 1        | student1@asd.com |
      | student2 | Student   | 2        | student2@asd.com |
      | teacher1 | Teacher   | 1        | teacher1@asd.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
      | teacher1 | C1     | teacher |
    And the following "activities" exist:
      | activity | name                  | introduction           | course | idnumber |
      | forumng  | Test forum name       | Test forum description | C1     | forumng1 |
      | forumng  | Test forum discussion | Test forum description | C1     | forumng2 |

  Scenario: Add discussions and check options in the app
    And I enter the app
    And I log in as "teacher1"
    And I press "Course 1" near "Recently accessed courses" in the app
    And I press "Test forum discussion" in the app
    When I click on "#mod_forumg_add_discussion" "css_element"
    And I should see "Subject"
    And I should see "Message"
    And I should see "Attachments"
    And I should see "(Show as sticky)"
    And I should see "Only show from"
    And I should see "Post as:"
    And I should see "Date"
    And I should see "Standard Post"
    And I set the field "Subject" to "Test discussion 1" in the app
    And I set the field "Message" to "Test message" in the app
    And I click on "#mma-forumng-show-sticky" "css_element"
    And I click on "ion-datetime" "css_element"
    And I press "Done"
    And I should not see "Date"
    When I click on "#mma-forumng-add-discussion-button" "css_element"
    And I should see "Test discussion 1"
    And I am on homepage
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test forum discussion"
    And I should see "Test discussion 1"
    And "//img[@alt='This discussion always appears at top of list']" "xpath_element" should exist
    And I should see "Test discussion 1"
    And I should see "Teacher 1"
    And I follow "Test discussion 1"
    And I should see "Test message"
    And I log out
    And I enter the app
    And I log in as "student1"
    And I press "Course 1" near "Recently accessed courses" in the app
    And I press "Test forum name" in the app
    When I click on "#mod_forumg_add_discussion" "css_element"
    And I should not see "(Show as sticky)"
    And I should not see "Only show from"
    And I should not see "Post as:"
    And I should not see "Date"
    And I should not see "Standard Post"
    And I set the field "Subject" to "Test discussion 2 student" in the app
    And I set the field "Message" to "Test message student" in the app
    When I click on "#mma-forumng-add-discussion-button" "css_element"
    And I should see "Test discussion 2 student"
    And I am on homepage
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    And I should see "Test discussion 2 student"
    And "//img[@alt='This discussion always appears at top of list']" "xpath_element" should not exist
    And I follow "Test discussion 2 student"
    And I should see "Test message student"
