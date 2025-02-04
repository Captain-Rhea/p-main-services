<?php

namespace App\Controllers;

use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Capsule\Manager as Capsule;
use App\Helpers\ResponseHandle;
use App\Models\BlogCategoryModel;
use App\Models\BlogTagModel;

class CategoriesTagsController
{
    /**
     * GET /v1/category
     */
    public function getCategories(Request $request, Response $response): Response
    {
        try {
            $page = (int)($request->getQueryParams()['page'] ?? 1);
            $limit = (int)($request->getQueryParams()['per_page'] ?? 10);
            $categoryName = $request->getQueryParams()['name'] ?? null;
            $startDate = $request->getQueryParams()['start_date'] ?? null;
            $endDate = $request->getQueryParams()['end_date'] ?? null;

            $query = BlogCategoryModel::orderBy('updated_at', 'desc');

            if ($categoryName) {
                $query->where(function ($q) use ($categoryName) {
                    $q->where('name_th', 'like', '%' . $categoryName . '%')
                        ->orWhere('name_en', 'like', '%' . $categoryName . '%');
                });
            }

            if ($startDate) {
                $query->whereDate('created_at', '>=', $startDate);
            }

            if ($endDate) {
                $query->whereDate('created_at', '<=', $endDate);
            }

            $categories = $query->paginate($limit, ['*'], 'page', $page);

            $transformedData = array_map(function ($category) {
                return [
                    'id' => $category->id,
                    'name_th' => $category->name_th,
                    'name_en' => $category->name_en,
                    'slug' => $category->slug,
                    'created_at' => $category->created_at->toDateTimeString(),
                    'updated_at' => $category->updated_at->toDateTimeString(),
                ];
            }, $categories->items());

            $data = [
                'pagination' => [
                    'current_page' => $categories->currentPage(),
                    'per_page' => $categories->perPage(),
                    'total' => $categories->total(),
                    'last_page' => $categories->lastPage(),
                ],
                'data' => $transformedData
            ];

            return ResponseHandle::success($response, $data, 'Categories list retrieved successfully');
        } catch (Exception $e) {
            return ResponseHandle::error($response, $e->getMessage(), 500);
        }
    }

    /**
     * GET /v1/category/{id}/posts
     */
    public function getCategoryLinkPost(Request $request, Response $response, array $args): Response
    {
        try {
            $categoryId = $args['id'] ?? null;

            if (!$categoryId) {
                return ResponseHandle::error($response, 'Category ID is required.', 400);
            }

            $category = BlogCategoryModel::where('id', $categoryId)->first();

            if (!$category) {
                return ResponseHandle::error($response, 'Category not found.', 404);
            }

            $page = (int)($request->getQueryParams()['page'] ?? 1);
            $limit = (int)($request->getQueryParams()['per_page'] ?? 10);

            $posts = $category->posts()->orderBy('updated_at', 'desc')->paginate($limit, ['*'], 'page', $page);

            $transformedData = array_map(function ($post) {
                return [
                    'id' => $post->id,
                    'title_th' => $post->title_th,
                    'title_en' => $post->title_en,
                    'slug' => $post->slug,
                    'created_at' => $post->created_at->toDateTimeString(),
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

            return ResponseHandle::success($response, $data, 'Posts linked to category retrieved successfully');
        } catch (Exception $e) {
            return ResponseHandle::error($response, $e->getMessage(), 500);
        }
    }

    /**
     * POST /v1/category
     */
    public function createCategory(Request $request, Response $response): Response
    {
        try {
            $body = json_decode($request->getBody()->getContents(), true);

            if (!$body || !isset($body['name_th'], $body['name_en'])) {
                return ResponseHandle::error($response, 'Invalid request body.', 400);
            }

            $nameTh = trim($body['name_th']);
            $nameEn = trim($body['name_en']);

            if (BlogCategoryModel::where('name_th', $nameTh)->orWhere('name_en', $nameEn)->exists()) {
                return ResponseHandle::error($response, 'Category name already exists.', 400);
            }

            Capsule::beginTransaction();

            $category = BlogCategoryModel::create([
                'name_th' => $nameTh,
                'name_en' => $nameEn,
            ]);

            Capsule::commit();

            return ResponseHandle::success($response, [
                'id' => $category->id,
                'name_th' => $category->name_th,
                'name_en' => $category->name_en,
                'slug' => $category->slug,
                'created_at' => $category->created_at->toDateTimeString(),
                'updated_at' => $category->updated_at->toDateTimeString(),
            ], 'Category created successfully');
        } catch (Exception $e) {
            Capsule::rollBack();
            return ResponseHandle::error($response, $e->getMessage(), 500);
        }
    }

    /**
     * PUT /v1/category/{id}
     */
    public function updateCategory(Request $request, Response $response, array $args): Response
    {
        try {
            $categoryId = $args['id'] ?? null;

            if (!$categoryId) {
                return ResponseHandle::error($response, 'Category ID is required.', 400);
            }

            $category = BlogCategoryModel::where('id', $categoryId)->first();

            if (!$category) {
                return ResponseHandle::error($response, 'Category not found.', 404);
            }

            $body = json_decode($request->getBody()->getContents(), true);

            if (!$body || !isset($body['name_th'], $body['name_en'])) {
                return ResponseHandle::error($response, 'Invalid request body.', 400);
            }

            $nameTh = trim($body['name_th']);
            $nameEn = trim($body['name_en']);

            $exists = BlogCategoryModel::where('id', '!=', $categoryId)
                ->where(function ($query) use ($nameTh, $nameEn) {
                    $query->where('name_th', $nameTh)->orWhere('name_en', $nameEn);
                })
                ->exists();

            if ($exists) {
                return ResponseHandle::error($response, 'Category name already exists.', 400);
            }

            Capsule::beginTransaction();

            $category->update([
                'name_th' => $nameTh,
                'name_en' => $nameEn,
            ]);

            Capsule::commit();

            return ResponseHandle::success($response, [
                'id' => $category->id,
                'name_th' => $category->name_th,
                'name_en' => $category->name_en,
                'slug' => $category->slug,
                'created_at' => $category->created_at->toDateTimeString(),
                'updated_at' => $category->updated_at->toDateTimeString(),
            ], 'Category updated successfully');
        } catch (Exception $e) {
            Capsule::rollBack();
            return ResponseHandle::error($response, $e->getMessage(), 500);
        }
    }

    /**
     * DELETE /v1/category/{id}
     */
    public function deleteCategory(Request $request, Response $response, array $args): Response
    {
        try {
            $categoryId = $args['id'] ?? null;

            if (!$categoryId) {
                return ResponseHandle::error($response, 'Category ID is required.', 400);
            }

            $category = BlogCategoryModel::where('id', $categoryId)->first();

            if (!$category) {
                return ResponseHandle::error($response, 'Category not found.', 404);
            }

            if ($category->posts()->exists()) {
                return ResponseHandle::error($response, 'Cannot delete category because it is linked to blog posts.', 400);
            }

            Capsule::beginTransaction();

            $category->posts()->detach();

            $category->delete();

            Capsule::commit();

            return ResponseHandle::success($response, null, 'Category deleted successfully');
        } catch (Exception $e) {
            Capsule::rollBack();
            return ResponseHandle::error($response, $e->getMessage(), 500);
        }
    }

    /**
     * GET /v1/tag
     */
    public function getTags(Request $request, Response $response): Response
    {
        try {
            $page = (int)($request->getQueryParams()['page'] ?? 1);
            $limit = (int)($request->getQueryParams()['per_page'] ?? 10);
            $tagName = $request->getQueryParams()['name'] ?? null;

            $query = BlogTagModel::orderBy('updated_at', 'desc');

            if ($tagName) {
                $query->where(function ($q) use ($tagName) {
                    $q->where('name_th', 'like', '%' . $tagName . '%')
                        ->orWhere('name_en', 'like', '%' . $tagName . '%');
                });
            }

            $tags = $query->paginate($limit, ['*'], 'page', $page);

            $transformedData = array_map(function ($tag) {
                return [
                    'id' => $tag->id,
                    'name_th' => $tag->name_th,
                    'name_en' => $tag->name_en,
                    'slug' => $tag->slug,
                    'created_at' => $tag->created_at->toDateTimeString(),
                    'updated_at' => $tag->updated_at->toDateTimeString(),
                ];
            }, $tags->items());

            $data = [
                'pagination' => [
                    'current_page' => $tags->currentPage(),
                    'per_page' => $tags->perPage(),
                    'total' => $tags->total(),
                    'last_page' => $tags->lastPage(),
                ],
                'data' => $transformedData
            ];

            return ResponseHandle::success($response, $data, 'Tags list retrieved successfully');
        } catch (Exception $e) {
            return ResponseHandle::error($response, $e->getMessage(), 500);
        }
    }

    /**
     * GET /v1/tag/{id}/posts
     */
    public function getTagLinkPost(Request $request, Response $response, array $args): Response
    {
        try {
            $tagId = $args['id'] ?? null;

            if (!$tagId) {
                return ResponseHandle::error($response, 'Tag ID is required.', 400);
            }

            $tag = BlogTagModel::where('id', $tagId)->first();

            if (!$tag) {
                return ResponseHandle::error($response, 'Tag not found.', 404);
            }

            $page = (int)($request->getQueryParams()['page'] ?? 1);
            $limit = (int)($request->getQueryParams()['per_page'] ?? 10);

            $posts = $tag->posts()->orderBy('updated_at', 'desc')->paginate($limit, ['*'], 'page', $page);

            $transformedData = array_map(function ($post) {
                return [
                    'id' => $post->id,
                    'title_th' => $post->title_th,
                    'title_en' => $post->title_en,
                    'slug' => $post->slug,
                    'created_at' => $post->created_at->toDateTimeString(),
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

            return ResponseHandle::success($response, $data, 'Posts linked to tag retrieved successfully');
        } catch (Exception $e) {
            return ResponseHandle::error($response, $e->getMessage(), 500);
        }
    }

    /**
     * POST /v1/tag
     */
    public function createTag(Request $request, Response $response): Response
    {
        try {
            $body = json_decode($request->getBody()->getContents(), true);

            if (!$body || !isset($body['name_th'], $body['name_en'])) {
                return ResponseHandle::error($response, 'Invalid request body.', 400);
            }

            $nameTh = trim($body['name_th']);
            $nameEn = trim($body['name_en']);

            if (BlogTagModel::where('name_th', $nameTh)->orWhere('name_en', $nameEn)->exists()) {
                return ResponseHandle::error($response, 'Tag name already exists.', 400);
            }

            Capsule::beginTransaction();

            $tag = BlogTagModel::create([
                'name_th' => $nameTh,
                'name_en' => $nameEn,
            ]);

            Capsule::commit();

            return ResponseHandle::success($response, [
                'id' => $tag->id,
                'name_th' => $tag->name_th,
                'name_en' => $tag->name_en,
                'slug' => $tag->slug,
                'created_at' => $tag->created_at->toDateTimeString(),
                'updated_at' => $tag->updated_at->toDateTimeString(),
            ], 'Tag created successfully');
        } catch (Exception $e) {
            Capsule::rollBack();
            return ResponseHandle::error($response, $e->getMessage(), 500);
        }
    }

    /**
     * PUT /v1/tag/{id}
     */
    public function updateTag(Request $request, Response $response, array $args): Response
    {
        try {
            $tagId = $args['id'] ?? null;

            if (!$tagId) {
                return ResponseHandle::error($response, 'Tag ID is required.', 400);
            }

            $tag = BlogTagModel::where('id', $tagId)->first();

            if (!$tag) {
                return ResponseHandle::error($response, 'Tag not found.', 404);
            }

            $body = json_decode($request->getBody()->getContents(), true);

            if (!$body || !isset($body['name_th'], $body['name_en'])) {
                return ResponseHandle::error($response, 'Invalid request body.', 400);
            }

            $nameTh = trim($body['name_th']);
            $nameEn = trim($body['name_en']);

            $exists = BlogTagModel::where('id', '!=', $tagId)
                ->where(function ($query) use ($nameTh, $nameEn) {
                    $query->where('name_th', $nameTh)->orWhere('name_en', $nameEn);
                })
                ->exists();

            if ($exists) {
                return ResponseHandle::error($response, 'Tag name already exists.', 400);
            }

            Capsule::beginTransaction();

            $tag->update([
                'name_th' => $nameTh,
                'name_en' => $nameEn,
            ]);

            Capsule::commit();

            return ResponseHandle::success($response, [
                'id' => $tag->id,
                'name_th' => $tag->name_th,
                'name_en' => $tag->name_en,
                'slug' => $tag->slug,
                'created_at' => $tag->created_at->toDateTimeString(),
                'updated_at' => $tag->updated_at->toDateTimeString(),
            ], 'Tag updated successfully');
        } catch (Exception $e) {
            Capsule::rollBack();
            return ResponseHandle::error($response, $e->getMessage(), 500);
        }
    }

    /**
     * DELETE /v1/tag/{id}
     */
    public function deleteTag(Request $request, Response $response, array $args): Response
    {
        try {
            $tagId = $args['id'] ?? null;

            if (!$tagId) {
                return ResponseHandle::error($response, 'Tag ID is required.', 400);
            }

            $tag = BlogTagModel::where('id', $tagId)->first();

            if (!$tag) {
                return ResponseHandle::error($response, 'Tag not found.', 404);
            }

            Capsule::beginTransaction();

            $tag->delete();

            Capsule::commit();

            return ResponseHandle::success($response, null, 'Tag deleted successfully');
        } catch (Exception $e) {
            Capsule::rollBack();
            return ResponseHandle::error($response, $e->getMessage(), 500);
        }
    }
}
