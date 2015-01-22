@mod @mod_forumng @ou @ou_vle
Feature: Add forumng activity and test basic tagging functionality
  In order to add and view tags
  As admin
  I need to add forum activities to moodle courses

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |

  Scenario: Add tagging to discussions
    And I log in as "admin"
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "ForumNG" to section "1" and I fill the form with:
      | Forum name | Test forum name |
      | Forum introduction | Test forum description |
      |Enable discussion tagging | 1 |
    And I follow "Test forum name"
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

    # Check all tags are displayed in dropdown
    And I follow "Course 1"
    And I follow "Test forum name"
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
    Given "tr#discrow_3" "css_element" should exist
    Then "three" "link" should exist in the "tr#discrow_3" "css_element"
    Then "threea" "link" should exist in the "tr#discrow_3" "css_element"
    Then "threeb" "link" should exist in the "tr#discrow_3" "css_element"

    Given "tr#discrow_2" "css_element" should exist
    Then "two" "link" should exist in the "tr#discrow_2" "css_element"
    Then "twoa" "link" should exist in the "tr#discrow_2" "css_element"
    Then "twob" "link" should exist in the "tr#discrow_2" "css_element"

    Given "tr#discrow_1" "css_element" should exist
    Then "one" "link" should exist in the "tr#discrow_1" "css_element"
    Then "onea" "link" should exist in the "tr#discrow_1" "css_element"
    Then "oneb" "link" should exist in the "tr#discrow_1" "css_element"

    # Check that the correct discussion is displayed if we click on a tag link
    When I click on "oneb" "link"
    Then "tr#discrow_1" "css_element" should exist
    Then "one" "link" should exist in the "tr#discrow_1" "css_element"
    Then "onea" "link" should exist in the "tr#discrow_1" "css_element"
    Then "oneb" "link" should exist in the "tr#discrow_1" "css_element"
    And "tr#discrow_3" "css_element" should not exist
    And "tr#discrow_2" "css_element" should not exist
    And "Show all" "link" should exist in the "div.forumng_discuss_tagfilter" "css_element"

    # Check that we return to view page when the 'Show all' link is clicked on
    When I click on "Show all" "link"
    Then "tr#discrow_3" "css_element" should exist
    And "Discussion 3" "link" should exist in the "tr#discrow_3" "css_element"
    Then "tr#discrow_2" "css_element" should exist
    Then "tr#discrow_1" "css_element" should exist

    # Check that we can display a discussion
    When I click on "Discussion 3" "link"
    Then I should see "Discussion 3" in the "h3.forumng-subject" "css_element"
    Then I should see "ghi" in the "div.forumng-message" "css_element"

    # Check that we open discussion options and set/edit tags to new values
    When I click on "Discussion options" "button"
    Then I should see "three, threea, threeb" in the "textarea#id_tags_othertags" "css_element"
    Given I set the field "tags" to "two, three, threeB"

    # Check change of discussion tags has taken place on view page
    When I click on "Save changes" "button"
    And I follow "Course 1"
    And I follow "Test forum name"
    Given "tag" "select" should exist
    Then the "tag" select box should contain "two (2)"
    Then the "tag" select box should not contain "threea (1)"

    # Check that selecting tag option "two (2)2 brings up 2 discussions
    Given I set the field "tag" to "two (2)"
    When I press "Go"
    Then "tr#discrow_3" "css_element" should exist
    Then "tr#discrow_2" "css_element" should exist
    Then "tr#discrow_1" "css_element" should not exist

    # Check that there are links in each discussion are correct
    Then "two" "link" should exist in the "tr#discrow_2" "css_element"
    Then "two" "link" should exist in the "tr#discrow_3" "css_element"
    Then "threea" "link" should not exist in the "tr#discrow_3" "css_element"
    And "Show all" "link" should exist in the "div.forumng_discuss_tagfilter" "css_element"

    # Check that we return to view page when the 'Show all' link is clicked on
    When I click on "Show all" "link"

    # Test forum wide 'set' tags
    Then I navigate to "Edit settings" node in "ForumNG administration"
    When I click on "Edit settings" "link"
    Then the "Enable discussion tagging" "checkbox" should be enabled
    Given I set the field "id_settags_othertags" to "setA, setB, setC"
    And I click on "Save and display" "button"

    # Check to see that 'set' tags are not showing up in forumng view tag dropdown
    Given "tag" "select" should exist
    Then the "tag" select box should not contain "seta (0)"
    Then the "tag" select box should not contain "setb (0)"
    Then the "tag" select box should not contain "setc (0)"

    # Add a new forum for checking copying and moving of discussions with tags
    And I follow "Course 1"
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
    And I follow "Course 1"
    And I follow "Test forum name two"
    And I click on "Discussion two 1" "link"
    When I click on "Copy" "button"
    Then I click on "Begin copy" "button"
    And I follow "Course 1"
    And I follow "Test forum name"
    When I click on "Paste discussion" "button"
    Then I should see "Discussion two 1"
    And "t20" "link" should exist
    And "t21" "link" should exist
    And "t23" "link" should exist
    Given I click on "Discussion two 1" "link"
    And I click on "Discussion options" "button"
    Then I should see "t20, t21, t23,"
    And I click on "Cancel" "button"

    # Test the moving of a discussion
    Given I click on "Test forum name" "link"
    Then I should see "Discussion 2"
    And I click on "Discussion 2" "link"
    Given "target" "select" should exist
    Then the "target" select box should contain "Test forum name two"
    Given I set the field "target" to "Test forum name two"
    And I click on "Move" "button"
    Then "Test forum name two" "link" should exist
    And "Discussion 2" "link" should exist
    And "two" "link" should exist
    And "twoa" "link" should exist
    And "twob" "link" should exist

    Given I follow "Course 1"
    And I follow "Test forum name"
    Then "Discussion 2" "link" should not exist
    And "twoa" "link" should not exist
    And "twob" "link" should not exist

    # Exit from test
    And I log out
