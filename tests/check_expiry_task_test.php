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
 * Unit tests for the expiry notification task.
 *
 * @package     local_securitytxt
 * @copyright   2026 Avetica
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_securitytxt;

use local_securitytxt\task\check_expiry;

/**
 * Tests for {@see check_expiry}, covering test scenarios 5.1, 5.2, 5.3 and 5.5.
 *
 * @copyright   2026 Avetica
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_securitytxt\task\check_expiry
 *
 * Coverage target stays a doc-comment annotation rather than a #[CoversClass] attribute: attributes
 * need PHPUnit 10+, while this plugin's supported floor (Moodle 4.5 LTS) still ships PHPUnit 9.
 */
final class check_expiry_task_test extends \advanced_testcase {
    /**
     * Run the task with the message sink active and return everything it sent.
     *
     * @return \stdClass[] The captured messages.
     */
    private function run_task(): array {
        $sink = $this->redirectMessages();
        (new check_expiry())->execute();
        $messages = $sink->get_messages();
        $sink->close();

        return $messages;
    }

    /**
     * Test scenario 5.1 — crossing the 30 day window warns every site administrator once.
     */
    public function test_scenario_5_1_warns_when_expiry_comes_into_range(): void {
        $this->resetAfterTest();
        set_config('expires', date('Y-m-d', strtotime('+10 days')), 'local_securitytxt');

        $messages = $this->run_task();

        $this->assertCount(1, $messages);
        $this->assertSame('expirynotice', reset($messages)->eventtype);
        $this->assertSame('1', get_config('local_securitytxt', 'notified_expiring'));
        $this->assertFalse(get_config('local_securitytxt', 'notified_expired'));
    }

    /**
     * Test scenario 5.2 — an expired date produces the expired warning.
     */
    public function test_scenario_5_2_warns_when_expiry_has_passed(): void {
        $this->resetAfterTest();
        set_config('expires', date('Y-m-d', strtotime('-2 days')), 'local_securitytxt');

        $messages = $this->run_task();

        $this->assertCount(1, $messages);
        $this->assertStringContainsString('has expired', reset($messages)->subject);
        $this->assertSame('1', get_config('local_securitytxt', 'notified_expired'));
    }

    /**
     * Test scenario 5.3 — a second cron run on the same date stays silent.
     */
    public function test_scenario_5_3_no_duplicate_warning_on_repeat_run(): void {
        $this->resetAfterTest();
        set_config('expires', date('Y-m-d', strtotime('+10 days')), 'local_securitytxt');

        $this->assertCount(1, $this->run_task());
        $this->assertCount(0, $this->run_task());
    }

    /**
     * Test scenario 5.3 — the expired warning is likewise sent only once.
     */
    public function test_scenario_5_3_no_duplicate_expired_warning(): void {
        $this->resetAfterTest();
        set_config('expires', date('Y-m-d', strtotime('-2 days')), 'local_securitytxt');

        $this->assertCount(1, $this->run_task());
        $this->assertCount(0, $this->run_task());
    }

    /**
     * Test scenario 5.4 — after the flags are cleared, a newly approaching date warns again.
     */
    public function test_scenario_5_4_warns_again_after_flags_are_reset(): void {
        $this->resetAfterTest();
        set_config('expires', date('Y-m-d', strtotime('+10 days')), 'local_securitytxt');
        $this->assertCount(1, $this->run_task());

        // What admin_setting_expires::write_setting() does when a new date is saved.
        set_config('notified_expiring', 0, 'local_securitytxt');
        set_config('notified_expired', 0, 'local_securitytxt');
        set_config('expires', date('Y-m-d', strtotime('+20 days')), 'local_securitytxt');

        $this->assertCount(1, $this->run_task());
    }

    /**
     * Test scenario 5.5 — a date well outside the window sends nothing and leaves both flags unset.
     */
    public function test_scenario_5_5_silent_outside_warning_window(): void {
        $this->resetAfterTest();
        set_config('expires', date('Y-m-d', strtotime('+1 year')), 'local_securitytxt');

        $this->assertCount(0, $this->run_task());
        $this->assertFalse(get_config('local_securitytxt', 'notified_expiring'));
        $this->assertFalse(get_config('local_securitytxt', 'notified_expired'));
    }

    /**
     * Nothing is sent while the plugin has not been configured at all.
     */
    public function test_silent_when_not_configured(): void {
        $this->resetAfterTest();

        $this->assertCount(0, $this->run_task());
    }

    /**
     * A suspended or deleted site administrator receives no notification.
     */
    public function test_suspended_and_deleted_admins_are_skipped(): void {
        global $CFG, $DB;
        $this->resetAfterTest();

        $active = $this->getDataGenerator()->create_user();
        $suspended = $this->getDataGenerator()->create_user(['suspended' => 1]);
        $deleted = $this->getDataGenerator()->create_user();
        $DB->set_field('user', 'deleted', 1, ['id' => $deleted->id]);

        $CFG->siteadmins = implode(',', [$active->id, $suspended->id, $deleted->id]);
        set_config('expires', date('Y-m-d', strtotime('+10 days')), 'local_securitytxt');

        $messages = $this->run_task();

        $this->assertCount(1, $messages);
        $this->assertSame((int) $active->id, (int) reset($messages)->useridto);
    }

    /**
     * The warning names the deadline, the days left and where to change it, so the reader needs no
     * extra context (task 6b in tasks.md).
     */
    public function test_warning_states_deadline_days_and_where_to_fix_it(): void {
        $this->resetAfterTest();
        $expires = date('Y-m-d', strtotime('+10 days'));
        set_config('expires', $expires, 'local_securitytxt');

        $messages = $this->run_task();
        $message = reset($messages);

        $this->assertStringContainsString('10 days', $message->subject);
        $this->assertStringContainsString($expires, $message->fullmessage);
        $this->assertStringContainsString('section=local_securitytxt', $message->fullmessage);
    }
}
