@mod @mod_forumng @ou @ou_vle @forumngfeature_usage
Feature: Show usage statistics for ForumNG
  In order view all readers of discussions
  As an administrator
  I need to use the forumng feature that shows readers

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | student1 | Student | 1 | student1@asd.com |
      | student2 | Student | 2 | student2@asd.com |
      | student3 | Student | 3 | student2@asd.com |
      | teacher1 | Teacher | 1 | teacher1@asd.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | teacher |
      | student1 | C1 | student |
      | student2 | C1 | student |
      | student3 | C1 | student |
    And the following "activities" exist:
      | activity | name                    | introduction                   | course | idnumber |
      | forumng  | Test forum name marking | Test forum marking description | C1     | forumng1 |
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I am on "Course 1" course homepage
    And I follow "Test forum name marking"
    And I add a discussion with the following data:
      | Subject | Discussion 1 |
      | Message | Discussion 1 |
    And I follow "Test forum name marking"
    And I add a discussion with the following data:
      | Subject | Discussion 2 |
      | Message | Discussion 2 |
    And I follow "Test forum name marking"
    And I add a discussion with the following data:
      | Subject | Discussion 3 |
      | Message | Discussion 3 |
    And I follow "Test forum name marking"
    And I log out

  # JS required or fails to find forumng_usage_list elements on the page
  @javascript
  Scenario: Testing the usage statistics report
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    Given I follow "Test forum name marking"
    Then I should see "Test forum marking description"
    And I add a discussion with the following data:
      | Subject | Discussion 4 |
      | Message | Discussion 4 by Student 1 |
    And I follow "Test forum name marking"

    # Discussions default to the un-read state
    # Read and automatically mark discussion
    And I follow "Discussion 1"
    And I reply to post "1" with the following data:
      | Message | REPLY-D1-S1 |
    And I wait "1" seconds
    And "Subscribe to discussion" "button" should exist
    Given I press "Subscribe to discussion"
    And I wait "1" seconds
    And I press "Flag discussion"
    Then I should see "Your email preferences"
    Given I follow "Test forum name marking"
    And I log out

    Given I log in as "student2"
    And I am on "Course 1" course homepage
    Given I follow "Test forum name marking"
     # Read and automatically mark discussion
    And I follow "Discussion 2"
    And I reply to post "1" with the following data:
      | Message | REPLY-D2-S2 |
    And I wait "1" seconds
    And I press "Flag discussion"
    Given I follow "Test forum name marking"
    And I follow "Discussion 3"
    And I reply to post "1" with the following data:
      | Message | REPLY-D3-S2 |
    And I wait "1" seconds
    And I press "Flag discussion"
    Given I follow "Test forum name marking"
    And I log out

    Given I log in as "student3"
    And I am on "Course 1" course homepage
    Given I follow "Test forum name marking"
    And I add a discussion with the following data:
      | Subject | Discussion 5 |
      | Message | Discussion 5 by Student 3 |
    And I follow "Test forum name marking"
    And I add a discussion with the following data:
      | Subject | Discussion 6 |
      | Message | Discussion 6 by Student 3 |
    And I follow "Test forum name marking"
    # Read and automatically mark both discussions
    And I follow "Discussion 1"
    And I reply to post "1" with the following data:
      | Message | REPLY-D2-S3 |
    And I wait "1" seconds
    And I click on ".forumng-p2 .forumng-flagpost a" "css_element"
    And I wait "1" seconds
    And "Subscribe to discussion" "button" should exist
    Given I press "Subscribe to discussion"
    Then I should see "Your email preferences"

    Given I follow "Test forum name marking"
    And I follow "Discussion 2"
    And I reply to post "1" with the following data:
      | Message | REPLY-D2-S3 |
    And I wait "1" seconds
    And I press "Flag discussion"
    Given I follow "Test forum name marking"
    And I follow "Discussion 4"
    And I reply to post "1" with the following data:
      | Message | REPLY-D4-S3 |
    And I wait "1" seconds
    And I press "Flag discussion"
    Given I follow "Test forum name marking"
    And I log out

    Given I log in as "admin"
    And I am on "Course 1" course homepage
    Given I follow "Test forum name marking"

    # Check the Show usage page
    And "Show usage" "button" should exist
    And I press "Show usage"
    And I should see "Usage" in the "h2" "css_element"
    And I should see "Contribution" in the ".forumng_usage_sectitle" "css_element"
    And I should see "Most posts" in the ".forumng_usage_listhead" "css_element"
    And I should see "Most discussions"

    # Check the MOST POSTS counts
    And I should see "3" in the ".forumng_usage_contrib .forumng_usage_contrib_cont:nth-child(1) .forumng_usage_list li:nth-child(1) .forumng_usage_list_tot" "css_element"
    And I should see "2" in the ".forumng_usage_contrib .forumng_usage_contrib_cont:nth-child(1) .forumng_usage_list li:nth-child(2) .forumng_usage_list_tot" "css_element"
    And I should see "1" in the ".forumng_usage_contrib .forumng_usage_contrib_cont:nth-child(1) .forumng_usage_list li:nth-child(3) .forumng_usage_list_tot" "css_element"
    # Check the MOST POSTS
    And I should see "Student 3" in the "div.forumng_usage_contrib_cont:nth-child(1) > ol:nth-child(2) > li:nth-child(1) > div:nth-child(3) > div:nth-child(1) > a:nth-child(1)" "css_element"
    And I should see "Student 2" in the "div.forumng_usage_contrib_cont:nth-child(1) > ol:nth-child(2) > li:nth-child(2) > div:nth-child(3) > div:nth-child(1) > a:nth-child(1)" "css_element"
    And I should see "Student 1" in the "div.forumng_usage_contrib_cont:nth-child(1) > ol:nth-child(2) > li:nth-child(3) > div:nth-child(3) > div:nth-child(1) > a:nth-child(1)" "css_element"

    # Check the MOST DISCUSSIONS counts
    And I should see "3" in the ".forumng_usage_contrib .forumng_usage_contrib_cont:nth-child(2) .forumng_usage_list:nth-child(2) li:nth-child(1) .forumng_usage_list_tot" "css_element"
    And I should see "2" in the ".forumng_usage_contrib .forumng_usage_contrib_cont:nth-child(2) .forumng_usage_list:nth-child(2) li:nth-child(2) .forumng_usage_list_tot" "css_element"
    And I should see "1" in the ".forumng_usage_contrib .forumng_usage_contrib_cont:nth-child(2) .forumng_usage_list:nth-child(2) li:nth-child(3) .forumng_usage_list_tot" "css_element"
    # Check the MOST DISCUSSION
    And I should see "Admin User" in the "div.forumng_usage_contrib_cont:nth-child(2) > ol:nth-child(2) > li:nth-child(1) > div:nth-child(3) > div:nth-child(1) > a:nth-child(1)" "css_element"
    And I should see "Student 3" in the "div.forumng_usage_contrib_cont:nth-child(2) > ol:nth-child(2) > li:nth-child(2) > div:nth-child(3) > div:nth-child(1) > a:nth-child(1)" "css_element"
    And I should see "Student 1" in the "div.forumng_usage_contrib_cont:nth-child(2) > ol:nth-child(2) > li:nth-child(3) > div:nth-child(3) > div:nth-child(1) > a:nth-child(1)" "css_element"

    And I should see "Post history - 12 posts" in the ".forumng_usage_usagechart" "css_element"
    And "Update post history" "button" should exist
    And ".forumng_usage_chart" "css_element" should exist
    And ".forumng_usage_subscribers" "css_element" should exist
    And I should see "Subscribers" in the ".forumng_usage_subscribers h4" "css_element"
    And I should see "Subscriber type"
    And I should see "Number"
    And I should see "Total subscribers"
    And I should see "2" in the "table.generaltable:nth-child(2) > tbody:nth-child(2) > tr:nth-child(1) > td:nth-child(2)" "css_element"

    And I should see "Whole forum subscribers"
    And I should see "Group forum subscribers"
    And I should see "Discussion subscribers"
    And I should see "2" in the "tr.lastrow:nth-child(4) > td:nth-child(2)" "css_element"

    And I should see "Most read discussions"
    And I should see "2" in the "ol.forumng_usage_list:nth-child(1) > li:nth-child(1) > div:nth-child(1)" "css_element"
    And I should see "Discussion 4" in the "ol.forumng_usage_list:nth-child(1) > li:nth-child(1) > div:nth-child(3) > div:nth-child(1) > a:nth-child(1)" "css_element"
    And I should see "Student 1" in the "ol.forumng_usage_list:nth-child(1) > li:nth-child(1) > div:nth-child(3) > div:nth-child(3) > a:nth-child(1)" "css_element"

    And I should see "2" in the "ol.forumng_usage_list:nth-child(1) > li:nth-child(2) > div:nth-child(1)" "css_element"
    And I should see "Discussion 2" in the "ol.forumng_usage_list:nth-child(1) > li:nth-child(2) > div:nth-child(3) > div:nth-child(1) > a:nth-child(1)" "css_element"
    And I should see "Admin User" in the "ol.forumng_usage_list:nth-child(1) > li:nth-child(2) > div:nth-child(3) > div:nth-child(3) > a:nth-child(1)" "css_element"

    And I should see "2" in the "ol.forumng_usage_list:nth-child(1) > li:nth-child(3) > div:nth-child(1)" "css_element"
    And I should see "Discussion 1" in the "ol.forumng_usage_list:nth-child(1) > li:nth-child(3) > div:nth-child(3) > div:nth-child(1) > a:nth-child(1)" "css_element"
    And I should see "Admin User" in the "ol.forumng_usage_list:nth-child(1) > li:nth-child(3) > div:nth-child(3) > div:nth-child(3) > a:nth-child(1)" "css_element"

    And I should see "1" in the "ol.forumng_usage_list:nth-child(1) > li:nth-child(4) > div:nth-child(1)" "css_element"
    And I should see "Discussion 6" in the "ol.forumng_usage_list:nth-child(1) > li:nth-child(4) > div:nth-child(3) > div:nth-child(1) > a:nth-child(1)" "css_element"
    And I should see "Student 3" in the "ol.forumng_usage_list:nth-child(1) > li:nth-child(4) > div:nth-child(3) > div:nth-child(3) > a:nth-child(1)" "css_element"
    And I should see "1" in the "ol.forumng_usage_list:nth-child(1) > li:nth-child(5) > div:nth-child(1)" "css_element"
    And I should see "Discussion 5" in the "ol.forumng_usage_list:nth-child(1) > li:nth-child(5) > div:nth-child(3) > div:nth-child(1) > a:nth-child(1)" "css_element"
    And I should see "Student 3" in the "ol.forumng_usage_list:nth-child(1) > li:nth-child(5) > div:nth-child(3) > div:nth-child(3) > a:nth-child(1)" "css_element"
    And I should see "Most flagged posts"
    And I should see "1" in the "div.forumng_usage_flagged:nth-child(5) > ol:nth-child(2) > li:nth-child(1) > div:nth-child(1)" "css_element"
    And I should see "Re: Discussion 1" in the "div.forumng_usage_flagged:nth-child(5) > ol:nth-child(2) > li:nth-child(1) > div:nth-child(3) > div:nth-child(1) > a:nth-child(1)" "css_element"
    And I should see "Student 1" in the "div.forumng_usage_flagged:nth-child(5) > ol:nth-child(2) > li:nth-child(1) > div:nth-child(3) > div:nth-child(3) > a:nth-child(1)" "css_element"

    And I should see "Most flagged discussions"
    And I should see "2" in the "div.forumng_usage_flagged:nth-child(6) > ol:nth-child(2) > li:nth-child(1) > div:nth-child(1)" "css_element"
    And I should see "Discussion 2" in the "div.forumng_usage_flagged:nth-child(6) > ol:nth-child(2) > li:nth-child(1) > div:nth-child(3) > div:nth-child(1) > a:nth-child(1)" "css_element"
    And I should see "Admin User" in the "div.forumng_usage_flagged:nth-child(6) > ol:nth-child(2) > li:nth-child(1) > div:nth-child(3) > div:nth-child(3) > a:nth-child(1)" "css_element"
    And I should see "1" in the "div.forumng_usage_flagged:nth-child(6) > ol:nth-child(2) > li:nth-child(2) > div:nth-child(1)" "css_element"
    And I should see "Discussion 4" in the "div.forumng_usage_flagged:nth-child(6) > ol:nth-child(2) > li:nth-child(2) > div:nth-child(3) > div:nth-child(1) > a:nth-child(1)" "css_element"
    And I should see "Student 1" in the "div.forumng_usage_flagged:nth-child(6) > ol:nth-child(2) > li:nth-child(2) > div:nth-child(3) > div:nth-child(3) > a:nth-child(1)" "css_element"
    And I should see "1" in the "div.forumng_usage_flagged:nth-child(6) > ol:nth-child(2) > li:nth-child(3) > div:nth-child(1)" "css_element"
    And I should see "Discussion 3" in the "div.forumng_usage_flagged:nth-child(6) > ol:nth-child(2) > li:nth-child(3) > div:nth-child(3) > div:nth-child(1) > a:nth-child(1)" "css_element"
    And I should see "Admin User" in the "div.forumng_usage_flagged:nth-child(6) > ol:nth-child(2) > li:nth-child(3) > div:nth-child(3) > div:nth-child(3) > a:nth-child(1)" "css_element"
    And I should see "1" in the "div.forumng_usage_flagged:nth-child(6) > ol:nth-child(2) > li:nth-child(4) > div:nth-child(1)" "css_element"
    And I should see "Discussion 1" in the "div.forumng_usage_flagged:nth-child(6) > ol:nth-child(2) > li:nth-child(4) > div:nth-child(3) > div:nth-child(1) > a:nth-child(1)" "css_element"
    And I should see "Admin User" in the "div.forumng_usage_flagged:nth-child(6) > ol:nth-child(2) > li:nth-child(4) > div:nth-child(3) > div:nth-child(3) > a:nth-child(1)" "css_element"

    And ".forumng_usage_readers" "css_element" should exist
    And ".forumngusageshowmostreaders" "css_element" should exist
    And ".forumng_usage_list_tot" "css_element" should exist
    And ".forumng_usage_list_pic" "css_element" should exist
    And ".forumng_usage_list_info" "css_element" should exist
    And ".forumng_usage_flagged" "css_element" should exist
    And I should not see "No posts flagged"
    And I should not see "No discussions flagged"
    And I log out
