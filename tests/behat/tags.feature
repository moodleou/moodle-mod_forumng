@mod @mod_forumng @ou @ou_vle @mod_forumng_tags @javascript
Feature: Add forumng activity and test basic tagging functionality
  In order to add and view tags
  As admin
  I need to add forum activities to moodle courses

  Background:
      Given the following "users" exist:
      | username | firstname | lastname | email |
      | student1 | Student | 1 | student1@asd.com |
      | student2 | Student | 2 | student2@asd.com |
      | teacher1 | Teacher | 1 | teacha1@asd.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "groups" exist:
      | name | course | idnumber |
      | Group 1 | C1 | G1 |
      | Group 2 | C1 | G2 |
    And the following "course enrolments" exist:
      | user | course | role |
      | student1 | C1 | student |
      | student2 | C1 | student |
      | teacher1 | C1 | editingteacher |

  Scenario: Add tagging to discussions
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I add a "ForumNG" to section "1" and I fill the form with:
      | Forum name | Test forum name |
      | Forum introduction | Test forum description |
      |Enable discussion tagging | 1 |
    And I follow "Test forum name"
    And I press "Start a new discussion"
    And I press "Cancel"
    And I add a discussion with the following data:
      | Subject | Discussion 1 |
      | Message | abc |
      | tags | one, oneA, oneB |
    And I follow "Test forum name"
    And I add a discussion with the following data:
      | Subject | Discussion 2 |
      | Message | def |
     | tags | two, twoA, twoB |
    And I follow "Test forum name"
    And I add a discussion with the following data:
      | Subject | Discussion 3 |
      | Message | ghi |
      | tags | three, threeA, threeB |
    And I follow "Test forum name"
    And I add a discussion with the following data:
      | Subject | Discussion 4 |
      | Message | ghi |
      | tags | four, fourA, fourB |
    And I follow "Test forum name"
    And I add a discussion with the following data:
      | Subject | Discussion 5 |
      | Message | no tags      |
    And I press "Discussion options"
    And I press "Save changes"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"

    # Check that 'Groups' dropdown does not exist
    Then "group" "select" should not exist

    # Check all tags are displayed in dropdown
    Given "tag" "select" should exist
    Then the "tag" select box should contain "one (1)"
    Then the "tag" select box should contain "onea (1)"
    Then the "tag" select box should contain "oneb (1)"
    Then the "tag" select box should contain "two (1)"
    Then the "tag" select box should contain "twoa (1)"
    Then the "tag" select box should contain "twob (1)"
    Then the "tag" select box should contain "three (1)"
    Then the "tag" select box should contain "threea (1)"
    Then the "tag" select box should contain "threeb (1)"

    # Check correct tags are displayed for each discusssion
    Given "tr.forumng-discussion-short:nth-child(3)" "css_element" should exist
    Then "three" "link" should exist in the "tr.forumng-discussion-short:nth-child(3)" "css_element"
    Then "threea" "link" should exist in the "tr.forumng-discussion-short:nth-child(3)" "css_element"
    Then "threeb" "link" should exist in the "tr.forumng-discussion-short:nth-child(3)" "css_element"

    Given "tr.forumng-discussion-short:nth-child(4)" "css_element" should exist
    Then "two" "link" should exist in the "tr.forumng-discussion-short:nth-child(4)" "css_element"
    Then "twoa" "link" should exist in the "tr.forumng-discussion-short:nth-child(4)" "css_element"
    Then "twob" "link" should exist in the "tr.forumng-discussion-short:nth-child(4)" "css_element"

    Given "tr.forumng-discussion-short:nth-child(5)" "css_element" should exist
    Then "one" "link" should exist in the "tr.forumng-discussion-short:nth-child(5)" "css_element"
    Then "onea" "link" should exist in the "tr.forumng-discussion-short:nth-child(5)" "css_element"
    Then "oneb" "link" should exist in the "tr.forumng-discussion-short:nth-child(5)" "css_element"

    # Check that the correct discussion is displayed if we click on a tag link
    When I click on "oneb" "link"
    Then "tr.forumng-discussion-short:nth-child(1)" "css_element" should exist
    Then "one" "link" should exist in the "tr.forumng-discussion-short:nth-child(1)" "css_element"
    Then "onea" "link" should exist in the "tr.forumng-discussion-short:nth-child(1)" "css_element"
    Then "oneb" "link" should exist in the "tr.forumng-discussion-short:nth-child(1)" "css_element"
    And "tr.forumng-discussion-short:nth-child(2)" "css_element" should not exist
    And "Show all" "link" should exist in the "div.forumng_discuss_tagfilter" "css_element"

    # Check that we return to view page when the 'Show all' link is clicked on
    When I click on "Show all" "link"
    Then "tr.forumng-discussion-short:nth-child(2)" "css_element" should exist
    And "Discussion 3" "link" should exist in the "tr.forumng-discussion-short:nth-child(3)" "css_element"
    Then "tr.forumng-discussion-short:nth-child(4)" "css_element" should exist
    Then "tr.forumng-discussion-short:nth-child(5)" "css_element" should exist

    # Check that we can display a discussion
    When I click on "Discussion 3" "link"
    Then I should see "Discussion 3" in the "h3.forumng-subject" "css_element"
    Then I should see "ghi" in the "div.forumng-message" "css_element"

    # Check that we open discussion options and set/edit tags to new values
    When I click on "Discussion options" "button"
    Then I should see "three" in the ".form-autocomplete-selection" "css_element"
    And I should see "threea" in the ".form-autocomplete-selection" "css_element"
    And I should see "threeb" in the ".form-autocomplete-selection" "css_element"
    Given I click on "span[data-value=three]" "css_element"
    And I click on "span[data-value=threea]" "css_element"
    And I set the field "tags" to "two,"

    # Check change of discussion tags has taken place on view page
    When I click on "Save changes" "button"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    Given "tag" "select" should exist
    Then the "tag" select box should contain "two (2)"
    Then the "tag" select box should not contain "threea (1)"

    # Check that selecting tag option "two (2)2 brings up 2 discussions
    Given I set the field "tag" to "two (2)"
    Then "tr.forumng-discussion-short:nth-child(1)" "css_element" should exist
    Then "tr.forumng-discussion-short:nth-child(2)" "css_element" should exist
    Then "tr.forumng-discussion-short:nth-child(3)" "css_element" should not exist

    # Check that there are links in each discussion are correct
    Then "two" "link" should exist in the "tr.forumng-discussion-short:nth-child(1)" "css_element"
    Then "two" "link" should exist in the "tr.forumng-discussion-short:nth-child(2)" "css_element"
    Then "threea" "link" should not exist in the "tr.forumng-discussion-short:nth-child(2)" "css_element"
    And "Show all" "link" should exist in the "div.forumng_discuss_tagfilter" "css_element"

    # Check that we return to view page when the 'Show all' link is clicked on
    When I click on "Show all" "link"

    # Test forum wide 'set' tags
    Then I navigate to "Edit settings" node in "ForumNG administration"
    When I click on "Edit settings" "link"
    Then the "Enable discussion tagging" "checkbox" should be enabled
    Given I set the field "Set tags for forum" to "setA, setB, setC"
    And I click on "Save and display" "button"

    # Check to see that 'set' tags are not showing up in forumng view tag dropdown
    Given "tag" "select" should exist
    Then the "tag" select box should not contain "seta (0)"
    Then the "tag" select box should not contain "setb (0)"
    Then the "tag" select box should not contain "setc (0)"

    # Add a new forum for checking copying and moving of discussions with tags
    And I am on "Course 1" course homepage
    And I add a "ForumNG" to section "2" and I fill the form with:
      | Forum name | Test forum name two |
      | Forum introduction | Test forum two description |
      |Enable discussion tagging | 1 |

    And I follow "Test forum name two"
    And I add a discussion with the following data:
      | Subject | Discussion two 1 |
      | Message | abc2 |
      | tags | t20, t21, t23 |
    And I follow "Test forum name"
    And I add a discussion with the following data:
      | Subject | Discussion two 2 |
      | Message | def2 |
      | tags | t30, t31, t33 |

    # Test the copying of a discussion
    And I am on "Course 1" course homepage
    And I follow "Test forum name two"
    And I click on "Discussion two 1" "link"
    When I click on "Copy" "button"
    Then I click on "Begin copy" "button"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    When I click on "Paste discussion" "button"
    Then I should see "Discussion two 1"
    And "t20" "link" should exist
    And "t21" "link" should exist
    And "t23" "link" should exist
    Given I click on "Discussion two 1" "link"
    And I click on "Discussion options" "button"
    Then I should see "t20" in the ".form-autocomplete-selection" "css_element"
    Then I should see "t21" in the ".form-autocomplete-selection" "css_element"
    Then I should see "t23" in the ".form-autocomplete-selection" "css_element"
    And I click on "Cancel" "button"

    # Test the moving of a discussion
    Given I click on "Test forum name" "link"
    Then I should see "Discussion 4"
    And I click on "Discussion 4" "link"
    Given "target" "select" should exist
    Then the "target" select box should contain "Test forum name two"
    Given I set the field "target" to "Test forum name two"
    And I click on "Move" "button"
    Then "Test forum name two" "link" should exist
    And "Discussion 4" "link" should exist
    And "four" "link" should exist
    And "foura" "link" should exist
    And "fourb" "link" should exist

    Given I am on "Course 1" course homepage
    And I follow "Test forum name"
    Then "Discussion 4" "link" should not exist
    And "four" "link" should not exist
    And "foura" "link" should not exist
    And "fourb" "link" should not exist

    # Log out as admin
    And I log out

    # Log in as a student 1 to test adding discussion tags
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    Then I should see "Test forum description"
    And "Start a new discussion" "button" should exist
    And I add a discussion with the following data:
      | Subject | Discussion S1 |
      | Message | abc |

    # Check that we set/edit tags to new values
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    And I follow "Discussion S1"
    When I click on "Edit tags" "button"
    Given I set the field "tags" to "s1, s12, s13"
    And I click on "Save changes" "button"

    # Log out as student 1
    And I log out

    # Log in as a student 2 to test adding discussion tags
    Given I log in as "student2"
    And I am on site homepage
    # Check that we set/edit tags to new values
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    And I follow "Discussion S1"
    Then I should see "Discussion tags: s1, s12, s13"
    And I should not see "Edit tags"

    # Log out as student 2
    And I log out

  Scenario: Test system changes
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I add a "ForumNG" to section "1" and I fill the form with:
      | Forum name | Test forum name |
      | Forum introduction | Test forum description |
      | Enable discussion tagging | 1 |
      | Group mode | Separate groups |

    # Set up groups for the forum
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    Given "Separate groups" "select" should exist
    And I set the field "Separate groups" to "Group 1"
    And I add a discussion with the following data:
      | Subject | Discussion 1 |
      | Message | abc |
    And I follow "Test forum name"
    And I set the field "Separate groups" to "Group 2"
    And I add a discussion with the following data:
      | Subject | Discussion 2 |
      | Message | def |

    # Enrol users into groups
    Given I am on "Course 1" course homepage
    And I navigate to "Users > Groups" in current page administration
    Then "Groups" "select" should exist

    Given I set the field "Groups" to "Group 1 (0)"
    Then I click on "Add/remove users" "button"
    And I set the field "Potential members" to "Teacher 1 (teacha1@asd.com) (0)"
    And I press "Add"
    And I press "Back to groups"
    And I log out

    # Test teacher can only see groups from manage 'set' tags screen
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    Then I should see "Test forum description"
    Given I press "Edit Set tags"
    Then I should see "Set tags for Group 1"
    And I should see "Set tags for Group 2"
    And I should not see "Set tags for forum"
    And I log out

    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I navigate to "Users > Permissions" in current page administration
    Given I override the system permissions of "Teacher" role with:
      | forumngfeature/edittags:editsettags | Prevent |
    And I click on "Back to Course: Course 1" "link"
    And I log out

    # Test teacher can only see group 2 set tags
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    Then I should see "Test forum description"
    And I should not see "Edit Set tags"
    And I log out

    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    And I navigate to "Permissions" in current page administration
    Given I override the system permissions of "Teacher" role with:
      | forumngfeature/edittags:editsettags | Allow |
    And I click on "Back to ForumNG: Test forum name" "link"
    And I log out

    # Test teacher can only see groups from manage 'set' tags screen
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    Then I should see "Test forum description"
    Given I press "Edit Set tags"
    Then I should see "Set tags for Group 1"
    And I should see "Set tags for Group 2"
    And I should not see "Set tags for forum"
    And I log out

    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    And I navigate to "Permissions" in current page administration
    Given I override the system permissions of "Teacher" role with:
      | moodle/site:accessallgroups | Prevent |
    And I click on "Back to ForumNG: Test forum name" "link"
    And I log out

    # Test teacher can only see groups from manage 'set' tags screen
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    Then I should see "Test forum description"
    Given I press "Edit Set tags"
    Then I should see "Set tags for Group 1"
    And I should not see "Set tags for Group 2"
    And I should not see "Set tags for forum"

  Scenario: Add group tagging to forums
    And I log in as "admin"
    And I am on site homepage
    # Create 2 Discussions
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I add a "ForumNG" to section "1" and I fill the form with:
      | Forum name                | Test forum name        |
      | Forum introduction        | Test forum description |
      | Enable discussion tagging | 1                      |
    # No Groups default
    And I follow "Test forum name"
    And I add a discussion with the following data:
      | Subject | Discussion 1 |
      | Message | abc          |
    And I follow "Test forum name"
    And I add a discussion with the following data:
      | Subject | Discussion 2 |
      | Message | def          |
    And I follow "Test forum name"
    # Check that 'Edit Set tags' button is displayed.
    Given I click on "Edit Set tags" "button"
    Then I should see "Set tags for forum"
    # This is the only control we have over a group id
    And I set the following fields to these values:
      | Set tags for forum | f1, f2, f3  |
    And I press "Save changes"

    # Make use of forum wide tags
    Then I click on "Discussion 1" "link"
    Then I click on "Edit tags" "button"
    When I click on " .form-autocomplete-downarrow" "css_element"
    Then I should see "f1"
    And I should see "f2"
    And I should see "f3"
    Given I click on "f1" "list_item"
    And I click on "Save changes" "button"
    # Returns to discuss.php page
    Then I should see "Discussion tags: f1"
    Given I click on "Edit tags" "button"
    Then I should see "f1"
    # Now need to return to main forum page
    And I click on "Cancel" "button"
    Given I click on "Test forum name" "link"
    Then I click on "Discussion 2" "link"
    Then I click on "Edit tags" "button"
    When I click on " .form-autocomplete-downarrow" "css_element"
    Then I should see "f1"
    And I should see "f2"
    And I should see "f3"
    Given I click on "f3" "list_item"
    And I click on "Save changes" "button"
    # Returns to discuss.php page
    Then I should see "Discussion tags: f3"
    And I click on "Test forum name" "link"
    Then "f3" "link" in the "tr.forumng-discussion-short.r0" "css_element" should be visible
    And "f1" "link" in the "tr.forumng-discussion-short.r1.lastrow" "css_element" should be visible

    # Set up groups for the forum
    Then I navigate to "Edit settings" in current page administration
    And I expand all fieldsets
    Then I should see "Visible"
    And the "Group mode" select box should contain "Separate groups"
    Given I set the field "Group mode" to "Separate groups"
    And I click on "Save and display" "button"

    # Set discussion 1 to group 1
    Given I click on "Discussion 1" "link"
    Then I click on "Discussion options" "button"
    Then I should see "Group"
    And I set the field "Group" to "Group 1"
    And I click on "Save changes" "button"
    Then I click on "Test forum name" "link"

    # Set discussion 2 to group 2.
    Given I click on "Discussion 2" "link"
    Then I click on "Discussion options" "button"
    Then I should see "Group"
    And I set the field "Group" to "Group 2"
    And I click on "Save changes" "button"
    Then I click on "Test forum name" "link"

    # Test that text areas appear for the group tags
    When I click on "Edit Set tags" "button"
    Then I should see "Set tags for forum"
    And I should see "Set tags for Group 1"
    And I should see "Set tags for Group 2"
    And I should see "f1" in the ".form-group.row:first-child" "css_element"
    And I should see "f2" in the ".form-group.row:first-child" "css_element"
    And I should see "f3" in the ".form-group.row:first-child" "css_element"

    # Create group tags
    When I set the field "Set tags for Group 1" to "g1, g2, g3"
    And I set the field "Set tags for Group 2" to "g4, g5, g6"
    And I press "Save changes"
    And I press "Edit Set tags"
    Then I should see "g1" in the ".form-group.row:nth-child(2)" "css_element"
    And I should see "g2" in the ".form-group.row:nth-child(2)" "css_element"
    And I should see "g3" in the ".form-group.row:nth-child(2)" "css_element"
    And I should see "g4" in the ".form-group.row:last-child" "css_element"
    And I should see "g5" in the ".form-group.row:last-child" "css_element"
    And I should see "g6" in the ".form-group.row:last-child" "css_element"

    Given I press "Cancel"
    And I follow "Discussion 1"
    And I press "Edit tags"
    And I click on " .form-autocomplete-downarrow" "css_element"
    Then I should see "g1"
    And I should not see "g4"

    # Test backup and restore
    And I am on "Course 1" course homepage
    And I duplicate "Test forum name" activity editing the new copy with:
      | Forum name | Duplicated Test forum name |
    # And I click on "Turn editing off" "link"
    And I follow "Duplicated Test forum name"
    Given I press "Edit Set tags"
    Then I should see "Set tags for forum"
    And I should see "Set tags for Group 1"
    And I should see "Set tags for Group 2"
    And I should see "f1" in the ".form-group.row:first-child" "css_element"
    And I should see "f2" in the ".form-group.row:first-child" "css_element"
    And I should see "f3" in the ".form-group.row:first-child" "css_element"
    And I should see "g1" in the ".form-group.row:nth-child(2)" "css_element"
    And I should see "g2" in the ".form-group.row:nth-child(2)" "css_element"
    And I should see "g3" in the ".form-group.row:nth-child(2)" "css_element"
    And I should see "g4" in the ".form-group.row:last-child" "css_element"
    And I should see "g5" in the ".form-group.row:last-child" "css_element"
    And I should see "g6" in the ".form-group.row:last-child" "css_element"

    # Exit from test
    And I log out
