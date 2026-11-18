<?php

namespace App\Console\Commands;

use App\Services\AppointmentReminderService;
use Illuminate\Console\Command;

class SendAppointmentReminders extends Command
{
    protected $signature = 'appointments:send-reminders';
    protected $description = 'Send app-only interactive (1-day & 2-hour) reminders and auto-cancel ignored reminders past the 1-hour waiting window';

    public function handle()
    {
        $result = AppointmentReminderService::dispatchDueReminders();

        $this->info(
            "Done (App-Only). 1-Day reminders: {$result['sent_24h']}, 2-Hour reminders: {$result['sent_2h']}, Auto-cancelled: {$result['auto_cancelled']}."
        );

        return 0;
    }
}
