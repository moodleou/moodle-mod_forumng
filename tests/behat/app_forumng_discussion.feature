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
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
      | teacher1 | C1     | teacher |
    And the following "activities" exist:
      | activity | name                  | introduction           | course | idnumber |
      | forumng  | Test forum name       | Test forum description | C1     | forumng1 |
      | forumng  | Test forum discussion | Test forum description | C1     | forumng2 |

  Scenario: Add discussions and check sticky and block and time limit
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    And I add a discussion with the following data:
      | Subject | Discussion 1 |
      | Message | abc |
    And I follow "Test forum name"
    And I add a discussion with the following data:
      | Subject | Discussion 2 |
      | Message | abcdefg |
      | sticky | 1 |
    Then I press "Lock"
    And I set the following fields to these values:
      | Message | A lock post |
    And I press "Lock discussion"
    And I press "Discussion options"
    #changing the display options to the following
    When I set the following fields to these values:
      | timestart[enabled] | 1                   |
      | timestart[day]     | ## yesterday ## j ## |
      | timestart[month]   | ## yesterday ## n ## |
      | timestart[year]    | ## yesterday ## Y ## |
      | timeend[enabled]   | 1                   |
      | timeend[day]       | ## yesterday ## j ## |
      | timeend[month]     | ## yesterday ## n ## |
      | timeend[year]      | ## yesterday ## Y ## |
    And I press "Save changes"
    And I follow "Test forum name"
    And I add a discussion with the following data:
      | Subject | Discussion 3 |
      | Message | abcdefghijk |
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
      | sticky | 1 |
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
    And "//div[@class='mma-forumng-discussion-icons']/span[contains(@class, 'forumng-locked forumng-timeout forumng-sticky')]/span/img[contains(@alt, 'read-only')]" "xpath_element" should exist
    And "//div[@class='mma-forumng-discussion-icons']/span[contains(@class, 'forumng-locked forumng-timeout forumng-sticky')]/span/img[contains(@alt, '(time limit)')]" "xpath_element" should exist
    And "//div[@class='mma-forumng-discussion-icons']/span[contains(@class, 'forumng-locked forumng-timeout forumng-sticky')]/span/img[contains(@alt, 'top of list')]" "xpath_element" should exist
    And I enter the app
    And I log in as "student1"
    And I press "Course 1" near "Recently accessed courses" in the app
    And I press "Test forum discussion" in the app
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
