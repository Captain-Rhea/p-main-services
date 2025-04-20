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
     * GET /v1/storage/image
     */
    public function getImageList(Request $request, Response $response): Response
    {
        try {
            $queryParams = $request->getQueryParams();
            $params = [
                'page' => $queryParams['page'] ?? 1,
                'per_page' => $queryParams['per_page'] ?? 10,
                'group' => $queryParams['group'] ?? null,
                'file_type' => $queryParams['file_type'] ?? null,
                'created_by' => $queryParams['created_by'] ?? null,
                'start_date' => $queryParams['start_date'] ?? null,
                'end_date' => $queryParams['end_date'] ?? null,
                'search' => $queryParams['search'] ?? null,
            ];

            $imageResponse = StorageAPIHelper::get('/api/v1/files', $params);
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

            $uploadedByUserIds = collect($imageData)->pluck('created_by')->unique()->toArray();
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
                $uploadedBy = $members->get($image['created_by'], null);
                $image['created_by'] = $uploadedBy;
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

            $multipartData = [
                [
                    'name'     => 'file',
                    'contents' => $uploadedFile->getStream(),
                    'filename' => $uploadedFile->getClientFilename(),
                    'headers'  => [
                        'Content-Type' => $uploadedFile->getClientMediaType(),
                    ],
                ],
                [
                    'name' => 'group',
                    'contents' => $group
                ],
                [
                    'name' => 'created_by',
                    'contents' => $currentUser['user_id']
                ],
            ];

            $uploadResponse = StorageAPIHelper::post('/api/v1/files', $multipartData, [], true);
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
    public function updateImage(Request $request, Response $response, $args): Response
    {
        try {
            $currentUser = $request->getAttribute('user');
            $imageId = $args['id'] ?? null;
            $body = json_decode((string)$request->getBody(), true);
            $newName = $body['new_name'] ?? null;
            $newDescription = $body['new_description'] ?? null;

            if (empty($imageId)) {
                return ResponseHandle::error($response, "Image ID is required", 400);
            }

            if (empty($newName)) {
                return ResponseHandle::error($response, "New name is required", 400);
            }

            $res = StorageAPIHelper::put('/api/v1/files/' . $imageId, [
                'file_name' => $newName,
                'file_description' => $newDescription,
                'updated_by' => $currentUser['user_id']
            ]);
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
            $idsString = $queryParams['ids'] ?? null;

            if (empty($idsString)) {
                return ResponseHandle::error($response, "Image IDs are required", 400);
            }

            $imageIds = array_filter(explode(',', $idsString));

            $errors = [];
            foreach ($imageIds as $imageId) {
                $imageId = trim($imageId);

                if (empty($imageId)) continue;

                usleep(150000);

                try {
                    $res = StorageAPIHelper::delete('/api/v1/files/' . $imageId);
                    $statusCode = $res->getStatusCode();

                    if ($statusCode >= 400) {
                        $responseBody = json_decode($res->getBody()->getContents(), true);
                        $errors[] = [
                            'id' => $imageId,
                            'error' => $responseBody['message'] ?? 'Unknown error'
                        ];
                    }
                } catch (Exception $ex) {
                    $errors[] = [
                        'id' => $imageId,
                        'error' => $ex->getMessage()
                    ];
                }
            }

            if (!empty($errors)) {
                return ResponseHandle::error($response, [
                    'message' => 'Some images failed to delete',
                    'errors' => $errors
                ], 207);
            }

            return ResponseHandle::success($response, 'All images deleted successfully.');
        } catch (Exception $e) {
            return ResponseHandle::error($response, $e->getMessage(), 500);
        }
    }

    /**
     * GET /v1/storage/image/download
     */
    public function downloadImage(Request $request, Response $response): Response
    {
        try {
            $queryParams = $request->getQueryParams();
            $imageId = $queryParams['image_id'] ?? null;

            if (empty($imageId)) {
                return ResponseHandle::error($response, "Storage IDs are required", 400);
            }

            $res = StorageAPIHelper::get('/api/v1/files/' . $imageId);
            $statusCode = $res->getStatusCode();
            $responseBody = json_decode($res->getBody()->getContents(), true);

            if ($statusCode >= 400) {
                return ResponseHandle::apiError($response, $responseBody, $statusCode);
            }

            $imageBaseUrl = $responseBody['data']['file_url'];
            if (empty($imageBaseUrl)) {
                return ResponseHandle::error($response, "Image ID is missing, unable to delete", 400);
            }

            $imageStream = fopen($imageBaseUrl, 'rb');
            if (!$imageStream) {
                return ResponseHandle::error($response, "Failed to fetch image", 500);
            }

            $fileName = basename($responseBody['data']['file_name']);

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
}
