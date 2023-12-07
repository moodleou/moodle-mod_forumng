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
    And I should see "1" in the "//table[contains(@class,'forumng-discussionlist')]/tbody/tr[1]//td[4]" "xpath_element"
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
    And ".forumng-p2 .forumng-jumpto .forumng-parent .accesshide" "css_element" should exist
    And ".forumng-p3 .forumng-jumpto .forumng-parent .accesshide" "css_element" should exist
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
    And I wait "2" seconds
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
    And the "title" attribute of ".forumng-p1 .forumng-flagpost a" "css_element" should contain "Star this post for future reference"

    # Click 'Expand' to access 'Flag' for Replies
    And I expand post "4"
    And I click on ".forumng-p4 .forumng-flagpost a" "css_element"
    And I wait "1" seconds
    And I expand post "3"
    And I click on ".forumng-p3 .forumng-flagpost a" "css_element"
    And I wait "1" seconds
    And I expand post "2"
    And I click on ".forumng-p2 .forumng-flagpost a" "css_element"
    And ".forumng-p2 .forumng-flagpost img" "css_element" should exist
    And the "title" attribute of ".forumng-p2 .forumng-flagpost a" "css_element" should contain "Remove star"

    # Check flagged posts display ok on main forum page
    And I follow "Test forum name"
    And "Skip to starred posts" "link" should exist
    And ".forumng-flagged-link" "css_element" should exist
    And ".forumng-flagged" "css_element" should exist
    And "REPLY3" "link" should exist
    And "REPLY2" "link" should exist
    And "REPLY1" "link" should exist

    # Click Reply3 to remove flag
    And I click on "#forumng-flaggedposts tr.r0.lastrow td.cell.c0 form.forumng-flag input[type=image]" "css_element"
    And I wait "3" seconds
    Then "REPLY3" "link" should not exist
    And "REPLY2" "link" should exist
    And "REPLY1" "link" should exist

    # Return to discussion page
    And I follow "Discussion 1"
    And the "title" attribute of ".forumng-p2 .forumng-flagpost a" "css_element" should contain "Remove star"
    # Click to un-flag Reply1
    And I click on ".forumng-p2 .forumng-flagpost a" "css_element"
    And I expand post "2"
    And the "title" attribute of ".forumng-p2 .forumng-flagpost a" "css_element" should contain "Star this post for future reference"

    # Check numbner of flagged posts display on main forum page
    And I follow "Test forum name"
    And "Skip to starred posts" "link" should exist
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
    And the "title" attribute of ".forumng-p4 .forumng-flagpost a" "css_element" should contain "Star this post for future reference"

    # Click to flag Reply1.
    And I click on ".forumng-p2 .forumng-flagpost a" "css_element"
    And I wait "1" seconds
    # Click to flag Reply2.
    And I click on ".forumng-p3 .forumng-flagpost a" "css_element"
    And I wait "1" seconds
    # Click to flag Reply3.
    And I click on ".forumng-p4 .forumng-flagpost a" "css_element"
    And the "title" attribute of ".forumng-p4 .forumng-flagpost a" "css_element" should contain "Remove star"

    # Check flagged posts display ok on main forum page
    And I follow "Test forum name"
    And "Skip to starred posts" "link" should exist
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
    And the "title" attribute of ".forumng-p2 .forumng-flagpost a" "css_element" should contain "Remove star"
    And I click on ".forumng-p2 .forumng-flagpost img" "css_element"
    And the "title" attribute of ".forumng-p2 .forumng-flagpost a" "css_element" should contain "Star this post for future reference"

    # Check number of flagged posts display on main forum page.
    And I follow "Test forum name"
    And "Skip to starred posts" "link" should exist
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
    And "Unsubscribe from discussions" "button" should exist
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
    And I follow "Settings"
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
    And I click on "Reply" "link" in the ".forumng-p2" "css_element"
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

  @javascript
  Scenario: Check date validation
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    And I am on the "Test forum name" "forumng activity" page
    And I navigate to "Settings" in current page administration
    And I expand all fieldsets
    And I click on "#id_postinguntil_enabled" "css_element"
    And I click on "#id_postingfrom_enabled" "css_element"
    And I set the field "id_postinguntil_year" to "2011"
    And I set the field "id_enableratings" to "1"
    And I set the field "id_ratingthreshold" to "1"
    And I click on "#id_ratinguntil_enabled" "css_element"
    And I click on "#id_ratingfrom_enabled" "css_element"
    And I set the field "id_ratinguntil_year" to "2011"
    When I press "Save and display"
    Then I should see "Selection end date cannot be earlier than the start date"

  @javascript
  Scenario: Show notice message when user can post as anon.
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    And I navigate to "Settings" in current page administration
    And I expand all fieldsets
    And I set the field "id_canpostanon" to "2"
    And I set the field "id_enableratings" to "1"
    And I set the field "id_ratingthreshold" to "1"
    When I press "Save and display"
    Then I should see "Posts to this forum will be identity protected - individuals' names will not be displayed."
    And I add a discussion with the following data:
      | Subject | Discussion original |
      | Message | abc                 |
    Then I should not see "Posts to this forum will be identity protected - individuals' names will not be displayed."
    And I log out
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    Then I should see "Posts to this forum will be identity protected - individuals' names will not be displayed."
    And I follow "Discussion original"
    Then I should not see "Posts to this forum will be identity protected - individuals' names will not be displayed."
    And I log out
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    And I navigate to "Settings" in current page administration
    And I set the field "id_canpostanon" to "1"
    When I press "Save and display"
    Then I should not see "Posts to this forum will be identity protected - individuals' names will not be displayed."
    And I log out
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    Then I should not see "Posts to this forum will be identity protected - individuals' names will not be displayed."

  Scenario: Scheduled post should display future date after edited
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    And I add a discussion with the following data:
      | Subject            | Discussion 1 original |
      | Message            | abc                   |
      | timestart[enabled] | 1                     |
      | timestart[day]     | 13                    |
      | timestart[month]   | 11                    |
      | timestart[year]    | 2030                  |
    And I edit post "1" with the following data:
      | Subject | Discussion 1 edited |
    And I should see "Discussion 1 edited" in the ".forumng-subject" "css_element"
    And I should see "13 November 2030" in the ".forumng-pic-info" "css_element"
    And I follow "Test forum name"
    And I should see "13/11/30" in the ".forumng-lastpost" "css_element"

  Scenario: Check atom and rss links displayed as expected
    Given the following config values are set as admin:
      | enablerssfeeds   | 1 |
      | forumng_feedtype | 2 |
      | forumng_enablerssfeeds | 1 |
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    When I follow "Test forum name"
    Then "Atom" "link" should exist
    And "RSS" "link" should exist
    When I add a discussion with the following data:
      | Subject            | Discussion 1 |
      | Message            | abc          |
    Then "Atom" "link" should exist
    And "RSS" "link" should exist
    Given the following "permission overrides" exist:
      | capability           | permission | role    | contextlevel | reference |
      | mod/forumng:showatom | Prevent    | student | System       |           |
    And I log out
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    When I follow "Test forum name"
    Then "Atom" "link" should not exist
    And "RSS" "link" should exist
    When I follow "Discussion 1"
    Then "Atom" "link" should not exist
    And "RSS" "link" should exist
    Given the following "permission overrides" exist:
      | capability           | permission | role    | contextlevel | reference |
      | mod/forumng:showrss  | Prevent    | student | System       |           |
      | mod/forumng:showatom | Allow      | student | System       |           |
    And I am on "Course 1" course homepage
    When I follow "Test forum name"
    Then "Atom" "link" should exist
    And "RSS" "link" should not exist
    When I follow "Discussion 1"
    Then "Atom" "link" should exist
    And "RSS" "link" should not exist
    Given the following "permission overrides" exist:
      | capability           | permission | role    | contextlevel | reference |
      | mod/forumng:showrss  | Prevent    | student | System       |           |
      | mod/forumng:showatom | Prevent    | student | System       |           |
    And I am on "Course 1" course homepage
    When I follow "Test forum name"
    Then "Atom" "link" should not exist
    And "RSS" "link" should not exist
    And ".forumng-feedlinks" "css_element" should not exist
    When I follow "Discussion 1"
    Then "Atom" "link" should not exist
    And "RSS" "link" should not exist
    And ".forumng-feedlinks" "css_element" should not exist

  @javascript
  Scenario: Deleting post with collapse all.
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    And I add a discussion with the following data:
      | Subject | Discussion 1 |
      | Message | abc          |
    And I reply to post "1" with the following data:
      | Message | REPLY1 |
    And I reload the page
    And I click on "Expand all posts" "link"
    Given I click on "li.forumng-delete a" "css_element"
    Then I should see "Are you sure you want to delete this post?"

  Scenario: Check configurations for manage old discussions and default setting
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    When I navigate to "Plugins > Activity modules > ForumNG" in site administration
    Then I should see "Manage old discussions after"
    And "//select[contains(@id,'id_s__forumng_removeolddiscussions')]/option[contains(@value, '62208000') and contains(@selected, '')]" "xpath_element" should exist
    And I should see "Default: Never remove"
    Then I should see "Action or move discussions to"
    And "//select[contains(@id,'id_s__forumng_withremoveddiscussions')]/option[contains(@value, '0') and contains(@selected, '')]" "xpath_element" should exist
    And I should see "Default: Delete permanently"
    Then I am on "Course 1" course homepage
    And I follow "Test forum name"
    And I navigate to "Settings" in current page administration
    Then "//select[contains(@id,'id_removeafter')]/option[contains(@value, '62208000') and contains(@selected, '')]" "xpath_element" should exist
    And "//select[contains(@id,'id_removeto')]/option[contains(@value, '0') and contains(@selected, '')]" "xpath_element" should exist
    And I press "Cancel"
    And the following config values are set as admin:
      | forumng_removeolddiscussions   | 31104000 |
      | forumng_withremoveddiscussions | -1       |
    When the following "activity" exist:
      | activity  | name              | intro                  | course |
      | forumng   | Test forum name 2 | Test forum description | C1     |

    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I follow "Test forum name 2"
    And I navigate to "Settings" in current page administration
    Then "//select[contains(@id,'id_removeafter')]/option[contains(@value, '31104000') and contains(@selected, '')]" "xpath_element" should exist
    And "//select[contains(@id,'id_removeto')]/option[contains(@value, '-1') and contains(@selected, '')]" "xpath_element" should exist
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    And I navigate to "Settings" in current page administration
    Then "//select[contains(@id,'id_removeafter')]/option[contains(@value, '62208000') and contains(@selected, '')]" "xpath_element" should exist
    And "//select[contains(@id,'id_removeto')]/option[contains(@value, '0') and contains(@selected, '')]" "xpath_element" should exist

  @javascript
  Scenario: Permalink post with new param.
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    And I add a discussion with the following data:
      | Subject | Discussion 1 |
      | Message | abc          |
    And I reply to post "1" with the following data:
      | Message | REPLY1 |
    And I reload the page
    And I click on "Expand all posts" "link"
    Then "//div[contains(@class, 'forumng-post')]//li[contains(@class, 'forumng-permalink')]//a[contains(@href, '&p=p')]" "xpath_element" should exist

  Scenario: Check user identity in list subscribers.
    Given the following "users" exist:
      | username | firstname | lastname | email            |
      | student3 | Student   | 3        | student3@asd.com |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student3 | C1     | student |
    And the following config values are set as admin:
      | showuseridentity | username,email |
    And I log in as "student3"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    And I should see "Subscribe to forum"
    And I press "Subscribe to forum"
    Then I log out

    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    And I press "Subscribe to forum"
    And I press "View subscribers"
    And I should see "Student 3"
    And I should see "Email address"
    And I should see "Username"
    And I should see "student3"
    And I should see "student3@asd.com"
    And I should see "student3"

  Scenario: View feed option on all participants.
    Given the following config values are set as admin:
      | enablerssfeeds         | 1 |
      | forumng_feedtype       | 2 |
      | forumng_enablerssfeeds | 1 |
    And the following "groups" exist:
      | name    | course | idnumber |
      | Group 1 | C1     | G1       |
      | Group 2 | C1     | G2       |
    And the following "group members" exist:
      | user     | group |
      | student1 | G1    |
    And the following "activities" exist:
      | activity | name             | course | section | groupmode |
      | forumng  | Test group forum | C1     | 1       | 2         |
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test group forum"
    And I add a discussion with the following data:
      | Subject | Discussion 1 |
      | Message | Discussion 1 |
    And I log out
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test group forum"
    And I click on "Atom" "link"

  @javascript
  Scenario: Check forum completion feature in web.
    Given the following "courses" exist:
      | fullname | shortname | format      | enablecompletion |
      | Course 2 | C2        | oustudyplan | 1                |
    And the following "course enrolments" exist:
      | user | course | role |
      | student1 | C2 | student |
    And the following "activities" exist:
      | activity | name                  | introduction           | course | idnumber | completion | completionview | completiondiscussionsenabled | completiondiscussions |
      | forumng  | Test forum completion | Test forum description | C2     | forumng2 | 2          | 1              | 1                            | 1                     |
    And I log in as "student1"
    And I am on "Course 2" course homepage
    Then I should see "0%"
    And I should not see "100%"
    And I follow "Test forum completion"
    And I am on "Course 2" course homepage
    # Check activity is not completed because we haven't see the second session.
    Then I should see "0%"
    And I should not see "100%"
    And I follow "Test forum completion"
    And I add a discussion with the following data:
      | Subject | Discussion 1 |
      | Message | Discussion 1 |
    When I am on "Course 2" course homepage
    Then I should see "100%"

  Scenario: Check history of post edits
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
    When I edit post "3" with the following data:
      | Message | Change reply2 EDIT |
    Then I should see "Change reply2 EDIT"
    And "History" "link" should exist
    Given I follow "History"
    Then I should see "REPLY2"
    And I should see "Change reply2 EDIT"

  @javascript
  Scenario: Check forum custom completion with wordcount.
    Given the following "courses" exist:
      | fullname | shortname | format      | enablecompletion |
      | Course 3 | C3        | oustudyplan | 1                |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C3     | student |
    And the following "activities" exist:
      | activity | name                          | introduction           | course | idnumber | completion | completionrepliesenabled | completionreplies | completionwordcountminenabled | completionwordcountmin | completionwordcountmaxenabled | completionwordcountmax |
      | forumng  | Test forum replies completion | Test forum description | C3     | forumng3 | 2          | 1                        | 2                 | 1                             | 1                      | 1                             | 5                      |
    And the following "activities" exist:
      | activity | name                              | introduction           | course | idnumber | completion | completiondiscussionsenabled | completiondiscussions | completionwordcountminenabled | completionwordcountmin | completionwordcountmaxenabled | completionwordcountmax |
      | forumng  | Test forum discussions completion | Test forum description | C3     | forumng4 | 2          | 1                            | 1                     | 1                             | 1                      | 1                             | 5                      |
    And the following "activities" exist:
      | activity | name                        | introduction           | course | idnumber | completion | completionpostsenabled | completionposts | completionwordcountminenabled | completionwordcountmin | completionwordcountmaxenabled | completionwordcountmax |
      | forumng  | Test forum posts completion | Test forum description | C3     | forumng5 | 2          | 1                      | 3               | 1                             | 1                      | 1                             | 5                      |
    And I log in as "student1"
    And I am on "Course 3" course homepage
    # Check custom completion replies with wordcount.
    And I follow "Test forum replies completion"
    And "Test forum replies completion" should have the "Make replies: 2" completion condition
    And "Test forum replies completion" should have the "Make discussion or reply with minimum word count: 1" completion condition
    And "Test forum replies completion" should have the "Make discussion or reply with maximum word count: 5" completion condition
    When I add a discussion with the following data:
      | Subject | Discussion 1 |
      | Message | Discussion 1 |
    And I reply to post "1" with the following data:
      | Message | REPLY1 |
    And I reply to post "1" with the following data:
      | Message | REPLY2 |
    And I reload the page
    Then the "Make replies: 2" completion condition of "Test forum replies completion" is displayed as "done"
    And the "Make discussion or reply with minimum word count: 1" completion condition of "Test forum replies completion" is displayed as "done"
    And the "Make discussion or reply with maximum word count: 5" completion condition of "Test forum replies completion" is displayed as "done"
    And I am on "Course 3" course homepage
   # Check custom completion discussion with wordcount.
    And I follow "Test forum discussions completion"
    And "Test forum discussions completion" should have the "Make discussions: 1" completion condition
    And "Test forum discussions completion" should have the "Make discussion or reply with minimum word count: 1" completion condition
    And "Test forum discussions completion" should have the "Make discussion or reply with maximum word count: 5" completion condition
    When I add a discussion with the following data:
      | Subject | Discussion 1 |
      | Message | Discussion 1 |
    Then the "Make discussions: 1" completion condition of "Test forum discussions completion" is displayed as "done"
    And the "Make discussion or reply with minimum word count: 1" completion condition of "Test forum discussions completion" is displayed as "done"
    And the "Make discussion or reply with maximum word count: 5" completion condition of "Test forum discussions completion" is displayed as "done"
    And I edit post "1" with the following data:
      | Message | Discussion does not meet the maximum word count |
    And I reload the page
    And "Test forum discussions completion" should have the "Make discussion or reply with maximum word count: 5" completion condition
    # Check custom completion posts with wordcount.
    And I am on "Course 3" course homepage
    And I follow "Test forum posts completion"
    And "Test forum posts completion" should have the "Make posts: 3" completion condition
    And "Test forum posts completion" should have the "Make discussion or reply with minimum word count: 1" completion condition
    And "Test forum posts completion" should have the "Make discussion or reply with maximum word count: 5" completion condition
    When I add a discussion with the following data:
      | Subject | Discussion 1 |
      | Message | Discussion 1 |
    And I reply to post "1" with the following data:
      | Message | REPLY1 |
    And I reply to post "1" with the following data:
      | Message | REPLY2 |
    And I reload the page
    Then the "Make posts: 3" completion condition of "Test forum posts completion" is displayed as "done"
    And the "Make discussion or reply with minimum word count: 1" completion condition of "Test forum posts completion" is displayed as "done"
    And the "Make discussion or reply with maximum word count: 5" completion condition of "Test forum posts completion" is displayed as "done"

  Scenario: Verify ForumNG uploads are not allowed.
    Given the following "activities" exist:
      | activity | course | idnumber | name    | introduction        | attachmentmaxbytes |
      | forumng  | C1     | F1       | Forum 2 | Forum 2 description | -1                 |
    And I am on the "Forum 2" "forumng activity" page logged in as "student1"
    And I press "Start a new discussion"
    And I should not see "Attachments"
    # Test normal forum should able to upload.
    And I am on the "Test forum name" "forumng activity" page logged in as "student1"
    And I press "Start a new discussion"
    And I should see "Attachments"
