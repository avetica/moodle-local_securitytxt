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
 * Daily check on the RFC 9116 Expires date.
 *
 * @package     local_securitytxt
 * @copyright   2026 Avetica
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_securitytxt\task;

/**
 * Warns site administrators once when the expiry date comes into range, and once when it passes.
 *
 * @copyright   2026 Avetica
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class check_expiry extends \core\task\scheduled_task {
    /** @var int How far ahead an upcoming expiry is announced. */
    public const WARNING_WINDOW_DAYS = 30;

    /**
     * Name shown in the scheduled task admin screen.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_checkexpiry', 'local_securitytxt');
    }

    /**
     * Send the expiry notification that is due, if any.
     */
    public function execute(): void {
        // In redirect mode the organisation maintains Expires in its own file, not here.
        if (\local_securitytxt\content_generator::is_redirect_mode()) {
            return;
        }

        $expires = trim((string) get_config('local_securitytxt', 'expires'));
        if ($expires === '') {
            return;
        }

        $deadline = strtotime($expires . ' 23:59:59 UTC');
        if ($deadline === false) {
            return;
        }

        $now = time();

        if ($deadline < $now) {
            if (!get_config('local_securitytxt', 'notified_expired')) {
                $this->notify_admins('expired', $expires, 0);
                set_config('notified_expired', 1, 'local_securitytxt');
            }
            return;
        }

        $daysleft = (int) floor(($deadline - $now) / DAYSECS);
        if ($daysleft <= self::WARNING_WINDOW_DAYS && !get_config('local_securitytxt', 'notified_expiring')) {
            $this->notify_admins('expiring', $expires, $daysleft);
            set_config('notified_expiring', 1, 'local_securitytxt');
        }
    }

    /**
     * Send one notification to every active site administrator.
     *
     * @param string $type Either 'expiring' or 'expired'.
     * @param string $expires The configured date in YYYY-MM-DD format.
     * @param int $daysleft Whole days until the deadline.
     */
    private function notify_admins(string $type, string $expires, int $daysleft): void {
        $placeholders = (object) [
            'sitename' => format_string(get_site()->fullname),
            'date' => $expires,
            'days' => $daysleft,
            'url' => (new \moodle_url('/admin/settings.php', ['section' => 'local_securitytxt']))->out(false),
        ];

        $subject = get_string('notice_' . $type . '_subject', 'local_securitytxt', $placeholders);
        $body = get_string('notice_' . $type . '_body', 'local_securitytxt', $placeholders);

        foreach ($this->get_recipients() as $recipient) {
            $message = new \core\message\message();
            $message->component = 'local_securitytxt';
            $message->name = 'expirynotice';
            $message->userfrom = \core_user::get_noreply_user();
            $message->userto = $recipient;
            $message->subject = $subject;
            $message->fullmessage = $body;
            $message->fullmessageformat = FORMAT_PLAIN;
            $message->fullmessagehtml = '';
            $message->smallmessage = $subject;
            $message->notification = 1;
            $message->contexturl = $placeholders->url;
            $message->contexturlname = $subject;

            message_send($message);
        }
    }

    /**
     * The site administrators who should receive the warning.
     *
     * @return \stdClass[] Active administrator accounts.
     */
    private function get_recipients(): array {
        global $CFG, $DB;

        $adminids = array_filter(array_map('intval', explode(',', (string) $CFG->siteadmins)));
        if (empty($adminids)) {
            return [];
        }

        // A deleted or suspended administrator must not be messaged.
        return array_filter(
            $DB->get_records_list('user', 'id', $adminids),
            static fn(\stdClass $user): bool => empty($user->deleted) && empty($user->suspended)
        );
    }
}
