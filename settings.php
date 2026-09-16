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
 * Settings page with the RFC 9116 fields, placed under Site administration > Security.
 *
 * @package     local_securitytxt
 * @copyright   2026 Avetica
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// The page carries its own capability rather than the default moodle/site:config, so a Manager can
// maintain the disclosure policy without being a full site administrator.
$settings = new admin_settingpage(
    'local_securitytxt',
    new lang_string('pluginname', 'local_securitytxt'),
    'local/securitytxt:manage'
);
$ADMIN->add('security', $settings);

if ($ADMIN->fulltree) {
    $settings->add(new local_securitytxt\admin_setting_contact(
        'local_securitytxt/contact',
        new lang_string('setting_contact', 'local_securitytxt'),
        new lang_string('setting_contact_desc', 'local_securitytxt'),
        '',
        PARAM_RAW
    ));

    $settings->add(new local_securitytxt\admin_setting_expires(
        'local_securitytxt/expires',
        new lang_string('setting_expires', 'local_securitytxt'),
        new lang_string('setting_expires_desc', 'local_securitytxt')
    ));

    $settings->add(new admin_setting_configtext(
        'local_securitytxt/encryption',
        new lang_string('setting_encryption', 'local_securitytxt'),
        new lang_string('setting_encryption_desc', 'local_securitytxt'),
        '',
        PARAM_RAW_TRIMMED
    ));

    $settings->add(new admin_setting_configtext(
        'local_securitytxt/preferredlanguages',
        new lang_string('setting_preferredlanguages', 'local_securitytxt'),
        new lang_string('setting_preferredlanguages_desc', 'local_securitytxt'),
        '',
        PARAM_RAW_TRIMMED
    ));

    // The default is only used while the administrator has not saved a value of their own, so an
    // overridden Canonical is never silently replaced by the suggestion.
    $settings->add(new admin_setting_configtext(
        'local_securitytxt/canonical',
        new lang_string('setting_canonical', 'local_securitytxt'),
        new lang_string('setting_canonical_desc', 'local_securitytxt'),
        $CFG->wwwroot . '/.well-known/security.txt',
        PARAM_RAW_TRIMMED
    ));

    $settings->add(new admin_setting_configtext(
        'local_securitytxt/policy',
        new lang_string('setting_policy', 'local_securitytxt'),
        new lang_string('setting_policy_desc', 'local_securitytxt'),
        '',
        PARAM_RAW_TRIMMED
    ));
}
