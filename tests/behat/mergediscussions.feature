@mod @mod_forumng @ou @ou_vle @mod_forumng_postanon @javascript
Feature: Test merge discussions functionality
  In order to manage forum discussions
  As a teacher
  I need to merge discussions together

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email            |
      | teacher1 | Teacher   | 1        | teacha1@asd.com  |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | teacher1 | C1     | teacher |
    And the following "activities" exist:
      | activity | name      | introduction               | course | idnumber |
      | forumng  | ForumNG 1 | Test ForumNG 1 description | C1     | forumng1 |
      | forumng  | ForumNG 2 | Test ForumNG 2 description | C1     | forumng2 |

  Scenario: Merge discussions
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "ForumNG 1"
    And I add a discussion with the following data:
      | Subject     | Discussion 1  |
      | Message     | Message 1     |
    And I follow "ForumNG 1"
    And I add a discussion with the following data:
      | Subject     | Discussion 2                                |
      | Message     | Message 2                                   |
    And I follow "ForumNG 1"
    Then I should see "Discussion 2" in the ".forumng-discussionlist tr.r0 td.forumng-subject" "css_element"
    And I should see "Discussion 1" in the ".forumng-discussionlist tr.r1 td.forumng-subject" "css_element"
    When I follow "Discussion 1"
    And I press "Merge"
    Then I should see "Begin merge" in the "Merging Instructions" "dialogue"
    And I press "Begin merge"
    And I follow "Discussion 2"
    And I press "Merge here"
    Then I should see "Message 2" in the "#forumng-main .forumng-post .forumng-post-outerbox .forumng-postmain .forumng-message" "css_element"
    And I should see "Message 1" in the "#forumng-main .forumng-replies .forumng-post .forumng-post-outerbox .forumng-summary" "css_element"

  Scenario: Cancel merge
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "ForumNG 1"
    And I add a discussion with the following data:
      | Subject     | Discussion 1  |
      | Message     | Message 1     |
    And I follow "ForumNG 1"
    And I add a discussion with the following data:
      | Subject     | Discussion 2                                |
      | Message     | Message 2                                   |
    And I follow "ForumNG 1"
    Then I should see "Discussion 2" in the ".forumng-discussionlist tr.r0 td.forumng-subject" "css_element"
    And I should see "Discussion 1" in the ".forumng-discussionlist tr.r1 td.forumng-subject" "css_element"
    # Cancel before beginning the merge
    When I follow "Discussion 1"
    And I press "Merge"
    Then I should see "Begin merge"
    When I click on "Cancel" "button" in the "Merging Instructions" "dialogue"
    Then I should see "Discussion 1" in the ".forumng-subject" "css_element"
    And "Merge" "button" should exist
    # Cancel before completing the merge
    When I follow "ForumNG 1"
    And I follow "Discussion 1"
    And I press "Merge"
    And I press "Begin merge"
    And I follow "Discussion 2"
    And I press "Cancel"
    Then I should see "Message 2" in the "#forumng-main .forumng-post .forumng-post-outerbox .forumng-postmain .forumng-message" "css_element"
    And "#forumng-main .forumng-replies" "css_element" should not exist

  Scenario: Cannot merge with self
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "ForumNG 1"
    And I add a discussion with the following data:
      | Subject     | Discussion 1  |
      | Message     | Message 1     |
    And I follow "ForumNG 1"
    And I should see "Discussion 1" in the ".forumng-discussionlist tr.r0 td.forumng-subject" "css_element"
    When I follow "Discussion 1"
    And I press "Merge"
    And I press "Begin merge"
    And "tr.forumng-discussion-short.r0.disabled" "css_element" should exist

  Scenario: Cannot merge with discussion in another forum
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "ForumNG 1"
    And I add a discussion with the following data:
      | Subject     | Discussion 1  |
      | Message     | Message 1     |
    And I am on the "ForumNG 2" "forumng activity" page
    And I add a discussion with the following data:
      | Subject     | Discussion 2  |
      | Message     | Message 2     |
    And I follow "ForumNG 2"
    Then I should see "Discussion 2" in the ".forumng-discussionlist tr.r0 td.forumng-subject" "css_element"
    When I follow "Discussion 2"
    And I press "Merge"
    And I press "Begin merge"
    And I am on the "ForumNG 1" "forumng activity" page
    And I follow "Discussion 1"
    Then "Cancel merge" "button" should exist
    And I should see "(Cannot merge here.)" in the ".forumngfeature-merge-extrahtml" "css_element"

  Scenario: Check forum completion with discussion merge.
    Given the following "users" exist:
      | username | firstname | lastname | email      |
      | student1 | Student   | 1        | s1@asd.com |
    And the following "courses" exist:
      | fullname | shortname | format      | enablecompletion |
      | Course 2 | C2        | oustudyplan | 1                |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C2     | student |
      | teacher1 | C2     | teacher |
    And the following "activities" exist:
      | activity | name                  | introduction           | course | idnumber | completion | completionview | completiondiscussionsenabled | completiondiscussions |
      | forumng  | Test forum completion | Test forum description | C2     | forumng3 | 2          | 1              | 1                            | 1                     |
    And the following "mod_forumng > discussions" exist:
      | forum                 | subject     | user     |
      | Test forum completion | discussion1 | teacher1 |
    And the following "mod_forumng > posts" exist:
      | discussion  | message | user     |
      | discussion1 | reply1  | student1 |
    And the following "mod_forumng > discussions" exist:
      | forum                 | subject     | user     |
      | Test forum completion | discussion2 | student1 |
    And the following "mod_forumng > posts" exist:
      | discussion  | message | user     |
      | discussion2 | reply2  | student1 |
    When I log in as "admin"
    And I am on "Course 2" course homepage
    And I follow "Test forum completion"
    Then I should see "discussion2"
    And I should see "discussion1"
    When I follow "discussion2"
    And I press "Merge"
    Then I should see "Begin merge" in the "Merging Instructions" "dialogue"
    And I press "Begin merge"
    And I follow "discussion1"
    And I press "Merge here"
    Then I should see "reply1"
    And I should see "reply2"
