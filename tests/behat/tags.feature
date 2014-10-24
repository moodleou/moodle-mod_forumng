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
    Then the "tag" select box should contain "oneA (1)"
    Then the "tag" select box should contain "oneB (1)"
    Then the "tag" select box should contain "two (1)"
    Then the "tag" select box should contain "twoA (1)"
    Then the "tag" select box should contain "twoB (1)"
    Then the "tag" select box should contain "three (1)"
    Then the "tag" select box should contain "threeA (1)"
    Then the "tag" select box should contain "threeB (1)"

    # Check correct tags are displayed for each discusssion
    Given "tr#discrow_3" "css_element" should exist
    Then "three" "link" should exist in the "tr#discrow_3" "css_element"
    Then "threeA" "link" should exist in the "tr#discrow_3" "css_element"
    Then "threeB" "link" should exist in the "tr#discrow_3" "css_element"

    Given "tr#discrow_2" "css_element" should exist
    Then "two" "link" should exist in the "tr#discrow_2" "css_element"
    Then "twoA" "link" should exist in the "tr#discrow_2" "css_element"
    Then "twoB" "link" should exist in the "tr#discrow_2" "css_element"

    Given "tr#discrow_1" "css_element" should exist
    Then "one" "link" should exist in the "tr#discrow_1" "css_element"
    Then "oneA" "link" should exist in the "tr#discrow_1" "css_element"
    Then "oneB" "link" should exist in the "tr#discrow_1" "css_element"

    # Check that the correct discussion is displayed if we click on a tag link
    When I click on "oneB" "link"
    Then "tr#discrow_1" "css_element" should exist
    Then "one" "link" should exist in the "tr#discrow_1" "css_element"
    Then "oneA" "link" should exist in the "tr#discrow_1" "css_element"
    Then "oneB" "link" should exist in the "tr#discrow_1" "css_element"
    And "tr#discrow_3" "css_element" should not exist
    And "tr#discrow_2" "css_element" should not exist
    And "Remove" "link" should exist in the "div.forumng_discuss_tagfilter" "css_element"

    # Check that we return to view page when the 'Remove' link is clicked on
    When I click on "Remove" "link"
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
    Then I should see "three, threeA, threeB" in the "textarea#id_tags_othertags" "css_element"
    Given I set the field "tags" to "two, three, threeB"

    # Check change of discussion tags has taken place on view page
    When I click on "Save changes" "button"
    And I follow "Course 1"
    And I follow "Test forum name"
    Given "tag" "select" should exist
    Then the "tag" select box should contain "two (2)"
    Then the "tag" select box should not contain "threeA (1)"

    # Check that selecting tag option "two (2)2 brings up 2 discussions
    Given I set the field "tag" to "two (2)"
    When I press "Go"
    Then "tr#discrow_3" "css_element" should exist
    Then "tr#discrow_2" "css_element" should exist
    Then "tr#discrow_1" "css_element" should not exist

    # Check that there are links in each discussion are correct
    Then "two" "link" should exist in the "tr#discrow_2" "css_element"
    Then "two" "link" should exist in the "tr#discrow_3" "css_element"
    Then "threeA" "link" should not exist in the "tr#discrow_3" "css_element"

    # Exit from test
    And I log out
