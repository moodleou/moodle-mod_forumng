@mod @mod_forumng @ou @ou_vle @forumng_create_draft @app @javascript
Feature:  Add draft in forumng and test app can view discussion listing page
  In order to see draft list in the discussion listing page
  As a student
  I can see draft list in the discussion listing page

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
      | forumng  | Test forum anon 2     | Test ForumNG 4 description | C1     | forumng4 | 2           |


  Scenario: Add draft and edit draft and check options in the app
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
    And I set the field "Subject" to "Test draft 1" in the app
    And I set the field "Message" to "Test message" in the app
    And I click on "#mma-forumng-show-sticky" "css_element"
    And I click on "ion-datetime" "css_element"
    And I press "Done"
    When I press "Save as draft" in the app
    And I press "Cancel" in the app
    And "Test draft 1 Test message" "text" should exist in the ".mma-forumng-drafts .row .cell.c0" "css_element"
    And "(new discussion)" "text" should exist in the ".mma-forumng-drafts .row .cell.c1" "css_element"
    And I click on "Test draft 1 Test message" "text" in the ".mma-forumng-drafts .row .cell.c0" "css_element"
    And I set the field "Message" to "Test update message" in the app
    When I press "Save as draft" in the app
    And I press "Cancel" in the app
    And "Test draft 1 Test update message" "text" should exist in the ".mma-forumng-drafts .row .cell.c0" "css_element"
    And I click on "Test draft 1 Test update message" "text" in the ".mma-forumng-drafts .row .cell.c0" "css_element"
    When I press "Post discussion" in the app
    And I should not see "Test draft 1 Test update message"
    And I press "Test draft 1" in the app
    And I press "Reply" in the app
    And I should see "Subject"
    And I should see "Message"
    And I should see "Attachments"
    And I should see "Mark posts as important"
    And I should see "Post as?"
    And I set the field "Change subject (optional)" to "Test draft reply" in the app
    And I set the field "Message" to "Test draft reply message" in the app
    And I click on "#mma-forumng-show-important" "css_element"
    And I press "Save as draft" in the app
    And I click on "page-core-site-plugins-plugin button.back-button" "css_element"
    And "Test draft reply Test draft reply..." "text" should exist in the ".mma-forumng-drafts .row .cell.c0" "css_element"
    And "Test draft 1 (reply to Teacher 1)" "text" should exist in the ".mma-forumng-drafts .row .cell.c1" "css_element"
    And I click on "Test draft reply Test draft reply..." "text" in the ".mma-forumng-drafts .row .cell.c0" "css_element"
    And I set the field "Message" to "Reply message 2" in the app
    And I press "Save as draft" in the app
    And I click on "page-core-site-plugins-plugin button.back-button" "css_element"
    And "Test draft reply Reply message 2" "text" should exist in the ".mma-forumng-drafts .row .cell.c0" "css_element"
    And I click on "Test draft reply Reply message 2" "text" in the ".mma-forumng-drafts .row .cell.c0" "css_element"
    And I press "Post reply" in the app
    And I should see "Test draft reply"
    And I should see "Reply message 2"
    And I click on "page-core-site-plugins-plugin button.back-button" "css_element"
    Then I should not see "Test draft reply Reply message 2"

  Scenario: Add draft and edit draft and check options in the app for student
    And I enter the app
    And I log in as "student1"
    And I press "Course 1" near "Recently accessed courses" in the app
    And I press "Test forum discussion" in the app
    When I click on "#mod_forumg_add_discussion" "css_element"
    And I should see "Subject"
    And I should see "Message"
    And I should see "Attachments"
    And I should not see "(Show as sticky)"
    And I should not see "Only show from"
    And I should not see "Post as:"
    And I should not see "Date"
    And I should not see "Standard Post"
    And I set the field "Subject" to "Test draft 1" in the app
    And I set the field "Message" to "Test message" in the app
    When I press "Save as draft" in the app
    And I press "Cancel" in the app
    And "Test draft 1 Test message" "text" should exist in the ".mma-forumng-drafts .row .cell.c0" "css_element"
    And "(new discussion)" "text" should exist in the ".mma-forumng-drafts .row .cell.c1" "css_element"
    And I click on "Test draft 1 Test message" "text" in the ".mma-forumng-drafts .row .cell.c0" "css_element"
    And I set the field "Message" to "Test update message" in the app
    When I press "Save as draft" in the app
    And I press "Cancel" in the app
    And "Test draft 1 Test update message" "text" should exist in the ".mma-forumng-drafts .row .cell.c0" "css_element"
    And I click on "Test draft 1 Test update message" "text" in the ".mma-forumng-drafts .row .cell.c0" "css_element"
    When I press "Post discussion" in the app
    And I should not see "Test draft 1 Test update message"
    And I press "Test draft 1" in the app
    And I press "Reply" in the app
    And I should see "Subject"
    And I should see "Message"
    And I should see "Attachments"
    And I should not see "Mark posts as important"
    And I should not see "Post as?"
    And I set the field "Change subject (optional)" to "Test draft reply" in the app
    And I set the field "Message" to "Test draft reply message" in the app
    And I press "Save as draft" in the app
    And I click on "page-core-site-plugins-plugin button.back-button" "css_element"
    And "Test draft reply Test draft reply..." "text" should exist in the ".mma-forumng-drafts .row .cell.c0" "css_element"
    And "Test draft 1 (reply to Student 1)" "text" should exist in the ".mma-forumng-drafts .row .cell.c1" "css_element"
    And I click on "Test draft reply Test draft reply..." "text" in the ".mma-forumng-drafts .row .cell.c0" "css_element"
    And I set the field "Message" to "Reply message 2" in the app
    And I press "Save as draft" in the app
    And I click on "page-core-site-plugins-plugin button.back-button" "css_element"
    And "Test draft reply Reply message 2" "text" should exist in the ".mma-forumng-drafts .row .cell.c0" "css_element"
    And I click on "Test draft reply Reply message 2" "text" in the ".mma-forumng-drafts .row .cell.c0" "css_element"
    And I press "Post reply" in the app
    And I should see "Test draft reply"
    And I should see "Reply message 2"
    And I click on "page-core-site-plugins-plugin button.back-button" "css_element"
    Then I should not see "Test draft reply Reply message 2"


  Scenario: Test delete draft
    And I enter the app
    And I log in as "student1"
    And I press "Course 1" near "Recently accessed courses" in the app
    And I press "Test forum discussion" in the app
    When I click on "#mod_forumg_add_discussion" "css_element"
    And I should see "Subject"
    And I should see "Message"
    And I should see "Attachments"
    And I should not see "(Show as sticky)"
    And I should not see "Only show from"
    And I should not see "Post as:"
    And I should not see "Date"
    And I should not see "Standard Post"
    And I set the field "Subject" to "Test draft 1" in the app
    And I set the field "Message" to "Test message" in the app
    When I press "Save as draft" in the app
    And I press "Cancel" in the app
    And "Test draft 1 Test message" "text" should exist in the ".mma-forumng-drafts .row .cell.c0" "css_element"
    And "(new discussion)" "text" should exist in the ".mma-forumng-drafts .row .cell.c1" "css_element"
    And I click on "Test draft 1 Test message" "text" in the ".mma-forumng-drafts .row .cell.c0" "css_element"
    And I set the field "Message" to "Test update message" in the app
    When I press "Save as draft" in the app
    And I press "Cancel" in the app
    And "Test draft 1 Test update message" "text" should exist in the ".mma-forumng-drafts .row .cell.c0" "css_element"
    And I click on "Test draft 1 Test update message" "text" in the ".mma-forumng-drafts .row .cell.c0" "css_element"
    When I press "Post discussion" in the app
    And I should not see "Test draft 1 Test update message"
    And I press "Test draft 1" in the app
    And I press "Reply" in the app
    And I should see "Subject"
    And I should see "Message"
    And I should see "Attachments"
    And I should not see "Mark posts as important"
    And I should not see "Post as?"
    And I set the field "Change subject (optional)" to "Test draft reply" in the app
    And I set the field "Message" to "Test draft reply message" in the app
    And I press "Save as draft" in the app
    And I click on "page-core-site-plugins-plugin button.back-button" "css_element"
    And "Test draft reply Test draft reply..." "text" should exist in the ".mma-forumng-drafts .row .cell.c0" "css_element"
    And "Test draft 1 (reply to Student 1)" "text" should exist in the ".mma-forumng-drafts .row .cell.c1" "css_element"
    And ".mma-forumng-delete-draft" "css_element" should exist
    And I click on ".mma-forumng-delete-draft" "css_element"
    And I should see "Are you sure you want to delete this draft post"
    And I press "Delete"
    And I should not see "Test draft reply"

  Scenario: Test draft reply anon
    And I enter the app
    And I log in as "teacher1"
    And I press "Course 1" near "Recently accessed courses" in the app
    And I press "Test forum anon 2" in the app
    When I click on "#mod_forumg_add_discussion" "css_element"
    And I set the field "Subject" to "Test discussion draft 1" in the app
    And I set the field "Message" to "Test message" in the app
    And I click on "#mma-forumng-show-sticky" "css_element"
    And I click on "ion-datetime" "css_element"
    And I press "Done"
    When I press "Post discussion" in the app
    And I enter the app
    And I log in as "student1"
    And I press "Course 1" near "Recently accessed courses" in the app
    And I press "Test forum anon 2" in the app
    And I press "Test discussion draft 1" in the app
    And I press "Reply" in the app
    And I set the field "Change subject (optional)" to "Test draft reply" in the app
    And I set the field "Message" to "Test draft reply message" in the app
    And I press "Save as draft" in the app
    And I click on "page-core-site-plugins-plugin button.back-button" "css_element"
    And I should see "reply to Identity protected"
    And I am on homepage
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test forum anon 2"
    And I should see "reply to Identity protected"
