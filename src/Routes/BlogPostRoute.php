<?php

namespace App\Routes;

use App\Controllers\BlogPostController;
use App\Middleware\AuthMiddleware;

class BlogPostRoute extends BaseRoute
{
    public function register(): void
    {
        $this->app->group('/v1/blog-posts', function ($group) {
            $group->get('', [BlogPostController::class, 'getBlogPosts']);
            $group->post('', [BlogPostController::class, 'createBlogPost']);
            $group->delete('/{id}', [BlogPostController::class, 'deleteBlogPost']);
            $group->get('/trashed', [BlogPostController::class, 'getTrashedBlogPosts']);
            $group->delete('/{id}/force', [BlogPostController::class, 'permanentlyDeleteBlogPost']);
        })->add(new AuthMiddleware());
    }
}
