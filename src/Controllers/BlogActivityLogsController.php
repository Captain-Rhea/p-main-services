<?php

namespace App\Controllers;

use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\BlogActivityLogModel;
use App\Helpers\ResponseHandle;

class BlogActivityLogsController
{
    /**
     * GET /v1/activity-log
     */
    public function getLogs(Request $request, Response $response, array $args): Response
    {
        try {
            $page = (int)($request->getQueryParams()['page'] ?? 1);
            $limit = (int)($request->getQueryParams()['per_page'] ?? 10);
            $userId = $request->getQueryParams()['user_id'] ?? null;
            $articleId = $request->getQueryParams()['article_id'] ?? null;
            $startDate = $request->getQueryParams()['start_date'] ?? null;
            $endDate = $request->getQueryParams()['end_date'] ?? null;

            $query = BlogActivityLogModel::orderBy('created_at', 'desc');

            if ($userId) {
                $query->whereDate('user_id', $userId);
            }

            if ($articleId) {
                $query->whereDate('article_id', $articleId);
            }

            if ($startDate) {
                $query->whereDate('created_at', '>=', $startDate);
            }

            if ($endDate) {
                $query->whereDate('created_at', '<=', $endDate);
            }

            $logs = $query->paginate($limit, ['*'], 'page', $page);

            $transformedData = array_map(function ($log) {
                return [
                    'id' => $log->id,
                    'article_id' => $log->article_id,
                    'action' => $log->action,
                    'details' => $log->details,
                    'created_at' => $log->created_at->toDateTimeString(),
                ];
            }, $logs->items());

            $data = [
                'pagination' => [
                    'current_page' => $logs->currentPage(),
                    'per_page' => $logs->perPage(),
                    'total' => $logs->total(),
                    'last_page' => $logs->lastPage(),
                ],
                'data' => $transformedData
            ];

            return ResponseHandle::success($response, $data, 'Logs retrieved successfully');
        } catch (Exception $e) {
            return ResponseHandle::error($response, $e->getMessage(), 500);
        }
    }

    /**
     * GET /v1/activity-log/article/{id}
     */
    public function getLogsByArticle(Request $request, Response $response, array $args): Response
    {
        try {
            $articleId = $args['id'] ?? null;
            if (!$articleId) {
                return ResponseHandle::error($response, 'Article ID is required.', 400);
            }

            $page = (int)($request->getQueryParams()['page'] ?? 1);
            $limit = (int)($request->getQueryParams()['per_page'] ?? 10);
            $startDate = $request->getQueryParams()['start_date'] ?? null;
            $endDate = $request->getQueryParams()['end_date'] ?? null;

            $query = BlogActivityLogModel::where('article_id', $articleId)->orderBy('created_at', 'desc');

            if ($startDate) {
                $query->whereDate('created_at', '>=', $startDate);
            }

            if ($endDate) {
                $query->whereDate('created_at', '<=', $endDate);
            }

            $logs = $query->paginate($limit, ['*'], 'page', $page);

            $transformedData = array_map(function ($log) {
                return [
                    'id' => $log->id,
                    'article_id' => $log->article_id,
                    'action' => $log->action,
                    'details' => $log->details,
                    'created_at' => $log->created_at->toDateTimeString(),
                ];
            }, $logs->items());

            $data = [
                'pagination' => [
                    'current_page' => $logs->currentPage(),
                    'per_page' => $logs->perPage(),
                    'total' => $logs->total(),
                    'last_page' => $logs->lastPage(),
                ],
                'data' => $transformedData
            ];

            return ResponseHandle::success($response, $data, 'Logs retrieved successfully');
        } catch (Exception $e) {
            return ResponseHandle::error($response, $e->getMessage(), 500);
        }
    }

    /**
     * GET /v1/activity-log/user/{id}
     */
    public function getLogsByUser(Request $request, Response $response, array $args): Response
    {
        try {
            $userId = $args['id'] ?? null;
            if (!$userId) {
                return ResponseHandle::error($response, 'User ID is required.', 400);
            }

            $page = (int)($request->getQueryParams()['page'] ?? 1);
            $limit = (int)($request->getQueryParams()['per_page'] ?? 10);
            $startDate = $request->getQueryParams()['start_date'] ?? null;
            $endDate = $request->getQueryParams()['end_date'] ?? null;

            $query = BlogActivityLogModel::where('user_id', $userId)->orderBy('created_at', 'desc');

            if ($startDate) {
                $query->whereDate('created_at', '>=', $startDate);
            }

            if ($endDate) {
                $query->whereDate('created_at', '<=', $endDate);
            }

            $logs = $query->paginate($limit, ['*'], 'page', $page);

            $transformedData = array_map(function ($log) {
                return [
                    'id' => $log->id,
                    'article_id' => $log->article_id,
                    'action' => $log->action,
                    'details' => $log->details,
                    'created_at' => $log->created_at->toDateTimeString(),
                ];
            }, $logs->items());

            $data = [
                'pagination' => [
                    'current_page' => $logs->currentPage(),
                    'per_page' => $logs->perPage(),
                    'total' => $logs->total(),
                    'last_page' => $logs->lastPage(),
                ],
                'data' => $transformedData
            ];

            return ResponseHandle::success($response, $data, 'User logs retrieved successfully');
        } catch (Exception $e) {
            return ResponseHandle::error($response, $e->getMessage(), 500);
        }
    }
}
