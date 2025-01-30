<?php

namespace App\Routes;

use App\Controllers\StorageController;
use App\Middleware\AuthMiddleware;

class StorageRoute extends BaseRoute
{
    public function register(): void
    {
        $this->app->group('/v1/storage', function ($group) {
            $group->get('', [StorageController::class, 'getStorageUsed']);
            $group->get('/image', [StorageController::class, 'getImageList']);
            $group->post('/image', [StorageController::class, 'uploadImage']);
            $group->put('/image/{id}', [StorageController::class, 'updateImageName']);
            $group->delete('/image', [StorageController::class, 'deleteImages']);

            $group->post('/blog/image', [StorageController::class, 'uploadBlogImage']);
        })->add(new AuthMiddleware());
    }
}
