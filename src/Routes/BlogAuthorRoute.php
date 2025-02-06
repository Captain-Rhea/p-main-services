<?php

namespace App\Routes;

use App\Controllers\BlogAuthorController;
use App\Middleware\AuthMiddleware;

class BlogAuthorRoute extends BaseRoute
{
    public function register(): void
    {
        $this->app->group('/v1/blog-author', function ($group) {
            $group->get('', [BlogAuthorController::class, 'getAuthors']);
            $group->post('', [BlogAuthorController::class, 'createAuthor']);
            $group->put('/{id}', [BlogAuthorController::class, 'updateAuthor']);
            $group->delete('/{id}', [BlogAuthorController::class, 'deleteAuthor']);
        })->add(new AuthMiddleware());
    }
}
