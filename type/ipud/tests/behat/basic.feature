@mod @mod_forumng @ou @ou_vle @forumngtype_ipud
Feature: In-page discussions
  In order to use forum for In-page
  As a admin
  I can not post new discussion
  student can not see location column
  user can not reply to post level 2

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email            |
      | student1 | Student   | 1        | student1@asd.com |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I add a "ForumNG" to section "1" and I fill the form with:
      | Forum name | Test forum |
      | Forum type | ipud       |
      | ID number  | TEST12345  |
    And I create a new discussion:
      | shortname | forum     | group | username | subject                   | message                   | timestart | timeend | location |
      | C1        | TEST12345 |       | admin    | Subject for discussion D1 | Message for discussion D1 |           |         |          |
    And I follow "Test forum"
    And I should not see "Start a new discussion"
    And I should not see "Lock discussions"
    And I should not see "Move" in the "#forumng-features" "css_element"
    And I should see "Forum view"
    And I should not see "Started by"
    And I should see "Subject for discussion D1"
    And I should see "Message for discussion D1" in the ".forumng-subject" "css_element"
    And I should see "Unread"
    Then I follow "Link to forum view"
    And I should not see "Discussion options" in the "#forumng-features" "css_element"
    And I should not see "Lock" in the "#forumng-features" "css_element"
    And I should not see "Merge" in the "#forumng-features" "css_element"
    And I should not see "Merge" in the "#forumng-features" "css_element"
    And I should not see "Flag discussion" in the "#forumng-features" "css_element"
    And I should not see "Move" in the "#forumng-features" "css_element"
    And I log out

  Scenario: Test ipud forum with student can view original forum to test reply.
    # User can not view discussion
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test forum"
    Then I should not see "Start a new discussion"
    Then I should not see "Forum view"
    And I should see "Subject for discussion D1"
    And I should see "Message for discussion D1"
    And I should not see "Unread"
    And I log out
    # Give user permission to test reply
    Given I log in as "admin"
    And I set the following system permissions of "Student" role:
      | capability               | permission |
      | mod/forumng:viewrealipud | Allow      |
    And I log out
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test forum"
    Then I should not see "Start a new discussion"
    Then I should see "Forum view"
    And I should see "Unread"
    Then I follow "Link to forum view"
    And I should see "Subject for discussion D1"
    And I should see "Message for discussion D1"
    And "#forumg_customeditor" "css_element" should exist
    And I set the following fields to these values:
      | Message | Test bottom reply in the discussion |
    And I press "Post reply"
    And I should see "Test bottom reply in the discussion"
    Then I reply to post "2" with the following data:
      | Message | post level 2 |
    Then I should not see "Reply" in the ".forumng-p3" "css_element"
    Then I expand post "2"
    And I should not see "Permalink" in the ".forumng-commands" "css_element"
    And I click on "Edit" "text" in the ".forumng-commands" "css_element"
    And I should not see "Subject" in the ".mform" "css_element"
    And I should not see "Attachments" in the ".mform" "css_element"
    And I press "Cancel"
    Then I reply to post "2" with the following data:
      | Message | post level 2 another one |
    # Total reply should visible
    And I should see "2 Replies"
    And I log out

