<?php

namespace App\Console\Commands;

use App\Services\AppointmentReminderService;
use Illuminate\Console\Command;

class SendAppointmentReminders extends Command
{
    protected $signature = 'appointments:send-reminders';
    protected $description = 'Send app-only 1-day (advance) and 3-hour (same-day) appointment reminders and auto-cancel ignored reminders past their waiting time';

    public function handle()
    {
        $result = AppointmentReminderService::dispatchDueReminders();

        $this->info(
            "Done (App-Only). 1-Day reminders: {$result['sent_24h']}, 3-Hour same-day reminders: {$result['sent_3h']}, Auto-cancelled: {$result['auto_cancelled']}."
        );

        return 0;
    }
}
