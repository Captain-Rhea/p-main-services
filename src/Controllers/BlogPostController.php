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
use App\Models\BlogPostModel;

class BlogPostController
{
    /**
     * GET /v1/blog-posts
     */
    public function getBlogPosts(Request $request, Response $response): Response
    {
        try {
            $page = (int)($request->getQueryParams()['page'] ?? 1);
            $limit = (int)($request->getQueryParams()['per_page'] ?? 10);
            $search = $request->getQueryParams()['search'] ?? null;
            $status = $request->getQueryParams()['status'] ?? null;
            $startDate = $request->getQueryParams()['start_date'] ?? null;
            $endDate = $request->getQueryParams()['end_date'] ?? null;

            $query = BlogPostModel::orderBy('updated_at', 'desc');

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

            $posts = $query->paginate($limit, ['*'], 'page', $page);

            if ($posts->isEmpty()) {
                return ResponseHandle::success($response, [
                    'pagination' => [
                        'current_page' => $posts->currentPage(),
                        'per_page' => $posts->perPage(),
                        'total' => $posts->total(),
                        'last_page' => $posts->lastPage(),
                    ],
                    'data' => []
                ], 'No blog posts found.');
            }

            $userIds = collect($posts->items())->pluck('created_by')
                ->merge(collect($posts->items())->pluck('updated_by'))
                ->merge(collect($posts->items())->pluck('published_by'))
                ->merge(collect($posts->items())->pluck('locked_by'))
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

            $transformedData = $posts->map(function ($post) use ($members) {
                return [
                    'id' => $post->id,
                    'slug' => $post->slug,
                    'cover_image' => $post->cover_image,
                    'title_th' => $post->title_th,
                    'title_en' => $post->title_en,
                    'summary_th' => $post->summary_th,
                    'summary_en' => $post->summary_en,
                    'status' => $post->status,
                    'published_by' => $members->get($post['published_by'], null),
                    'published_at' => $post->published_at ? $post->published_at->toDateTimeString() : null,
                    'locked_by' => $members->get($post['locked_by'], null),
                    'locked_at' => $post->locked_at ? $post->locked_at->toDateTimeString() : null,
                    'created_by' => $members->get($post['created_by'], null),
                    'created_at' => $post->created_at->toDateTimeString(),
                    'updated_by' => $members->get($post['updated_by'], null),
                    'updated_at' => $post->updated_at->toDateTimeString(),
                ];
            }, $posts->items());

            $data = [
                'pagination' => [
                    'current_page' => $posts->currentPage(),
                    'per_page' => $posts->perPage(),
                    'total' => $posts->total(),
                    'last_page' => $posts->lastPage(),
                ],
                'data' => $transformedData
            ];

            return ResponseHandle::success($response, $data, 'Blog posts retrieved successfully');
        } catch (Exception $e) {
            return ResponseHandle::error($response, $e->getMessage(), 500);
        }
    }

    /**
     * POST /v1/blog-posts
     */
    public function createBlogPost(Request $request, Response $response): Response
    {
        try {
            $currentUser = $request->getAttribute('user');

            $postId = Uuid::uuid4()->toString();

            Capsule::beginTransaction();

            $post = BlogPostModel::create([
                'id' => $postId,
                'created_by' => $currentUser['user_id'],
                'updated_by' => $currentUser['user_id'],
            ]);

            BlogActivityLogsHelper::logActivity($currentUser['user_id'], $postId, 'created');

            Capsule::commit();

            return ResponseHandle::success($response, [
                'id' => $post->id,
                'slug' => $post->slug,
                'status' => $post->status,
                'created_at' => $post->created_at->toDateTimeString(),
                'updated_at' => $post->updated_at->toDateTimeString(),
            ], 'Blog post created successfully');
        } catch (Exception $e) {
            Capsule::rollBack();
            return ResponseHandle::error($response, $e->getMessage(), 500);
        }
    }

    /**
     * DELETE /v1/blog-posts/{id}
     */
    public function deleteBlogPost(Request $request, Response $response, array $args): Response
    {
        try {
            $currentUser = $request->getAttribute('user');
            $postId = $args['id'] ?? null;

            if (!$postId) {
                return ResponseHandle::error($response, 'Blog post ID is required.', 400);
            }

            $post = BlogPostModel::where('id', $postId)->first();

            if (!$post) {
                return ResponseHandle::error($response, 'Blog post not found.', 404);
            }

            Capsule::beginTransaction();

            $post->update([
                'deleted_by' => $currentUser['user_id']
            ]);

            $post->delete();

            BlogActivityLogsHelper::logActivity($currentUser['user_id'], $postId, 'deleted', ['action' => 'Soft Delete']);

            Capsule::commit();

            return ResponseHandle::success($response, null, 'Blog post deleted successfully');
        } catch (Exception $e) {
            Capsule::rollBack();
            return ResponseHandle::error($response, $e->getMessage(), 500);
        }
    }

    /**
     * GET /v1/blog-posts/trashed
     */
    public function getTrashedBlogPosts(Request $request, Response $response): Response
    {
        try {
            $page = (int)($request->getQueryParams()['page'] ?? 1);
            $limit = (int)($request->getQueryParams()['per_page'] ?? 10);
            $search = $request->getQueryParams()['search'] ?? null;
            $startDate = $request->getQueryParams()['start_date'] ?? null;
            $endDate = $request->getQueryParams()['end_date'] ?? null;

            $query = BlogPostModel::onlyTrashed()->orderBy('deleted_at', 'desc');

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

            $posts = $query->paginate($limit, ['*'], 'page', $page);

            if ($posts->isEmpty()) {
                return ResponseHandle::success($response, [
                    'pagination' => [
                        'current_page' => $posts->currentPage(),
                        'per_page' => $posts->perPage(),
                        'total' => $posts->total(),
                        'last_page' => $posts->lastPage(),
                    ],
                    'data' => []
                ], 'No soft deleted blog posts found.');
            }

            $userIds = collect($posts->items())->pluck('created_by')
                ->merge(collect($posts->items())->pluck('updated_by'))
                ->merge(collect($posts->items())->pluck('deleted_by'))
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

            $transformedData = $posts->map(function ($post) use ($members) {
                return [
                    'id' => $post->id,
                    'slug' => $post->slug,
                    'cover_image' => $post->cover_image,
                    'title_th' => $post->title_th,
                    'title_en' => $post->title_en,
                    'summary_th' => $post->summary_th,
                    'summary_en' => $post->summary_en,
                    'status' => $post->status,
                    'deleted_by' => $members->get($post['deleted_by'], null),
                    'deleted_at' => $post->deleted_at ? $post->deleted_at->toDateTimeString() : null,
                    'created_by' => $members->get($post['created_by'], null),
                    'created_at' => $post->created_at->toDateTimeString(),
                    'updated_by' => $members->get($post['updated_by'], null),
                    'updated_at' => $post->updated_at->toDateTimeString(),
                ];
            }, $posts->items());

            $data = [
                'pagination' => [
                    'current_page' => $posts->currentPage(),
                    'per_page' => $posts->perPage(),
                    'total' => $posts->total(),
                    'last_page' => $posts->lastPage(),
                ],
                'data' => $transformedData
            ];

            return ResponseHandle::success($response, $data, 'Soft deleted blog posts retrieved successfully');
        } catch (Exception $e) {
            return ResponseHandle::error($response, $e->getMessage(), 500);
        }
    }

    /**
     * DELETE /v1/blog-posts/{id}/force
     */
    public function permanentlyDeleteBlogPost(Request $request, Response $response, array $args): Response
    {
        try {
            $currentUser = $request->getAttribute('user');
            $postId = $args['id'] ?? null;

            if (!$postId) {
                return ResponseHandle::error($response, 'Blog post ID is required.', 400);
            }

            $post = BlogPostModel::withTrashed()->where('id', $postId)->first();

            if (!$post) {
                return ResponseHandle::error($response, 'Blog post not found.', 404);
            }

            if (!$post->trashed()) {
                return ResponseHandle::error($response, 'Blog post must be soft deleted before permanently deleting.', 400);
            }

            Capsule::beginTransaction();

            $deletedPostData = [
                'id' => $post->id,
                'slug' => $post->slug,
                'title_th' => $post->title_th,
                'title_en' => $post->title_en,
                'summary_th' => $post->summary_th,
                'summary_en' => $post->summary_en,
                'status' => $post->status,
                'cover_image' => $post->cover_image,
                'published_by' => $post->published_by,
                'published_at' => $post->published_at ? $post->published_at->toDateTimeString() : null,
                'locked_by' => $post->locked_by,
                'locked_at' => $post->locked_at ? $post->locked_at->toDateTimeString() : null,
                'created_by' => $post->created_by,
                'created_at' => $post->created_at->toDateTimeString(),
                'updated_by' => $post->updated_by,
                'updated_at' => $post->updated_at->toDateTimeString(),
                'deleted_by' => $post->deleted_by,
                'deleted_at' => $post->deleted_at->toDateTimeString(),
            ];

            BlogActivityLogsHelper::logActivity($currentUser['user_id'], $postId, 'permanently_deleted', $deletedPostData);

            $post->forceDelete();

            Capsule::commit();

            return ResponseHandle::success($response, null, 'Blog post permanently deleted successfully');
        } catch (Exception $e) {
            Capsule::rollBack();
            return ResponseHandle::error($response, $e->getMessage(), 500);
        }
    }
}
