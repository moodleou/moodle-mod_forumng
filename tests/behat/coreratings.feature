@mod @mod_forumng @ou @ou_vle @forumng_rating
Feature: Add forumng activity and test basic ratings functionality
  In order to rate posts
  As a teacher
  I need to add forum activities and enable ratings

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacha1@asd.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | teacher |
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    When I add a "ForumNG" to section "1" and I fill the form with:
      | Forum name | Test forum name |
      | Forum introduction | Test forum description |
      | Allow posts to be rated | 2 |
      | ratingscale[modgrade_type] | Point |
      | ratingscale[modgrade_point] | 5 |
    And I follow "Test forum name"
    And I add a discussion with the following data:
      | Subject | Admin1 |
      | Message | Test |
    And I reply to post "1" with the following data:
      | Change subject (optional) | Admin2 |
      | Message | Test2 |
    # Forum chooses average of ratings by default for point scales.
    Then I should see "Average of ratings"
    And I am on homepage
    And I log out

  @javascript
  Scenario: Access forum as teacher and rate
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    And I follow "Admin1"
    When I reply to post "2" with the following data:
      | Change subject (optional) | Teacher1 post |
      | Message | Test 3 |
    Then I should see "Test2"
    And I should see "Test 3"
    And ".forumng-ratings-standard" "css_element" should exist
    And ".forumng-p1 .forumng-ratings-standard select" "css_element" should exist
    And ".forumng-p2 .forumng-ratings-standard select" "css_element" should exist
    And ".forumng-p3 .forumng-ratings-standard select" "css_element" should not exist
    Given I set the field "rating" to "3"
    Then I should see "Average of ratings: 3 (1)"
    # Re-access, testing rating works when posts collapsed.
    Given I click on "#forumng-arrowback a" "css_element"
    And I follow "Admin1"
    When I set the field "rating" to "5"
    Then I should see "Average of ratings: 5 (1)"
    Given I expand post "2"
    When I set the field "rating" to "1"
    Then I should see "Average of ratings: 1 (1)"
    # Check rating output on view all posts page.
    Given I follow "Test forum name"
    And I press "Participation by user"
    When I follow "Show all posts by Teacher 1"
    Then I should see "Average of ratings: "
    # Switch on grading and check aggregates.
    Given I am on "Course 1" course homepage
    And I log out
    And I wait until the page is ready
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    And I navigate to "Edit settings" node in "ForumNG administration"
    And I set the field "Grade" to "Count of ratings"
    And I press "Save and display"
    When I follow "Admin1"
    Then I should see "Count of ratings:"
    And "(1)" "link" should exist
    # Switch on rating time limit to.
    Given I follow "Test forum name"
    And I navigate to "Edit settings" node in "ForumNG administration"
    And I set the field "id_ratingfrom_enabled" to "1"
    And I set the field "id_ratinguntil_enabled" to "1"
    And I set the field "id_ratingfrom_year" to "2010"
    And I set the field "id_ratinguntil_year" to "2010"
    And I press "Save and display"
    When I follow "Admin1"
    And I expand post "0"
    Then ".forumng-p2 .forumng-ratings-standard" "css_element" should exist
    And ".forumng-p2 .forumng-ratings-standard select" "css_element" should not exist
