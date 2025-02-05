<?php

namespace App\Routes;

use App\Controllers\CategoriesTagsController;
use App\Middleware\AuthMiddleware;

class CategoriesTagsRoute extends BaseRoute
{
    public function register(): void
    {
        $this->app->group('/v1/category', function ($group) {
            $group->get('', [CategoriesTagsController::class, 'getCategories']);
            $group->post('', [CategoriesTagsController::class, 'createCategory']);
            $group->put('/{id}', [CategoriesTagsController::class, 'updateCategory']);
            $group->delete('/{id}', [CategoriesTagsController::class, 'deleteCategory']);
            $group->get('/{id}/posts', [CategoriesTagsController::class, 'getCategoryLinkPost']);
        })->add(new AuthMiddleware());

        $this->app->group('/v1/tag', function ($group) {
            $group->get('', [CategoriesTagsController::class, 'getTags']);
            $group->post('', [CategoriesTagsController::class, 'createTag']);
            $group->put('/{id}', [CategoriesTagsController::class, 'updateTag']);
            $group->delete('/{id}', [CategoriesTagsController::class, 'deleteTag']);
            $group->get('/{id}/posts', [CategoriesTagsController::class, 'getTagLinkPost']);
        })->add(new AuthMiddleware());
    }
}
