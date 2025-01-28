<?php

namespace App\Controllers;

use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Helpers\ResponseHandle;
use App\Helpers\AuthAPIHelper;
use App\Helpers\StorageAPIHelper;

class MemberController
{
    /**
     * GET /v1/member/invite
     */
    public function getInvitation(Request $request, Response $response): Response
    {
        try {
            $queryParams = $request->getQueryParams();
            $res = AuthAPIHelper::get('/v1/member/invite', [
                'page' => $queryParams['page'] ?? 1,
                'per_page' => $queryParams['per_page'] ?? 10,
                'recipient_email' => $queryParams['recipient_email'] ?? null,
                'status_id' => $queryParams['status_id'] ?? null,
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
     * POST /v1/member/invite
     */
    public function createInvitation(Request $request, Response $response): Response
    {
        try {
            $user = $request->getAttribute('user');

            $body = json_decode((string)$request->getBody(), true);
            $inviter = $user['user_id'];
            $recipientEmail = $body['recipient_email'] ?? null;
            $roleId = $body['role_id'] ?? null;

            if (!$recipientEmail || !$roleId || !$inviter) {
                return ResponseHandle::error($response, 'Recipient Email, Role ID, and Inviter ID are required', 400);
            }

            $res = AuthAPIHelper::post('/v1/member/invite', [
                'inviter' => $inviter,
                'recipient_email' => $recipientEmail,
                'role_id' => $roleId
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
     * PUT /v1/member/invite/reject/{id}
     */
    public function rejectInvitation(Request $request, Response $response, $args): Response
    {
        try {
            $inviteId = $args['id'] ?? '';

            if (!$inviteId) {
                return ResponseHandle::error($response, 'Invite ID is required', 400);
            }

            $res = AuthAPIHelper::put('/v1/member/invite/reject/' . $inviteId);
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
     * POST /v1/member/invite/verify
     */
    public function verifyInvitation(Request $request, Response $response): Response
    {
        try {
            $body = json_decode((string)$request->getBody(), true);
            $refCode = $body['ref_code'] ?? null;

            if (!$refCode) {
                return ResponseHandle::error($response, 'Reference code is required', 400);
            }

            $res = AuthAPIHelper::post('/v1/member/invite/verify', [
                'ref_code' => $refCode,
            ]);
            $statusCode = $res->getStatusCode();
            $body = json_decode($res->getBody()->getContents(), true);
            if ($statusCode >= 400) {
                return ResponseHandle::apiError($response, $body, $statusCode);
            }

            $resRole = AuthAPIHelper::get('/v1/roles');
            $statusCodeRole = $resRole->getStatusCode();
            $bodyRole = json_decode($resRole->getBody()->getContents(), true);
            if ($statusCodeRole >= 400) {
                return ResponseHandle::apiError($response, $bodyRole, $statusCodeRole);
            }

            $roleId = $body['data']['role_id'];
            $role = null;
            foreach ($bodyRole['data'] as $roleItem) {
                if ($roleItem['id'] === intval($roleId)) {
                    $role = $roleItem['name'];
                    break;
                }
            }

            if (!$role) {
                return ResponseHandle::error($response, 'Role not found', 404);
            }

            $verifyData = [
                'recipient_email' => $body['data']['recipient_email'],
                'role' => $role,
            ];

            return ResponseHandle::success($response, $verifyData, 'The invitation has been successfully verified.');
        } catch (Exception $e) {
            return ResponseHandle::error($response, $e->getMessage(), 500);
        }
    }

    /**
     * POST /v1/member/invite/accept
     */
    public function acceptInvitation(Request $request, Response $response): Response
    {
        try {
            $body = json_decode((string)$request->getBody(), true);
            $refCode = $body['ref_code'] ?? null;
            $recipientEmail = $body['recipient_email'] ?? null;
            $password = $body['password'] ?? null;
            $roleName = $body['role_name'] ?? null;
            $phone = $body['phone'] ?? null;
            $firstNameTh = $body['first_name_th'] ?? null;
            $lastNameTh = $body['last_name_th'] ?? null;
            $nicknameTh = $body['nickname_th'] ?? null;
            $firstNameEn = $body['first_name_en'] ?? null;
            $lastNameEn = $body['last_name_en'] ?? null;
            $nicknameEn = $body['nickname_en'] ?? null;


            if (!$refCode || !$recipientEmail || !$password || !$roleName || !$phone) {
                return ResponseHandle::error($response, 'Ref Code, Email, password, role ID and phone are required', 400);
            }

            $resRole = AuthAPIHelper::get('/v1/roles');
            $statusCodeRole = $resRole->getStatusCode();
            $bodyRole = json_decode($resRole->getBody()->getContents(), true);
            if ($statusCodeRole >= 400) {
                return ResponseHandle::apiError($response, $bodyRole, $statusCodeRole);
            }

            $roleId = 0;
            foreach ($bodyRole['data'] as $roleItem) {
                if ($roleItem['name'] === $roleName) {
                    $roleId = $roleItem['id'];
                    break;
                }
            }

            $res = AuthAPIHelper::post('/v1/member/invite/accept', [
                'ref_code' => $refCode ?? null,
                'recipient_email' => $recipientEmail ?? null,
                'password' => $password ?? null,
                'role_id' => $roleId ?? null,
                'phone' => $phone ?? null,
                'first_name_th' => $firstNameTh ?? null,
                'last_name_th' => $lastNameTh ?? null,
                'nickname_th' => $nicknameTh ?? null,
                'first_name_en' => $firstNameEn ?? null,
                'last_name_en' => $lastNameEn ?? null,
                'nickname_en' => $nicknameEn ?? null,
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
     * GET /v1/member
     */
    public function getMembers(Request $request, Response $response): Response
    {
        try {
            $queryParams = $request->getQueryParams();
            $params = [
                'page' => $queryParams['page'] ?? 1,
                'per_page' => $queryParams['per_page'] ?? 10,
                'user_id' => $queryParams['user_id'] ?? null,
                'status_id' => $queryParams['status_id'] ?? null,
                'email' => $queryParams['email'] ?? null,
                'first_name' => $queryParams['first_name'] ?? null,
                'last_name' => $queryParams['last_name'] ?? null,
                'nickname' => $queryParams['nickname'] ?? null,
                'phone' => $queryParams['phone'] ?? null,
                'role_id' => $queryParams['role_id'] ?? null,
                'start_date' => $queryParams['start_date'] ?? null,
                'end_date' => $queryParams['end_date'] ?? null,
            ];

            $res = AuthAPIHelper::get('/v1/member', $params);
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
     * POST /v1/member
     */
    public function createMember(Request $request, Response $response): Response
    {
        try {
            $body = json_decode((string)$request->getBody(), true);
            $email = $body['email'] ?? null;
            $password = $body['password'] ?? null;
            $roleId = $body['role_id'] ?? null;
            $phone = $body['phone'] ?? null;
            $firstNameTh = $body['first_name_th'] ?? null;
            $lastNameTh = $body['last_name_th'] ?? null;
            $nicknameTh = $body['nickname_th'] ?? null;
            $firstNameEn = $body['first_name_en'] ?? null;
            $lastNameEn = $body['last_name_en'] ?? null;
            $nicknameEn = $body['nickname_en'] ?? null;

            if (!$email || !$password || !$roleId || !$phone) {
                return ResponseHandle::error($response, 'Email, password, role ID and phone are required', 400);
            }

            $res = AuthAPIHelper::post('/v1/member', [
                'email' => $email ?? null,
                'password' => $password ?? null,
                'role_id' => $roleId ?? null,
                'phone' => $phone ?? null,
                'first_name_th' => $firstNameTh ?? null,
                'last_name_th' => $lastNameTh ?? null,
                'nickname_th' => $nicknameTh ?? null,
                'first_name_en' => $firstNameEn ?? null,
                'last_name_en' => $lastNameEn ?? null,
                'nickname_en' => $nicknameEn ?? null,
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
     * DELETE /v1/member/{id}
     */
    public function permanentlyDeleteMember(Request $request, Response $response, $args): Response
    {
        try {
            $userId = $args['id'] ?? null;

            if (!$userId) {
                return ResponseHandle::error($response, 'User ID is required', 400);
            }

            $resMember = AuthAPIHelper::get('/v1/my-member/profile', ['user_id' => $userId]);
            $statusCodeMember = $resMember->getStatusCode();
            $bodyMember = json_decode($resMember->getBody()->getContents(), true);
            if ($statusCodeMember >= 400) {
                return ResponseHandle::apiError($response, $bodyMember, $statusCodeMember);
            }

            $avatarId = $bodyMember['data']['user_info']['avatar_id'];

            $res = AuthAPIHelper::delete('/v1/member/' . $userId);
            $statusCode = $res->getStatusCode();
            $body = json_decode($res->getBody()->getContents(), true);
            if ($statusCode >= 400) {
                return ResponseHandle::apiError($response, $body, $statusCode);
            }

            if ($avatarId) {
                $resDelete = StorageAPIHelper::delete('/v1/image', [], ['ids' => $avatarId]);
                $statusCodeDelete = $resDelete->getStatusCode();
                $responseBodyDelete = json_decode($resDelete->getBody()->getContents(), true);

                if ($statusCodeDelete >= 400) {
                    return ResponseHandle::apiError($response, $responseBodyDelete, $statusCodeDelete);
                }
            }

            return $res;
        } catch (Exception $e) {
            return ResponseHandle::error($response, $e->getMessage(), 500);
        }
    }

    /**
     * DELETE /v1/member/{id}/soft
     */
    public function softDeleteMember(Request $request, Response $response, $args): Response
    {
        try {
            $userId = $args['id'] ?? null;

            $res = AuthAPIHelper::delete('/v1/member/' . $userId . '/soft');
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
     * PUT /v1/member/{id}/restore
     */
    public function restoreDeleteMember(Request $request, Response $response, $args): Response
    {
        try {
            $userId = $args['id'] ?? null;

            if (!$userId) {
                return ResponseHandle::error($response, 'User ID is required', 400);
            }

            $res = AuthAPIHelper::put('/v1/member/active/' . $userId);
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
     * PUT /v1/member/suspend/{id}
     */
    public function suspendMember(Request $request, Response $response, $args): Response
    {
        try {
            $userId = $args['id'] ?? null;

            if (!$userId) {
                return ResponseHandle::error($response, 'User ID is required', 400);
            }

            $res = AuthAPIHelper::put('/v1/member/suspend/' . $userId);
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
     * PUT /v1/member/active/{id}
     */
    public function activeMember(Request $request, Response $response, $args): Response
    {
        try {
            $userId = $args['id'] ?? null;

            if (!$userId) {
                return ResponseHandle::error($response, 'User ID is required', 400);
            }

            $res = AuthAPIHelper::put('/v1/member/active/' . $userId);
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
     * PUT /v1/member/change-role/{user_id}
     */
    public function changeRoleMember(Request $request, Response $response, $args): Response
    {
        try {
            $userId = $args['user_id'] ?? null;

            if (!$userId) {
                return ResponseHandle::error($response, 'User ID is required', 400);
            }

            $body = json_decode((string)$request->getBody(), true);
            $newRoleId = $body['new_role_id'] ?? null;

            if (!$newRoleId) {
                return ResponseHandle::error($response, 'New Role ID is required', 400);
            }

            $res = AuthAPIHelper::put('/v1/member/change-role/' . $userId, [
                'new_role_id' => $newRoleId
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
