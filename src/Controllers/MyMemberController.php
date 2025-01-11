<?php

namespace App\Controllers;

use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Helpers\ResponseHandle;
use App\Helpers\AuthAPIHelper;
use App\Helpers\StorageAPIHelper;

class MyMemberController
{
    /**
     * GET /v1/my-member/profile
     */
    public function myProfile(Request $request, Response $response): Response
    {
        try {
            $user = $request->getAttribute('user');

            $res = AuthAPIHelper::get('/v1/my-member/profile', ['user_id' => $user['user_id']]);
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
     * PUT /v1/my-member/avatar
     */
    public function updateAvatar(Request $request, Response $response): Response
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

    /**
     * PUT /v1/my-member/detail
     */
    public function updateUserDetail(Request $request, Response $response): Response
    {
        try {
            $user = $request->getAttribute('user');

            $body = json_decode((string)$request->getBody(), true);
            if (!is_array($body)) {
                return ResponseHandle::error($response, 'Invalid request body', 400);
            }

            $res = AuthAPIHelper::put('/v1/my-member/detail', $body, ['user_id' => $user['user_id']]);
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
     * POST /v1/my-member/reset-password
     */
    public function resetPassword(Request $request, Response $response): Response
    {
        try {
            $user = $request->getAttribute('user');

            $body = json_decode((string)$request->getBody(), true);
            $newPassword = $body['new_password'] ?? null;

            if (!$newPassword) {
                return ResponseHandle::error($response, 'User and New password are required', 400);
            }

            $res = AuthAPIHelper::post('/v1/my-member/reset-password', $body, ['user_id' => $user['user_id']]);
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
