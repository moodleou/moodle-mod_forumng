@ou @ou_vle @mod @mod_forumng
Feature: Test upload
  With Plus symbol "+" in file name
  I can download these files in post

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "activities" exist:
      | activity | name            | introduction           | course | idnumber |
      | forumng  | Test forum name | Test forum description | C1     | forumng1 |

  @javascript
  Scenario: Test upload file with Plus symbol "+" in file name.
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    And I press "Start a new discussion"
    And I set the following fields to these values:
      | Subject | abc123 |
      | Message | x      |
    # Case file name has Plus symbol "+".
    And I upload "mod/forumng/tests/importfiles/a+d#cb.jpg" file to "Attachments" filemanager
    And I press "Post discussion"
    Then I should see "a+d#cb.jpg" in the ".forumng-attachments" "css_element"
    And following "a+d#cb.jpg" should download between "100" and "100000" bytes
    When I follow "Test forum name"
    And I press "Start a new discussion"
    And I set the following fields to these values:
      | Subject | abc1234 |
      | Message | x2      |
    And I upload "mod/forumng/tests/importfiles/samplepptx #@.pptx" file to "Attachments" filemanager
    And I press "Post discussion"
    Then I should see "samplepptx #@.pptx" in the ".forumng-attachments" "css_element"
    And following "samplepptx #@.pptx" should download between "100" and "500000" bytes
