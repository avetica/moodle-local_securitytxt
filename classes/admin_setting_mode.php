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
 * Admin setting for the choice between building security.txt here and forwarding to an existing one.
 *
 * @package     local_securitytxt
 * @copyright   2026 Avetica
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_securitytxt;

/**
 * The mode choice, which only changes once the settings the new mode depends on are valid too.
 *
 * Moodle saves each setting on its own and never rolls an earlier one back when a later one fails
 * validation (admin_write_settings()). Without this check, choosing "Redirect" with an empty or
 * invalid address stored the mode anyway, and the public security.txt went straight to a 404.
 *
 * @copyright   2026 Avetica
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class admin_setting_mode extends \admin_setting_configselect {
    /**
     * Constructor.
     *
     * @param string $name Setting name, e.g. local_securitytxt/mode.
     * @param string $visiblename Localised label.
     * @param string $description Localised help text.
     */
    public function __construct($name, $visiblename, $description) {
        parent::__construct($name, $visiblename, $description, content_generator::MODE_FIELDS, [
            content_generator::MODE_FIELDS => new \lang_string('setting_mode_fields', 'local_securitytxt'),
            content_generator::MODE_REDIRECT => new \lang_string('setting_mode_redirect', 'local_securitytxt'),
        ]);
    }

    /**
     * The mode the administrator is choosing: the one submitted with this save, otherwise the stored one.
     *
     * The dependent fields validate against this rather than the stored mode, so a refused switch
     * still shows why it was refused next to the field that caused it.
     *
     * @return string One of the content_generator::MODE_* values.
     */
    public static function chosen(): string {
        $submitted = optional_param('s_local_securitytxt_mode', null, PARAM_ALPHANUMEXT);
        if ($submitted === content_generator::MODE_FIELDS || $submitted === content_generator::MODE_REDIRECT) {
            return $submitted;
        }

        return content_generator::is_redirect_mode() ? content_generator::MODE_REDIRECT : content_generator::MODE_FIELDS;
    }

    /**
     * Store the mode, but only when the switch leaves a working security.txt behind.
     *
     * Only an actual switch is checked. Saving the page in the current mode needs no check here, the
     * fields report their own errors, and the install default (fields, with nothing filled in yet)
     * must not be refused.
     *
     * @param string $data The submitted mode.
     * @return string Empty string on success, otherwise the error message.
     */
    public function write_setting($data) {
        $current = $this->get_setting();
        $error = null;

        if ($data === content_generator::MODE_REDIRECT && $current !== content_generator::MODE_REDIRECT) {
            $error = content_generator::check_redirect_url(self::submitted('redirecturl'));
        } else if ($data === content_generator::MODE_FIELDS && $current === content_generator::MODE_REDIRECT) {
            $error = admin_setting_contact::check(self::submitted('contact'))
                ?? admin_setting_expires::check(self::submitted('expires'));
        }

        if ($error !== null) {
            return get_string('error_modeunchanged', 'local_securitytxt');
        }

        return parent::write_setting($data);
    }

    /**
     * A field submitted together with the mode, falling back to its stored value.
     *
     * Moodle hands each setting only its own value, so the other fields of the same save are read
     * from the request. They are only validated here, never stored: each field still saves itself.
     *
     * @param string $name Setting name without the plugin prefix.
     * @return string The trimmed value.
     */
    private static function submitted(string $name): string {
        $value = optional_param('s_local_securitytxt_' . $name, null, PARAM_RAW);

        return trim((string) ($value ?? get_config('local_securitytxt', $name)));
    }
}
