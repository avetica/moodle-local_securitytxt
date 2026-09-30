# local_securitytxt

A Moodle local plugin that lets a site administrator publish an [RFC 9116](https://www.rfc-editor.org/rfc/rfc9116) compliant `security.txt` file, so security researchers know where to report a vulnerability.

## What it does

- Adds a settings page under **Site administration > Security > Security.txt**. The administrator first chooses how the file is published:
  - **Fill in the fields below**: the file is built from the RFC 9116 fields Contact, Expires, Encryption, Preferred-Languages, Canonical and Policy.
  - **Redirect to an existing security.txt**: for organisations that already publish a (centrally managed or digitally signed) `security.txt` elsewhere. Visitors are forwarded there with an HTTP 302, so the file reaches them byte for byte unchanged and a signature stays valid. The fields are hidden and not served.
- Serves the file as plain text from `/local/securitytxt/wellknown.php`, publicly and without a Moodle session.
- Refuses to save an empty Contact or an expiry date in the past, so the published file is never invalid. In redirect mode it instead requires an https address that does not point back at this site.
- In fields mode, warns every site administrator through a Moodle notification 30 days before the `Expires` date, and again once it has passed.

Only users holding `local/securitytxt:manage` (by default the Manager role) can edit the settings. The public endpoint has no capability check, because researchers reach it anonymously.

## Installation

1. Copy the plugin into `local/securitytxt` in your Moodle directory.
2. Visit **Site administration > Notifications** and complete the upgrade.
3. Under **Site administration > Security > Security.txt**, either fill in at least Contact and Expires, or choose the redirect and enter the address of your existing file.

Until Contact and Expires are both filled in (or, in redirect mode, a valid address), the endpoint returns HTTP 404: no half-configured file is ever published.

In redirect mode, also list this site's address (`https://<your-moodle>/.well-known/security.txt`) in the `Canonical` field of the file you redirect to. RFC 9116 section 2.5.2 tells researchers not to trust a file retrieved from an address that is not listed there.

## Documentation

A task-based guide for the site administrator (publishing, forwarding, checking the result, extending the expiry date) is in [docs/administrator-guide.md](docs/administrator-guide.md).

## Required: routing /.well-known/security.txt

RFC 9116 requires the file at `https://<yoursite>/.well-known/security.txt`. **This plugin does not set up that route** - it only serves the content at `/local/securitytxt/wellknown.php`. Your technical administrator has to point the well-known path at that script.

**Kubernetes ingress (nginx):**

```yaml
nginx.ingress.kubernetes.io/configuration-snippet: |
  rewrite ^/.well-known/security.txt$ /local/securitytxt/wellknown.php last;
```

**Apache, in `.htaccess` or a `<Directory>` block:**

```apache
RewriteEngine On
RewriteRule ^\.well-known/security\.txt$ /local/securitytxt/wellknown.php [L]
```

**Apache, directly in the `<VirtualHost>`:**

```apache
RewriteEngine On
RewriteRule ^/\.well-known/security\.txt$ /local/securitytxt/wellknown.php [L]
```

The leading slash is the only difference, and it matters: in a `<VirtualHost>` the rule is matched
against the path including its leading `/`, in `.htaccess` and `<Directory>` without it. The
`.htaccess` form placed in a `<VirtualHost>` never matches, so the well-known address keeps returning
404. `mod_rewrite` must be enabled either way (`a2enmod rewrite` on Debian and Ubuntu).

**Nginx (server block):**

```nginx
location = /.well-known/security.txt {
    rewrite ^ /local/securitytxt/wellknown.php last;
}
```

### Record the route in your infrastructure-as-code

Put the annotation or rewrite in the Helm chart or manifest that is kept in Git, not directly on a live ingress with `kubectl` or a portal. A GitOps reconcile or a full redeploy treats a hand-applied change as drift and removes it, which leaves `/.well-known/security.txt` returning 404 even though the plugin is still working and the saved fields are untouched (those live in the database and survive any redeploy).

## Caching

A configured file, and in redirect mode the redirect itself, is served with `Cache-Control: public, max-age=3600`, so scanners and proxies do not hit the site repeatedly but still pick up a change within the hour. Before the first configuration the 404 is served with `Cache-Control: no-cache`, so no proxy keeps serving "not configured" after the administrator fills the fields in.

## Notes

- `Expires` is stored as a calendar date and served as a full RFC 3339 timestamp (`...T23:59:59Z`), because scanners such as Internet.nl reject a bare `YYYY-MM-DD` value.
- Multiple contact methods are allowed: put one per line, and each gets its own `Contact:` line.
- The plugin stores no personal data; the fields are organisational contact details.

## Out of scope

- Routing to `/.well-known/security.txt` (see above).
- PGP cleartext signing of the file, which RFC 9116 recommends but does not require.
- The optional `Acknowledgments` and `Hiring` fields.

## For support

Notes for colleagues answering support questions about this plugin.

**"/.well-known/security.txt gives 404, but the settings are filled in."** Check in this order:
1. Open `/local/securitytxt/wellknown.php`. If that shows the file, the plugin works and the route is missing or wrong. If that also gives 404, Contact or Expires is empty (or, when forwarding, the address is not saved).
2. The route lives in the web server or ingress, not in the plugin. After a redeploy or a GitOps reconcile a hand-applied ingress annotation is removed. The fix is to put the rule in the Helm chart or manifest in Git (see above).
3. On Apache, a rule copied from `.htaccess` into a `<VirtualHost>` never matches because of the leading slash, and `mod_rewrite` must be enabled. Use the form that belongs to the place the rule is in.

**"The file still shows the old expiry date."** The response is cached for one hour (`Cache-Control: public, max-age=3600`); a proxy or scanner may hold it for that long.

**"I switched to redirect and the page would not save."** The mode only changes when the address is a valid https address that does not point at this site. The page shows which condition failed, and the current file stays online.

**"No expiry notification arrived."** Notifications go to users in `$CFG->siteadmins`, once 30 days before `Expires` and once after it has passed, and start over when the date is changed. In redirect mode they are not sent. They arrive as a Moodle notification; email is off by default and is switched on per user in the notification preferences. The check runs as a daily scheduled task ("Check the Security.txt expiry date"), so cron must be running.

**"Who can change the settings?"** Users with `local/securitytxt:manage` (Manager role by default). The public endpoint has no capability check, because researchers reach it anonymously.

**Where is the configuration?** **Site administration > Security > Security.txt**. The values are stored as plugin settings in the database and survive a redeploy.

## Licence

GNU GPL v3 or later.
