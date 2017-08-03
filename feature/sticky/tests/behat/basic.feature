@mod @mod_forumng @ou @ou_vle @forumngfeature_sticky
Feature: Make discussions sticky
  In order to make discussions sticky
  As a teacher
  I need to be able to use both features that make discussions stick to the top

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | student1 | Student | 1 | student1@asd.com |
      | student2 | Student | 2 | student2@asd.com |
      | teacher1 | Teacher | 1 | teacher1@asd.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | teacher |
      | student1 | C1 | student |
      | student2 | C1 | student |
    And the following "activities" exist:
      | activity | name                   | introduction                  | course | idnumber |
      | forumng  | Test forum name sticky | Test forum sticky description | C1     | forumng1 |
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I am on "Course 1" course homepage
    And I follow "Test forum name sticky"
    And I add a discussion with the following data:
      | Subject | Discussion 1 |
      | Message | Discussion 1 |
    And I follow "Test forum name sticky"
    And I add a discussion with the following data:
      | Subject | Discussion 2 |
      | Message | Discussion 2 |
    And I follow "Test forum name sticky"
    And I add a discussion with the following data:
      | Subject | Discussion 3 |
      | Message | Discussion 3 |
    And I follow "Test forum name sticky"
    And I add a discussion with the following data:
      | Subject | Discussion 4 |
      | Message | Discussion 4 |
    And I log out

  Scenario: Testing the 'Make discussion sticky' options
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test forum name sticky"
    Then I should see "Test forum sticky description"

    # Check order of existing discussions
    And "Discussion 4" "table_row" should appear before "Discussion 3" "table_row"
    And "Discussion 3" "table_row" should appear before "Discussion 2" "table_row"
    And "Discussion 2" "table_row" should appear before "Discussion 1" "table_row"

    # Check for non existence of any icons
    And "//td[1]//img" "xpath_element" should not exist in the "Discussion 4" "table_row"
    And "//td[1]//img" "xpath_element" should not exist in the "Discussion 3" "table_row"
    And "//td[1]//img" "xpath_element" should not exist in the "Discussion 2" "table_row"
    And "//td[1]//img" "xpath_element" should not exist in the "Discussion 1" "table_row"

    # Check for availability of sticky buttons for Admin 
    Then "Make discussions sticky" "button" should exist
    Then "Make discussions not sticky" "button" should exist

    # Delete discussion
    And I follow "Discussion 4"
    When I press "Delete"
    And I press "Delete"
    # Confirm discussion deleted
    And ".forumng-deleted" "css_element" should exist in the "Discussion 4" "table_row"

    # Lock dscussion
    And I follow "Discussion 2"
    When I press "Lock"
    Given I set the field "Message" to "Now locked for sticky test"
    # Needed for flow
    And I wait "1" seconds
    When I press "Lock discussion"
    And I wait "1" seconds
    Then I should see "This discussion is now closed"
    And I should see "Now locked for sticky test"
    And "Unlock" "button" should exist
    # Check for availability of sticky for Admin
    And "Discussion options" "button" should exist
    And I follow "Test forum name sticky"
    # Confirm discussion locked
    And ".forumng-locked" "css_element" should exist in the "Discussion 2" "table_row"

    # All discussions made sticky
    Given I press "Make discussions sticky"
    Given I press "All discussions shown"

    # Check discussions sticky ordering
    And "Discussion 2" "table_row" should appear before "Discussion 3" "table_row"
    And "Discussion 3" "table_row" should appear before "Discussion 1" "table_row"
    And "Discussion 1" "table_row" should appear before "Discussion 4" "table_row"

    # Check for sticky discussion marked locked
    And ".forumng-sticky.forumng-locked" "css_element" should exist in the "Discussion 2" "table_row"
    # Check for discussion marked deleted
    And ".forumng-deleted" "css_element" should exist in the "Discussion 4" "table_row"

    # Check for existence of icons
    And "//td[1]//img" "xpath_element" should exist in the "Discussion 2" "table_row"
    And ".forumng-sticky" "css_element" should exist in the "Discussion 2" "table_row"
    And "//td[1]//img" "xpath_element" should exist in the "Discussion 3" "table_row"
    And ".forumng-sticky" "css_element" should exist in the "Discussion 3" "table_row"
    And "//td[1]//img" "xpath_element" should exist in the "Discussion 1" "table_row"
    And ".forumng-sticky" "css_element" should exist in the "Discussion 1" "table_row"
    # Check for non-existence of icons
    And "//td[1]//img" "xpath_element" should not exist in the "Discussion 4" "table_row"
    And ".forumng-sticky" "css_element" should not exist in the "Discussion 4" "table_row"

    # All discussions made not sticky
    Given I press "Make discussions not sticky"
    Given I press "All discussions shown"

    # Check discussions ordering has changed
    And "Discussion 2" "table_row" should appear before "Discussion 4" "table_row"
    And "Discussion 4" "table_row" should appear before "Discussion 3" "table_row"
    And "Discussion 3" "table_row" should appear before "Discussion 1" "table_row"

    # Check for discussion marked locked
    And ".forumng-locked" "css_element" should exist in the "Discussion 2" "table_row"
    # Check for discussion marked deleted
    And ".forumng-deleted" "css_element" should exist in the "Discussion 4" "table_row"

    # Check for existence of icons
    And "//td[1]//img" "xpath_element" should exist in the "Discussion 2" "table_row"
    And "//td[1]//img" "xpath_element" should not exist in the "Discussion 4" "table_row"
    And "//td[1]//img" "xpath_element" should not exist in the "Discussion 3" "table_row"
    And "//td[1]//img" "xpath_element" should not exist in the "Discussion 1" "table_row"

    # Multiple discussions made sticky
    Given I press "Make discussions sticky"
    Given I press "Selected discussions"
    And I wait "1" seconds
    And I set the field with xpath "//tr[contains(normalize-space(.), 'Discussion 3')]//input[@type='checkbox']" to "1"
    And I set the field with xpath "//tr[contains(normalize-space(.), 'Discussion 1')]//input[@type='checkbox']" to "1"
    And I press "Confirm selection"

    # Check discussions order is changed
    And "Discussion 3" "table_row" should appear before "Discussion 1" "table_row"
    And "Discussion 1" "table_row" should appear before "Discussion 2" "table_row"
    And "Discussion 2" "table_row" should appear before "Discussion 4" "table_row"
    # Check for extant sticky icons
    And "//td[1]//img" "xpath_element" should exist in the "Discussion 2" "table_row"
    And "//td[1]//img" "xpath_element" should exist in the "Discussion 3" "table_row"
    And "//td[1]//img" "xpath_element" should exist in the "Discussion 1" "table_row"
    And "//td[1]//img" "xpath_element" should not exist in the "Discussion 4" "table_row"

    # Discussion made not sticky
    Given I press "Make discussions not sticky"
    Given I press "Selected discussions"
    # Need time to access checkboxes
    And I wait "1" seconds
    And I set the field with xpath "//tr[contains(normalize-space(.), 'Discussion 3')]//input[@type='checkbox']" to "1"
    And I press "Confirm selection"
    # Check discussions order (allowing for extra divider row)
    And "Discussion 1" "table_row" should appear before "Discussion 2" "table_row"
    And "Discussion 2" "table_row" should appear before "Discussion 4" "table_row"
    And "Discussion 4" "table_row" should appear before "Discussion 3" "table_row"

    # Check for extant/non extant sticky icons
    And "//td[1]//img" "xpath_element" should exist in the "Discussion 1" "table_row"
    And ".forumng-sticky" "css_element" should exist in the "Discussion 1" "table_row"
    And "//td[1]//img" "xpath_element" should exist in the "Discussion 2" "table_row"
    And ".forumng-locked" "css_element" should exist in the "Discussion 2" "table_row"
    And "//td[1]//img" "xpath_element" should not exist in the "Discussion 4" "table_row"
    And ".forumng-deleted" "css_element" should exist in the "Discussion 4" "table_row"
    And "//td[1]//img" "xpath_element" should not exist in the "Discussion 3" "table_row"
    And I log out

    # Test Student does not get option to make things multi-sticky
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test forum name sticky"

    # Check discussions states seen as student
    And "Discussion 1" "table_row" should appear before "Discussion 2" "table_row"
    And ".forumng-sticky" "css_element" should exist in the "Discussion 1" "table_row"
    And "Discussion 2" "table_row" should appear before "Discussion 3" "table_row"
    And ".forumng-locked" "css_element" should exist in the "Discussion 2" "table_row"
    And "//td[1]//img" "xpath_element" should not exist in the "Discussion 3" "table_row"

    # Check for unavailability of sticky for Students
    And "Make discussions sticky" "button" should not exist
    And "Make discussions not sticky" "button" should not exist
    And I log out

  Scenario: Testing the 'Discussion options'
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test forum name sticky"
    Then I should see "Test forum sticky description"

    # Check initial order of existing discussion threads
    And "Discussion 4" "table_row" should appear before "Discussion 3" "table_row"
    And "Discussion 3" "table_row" should appear before "Discussion 2" "table_row"
    And "Discussion 2" "table_row" should appear before "Discussion 1" "table_row"
    # Check for non existence of sticky icons
    And "//td[1]//img" "xpath_element" should not exist in the "Discussion 4" "table_row"
    And "//td[1]//img" "xpath_element" should not exist in the "Discussion 3" "table_row"
    And "//td[1]//img" "xpath_element" should not exist in the "Discussion 2" "table_row"
    And "//td[1]//img" "xpath_element" should not exist in the "Discussion 1" "table_row"

    # Check changes to order of discussion threads
    And I follow "Discussion 1"
    Given I press "Discussion options"
    And I set the field "Sticky discussion" to "Discussion stays on top of list"
    And I press "Save changes"
    And I follow "Test forum name sticky"
    # Check discussions order has changed (allowing for extra divider row)
    And "Discussion 1" "table_row" should appear before "Discussion 4" "table_row"
    And "Discussion 4" "table_row" should appear before "Discussion 3" "table_row"
    And "Discussion 3" "table_row" should appear before "Discussion 2" "table_row"
    # Check for addition of changed discussion thread sticky icon
    And "//td[1]//img" "xpath_element" should exist in the "Discussion 1" "table_row"
    And "//td[1]//img" "xpath_element" should not exist in the "Discussion 4" "table_row"
    And "//td[1]//img" "xpath_element" should not exist in the "Discussion 3" "table_row"
    And "//td[1]//img" "xpath_element" should not exist in the "Discussion 2" "table_row"
    And I follow "Discussion 3"
    Given I press "Discussion options"
    And I set the field "Sticky discussion" to "Discussion stays on top of list"
    And I press "Save changes"
    And I follow "Test forum name sticky"

    # Check discussions order has changed again (allowing for extra divider row)
    And "Discussion 3" "table_row" should appear before "Discussion 1" "table_row"
    And "Discussion 1" "table_row" should appear before "Discussion 4" "table_row"
    And "Discussion 4" "table_row" should appear before "Discussion 2" "table_row"
    # Check for addition of changed discussion threads sticky icons
    And "//td[1]//img" "xpath_element" should exist in the "Discussion 3" "table_row"
    And "//td[1]//img" "xpath_element" should exist in the "Discussion 1" "table_row"
    And "//td[1]//img" "xpath_element" should not exist in the "Discussion 4" "table_row"
    And "//td[1]//img" "xpath_element" should not exist in the "Discussion 2" "table_row"

    # Add discussion that is sticky
    Given I add a discussion with the following data:
      | Subject | Discussion 5 |
      | Message | Discussion 5 |
      | sticky | 1 |
    And I follow "Test forum name sticky"
    # Check new discussion made sticky is at the top
    And "Discussion 5" "table_row" should appear before "Discussion 3" "table_row"
    And "Discussion 3" "table_row" should appear before "Discussion 1" "table_row"
    And "Discussion 1" "table_row" should appear before "Discussion 4" "table_row"
    And "Discussion 4" "table_row" should appear before "Discussion 2" "table_row"
    # Check discussion threads sticky icons includes new one
    And "//td[1]//img" "xpath_element" should exist in the "Discussion 5" "table_row"
    And "//td[1]//img" "xpath_element" should exist in the "Discussion 3" "table_row"
    And "//td[1]//img" "xpath_element" should exist in the "Discussion 1" "table_row"
    And "//td[1]//img" "xpath_element" should not exist in the "Discussion 4" "table_row"
    And "//td[1]//img" "xpath_element" should not exist in the "Discussion 2" "table_row"

    # Add discussion that is not sticky
    Given I add a discussion with the following data:
      | Subject | Discussion 6 |
      | Message | Discussion 6 |
    And I follow "Test forum name sticky"
    # Check order & position of new non-sticky discussion thread (allowing for extra divider row)
    And "Discussion 5" "table_row" should appear before "Discussion 3" "table_row"
    And "Discussion 3" "table_row" should appear before "Discussion 1" "table_row"
    And "Discussion 1" "table_row" should appear before "Discussion 6" "table_row"
    And "Discussion 6" "table_row" should appear before "Discussion 4" "table_row"
    And "Discussion 4" "table_row" should appear before "Discussion 2" "table_row"
    # Check for discussion threads sticky icons
    And "//td[1]//img" "xpath_element" should exist in the "Discussion 5" "table_row"
    And "//td[1]//img" "xpath_element" should exist in the "Discussion 3" "table_row"
    And "//td[1]//img" "xpath_element" should exist in the "Discussion 1" "table_row"
    And "//td[1]//img" "xpath_element" should not exist in the "Discussion 6" "table_row"
    And "//td[1]//img" "xpath_element" should not exist in the "Discussion 4" "table_row"
    And "//td[1]//img" "xpath_element" should not exist in the "Discussion 2" "table_row"

    # Check sort on 'Discussion' reorders the discussion threads
    Given I follow "Discussion"
    Then "Discussion 1" "table_row" should appear before "Discussion 3" "table_row"
    And "Discussion 3" "table_row" should appear before "Discussion 5" "table_row"
    And "Discussion 5" "table_row" should appear before "Discussion 2" "table_row"
    And "Discussion 2" "table_row" should appear before "Discussion 4" "table_row"
    And "Discussion 4" "table_row" should appear before "Discussion 6" "table_row"

    # Delete a sticky discussion
    And I follow "Discussion 3"
    When I press "Delete"
    And I wait "1" seconds
    And I press "Delete"
    And ".forumng-deleted" "css_element" should exist in the "Discussion 3" "table_row"
    # Delete a normal discussion
    And I follow "Discussion 4"
    When I press "Delete"
    And I wait "1" seconds
    And I press "Delete"

    # Check 'delete' reorders the 'sticky' discussion threads
    And "Discussion 1" "table_row" should appear before "Discussion 5" "table_row"
    And "Discussion 5" "table_row" should appear before "Discussion 3" "table_row"
    And "Discussion 3" "table_row" should appear before "Discussion 2" "table_row"
    And "Discussion 2" "table_row" should appear before "Discussion 4" "table_row"
    And "Discussion 4" "table_row" should appear before "Discussion 6" "table_row"
    And ".forumng-deleted" "css_element" should exist in the "Discussion 3" "table_row"
    And ".forumng-deleted" "css_element" should exist in the "Discussion 4" "table_row"

    # Check cant manually 'make sticky' after deletions
    # Check the sticky discussion
    And I follow "Discussion 3"
    And "Discussion options" "button" should not exist
    And "Undelete" "button" should exist
    And "Lock" "button" should not exist
    And I follow "Test forum name sticky"
    # Check the normal discussion
    And I follow "Discussion 4"
    And "Discussion options" "button" should not exist
    And "Undelete" "button" should exist
    And "Lock" "button" should not exist
    And I follow "Test forum name sticky"

    # Check manual 'Lock' after deletion
    # Check the sticky discussion
    And I follow "Discussion 1"
    When I press "Lock"
    Given I set the field "Message" to "Now locked for sticky test"
    When I press "Lock discussion"
    And I should see "This discussion is now closed" in the ".forumng-subject" "css_element"
    And I should see "Now locked for sticky test" in the ".forumng-message" "css_element"

    And "Unlock" "button" should exist
    And "Discussion options" "button" should exist
    And I follow "Test forum name sticky"
    And ".forumng-sticky.forumng-locked" "css_element" should exist in the "Discussion 1" "table_row"
    And I log out

    # Test student does not see manual 'sticky' options
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test forum name sticky"
    # Check for unavailability of sticky for Students
    And "Make discussions sticky" "button" should not exist
    And "Make discussions not sticky" "button" should not exist
    And I follow "Discussion 1"
    # Check Students can not get to 'sticky' option
    Then "Discussion options" "button" should not exist
    Then "Delete" "button" should not exist
    Then "Lock" "button" should not exist
    And I log out
