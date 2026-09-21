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
 * Dutch language strings for local_securitytxt.
 *
 * @package     local_securitytxt
 * @copyright   2026 Avetica
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['error_contactrequired'] = 'Geef ten minste één contactmethode op waarmee een beveiligingsonderzoeker contact met u kan opnemen.';
$string['error_expiresformat'] = 'Voer een geldige kalenderdatum in.';
$string['error_expirespast'] = 'De vervaldatum moet in de toekomst liggen.';
$string['error_expiresrequired'] = 'Een vervaldatum is verplicht volgens RFC 9116.';
$string['messageprovider:expirynotice'] = 'Waarschuwingen over vervaldatum security.txt';
$string['notice_expired_body'] = 'Het security.txt-bestand op {$a->sitename} is verlopen op {$a->date}.

Beveiligingsonderzoekers en geautomatiseerde scanners beschouwen uw contactgegevens voor verantwoorde openbaarmaking nu als verouderd, waardoor uw site mogelijk als niet-onderhouden wordt gezien.

Stel hier een nieuwe vervaldatum in: {$a->url}';
$string['notice_expired_subject'] = 'Uw security.txt is verlopen';
$string['notice_expiring_body'] = 'Het security.txt-bestand op {$a->sitename} verloopt op {$a->date}, over {$a->days} dagen.

Zodra die datum verstrijkt, beschouwen beveiligingsonderzoekers en geautomatiseerde scanners uw contactgegevens voor verantwoorde openbaarmaking als verouderd.

Verleng de vervaldatum hier: {$a->url}';
$string['notice_expiring_subject'] = 'Uw security.txt verloopt over {$a->days} dagen';
$string['pluginname'] = 'Security.txt';
$string['privacy:metadata'] = 'De security.txt-plugin slaat geen persoonsgegevens op. De RFC 9116-velden (Contact, Expires, Encryption, Preferred-Languages, Canonical, Policy) zijn organisatorische contactgegevens en geen persoonsgegevens van eindgebruikers.';
$string['securitytxt:manage'] = 'De security.txt-instellingen beheren';
$string['setting_canonical'] = 'Canonical';
$string['setting_canonical_desc'] = 'De officiële URL waar dit bestand thuishoort. Deze wordt automatisch voorgesteld op basis van uw site-adres. Pas dit alleen aan als uw site via meerdere domeinen bereikbaar is en u naar het hoofddomein wilt verwijzen.';
$string['setting_contact'] = 'Contact';
$string['setting_contact_desc'] = 'Hoe een beveiligingsonderzoeker een kwetsbaarheid kan melden, bijvoorbeeld een e-mailadres (mailto:security@example.org) of een link naar een meldingsformulier. Meerdere contactmethoden zijn toegestaan, één per regel. De indeling zelf wordt niet gecontroleerd.';
$string['setting_encryption'] = 'Encryptie (Encryption)';
$string['setting_encryption_desc'] = 'Een link naar uw publieke PGP-sleutel, zodat een onderzoeker een versleuteld rapport kan sturen. Laat leeg als u dit niet aanbiedt.';
$string['setting_expires'] = 'Vervaldatum (Expires)';
$string['setting_expires_desc'] = 'De datum tot wanneer deze informatie geldig is. Onderzoekers en geautomatiseerde scanners gebruiken dit om te controleren of het bestand actueel is; een datum circa één jaar in de toekomst is gebruikelijk. De datum moet in de toekomst liggen en u ontvangt een melding voordat deze verloopt.';
$string['setting_policy'] = 'Beleid (Policy)';
$string['setting_policy_desc'] = 'Een link naar uw volledige beleid voor verantwoorde openbaarmaking (Responsible Disclosure / Coordinated Vulnerability Disclosure). Laat leeg als u geen afzonderlijk beleidsdocument hebt.';
$string['setting_preferredlanguages'] = 'Voorkeurstalen (Preferred-Languages)';
$string['setting_preferredlanguages_desc'] = 'De taal of talen waarin u meldingen bij voorkeur ontvangt, bijvoorbeeld "nl, en". Dit vertaalt het security.txt-bestand zelf niet, maar geeft aan welke taal een onderzoeker kan gebruiken.';
$string['setting_testlink'] = 'Uitvoer testen';
$string['setting_testlink_desc'] = 'Sla uw wijzigingen hierboven eerst op, en vergelijk dan deze twee: ze moeten precies dezelfde inhoud laten zien.';
$string['setting_testlink_dotwellknown'] = 'Open /.well-known/security.txt';
$string['setting_testlink_dotwellknown_desc'] = 'Het echte adres dat een beveiligingsonderzoeker bezoekt. Dezelfde inhoud als bij de knop links - de routing staat goed ingericht. Een foutmelding ("Not Found") - uw beheerder moet de routing uit README.md nog instellen.';
$string['setting_testlink_wellknown'] = 'Open wellknown.php';
$string['setting_testlink_wellknown_desc'] = 'Het eigen script van deze plugin. Toont altijd uw huidige instellingen, op elke installatie, ongeacht de serverconfiguratie.';
$string['task_checkexpiry'] = 'Controleer de vervaldatum van security.txt';
