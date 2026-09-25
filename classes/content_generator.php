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

    /** @var string Mode setting value: the file is built from the fields on the settings page. */
    public const MODE_FIELDS = 'fields';

    /** @var string Mode setting value: visitors are forwarded to a security.txt the organisation already has. */
    public const MODE_REDIRECT = 'redirect';

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
     * Whether the administrator chose to forward visitors to an existing security.txt.
     *
     * A site that upgraded from 1.0 before the mode setting existed has no stored value, which
     * counts as the fields mode it was already using.
     *
     * @return bool True in redirect mode.
     */
    public static function is_redirect_mode(): bool {
        return get_config('local_securitytxt', 'mode') === self::MODE_REDIRECT;
    }

    /**
     * The address visitors are forwarded to in redirect mode.
     *
     * The stored value is checked again here, not only in the form: a value set from CLI or
     * $CFG->forced_plugin_settings never passes through the form, and a broken one must not end up
     * in a Location header.
     *
     * @return string|null The https URL, or null when not in redirect mode or no usable URL is stored.
     */
    public static function get_redirect_url(): ?string {
        if (!self::is_redirect_mode()) {
            return null;
        }

        $url = trim((string) get_config('local_securitytxt', 'redirecturl'));
        if (self::check_redirect_url($url) !== null) {
            return null;
        }

        return $url;
    }

    /**
     * Every rule a redirect target must meet, shared by the settings form and the public endpoint.
     *
     * @param string $url The candidate URL, already trimmed.
     * @return string|null Null when the URL may be used, otherwise the language string identifier of
     *     the reason it may not.
     */
    public static function check_redirect_url(string $url): ?string {
        if ($url === '') {
            return 'error_redirecturlrequired';
        }

        if (!self::is_valid_redirect_url($url)) {
            return 'error_redirecturlhttps';
        }

        // Pointing at this site's own security.txt would send visitors round in a circle.
        if (self::is_own_endpoint($url)) {
            return 'error_redirecturlself';
        }

        return null;
    }

    /**
     * Whether a URL points at this site's own security.txt, in any spelling that reaches it.
     *
     * Compared as normalised parts rather than as text: https://site:443/.well-known/security.txt,
     * an upper-case host, a trailing slash or a query string all reach the same script. The scheme
     * is left out on purpose: behind a TLS-terminating proxy wwwroot can be http while visitors use
     * https, and both still end up here.
     *
     * @param string $url The candidate URL.
     * @return bool True when forwarding there would loop back to this site.
     */
    public static function is_own_endpoint(string $url): bool {
        $target = self::normalise_url($url);
        if ($target === null) {
            return false;
        }

        foreach (['/.well-known/security.txt', '/local/securitytxt/wellknown.php'] as $path) {
            $own = self::normalise_url((new \moodle_url($path))->out(false));
            // Extra path segments after the script name (PATH_INFO) still run the same script.
            if ($target === $own || strpos($target, $own . '/') === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Reduce a URL to host, effective port and path, so equivalent spellings compare equal.
     *
     * @param string $url An absolute URL.
     * @return string|null The normalised form, or null when the URL has no host.
     */
    private static function normalise_url(string $url): ?string {
        $parts = parse_url($url);
        if ($parts === false || empty($parts['host'])) {
            return null;
        }

        $host = rtrim(strtolower($parts['host']), '.');
        // The default port of either scheme is the same as leaving it out.
        $port = $parts['port'] ?? null;
        if ($port === 80 || $port === 443) {
            $port = null;
        }

        // Decode first, so %2e or %2F cannot disguise the path, then fold repeated and trailing slashes.
        $path = rawurldecode($parts['path'] ?? '');
        $path = rtrim(preg_replace('#/+#', '/', $path), '/');

        return strtolower($host . ':' . $port . $path);
    }

    /**
     * Whether a URL is acceptable as redirect target: a clean absolute https URL.
     *
     * RFC 9116 section 3 requires the file to be retrieved over https, so plain http is refused.
     *
     * @param string $url The candidate URL.
     * @return bool True when the URL may be used.
     */
    public static function is_valid_redirect_url(string $url): bool {
        if ($url === '' || clean_param($url, PARAM_URL) !== $url) {
            return false;
        }

        return stripos($url, 'https://') === 0 && (string) parse_url($url, PHP_URL_HOST) !== '';
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
