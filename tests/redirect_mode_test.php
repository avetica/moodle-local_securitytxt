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
 * Unit tests for redirecting to an existing security.txt.
 *
 * @package     local_securitytxt
 * @copyright   2026 Avetica
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_securitytxt;

use local_securitytxt\task\check_expiry;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/adminlib.php');

/**
 * Tests for the mode choice and the redirect, covering test scenarios 8.1 to 8.7.
 *
 * @copyright   2026 Avetica
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_securitytxt\content_generator
 * @covers \local_securitytxt\admin_setting_mode
 * @covers \local_securitytxt\admin_setting_redirecturl
 * @covers \local_securitytxt\admin_setting_contact
 * @covers \local_securitytxt\admin_setting_expires
 * @covers \local_securitytxt\task\check_expiry
 *
 * Coverage targets stay doc-comment annotations rather than #[CoversClass] attributes: attributes
 * need PHPUnit 10+, while this plugin's supported floor (Moodle 4.5 LTS) still ships PHPUnit 9.
 */
final class redirect_mode_test extends \advanced_testcase {
    /** @var string An address an organisation could really publish its security.txt on. */
    private const EXTERNAL_URL = 'https://www.example.org/.well-known/security.txt';

    /**
     * Build the redirect URL setting under test.
     *
     * @return admin_setting_redirecturl
     */
    private function redirecturl_setting(): admin_setting_redirecturl {
        return new admin_setting_redirecturl('local_securitytxt/redirecturl', 'Redirect URL', '');
    }

    /**
     * Save the settings page the way admin/settings.php does, including the request it reads from.
     *
     * admin_setting_mode reads the other fields of the same save from the request, so the form data
     * is put in $_POST as well as handed to admin_write_settings().
     *
     * @param array $fields Setting name (without plugin prefix) => submitted value.
     * @return string[] Setting name => error message, for every field that was refused.
     */
    private function save_page(array $fields): array {
        $formdata = [];
        foreach ($fields as $name => $value) {
            $formdata['s_local_securitytxt_' . $name] = $value;
        }
        $_POST = $formdata;

        try {
            admin_write_settings($formdata);
        } finally {
            $_POST = [];
        }

        $errors = [];
        foreach (\admin_get_root()->errors as $fullname => $error) {
            $errors[substr($fullname, strlen('s_local_securitytxt_'))] = $error->error;
        }
        return $errors;
    }

    /**
     * A site publishing a working security.txt from its fields, as before any mode switch.
     */
    private function configure_working_fields_site(): void {
        set_config('mode', content_generator::MODE_FIELDS, 'local_securitytxt');
        set_config('contact', 'mailto:security@example.org', 'local_securitytxt');
        set_config('expires', '2099-12-31', 'local_securitytxt');
        set_config('redirecturl', '', 'local_securitytxt');
    }

    /**
     * A site forwarding to a working external security.txt, with its old fields emptied.
     */
    private function configure_working_redirect_site(): void {
        set_config('mode', content_generator::MODE_REDIRECT, 'local_securitytxt');
        set_config('redirecturl', self::EXTERNAL_URL, 'local_securitytxt');
        set_config('contact', '', 'local_securitytxt');
        set_config('expires', '', 'local_securitytxt');
    }

    /**
     * Spellings of this site's own security.txt address that all reach the same script.
     *
     * @return string[]
     */
    private function own_address_variants(): array {
        global $CFG;
        $parts = parse_url($CFG->wwwroot);
        $host = $parts['host'];
        $base = $parts['path'] ?? '';
        $wellknown = $base . '/.well-known/security.txt';

        return [
            'as configured' => $CFG->wwwroot . '/.well-known/security.txt',
            'https' => 'https://' . $host . $wellknown,
            'default port' => 'https://' . $host . ':443' . $wellknown,
            'upper-case host' => 'https://' . strtoupper($host) . $wellknown,
            'trailing slash' => 'https://' . $host . $wellknown . '/',
            'query and fragment' => 'https://' . $host . $wellknown . '?x=1#top',
            'double slash' => 'https://' . $host . $base . '//.well-known//security.txt',
            'encoded dot' => 'https://' . $host . $base . '/%2Ewell-known/security.txt',
            'plugin script' => 'https://' . $host . ':443' . $base . '/local/securitytxt/wellknown.php',
            'plugin script with path info' => 'https://' . $host . $base . '/local/securitytxt/wellknown.php/x',
        ];
    }

    /**
     * The settings page exactly as settings.php registers it.
     *
     * @return \admin_settingpage
     */
    private function settings_page(): \admin_settingpage {
        // The second argument builds the full tree; without it settings.php's fulltree block is skipped.
        return \admin_get_root(true, true)->locate('local_securitytxt');
    }

    /**
     * Test scenario 8.1 — a fresh or upgraded site stays in fields mode and serves its fields.
     */
    public function test_scenario_8_1_fields_mode_is_the_default(): void {
        $this->resetAfterTest();
        set_config('contact', 'mailto:security@example.org', 'local_securitytxt');
        set_config('expires', '2099-12-31', 'local_securitytxt');

        $this->assertSame(content_generator::MODE_FIELDS, get_config('local_securitytxt', 'mode'));
        $this->assertFalse(content_generator::is_redirect_mode());
        $this->assertNull(content_generator::get_redirect_url());
        $this->assertNotNull(content_generator::generate());
    }

    /**
     * Test scenario 8.1 — a site upgraded before the mode setting existed still counts as fields mode.
     */
    public function test_scenario_8_1_missing_mode_counts_as_fields(): void {
        $this->resetAfterTest();
        unset_config('mode', 'local_securitytxt');

        $this->assertFalse(content_generator::is_redirect_mode());
    }

    /**
     * Test scenario 8.2 — in redirect mode the configured address is what visitors are sent to.
     */
    public function test_scenario_8_2_redirect_mode_returns_the_url(): void {
        $this->resetAfterTest();
        set_config('mode', content_generator::MODE_REDIRECT, 'local_securitytxt');
        set_config('redirecturl', self::EXTERNAL_URL, 'local_securitytxt');

        $this->assertTrue(content_generator::is_redirect_mode());
        $this->assertSame(self::EXTERNAL_URL, content_generator::get_redirect_url());
    }

    /**
     * Test scenario 8.2 — a URL stored while in fields mode is never used.
     */
    public function test_scenario_8_2_fields_mode_ignores_a_stored_url(): void {
        $this->resetAfterTest();
        set_config('mode', content_generator::MODE_FIELDS, 'local_securitytxt');
        set_config('redirecturl', self::EXTERNAL_URL, 'local_securitytxt');

        $this->assertNull(content_generator::get_redirect_url());
    }

    /**
     * Test scenario 8.3 — in redirect mode empty Contact and Expires no longer block saving.
     */
    public function test_scenario_8_3_hidden_fields_are_not_required(): void {
        $this->resetAfterTest();
        set_config('mode', content_generator::MODE_REDIRECT, 'local_securitytxt');

        $contact = new admin_setting_contact('local_securitytxt/contact', 'Contact', '', '', PARAM_RAW);
        $expires = new admin_setting_expires('local_securitytxt/expires', 'Expires', '');

        $this->assertSame('', $contact->write_setting(''));
        $this->assertSame('', $expires->write_setting(''));
    }

    /**
     * Test scenario 8.3 — an Expires date that passed after switching to redirect mode does not block saving.
     */
    public function test_scenario_8_3_past_expires_does_not_block_redirect(): void {
        $this->resetAfterTest();
        set_config('mode', content_generator::MODE_REDIRECT, 'local_securitytxt');

        $expires = new admin_setting_expires('local_securitytxt/expires', 'Expires', '');

        $this->assertSame('', $expires->write_setting('2000-01-01'));
    }

    /**
     * Test scenario 8.3 — back in fields mode the usual rules apply again.
     */
    public function test_scenario_8_3_fields_mode_still_requires_contact(): void {
        $this->resetAfterTest();
        set_config('mode', content_generator::MODE_FIELDS, 'local_securitytxt');

        $contact = new admin_setting_contact('local_securitytxt/contact', 'Contact', '', '', PARAM_RAW);

        $this->assertSame(get_string('error_contactrequired', 'local_securitytxt'), $contact->write_setting(''));
    }

    /**
     * Test scenario 8.4 — redirect mode without an address is refused.
     */
    public function test_scenario_8_4_empty_url_is_refused(): void {
        $this->resetAfterTest();
        set_config('mode', content_generator::MODE_REDIRECT, 'local_securitytxt');

        $result = $this->redirecturl_setting()->write_setting('   ');

        $this->assertSame(get_string('error_redirecturlrequired', 'local_securitytxt'), $result);
        // Nothing but the empty install default is stored.
        $this->assertSame('', get_config('local_securitytxt', 'redirecturl'));
    }

    /**
     * Test scenario 8.4 — anything but a complete https address is refused.
     *
     * @dataProvider invalid_url_provider
     * @param string $url The submitted value.
     */
    public function test_scenario_8_4_non_https_url_is_refused(string $url): void {
        $this->resetAfterTest();
        set_config('mode', content_generator::MODE_REDIRECT, 'local_securitytxt');

        $result = $this->redirecturl_setting()->write_setting($url);

        $this->assertSame(get_string('error_redirecturlhttps', 'local_securitytxt'), $result);
        // Nothing but the empty install default is stored.
        $this->assertSame('', get_config('local_securitytxt', 'redirecturl'));
    }

    /**
     * Values that must never become a redirect target.
     *
     * @return array[]
     */
    public static function invalid_url_provider(): array {
        return [
            'plain http' => ['http://www.example.org/.well-known/security.txt'],
            'no scheme' => ['www.example.org/.well-known/security.txt'],
            'relative path' => ['/.well-known/security.txt'],
            'other scheme' => ['ftp://www.example.org/security.txt'],
            'no host' => ['https://'],
            'javascript' => ['javascript:alert(1)'],
        ];
    }

    /**
     * Test scenario 8.4 — pointing at this site's own security.txt is refused in any spelling, it would loop.
     */
    public function test_scenario_8_4_own_address_is_refused(): void {
        $this->resetAfterTest();
        set_config('mode', content_generator::MODE_REDIRECT, 'local_securitytxt');

        foreach ($this->own_address_variants() as $label => $url) {
            $this->assertTrue(content_generator::is_own_endpoint($url), $label);

            // A spelling PARAM_URL already rejects (a doubled slash) is refused as malformed before the
            // own-address check runs; every other spelling gets the message that explains the loop.
            $expected = content_generator::is_valid_redirect_url($url) ? 'error_redirecturlself' : 'error_redirecturlhttps';
            $result = $this->redirecturl_setting()->write_setting($url);
            $this->assertSame(get_string($expected, 'local_securitytxt'), $result, $label);
        }
        $this->assertSame('', get_config('local_securitytxt', 'redirecturl'));
    }

    /**
     * Test scenario 8.4 — another file on the same host, or the same path on another host, is not this site.
     */
    public function test_scenario_8_4_other_address_on_same_host_is_accepted(): void {
        global $CFG;
        $this->resetAfterTest();
        $host = parse_url($CFG->wwwroot, PHP_URL_HOST);

        foreach (['https://' . $host . '/security/security.txt', 'https://other.' . $host . '/.well-known/security.txt'] as $url) {
            $this->assertFalse(content_generator::is_own_endpoint($url), $url);
            $this->assertNull(content_generator::check_redirect_url($url), $url);
        }
    }

    /**
     * Test scenario 8.4 — a valid https address is stored.
     */
    public function test_scenario_8_4_valid_url_is_stored(): void {
        $this->resetAfterTest();
        set_config('mode', content_generator::MODE_REDIRECT, 'local_securitytxt');

        $this->assertSame('', $this->redirecturl_setting()->write_setting(self::EXTERNAL_URL));
        $this->assertSame(self::EXTERNAL_URL, get_config('local_securitytxt', 'redirecturl'));
    }

    /**
     * Test scenario 8.5 — a broken URL set outside the form (CLI, forced settings) is never redirected to.
     */
    public function test_scenario_8_5_invalid_stored_url_is_not_used(): void {
        $this->resetAfterTest();
        set_config('mode', content_generator::MODE_REDIRECT, 'local_securitytxt');

        foreach (['', 'http://www.example.org/security.txt', "https://www.example.org/\r\nSet-Cookie: x=1"] as $url) {
            set_config('redirecturl', $url, 'local_securitytxt');
            $this->assertNull(content_generator::get_redirect_url(), var_export($url, true));
        }
    }

    /**
     * Test scenario 8.5 — this site's own address set outside the form is never redirected to either.
     */
    public function test_scenario_8_5_own_address_set_outside_the_form_is_not_used(): void {
        $this->resetAfterTest();
        set_config('mode', content_generator::MODE_REDIRECT, 'local_securitytxt');

        foreach ($this->own_address_variants() as $label => $url) {
            set_config('redirecturl', $url, 'local_securitytxt');
            $this->assertNull(content_generator::get_redirect_url(), $label);
        }
    }

    /**
     * Test scenario 8.4 — switching to redirect mode with a valid address, through the full save.
     */
    public function test_scenario_8_4_switch_to_redirect_with_valid_url_is_saved(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->configure_working_fields_site();

        $errors = $this->save_page([
            'mode' => content_generator::MODE_REDIRECT,
            'redirecturl' => self::EXTERNAL_URL,
            'contact' => 'mailto:security@example.org',
            'expires' => '2099-12-31',
        ]);

        $this->assertSame([], $errors);
        $this->assertSame(content_generator::MODE_REDIRECT, get_config('local_securitytxt', 'mode'));
        $this->assertSame(self::EXTERNAL_URL, content_generator::get_redirect_url());
    }

    /**
     * Test scenario 8.4 — a refused switch to redirect mode keeps the working security.txt online.
     *
     * @dataProvider refused_switch_provider
     * @param string $url The submitted address, a placeholder for this site's own one.
     * @param string $urlerror The language string the address field must show.
     */
    public function test_scenario_8_4_refused_switch_keeps_fields_mode(string $url, string $urlerror): void {
        global $CFG;
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->configure_working_fields_site();
        $before = content_generator::generate();
        $url = str_replace('{own}', 'https://' . parse_url($CFG->wwwroot, PHP_URL_HOST) . ':443'
            . parse_url($CFG->wwwroot, PHP_URL_PATH) . '/.well-known/security.txt', $url);

        $errors = $this->save_page([
            'mode' => content_generator::MODE_REDIRECT,
            'redirecturl' => $url,
            'contact' => 'mailto:security@example.org',
            'expires' => '2099-12-31',
        ]);

        $this->assertSame(get_string('error_modeunchanged', 'local_securitytxt'), $errors['mode'] ?? null);
        $this->assertSame(get_string($urlerror, 'local_securitytxt'), $errors['redirecturl'] ?? null);
        $this->assertSame(content_generator::MODE_FIELDS, get_config('local_securitytxt', 'mode'));
        $this->assertSame('', get_config('local_securitytxt', 'redirecturl'));
        $this->assertNull(content_generator::get_redirect_url());
        $this->assertNotNull($before);
        $this->assertSame($before, content_generator::generate());
    }

    /**
     * Addresses a switch to redirect mode must be refused with.
     *
     * @return array[]
     */
    public static function refused_switch_provider(): array {
        return [
            'empty' => ['', 'error_redirecturlrequired'],
            'plain http' => ['http://www.example.org/.well-known/security.txt', 'error_redirecturlhttps'],
            'own address with default port' => ['{own}', 'error_redirecturlself'],
        ];
    }

    /**
     * Test scenario 8.4 — switching back to fields mode with the fields still empty keeps the redirect working.
     */
    public function test_scenario_8_4_refused_switch_back_keeps_redirect_mode(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->configure_working_redirect_site();

        $errors = $this->save_page([
            'mode' => content_generator::MODE_FIELDS,
            'redirecturl' => self::EXTERNAL_URL,
            'contact' => '',
            'expires' => '',
        ]);

        $this->assertSame(get_string('error_modeunchanged', 'local_securitytxt'), $errors['mode'] ?? null);
        $this->assertSame(get_string('error_contactrequired', 'local_securitytxt'), $errors['contact'] ?? null);
        $this->assertSame(get_string('error_expiresrequired', 'local_securitytxt'), $errors['expires'] ?? null);
        $this->assertSame(content_generator::MODE_REDIRECT, get_config('local_securitytxt', 'mode'));
        $this->assertSame(self::EXTERNAL_URL, content_generator::get_redirect_url());
    }

    /**
     * Test scenario 8.4 — switching back to fields mode with valid fields is saved and served.
     */
    public function test_scenario_8_4_switch_back_with_valid_fields_is_saved(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->configure_working_redirect_site();

        $errors = $this->save_page([
            'mode' => content_generator::MODE_FIELDS,
            'redirecturl' => self::EXTERNAL_URL,
            'contact' => 'mailto:security@example.org',
            'expires' => '2099-12-31',
        ]);

        $this->assertSame([], $errors);
        $this->assertSame(content_generator::MODE_FIELDS, get_config('local_securitytxt', 'mode'));
        $this->assertNull(content_generator::get_redirect_url());
        $this->assertStringContainsString('Contact: mailto:security@example.org', content_generator::generate());
    }

    /**
     * Test scenario 8.6 — in redirect mode the expiry task sends nothing, even for an old stored date.
     */
    public function test_scenario_8_6_expiry_task_is_silent_in_redirect_mode(): void {
        $this->resetAfterTest();
        set_config('mode', content_generator::MODE_REDIRECT, 'local_securitytxt');
        set_config('expires', date('Y-m-d', strtotime('-5 days')), 'local_securitytxt');

        $sink = $this->redirectMessages();
        (new check_expiry())->execute();
        $messages = $sink->get_messages();
        $sink->close();

        $this->assertCount(0, $messages);
        $this->assertFalse(get_config('local_securitytxt', 'notified_expired'));
    }

    /**
     * Test scenario 8.7 — the mode choice comes first, the URL and the fields depend on it.
     *
     * The fields below read as depending on the choice above them; which rules apply to them follows
     * the mode chosen in the same save (admin_setting_mode::chosen()), not the page order.
     */
    public function test_scenario_8_7_mode_comes_first_and_drives_visibility(): void {
        $this->resetAfterTest();
        $page = $this->settings_page();

        $names = array_values(array_map(static fn(\admin_setting $s): string => $s->name, (array) $page->settings));
        $this->assertSame('mode', $names[0]);
        $this->assertSame('redirecturl', $names[1]);

        // Read the registered hide_if rules; the property has no public getter on every supported version.
        $property = new \ReflectionProperty(\admin_settingpage::class, 'dependencies');
        $property->setAccessible(true);
        $rules = [];
        foreach ($property->getValue($page) as $dependency) {
            $rules[$dependency->settingname] = [$dependency->dependenton, $dependency->condition, $dependency->value];
        }

        $this->assertSame(
            ['s_local_securitytxt_mode', 'neq', content_generator::MODE_REDIRECT],
            $rules['s_local_securitytxt_redirecturl']
        );
        foreach (['contact', 'expires', 'encryption', 'preferredlanguages', 'canonical', 'policy'] as $field) {
            $this->assertSame(
                ['s_local_securitytxt_mode', 'eq', content_generator::MODE_REDIRECT],
                $rules['s_local_securitytxt_' . $field],
                $field
            );
        }
    }
}
