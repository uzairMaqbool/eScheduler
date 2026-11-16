<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\MeetingRequest;

class ExpireMeetingRequests extends Command {
    protected $signature = 'meetings:expire';

    protected $description = 'Expire pending meeting requests';

    public function handle() {
        $now = now();
        $meetings = MeetingRequest::query()
            ->where(
                'status',
                MeetingRequest::STATUS_PENDING
            )
            ->where(function ($q) use ($now) {
                /*
                |--------------------------------------------------------------------------
                | EXPLICIT EXPIRY
                |--------------------------------------------------------------------------
                */
                $q->where(function ($q2) use ($now) {
                    $q2->whereNotNull('expires_at')
                        ->where(
                            'expires_at',
                            '<=',
                            $now
                        );
                })

                /*
                |--------------------------------------------------------------------------
                | FALLBACK EXPIRY
                |--------------------------------------------------------------------------
                */
                ->orWhere(function ($q2) use ($now) {
                    $q2->whereNull('expires_at')
                        ->where(
                            'created_at',
                            '<=',
                            $now->copy()->subHours(5)
                        );
                });
            })
            ->get();

        $count = 0;
        $dashboardService = app(\App\Services\LiveMeetingDashboardService::class);
        foreach ($meetings as $meeting) {
            $meeting->update([
                'status'     => MeetingRequest::STATUS_EXPIRED,
                'expires_at' => $now,
            ]);
            $dashboardService->syncMeetingSnapshot(
                $meeting->fresh()
            );
            $count++;
        }

        Log::info('Meeting expiry executed', [
            'expired_count' => $count,
            'executed_at'   => $now->toDateTimeString(),
        ]);

        $this->info(
            "Expired meeting requests: {$count}"
        );
        return self::SUCCESS;
    }
}