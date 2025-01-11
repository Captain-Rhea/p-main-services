<?php

namespace App\Controllers;

use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Helpers\ResponseHandle;
use App\Helpers\StorageAPIHelper;

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

            $res = StorageAPIHelper::get('/v1/image', $params);
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
     * POST /v1/storage/image
     */
    public function uploadImage(Request $request, Response $response): Response
    {
        try {
            $user = $request->getAttribute('user');
            $uploadedFiles = $request->getUploadedFiles();

            if (empty($uploadedFiles['file'])) {
                return ResponseHandle::error($response, 'No file uploaded', 400);
            }

            $file = $uploadedFiles['file'];

            if ($file->getError() !== UPLOAD_ERR_OK) {
                return ResponseHandle::error($response, 'Upload failed', 400);
            }

            $storageResponse = StorageAPIHelper::post('/v1/image', [
                [
                    'name' => 'file',
                    'contents' => $file->getStream(),
                    'filename' => $file->getClientFilename()
                ],
                [
                    'name' => 'group',
                    'contents' => 'avatar'
                ],
                [
                    'name' => 'uploaded_by',
                    'contents' => $user['user_id']
                ]
            ], [], 'multipart');

            $statusCode = $storageResponse->getStatusCode();
            $storageResponseBody = json_decode($storageResponse->getBody()->getContents(), true);

            if ($statusCode >= 400) {
                return ResponseHandle::apiError($response, $storageResponseBody, $statusCode);
            }

            $apiBody = [
                'avatar_id' => $storageResponseBody['data']['image_id'],
                'avatar_base_url' => $storageResponseBody['data']['base_url'],
                'avatar_lazy_url' => $storageResponseBody['data']['lazy_url']
            ];

            $res = AuthAPIHelper::put('/v1/my-member/avatar', $apiBody, ['user_id' => $user['user_id']]);
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
}
