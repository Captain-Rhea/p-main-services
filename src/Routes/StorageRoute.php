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

            $group->get('/blog/image', [StorageController::class, 'getBlogImage']);
            $group->post('/blog/image', [StorageController::class, 'uploadBlogImage']);
            $group->delete('/blog/image', [StorageController::class, 'deleteBlogImage']);
            $group->get('/blog/image/download', [StorageController::class, 'downloadBlogImage']);
            $group->post('/blog/image/name', [StorageController::class, 'editBlogImageName']);
        })->add(new AuthMiddleware());
    }
}
