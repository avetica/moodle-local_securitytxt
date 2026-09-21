# Changelog

All notable changes to `local_securitytxt` are documented here.

## 1.0.1 - 2026-09-21

Independently reviewed and functionally tested; no open findings.

### Added

- Dutch translation of every interface string.
- An "Uitvoer testen" block on the settings page with two links, one to the plugin's own script and
  one to the real `/.well-known/security.txt` address, so an administrator can see for themselves
  whether the web server routing is in place.
- A `LICENSE` file with the full GPL v3 text, alongside the per-file boilerplate headers.

### Fixed

- `$plugin->supported` declared the supported Moodle versions as a list instead of the two-number
  range Moodle expects, which made `all_plugins_ok()` throw for every plugin on the site.
- Installing or upgrading logged a debugging notice for Contact and Expires, because both fields
  offered an empty default that their own validation then refused. Neither field has a default now.
- A line break inside Encryption, Preferred-Languages, Canonical or Policy added an extra field to
  the served file. Such a value is now collapsed onto its own line.

## 1.0.0 - 2026-09-16

First stable release. Functionally identical to 1.0.0-rc1.

## 1.0.0-rc1 - 2026-09-16

First release candidate. Feature-complete with the full automated test suite passing; awaiting the independent review and functional test before it is marked stable.

### Added

- Settings page under Site administration > Security with the RFC 9116 fields: Contact, Expires, Encryption, Preferred-Languages, Canonical and Policy.
- Public `wellknown.php` endpoint serving the file as `text/plain` with CRLF line endings, without a Moodle session, and HTTP 404 while the plugin is unconfigured.
- Validation refusing an empty or whitespace-only Contact, a missing Expires and an Expires date in the past.
- Automatic Canonical suggestion based on the site address, which never overwrites a value the administrator set.
- Daily scheduled task warning site administrators 30 days before the expiry date and once after it passes, through the Moodle notification bell.
- Capability `local/securitytxt:manage`, granted to the Manager role by default.
