<?php

use Slim\App;
use App\Helpers\ResponseHandle;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use App\Routes\AuthRoute;
use App\Routes\BlogActivityLogsRoute;
use App\Routes\CategoriesTagsRoute;
use App\Routes\MemberRoute;
use App\Routes\MyMemberRoute;
use App\Routes\RolePermissionRoute;
use App\Routes\StorageRoute;

return function (App $app) {
    (new AuthRoute($app))->register();
    (new MemberRoute($app))->register();
    (new MyMemberRoute($app))->register();
    (new StorageRoute($app))->register();
    (new RolePermissionRoute($app))->register();
    (new CategoriesTagsRoute($app))->register();
    (new BlogActivityLogsRoute($app))->register();

    $app->map(['GET', 'POST', 'PUT', 'DELETE'], '/{routes:.+}', function (Request $request, Response $response) {
        return ResponseHandle::error($response, 'Route not found', 404);
    });
};
