<?php

namespace App\Routes;

use App\Controllers\BlogActivityLogsController;
use App\Middleware\AuthMiddleware;

class BlogActivityLogsRoute extends BaseRoute
{
    public function register(): void
    {
        $this->app->group('/v1/activity-log', function ($group) {
            $group->get('', [BlogActivityLogsController::class, 'getLogs']);
            $group->get('/article/{id}', [BlogActivityLogsController::class, 'getLogsByArticle']);
            $group->get('/user/{id}', [BlogActivityLogsController::class, 'getLogsByUser']);
        })->add(new AuthMiddleware());
    }
}
