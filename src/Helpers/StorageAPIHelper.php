<?php

namespace App\Helpers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class StorageAPIHelper
{
    private static $client;

    private static function initClient()
    {
        if (!self::$client) {
            self::$client = new Client([
                'base_uri' => $_ENV['STORAGE_BASE_URL'],
                'headers' => [
                    'Authorization' => "Bearer " . $_ENV['STORAGE_CONNECTION_KEY'],
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

    public static function post($endpoint, $data = [], $queryParams = [], $isMultipart = false)
    {
        self::initClient();

        try {
            $options = [
                'query' => $queryParams,
            ];

            if ($isMultipart) {
                $options['multipart'] = $data;
            } else {
                $options['json'] = $data;
            }

            $response = self::$client->post($endpoint, $options);

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
