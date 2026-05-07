<?php

namespace App\Http\Controllers;

use App\Http\Requests\Notification\MarkTaskDueNotificationReadRequest;
use App\Services\TaskDueNotificationService;

class TaskDueNotificationController extends Controller
{
    public function __construct(private readonly TaskDueNotificationService $notificationService) {}

    public function index()
    {
        $user = auth('api')->user();

        if (! $user) {
            return $this->respondError('', null, 401);
        }

        return $this->respondSuccess($this->notificationService->index($user), '');
    }

    public function markRead(MarkTaskDueNotificationReadRequest $request)
    {
        $user = auth('api')->user();

        if (! $user) {
            return $this->respondError('', null, 401);
        }

        /** @var string $taskId */
        $taskId = $request->validated()['task'];
        $this->notificationService->markRead($user, $taskId);

        return $this->respondSuccess(['task_id' => $taskId], '');
    }

    public function markAllRead()
    {
        $user = auth('api')->user();

        if (! $user) {
            return $this->respondError('', null, 401);
        }

        $count = $this->notificationService->markAllRead($user);

        return $this->respondSuccess(['updated' => $count], '');
    }

    public function clearAll()
    {
        $user = auth('api')->user();

        if (! $user) {
            return $this->respondError('', null, 401);
        }

        $count = $this->notificationService->clearAll($user);

        return $this->respondSuccess(['updated' => $count], '');
    }
}
