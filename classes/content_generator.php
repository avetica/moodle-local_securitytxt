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
 * Builds the RFC 9116 security.txt body from the stored plugin configuration.
 *
 * @package     local_securitytxt
 * @copyright   2026 Avetica
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_securitytxt;

/**
 * Generates the security.txt content shared by wellknown.php and the unit tests.
 *
 * @copyright   2026 Avetica
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class content_generator {
    /** @var string Field separator required by RFC 9116 (CRLF, not LF). */
    public const EOL = "\r\n";

    /** @var array Optional single-value settings, in the RFC 9116 output order. */
    private const OPTIONAL_FIELDS = [
        'encryption' => 'Encryption',
        'preferredlanguages' => 'Preferred-Languages',
        'canonical' => 'Canonical',
        'policy' => 'Policy',
    ];

    /**
     * Build the full security.txt body.
     *
     * @return string|null The file content, or null while Contact or Expires is still missing.
     */
    public static function generate(): ?string {
        $contacts = self::get_contacts();
        $expires = trim((string) get_config('local_securitytxt', 'expires'));

        if (empty($contacts) || $expires === '') {
            return null;
        }

        $lines = [];
        foreach ($contacts as $contact) {
            $lines[] = 'Contact: ' . $contact;
        }
        $lines[] = 'Expires: ' . self::format_expires($expires);

        foreach (self::OPTIONAL_FIELDS as $setting => $field) {
            // A line break inside one of these values would add a whole extra field to the file, so
            // it is collapsed to a space here rather than in the form: a single-line input already
            // strips one, but a value set from CLI or $CFG->forced_plugin_settings never passes
            // through the form at all. A space keeps the damage visible instead of silently gluing
            // two values into one that still looks valid.
            $value = trim(preg_replace('/\R+/', ' ', (string) get_config('local_securitytxt', $setting)));
            if ($value !== '') {
                $lines[] = $field . ': ' . $value;
            }
        }

        return implode(self::EOL, $lines) . self::EOL;
    }

    /**
     * Split the stored Contact setting into one value per line, dropping blank lines.
     *
     * @return string[] The configured contact values.
     */
    public static function get_contacts(): array {
        $raw = (string) get_config('local_securitytxt', 'contact');
        $contacts = [];

        foreach (preg_split('/\R/', $raw) as $line) {
            $line = trim($line);
            if ($line !== '') {
                $contacts[] = $line;
            }
        }

        return $contacts;
    }

    /**
     * Convert the stored calendar date to the full RFC 3339 timestamp RFC 9116 section 2.5.5 requires.
     *
     * The admin picks a date; scanners reject a bare YYYY-MM-DD value, so it is always served as the
     * end of that day in UTC.
     *
     * @param string $expires The stored date in YYYY-MM-DD format.
     * @return string The RFC 3339 timestamp, e.g. 2027-09-14T23:59:59Z.
     */
    public static function format_expires(string $expires): string {
        return trim($expires) . 'T23:59:59Z';
    }
}
