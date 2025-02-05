<?php

namespace App\Routes;

use App\Controllers\BlogActivityLogsController;
use App\Middleware\AuthMiddleware;

class BlogActivityLogsRoute extends BaseRoute
{
    public function register(): void
    {
        $this->app->group('/v1/activity-log', function ($group) {
            $group->get('/post/{id}', [BlogActivityLogsController::class, 'getLogsByPost']);
            $group->get('/user/{id}', [BlogActivityLogsController::class, 'getLogsByUser']);
        })->add(new AuthMiddleware());
    }
}
