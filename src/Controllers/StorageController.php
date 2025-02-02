<?php

namespace App\Controllers;

use Exception;
use Slim\Psr7\Stream;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Capsule\Manager as Capsule;
use App\Helpers\ResponseHandle;
use App\Helpers\StorageAPIHelper;
use App\Helpers\AuthAPIHelper;
use App\Models\StorageModel;

class StorageController
{
    /**
     * GET /v1/storage
     */
    public function getStorageUsed(Request $request, Response $response): Response
    {
        try {
            $res = StorageAPIHelper::get('/v1/storage');
            $statusCode = $res->getStatusCode();
            $body = json_decode($res->getBody()->getContents(), true);
            if ($statusCode >= 400) {
                return ResponseHandle::apiError($response, $body, $statusCode);
            }

            return $res;
        } catch (Exception $e) {
            return ResponseHandle::error($response, $e->getMessage(), 500);
        }
    }

    /**
     * GET /v1/storage/image
     */
    public function getImageList(Request $request, Response $response): Response
    {
        try {
            $queryParams = $request->getQueryParams();
            $params = [
                'page' => $queryParams['page'] ?? 1,
                'per_page' => $queryParams['per_page'] ?? 10,
                'image_id' => $queryParams['image_id'] ?? null,
                'group' => $queryParams['group'] ?? null,
                'name' => $queryParams['name'] ?? null,
                'start_date' => $queryParams['start_date'] ?? null,
                'end_date' => $queryParams['end_date'] ?? null,
            ];

            $imageResponse = StorageAPIHelper::get('/v1/image', $params);
            $imageResponseStatus = $imageResponse->getStatusCode();
            $imageResponseBody = json_decode($imageResponse->getBody()->getContents(), true);

            if ($imageResponseStatus >= 400) {
                return ResponseHandle::apiError($response, $imageResponseBody, $imageResponseStatus);
            }

            $imageData = $imageResponseBody['data']['data'] ?? [];
            if (empty($imageData)) {
                return ResponseHandle::success($response, [
                    'pagination' => $imageResponseBody['data']['pagination'] ?? null,
                    'data' => [],
                ], 'No images found');
            }

            $uploadedByUserIds = collect($imageData)->pluck('uploaded_by')->unique()->toArray();
            if (empty($uploadedByUserIds)) {
                return ResponseHandle::success($response, [
                    'pagination' => $imageResponseBody['data']['pagination'],
                    'data' => $imageData,
                ], 'Image list retrieved successfully');
            }

            $memberResponse = AuthAPIHelper::get('/v1/member/batch', ['ids' => implode(',', $uploadedByUserIds)]);
            $memberResponseStatus = $memberResponse->getStatusCode();
            $memberResponseBody = json_decode($memberResponse->getBody()->getContents(), true);

            if ($memberResponseStatus >= 400) {
                return ResponseHandle::apiError($response, $memberResponseBody, $memberResponseStatus);
            }

            $members = collect($memberResponseBody['data'])->keyBy('user_id');

            $updatedImages = collect($imageData)->map(function ($image) use ($members) {
                $uploadedBy = $members->get($image['uploaded_by'], null);
                $image['uploaded_by'] = $uploadedBy;
                return $image;
            });

            return ResponseHandle::success($response, [
                'pagination' => $imageResponseBody['data']['pagination'],
                'data' => $updatedImages,
            ], 'Image list retrieved successfully');
        } catch (Exception $e) {
            return ResponseHandle::error($response, $e->getMessage(), 500);
        }
    }

    /**
     * POST /v1/storage/image
     */
    public function uploadImage(Request $request, Response $response): Response
    {
        try {
            $currentUser = $request->getAttribute('user');
            $uploadedFiles = $request->getUploadedFiles();
            $parsedBody = $request->getParsedBody();
            $group = $parsedBody['group'] ?? 'default';

            if (empty($uploadedFiles['file'])) {
                return ResponseHandle::error($response, 'No file uploaded', 400);
            }

            $uploadedFile = $uploadedFiles['file'];

            if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
                return ResponseHandle::error($response, 'Upload failed', 400);
            }

            $uploadResponse = StorageAPIHelper::post('/v1/image', [
                [
                    'name' => 'file',
                    'contents' => $uploadedFile->getStream(),
                    'filename' => $uploadedFile->getClientFilename()
                ],
                [
                    'name' => 'group',
                    'contents' => $group
                ],
                [
                    'name' => 'uploaded_by',
                    'contents' => $currentUser['user_id']
                ]
            ], [], 'multipart');

            $uploadResponseStatus = $uploadResponse->getStatusCode();
            $uploadResponseBody = json_decode($uploadResponse->getBody()->getContents(), true);

            if ($uploadResponseStatus >= 400) {
                return ResponseHandle::apiError($response, $uploadResponseBody, $uploadResponseStatus);
            }

            return $uploadResponse;
        } catch (Exception $e) {
            return ResponseHandle::error($response, $e->getMessage(), 500);
        }
    }

    /**
     * PUT /v1/storage/image/{id}
     */
    public function updateImageName(Request $request, Response $response, $args): Response
    {
        try {
            $imageId = $args['id'] ?? null;
            $body = json_decode((string)$request->getBody(), true);
            $newName = $body['new_name'] ?? null;

            if (empty($imageId)) {
                return ResponseHandle::error($response, "Image ID is required", 400);
            }

            if (empty($newName)) {
                return ResponseHandle::error($response, "New name is required", 400);
            }

            $res = StorageAPIHelper::put('/v1/image/' . $imageId, ['new_name' => $newName]);
            $statusCode = $res->getStatusCode();
            $responseBody = json_decode($res->getBody()->getContents(), true);

            if ($statusCode >= 400) {
                return ResponseHandle::apiError($response, $responseBody, $statusCode);
            }

            return $res;
        } catch (Exception $e) {
            return ResponseHandle::error($response, $e->getMessage(), 500);
        }
    }

    /**
     * DELETE /v1/storage/image
     */
    public function deleteImages(Request $request, Response $response): Response
    {
        try {
            $queryParams = $request->getQueryParams();
            $ids = $queryParams['ids'] ?? null;

            if (empty($ids)) {
                return ResponseHandle::error($response, "Image IDs are required", 400);
            }

            $res = StorageAPIHelper::delete('/v1/image', [], ['ids' => $ids]);
            $statusCode = $res->getStatusCode();
            $responseBody = json_decode($res->getBody()->getContents(), true);

            if ($statusCode >= 400) {
                return ResponseHandle::apiError($response, $responseBody, $statusCode);
            }

            return $res;
        } catch (Exception $e) {
            return ResponseHandle::error($response, $e->getMessage(), 500);
        }
    }

    // GET /v1/storage/blog/image
    public function getBlogImage(Request $request, Response $response): Response
    {
        try {
            $page = (int)($request->getQueryParams()['page'] ?? 1);
            $limit = (int)($request->getQueryParams()['per_page'] ?? 10);
            $imageName = $request->getQueryParams()['image_name'] ?? null;
            $startDate = $request->getQueryParams()['start_date'] ?? null;
            $endDate = $request->getQueryParams()['end_date'] ?? null;

            $query = StorageModel::orderBy('storage_id', 'desc');

            if ($imageName) {
                $query->where('image_name', 'like', '%' . $imageName . '%');
            }

            if ($startDate) {
                $query->whereDate('created_at', '>=', $startDate);
            }
            if ($endDate) {
                $query->whereDate('created_at', '<=', $endDate);
            }

            $images = $query->paginate($limit, ['*'], 'page', $page);

            $transformedData = $images->map(function ($image) {
                return [
                    'storage_id' => $image->storage_id,
                    'image_id' => $image->image_id,
                    'image_name' => $image->image_name,
                    'base_url' => $image->base_url,
                    'lazy_url' => $image->lazy_url,
                    'base_size' => $image->base_size,
                    'lazy_size' => $image->lazy_size,
                    'created_by' => $image->created_by,
                    'created_at' => $image->created_at->toDateTimeString(),
                    'updated_at' => $image->updated_at->toDateTimeString()
                ];
            });

            $data = [
                'pagination' => [
                    'current_page' => $images->currentPage(),
                    'per_page' => $images->perPage(),
                    'total' => $images->total(),
                    'last_page' => $images->lastPage(),
                ],
                'data' => $transformedData
            ];

            return ResponseHandle::success($response, $data, 'Image list retrieved successfully');
        } catch (Exception $e) {
            return ResponseHandle::error($response, $e->getMessage(), 500);
        }
    }

    /**
     * POST /v1/storage/blog/image
     */
    public function uploadBlogImage(Request $request, Response $response): Response
    {
        try {
            $currentUser = $request->getAttribute('user');
            $uploadedFiles = $request->getUploadedFiles();

            if (empty($uploadedFiles['file'])) {
                return ResponseHandle::error($response, 'No file uploaded', 400);
            }

            $uploadedFile = $uploadedFiles['file'];

            if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
                return ResponseHandle::error($response, 'Upload failed', 400);
            }

            $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
            $maxFileSize = 10 * 1024 * 1024; // 10MB

            if (!in_array($uploadedFile->getClientMediaType(), $allowedMimeTypes)) {
                return ResponseHandle::error($response, 'Invalid file type. Only JPG, PNG, and WEBP are allowed.', 400);
            }

            if ($uploadedFile->getSize() > $maxFileSize) {
                return ResponseHandle::error($response, 'File size exceeds 5MB.', 400);
            }

            $uploadResponse = StorageAPIHelper::post('/v1/image', [
                [
                    'name' => 'file',
                    'contents' => $uploadedFile->getStream(),
                    'filename' => $uploadedFile->getClientFilename()
                ],
                [
                    'name' => 'group',
                    'contents' => 'blog-storage'
                ],
                [
                    'name' => 'uploaded_by',
                    'contents' => $currentUser['user_id']
                ]
            ], [], 'multipart');

            $uploadResponseStatus = $uploadResponse->getStatusCode();
            $uploadResponseBody = json_decode($uploadResponse->getBody()->getContents(), true);
            if ($uploadResponseStatus >= 400) {
                return ResponseHandle::apiError($response, $uploadResponseBody, $uploadResponseStatus);
            }

            $imageModel = StorageModel::create([
                'image_id' => $uploadResponseBody['data']['image_id'],
                'group' => 'blog-storage',
                'image_name' => $uploadResponseBody['data']['name'],
                'base_url' => $uploadResponseBody['data']['base_url'],
                'lazy_url' => $uploadResponseBody['data']['lazy_url'],
                'base_size' => $uploadResponseBody['data']['base_size'],
                'lazy_size' => $uploadResponseBody['data']['lazy_size'],
                'created_by' => $currentUser['user_id']
            ]);

            return ResponseHandle::success($response, $imageModel, 'Upload successful');
        } catch (Exception $e) {
            return ResponseHandle::error($response, $e->getMessage(), 500);
        }
    }

    /**
     * DELETE /v1/storage/blog/image
     */
    public function deleteBlogImage(Request $request, Response $response): Response
    {
        try {
            $queryParams = $request->getQueryParams();
            $storageId = $queryParams['storage_id'] ?? null;
            if (empty($storageId)) {
                return ResponseHandle::error($response, "Storage IDs are required", 400);
            }

            $storage = StorageModel::where('storage_id', $storageId)->first();
            if (!$storage) {
                return ResponseHandle::error($response, "Storage ID not found", 404);
            }

            $imageId = $storage->image_id;
            if (empty($imageId)) {
                return ResponseHandle::error($response, "Image ID is missing, unable to delete", 400);
            }

            Capsule::beginTransaction();

            $storage->delete();

            $res = StorageAPIHelper::delete('/v1/image', [], ['ids' => $imageId]);
            $statusCode = $res->getStatusCode();
            $responseBody = json_decode($res->getBody()->getContents(), true);

            if ($statusCode >= 400) {
                Capsule::rollBack();
                return ResponseHandle::apiError($response, $responseBody, $statusCode);
            }

            Capsule::commit();

            return ResponseHandle::success($response, null, 'Images deleted successfully');
        } catch (Exception $e) {
            return ResponseHandle::error($response, $e->getMessage(), 500);
        }
    }

    /**
     * GET /v1/storage/blog/image/download
     */
    public function downloadBlogImage(Request $request, Response $response): Response
    {
        try {
            $queryParams = $request->getQueryParams();
            $storageId = $queryParams['storage_id'] ?? null;
            if (empty($storageId)) {
                return ResponseHandle::error($response, "Storage IDs are required", 400);
            }

            $storage = StorageModel::where('storage_id', $storageId)->first();
            if (!$storage) {
                return ResponseHandle::error($response, "Storage ID not found", 404);
            }

            $imageBaseUrl = $storage->base_url;
            if (empty($imageBaseUrl)) {
                return ResponseHandle::error($response, "Image ID is missing, unable to delete", 400);
            }

            $imageStream = fopen($imageBaseUrl, 'rb');
            if (!$imageStream) {
                return ResponseHandle::error($response, "Failed to fetch image", 500);
            }

            $fileName = basename($storage->image_name);

            $stream = new Stream($imageStream);

            return $response
                ->withHeader('Content-Type', 'image/jpeg')
                ->withHeader('Content-Disposition', 'attachment; filename="' . $fileName . '"')
                ->withBody($stream);

            return $response;
        } catch (Exception $e) {
            return ResponseHandle::error($response, $e->getMessage(), 500);
        }
    }

    /**
     * POST /v1/storage/blog/image/name
     */
    public function editBlogImageName(Request $request, Response $response): Response
    {
        try {
            $body = json_decode($request->getBody(), true);
            if (!$body || !isset($body['storage_id']) || !isset($body['new_image_name'])) {
                return ResponseHandle::error($response, "Storage ID and new image name are required", 400);
            }

            $storageId = $body['storage_id'];
            $newImageName = trim($body['new_image_name']);

            $newImageName = preg_replace('/[^\p{Thai}\p{Latin}\p{N}_ .-]/u', '', $newImageName);

            if (empty($newImageName)) {
                return ResponseHandle::error($response, "Invalid image name", 400);
            }

            $storage = StorageModel::where('storage_id', $storageId)->first();
            if (!$storage) {
                return ResponseHandle::error($response, "Storage ID not found", 404);
            }

            $updated = $storage->update(['image_name' => $newImageName]);
            if (!$updated) {
                return ResponseHandle::error($response, "Failed to update image name", 500);
            }

            return ResponseHandle::success($response, $storage, 'Update image name successfully');
        } catch (Exception $e) {
            return ResponseHandle::error($response, $e->getMessage(), 500);
        }
    }

    /**
     * DELETE /v1/storage/blog/images
     */
    public function multipleDeleteImages(Request $request, Response $response): Response
    {
        try {
            $queryParams = $request->getQueryParams();
            $storageIds = $queryParams['storage_ids'] ?? null;

            if (empty($storageIds)) {
                return ResponseHandle::error($response, "Storage IDs are required", 400);
            }

            $storageIdArray = array_map('trim', explode(',', $storageIds));

            $storages = StorageModel::whereIn('storage_id', $storageIdArray)->get();

            if ($storages->isEmpty()) {
                return ResponseHandle::error($response, "Storage IDs not found", 404);
            }

            $imageIds = $storages->pluck('image_id')->filter()->toArray();

            if (empty($imageIds)) {
                return ResponseHandle::error($response, "No valid Image IDs found, unable to delete", 400);
            }

            Capsule::beginTransaction();

            StorageModel::whereIn('storage_id', $storageIdArray)->delete();

            $imageIdsString = implode(',', $imageIds);
            $res = StorageAPIHelper::delete('/v1/image', [], ['ids' => $imageIdsString]);

            $statusCode = $res->getStatusCode();
            $responseBody = json_decode($res->getBody()->getContents(), true);

            if ($statusCode >= 400) {
                Capsule::rollBack();
                return ResponseHandle::apiError($response, $responseBody, $statusCode);
            }

            Capsule::commit();

            return ResponseHandle::success($response, null, 'Images deleted successfully');
        } catch (Exception $e) {
            Capsule::rollBack();
            return ResponseHandle::error($response, $e->getMessage(), 500);
        }
    }
}
