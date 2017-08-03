@ou @ou_vle @mod @mod_forumng
Feature: Username protection
  In order to stop people accidentally posting OUCU due to broken password managers
  As anybody at all
  I should get a validation error if I use OUCU or PI in a subject line

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "users" exist:
      | username | idnumber |
      | abc123   | W1234567 |
    And the following "course enrolments" exist:
      | user   | course | role    |
      | abc123 | C1     | student |
    And the following "activities" exist:
      | activity | name      | course | idnumber | section |
      | forumng  | TestForum | C1     | WTF      | 0       |
    And I log in as "abc123"
    And I am on "Course 1" course homepage
    And I follow "TestForum"

  @javascript
  Scenario: Post a new discussion
    When I press "Start a new discussion"
    And I set the following fields to these values:
      | Subject | abc123 |
      | Message | x      |
    And I wait until "#id_submitbutton[disabled]" "css_element" does not exist
    And I press "Post discussion"
    Then I should see "You have set the subject line to your login"
    And I should see "To continue, change the subject"

    When I set the field "Subject" to "W1234567"
    And I press "Post discussion"
    Then I should see "You have set the subject line to your login"

    When I set the field "Subject" to "Not abc123"
    And I press "Post discussion"
    Then I should not see "You have set the subject line to your login"
    And I should see "Not abc123" in the ".breadcrumb-nav" "css_element"

  @javascript
  Scenario: Post a new reply
    Given I press "Start a new discussion"
    And I set the following fields to these values:
      | Subject | Original |
      | Message | x        |
    And I wait until "#id_submitbutton[disabled]" "css_element" does not exist
    And I press "Post discussion"

    When I follow "Reply"
    And I switch to "forumng-post-iframe" iframe
    And I set the following fields to these values:
      | subject    | abc123 |
      | Message    | xxx    |
    And I wait until "#id_submitbutton[disabled]" "css_element" does not exist
    And I press "Post reply"
    Then I should see "You have set the subject line to your login"
    And I should see "To continue, delete or change the subject"

    When I set the field "id_subject" to "W1234567"
    And I press "Post reply"
    Then I should see "You have set the subject line to your login"

    When I set the field "id_subject" to "Not abc123"
    And I press "Post reply"
    And I switch to the main frame
    Then I should see "xxx" in the ".forumng-replies" "css_element"
