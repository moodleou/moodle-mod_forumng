@mod @mod_forumng @ou @ou_vle @forumng_userparticipation
Feature: Add forumng activity and test user ratings
  In order to effectively teach
  As a teacher
  I need to easily view students forum post ratings

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email            |
      | teacher1 | Teacher   | 1        | teacher1@asd.com |
      | student1 | Student   | 1        | student1@asd.com |
      | student2 | Student   | 2        | student2@asd.com |
    When the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    Then the following "course enrolments" exist:
      | user     | course | role    |
      | teacher1 | C1     | teacher |
      | student1 | C1     | student |
      | student2 | C1     | student |
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I add a "ForumNG" to section "1" and I fill the form with:
      | Forum name                  | Test forum name        |
      | Forum introduction          | Test forum description |
      | Allow posts to be rated     | 2                      |
      | ratingscale[modgrade_type]  | Point                  |
      | ratingscale[modgrade_point] | 10                     |
      | Grade                       | No grade               |
    And I follow "Test forum name"
    And I add a discussion with the following data:
      | Subject | Admin1 |
      | Message | Test   |
    And I reply to post "1" with the following data:
      | Change subject (optional) | Admin2 |
      | Message                   | Test2  |
    And I follow "C1"
    And I log out

  Scenario: Access forum as a student, create a discussion and reply to a post and then rate as a teacher

    Given I log in as "student1"
    When I am on "Course 1" course homepage
    Then I follow "Test forum name"
    And I follow "Admin1"
    And I reply to post "2" with the following data:
      | Change subject (optional) | Student 1 |
      | Message                   | Test3   |
    And I should see "Test3"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    And I add a discussion with the following data:
      | Subject | Disc2   |
      | Message | Test2   |
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    And I follow "Admin1"
    And I reply to post "1" with the following data:
      | Change subject (optional) | Student 1                     |
      | Message                   | Reply by Student 1   |
    And I log out

    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    And I press "Participation by user"
    And I should see "0.0" in the "//div/table//tr[@id='mod-forumng-participation_r0']/td[@class='cell c4']/div[text()]" "xpath_element"
    And I follow "Show all posts by Student 1"
    And I set the following fields to these values:
      | rating | 3 |
    And I press "Rate"
    And I follow "User participation"
    And I should see "3.0" in the "//div/table//tr[@id='mod-forumng-participation_r0']/td[@class='cell c4']/div[text()]" "xpath_element"
    And I follow "C1"
    And I log out

    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    And "My participation" "button" should exist
    And "Participation by user" "button" should not exist

    And I follow "Disc2"
    And I reply to post "1" with the following data:
      | Change subject (optional) | Student 1                       |
      | Message                   | Reply to Disc2 by Student 1   |
    And I should see "Reply to Disc2 by Student 1"
    And I log out

    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    And I follow "Disc2"
    And I set the following fields to these values:
      | rating | 4 |
    And I press "Rate"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    And I press "Participation by user"
    And I should see "3.5" in the "//div/table//tr[@id='mod-forumng-participation_r0']/td[@class='cell c4']/div[text()]" "xpath_element"

    # Now change a post created date
    And I amend the forumng posts to new created date:
      |  Disc2     | 2014-12-02 |
    And I press "Update"
    And I set the following fields to these values:
      | start[enabled] | 1 |
      | end[enabled]   | 1 |
    And I press "Update"
    And I should see "3.0" in the "//div/table//tr[@id='mod-forumng-participation_r0']/td[@class='cell c4']/div[text()]" "xpath_element"

    And I set the following fields to these values:
      | start[day]   | 1        |
      | start[month] | January  |
      | start[year]  | 2014     |
      | end[day]     | 25       |
      | end[month]   | December |
      | end[year]    | 2014     |
    And I press "Update"
    And I should see "4.0" in the "//div/table//tr[@id='mod-forumng-participation_r0']/td[@class='cell c4']/div[text()]" "xpath_element"
    And I log out

    # View with different grade setting
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    And I follow "Edit settings"
    And I set the following fields to these values:
     | Grade | Teacher grades students |
    And I press "Save and display"
    And I log out

    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    And I press "Participation by user"
    And I should see "Grade"
    And I should see "3.5" in the "//div/table//tr[@id='mod-forumng-participation_r0']/td[@class='cell c5']/div[text()]" "xpath_element"
    And I log out
