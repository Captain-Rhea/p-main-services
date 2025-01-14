<?php

namespace App\Helpers;

use Carbon\Carbon;
use Psr\Http\Message\ResponseInterface as Response;

class CookieHelper
{
    /**
     * Set a cookie in the response.
     *
     * @param Response $response
     * @param string $name
     * @param string $value
     * @param int $days
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $httpOnly
     * @param string $sameSite
     * @return Response
     */
    public static function setCookie(
        Response $response,
        string $name,
        string $value,
        int $days = 8,
        string $path = '/',
        string $domain = '',
        bool $secure = false,
        bool $httpOnly = true,
        string $sameSite = 'Lax'
    ): Response {
        $expires = Carbon::now('Asia/Bangkok')->addDays($days)->format('D, d-M-Y H:i:s T');

        $cookieHeader = sprintf(
            '%s=%s; Expires=%s; Path=%s; %s%s; SameSite=%s',
            $name,
            $value,
            $expires,
            $path,
            $secure ? 'Secure; ' : '',
            $httpOnly ? 'HttpOnly; ' : '',
            $sameSite
        );

        return $response->withHeader('Set-Cookie', $cookieHeader);
    }

    /**
     * Clear a cookie by name.
     *
     * @param Response $response
     * @param string $name
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $httpOnly
     * @param string $sameSite
     * @return Response
     */
    public static function clearCookie(
        Response $response,
        string $name,
        string $path = '/',
        string $domain = '',
        bool $secure = false,
        bool $httpOnly = true,
        string $sameSite = 'Lax'
    ): Response {
        return self::setCookie($response, $name, '', -1, $path, $domain, $secure, $httpOnly, $sameSite);
    }
}
