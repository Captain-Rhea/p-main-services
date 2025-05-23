<?php

namespace App\Controllers;

use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Helpers\ResponseHandle;
use App\Helpers\AuthAPIHelper;
use App\Helpers\CookieHelper;
use Illuminate\Support\Carbon;

class AuthController
{
    /**
     * POST /v1/auth/login
     */
    public function login(Request $request, Response $response): Response
    {
        try {
            $body = json_decode((string)$request->getBody(), true);
            $email = $body['email'] ?? null;
            $password = $body['password'] ?? null;

            if (!$email || !$password) {
                return ResponseHandle::error($response, 'Email and password are required', 400);
            }

            $res = AuthAPIHelper::post('/v1/auth/login', [
                'email' => $email,
                'password' => $password
            ]);
            $statusCode = $res->getStatusCode();
            $body = json_decode($res->getBody()->getContents(), true);
            if ($statusCode >= 400) {
                return ResponseHandle::apiError($response, $body, $statusCode);
            }

            $accessToken = $body['data']['token'];

            $response = CookieHelper::setCookie($response, '__secure_app_scope', $accessToken);

            return ResponseHandle::success($response, null, 'Login successful');
        } catch (Exception $e) {
            return ResponseHandle::error($response, $e->getMessage(), 500);
        }
    }

    /**
     * POST /v1/auth/is-login
     */
    public function isLogin(Request $request, Response $response): Response
    {
        try {
            $oldToken = $request->getAttribute('token');
            $res = AuthAPIHelper::get('/v1/auth/is-login', ['token' => $oldToken]);
            $statusCode = $res->getStatusCode();
            $body = json_decode($res->getBody()->getContents(), true);
            if ($statusCode >= 400) {
                return ResponseHandle::apiError($response, $body, $statusCode);
            }

            $newToken = $body['data']['token'];

            if ($oldToken !== $newToken) {
                $response = CookieHelper::setCookie($response, '__secure_app_scope', $newToken);
            }

            return ResponseHandle::success($response, null, 'Login successful');
        } catch (Exception $e) {
            return ResponseHandle::error($response, $e->getMessage(), 500);
        }
    }

    /**
     * POST /v1/auth/logout
     */
    public function logout(Request $request, Response $response): Response
    {
        try {
            $response = CookieHelper::clearCookie($response, '__secure_app_scope');
            return ResponseHandle::success($response, null, 'Logout successful');
        } catch (Exception $e) {
            return ResponseHandle::error($response, $e->getMessage(), 500);
        }
    }


    /**
     * POST /v1/auth/force-logout
     */
    public function forceLogout(Request $request, Response $response): Response
    {
        try {
            $response = CookieHelper::clearCookie($response, '__secure_app_scope');
            return ResponseHandle::success($response, null, 'Force logout successful');
        } catch (Exception $e) {
            return ResponseHandle::error($response, $e->getMessage(), 500);
        }
    }

    /**
     * POST /v1/auth/reset-password
     */
    public function resetPassword(Request $request, Response $response): Response
    {
        try {
            $body = json_decode((string)$request->getBody(), true);
            $userId = $body['user_id'] ?? null;
            $newPassword = $body['new_password'] ?? null;

            if (!$userId || !$newPassword) {
                return ResponseHandle::error($response, 'User ID and new password are required', 400);
            }

            $res = AuthAPIHelper::post('/v1/auth/reset-password', [
                'user_id' => $userId,
                'new_password' => $newPassword,
            ]);
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
     * GET /v1/auth/forgot-password
     */
    public function getForgotPasswords(Request $request, Response $response): Response
    {
        try {
            $queryParams = $request->getQueryParams();
            $res = AuthAPIHelper::get('/v1/auth/forgot-password', [
                'page' => $queryParams['page'] ?? 1,
                'per_page' => $queryParams['per_page'] ?? 10,
                'recipient_email' => $queryParams['recipient_email'] ?? null,
                'is_used' => $queryParams['is_used'] ?? null,
                'start_date' => $queryParams['start_date'] ?? null,
                'end_date' => $queryParams['end_date'] ?? null
            ]);
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
     * POST /v1/auth/send/forgot-mail
     */
    public function sendForgotMail(Request $request, Response $response): Response
    {
        try {
            $body = json_decode((string)$request->getBody(), true);
            $recipientEmail = $body['recipient_email'] ?? null;

            if (!$recipientEmail) {
                return ResponseHandle::error($response, 'Recipient email is required', 400);
            }

            $res = AuthAPIHelper::post('/v1/auth/send/forgot-mail', [
                'recipient_email' => $recipientEmail,
            ]);
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
     * POST /v1/auth/send/forgot-mail/verify
     */
    public function forgotMailVerify(Request $request, Response $response): Response
    {
        try {
            $body = json_decode((string)$request->getBody(), true);
            $resetKey = $body['reset_key'] ?? null;

            if (!$resetKey) {
                return ResponseHandle::error($response, 'Reset key is required', 400);
            }


            $res = AuthAPIHelper::post('/v1/auth/send/forgot-mail/verify', [
                'reset_key' => $resetKey,
            ]);
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
     * POST /v1/auth/send/forgot-mail/reset-password
     */
    public function forgotMailResetNewPassword(Request $request, Response $response): Response
    {
        try {
            $body = json_decode((string)$request->getBody(), true);
            $recipientEmail = $body['recipient_email'] ?? null;
            $resetKey = $body['reset_key'] ?? null;
            $newPassword = $body['new_password'] ?? null;

            if (!$recipientEmail || !$resetKey || !$newPassword) {
                return ResponseHandle::error($response, 'Email, reset key, and new password are required', 400);
            }

            $res = AuthAPIHelper::post('/v1/auth/send/forgot-mail/reset-password', [
                'recipient_email' => $recipientEmail,
                'reset_key' => $resetKey,
                'new_password' => $newPassword,
            ]);
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
     * GET /v1/auth/transaction/login
     */
    public function getLoginTransaction(Request $request, Response $response): Response
    {
        try {
            $queryParams = $request->getQueryParams();
            $res = AuthAPIHelper::get('/v1/auth/transaction/login', [
                'page' => $queryParams['page'] ?? 1,
                'per_page' => $queryParams['per_page'] ?? 10,
                'user_id' => $queryParams['user_id'] ?? null,
                'start_date' => $queryParams['start_date'] ?? null,
                'end_date' => $queryParams['end_date'] ?? null
            ]);
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
     * POST /v1/auth/send/forgot-otp/reset-password
     */
    public function forgotMailResetNewPasswordByOTP(Request $request, Response $response): Response
    {
        try {
            $body = json_decode((string)$request->getBody(), true);
            $recipientEmail = $body['recipient_email'] ?? null;
            $newPassword = $body['new_password'] ?? null;
            $otpRef = $body['otp_ref'] ?? null;
            $otpCode = $body['otp_code'] ?? null;

            if (!$recipientEmail || !$newPassword || !$otpRef || !$otpCode) {
                return ResponseHandle::error($response, 'Request body are required', 400);
            }

            $res = AuthAPIHelper::post('/v1/auth/send/forgot-otp/reset-password', [
                'recipient_email' => $recipientEmail,
                'new_password' => $newPassword,
                'otp_ref' => $otpRef,
                'otp_code' => $otpCode,
            ]);
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
