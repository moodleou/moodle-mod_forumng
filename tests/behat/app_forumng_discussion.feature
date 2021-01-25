@mod @mod_forumng @ou @ou_vle @forumng_disscussion @app @javascript
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
      | forumng  | Test forum anon 2     | Test ForumNG 4 description | C1     | forumng4 | 2           |


  Scenario: Add discussions and check sticky and block and time limit
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    Then ".mma-forumng-discussion-title" "css_element" should not exist
    And I add a discussion with the following data:
      | Subject | Discussion 1 |
      | Message | abc          |
    And I follow "Test forum name"
    And I add a discussion with the following data:
      | Subject | Discussion 2 |
      | Message | abcdefg      |
      | sticky  | 1            |
    Then I press "Lock"
    And I set the following fields to these values:
      | Message | A lock post |
    And I press "Lock discussion"
    And I press "Discussion options"
    #changing the display options to the following
    When I set the following fields to these values:
      | timestart[enabled] | 1                    |
      | timestart[day]     | ## yesterday ## j ## |
      | timestart[month]   | ## yesterday ## n ## |
      | timestart[year]    | ## yesterday ## Y ## |
      | timeend[enabled]   | 1                    |
      | timeend[day]       | ## yesterday ## j ## |
      | timeend[month]     | ## yesterday ## n ## |
      | timeend[year]      | ## yesterday ## Y ## |
    And I press "Save changes"
    And I follow "Test forum name"
    And I add a discussion with the following data:
      | Subject | Discussion 3 |
      | Message | abcdefghijk  |
    And I am on "Course 1" course homepage
    And I follow "Test forum discussion"
    And I add a discussion with the following data:
      | Subject | a discussion |
      | Message | test message |
    And I reply to post "1" with the following data:
      | Message | REPLY1 |
    And I reply to post "1" with the following data:
      | Message | REPLY2 |
    And I reply to post "1" with the following data:
      | Message | REPLY3 |
    And I click on ".arrow_text" "css_element" in the "#forumng-arrowback" "css_element"
    And I add a discussion with the following data:
      | Subject | b discussion |
      | Message | test message |
      | sticky  | 1            |
    And I reply to post "1" with the following data:
      | Message | REPLY1 |
    And I reply to post "1" with the following data:
      | Message | REPLY2 |
    And I click on ".arrow_text" "css_element" in the "#forumng-arrowback" "css_element"
    And I add a discussion with the following data:
      | Subject | c discussion |
      | Message | test message |
    And I reply to post "1" with the following data:
      | Message | REPLY1 |
    And I reply to post "1" with the following data:
      | Message | REPLY2 |
    And I reply to post "1" with the following data:
      | Message | REPLY3 |
    And I reply to post "1" with the following data:
      | Message | REPLY4 |
    And I enter the app
    And I log in as "teacher1"
    And I press "Course 1" near "Recently accessed courses" in the app
    And I press "Test forum name" in the app
    And I should see "Sort discussions"
    And "//div[@class='mma-forumng-discussion-icons']/span[contains(@class, 'forumng-locked forumng-timeout forumng-sticky')]/span/img[contains(@alt, 'read-only')]" "xpath_element" should exist
    And "//div[@class='mma-forumng-discussion-icons']/span[contains(@class, 'forumng-locked forumng-timeout forumng-sticky')]/span/img[contains(@alt, '(time limit)')]" "xpath_element" should exist
    And "//div[@class='mma-forumng-discussion-icons']/span[contains(@class, 'forumng-locked forumng-timeout forumng-sticky')]/span/img[contains(@alt, 'top of list')]" "xpath_element" should exist
    And I enter the app
    And I log in as "student1"
    And I press "Course 1" near "Recently accessed courses" in the app
    And I press "Test forum discussion" in the app
    And I should see "Sort discussions"
    # Discussions has sticky always in the top list.
    And I should see "b discussion" in the "//ion-list[contains(@class,'mma-forumng-discussion-list')]/ion-item[contains(@class, 'mma-forumng-discussion-short')][1]" "xpath_element"
    And I should see "c discussion" in the "//ion-list[contains(@class,'mma-forumng-discussion-list')]/ion-item[contains(@class, 'mma-forumng-discussion-short')][2]" "xpath_element"
    And I should see "a discussion" in the "//ion-list[contains(@class,'mma-forumng-discussion-list')]/ion-item[contains(@class, 'mma-forumng-discussion-short')][3]" "xpath_element"
    When I click on ".mma-forumng-discussion-sort" "css_element"
    And I should see "Sort by title (A to Z)"
    And I should see "Sort by most unread posts"
    And I should see "Sort by date of last post"
    And I follow "Sort by title (A to Z)"
    And I should see "b discussion" in the "//ion-list[contains(@class,'mma-forumng-discussion-list')]/ion-item[contains(@class, 'mma-forumng-discussion-short')][1]" "xpath_element"
    And I should see "a discussion" in the "//ion-list[contains(@class,'mma-forumng-discussion-list')]/ion-item[contains(@class, 'mma-forumng-discussion-short')][2]" "xpath_element"
    And I should see "c discussion" in the "//ion-list[contains(@class,'mma-forumng-discussion-list')]/ion-item[contains(@class, 'mma-forumng-discussion-short')][3]" "xpath_element"
    When I click on ".mma-forumng-discussion-sort" "css_element"
    And I follow "Sort by most unread posts"
    And I should see "b discussion" in the "//ion-list[contains(@class,'mma-forumng-discussion-list')]/ion-item[contains(@class, 'mma-forumng-discussion-short')][1]" "xpath_element"
    And I should see "c discussion" in the "//ion-list[contains(@class,'mma-forumng-discussion-list')]/ion-item[contains(@class, 'mma-forumng-discussion-short')][2]" "xpath_element"
    And I should see "a discussion" in the "//ion-list[contains(@class,'mma-forumng-discussion-list')]/ion-item[contains(@class, 'mma-forumng-discussion-short')][3]" "xpath_element"

  Scenario: Display post basic and lock discussion
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    And I add a discussion with the following data:
      | Subject | Discussion 1 |
      | Message | abc          |
    And I reply to post "1" with the following data:
      | Message | REPLY1 |
    And I reply to post "1" with the following data:
      | Message | REPLY2 |
    And I reply to post "1" with the following data:
      | Message | REPLY3 |
    And I follow "Test forum name"
    And I enter the app
    And I log in as "teacher1"
    And I press "Course 1" near "Recently accessed courses" in the app
    And I press "Test forum name" in the app
    And I click on "//ion-list[contains(@class,'mma-forumng-discussion-list')]/ion-item[contains(@class, 'mma-forumng-discussion-short')][1]" "xpath_element"
    Then I should see "Discussion 1"
    And I should see "abc"
    And I should see "EXPAND ALL"
    Then I click on "page-core-site-plugins-plugin core-context-menu button" "css_element"
    When I press "Lock" in the app
    And I set the field "Message" to "Test lock message" in the app
    When I click on ".mma-forumng-lock-button" "css_element"
    Then I should see "This discussion is now closed"

  Scenario: Display post when moderators post anonymously
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test forum anon"
    And I add a discussion with the following data:
      | Subject | Discussion 1 |
      | Message | abc          |
    And I reply to post "1" with the following data:
      | Change subject (optional) | Reply 1       |
      | Message                   | Message       |
      | asmoderator               | Standard Post |
    And I reply to post "1" with the following data:
      | Change subject (optional) | Reply 2                                     |
      | Message                   | Message                                     |
      | asmoderator               | Identify self as moderator (name displayed) |
    And I reply to post "1" with the following data:
      | Change subject (optional) | Reply 2                                                |
      | Message                   | Message                                                |
      | asmoderator               | Identify self as moderator (name hidden from students) |
    And I follow "Test forum anon"
    And I enter the app
    And I log in as "student1"
    And I press "Course 1" near "Recently accessed courses" in the app
    And I press "Test forum anon" in the app
    And I click on "//ion-list[contains(@class,'mma-forumng-discussion-list')]/ion-item[contains(@class, 'mma-forumng-discussion-short')][1]" "xpath_element"
    And I should see "Discussion 1"
    And I should see "Teacher 1" in the "//ion-list[contains(@class,'mma-forumng-posts-list')]/ion-item[contains(@class, 'mma-forumng-post-reply')][1]" "xpath_element"
    And I should see "Teacher 1" in the "//ion-list[contains(@class,'mma-forumng-posts-list')]/ion-item[contains(@class, 'mma-forumng-post-reply')][2]" "xpath_element"
    And I should see "Moderator" in the "//ion-list[contains(@class,'mma-forumng-posts-list')]/ion-item[contains(@class, 'mma-forumng-post-reply')][2]" "xpath_element"
    Then I should see "Moderator" in the "//ion-list[contains(@class,'mma-forumng-posts-list')]/ion-item[contains(@class, 'mma-forumng-post-reply')][3]" "xpath_element"

  Scenario: Display post when non-moderators post anonymously
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test forum anon 2"
    And I add a discussion with the following data:
      | Subject | Discussion 1 |
      | Message | abc          |
    And I reply to post "1" with the following data:
      | Change subject (optional) | Reply 1       |
      | Message                   | Message       |
      | asmoderator               | Standard Post |
    And I reply to post "1" with the following data:
      | Change subject (optional) | Reply 2                                     |
      | Message                   | Message                                     |
      | asmoderator               | Identify self as moderator (name displayed) |
    And I reply to post "1" with the following data:
      | Change subject (optional) | Reply 2                                                |
      | Message                   | Message                                                |
      | asmoderator               | Identify self as moderator (name hidden from students) |
    And I follow "Test forum anon 2"
    And I enter the app
    And I log in as "student1"
    And I press "Course 1" near "Recently accessed courses" in the app
    And I press "Test forum anon 2" in the app
    And I should see "Posts to this forum will be identity protected - individuals' names will not be displayed."
    And I click on "//ion-list[contains(@class,'mma-forumng-discussion-list')]/ion-item[contains(@class, 'mma-forumng-discussion-short')][1]" "xpath_element"
    And I should see "Discussion 1"
    And I should see "Moderator" in the "//ion-list[contains(@class,'mma-forumng-posts-list')]/ion-item[contains(@class, 'mma-forumng-post-reply')][2]" "xpath_element"
    Then I should see "Moderator" in the "//ion-list[contains(@class,'mma-forumng-posts-list')]/ion-item[contains(@class, 'mma-forumng-post-reply')][3]" "xpath_element"

  @javascript
  Scenario: Expand collapse post and unread post
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test forum discussion"
    And I add a discussion with the following data:
      | Subject | a discussion |
      | Message | test message |
    And I log out
    Then I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test forum discussion"
    And I follow "a discussion"
    And I reply to post "1" with the following data:
      | Message | Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum. |
    And I log out
    Then I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test forum discussion"
    And I follow "a discussion"
    And I reply to post "1" with the following data:
      | Message | Teacher Lorem ipsum dolor sit amet |
    And I reply to post "1" with the following data:
      | Message | Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum. |
    And I enter the app
    And I log in as "student1"
    And I press "Course 1" near "Recently accessed courses" in the app
    And I press "Test forum discussion" in the app
    And I click on "//ion-list[contains(@class,'mma-forumng-discussion-list')]/ion-item[contains(@class, 'mma-forumng-discussion-short')][1]" "xpath_element"
    # Before click Expand all, I should see short message with (...) .
    And I should see "..."
    And I should see "EXPAND ALL POSTS"
    And I press "EXPAND ALL POSTS"
    And I should not see "EXPAND ALL POSTS"
    And I should see "COLLAPSE ALL POSTS"
    Then I click on "page-core-site-plugins-plugin core-context-menu button" "css_element"
    And I should see "Open in browser"
    And I should see "Refresh"

  @_file_upload
  Scenario: Add forum downloads to manage downloads page
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test forum discussion"
    And I add a discussion with the following data:
      | Subject | a discussion |
      | Message | test message |
    And I reply to post "1" with the following data:
      | Message    | REPLY1                               |
      | Attachment | mod/forumng/tests/fixtures/Reply.txt |
    And I enter the app
    And I log in as "student1"
    And I press "Course 1" near "Recently accessed courses" in the app
    And I press "Test forum discussion" in the app
    And I click on "//ion-list[contains(@class,'mma-forumng-discussion-list')]/ion-item[contains(@class, 'mma-forumng-discussion-short')][1]" "xpath_element"
    And I press "Reply.txt" in the app
    And I close the browser tab opened by the app
    And I click on "page-core-site-plugins-plugin button.back-button" "css_element"
    And I click on "page-core-site-plugins-module-index core-context-menu button" "css_element"
    Then I should see "Clear storage 6.46 KB"
