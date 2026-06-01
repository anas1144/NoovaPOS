<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Models\NotificationOutbox;
use App\Services\NotificationDispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class NotificationAPIController extends AppBaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = NotificationOutbox::query();
        foreach (['channel', 'status', 'event'] as $f) {
            if ($request->filled($f)) {
                $query->where($f, $request->get($f));
            }
        }
        if ($request->boolean('unread_only')) {
            $query->whereNull('read_at')
                ->where('channel', NotificationOutbox::CHANNEL_IN_APP);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->get('user_id'));
        }

        return $this->sendResponse(
            $query->orderByDesc('id')->paginate(getPageSize($request)),
            'Notifications retrieved successfully.'
        );
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $count = NotificationOutbox::query()
            ->where('user_id', Auth::id())
            ->where('channel', NotificationOutbox::CHANNEL_IN_APP)
            ->whereNull('read_at')
            ->count();

        return $this->sendResponse(['count' => $count], 'Unread count retrieved.');
    }

    public function store(Request $request): JsonResponse
    {
        $input = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'customer_id' => 'nullable|exists:customers,id',
            'channel' => ['required', Rule::in([
                NotificationOutbox::CHANNEL_EMAIL,
                NotificationOutbox::CHANNEL_SMS,
                NotificationOutbox::CHANNEL_WHATSAPP,
                NotificationOutbox::CHANNEL_PUSH,
                NotificationOutbox::CHANNEL_IN_APP,
            ])],
            'event' => 'nullable|string|max:60',
            'subject' => 'nullable|string|max:255',
            'body' => 'nullable|string',
            'recipient' => 'nullable|string|max:255',
            'payload' => 'nullable|array',
        ]);
        $input['status'] = NotificationOutbox::STATUS_QUEUED;

        return $this->sendResponse(
            NotificationOutbox::create($input),
            'Notification queued successfully.'
        );
    }

    public function markRead(NotificationOutbox $notification): JsonResponse
    {
        $notification->update([
            'read_at' => now(),
            'status' => NotificationOutbox::STATUS_READ,
        ]);
        return $this->sendResponse($notification, 'Notification marked as read.');
    }

    public function markAllRead(Request $request): JsonResponse
    {
        NotificationOutbox::query()
            ->where('user_id', Auth::id())
            ->where('channel', NotificationOutbox::CHANNEL_IN_APP)
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
                'status' => NotificationOutbox::STATUS_READ,
            ]);
        return $this->sendSuccess('All in-app notifications marked as read.');
    }

    public function destroy(NotificationOutbox $notification): JsonResponse
    {
        $notification->delete();
        return $this->sendSuccess('Notification deleted.');
    }

    public function dispatchPending(NotificationDispatcher $dispatcher, Request $request): JsonResponse
    {
        $limit = max(1, min(500, (int) $request->get('limit', 50)));
        $result = $dispatcher->dispatchPending($limit);
        return $this->sendResponse($result, 'Notification dispatch run completed.');
    }
}
