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
        }); // ->add(new AuthMiddleware());
    }
}
