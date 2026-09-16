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
 * Admin setting for the mandatory RFC 9116 Contact field.
 *
 * @package     local_securitytxt
 * @copyright   2026 Avetica
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_securitytxt;

/**
 * A required multi-line field holding one contact method per line.
 *
 * The URI format itself is deliberately not validated (see specs.md); only the presence of at least
 * one non-blank line is enforced.
 *
 * @copyright   2026 Avetica
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class admin_setting_contact extends \admin_setting_configtextarea {
    /**
     * Reject a value that holds no contact method at all.
     *
     * @param string $data The submitted value.
     * @return bool|string True when valid, otherwise the localised error message.
     */
    public function validate($data) {
        // Test scenario 3.1b: whitespace only is the same as empty.
        if (trim((string) $data) === '') {
            return get_string('error_contactrequired', 'local_securitytxt');
        }

        return parent::validate($data);
    }
}
