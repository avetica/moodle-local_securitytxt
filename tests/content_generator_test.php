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
 * Unit tests for the RFC 9116 content generation.
 *
 * @package     local_securitytxt
 * @copyright   2026 Avetica
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_securitytxt;

/**
 * Tests for {@see content_generator}, covering test scenarios 4.1, 4.1b and the precondition of 4.2.
 *
 * @copyright   2026 Avetica
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_securitytxt\content_generator
 *
 * Coverage target stays a doc-comment annotation rather than a #[CoversClass] attribute: attributes
 * need PHPUnit 10+, while this plugin's supported floor (Moodle 4.5 LTS) still ships PHPUnit 9.
 */
final class content_generator_test extends \advanced_testcase {
    /**
     * Test scenario 4.1 — a fully configured site produces the fields in RFC 9116 order.
     */
    public function test_scenario_4_1_fields_in_rfc_order(): void {
        $this->resetAfterTest();

        set_config('contact', 'mailto:security@klant.nl', 'local_securitytxt');
        set_config('expires', '2027-09-14', 'local_securitytxt');
        set_config('encryption', 'https://klant.nl/pgp-key.txt', 'local_securitytxt');
        set_config('preferredlanguages', 'nl, en', 'local_securitytxt');
        set_config('canonical', 'https://klant.nl/.well-known/security.txt', 'local_securitytxt');
        set_config('policy', 'https://klant.nl/responsible-disclosure', 'local_securitytxt');

        $content = content_generator::generate();

        $expected = "Contact: mailto:security@klant.nl\r\n"
            . "Expires: 2027-09-14T23:59:59Z\r\n"
            . "Encryption: https://klant.nl/pgp-key.txt\r\n"
            . "Preferred-Languages: nl, en\r\n"
            . "Canonical: https://klant.nl/.well-known/security.txt\r\n"
            . "Policy: https://klant.nl/responsible-disclosure\r\n";

        $this->assertSame($expected, $content);
    }

    /**
     * Test scenario 4.1 — line endings are CRLF, never bare LF.
     */
    public function test_scenario_4_1_uses_crlf_line_endings(): void {
        $this->resetAfterTest();

        set_config('contact', 'mailto:security@klant.nl', 'local_securitytxt');
        set_config('expires', '2027-09-14', 'local_securitytxt');

        $content = content_generator::generate();

        $this->assertStringContainsString("\r\n", $content);
        $this->assertSame(0, preg_match('/(?<!\r)\n/', $content), 'Found a bare LF without a preceding CR.');
    }

    /**
     * Test scenario 4.1 — Expires is served as a full RFC 3339 UTC timestamp, not a bare date.
     */
    public function test_scenario_4_1_expires_is_rfc3339(): void {
        $this->resetAfterTest();

        set_config('contact', 'mailto:security@klant.nl', 'local_securitytxt');
        set_config('expires', '2027-09-14', 'local_securitytxt');

        $content = content_generator::generate();

        $this->assertStringContainsString('Expires: 2027-09-14T23:59:59Z', $content);
        $this->assertDoesNotMatchRegularExpression('/Expires: \d{4}-\d{2}-\d{2}\r/', $content);
    }

    /**
     * Test scenario 4.1b — every configured contact gets its own Contact: line.
     */
    public function test_scenario_4_1b_multiple_contacts_on_separate_lines(): void {
        $this->resetAfterTest();

        set_config('contact', "mailto:security@klant.nl\nhttps://klant.nl/meldformulier", 'local_securitytxt');
        set_config('expires', '2027-09-14', 'local_securitytxt');

        $content = content_generator::generate();

        $this->assertStringContainsString("Contact: mailto:security@klant.nl\r\n", $content);
        $this->assertStringContainsString("Contact: https://klant.nl/meldformulier\r\n", $content);
        $this->assertSame(2, substr_count($content, 'Contact: '));
    }

    /**
     * Test scenario 4.1b — blank lines between contacts do not produce empty Contact: lines.
     */
    public function test_scenario_4_1b_blank_lines_between_contacts_are_dropped(): void {
        $this->resetAfterTest();

        set_config('contact', "mailto:security@klant.nl\n\n   \nhttps://klant.nl/meldformulier\n", 'local_securitytxt');
        set_config('expires', '2027-09-14', 'local_securitytxt');

        $content = content_generator::generate();

        $this->assertSame(2, substr_count($content, 'Contact: '));
        $this->assertStringNotContainsString("Contact: \r\n", $content);
    }

    /**
     * Test scenario 4.2 (precondition) — nothing is generated before the plugin is configured.
     */
    public function test_scenario_4_2_returns_null_without_configuration(): void {
        $this->resetAfterTest();

        $this->assertNull(content_generator::generate());
    }

    /**
     * Test scenario 4.2 (precondition) — a missing Expires alone already blocks generation.
     */
    public function test_scenario_4_2_returns_null_without_expires(): void {
        $this->resetAfterTest();

        set_config('contact', 'mailto:security@klant.nl', 'local_securitytxt');

        $this->assertNull(content_generator::generate());
    }

    /**
     * Test scenario 4.2 (precondition) — a missing Contact alone already blocks generation.
     */
    public function test_scenario_4_2_returns_null_without_contact(): void {
        $this->resetAfterTest();

        set_config('expires', '2027-09-14', 'local_securitytxt');

        $this->assertNull(content_generator::generate());
    }

    /**
     * Test scenario 3.1b — a Contact of only whitespace counts as empty, so nothing is generated.
     */
    public function test_scenario_3_1b_whitespace_only_contact_counts_as_empty(): void {
        $this->resetAfterTest();

        set_config('contact', "   \n\t\n ", 'local_securitytxt');
        set_config('expires', '2027-09-14', 'local_securitytxt');

        $this->assertNull(content_generator::generate());
    }

    /**
     * Optional fields that are left empty do not appear in the output at all.
     */
    public function test_empty_optional_fields_are_omitted(): void {
        $this->resetAfterTest();

        set_config('contact', 'mailto:security@klant.nl', 'local_securitytxt');
        set_config('expires', '2027-09-14', 'local_securitytxt');
        set_config('encryption', '', 'local_securitytxt');
        set_config('policy', '   ', 'local_securitytxt');
        // Canonical ships with a default value from settings.php, so clear it explicitly here.
        set_config('canonical', '', 'local_securitytxt');

        $content = content_generator::generate();

        $this->assertStringNotContainsString('Encryption:', $content);
        $this->assertStringNotContainsString('Policy:', $content);
        $this->assertSame("Contact: mailto:security@klant.nl\r\nExpires: 2027-09-14T23:59:59Z\r\n", $content);
    }

    /**
     * A line break inside an optional value can never add a field of its own to the file.
     *
     * Not reachable through the settings form, where a single-line input drops the break, but a
     * value written from the CLI or forced through $CFG->forced_plugin_settings bypasses the form.
     */
    public function test_line_breaks_in_optional_fields_cannot_inject_a_field(): void {
        $this->resetAfterTest();

        set_config('contact', 'mailto:security@klant.nl', 'local_securitytxt');
        set_config('expires', '2027-09-14', 'local_securitytxt');
        set_config(
            'canonical',
            "https://klant.nl/.well-known/security.txt\r\nContact: mailto:aanvaller@evil.test",
            'local_securitytxt'
        );

        $content = content_generator::generate();

        // The injected text survives as part of the Canonical value, which is the admin's own typo to
        // fix; what must never happen is it becoming a Contact field of its own.
        $lines = explode(content_generator::EOL, trim($content));
        $contactlines = array_filter($lines, static fn($line) => str_starts_with($line, 'Contact:'));
        $this->assertCount(1, $contactlines);

        $this->assertSame(
            "Contact: mailto:security@klant.nl\r\n"
            . "Expires: 2027-09-14T23:59:59Z\r\n"
            . "Canonical: https://klant.nl/.well-known/security.txt Contact: mailto:aanvaller@evil.test\r\n",
            $content
        );
    }
}
