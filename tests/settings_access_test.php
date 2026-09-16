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
 * Unit tests for access to the settings page.
 *
 * @package     local_securitytxt
 * @copyright   2026 Avetica
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_securitytxt;

/**
 * Tests test scenario 6.1: the settings page is gated on local/securitytxt:manage.
 *
 * @copyright   2026 Avetica
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * This checks the capability wiring in db/access.php and settings.php rather than a single class.
 *
 * @coversNothing
 */
final class settings_access_test extends \advanced_testcase {
    /**
     * Test scenario 6.1 — an ordinary user holds neither the capability nor access to the page.
     */
    public function test_scenario_6_1_plain_user_is_denied(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $this->assertFalse(has_capability('local/securitytxt:manage', \context_system::instance()));
    }

    /**
     * A manager does hold the capability, so the same page is reachable for them.
     */
    public function test_manager_is_allowed(): void {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $managerroleid = $DB->get_field('role', 'id', ['shortname' => 'manager'], MUST_EXIST);
        role_assign($managerroleid, $user->id, \context_system::instance()->id);
        $this->setUser($user);

        $this->assertTrue(has_capability('local/securitytxt:manage', \context_system::instance()));
    }

    /**
     * Test scenario 6.1 — the settings page itself carries the capability, so Moodle refuses a user
     * who lacks it rather than relying on the page being hidden from the menu.
     */
    public function test_scenario_6_1_settings_page_requires_the_capability(): void {
        global $CFG;
        $this->resetAfterTest();
        require_once($CFG->libdir . '/adminlib.php');

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $page = \admin_get_root(true, false)->locate('local_securitytxt');

        $this->assertInstanceOf(\admin_settingpage::class, $page);
        $this->assertFalse($page->check_access());
    }
}
