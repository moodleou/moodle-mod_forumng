@mod @mod_forumng @ou @ou_vle @forumng_basic
Feature: Add forumng activity and test basic functionality
  In order to discuss topics with other users
  As a teacher
  I need to add forum activities to moodle courses

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | student1 | Student | 1 | student1@asd.com |
      | student2 | Student | 2 | student2@asd.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user | course | role |
      | student1 | C1 | student |
    And the following "activities" exist:
      | activity | name            | introduction           | course | idnumber |
      | forumng  | Test forum name | Test forum description | C1     | forumng1 |

  Scenario: Access forum as student
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    Then I should see "Test forum description"
    And "Start a new discussion" "button" should exist

  Scenario: Add discussions and check sorting and sticky
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    And I add a discussion with the following data:
      | Subject | Discussion 1 |
      | Message | abc |
    And I follow "Test forum name"
    Then I should see "Discussion 1" in the ".forumng-subject" "css_element"
    And "//td[1]//img" "xpath_element" should not exist in the "Discussion 1" "table_row"
    And I should see "1" in the "//table[contains(@class,'forumng-discussionlist')]/tbody/tr[1]//td[3]" "xpath_element"
    Given I add a discussion with the following data:
      | Subject | Discussion 2 |
      | Message | abcdefg |
      | sticky | 1 |
    And I follow "Test forum name"
    Then I should see "Discussion 2" in the ".forumng-subject" "css_element"
    And I should see "Discussion 2" in the "//table[contains(@class,'forumng-discussionlist')]/tbody/tr[1]//td[1]" "xpath_element"
    And "//td[1]//img" "xpath_element" should exist in the "Discussion 2" "table_row"
    Given I add a discussion with the following data:
      | Subject | Discussion 3 |
      | Message | abcdefghijk |
    And I follow "Test forum name"
    # Check discussion 3 is second in list of discussions (allowing for extra divider row)
    Then I should see "Discussion 3" in the "//table[contains(@class,'forumng-discussionlist')]/tbody/tr[3]//td[1]" "xpath_element"
    # Check sorting
    Given I follow "Discussion"
    Then I should see "Discussion 2" in the "//table[contains(@class,'forumng-discussionlist')]/tbody/tr[1]//td[1]" "xpath_element"
    And I should see "Discussion 1" in the "//table[contains(@class,'forumng-discussionlist')]/tbody/tr[3]//td[1]" "xpath_element"
    Given I follow "Last post"
    Then I should see "Discussion 2" in the "//table[contains(@class,'forumng-discussionlist')]/tbody/tr[1]//td[1]" "xpath_element"
    And I should see "Discussion 3" in the "//table[contains(@class,'forumng-discussionlist')]/tbody/tr[3]//td[1]" "xpath_element"
    Given I follow "Discussion 1"
    And I reply to post "1" with the following data:
      | Message | HELLO |
    And I follow "Test forum name"
    Then I should see "2" in the "//table[contains(@class,'forumng-discussionlist')]/tbody/tr[3]//td[4]" "xpath_element"
    Given I follow "Posts"
    Then I should see "Discussion 1" in the "//table[contains(@class,'forumng-discussionlist')]/tbody/tr[3]//td[1]" "xpath_element"
    Given I follow "Posts"
    Then I should see "Discussion 3" in the "//table[contains(@class,'forumng-discussionlist')]/tbody/tr[3]//td[1]" "xpath_element"

  @mod_forumng_unread
  Scenario: Check discussion post replies, unread and editing
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    And I add a discussion with the following data:
      | Subject | Discussion 1 |
      | Message | abc |
    And I reply to post "1" with the following data:
      | Message | REPLY1 |
    And I reply to post "1" with the following data:
      | Message | REPLY2 |
    And I log out
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    Then I should see "(Unread posts)"
    Given I follow "Test forum name"
    Then I should see "3" in the "//table[contains(@class,'forumng-discussionlist')]/tbody/tr[1]//td[4]" "xpath_element"
    And I should see "3" in the "//table[contains(@class,'forumng-discussionlist')]/tbody/tr[1]//td[2]" "xpath_element"
    Given I follow "Discussion 1"
    Then "li.forumng-edit" "css_element" should not exist
    And "li.forumng-delete" "css_element" should not exist
    And I should see "Collapse all posts"
    And ".forumng-p2 a.forumng-parent" "css_element" should exist
    And ".forumng-p2 a.forumng-next" "css_element" should exist
    And ".forumng-p2 a.forumng-prev" "css_element" should exist
    And ".forumng-p3 a.forumng-next" "css_element" should not exist
    Given I reply to post "3" with the following data:
      | Message | REPLY3 |
    Then I should see "REPLY3"
    And "li.forumng-edit" "css_element" should exist
    And "li.forumng-delete" "css_element" should exist
    Given I reply to post "4" with the following data:
      | Message | REPLY4 |
    Then I should see "REPLY4"
    Given I edit post "4" with the following data:
      | Message | REPLY3 EDIT |
    Then I should see "REPLY3 EDIT"
    Given I click on "#forumng-arrowback a" "css_element"
    Then I should see "5" in the "//table[contains(@class,'forumng-discussionlist')]/tbody/tr[1]//td[4]" "xpath_element"
    And I should see "" in the "//table[contains(@class,'forumng-discussionlist')]/tbody/tr[1]//td[2]" "xpath_element"
    Given I press "Change"
    Then I should see "Manually mark as read"
    And I should see "" in the "//table[contains(@class,'forumng-discussionlist')]/tbody/tr[1]//td[2]" "xpath_element"
    Given I am on "Course 1" course homepage
    Then I should not see "(unread posts)"
    And I log out
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    Then I should see "(Unread posts)"
    Given I follow "Test forum name"
    And I should see "5" in the "//table[contains(@class,'forumng-discussionlist')]/tbody/tr[1]//td[4]" "xpath_element"
    And I should see "2" in the "//table[contains(@class,'forumng-discussionlist')]/tbody/tr[1]//td[2]" "xpath_element"
    When I press "Change"
    Then I should see "Manually mark as read"
    And I should see "2" in the "//table[contains(@class,'forumng-discussionlist')]/tbody/tr[1]//td[2]" "xpath_element"
    Given I follow "Discussion 1"
    Then ".forumng-p4 .forumng-markread" "css_element" should exist
    And ".forumng-p5 .forumng-markread" "css_element" should exist
    When I click on ".forumng-p4 .forumng-markread a" "css_element"
    Then ".forumng-p4 .forumng-markread" "css_element" should not exist
    Given I follow "Test forum name"
    And I should see "1" in the "//table[contains(@class,'forumng-discussionlist')]/tbody/tr[1]//td[2]" "xpath_element"
    Given I follow "Discussion 1"
    When I click on ".forumng-p5 .forumng-markread a" "css_element"
    Then ".forumng-p5 .forumng-markread" "css_element" should not exist
    Given I follow "Test forum name"
    And I should see "Manually mark as read"
    And I should see "" in the "//table[contains(@class,'forumng-discussionlist')]/tbody/tr[1]//td[2]" "xpath_element"
    Given I follow "Discussion 1"
    When I press "Show readers"
    Then I should see "Student 1"
    And I should see "Admin User"
    When I am on "Course 1" course homepage
    Then I should not see "(Unread posts)"

  Scenario: Deleting + locking discussions + posts
    # NOTE - this is non-js specific, will fail if @javascript enabled on this scenario.
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    And I add a discussion with the following data:
      | Subject | Discussion 1 |
      | Message | abc |
    And I reply to post "1" with the following data:
      | Message | REPLY1 |
    Given I click on "li.forumng-delete a" "css_element"
    Then I should see "Are you sure you want to delete this post?"
    Given I press "Cancel"
    Then I should not see "Deleted post."
    Given I click on "li.forumng-delete a" "css_element"
    And I press "Delete"
    Then I should see "Deleted post."
    And "li.forumng-undelete" "css_element" should exist
    Given I click on "li.forumng-undelete a" "css_element"
    Then I should see "Are you sure you want to undelete this post?"
    Given I press "Cancel"
    Then I should see "Deleted post."
    Given I click on "li.forumng-undelete a" "css_element"
    And I press "Undelete"
    Then I should not see "Deleted post."
    Given I press "Lock"
    Then I should see "Lock discussion: Discussion 1"
    Given I set the following fields to these values:
      | Message | A lock post |
    And I press "Lock discussion"
    Then I should see "This discussion is now closed"
    And I should see "A lock post"
    And "Reply" "link" should not exist
    Given I follow "Test forum name"
    Then ".forumng-subject.cell.c0 img" "css_element" should exist
    Given I follow "Discussion 1"
    And I press "Unlock"
    Then I should see "Are you sure you want to unlock this discussion?"
    Given I press "Cancel"
    Then I should see "This discussion is now closed"
    Given I press "Unlock"
    And I press "Unlock"
    Then "Lock" "button" should exist
    And "Reply" "link" should exist

  Scenario: Flagging (and removing flag) posts without javascript
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    And I add a discussion with the following data:
      | Subject | Discussion 1 |
      | Message | abc |
    And I reply to post "1" with the following data:
      | Message | REPLY1 |
    And I reply to post "1" with the following data:
      | Message | REPLY2 |
    And I reply to post "1" with the following data:
      | Message | REPLY3 |
    Then I should see "Discussion 1"
    And I should see "REPLY1"
    And I should see "REPLY2"
    And I should see "REPLY3"
    And ".forumng-flagpost" "css_element" should exist

    # Discussion1 post
    And ".forumng-p1 .forumng-flagpost img" "css_element" should exist
    # Reply1 post
    And ".forumng-p2 .forumng-flagpost img" "css_element" should exist
    # Reply3 post
    And ".forumng-p4 .forumng-flagpost img" "css_element" should exist
    And the "title" attribute of ".forumng-p1 .forumng-flagpost a" "css_element" should contain "Flag this post for future reference"

    # Click to flag Reply1
    And I click on ".forumng-p2 .forumng-flagpost a" "css_element"
    # Click 'Expand' to access 'Flag' for Replies
    And I expand post "3"
    And I click on ".forumng-p3 .forumng-flagpost a" "css_element"
    And I expand post "4"
    And I click on ".forumng-p4 .forumng-flagpost a" "css_element"
    And ".forumng-p4 .forumng-flagpost img" "css_element" should exist
    And the "title" attribute of ".forumng-p2 .forumng-flagpost a" "css_element" should contain "Remove flag"

    # Check flagged posts display ok on main forum page
    And I follow "Test forum name"
    And "3 flagged posts" "link" should exist
    And ".forumng-flagged-link" "css_element" should exist
    And ".forumng-flagged" "css_element" should exist
    And "REPLY3" "link" should exist
    And "REPLY2" "link" should exist
    And "REPLY1" "link" should exist

    # Click Reply3 to remove flag
    And I click on "tr.r0 td.cell.c0 form.forumng-flag input[type=image]" "css_element"
    Then "REPLY3" "link" should not exist
    And "REPLY2" "link" should exist
    And "REPLY1" "link" should exist

    # Return to discussion page
    And I follow "Discussion 1"
    And the "title" attribute of ".forumng-p2 .forumng-flagpost a" "css_element" should contain "Remove flag"
    # Click to un-flag Reply1
    And I click on ".forumng-p2 .forumng-flagpost a" "css_element"
    And I expand post "2"
    And the "title" attribute of ".forumng-p2 .forumng-flagpost a" "css_element" should contain "Flag this post for future reference"

    # Check numbner of flagged posts display on main forum page
    And I follow "Test forum name"
    And "1 flagged posts" "link" should exist
    And ".forumng-flagged-link" "css_element" should exist
    And ".forumng-flagged" "css_element" should exist
    And "REPLY2" "link" should exist
    And I log out


  @javascript
  Scenario: Flagging (and removing flag) posts with javascript
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    And I add a discussion with the following data:
      | Subject | Discussion 1 abc |
      | Message | abc |
    And I reply to post "1" with the following data:
      | Message | REPLY1 |
    And I reply to post "1" with the following data:
      | Message | REPLY2 |
    And I reply to post "1" with the following data:
      | Message | REPLY3 |
    Then I should see "Discussion 1 abc"
    And I should see "REPLY1"
    And I should see "REPLY2"
    And I should see "REPLY3"

    # Discussion1 post
    And ".forumng-p1 .forumng-flagpost a img" "css_element" should exist
    # Reply3 post
    And ".forumng-p4 .forumng-flagpost a img" "css_element" should exist
    And the "title" attribute of ".forumng-p4 .forumng-flagpost a" "css_element" should contain "Flag this post for future reference"

    # Click to flag Reply1.
    And I click on ".forumng-p2 .forumng-flagpost a" "css_element"
    And I wait "1" seconds
    # Click to flag Reply2.
    And I click on ".forumng-p3 .forumng-flagpost a" "css_element"
    And I wait "1" seconds
    # Click to flag Reply3.
    And I click on ".forumng-p4 .forumng-flagpost a" "css_element"
    And the "title" attribute of ".forumng-p4 .forumng-flagpost a" "css_element" should contain "Remove flag"

    # Check flagged posts display ok on main forum page
    And I follow "Test forum name"
    And "3 flagged posts" "link" should exist
    And ".forumng-flagged-link" "css_element" should exist
    And ".forumng-flagged" "css_element" should exist
    And "REPLY3" "link" should exist
    And "REPLY2" "link" should exist
    And "REPLY1" "link" should exist

    # Click to un-flag Reply3 from forum main page
    And I click on "#forumng-flaggedposts .r0 form.forumng-flag input[type='image']" "css_element"
    And I wait "1" seconds
    And "REPLY3" "link" should not exist
    And "REPLY2" "link" should exist
    And "REPLY1" "link" should exist

    # Return to discussion page
    And I follow "Discussion 1 abc"
    # Click to un-flag Reply1
    And the "title" attribute of ".forumng-p2 .forumng-flagpost a" "css_element" should contain "Remove flag"
    And I click on ".forumng-p2 .forumng-flagpost img" "css_element"
    And the "title" attribute of ".forumng-p2 .forumng-flagpost a" "css_element" should contain "Flag this post for future reference"

    # Check number of flagged posts display on main forum page.
    And I follow "Test forum name"
    And "1 flagged posts" "link" should exist
    And ".forumng-flagged-link" "css_element" should exist
    And ".forumng-flagged" "css_element" should exist
    And "REPLY3" "link" should not exist
    And "REPLY1" "link" should not exist
    And "REPLY2" "link" should exist
    And I log out

  Scenario: Test subscription buttons
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    When I follow "Test forum name"
    Then I should see "You do not currently receive messages from this forum"
    And "Subscribe" "button" should exist
    Given I add a discussion with the following data:
      | Subject | Discussion 1 |
      | Message | abc |
    Then I should see "You do not currently receive messages from this"
    And "Subscribe to discussion" "button" should exist
    Given I press "Subscribe to discussion"
    Then I should see "Your email preferences"
    And I should see "No digest (single email per forum post)"
    And I should see "Pretty HTML format"
    And "change" "link" should exist
    And "Unsubscribe from discussion" "button" should exist
    Given I follow "Test forum name"
    Then I should see "You receive messages from some discussions in"
    And I should see "Your email preferences ("
    Given I press "Subscribe to whole forum"
    Then I should see "You receive messages from this forum via email to"
    And I should see "Your email preferences ("
    And "Unsubscribe" "button" should exist
    Given I follow "change"
    And I should see "Forum preferences"
    And I should see "Email digest type"
    And I should see "Email format"
    And I set the field "mailformat" to "0"
    And I set the field "maildigest" to "1"
    When I press "Save changes"
    Then I should see "Complete (daily email with full posts)"
    And I should see "Plain text format"
    Given I follow "Discussion 1"
    Then I should not see "You do not currently receive messages from this"
    And I should not see "Your email preferences"
    Given I follow "Test forum name"
    And I press "Unsubscribe"
    Then I should see "You do not currently receive messages from this"
    Given I log out
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    And I follow "Edit settings"
    And I set the field "subscription" to "3"
    And I press "Save and display"
    When I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    When I follow "Test forum name"
    Then I should see "You receive messages from this forum via email to"
    And I should see "This forum does not allow you to unsubscribe"

  @javascript
  Scenario: Test forum feature buttons on mobile
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    Then "Show usage" "button" should be visible
    And "Participation by user" "button" should be visible
    And "..." "button" should not exist
    Given I change window size to "320x768"
    And I wait "1" seconds
    Then "Show usage" "button" should not be visible
    And "Participation by user" "button" should be visible
    And "..." "button" should exist
    Given I change window size to "large"
    And I wait "1" seconds
    Then "Show usage" "button" should be visible
    And "Participation by user" "button" should be visible
    And "..." "button" should not be visible

  @javascript
  Scenario: Add discussions and check button disable
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    And I add a discussion with the following data:
      | Subject | Discussion 1 |
      | Message | abc |
    And I reply to post "1" with the following data:
      | Message | REPLY1 |
    And I click on "Reply" "link"
    Then ".forumng-p2 .forumng-delete a.forumng-disabled" "css_element" should exist
    Then ".forumng-p2 .forumng-edit a.forumng-disabled" "css_element" should exist
    Then ".forumng-p2 .forumng-replylink a.forumng-disabled" "css_element" should exist
    And I switch to "forumng-post-iframe" iframe
    And I press "Cancel"
    Then ".forumng-p2 .forumng-delete a.forumng-disabled" "css_element" should not exist
    Then ".forumng-p2 .forumng-edit a.forumng-disabled" "css_element" should not exist
    Then ".forumng-p2 .forumng-replylink a.forumng-disabled" "css_element" should not exist

  @javascript
  Scenario: Edit discussion subject
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    And I add a discussion with the following data:
      | Subject | Discussion original |
      | Message | abc |
    When I edit post "1" with the following data:
      | Subject | Discussion edited |
    And I should see "Discussion edited" in the ".forumng-subject" "css_element"
