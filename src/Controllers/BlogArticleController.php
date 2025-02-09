<?php

namespace App\Controllers;

use App\Helpers\AuthAPIHelper;
use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Helpers\ResponseHandle;
use Illuminate\Database\Capsule\Manager as Capsule;
use App\Helpers\BlogActivityLogsHelper;
use Ramsey\Uuid\Uuid;
use App\Models\BlogArticleModel;

class BlogArticleController
{
    /**
     * GET /v1/blog-articles
     */
    public function getBlogArticles(Request $request, Response $response): Response
    {
        try {
            $page = (int)($request->getQueryParams()['page'] ?? 1);
            $limit = (int)($request->getQueryParams()['per_page'] ?? 10);
            $search = $request->getQueryParams()['search'] ?? null;
            $status = $request->getQueryParams()['status'] ?? null;
            $startDate = $request->getQueryParams()['start_date'] ?? null;
            $endDate = $request->getQueryParams()['end_date'] ?? null;

            $query = BlogArticleModel::orderBy('updated_at', 'desc');

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title_th', 'like', '%' . $search . '%')
                        ->orWhere('title_en', 'like', '%' . $search . '%')
                        ->orWhere('summary_th', 'like', '%' . $search . '%')
                        ->orWhere('summary_en', 'like', '%' . $search . '%');
                });
            }

            if ($status) {
                $query->where('status', $status);
            }

            if ($startDate) {
                $query->whereDate('created_at', '>=', $startDate);
            }

            if ($endDate) {
                $query->whereDate('created_at', '<=', $endDate);
            }

            $articles = $query->paginate($limit, ['*'], 'page', $page);

            if ($articles->isEmpty()) {
                return ResponseHandle::success($response, [
                    'pagination' => [
                        'current_page' => $articles->currentPage(),
                        'per_page' => $articles->perPage(),
                        'total' => $articles->total(),
                        'last_page' => $articles->lastPage(),
                    ],
                    'data' => []
                ], 'No blog articles found.');
            }

            $userIds = collect($articles->items())->pluck('created_by')
                ->merge(collect($articles->items())->pluck('updated_by'))
                ->merge(collect($articles->items())->pluck('published_by'))
                ->merge(collect($articles->items())->pluck('locked_by'))
                ->unique()
                ->filter()
                ->toArray();

            $members = collect();
            if (!empty($userIds)) {
                $memberResponse = AuthAPIHelper::get('/v1/member/batch', ['ids' => implode(',', $userIds)]);
                $memberResponseStatus = $memberResponse->getStatusCode();
                $memberResponseBody = json_decode($memberResponse->getBody()->getContents(), true);
                if ($memberResponseStatus >= 400) {
                    return ResponseHandle::apiError($response, $memberResponseBody, $memberResponseStatus);
                }
                $members = collect($memberResponseBody['data'])->keyBy('user_id');
            }

            $transformedData = $articles->map(function ($article) use ($members) {
                return [
                    'id' => $article->id,
                    'slug' => $article->slug,
                    'cover_image' => $article->cover_image,
                    'title_th' => $article->title_th,
                    'title_en' => $article->title_en,
                    'summary_th' => $article->summary_th,
                    'summary_en' => $article->summary_en,
                    'status' => $article->status,
                    'published_by' => $members->get($article['published_by'], null),
                    'published_at' => $article->published_at ? $article->published_at->toDateTimeString() : null,
                    'locked_by' => $members->get($article['locked_by'], null),
                    'locked_at' => $article->locked_at ? $article->locked_at->toDateTimeString() : null,
                    'created_by' => $members->get($article['created_by'], null),
                    'created_at' => $article->created_at->toDateTimeString(),
                    'updated_by' => $members->get($article['updated_by'], null),
                    'updated_at' => $article->updated_at->toDateTimeString(),
                ];
            }, $articles->items());

            $data = [
                'pagination' => [
                    'current_page' => $articles->currentPage(),
                    'per_page' => $articles->perPage(),
                    'total' => $articles->total(),
                    'last_page' => $articles->lastPage(),
                ],
                'data' => $transformedData
            ];

            return ResponseHandle::success($response, $data, 'Blog articles retrieved successfully');
        } catch (Exception $e) {
            return ResponseHandle::error($response, $e->getMessage(), 500);
        }
    }

    /**
     * POST /v1/blog-article
     */
    public function createBlogArticle(Request $request, Response $response): Response
    {
        try {
            $currentUser = $request->getAttribute('user');

            $articleId = Uuid::uuid4()->toString();

            Capsule::beginTransaction();

            $article = BlogArticleModel::create([
                'id' => $articleId,
                'created_by' => $currentUser['user_id'],
                'updated_by' => $currentUser['user_id'],
            ]);

            BlogActivityLogsHelper::logActivity($currentUser['user_id'], $articleId, 'created');

            Capsule::commit();

            return ResponseHandle::success($response, [
                'id' => $article->id,
                'slug' => $article->slug,
                'status' => $article->status,
                'created_at' => $article->created_at->toDateTimeString(),
                'updated_at' => $article->updated_at->toDateTimeString(),
            ], 'Blog article created successfully');
        } catch (Exception $e) {
            Capsule::rollBack();
            return ResponseHandle::error($response, $e->getMessage(), 500);
        }
    }

    /**
     * DELETE /v1/blog-article/{id}
     */
    public function deleteBlogArticle(Request $request, Response $response, array $args): Response
    {
        try {
            $currentUser = $request->getAttribute('user');
            $articleId = $args['id'] ?? null;

            if (!$articleId) {
                return ResponseHandle::error($response, 'Blog article ID is required.', 400);
            }

            $article = BlogArticleModel::where('id', $articleId)->first();

            if (!$article) {
                return ResponseHandle::error($response, 'Blog article not found.', 404);
            }

            Capsule::beginTransaction();

            $article->update([
                'deleted_by' => $currentUser['user_id']
            ]);

            $article->delete();

            BlogActivityLogsHelper::logActivity($currentUser['user_id'], $articleId, 'deleted', ['action' => 'Soft Delete']);

            Capsule::commit();

            return ResponseHandle::success($response, null, 'Blog article deleted successfully');
        } catch (Exception $e) {
            Capsule::rollBack();
            return ResponseHandle::error($response, $e->getMessage(), 500);
        }
    }

    /**
     * GET /v1/blog-articles/trashed
     */
    public function getTrashedBlogArticles(Request $request, Response $response): Response
    {
        try {
            $page = (int)($request->getQueryParams()['page'] ?? 1);
            $limit = (int)($request->getQueryParams()['per_page'] ?? 10);
            $search = $request->getQueryParams()['search'] ?? null;
            $startDate = $request->getQueryParams()['start_date'] ?? null;
            $endDate = $request->getQueryParams()['end_date'] ?? null;

            $query = BlogArticleModel::onlyTrashed()->orderBy('deleted_at', 'desc');

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title_th', 'like', '%' . $search . '%')
                        ->orWhere('title_en', 'like', '%' . $search . '%')
                        ->orWhere('summary_th', 'like', '%' . $search . '%')
                        ->orWhere('summary_en', 'like', '%' . $search . '%');
                });
            }

            if ($startDate) {
                $query->whereDate('deleted_at', '>=', $startDate);
            }

            if ($endDate) {
                $query->whereDate('deleted_at', '<=', $endDate);
            }

            $articles = $query->paginate($limit, ['*'], 'page', $page);

            if ($articles->isEmpty()) {
                return ResponseHandle::success($response, [
                    'pagination' => [
                        'current_page' => $articles->currentPage(),
                        'per_page' => $articles->perPage(),
                        'total' => $articles->total(),
                        'last_page' => $articles->lastPage(),
                    ],
                    'data' => []
                ], 'No soft deleted blog articles found.');
            }

            $userIds = collect($articles->items())->pluck('created_by')
                ->merge(collect($articles->items())->pluck('updated_by'))
                ->merge(collect($articles->items())->pluck('deleted_by'))
                ->unique()
                ->filter()
                ->toArray();

            $members = collect();
            if (!empty($userIds)) {
                $memberResponse = AuthAPIHelper::get('/v1/member/batch', ['ids' => implode(',', $userIds)]);
                $memberResponseStatus = $memberResponse->getStatusCode();
                $memberResponseBody = json_decode($memberResponse->getBody()->getContents(), true);
                if ($memberResponseStatus >= 400) {
                    return ResponseHandle::apiError($response, $memberResponseBody, $memberResponseStatus);
                }
                $members = collect($memberResponseBody['data'])->keyBy('user_id');
            }

            $transformedData = $articles->map(function ($article) use ($members) {
                return [
                    'id' => $article->id,
                    'slug' => $article->slug,
                    'cover_image' => $article->cover_image,
                    'title_th' => $article->title_th,
                    'title_en' => $article->title_en,
                    'summary_th' => $article->summary_th,
                    'summary_en' => $article->summary_en,
                    'status' => $article->status,
                    'deleted_by' => $members->get($article['deleted_by'], null),
                    'deleted_at' => $article->deleted_at ? $article->deleted_at->toDateTimeString() : null,
                    'created_by' => $members->get($article['created_by'], null),
                    'created_at' => $article->created_at->toDateTimeString(),
                    'updated_by' => $members->get($article['updated_by'], null),
                    'updated_at' => $article->updated_at->toDateTimeString(),
                ];
            }, $articles->items());

            $data = [
                'pagination' => [
                    'current_page' => $articles->currentPage(),
                    'per_page' => $articles->perPage(),
                    'total' => $articles->total(),
                    'last_page' => $articles->lastPage(),
                ],
                'data' => $transformedData
            ];

            return ResponseHandle::success($response, $data, 'Soft deleted blog articles retrieved successfully');
        } catch (Exception $e) {
            return ResponseHandle::error($response, $e->getMessage(), 500);
        }
    }

    /**
     * DELETE /v1/blog-article/{id}/force
     */
    public function permanentlyDeleteBlogArticle(Request $request, Response $response, array $args): Response
    {
        try {
            $currentUser = $request->getAttribute('user');
            $articleId = $args['id'] ?? null;

            if (!$articleId) {
                return ResponseHandle::error($response, 'Blog article ID is required.', 400);
            }

            $article = BlogArticleModel::withTrashed()->where('id', $articleId)->first();

            if (!$article) {
                return ResponseHandle::error($response, 'Blog article not found.', 404);
            }

            if (!$article->trashed()) {
                return ResponseHandle::error($response, 'Blog article must be soft deleted before permanently deleting.', 400);
            }

            Capsule::beginTransaction();

            $deletedArticleData = [
                'id' => $article->id,
                'slug' => $article->slug,
                'title_th' => $article->title_th,
                'title_en' => $article->title_en,
                'summary_th' => $article->summary_th,
                'summary_en' => $article->summary_en,
                'status' => $article->status,
                'cover_image' => $article->cover_image,
                'published_by' => $article->published_by,
                'published_at' => $article->published_at ? $article->published_at->toDateTimeString() : null,
                'locked_by' => $article->locked_by,
                'locked_at' => $article->locked_at ? $article->locked_at->toDateTimeString() : null,
                'created_by' => $article->created_by,
                'created_at' => $article->created_at->toDateTimeString(),
                'updated_by' => $article->updated_by,
                'updated_at' => $article->updated_at->toDateTimeString(),
                'deleted_by' => $article->deleted_by,
                'deleted_at' => $article->deleted_at->toDateTimeString(),
            ];

            BlogActivityLogsHelper::logActivity($currentUser['user_id'], $articleId, 'permanently_deleted', $deletedArticleData);

            $article->forceDelete();

            Capsule::commit();

            return ResponseHandle::success($response, null, 'Blog article permanently deleted successfully');
        } catch (Exception $e) {
            Capsule::rollBack();
            return ResponseHandle::error($response, $e->getMessage(), 500);
        }
    }
}
