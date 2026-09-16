# Changelog

All notable changes to `local_securitytxt` are documented here.

## 1.0.0-rc1 - 2026-09-16

First release candidate. Feature-complete with the full automated test suite passing; awaiting the independent review and functional test before it is marked stable.

### Added

- Settings page under Site administration > Security with the RFC 9116 fields: Contact, Expires, Encryption, Preferred-Languages, Canonical and Policy.
- Public `wellknown.php` endpoint serving the file as `text/plain` with CRLF line endings, without a Moodle session, and HTTP 404 while the plugin is unconfigured.
- Validation refusing an empty or whitespace-only Contact, a missing Expires and an Expires date in the past.
- Automatic Canonical suggestion based on the site address, which never overwrites a value the administrator set.
- Daily scheduled task warning site administrators 30 days before the expiry date and once after it passes, through the Moodle notification bell.
- Capability `local/securitytxt:manage`, granted to the Manager role by default.
