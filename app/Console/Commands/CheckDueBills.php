<?php

namespace App\Console\Commands;

use App\Models\RecurringBill;
use App\Notifications\BillDueReminder;
use Illuminate\Console\Command;
use Carbon\Carbon;

class CheckDueBills extends Command
{
    protected $signature = 'bills:check-due';
    protected $description = 'Cek tagihan berulang yang jatuh tempo dalam 3 hari dan kirim reminder';

    public function handle()
    {
        $today = Carbon::now();
        $reminderThreshold = $today->copy()->addDays(3);

        $bills = RecurringBill::where('is_active', true)
            ->where(function ($query) use ($today, $reminderThreshold) {
                $query->whereRaw('due_day BETWEEN ? AND ?', [$today->day, $reminderThreshold->day]);
            })
            ->with('user')
            ->get();

        $sentCount = 0;

        foreach ($bills as $bill) {
            // Hindari kirim reminder berkali-kali di hari yang sama
            if ($bill->last_notified_at && $bill->last_notified_at->isToday()) {
                continue;
            }

            $bill->user->notify(new BillDueReminder($bill));
            $bill->update(['last_notified_at' => now()]);
            $sentCount++;
        }

        $this->info("Reminder terkirim untuk {$sentCount} tagihan.");

        return Command::SUCCESS;
    }
}