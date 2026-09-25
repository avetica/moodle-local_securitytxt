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
 * Admin setting for the address of an existing security.txt that visitors are forwarded to.
 *
 * @package     local_securitytxt
 * @copyright   2026 Avetica
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_securitytxt;

/**
 * An https URL that is required only in redirect mode.
 *
 * @copyright   2026 Avetica
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class admin_setting_redirecturl extends \admin_setting_configtext {
    /**
     * Constructor.
     *
     * @param string $name Setting name, e.g. local_securitytxt/redirecturl.
     * @param string $visiblename Localised label.
     * @param string $description Localised help text.
     */
    public function __construct($name, $visiblename, $description) {
        parent::__construct($name, $visiblename, $description, '', PARAM_RAW_TRIMMED, 60);
    }

    /**
     * Reject a missing, non-https or self-referencing URL while redirect mode is selected.
     *
     * @param string $data The submitted value.
     * @return bool|string True when valid, otherwise the localised error message.
     */
    public function validate($data) {
        // In fields mode this field is hidden and unused. The mode chosen in this same save counts,
        // not the stored one: a refused switch to redirect mode must still say what is wrong here.
        if (admin_setting_mode::chosen() !== content_generator::MODE_REDIRECT) {
            return true;
        }

        $error = content_generator::check_redirect_url(trim((string) $data));
        if ($error !== null) {
            return get_string($error, 'local_securitytxt');
        }

        return true;
    }
}
