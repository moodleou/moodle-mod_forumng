@mod @mod_forumng @ou @ou_vle @forumngfeature_export
Feature: Export discussions using portfolio
  In order to make an export of a discussion
  As a teacher
  I need to use the export feature

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "groups" exist:
      | name    | course | idnumber |
      | Group 1 | C1     | G1       |
      | Group 2 | C1     | G2       |
    And the following "users" exist:
      | username | firstname | lastname | email            |
      | student1 | Student   | 1        | student1@asd.com |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
    And the following "activities" exist:
      | activity | name        | introduction           | course | idnumber |
      | forumng  | Test forum  | Test forum description | C1     | forumng1 |

  Scenario: Button not active without portfolio enabled
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    When I follow "Test forum"
    Then "div.forumngfeature_export" "css_element" should not exist

  Scenario: Button active when portfolio enabled
    Given the following config values are set as admin:
      | enableportfolios | 1 |
    And I log in as "admin"
    And I am on site homepage
    And I navigate to "Manage portfolios" node in "Site administration > Plugins > Portfolios"
    And I set the field with xpath "//form[@id='applytodownload']/select" to "Enabled and visible"
    And I click on "form#applytodownload input[type='submit']" "css_element"
    And I press "Save"
    And I am on "Course 1" course homepage
    When I follow "Test forum"
    Then "div.forumngfeature_export" "css_element" should exist

  Scenario: Button not active when cannot view discussions
    Given the following config values are set as admin:
      | enableportfolios | 1 |
    And I log in as "admin"
    And I am on site homepage
    And I navigate to "Manage portfolios" node in "Site administration > Plugins > Portfolios"
    And I set the field with xpath "//form[@id='applytodownload']/select" to "Enabled and visible"
    And I click on "form#applytodownload input[type='submit']" "css_element"
    And I press "Save"
    And I am on "Course 1" course homepage
    And I navigate to "Permissions" node in "Course administration > Users"
    And I override the system permissions of "Student" role with:
      | mod/forumng:viewdiscussion | Prevent |
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    When I follow "Test forum"
    Then "div.forumngfeature_export" "css_element" should not exist

  Scenario: Try and get as far as we can exporting discussions
    Given the following config values are set as admin:
      | enableportfolios | 1 |
    And I log in as "admin"
    And I am on site homepage
    And I navigate to "Manage portfolios" node in "Site administration > Plugins > Portfolios"
    And I set the field with xpath "//form[@id='applytodownload']/select" to "Enabled and visible"
    And I click on "form#applytodownload input[type='submit']" "css_element"
    And I press "Save"
    And I am on "Course 1" course homepage
    And I follow "Test forum"
    And I add a discussion with the following data:
      | Subject | Ms1 |
      | Message | abc |
    And I follow "Test forum"
    And I add a discussion with the following data:
      | Subject | Ms2 |
      | Message | abc |
    And I follow "Test forum"
    When I press "Export"
    Then I should see "Do you want to include all discussions listed on this page, or only selected discussions"
    Given I press "All discussions shown"
    When I follow "Return to where you were"
    Then I should see "Ms1"
    Given I press "Export"
    When I press "Selected discussions"
    Then I should see "Tick the box beside each discussion you want to include"
    Given I set the field "Select discussion" to "1"
    When I press "Confirm selection"
    Then I should see "Downloading"

  Scenario: Try and get as far as we can exporting a discussion
    Given the following config values are set as admin:
      | enableportfolios | 1 |
    And I log in as "admin"
    And I am on site homepage
    And I navigate to "Manage portfolios" node in "Site administration > Plugins > Portfolios"
    And I set the field with xpath "//form[@id='applytodownload']/select" to "Enabled and visible"
    And I click on "form#applytodownload input[type='submit']" "css_element"
    And I press "Save"
    And I am on "Course 1" course homepage
    And I follow "Test forum"
    And I add a discussion with the following data:
      | Subject | Ms1 |
      | Message | abc |
    When I press "Export"
    Then I should see "Do you want to include the entire discussion, or only selected posts"
    Given I press "Discussion"
    When I follow "Return to where you were"
    Then I should see "abc"
    Given I press "Export"
    And I press "Selected posts"
    And I set the field "Select post" to "1"
    When I press "Confirm selection"
    Then I should see "Downloading"
