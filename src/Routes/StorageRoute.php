<?php

namespace App\Routes;

use App\Controllers\StorageController;
use App\Middleware\AuthMiddleware;

class StorageRoute extends BaseRoute
{
    public function register(): void
    {
        $this->app->group('/v1/storage', function ($group) {
            $group->get('/image', [StorageController::class, 'getImageList']);
            $group->post('/image', [StorageController::class, 'uploadImage']);
            $group->put('/image/{id}', [StorageController::class, 'updateImage']);
            $group->delete('/image', [StorageController::class, 'deleteImages']);

            $group->get('/image/download', [StorageController::class, 'downloadImage']);
        })->add(new AuthMiddleware());
    }
}
