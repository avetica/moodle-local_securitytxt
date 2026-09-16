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
 * Admin setting for the mandatory RFC 9116 Expires date.
 *
 * @package     local_securitytxt
 * @copyright   2026 Avetica
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_securitytxt;

/**
 * A required calendar date that must lie in the future.
 *
 * Moodle's admin tree has no built-in date picker, so this renders an HTML5 date input and adds the
 * server-side checks the RFC 9116 Expires field needs.
 *
 * @copyright   2026 Avetica
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class admin_setting_expires extends \admin_setting_configtext {
    /**
     * Constructor.
     *
     * @param string $name Setting name, e.g. local_securitytxt/expires.
     * @param string $visiblename Localised label.
     * @param string $description Localised help text.
     * @param string $defaultsetting Default value.
     */
    public function __construct($name, $visiblename, $description, $defaultsetting = '') {
        parent::__construct($name, $visiblename, $description, $defaultsetting, PARAM_RAW_TRIMMED, 12);
    }

    /**
     * Reject an empty, malformed or past date.
     *
     * @param string $data The submitted value.
     * @return bool|string True when valid, otherwise the localised error message.
     */
    public function validate($data) {
        $data = trim((string) $data);

        if ($data === '') {
            return get_string('error_expiresrequired', 'local_securitytxt');
        }

        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $data, $matches)) {
            return get_string('error_expiresformat', 'local_securitytxt');
        }

        if (!checkdate((int) $matches[2], (int) $matches[3], (int) $matches[1])) {
            return get_string('error_expiresformat', 'local_securitytxt');
        }

        // The date counts until the end of that day in UTC, matching what content_generator serves.
        if (strtotime($data . ' 23:59:59 UTC') <= time()) {
            return get_string('error_expirespast', 'local_securitytxt');
        }

        return true;
    }

    /**
     * Store the date and clear the notification flags whenever it actually changes.
     *
     * @param string $data The submitted value.
     * @return string Empty string on success, otherwise the error message.
     */
    public function write_setting($data) {
        $data = trim((string) $data);

        $validated = $this->validate($data);
        if ($validated !== true) {
            return $validated;
        }

        // Test scenario 5.4: a new deadline must be able to trigger its own warnings again.
        if ($data !== (string) $this->get_setting()) {
            set_config('notified_expiring', 0, 'local_securitytxt');
            set_config('notified_expired', 0, 'local_securitytxt');
        }

        return parent::write_setting($data);
    }

    /**
     * Render an HTML5 date input instead of the plain text box.
     *
     * @param string $data The current value.
     * @param string $query Search query for admin setting highlighting.
     * @return string The rendered setting.
     */
    public function output_html($data, $query = '') {
        $attributes = [
            'type' => 'date',
            'name' => $this->get_full_name(),
            'value' => $data,
            'id' => $this->get_id(),
            'class' => 'form-control text-ltr',
        ];

        if ($this->is_readonly()) {
            $attributes['disabled'] = 'disabled';
        }

        $element = \html_writer::div(
            \html_writer::empty_tag('input', $attributes),
            'form-text defaultsnext'
        );

        return format_admin_setting(
            $this,
            $this->visiblename,
            $element,
            $this->description,
            true,
            '',
            $this->get_defaultsetting(),
            $query
        );
    }
}
