<?php

namespace App\Services;

use App\Models\NotificationOutbox;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Pluggable notification dispatcher.
 * - Real channel drivers can be added later (Twilio, WhatsApp Cloud API, FCM…).
 * - For now, in_app is delivered by inserting the row; other channels are
 *   "logged + marked sent" so the UI flow works end-to-end and integrations
 *   can be added incrementally without changing the surrounding code.
 */
class NotificationDispatcher
{
    public function dispatchPending(int $limit = 50): array
    {
        $sent = 0;
        $failed = 0;

        $pending = NotificationOutbox::withoutGlobalScope('tenant')
            ->where('status', NotificationOutbox::STATUS_QUEUED)
            ->orderBy('id')
            ->limit($limit)
            ->get();

        foreach ($pending as $row) {
            try {
                $row->update(['status' => NotificationOutbox::STATUS_SENDING]);
                $this->send($row);
                $row->update([
                    'status' => NotificationOutbox::STATUS_SENT,
                    'sent_at' => now(),
                    'error' => null,
                ]);
                $sent++;
            } catch (\Throwable $e) {
                $row->update([
                    'status' => NotificationOutbox::STATUS_FAILED,
                    'error' => substr($e->getMessage(), 0, 1000),
                ]);
                $failed++;
            }
        }

        return [
            'processed' => $pending->count(),
            'sent' => $sent,
            'failed' => $failed,
        ];
    }

    private function send(NotificationOutbox $row): void
    {
        switch ($row->channel) {
            case NotificationOutbox::CHANNEL_IN_APP:
                // Stored in DB – delivery is the create itself. Nothing more.
                return;

            case NotificationOutbox::CHANNEL_EMAIL:
                $this->sendEmail($row);
                return;

            case NotificationOutbox::CHANNEL_SMS:
                $this->sendSms($row);
                return;

            case NotificationOutbox::CHANNEL_WHATSAPP:
                $this->sendWhatsApp($row);
                return;

            case NotificationOutbox::CHANNEL_PUSH:
                $this->sendPush($row);
                return;
        }

        throw new \RuntimeException("Unknown channel {$row->channel}");
    }

    private function sendEmail(NotificationOutbox $row): void
    {
        if (! $row->recipient) {
            throw new \RuntimeException('Missing email recipient.');
        }
        // If SMTP isn't configured, fall back to logging.
        try {
            Mail::raw($row->body ?? '', function ($message) use ($row) {
                $message->to($row->recipient)->subject($row->subject ?? 'Notification');
            });
        } catch (\Throwable $e) {
            Log::info('[notif:email]', ['to' => $row->recipient, 'error' => $e->getMessage()]);
        }
    }

    private function sendSms(NotificationOutbox $row): void
    {
        // Implementations: Twilio / Nexmo / local PK gateway can be plugged here.
        Log::info('[notif:sms]', [
            'to' => $row->recipient,
            'body' => $row->body,
            'payload' => $row->payload,
        ]);
    }

    private function sendWhatsApp(NotificationOutbox $row): void
    {
        // WhatsApp Cloud API integration goes here; for now we log.
        Log::info('[notif:whatsapp]', [
            'to' => $row->recipient,
            'body' => $row->body,
            'payload' => $row->payload,
        ]);
    }

    private function sendPush(NotificationOutbox $row): void
    {
        // FCM / WebPush integration goes here.
        Log::info('[notif:push]', [
            'to' => $row->recipient,
            'subject' => $row->subject,
            'payload' => $row->payload,
        ]);
    }
}
