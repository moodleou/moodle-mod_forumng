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
      | forumng  | ForumNG 1 | Test ForumNG 1 description | C1     | forumng1 | 0           |
      | forumng  | ForumNG 2 | Test ForumNG 2 description | C1     | forumng2 | 1           |
      | forumng  | ForumNG 3 | Test ForumNG 3 description | C1     | forumng3 | 2           |
      | forumng  | ForumNG 4 | Test ForumNG 4 description | C1     | forumng4 | 2           |
      | forumng  | ForumNG 5 | Test ForumNG 5 description | C1     | forumng5 | 2           |
      | forumng  | ForumNG 6 | Test ForumNG 6 description | C1     | forumng6 | 2           |

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

  Scenario: Student don't have mod/forumng:postanon capability can not see real name in 'User participation'
    Given the following "roles" exist:
      | name              | shortname        | description | archetype |
      | Forumng Post View | forumngpostsview | updateusers |           |
    And the following "permission overrides" exist:
      | capability                    | permission | role             | contextlevel | reference |
      | forumngfeature/userposts:view | Allow      | forumngpostsview | System       |           |
    And the following "role assigns" exist:
      | user     | role             | contextlevel | reference |
      | student2 | forumngpostsview | System       |           |
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "ForumNG 5"
    And I add a discussion with the following data:
      | Subject | Discussion 1 |
      | Message | Test 1       |
    And I reply to post "1" with the following data:
      | Change subject (optional) | Test 2 |
      | Message                   | Test 2 |
    And I log out
    When I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "ForumNG 5"
    Then "Participation by user" "button" should exist
    And I click on "Participation by user" "button"
    And I should see "Identity protected" in the "table .cell.c0" "css_element"
    And I should see "Show all posts by Identity protected" in the "table .cell.c3 a" "css_element"
    And I log out
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "ForumNG 5"
    And I click on "Participation by user" "button"
    Then I should see "Student 1" in the "table .cell.c0" "css_element"
    And I should see "Show all posts by Student 1" in the "table .cell.c3 a" "css_element"

  Scenario: Student don't have mod/forumng:postanon capability can not see real name in 'Usage' page
    Given the following "roles" exist:
      | name               | shortname        | description | archetype |
      | Forumng Usage View | forumngusageview | updateusers |           |
      | Forumng Post anon  | forumngpostanon  | postanon    |           |
    And the following "permission overrides" exist:
      | capability                | permission | role             | contextlevel | reference |
      | forumngfeature/usage:view | Allow      | forumngusageview | System       |           |
      | mod/forumng:postanon      | Allow      | forumngpostanon  | System       |           |
    And the following "role assigns" exist:
      | user     | role             | contextlevel | reference |
      | student2 | forumngusageview | System       |           |
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "ForumNG 6"
    And I add a discussion with the following data:
      | Subject | Discussion 1 |
      | Message | Test 1       |
    And I reply to post "1" with the following data:
      | Change subject (optional) | Test 2 |
      | Message                   | Test 2 |
    And I follow "ForumNG 6"
    And I add a discussion with the following data:
      | Subject     | Discussion 2                                |
      | Message     | Test 2                                      |
      | asmoderator | Identify self as moderator (name displayed) |
    And I reply to post "1" with the following data:
      | Change subject (optional) | Test 2                                      |
      | Message                   | Test 2                                      |
      | asmoderator               | Identify self as moderator (name displayed) |
    And I follow "ForumNG 6"
    And I add a discussion with the following data:
      | Subject     | Discussion 3                                           |
      | Message     | Test 3                                                 |
      | asmoderator | Identify self as moderator (name hidden from students) |
    And I reply to post "1" with the following data:
      | Change subject (optional) | Test 3                                                 |
      | Message                   | Test 3                                                 |
      | asmoderator               | Identify self as moderator (name hidden from students) |
    And I log out
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "ForumNG 6"
    And I add a discussion with the following data:
      | Subject | Discussion 4 |
      | Message | Test 4       |
    And I reply to post "1" with the following data:
      | Change subject (optional) | Test 4 |
      | Message                   | Test 4 |
    And I log out
    When I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "ForumNG 6"
    Then "Show usage" "button" should exist
    And I click on "Show usage" "button"
    And I should not see "Student 1"
    And I should see "1" in the ".forumng_usage_contrib_cont:nth-child(1) .forumng_usage_list .forumng_usage_list_tot" "css_element"
    And I should see "Teacher 1" in the ".forumng_usage_contrib_cont:nth-child(1) .forumng_usage_list .forumng_usage_list_info .fng_userlink a" "css_element"
    And I should see "1" in the ".forumng_usage_contrib_cont:nth-child(2) .forumng_usage_list .forumng_usage_list_tot" "css_element"
    And I should see "Teacher 1" in the ".forumng_usage_contrib_cont:nth-child(2) .forumng_usage_list .forumng_usage_list_info .fng_userlink a" "css_element"
    And I log out
    # Student have mod/forumng:postanon capability can see real name in 'Usage' page.
    When I log in as "admin"
    And the following "role assigns" exist:
      | user     | role            | contextlevel | reference |
      | student2 | forumngpostanon | System       |           |
    And I log out
    When I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "ForumNG 6"
    And I click on "Show usage" "button"
    Then I should see "3" in the ".forumng_usage_contrib_cont:nth-of-type(1) .forumng_usage_list li:nth-of-type(1) .forumng_usage_list_tot" "css_element"
    And I should see "Teacher 1" in the ".forumng_usage_contrib_cont:nth-of-type(1) .forumng_usage_list li:nth-of-type(1) .fng_userlink a" "css_element"
    And I should see "3" in the ".forumng_usage_contrib_cont:nth-of-type(2) .forumng_usage_list li:nth-of-type(1) .forumng_usage_list_tot" "css_element"
    And I should see "Teacher 1" in the ".forumng_usage_contrib_cont:nth-of-type(2) .forumng_usage_list li:nth-of-type(1) .fng_userlink a" "css_element"
    Then I should see "1" in the ".forumng_usage_contrib_cont:nth-of-type(1) .forumng_usage_list li:nth-of-type(2) .forumng_usage_list_tot" "css_element"
    And I should see "Student 1" in the ".forumng_usage_contrib_cont:nth-of-type(1) .forumng_usage_list li:nth-of-type(2) .fng_userlink a" "css_element"
    And I should see "1" in the ".forumng_usage_contrib_cont:nth-of-type(2) .forumng_usage_list li:nth-of-type(2) .forumng_usage_list_tot" "css_element"
    And I should see "Student 1" in the ".forumng_usage_contrib_cont:nth-of-type(2) .forumng_usage_list li:nth-of-type(2) .fng_userlink a" "css_element"
