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
     * POST /v1/my-member/avatar
     */
    public function uploadAvatar(Request $request, Response $response): Response
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

            $profileResponse = AuthAPIHelper::get('/v1/my-member/profile', ['user_id' => $currentUser['user_id']]);
            $profileResponseStatus = $profileResponse->getStatusCode();
            $profileResponseBody = json_decode($profileResponse->getBody()->getContents(), true);

            if ($profileResponseStatus >= 400) {
                return ResponseHandle::apiError($response, $profileResponseBody, $profileResponseStatus);
            }

            $currentAvatarId = $profileResponseBody['data']['user_info']['avatar_id'] ?? null;

            if ($currentAvatarId) {
                $deleteAvatarResponse = StorageAPIHelper::delete('/v1/image/' . $currentAvatarId);
                $deleteAvatarResponseStatus = $deleteAvatarResponse->getStatusCode();
                $deleteAvatarResponseBody = json_decode($deleteAvatarResponse->getBody()->getContents(), true);

                if ($deleteAvatarResponseStatus >= 400) {
                    return ResponseHandle::apiError($response, $deleteAvatarResponseBody, $deleteAvatarResponseStatus);
                }
            }

            $uploadAvatarResponse = StorageAPIHelper::post('/v1/image', [
                [
                    'name' => 'file',
                    'contents' => $uploadedFile->getStream(),
                    'filename' => $uploadedFile->getClientFilename()
                ],
                [
                    'name' => 'group',
                    'contents' => 'avatar'
                ],
                [
                    'name' => 'uploaded_by',
                    'contents' => $currentUser['user_id']
                ]
            ], [], 'multipart');

            $uploadAvatarResponseStatus = $uploadAvatarResponse->getStatusCode();
            $uploadAvatarResponseBody = json_decode($uploadAvatarResponse->getBody()->getContents(), true);

            if ($uploadAvatarResponseStatus >= 400) {
                return ResponseHandle::apiError($response, $uploadAvatarResponseBody, $uploadAvatarResponseStatus);
            }

            $updateAvatarRequestBody = [
                'avatar_id' => $uploadAvatarResponseBody['data']['image_id'],
                'avatar_base_url' => $uploadAvatarResponseBody['data']['base_url'],
                'avatar_lazy_url' => $uploadAvatarResponseBody['data']['lazy_url']
            ];

            $updateAvatarResponse = AuthAPIHelper::put('/v1/my-member/avatar', $updateAvatarRequestBody, ['user_id' => $currentUser['user_id']]);
            $updateAvatarResponseStatus = $updateAvatarResponse->getStatusCode();
            $updateAvatarResponseBody = json_decode($updateAvatarResponse->getBody()->getContents(), true);

            if ($updateAvatarResponseStatus >= 400) {
                return ResponseHandle::apiError($response, $updateAvatarResponseBody, $updateAvatarResponseStatus);
            }

            return $updateAvatarResponse;
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
