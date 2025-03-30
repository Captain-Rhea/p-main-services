<?php

namespace App\Controllers;

use Exception;
use App\Helpers\AuthAPIHelper;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Helpers\ResponseHandle;

class OtpController
{
    // GET /v1/otp
    public function getAll(Request $request, Response $response): Response
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

    // POST /v1/otp
    public function sendOTP(Request $request, Response $response): Response
    {
        try {
            $body = json_decode((string)$request->getBody(), true);
            $type = $body['type'] ?? null;
            $recipientEmail = $body['recipient_email'] ?? null;
            $ref = $body['ref'] ?? null;

            if (!$type || !$recipientEmail || !$ref) {
                return ResponseHandle::error($response, 'All required fields must be provided', 400);
            }

            $res = AuthAPIHelper::post('/v1/otp', [
                'type' => $type,
                'recipient_email' => $recipientEmail,
                'ref' => $ref,
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

    // POST /v1/otp/verify
    public function verify(Request $request, Response $response): Response
    {
        try {
            $body = json_decode((string)$request->getBody(), true);
            $type = $body['type'] ?? null;
            $recipientEmail = $body['recipient_email'] ?? null;
            $ref = $body['ref'] ?? null;
            $otpCode = $body['otp_code'] ?? null;

            if (!$type || !$recipientEmail || !$ref || !$otpCode) {
                return ResponseHandle::error($response, 'All required fields must be provided', 400);
            }

            $res = AuthAPIHelper::post('/v1/otp/verify', [
                'type' => $type,
                'recipient_email' => $recipientEmail,
                'ref' => $ref,
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
