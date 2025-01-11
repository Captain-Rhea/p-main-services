<?php

namespace App\Helpers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class AuthAPIHelper
{
    private static $client;

    private static function initClient()
    {
        if (!self::$client) {
            self::$client = new Client([
                'base_uri' => $_ENV['IDP_BASE_URL'],
                'headers' => [
                    'Authorization' => "Bearer " . $_ENV['IDP_CONNECTION_KEY'],
                    'Accept' => 'application/json',
                ],
            ]);
        }
    }

    public static function get($endpoint, $queryParams = [])
    {
        self::initClient();

        try {
            $response = self::$client->get($endpoint, [
                'query' => $queryParams,
            ]);
            return $response;
        } catch (RequestException $e) {
            return $e->getResponse();
        }
    }

    public static function post($endpoint, $data = [], $queryParams = [])
    {
        self::initClient();

        try {
            $response = self::$client->post($endpoint, [
                'query' => $queryParams,
                'json' => $data,
            ]);

            return $response;
        } catch (RequestException $e) {
            return $e->getResponse();
        }
    }

    public static function put($endpoint, $data = [], $queryParams = [])
    {
        self::initClient();

        try {
            $response = self::$client->put($endpoint, [
                'query' => $queryParams,
                'json' => $data,
            ]);

            return $response;
        } catch (RequestException $e) {
            return $e->getResponse();
        }
    }

    public static function delete($endpoint, $data = [], $queryParams = [])
    {
        self::initClient();

        try {
            $response = self::$client->delete($endpoint, [
                'query' => $queryParams,
                'json' => $data,
            ]);

            return $response;
        } catch (RequestException $e) {
            return $e->getResponse();
        }
    }
}

// การใช้งาน
// $response = AuthAPIHelper::get('endpoint', ['param1' => 'value1']);
// $response = AuthAPIHelper::post('endpoint', ['key' => 'value'], ['param1' => 'value1']);
// $response = AuthAPIHelper::put('endpoint', ['key' => 'value'], ['param1' => 'value1']);
// $response = AuthAPIHelper::delete('endpoint', [], ['param1' => 'value1']);
