<?php

namespace App\Helpers;

use App\Models\BlogActivityLogModel;
use Ramsey\Uuid\Uuid;

class BlogActivityLogsHelper
{
    public static function logActivity(int $userId, ?string $postId, string $action, ?array $details = null): void
    {
        BlogActivityLogModel::create([
            'id' => Uuid::uuid4()->toString(),
            'user_id' => $userId,
            'post_id' => $postId,
            'action' => $action,
            'details' => $details ? json_encode($details, JSON_UNESCAPED_UNICODE) : null
        ]);
    }
}
