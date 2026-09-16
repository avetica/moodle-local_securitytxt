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
 * Unit tests for the settings validation.
 *
 * @package     local_securitytxt
 * @copyright   2026 Avetica
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_securitytxt;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/adminlib.php');

/**
 * Tests for the required Contact and Expires fields, covering test scenarios 3.1 to 3.3 and 5.4.
 *
 * @copyright   2026 Avetica
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_securitytxt\admin_setting_expires
 * @covers \local_securitytxt\admin_setting_contact
 *
 * Coverage targets stay doc-comment annotations rather than #[CoversClass] attributes: attributes
 * need PHPUnit 10+, while this plugin's supported floor (Moodle 4.5 LTS) still ships PHPUnit 9.
 */
final class settings_validation_test extends \advanced_testcase {
    /**
     * Build the Contact setting under test.
     *
     * @return admin_setting_contact
     */
    private function contact_setting(): admin_setting_contact {
        return new admin_setting_contact('local_securitytxt/contact', 'Contact', '', '', PARAM_RAW);
    }

    /**
     * Build the Expires setting under test.
     *
     * @return admin_setting_expires
     */
    private function expires_setting(): admin_setting_expires {
        return new admin_setting_expires('local_securitytxt/expires', 'Expires', '');
    }

    /**
     * Test scenario 3.1 — an empty Contact is refused and nothing is stored.
     */
    public function test_scenario_3_1_empty_contact_is_refused(): void {
        $this->resetAfterTest();

        $result = $this->contact_setting()->write_setting('');

        $this->assertIsString($result);
        $this->assertNotSame('', $result);
        $this->assertFalse(get_config('local_securitytxt', 'contact'));
    }

    /**
     * Test scenario 3.1b — a Contact of only whitespace is treated the same as empty.
     */
    public function test_scenario_3_1b_whitespace_contact_is_refused(): void {
        $this->resetAfterTest();

        $result = $this->contact_setting()->write_setting("   \n\t  \n ");

        $this->assertIsString($result);
        $this->assertNotSame('', $result);
        $this->assertFalse(get_config('local_securitytxt', 'contact'));
    }

    /**
     * A valid Contact is accepted and stored.
     */
    public function test_valid_contact_is_stored(): void {
        $this->resetAfterTest();

        $result = $this->contact_setting()->write_setting('mailto:security@klant.nl');

        $this->assertSame('', $result);
        $this->assertSame('mailto:security@klant.nl', get_config('local_securitytxt', 'contact'));
    }

    /**
     * Test scenario 3.2 — an empty Expires is refused and nothing is stored.
     */
    public function test_scenario_3_2_empty_expires_is_refused(): void {
        $this->resetAfterTest();

        $result = $this->expires_setting()->write_setting('');

        $this->assertIsString($result);
        $this->assertNotSame('', $result);
        $this->assertFalse(get_config('local_securitytxt', 'expires'));
    }

    /**
     * Test scenario 3.3 — a past Expires date is refused and nothing is stored.
     */
    public function test_scenario_3_3_past_expires_is_refused(): void {
        $this->resetAfterTest();

        $result = $this->expires_setting()->write_setting(date('Y-m-d', strtotime('-1 day')));

        $this->assertIsString($result);
        $this->assertNotSame('', $result);
        $this->assertFalse(get_config('local_securitytxt', 'expires'));
    }

    /**
     * Test scenario 3.3 — today still counts as valid, because the date runs until end of day.
     */
    public function test_today_is_still_accepted(): void {
        $this->resetAfterTest();

        $today = gmdate('Y-m-d');
        $result = $this->expires_setting()->write_setting($today);

        $this->assertSame('', $result);
        $this->assertSame($today, get_config('local_securitytxt', 'expires'));
    }

    /**
     * A value that is not a calendar date at all is refused.
     */
    public function test_malformed_expires_is_refused(): void {
        $this->resetAfterTest();

        foreach (['not-a-date', '2027-13-01', '2027-02-30', '14-09-2027'] as $value) {
            $result = $this->expires_setting()->write_setting($value);

            $this->assertIsString($result, "Value {$value} should be refused");
            $this->assertNotSame('', $result, "Value {$value} should be refused");
        }

        $this->assertFalse(get_config('local_securitytxt', 'expires'));
    }

    /**
     * Test scenario 1.1 — a valid future date is accepted and stored.
     */
    public function test_scenario_1_1_valid_expires_is_stored(): void {
        $this->resetAfterTest();

        $future = date('Y-m-d', strtotime('+1 year'));
        $result = $this->expires_setting()->write_setting($future);

        $this->assertSame('', $result);
        $this->assertSame($future, get_config('local_securitytxt', 'expires'));
    }

    /**
     * Test scenario 5.4 — storing a new date clears both notification flags.
     */
    public function test_scenario_5_4_new_date_resets_notification_flags(): void {
        $this->resetAfterTest();

        set_config('expires', date('Y-m-d', strtotime('+10 days')), 'local_securitytxt');
        set_config('notified_expiring', 1, 'local_securitytxt');
        set_config('notified_expired', 1, 'local_securitytxt');

        $this->expires_setting()->write_setting(date('Y-m-d', strtotime('+1 year')));

        $this->assertSame('0', get_config('local_securitytxt', 'notified_expiring'));
        $this->assertSame('0', get_config('local_securitytxt', 'notified_expired'));
    }

    /**
     * Test scenario 5.4 — saving the same date again leaves the flags alone, so a warning that was
     * already sent is not repeated on the next cron run.
     */
    public function test_scenario_5_4_unchanged_date_keeps_notification_flags(): void {
        $this->resetAfterTest();

        $date = date('Y-m-d', strtotime('+10 days'));
        set_config('expires', $date, 'local_securitytxt');
        set_config('notified_expiring', 1, 'local_securitytxt');

        $this->expires_setting()->write_setting($date);

        $this->assertSame('1', get_config('local_securitytxt', 'notified_expiring'));
    }

    /**
     * Fetch a setting as settings.php actually registers it, rather than rebuilding it here.
     *
     * @param string $name The setting name without the component prefix.
     * @return \admin_setting The registered setting.
     */
    private function registered_setting(string $name): \admin_setting {
        // The second argument builds the full tree; without it settings.php's fulltree block is skipped.
        $page = \admin_get_root(true, true)->locate('local_securitytxt');

        foreach ($page->settings as $setting) {
            if ($setting->name === $name) {
                return $setting;
            }
        }

        $this->fail("Setting {$name} is not registered in settings.php");
    }

    /**
     * Test scenario 2.1 — Canonical is suggested from the site address before anything is saved.
     */
    public function test_scenario_2_1_canonical_defaults_to_site_address(): void {
        global $CFG;
        $this->resetAfterTest();

        $canonical = $this->registered_setting('canonical');

        $this->assertSame($CFG->wwwroot . '/.well-known/security.txt', $canonical->get_defaultsetting());
    }

    /**
     * Test scenario 2.2 — an overridden Canonical is returned as-is, never replaced by the default.
     */
    public function test_scenario_2_2_overridden_canonical_is_kept(): void {
        $this->resetAfterTest();

        set_config('canonical', 'https://eigendomein.nl/.well-known/security.txt', 'local_securitytxt');

        $canonical = $this->registered_setting('canonical');

        $this->assertSame('https://eigendomein.nl/.well-known/security.txt', $canonical->get_setting());
    }

    /**
     * Test scenario 1.1 — the Expires field is rendered as a date picker, not a free text box.
     */
    public function test_scenario_1_1_expires_renders_as_a_date_input(): void {
        $this->resetAfterTest();

        $html = $this->registered_setting('expires')->output_html('2027-09-14');

        $this->assertStringContainsString('type="date"', $html);
        $this->assertStringContainsString('value="2027-09-14"', $html);
    }

    /**
     * Test scenario 1.1 — the settings page sits under Site administration > Security.
     */
    public function test_scenario_1_1_page_is_registered_under_security(): void {
        $this->resetAfterTest();

        $security = \admin_get_root(true, false)->locate('security');

        $this->assertNotEmpty($security, 'The core security category is missing');
        $this->assertNotEmpty($security->locate('local_securitytxt'), 'The settings page is not under Security');
    }
}
