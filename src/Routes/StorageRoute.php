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

            $group->get('/image-storage', [StorageController::class, 'getImageStorage']);
            $group->post('/image-storage', [StorageController::class, 'uploadImageStorage']);
            $group->delete('/image-storage', [StorageController::class, 'deleteImageStorage']);
            $group->delete('/images-storage', [StorageController::class, 'multipleDeleteImagesStorage']);
            $group->post('/image-storage/name', [StorageController::class, 'editImageStorageName']);
            $group->get('/image-storage/download', [StorageController::class, 'downloadImageStorage']);
        })->add(new AuthMiddleware());
    }
}
