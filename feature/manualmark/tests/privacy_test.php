<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Test data provider for forumngfeature_manualmark.
 *
 * @package forumngfeature_manualmark
 * @copyright 2018 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;

use core_privacy\tests\provider_testcase;
use core_privacy\local\request\writer;
use forumngfeature_manualmark\privacy\provider;

/**
 * Data provider testcase class.
 *
 * @package forumngfeature_manualmark
 * @copyright 2018 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class forumngfeature_manualmark_privacy_testcase extends provider_testcase {

    public function setUp() {
        $this->resetAfterTest();
    }

    /**
     * Ensure that export_user_preferences returns no data if the user has no data.
     *
     * @throws coding_exception
     */
    public function test_export_user_preferences_not_defined() {
        $user = $this->getDataGenerator()->create_user();
        provider::export_user_preferences($user->id);

        $writer = writer::with_context(context_user::instance($user->id));
        $this->assertFalse($writer->has_any_data());
    }

    /**
     * Ensure that export_user_preferences returns single preferences.
     *
     * @throws coding_exception
     */
    public function test_export_user_preferences_single() {
        // Define a user preference.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        set_user_preference('forumng_manualmark', 1);

        // Validate exported data.
        provider::export_user_preferences($user->id);
        $context = context_user::instance($user->id);

        $writer = writer::with_context($context);
        $this->assertTrue($writer->has_any_data());
        $prefs = $writer->get_user_preferences('forumngfeature_manualmark');
        $this->assertCount(1, (array) $prefs);
        $this->assertEquals('Yes', $prefs->forumng_manualmark->value);
        $this->assertEquals(1, get_user_preferences('forumng_manualmark', null, $user));
        $this->assertEquals(
                get_string('privacy:metadata:preference:forumng_manualmark', 'forumngfeature_manualmark'),
                $prefs->forumng_manualmark->description
        );
    }

}
