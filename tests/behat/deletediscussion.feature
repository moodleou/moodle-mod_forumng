@mod @mod_forumng @ou @ou_vle @forumng_markpostsallread @app
Feature: Add forumng activity and test delete discussion.

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email            |
      | student1 | Student   | 1        | student1@asd.com |
      | student2 | Student   | 2        | student2@asd.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | admin    | C1     | manager |
      | student1 | C1     | student |
      | student2 | C1     | student |
    And the following "activities" exist:
      | activity | name            | introduction           | course | idnumber |
      | forumng  | Test forum name | Test forum description | C1     | forumng1 |
    And the following config values are set as admin:
      | disabledfeatures | CoreBlockDelegate_AddonBlockRecentlyAccessedCourses,CoreBlockDelegate_AddonBlockRecentlyAccessedItems | tool_mobile |


  @javascript
  Scenario: Add discussions and test delete discussion
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    And I add a discussion with the following data:
      | Subject | discussion1 |
      | Message | abc         |
    And I log out
    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    And I add a discussion with the following data:
      | Subject | discussion2 |
      | Message | abc         |

    And I enter the app
    And I log in as "student1"
    And I should see "Course 1"
    And I press "Course 1" in the app
    And I press "Test forum name" in the app
    Then I should see "discussion1"
    Then I should see "discussion2"
    And I press "discussion2" in the app
    And I should not see "Delete discussion"
    When I press the page menu button in the app
    Then I should not see "Delete discussion"

    And I am on homepage
    And I enter the app
    And I log in as "student2"
    And I should see "Course 1"
    And I press "Course 1" in the app
    And I press "Test forum name" in the app
    And I press "discussion2" in the app
    And I should not see "Delete discussion"
    When I press the page menu button in the app
    Then I should see "Delete discussion"
    Then I press "Delete discussion" in the app
    And I press "Delete" in the app
    Then I should not see "discussion2"
    And I should see "discussion1"

    And I am on homepage
    And I enter the app
    And I log in as "admin"
    And I should see "Course 1"
    And I press "Course 1" in the app
    And I press "Test forum name" in the app
    Then I should see "discussion2"
    And I press "discussion2" in the app
    And ".deleted-discussion" "css_element" should exist
    And I should not see "Undelete discussion"
    When I press the page menu button in the app
    Then I should see "Undelete discussion"
    Then I press "Undelete discussion" in the app
    And I press "Undelete" in the app
    Then ".deleted-discussion" "css_element" should not exist

  @javascript @app_from3.9.5
  Scenario: Add discussions and test delete discussion new version
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    And I add a discussion with the following data:
      | Subject | discussion1 |
      | Message | abc         |
    And I log out
    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    And I add a discussion with the following data:
      | Subject | discussion2 |
      | Message | abc         |

    And I enter the app
    And I log in as "student1"
    And I should find "Course 1" in the app
    And I press "Course 1" in the app
    And I press "Test forum name" in the app
    Then I should find "discussion1" in the app
    Then I should find "discussion2" in the app
    And I press "discussion2" in the app
    And I should not find "Delete discussion" in the app
    When I click on "page-core-site-plugins-plugin core-context-menu ion-button" "css_element"
    Then I should not find "Delete discussion" in the app

    And I am on homepage
    And I enter the app
    And I log in as "student2"
    And I should find "Course 1" in the app
    And I press "Course 1" in the app
    And I press "Test forum name" in the app
    And I press "discussion2" in the app
    And I should not find "Delete discussion" in the app
    When I click on "page-core-site-plugins-plugin core-context-menu ion-button" "css_element"
    Then I should find "Delete discussion" in the app
    Then I press "Delete discussion" in the app
    And I press "Delete" in the app
    Then I should not find "discussion2" in the app
    And I should find "discussion1" in the app

    And I am on homepage
    And I enter the app
    And I log in as "admin"
    And I should find "Course 1" in the app
    And I press "Course 1" in the app
    And I press "Test forum name" in the app
    Then I should find "discussion2" in the app
    And I press "discussion2" in the app
    And ".deleted-discussion" "css_element" should exist
    And I should not find "Undelete discussion" in the app
    When I click on "page-core-site-plugins-plugin core-context-menu ion-button" "css_element"
    Then I should find "Undelete discussion" in the app
    Then I press "Undelete discussion" in the app
    And I press "Undelete" in the app
    Then ".deleted-discussion" "css_element" should not exist
