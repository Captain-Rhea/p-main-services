<?php

namespace App\Controllers;

use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Helpers\ResponseHandle;
use App\Helpers\StorageAPIHelper;
use App\Helpers\AuthAPIHelper;

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
}
