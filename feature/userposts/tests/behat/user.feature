@mod @mod_forumng @ou @ou_vle @forumng_feature_userposts @javascript
Feature: Add forumng activity and test userposts filtering
  In order to easily evaluate user posts
  As a teacher
  I need to view posts filtered by rated and date posted

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | student1 | Student | 1 | student1@asd.com |
      | student2 | Student | 2 | student2@asd.com |
      | teacher1 | Teacher | 1 | teacher1@asd.com |
      | teacher2 | Teacher | 2 | teacher2@asd.com |
    When the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    Then the following "course enrolments" exist:
      | user | course | role |
      | student1 | C1 | student |
      | student2 | C1 | student |
      | teacher1 | C1 | teacher |
      | teacher2 | C1 | teacher |
    And the following "permission overrides" exist:
      | capability       | permission | role    | contextlevel | reference |
      | mod/forumng:rate | Allow      | student | Course       | C1        |


  Scenario: Check rating tab rated from and rated to filter
    Given I log in as "admin"
    When I am on site homepage
    Then I am on "Course 1" course homepage
    And I turn editing mode on
    And I add a "ForumNG" to section "1" and I fill the form with:
      | Forum name                  | Test forum name        |
      | Forum introduction          | Test forum description |
      | Allow posts to be rated     | Ratings (standard)     |
      | ratingscale[modgrade_type]  | point                  |
      | ratingscale[modgrade_point] | 10                     |
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    And I add a discussion with the following data:
      | Subject | Discussion 1 |
      | Message | abc          |
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    And I follow "Discussion 1"
    And I set the following fields to these values:
      | rating | 10 |
    And I log out
    And I amend the forumng rated posts to new rated date:
     | student1 |  Discussion 1 | 2014-12-02 |
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    And I follow "Discussion 1"
    And I reply to post "1" with the following data:
      | Change subject (optional) | Discussion 1 reply by student1                |
      | Message                   | This is reply text by student1 (Discussion 1) |
    And I follow "C1"
    And I follow "Test forum name"
    And I press "My participation"
    And I follow "Posts I rated"
    And I set the following fields to these values:
      | ratedstart[enabled]  | 1       |
      | ratedend[enabled]    | 1       |
      | ratedstart[month]    | January |
    And I press "Update"
    And I should see "No posts rated by Student 1"
    And I should not see "Discussion 1"
    And "//select[@name='rating']/option[@selected='selected'][normalize-space(text())='10']" "xpath_element" should not exist
    And I set the following fields to these values:
      | ratedstart[enabled] | 1    |
      | ratedend[enabled]   | 1    |
      | ratedstart[year]    | 2013 |
    And I press "Update"
    And I should see "Discussion 1"
    And "//select[@name='rating']/option[@selected='selected'][normalize-space(text())='10']" "xpath_element" should exist
    And I log out

    # Check all ratings options not visible when 'No ratings' enabled in Forum
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    And I navigate to "Edit settings" node in "ForumNG administration"
    And I set the field "id_enableratings" to "No ratings"
    And I press "Save and display"
    And I press "Participation by user"
    And I follow "Show all posts by Student 1"
    And I should not see "Rating"
    And I should not see "User posts"
    And I should not see "Posts user rated"


  Scenario: Check rating filtering tabs (create posts by a user and rate by another user)
    Given I log in as "admin"
    When I am on site homepage
    Then I am on "Course 1" course homepage
    And I turn editing mode on
    And I add a "ForumNG" to section "1" and I fill the form with:
      | Forum name                  | Test forum name        |
      | Forum introduction          | Test forum description |
      | Allow posts to be rated     | Ratings (standard)     |
      | ratingscale[modgrade_type]  | point                  |
      | ratingscale[modgrade_point] | 10                     |
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    And I add a discussion with the following data:
      | Subject | Discussion 1 |
      | Message | abc          |
    And I follow "ForumNG"
    And I add a discussion with the following data:
      | Subject | Discussion 2 |
      | Message | abcdefg      |
      | sticky  | 1            |
    And I log out

    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    And I follow "Discussion 1"
    And I reply to post "1" with the following data:
      | Change subject (optional) | Discussion 1 reply by student1                |
      | Message                   | This is reply text by student1 (Discussion 1) |
    And I follow "Test forum name"
    And I follow "Discussion 2"
    And I reply to post "1" with the following data:
      | Change subject (optional) | Discussion 2 reply by student1                |
      | Message                   | This is reply text by student1 (Discussion 2) |
    And I log out

    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    And I follow "Discussion 1"
    And I reply to post "1" with the following data:
      | Change subject (optional) | Discussion 1 reply by student2   |
      | Message | This is reply text by student2 (Discussion 1)      |
    And I am on homepage
    And I log out

    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    And I follow "Discussion 2"
    And I set the following fields to these values:
      | rating | 10 |
    And I follow "C1"
    And I follow "Test forum name"
    And I follow "Discussion 1"
    And I set the following fields to these values:
      | rating | 9 |
    And I log out

    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    And I press "Participation by user"
    And I follow "Show all posts by Student 1"
    And I set the following fields to these values:
      | rating | 7 |

    # Results.

    And I should see "User posts"
    And I should see "Discussion 1 reply by student1"
    And I should see "Discussion 2 reply by student1"
    And I set the following fields to these values:
      | ratedposts | 1 |
    And I press "Update"
    And I should see "Discussion 1 reply by student1"
    And I should not see "Discussion 2 reply by student1"
    And I follow "Posts user rated"
    And I should see "Discussion 1"
    And I should see "abc"
    And I should see "Discussion 2"
    And I should see "abcdefg"
    And "//span[@class='ratingaggregate'][normalize-space(text())='9']" "xpath_element" should exist
    And "//span[@class='ratingaggregate'][normalize-space(text())='10']" "xpath_element" should exist
    And I log out

    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    And I press "My participation"
    And I should see "My posts"
    And I should see "Discussion 1 reply by student1"
    And I should see "Discussion 2 reply by student1"
    And I set the following fields to these values:
      | ratedposts | 1 |
    And I press "Update"
    And I should see "Discussion 1 reply by student1"
    And I should not see "Discussion 2 reply by student1"
    And "//span[@class='ratingaggregate'][normalize-space(text())='7']" "xpath_element" should exist
    And I follow "Posts I rated"
    And I should see "Discussion 1"
    And I should see "abc"
    And I should see "Discussion 2"
    And I should see "abcdefg"
    And "//select[@name='rating']/option[@selected='selected'][normalize-space(text())='9']" "xpath_element" should exist
    And "//select[@name='rating']/option[@selected='selected'][normalize-space(text())='10']" "xpath_element" should exist


  Scenario: Add forumng replies and check display by user post
    Given I log in as "admin"
    And I am on site homepage
    When I am on "Course 1" course homepage
    Then I turn editing mode on
    And I add a "ForumNG" to section "1" and I fill the form with:
      | Forum name                  | Test forum name        |
      | Forum introduction          | Test forum description |
      | Allow posts to be rated     | Ratings (standard)     |
      | ratingscale[modgrade_type]  | point                  |
      | ratingscale[modgrade_point] | 10                     |

    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    And I add a discussion with the following data:
      | Subject | Discussion 1 |
      | Message | abc |
    And I follow "ForumNG"
    And I add a discussion with the following data:
      | Subject | Discussion 2 |
      | Message | abcdefg |
      | sticky | 1 |

    And I follow "ForumNG"
    And I add a discussion with the following data:
      | Subject | Discussion 3 |
      | Message | abcdefghijk |
    And I follow "ForumNG"
    And I follow "Discussion 1"
    And I reply to post "1" with the following data:
      | Change subject (optional) | Discussion 1 reply by admin  |
      | Message                   | HELLO                        |
    And I log out

    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    And I follow "Discussion 1"
    And I reply to post "1" with the following data:
      | Change subject (optional) | Discussion 1 reply by student1                |
      | Message                   | This is reply text by student1 (Discussion 1) |

    # View all posts for student1
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    And I press "My participation"
    And I should see "Posts rated by others"
    And I should see "Discussion 1 reply by student1"
    And I should see "This is reply text by student1 (Discussion 1)"
    And I should see "Average of ratings"

    # Now view rated posts only for student1
    And I set the following fields to these values:
      | ratedposts | 1 |
    And I press "Update"
    And I should not see "This is reply text by student1 (Discussion 1)"
    And I should not see "Average of ratings"
    And I should see "No posts by Student 1"
    And I log out

    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    And I follow "Discussion 1"
    And I reply to post "1" with the following data:
      | Change subject (optional) | Discussion 1 reply by student2   |
      | Message | This is reply text by student2 (Discussion 1)      |
    And I log out

    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    And I press "Participation by user"
    And I follow "Show all posts by Student 2"
    And I set the following fields to these values:
      | rating | 10 |
    And I log out

    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    And I follow "Discussion 1"
    And I reply to post "1" with the following data:
      | Change subject (optional) | Discussion 1 reply 2 by student2                |
      | Message                   | This is reply text 2 by student2 (Discussion 1) |
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    And I follow "Discussion 2"
    And I reply to post "1" with the following data:
      | Change subject (optional) | Discussion 2 reply by student2                 |
      | Message                   | This is reply text by student2 (Discussion 2)  |

    # View posts for student2
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    And I follow "ForumNG"
    And I press "My participation"
    And I should see "This is reply text by student2 (Discussion 1)"
    And I should see "This is reply text by student2 (Discussion 2)"
    And I should see "This is reply text 2 by student2 (Discussion 1)"

    And I set the following fields to these values:
      | ratedposts | 1 |
    And I press "Update"
    And I should see "This is reply text by student2 (Discussion 1)"
    And I should not see "This is reply text by student2 (Discussion 2)"
    And I should not see "This is reply text 2 by student2 (Discussion 1)"

    # Now check the date filter

    And I amend the forumng posts to new created date:
      |  Discussion 1 reply by student2     | 2014-12-02 |
      |  Discussion 1 reply 2 by student2   | 2015-01-03 |
      |  Discussion 2 reply by student2     | 2015-02-04 |
    And I press "Update"
    And I should see "This is reply text by student2 (Discussion 1)"
    And I should not see "This is reply text by student2 (Discussion 2)"
    And I should not see "This is reply text 2 by student2 (Discussion 1)"

    And I set the following fields to these values:
      | start[enabled] | 1 |
      | end[enabled]   | 1 |
    And I press "Update"
    And I should see "No posts by Student 2"
    And I should not see "This is reply text by student2 (Discussion 1)"
    And I should not see "This is reply text by student2 (Discussion 2)"
    And I should not see "This is reply text 2 by student2 (Discussion 1)"

    And I set the following fields to these values:
      | start[day]   | 1       |
      | start[month] | January |
      | start[year]  | 2015    |
    And I press "Update"
    And I should not see "This is reply text by student2 (Discussion 1)"
    And I should not see "This is reply text by student2 (Discussion 2)"
    And I should not see "This is reply text 2 by student2 (Discussion 1)"
    And I should see "No posts by Student 2"

    And I set the following fields to these values:
      | end[enabled]   | 0 |
    And I press "Update"
    And I should not see "This is reply text by student2 (Discussion 1)"
    And I should not see "This is reply text by student2 (Discussion 2)"
    And I should not see "This is reply text 2 by student2 (Discussion 1)"
    And I should see "No posts by Student 2"

    And I set the following fields to these values:
      | start[enabled] | 0 |
      | end[enabled]   | 1 |
    And I press "Update"
    And I should see "This is reply text by student2 (Discussion 1)"
    And I should not see "This is reply text by student2 (Discussion 2)"
    And I should not see "This is reply text 2 by student2 (Discussion 1)"

    And I set the following fields to these values:
      | ratedposts | 0 |
    And I press "Update"
    And I should see "This is reply text by student2 (Discussion 1)"
    And I should see "This is reply text by student2 (Discussion 2)"
    And I should see "This is reply text 2 by student2 (Discussion 1)"

    And I log out
