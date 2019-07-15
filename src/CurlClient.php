<?php

namespace Lesterius\FileMakerApi;

use Lesterius\FileMakerApi\Exception\Exception;

/**
 * Class CurlClient
 *
 * @package Lesterius\DataApi
 */
final class CurlClient
{
    private $sslVerify = false;
    private $baseUrl   = null;

    /**
     * CurlClient constructor
     *
     * @param $apiUrl
     * @param $sslVerify
     */
    public function __construct($apiUrl, $sslVerify)
    {
        $this->sslVerify = $sslVerify;
        $this->baseUrl   = $apiUrl;
    }

    /**
     * Execute a cURL request
     *
     * @param       $method
     * @param       $url
     * @param array $options
     *
     * @return Response
     * @throws Exception
     */
    public function request($method, $url, array $options)
    {
        $ch = curl_init();
        if ($ch === false) {
            throw new Exception('Failed to initialize curl');
        }

        $headers = [];
        $completeUrl = str_replace('%2F', '/', $this->baseUrl . curl_escape($ch, $url));

        if (!$this->sslVerify) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_POST, ($method === 'POST' ? true : false));

        $contentLength = 0;
        if (isset($options['json']) && !empty($options['json']) && $method !== 'GET') {
            $body = "{";
            foreach ($options['json'] as $jsonOptionKey => $jsonOptionData) {
                $body.= json_encode($jsonOptionKey) . ':'.( $this->isJson($jsonOptionData) ? $jsonOptionData : json_encode($jsonOptionData)).',';
            }
            $body = rtrim($body, ',');
            $body.= "}";

            if ($body === false) {
                throw new Exception("Failed to json encode parameters");
            }

            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);

            $contentLength = mb_strlen($body);
        }

        if (isset($options['file']) && !empty($options['file']) && $method === 'POST') {
            $cURLFile  = new \CURLFile($options['file']['path'], mime_content_type($options['file']['path']), $options['file']['name']);

            curl_setopt($ch, CURLOPT_POSTFIELDS, ['upload' => $cURLFile]);

            $contentLength = false;
        }

        //-- Set headers
        if (!isset($options['headers']['Content-Type'])) {
            $options['headers']['Content-Type'] = 'application/json';
        }

        if (!isset($options['headers']['Content-Length']) && $contentLength !== false) {
            $options['headers']['Content-Length'] = $contentLength;
        }

        foreach ($options['headers'] as $headerKey => $headerValue) {
            $headers[] = $headerKey.':'.$headerValue;
        }
        //--

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, true);

        if (isset($options['query_params']) && !empty($options['query_params'])) {
            $query_params = http_build_query($options['query_params']);
            $completeUrl .= (!empty($query_params) ? '?'.$query_params : '');
        }

        curl_setopt($ch, CURLOPT_URL, $completeUrl);

        $result = curl_exec($ch);

        if ($result === false) {
            throw new Exception(curl_error($ch), curl_errno($ch));
        }

        $responseHeaders  = substr($result, 0, curl_getinfo($ch, CURLINFO_HEADER_SIZE));
        $body             = substr($result, curl_getinfo($ch, CURLINFO_HEADER_SIZE));
        $response         = Response::parse($responseHeaders, $body);

        curl_close($ch);

        $this->validateResponse($response);

        return $response;
    }
    
    /**
     * @param Response $response
     *
     * @throws Exception
     */
    private function validateResponse(Response $response)
    {
        if ($response->getHttpCode() >= 400 && $response->getHttpCode() < 600 || $response->getHttpCode() === 100) {
            if (isset($response->getBody()['messages'][0]['message'])) {
                $eMessage = is_array($response->getBody()['messages'][0]['message']) ? implode(' - ', $response->getBody()['messages'][0]['message']) : $response->getBody()['messages'][0]['message'];
                $eCode    = isset($response->getBody()['messages'][0]['code']) ? $response->getBody()['messages'][0]['code'] : $response->getHttpCode();

                throw new Exception($eMessage, $eCode);
            }

            // A status code 100 with no message is OK
            if ($response->getHttpCode() !== 100) {
                $message = is_array($response->getBody() || is_object($response->getBody()) ?
                    json_encode($response->getBody()) : $response->getBody());

                if (empty($message)) {
                    $message = $response->getHeader('Status');
                }

                throw new Exception($message, $response->getHttpCode());
            }
        }
    }

    /**
     * @param $string
     * @return bool
     */
    private function isJson($string) {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }
}
