<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Services\SMSService;
use Illuminate\Console\Command;
use Carbon\Carbon;

class SendAppointmentReminders extends Command
{
    protected $signature = 'appointments:send-reminders';
    protected $description = 'Send SMS reminders to pet owners with appointments tomorrow';

    public function handle()
    {
        $targetDate = Carbon::tomorrow()->toDateString();

        $this->info("Looking for appointments on {$targetDate}...");

        $appointments = Appointment::with(['pet', 'user'])
            ->where('appointment_date', $targetDate)
            ->whereIn('status', ['pending', 'approved'])
            ->get();

        if ($appointments->isEmpty()) {
            $this->info('No appointments to remind about.');
            return 0;
        }

        $this->info("Found {$appointments->count()} appointment(s). Sending reminders...");

        $sent = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($appointments as $appointment) {
            if (empty($appointment->user->phone_number ?? null)) {
                $this->warn("Skipped appointment #{$appointment->id} — no phone number");
                $skipped++;
                continue;
            }

            $result = SMSService::sendAppointmentReminder($appointment);

            if ($result['success']) {
                $this->info("✅ Sent to {$appointment->user->phone_number} for appointment #{$appointment->id}");
                $sent++;
            } else {
                $this->error("❌ Failed for appointment #{$appointment->id}: {$result['error']}");
                $failed++;
            }
        }

        $this->info("Done. Sent: {$sent}, Failed: {$failed}, Skipped: {$skipped}");

        return 0;
    }
}
