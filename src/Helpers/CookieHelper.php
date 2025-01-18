<?php

namespace App\Helpers;

use Carbon\Carbon;
use Psr\Http\Message\ResponseInterface as Response;

class CookieHelper
{
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
        $sameSite = in_array($sameSite, ['Strict', 'Lax', 'None'], true) ? $sameSite : 'Lax';
        $expires = Carbon::now('Asia/Bangkok')->addDays($days)->format('D, d-M-Y H:i:s T');

        $cookieHeader = sprintf(
            '%s=%s; Expires=%s; Path=%s; %s%s%sSameSite=%s',
            $name,
            $value,
            $expires,
            $path,
            $domain ? "Domain=$domain; " : '',
            $secure ? 'Secure; ' : '',
            $httpOnly ? 'HttpOnly; ' : '',
            $sameSite
        );

        return $response->withHeader('Set-Cookie', $cookieHeader);
    }

    public static function clearCookie(
        Response $response,
        string $name,
        string $path = '/',
        string $domain = '',
        bool $secure = false,
        bool $httpOnly = true,
        string $sameSite = 'Lax'
    ): Response {
        $sameSite = in_array($sameSite, ['Strict', 'Lax', 'None'], true) ? $sameSite : 'Lax';
        return self::setCookie($response, $name, '', -1, $path, $domain, $secure, $httpOnly, $sameSite);
    }
}
