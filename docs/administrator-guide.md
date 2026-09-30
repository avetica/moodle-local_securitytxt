# Administrator guide: Security.txt

This guide is for the Moodle site administrator who sets up and maintains the security.txt file. It is written as tasks: find the task you need and follow the steps. Setting up the web server route (`/.well-known/security.txt`) is a one-time job for your technical administrator and is described in the [README](../README.md#required-routing-well-knownsecuritytxt).

## What this plugin does for you

A `security.txt` file tells security researchers where to report a vulnerability. Automated compliance scanners (for example Internet.nl) check for it, and it must contain valid contact details and an expiry date. This plugin lets you maintain that file from Moodle, refuses to publish an invalid file, and warns you before it expires.

Only people with the capability `local/securitytxt:manage` can change the settings. By default that is the Manager role, plus site administrators.

## Open the settings

1. Go to **Site administration > Security > Security.txt**.
2. The page opens with the choice "How to publish security.txt", followed by the fields, and at the bottom a block "Test the output".

## Task: publish a security.txt from Moodle

Use this if your organisation does not publish a security.txt elsewhere.

1. Under "How to publish security.txt", choose **Fill in the fields below**.
2. Fill in **Contact**: how a researcher reaches you, for example `mailto:security@example.org` or a link to a report form. You can list several methods, one per line. This field is required.
3. Fill in **Expires**: the date until which the information is valid. It must be in the future and is required. A date about one year ahead is common.
4. Optionally fill in:
   - **Encryption**: a link to your PGP public key.
   - **Preferred languages**: for example `nl, en`. This only tells a researcher which language to write in.
   - **Policy**: a link to your responsible disclosure policy.
   - **Canonical**: the official address of this file. It is filled in from your site address; only change it if your site is reachable on several domains.
5. Select **Save changes**.

If something is wrong (an empty Contact, a date in the past), the page shows a message at the field and does not save. The published file is therefore never invalid.

## Task: forward to a security.txt you already publish elsewhere

Use this if your organisation manages one central security.txt, for example a digitally signed file.

1. Under "How to publish security.txt", choose **Redirect to an existing security.txt**.
2. Enter the full **https** address of the existing file, for example `https://www.example.org/.well-known/security.txt`. You cannot enter the address of this Moodle site itself, because that would send visitors in a circle.
3. Select **Save changes**.

Visitors are forwarded to that address and receive the file unchanged, so a signature stays valid. The fields are hidden and not published, and the expiry warning is switched off, because the file is maintained elsewhere. The values you entered earlier are kept, so switching back does not require retyping.

Also list this site's address (`https://<your-moodle>/.well-known/security.txt`) in the `Canonical` field of the file you forward to. Researchers are told not to trust a file from an address that is not listed there.

If you switch methods and the settings for the new method are not valid, the switch is not made. The current security.txt stays online and the page says what to correct.

## Task: check that it works

1. Save your settings first.
2. In the block "Test the output", open both links:
   - **Open wellknown.php** is this plugin's own script. It always shows your current settings.
   - **Open /.well-known/security.txt** is the real address a researcher visits.
3. Both must show the same content. If the second link gives "Not Found", the web server route is not set up yet or was lost; ask your technical administrator to follow the README.

Until Contact and Expires are filled in (or, when forwarding, a valid address is saved), both addresses answer "Not Found". That is intended: a half-finished file is never published.

## Task: extend the expiry date

1. Open the settings page.
2. Change **Expires** to a new date in the future.
3. Select **Save changes**.

Moodle sends every site administrator a notification 30 days before the expiry date and again once the date has passed. The notifications appear in Moodle and can also be sent by email if you switch that on in your notification preferences (**Preferences > Notification preferences > Security.txt expiry warnings**). Both notifications contain a link to the settings page. After you set a new date, the warnings start over for that date.

## Good to know

- Changes are visible to scanners within the hour, because the file is cached for one hour.
- The plugin stores no personal data. The fields are organisational contact details.
- The plugin does not sign the file (PGP) and does not offer the optional `Acknowledgments` and `Hiring` fields.
