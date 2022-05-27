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
      | activity | name                  | introduction               | course | idnumber | canpostanon |
      | forumng  | Test forum name       | Test forum description     | C1     | forumng1 | 0           |
      | forumng  | Test forum discussion | Test forum description     | C1     | forumng2 | 0           |
      | forumng  | Test forum anon       | Test ForumNG 3 description | C1     | forumng3 | 1           |
    And the following config values are set as admin:
      | disabledfeatures | CoreBlockDelegate_AddonBlockRecentlyAccessedCourses,CoreBlockDelegate_AddonBlockRecentlyAccessedItems | tool_mobile |

  Scenario: Add discussions and check options in the app
    And I enter the app
    And I log in as "teacher1"
    And I press "Course 1" in the app
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
    And I press "Course 1" in the app
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
    And I enter the app
    And I log in as "student1"
    And I press "Course 1" in the app
    And I press "Test forum name" in the app
    And I press "Test discussion 2 student" in the app
    And I should see "Reply"
    And I should see "Edit"
    And I press "Reply" in the app
    And I should see "Subject"
    And I should see "Message"
    And I should see "Attachments"
    And I set the field "Change subject (optional)" to "Test reply" in the app
    And I set the field "Message" to "Test reply message" in the app
    And I press "Post reply" in the app
    And I press "EXPAND ALL" in the app
    And I should see "Test reply"
    And I should see "Test reply message"
    And I press "Edit" near "Test reply message" in the app
    And I should see "Test discussion 2 student"
    And I should see "Test message student"
    And I set the field "Change subject (optional)" to "Test rootpost edited subject" in the app
    And I set the field "Message" to "Test rootpost edited message" in the app
    And I press "Edit post" in the app
    And I press "EXPAND ALL" in the app
    And I should see "Test rootpost edited subject"
    And I should see "Test rootpost edited message"
    And I enter the app
    And I log in as "teacher1"
    And I press "Course 1" in the app
    And I press "Test forum name" in the app
    And I should see "Test discussion 2 student"
    And I press "Test discussion 2 student" in the app
    And I should see "Test rootpost edited subject"
    And I should see "Test rootpost edited message"
    And I should see "Delete"
    And I press "Delete" in the app
    And I should see "Are you sure you want to delete this post?"
    And I click on "//button[@ion-button='alert-button' and contains(span, 'Delete')]" "xpath_element"
    And I press "EXPAND ALL" in the app
    And I should see "Deleted post. This post was deleted by"
    And I press "Undelete" in the app
    And I should see "Are you sure you want to undelete this post?"
    And I click on "//button[@ion-button='alert-button' and contains(span, 'Undelete post')]" "xpath_element"
    And I should not see "Deleted post. This post was deleted by"
    And I enter the app
    And I log in as "teacher1"
    And I press "Course 1" in the app
    And I press "Test forum anon" in the app
    When I click on "#mod_forumg_add_discussion" "css_element"
    And I set the field "Subject" to "Test discussion anon" in the app
    And I set the field "Message" to "Test message anon" in the app
    And I click on "//core-site-plugins-plugin-content//ion-select" "xpath_element"
    And I click on "Identify self as moderator (name hidden from students)" "text"
    When I click on "#mma-forumng-add-discussion-button" "css_element"
    And I should see "Test discussion anon"
    And I press "Test discussion anon" in the app
    And I press "Reply" in the app
    And I set the field "Change subject (optional)" to "Test reply" in the app
    And I set the field "Message" to "Test reply message" in the app
    And I click on "//*[@id='mma-forumng-form']//ion-select" "xpath_element"
    And I click on "Identify self as moderator (name hidden from students)" "text"
    And I press "Post reply" in the app
    And I enter the app
    And I log in as "student1"
    And I press "Course 1" in the app
    And I press "Test forum anon" in the app
    And I press "Test discussion anon" in the app
    And I should not see "Teacher 1"

  Scenario: Edit post and check post as moderator
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    When I add a discussion with the following data:
      | Subject | Discussion 1 |
      | Message | abc          |
    When I edit post "1" with the following data:
      | Message     | REPLY3 EDIT                                 |
      | asmoderator | Identify self as moderator (name displayed) |
    Then I should see "REPLY3 EDIT"
    And I should see "Moderator"
    And I enter the app
    And I log in as "teacher1"
    And I press "Course 1" in the app
    And I press "Test forum name" in the app
    When I click on "Discussion 1" "text"
    Then I should see "REPLY3 EDIT"
    And I should see "Moderator"

  @app_from3.9.5
  Scenario: Add discussions and check options in the app new version.
    And I enter the app
    And I log in as "teacher1"
    And I press "Course 1" in the app
    And I press "Test forum discussion" in the app
    When I click on "#mod_forumg_add_discussion" "css_element"
    And I should find "Subject" in the app
    And I should find "Message" in the app
    And I should find "Attachments" in the app
    And I should find "(Show as sticky)" in the app
    And I should find "Only show from" in the app
    And I should find "Post as:" in the app
    And I should find "Date" in the app
    And I should find "Standard Post" in the app
    And I set the field "Subject" to "Test discussion 1" in the app
    And I set the field "Message" to "Test message" in the app
    And I click on "#mma-forumng-show-sticky" "css_element"
    And I click on "ion-datetime" "css_element"
    And I press "Done"
    And I should not find "Date" in the app
    And I scroll to bottom of the app in ForumNG
    When I click on "#mma-forumng-add-discussion-button" "css_element"
    And I should find "Test discussion 1" in the app
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
    And I press "Course 1" in the app
    And I press "Test forum name" in the app
    When I click on "#mod_forumg_add_discussion" "css_element"
    And I should not find "(Show as sticky)" in the app
    And I should not find "Only show from" in the app
    And I should not find "Post as:" in the app
    And I should not find "Date" in the app
    And I should not find "Standard Post" in the app
    And I set the field "Subject" to "Test discussion 2 student" in the app
    And I set the field "Message" to "Test message student" in the app
    When I click on "#mma-forumng-add-discussion-button" "css_element"
    And I should find "Test discussion 2 student" in the app
    And I am on homepage
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    And I should see "Test discussion 2 student"
    And "//img[@alt='This discussion always appears at top of list']" "xpath_element" should not exist
    And I follow "Test discussion 2 student"
    And I should see "Test message student"
    And I enter the app
    And I log in as "student1"
    And I press "Course 1" in the app
    And I press "Test forum name" in the app
    And I press "Test discussion 2 student" in the app
    And I should find "Reply" in the app
    And I should find "Edit" in the app
    And I press "Reply" in the app
    And I should find "Subject" in the app
    And I should find "Message" in the app
    And I should find "Attachments" in the app
    And I set the field "Subject" to "Test reply" in the app
    And I set the field "Message" to "Test reply message" in the app
    And I press "Post reply" in the app
    And I press "EXPAND ALL" in the app
    And I should find "Test reply" in the app
    And I should find "Test reply message" in the app
    And I press "Edit" near "Test reply message" in the app
    And I should find "Test discussion 2 student" in the app
    And I should find "Test message student" in the app
    And I set the field "Subject" to "Test rootpost edited subject" in the app
    And I set the field "Message" to "Test rootpost edited message" in the app
    And I press "Edit post" in the app
    And I press "EXPAND ALL" in the app
    And I should find "Test rootpost edited subject" in the app
    And I should find "Test rootpost edited message" in the app
    And I enter the app
    And I log in as "teacher1"
    And I press "Course 1" in the app
    And I press "Test forum name" in the app
    And I should find "Test discussion 2 student" in the app
    And I press "Test discussion 2 student" in the app
    And I should find "Test rootpost edited subject" in the app
    And I should find "Test rootpost edited message" in the app
    And I should find "Delete" in the app
    And I press "Delete" in the app
    And I should find "Are you sure you want to delete this post?" in the app
    And I click on "//button[contains(@class, 'alert-button') and contains(span, 'Delete')]" "xpath_element"
    And I press "EXPAND ALL" in the app
    And I should find "Deleted post." in the app
    And I press "Undelete" in the app
    And I should find "Are you sure you want to undelete this post?" in the app
    And I click on "//button[contains(@class, 'alert-button') and contains(span, 'Undelete post')]" "xpath_element"
    And I should not find "Deleted post." in the app
    And I enter the app
    And I log in as "teacher1"
    And I press "Course 1" in the app
    And I press "Test forum anon" in the app
    When I click on "#mod_forumg_add_discussion" "css_element"
    And I set the field "Subject" to "Test discussion anon" in the app
    And I set the field "Message" to "Test message anon" in the app
    And I scroll to bottom of the app in ForumNG
    And I click on "//core-site-plugins-plugin-content//ion-select" "xpath_element"
    And I press "Identify self as moderator (name hidden from students)" in the app
    When I click on "#mma-forumng-add-discussion-button" "css_element"
    And I should find "Test discussion anon" in the app
    And I press "Test discussion anon" in the app
    And I press "Reply" in the app
    And I set the field "Subject" to "Test reply" in the app
    And I set the field "Message" to "Test reply message" in the app
    And I scroll to bottom of the app in ForumNG
    And I click on "//*[@id='mma-forumng-form']//ion-select" "xpath_element"
    And I press "Identify self as moderator (name hidden from students)" in the app
    And I press "Post reply" in the app
    And I enter the app
    And I log in as "student1"
    And I press "Course 1" in the app
    And I press "Test forum anon" in the app
    And I press "Test discussion anon" in the app
    And I should not find "Teacher 1" in the app

  @app_from3.9.5
  Scenario: Edit post and check post as moderator new version.
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    When I add a discussion with the following data:
      | Subject | Discussion 1 |
      | Message | abc          |
    When I edit post "1" with the following data:
      | Message     | REPLY3 EDIT                                 |
      | asmoderator | Identify self as moderator (name displayed) |
    Then I should see "REPLY3 EDIT"
    And I should see "Moderator"
    And I enter the app
    And I log in as "teacher1"
    And I press "Course 1" in the app
    And I press "Test forum name" in the app
    And I press "Discussion 1" in the app
    Then I should find "REPLY3 EDIT" in the app
    And I should find "Moderator" in the app
