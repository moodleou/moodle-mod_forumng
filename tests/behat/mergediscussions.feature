@mod @mod_forumng @ou @ou_vle @mod_forumng_postanon
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
    When I press "Cancel"
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
    And I follow "Discussion 1"
    Then "Cancel merge" "button" should exist
    And I should see "(Cannot merge here.)" in the ".forumngfeature-merge-extrahtml" "css_element"
    When I press "Cancel merge"
    Then I should see "Discussion 1" in the ".forumng-subject" "css_element"
    And ".forumngfeature-merge-extrahtml" "css_element" should not exist
    And "Merge" "button" should exist

  Scenario: Cannot merge with discussion in another forum
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "ForumNG 1"
    And I add a discussion with the following data:
      | Subject     | Discussion 1  |
      | Message     | Message 1     |
    And I follow "ForumNG 2"
    And I add a discussion with the following data:
      | Subject     | Discussion 2  |
      | Message     | Message 2     |
    And I follow "ForumNG 2"
    Then I should see "Discussion 2" in the ".forumng-discussionlist tr.r0 td.forumng-subject" "css_element"
    When I follow "Discussion 2"
    And I press "Merge"
    And I press "Begin merge"
    And I follow "ForumNG 1"
    And I follow "Discussion 1"
    Then "Cancel merge" "button" should exist
    And I should see "(Cannot merge here.)" in the ".forumngfeature-merge-extrahtml" "css_element"