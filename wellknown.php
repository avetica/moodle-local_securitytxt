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
 * Public security.txt endpoint.
 *
 * Security researchers reach this anonymously, so there is no require_login() and no capability
 * check. The web server or ingress rewrites /.well-known/security.txt to this script; setting that
 * up is the site administrator's job (see README.md).
 *
 * @package     local_securitytxt
 * @copyright   2026 Avetica
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('NO_MOODLE_COOKIES', true);

require(__DIR__ . '/../../config.php');

if (\local_securitytxt\content_generator::is_redirect_mode()) {
    // The organisation publishes its own (often signed) security.txt elsewhere. Forwarding keeps it
    // byte for byte intact, which rebuilding it here never could. Without a usable URL nothing is
    // served, not the fields left over from before the switch: those are no longer maintained.
    $content = null;
    $redirecturl = \local_securitytxt\content_generator::get_redirect_url();
    if ($redirecturl !== null) {
        // A temporary redirect, so browsers and proxies do not remember it forever if the
        // organisation's address ever changes.
        header('Cache-Control: public, max-age=3600');
        header('Location: ' . $redirecturl, true, 302);
        exit;
    }
} else {
    $content = \local_securitytxt\content_generator::generate();
}

header('Content-Type: text/plain; charset=utf-8');

if ($content === null) {
    // Nothing is configured yet, so no security.txt exists. A proxy must not keep serving this
    // once the administrator fills in Contact and Expires.
    header('Cache-Control: no-cache');
    http_response_code(404);
    exit;
}

header('Cache-Control: public, max-age=3600');
echo $content;
