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
 * Language strings for local_securitytxt.
 *
 * @package     local_securitytxt
 * @copyright   2026 Avetica
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['error_contactrequired'] = 'Enter at least one way for a security researcher to reach you.';
$string['error_expiresformat'] = 'Enter a valid calendar date.';
$string['error_expirespast'] = 'The expiry date must be in the future.';
$string['error_expiresrequired'] = 'An expiry date is required by RFC 9116.';
$string['messageprovider:expirynotice'] = 'Security.txt expiry warnings';
$string['notice_expired_body'] = 'The security.txt file on {$a->sitename} expired on {$a->date}.

Security researchers and automated compliance scanners now treat your responsible disclosure details as out of date, which can make your site look unmaintained.

Set a new expiry date here: {$a->url}';
$string['notice_expired_subject'] = 'Your security.txt has expired';
$string['notice_expiring_body'] = 'The security.txt file on {$a->sitename} expires on {$a->date}, which is {$a->days} days from now.

Once that date passes, security researchers and automated compliance scanners treat your responsible disclosure details as out of date.

Extend the expiry date here: {$a->url}';
$string['notice_expiring_subject'] = 'Your security.txt expires in {$a->days} days';
$string['pluginname'] = 'Security.txt';
$string['privacy:metadata'] = 'The Security.txt plugin does not store any personal data. The RFC 9116 fields (Contact, Expires, Encryption, Preferred-Languages, Canonical, Policy) are organisational contact details, not personal data about end users.';
$string['securitytxt:manage'] = 'Manage the Security.txt settings';
$string['setting_canonical'] = 'Canonical';
$string['setting_canonical_desc'] = 'The official URL where this file belongs. It is suggested automatically from your site address; only change it if your site is reachable on several domains and you want to point at the real one.';
$string['setting_contact'] = 'Contact';
$string['setting_contact_desc'] = 'How a security researcher can report a vulnerability to you, for example an email address (mailto:security@example.org) or a link to a report form. Several contact methods are allowed, one per line. The format itself is not checked, so typos are not caught.';
$string['setting_encryption'] = 'Encryption';
$string['setting_encryption_desc'] = 'A link to your PGP public key, so a researcher can send an encrypted report. Leave empty if you do not offer this.';
$string['setting_expires'] = 'Expires';
$string['setting_expires_desc'] = 'The date until which this information is valid. Researchers and automated scanners use it to see whether the file is still current, so setting it about a year ahead is common. It must be in the future, and you receive a notification before it expires.';
$string['setting_policy'] = 'Policy';
$string['setting_policy_desc'] = 'A link to your full responsible disclosure policy: the rules and agreements around reporting vulnerabilities. Leave empty if you have no separate policy document.';
$string['setting_preferredlanguages'] = 'Preferred languages';
$string['setting_preferredlanguages_desc'] = 'The language or languages in which you prefer to receive a report, for example "nl, en". This does not translate the security.txt file itself; it only tells a researcher which language to use.';
$string['setting_testlink'] = 'Test the output';
$string['setting_testlink_desc'] = 'Save your changes above first, then compare these two: they should show the exact same content.';
$string['setting_testlink_dotwellknown'] = 'Open /.well-known/security.txt';
$string['setting_testlink_dotwellknown_desc'] = 'The real address a security researcher visits. Same content as the left button - the routing is set up correctly. A "Not Found" error - your administrator still needs to set up the routing from README.md.';
$string['setting_testlink_wellknown'] = 'Open wellknown.php';
$string['setting_testlink_wellknown_desc'] = "This plugin's own script. Always shows your current settings, on every installation, regardless of server configuration.";
$string['task_checkexpiry'] = 'Check the Security.txt expiry date';
