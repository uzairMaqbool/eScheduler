<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MeetingRequest;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;

class SendMeetingReminders extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'meetings:reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminders for upcoming meetings';

    /**
     * Execute the console command.
     */
    public function handle() {
        Log::info('Meeting Reminder Command Started');
        $now = now();

        $meetings = MeetingRequest::query()
            ->with([
                'senderParticipant.representative.user',
                'receiverParticipant.representative.user',
            ])
            ->where(
                'status',
                MeetingRequest::STATUS_ACCEPTED
            )
            ->whereNull('reminder_sent_at')
            ->whereHas('timeSlot', function ($q) use ($now) {
                $q->whereBetween(
                    'start_at',
                    [
                        $now,
                        $now->copy()->addMinutes(15),
                    ]
                );
            })
            ->get();

            Log::info('Upcoming Meetings Found', ['count' => $meetings->count()]);

        /* SEND REMINDERS */
        foreach ($meetings as $meeting) {
            $participants = collect([
                $meeting->senderParticipant,
                $meeting->receiverParticipant,
            ])->filter();

            Log::info('Sending Meeting Reminder', ['meeting_request_id' => $meeting->id]);

            foreach ($participants as $participant) {
                if ($participant?->representative?->user) {
                    Notification::create([
                        'user_id' => $participant->representative->user->id,
                        'type' => 'meeting_reminder',
                        'title' => 'Upcoming Meeting Reminder',
                        'message' =>
                            'You have an upcoming meeting in less than 15 minutes.',
                        'data' => json_encode([
                            'meeting_request_id' => $meeting->id,
                            'event_id'           => $meeting->event_id,
                            'time_slot_id'       => $meeting->time_slot_id,
                        ]),
                    ]);

                    Log::info('Meeting Reminder Sent', [
                        'meeting_request_id' => $meeting->id,
                        'user_id'            => $participant->representative->user->id,
                    ]);
                }
            }

            /* PREVENT DUPLICATE REMINDERS */
            $meeting->update([
                'reminder_sent_at' => $now
            ]);
        }
        Log::info('Meeting Reminder Command Completed');
    }
}
