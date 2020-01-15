@mod @mod_forumng @ou @ou_vle @forumng_basic
Feature: To Create a ForumNG on Learn2

  Background:
    Given the following "courses" exist:
      | shortname | fullname |
      | TEST1     | Course1  |
    Given the following config values are set as admin:
      | enablerssfeeds   | 1 |
      | forumng_feedtype | 2 |
    Given the following "users" exist:
      | username | firstname | lastname |
      | student1 | Student   | 1        |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | TEST1  | student |

  @javascript @_file_upload
  Scenario: Verify "Split"
    Given I log in as "admin"
    Given the following "activities" exist:
      | activity | course | idnumber | name | introduction           |
      | forumng  | TEST1  | F1       | F.wc | Test forum description |
    And I am on "Course1" course homepage
    And I follow "F.wc"
    And I add a discussion with the following data:
      | Subject | Split                |
      | Message | Test Split on Learn2 |
    And I follow "Reply"
    And I reply to post "1" with the following data:
      | Message    | Text r1                              |
      | Attachment | mod/forumng/tests/fixtures/Reply.txt |
    And I reply to post "1" with the following data:
      | subject | MOD01-r2 |
      | Message | Text r2  |
    And I follow "Split"
    And I should see "A new discussion will be created (shown below)."
    And I press "Cancel"
    And I expand post "2"
    And I follow "Split"
    And I set the field "subject" to "MOD01-r1 "
    And I press "Split post as new discussion"
    Then I should see "MOD01-r1"
    Then I should not see "MOD01-r2"
    And following "Reply.txt" should download between "372" and "375" bytes
    And I follow "F.wc"
    And I follow "Split"
    And I should not see "r1"

  @javascript
  Scenario: Verify "Jump to Parent"
    Given I log in as "admin"
    Given the following "activities" exist:
      | activity | course | idnumber | name | introduction           |
      | forumng  | TEST1  | F1       | F.wc | Test forum description |
    And I am on "Course1" course homepage
    And I follow "F.wc"
    And I add a discussion with the following data:
      | Subject | DSM01                                                                                                                                                           |
      | Message | Go to F.WC (just for variety) and start a new discussion. Paste a reasonable chunk of text (say, this test step) into the post and use subject 'DSM01'  Post it |
    And I reply to post "1" with the following data:
      | Change subject (optional) | r1                                                                       |
      | Message                   | Reply to it with subject 'r1' and again paste a reasonable chunk of text |
    And I reply to post "1" with the following data:
      | Change subject (optional) | r2                                                                       |
      | Message                   | Reply to it with subject 'r2' and again paste a reasonable chunk of text |
    And I reply to post "2" with the following data:
      | Change subject (optional) | r3                                                                       |
      | Message                   | Reply to it with subject 'r3' and again paste a reasonable chunk of text |
    And I reload the page
    And I expand post "4"
    And I follow "Parent"
    Then the focused element is ".forumng-p2 a.forumng-parent" "css_element"

  Scenario: Verify "Save as draft"
    Given I log in as "admin"
    And I am on "Course1" course homepage with editing mode on
    And I add a "ForumNG" to section "1"
    And I set the field "Forum" to "F.wc"
    And I press "Save and display"
    And I press "Start a new discussion"
    And I set the field "Subject" to "Save as Draft"
    And I set the field "Message" to "Test Save as Draft on Learn2"
    And I press "Save as draft"
    Then I should see "A draft version of this post"
    And I press "Cancel"
    And "Save as Draft" "link" should exist
    And I follow "Save as Draft"
    And I press "Post discussion"
    Then I should see "Save as Draft"

  @javascript @_file_upload
  Scenario: Verify "Attachment"
    Given I log in as "admin"
    And I am on "Course1" course homepage with editing mode on
    And I add a "ForumNG" to section "1"
    And I set the field "Forum" to "F.wc"
    And I press "Save and display"
    And I press "Start a new discussion"
    And I set the field "Subject" to "Attachment"
    And I set the field "Message" to "Test Attachment on Learn2"
    And I upload "mod/forumng/tests/fixtures/Attach.txt" file to "Attachments" filemanager
    And I press "Post discussion"
    And I follow "Attach.txt"
    And following "Attach.txt" should download between "372" and "375" bytes
    And I follow "Reply"
    And I reply to post "1" with the following data:
      | Message    | Reply to to check attachment         |
      | Attachment | mod/forumng/tests/fixtures/Reply.txt |
    And following "Reply.txt" should download between "372" and "375" bytes

  @javascript @_file_upload
  Scenario: Verify "Save as draft Attachment"
    Given I log in as "admin"
    And I am on "Course1" course homepage with editing mode on
    And I add a "ForumNG" to section "1"
    And I set the field "Forum" to "F.wc"
    And I press "Save and display"
    And I press "Start a new discussion"
    And I set the field "Subject" to "Save as draft"
    And I set the field "Message" to "Test Attachment is saved in draft on Learn2"
    And I upload "mod/forumng/tests/fixtures/Attach.txt" file to "Attachments" filemanager
    And I press "Save as draft"
    #checking if attachment is saved in the discussion
    Then I should see "Attach.txt"

  Scenario: Verify "Discussion options"
    Given I log in as "admin"
    Given the following "activities" exist:
      | activity | course | idnumber | name | introduction           |
      | forumng  | TEST1  | F1       | F.wc | Test forum description |
    And I am on "Course1" course homepage
    And I follow "F.wc"
    And I add a discussion with the following data:
      | Subject | Test Forum 1         |
      | Message | Test Forum on Learn2 |
    And I press "Discussion options"
    #changing the display options to the following
    When I set the following fields to these values:
      | timestart[enabled] | 1                    |
      | timestart[day]     | ## -2 days ## j ##   |
      | timestart[month]   | ## -2 days ## n ##   |
      | timestart[year]    | ## -2 days ## Y ##   |
      | timeend[enabled]   | 1                    |
      | timeend[day]       | ## yesterday ## j ## |
      | timeend[month]     | ## yesterday ## n ## |
      | timeend[year]      | ## yesterday ## Y ## |
    And I press "Save changes"
    And I follow "F.wc"
    And I add a discussion with the following data:
      | Subject | Test Forum 2         |
      | Message | Test Forum on Learn2 |
    And I press "Discussion options"
    #changing the display options to the following
    When I set the following fields to these values:
      | timestart[enabled] | 1                     |
      | timestart[day]     | ## yesterday  ## j ## |
      | timestart[month]   | ## yesterday  ## n ## |
      | timestart[year]    | ## yesterday  ## Y ## |
      | timeend[enabled]   | 1                     |
      | timeend[day]       | ## tomorrow ## j ##   |
      | timeend[month]     | ## tomorrow ## n ##   |
      | timeend[year]      | ## tomorrow ## Y ##   |
    And I press "Save changes"
    And I follow "F.wc"
    And I add a discussion with the following data:
      | Subject | Test Forum 3         |
      | Message | Test Forum on Learn2 |
    And I press "Discussion options"
    #changing the display options to the following
    When I set the following fields to these values:
      | timestart[enabled] | 1                   |
      | timestart[day]     | ## tomorrow ## j ## |
      | timestart[month]   | ## tomorrow ## n ## |
      | timestart[year]    | ## tomorrow ## Y ## |
      | timeend[enabled]   | 1                   |
      | timeend[day]       | ## +2 days ## j ##  |
      | timeend[month]     | ## +2 days ## n ##  |
      | timeend[year]      | ## +2 days ## Y ##  |
    And I press "Save changes"
    And I log out
    And I log in as "student1"
    And I am on "Course1" course homepage
    And I follow "F.wc"
    And I should not see "Test Forum 1"
    And I should see "Test Forum 2"
    And I should not see "Test Forum 3"

  Scenario: verify " Sticky discussions"
        Given I log in as "admin"
    Given the following "activities" exist:
      | activity | course | idnumber | name | introduction           |
      | forumng  | TEST1  | F1       | F.wc | Test forum description |
    And I am on "Course1" course homepage
    And I follow "F.wc"
    And I add a discussion with the following data:
      | Subject | Test Forum 1         |
      | Message | Test Forum on Learn2 |
    And I follow "F.wc"
    And I add a discussion with the following data:
      | Subject | Test Forum 2         |
      | Message | Test Forum on Learn2 |
    And I follow "F.wc"
    And I add a discussion with the following data:
      | Subject | Test Forum 3         |
      | Message | Test Forum on Learn2 |
    And I press "Discussion options"
    And I set the field "Sticky discussion?" to "Discussion stays on top of list"
    And I press "Save changes"
    And I follow "F.wc"
    Then "Test Forum 3" "link" should appear before "Test Forum 1" "link"
    And I follow "Discussion"
    Then "Test Forum 3" "link" should appear before "Test Forum 1" "link"




