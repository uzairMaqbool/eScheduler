<?php

namespace App\Console\Commands;

use App\Models\Event;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AutoCloseEvents extends Command {
    protected $signature = 'events:auto-close';

    protected $description = 'Automatically close completed events';

    public function handle() {
        $now = now();
        $count = Event::query()
            ->where(
                'status',
                Event::STATUS_PUBLISHED
            )
            ->whereNotNull('end_date')
            ->whereDate(
                'end_date',
                '<',
                today()
            )
            ->update([
                'status'     => Event::STATUS_CLOSED,
                'updated_at' => $now,
            ]);

        Log::info('Auto close events executed', [
            'closed_count' => $count,
            'executed_at'  => $now->toDateTimeString(),
        ]);

        $this->info(
            "Auto-closed events: {$count}"
        );
        return self::SUCCESS;
    }
}