<?php

namespace App\Controllers;

use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Helpers\ResponseHandle;
use Illuminate\Database\Capsule\Manager as Capsule;
use Ramsey\Uuid\Uuid;
use App\Models\BlogAuthorModel;

class BlogAuthorController
{
    /**
     * GET /v1/blog-authors
     */
    public function getAuthors(Request $request, Response $response): Response
    {
        try {
            $page = (int)($request->getQueryParams()['page'] ?? 1);
            $limit = (int)($request->getQueryParams()['per_page'] ?? 10);
            $search = $request->getQueryParams()['search'] ?? null;
            $startDate = $request->getQueryParams()['start_date'] ?? null;
            $endDate = $request->getQueryParams()['end_date'] ?? null;

            $query = BlogAuthorModel::orderBy('created_at', 'desc');

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name_th', 'like', '%' . $search . '%')
                        ->orWhere('name_en', 'like', '%' . $search . '%');
                });
            }

            if ($startDate) {
                $query->whereDate('created_at', '>=', $startDate);
            }

            if ($endDate) {
                $query->whereDate('created_at', '<=', $endDate);
            }

            $authors = $query->paginate($limit, ['*'], 'page', $page);

            $transformedData = array_map(function ($author) {
                return [
                    'id' => $author->id,
                    'name_th' => $author->name_th,
                    'name_en' => $author->name_en,
                    'profile_image' => $author->profile_image,
                    'created_at' => $author->created_at->toDateTimeString(),
                    'updated_at' => $author->updated_at->toDateTimeString(),
                ];
            }, $authors->items());

            $data = [
                'pagination' => [
                    'current_page' => $authors->currentPage(),
                    'per_page' => $authors->perPage(),
                    'total' => $authors->total(),
                    'last_page' => $authors->lastPage(),
                ],
                'data' => $transformedData
            ];

            return ResponseHandle::success($response, $data, 'Authors retrieved successfully');
        } catch (Exception $e) {
            return ResponseHandle::error($response, $e->getMessage(), 500);
        }
    }

    /**
     * POST /v1/blog-authors
     */
    public function createAuthor(Request $request, Response $response): Response
    {
        try {
            $body = json_decode($request->getBody()->getContents(), true);
            $uploadedFiles = $request->getUploadedFiles();

            if (!$body || !isset($body['name_th'], $body['name_en'])) {
                return ResponseHandle::error($response, 'Invalid request body.', 400);
            }

            $uploadedFile = $uploadedFiles['file'] ?? null;

            Capsule::beginTransaction();

            $author = BlogAuthorModel::create([
                'name_th' => trim($body['name_th']),
                'name_en' => trim($body['name_en']),
                'profile_image' => $body['profile_image'] ?? null,
            ]);

            Capsule::commit();

            return ResponseHandle::success($response, $author, 'Author created successfully');
        } catch (Exception $e) {
            Capsule::rollBack();
            return ResponseHandle::error($response, $e->getMessage(), 500);
        }
    }

    /**
     * PUT /v1/blog-authors/{id}
     */
    public function updateAuthor(Request $request, Response $response, array $args): Response
    {
        try {
            $authorId = $args['id'] ?? null;

            if (!$authorId) {
                return ResponseHandle::error($response, 'Author ID is required.', 400);
            }

            $author = BlogAuthorModel::where('id', $authorId)->first();

            if (!$author) {
                return ResponseHandle::error($response, 'Author not found.', 404);
            }

            $body = json_decode($request->getBody()->getContents(), true);

            if (!$body || !isset($body['name_th'], $body['name_en'])) {
                return ResponseHandle::error($response, 'Invalid request body.', 400);
            }

            Capsule::beginTransaction();

            $author->update([
                'name_th' => trim($body['name_th']),
                'name_en' => trim($body['name_en']),
                'profile_image' => $body['profile_image'] ?? $author->profile_image,
            ]);

            Capsule::commit();

            return ResponseHandle::success($response, $author, 'Author updated successfully');
        } catch (Exception $e) {
            Capsule::rollBack();
            return ResponseHandle::error($response, $e->getMessage(), 500);
        }
    }

    /**
     * DELETE /v1/blog-authors/{id}
     */
    public function deleteAuthor(Request $request, Response $response, array $args): Response
    {
        try {
            $authorId = $args['id'] ?? null;

            if (!$authorId) {
                return ResponseHandle::error($response, 'Author ID is required.', 400);
            }

            $author = BlogAuthorModel::where('id', $authorId)->first();

            if (!$author) {
                return ResponseHandle::error($response, 'Author not found.', 404);
            }

            Capsule::beginTransaction();

            $author->delete();

            Capsule::commit();

            return ResponseHandle::success($response, null, 'Author deleted successfully');
        } catch (Exception $e) {
            Capsule::rollBack();
            return ResponseHandle::error($response, $e->getMessage(), 500);
        }
    }
}
