<?php

namespace App\Routes;

use App\Controllers\BlogArticleController;
use App\Middleware\AuthMiddleware;

class BlogArticleRoute extends BaseRoute
{
    public function register(): void
    {
        $this->app->group('/v1/blog', function ($group) {
            $group->get('/articles', [BlogArticleController::class, 'getBlogArticles']);
            $group->post('/article', [BlogArticleController::class, 'createBlogArticle']);
            $group->delete('/article/{id}', [BlogArticleController::class, 'deleteBlogArticle']);
            $group->get('/articles/trashed', [BlogArticleController::class, 'getTrashedBlogArticles']);
            $group->delete('/article/{id}/force', [BlogArticleController::class, 'permanentlyDeleteBlogArticle']);
        })->add(new AuthMiddleware());
    }
}
