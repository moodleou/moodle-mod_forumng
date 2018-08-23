@mod @mod_forumng @ou @ou_vle @mod_forumng_postanon
Feature: Test post anonymously functionality
  In order to discuss topics with other users
  As a teacher
  I need the student can post anonymously
  I need to see anonymous posts as normal

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email            |
      | student1 | Student   | 1        | student1@asd.com |
      | student2 | Student   | 2        | student2@asd.com |
      | teacher1 | Teacher   | 1        | teacha1@asd.com  |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
      | student2 | C1     | student |
      | teacher1 | C1     | teacher |
    And the following "activities" exist:
      | activity | name      | introduction               | course | idnumber | canpostanon |
      | forumng  | ForumNG 1 | Test ForumNG 1 description | C1     | forumng3 | 0           |
      | forumng  | ForumNG 2 | Test ForumNG 2 description | C1     | forumng1 | 1           |
      | forumng  | ForumNG 3 | Test ForumNG 3 description | C1     | forumng2 | 2           |
      | forumng  | ForumNG 4 | Test ForumNG 4 description | C1     | forumng2 | 2           |

  Scenario: Post as normal
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "ForumNG 1"
    And I add a discussion with the following data:
      | Subject | Discussion 1 |
      | Message | abc          |
    And I follow "ForumNG 1"
    Then I should see "Discussion 1" in the ".forumng-discussionlist tr.r0 td.forumng-subject" "css_element"
    And I log out
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "ForumNG 1"
    Then I should see "Teacher 1" in the ".forumng-discussionlist tr.r0 td.forumng-lastpost" "css_element"
    And I should see "Teacher 1" in the ".forumng-discussionlist tr.r0 td.forumng-startedby" "css_element"
    And I follow "Discussion 1"
    Then I should see "Teacher 1" in the ".forumng-post .forumng-info .forumng-author" "css_element"
    When I reply to post "1" with the following data:
      | Change subject (optional) | Test1 |
      | Message                   | Test1 |
    Then I should see "Student 1" in the ".forumng-replies .forumng-post .forumng-info .forumng-author" "css_element"

  Scenario: Moderators can post anonymously
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "ForumNG 2"
    And I add a discussion with the following data:
      | Subject     | Discussion 1                                           |
      | Message     | abc                                                    |
      | asmoderator | Identify self as moderator (name hidden from students) |
    And I follow "ForumNG 2"
    And I add a discussion with the following data:
      | Subject     | Discussion 2               |
      | Message     | abc                        |
      | asmoderator | Identify self as moderator |
    And I follow "ForumNG 2"
    Then I should see "Discussion 2" in the ".forumng-discussionlist tr.r0 td.forumng-subject" "css_element"
    And I should see "Discussion 1" in the ".forumng-discussionlist tr.r1 td.forumng-subject" "css_element"
    And I log out
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "ForumNG 2"
    Then I should see "Discussion 1" in the ".forumng-discussionlist tr.r1 td.forumng-subject" "css_element"
    And I should see "Moderator" in the ".forumng-discussionlist tr.r1 td.forumng-lastpost" "css_element"
    And I should see "Moderator" in the ".forumng-discussionlist tr.r1 td.forumng-startedby" "css_element"
    And I should see "Discussion 2" in the ".forumng-discussionlist tr.r0 td.forumng-subject" "css_element"
    And I should see "Teacher 1" in the ".forumng-discussionlist tr.r0 td.forumng-lastpost" "css_element"
    And I should see "Moderator" in the ".forumng-discussionlist tr.r0 td.forumng-lastpost" "css_element"
    And I should see "Teacher 1" in the ".forumng-discussionlist tr.r0 td.forumng-startedby" "css_element"
    And I should see "Moderator" in the ".forumng-discussionlist tr.r0 td.forumng-startedby" "css_element"
    When I follow "Discussion 1"
    And I reply to post "1" with the following data:
      | Change subject (optional) | Test1 |
      | Message                   | Test1 |
    Then I should see "Student 1" in the ".forumng-replies .forumng-post .forumng-info .forumng-author" "css_element"
    When I follow "ForumNG 2"
    Then I should see "Student 1" in the ".forumng-discussionlist tr.r0 td.forumng-lastpost" "css_element"
    When I follow "ForumNG 2"
    And I follow "Discussion 2"
    Then I should see "Teacher 1" in the ".forumng-post .forumng-info .forumng-author" "css_element"
    And I should see "Moderator" in the ".forumng-post .forumng-info .forumng-author .forumng-moderator-flag" "css_element"

  Scenario: Non-moderators can post anonymously
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "ForumNG 3"
    And I add a discussion with the following data:
      | Subject | Discussion 1 |
      | Message | abc          |
    And I follow "ForumNG 3"
    Then I should see "Discussion 1" in the ".forumng-discussionlist tr.r0 td.forumng-subject" "css_element"
    Then I should see "Identity protected" in the ".forumng-discussionlist tr.r0 td.forumng-lastpost" "css_element"
    And I should see "Identity protected" in the ".forumng-discussionlist tr.r0 td.forumng-startedby" "css_element"
    And I follow "Discussion 1"
    And I should see "Identity protected" in the ".forumng-post .forumng-info .forumng-author" "css_element"
    And I log out
    When I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "ForumNG 3"
    Then I should see "Identity protected" in the ".forumng-discussionlist tr.r0 td.forumng-lastpost" "css_element"
    And I should see "Identity protected" in the ".forumng-discussionlist tr.r0 td.forumng-startedby" "css_element"
    When I follow "Discussion 1"
    Then I should see "Identity protected" in the ".forumng-post .forumng-info .forumng-author" "css_element"
    When I reply to post "1" with the following data:
      | Change subject (optional) | Test2 |
      | Message                   | Test2 |
    Then I should see "Identity protected" in the ".forumng-replies .forumng-post .forumng-info .forumng-author" "css_element"
    And I follow "ForumNG 3"
    Then I should see "Identity protected" in the ".forumng-discussionlist tr.r0 td.forumng-lastpost" "css_element"
    And I log out
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "ForumNG 3"
    Then I should see "Student 1" in the ".forumng-discussionlist tr.r0 td.forumng-startedby" "css_element"
    And I should see "Student 2" in the ".forumng-discussionlist tr.r0 td.forumng-lastpost" "css_element"

  Scenario: Flagged post display author as Identity protected
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "ForumNG 4"
    And I add a discussion with the following data:
      | Subject | Discussion 1 |
      | Message | abc          |
    And I follow "ForumNG 4"
    Then I should see "Discussion 1" in the ".forumng-discussionlist tr.r0 td.forumng-subject" "css_element"
    And I log out
    When I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "ForumNG 4"
    And I follow "Discussion 1"
    And I click on "Flag post" "link" in the ".forumng-post .forumng-commands .forumng-flagpost" "css_element"
    Then I should see "Remove flag" in the ".forumng-post .forumng-commands .forumng-flagpost .flagtext" "css_element"
    When I follow "ForumNG 4"
    Then I should see "(by Identity protected)" in the ".forumng-flagged .cell.c0" "css_element"
