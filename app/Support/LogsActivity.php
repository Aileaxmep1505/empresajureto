<?php

namespace App\Support;

use App\Services\Activity\ActivityLogger;

trait LogsActivity
{
    protected function logActivity(string $action, array $meta = [], array $extra = [])
    {
        return app(ActivityLogger::class)->log($action, $meta, request(), $extra);
    }
}