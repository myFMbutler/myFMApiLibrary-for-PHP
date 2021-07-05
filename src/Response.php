<?php

namespace Lesterius\FileMakerApi;

use Lesterius\FileMakerApi\Exception\Exception;

/**
 * Class Response
 * @package Lesterius\FileMakerApi
 */
final class Response
{
    /**
     * @var array
     */
    private $headers = [];
    /**
     * @var string
     */
    private $body;
    /**
     * @var string
     */
    private $responseType;

    const RESPONSE_TYPE_JSON = 'json';
    const RESPONSE_TYPE_TEXT = 'text';

    /**
     * Response constructor.
     *
     * @param array  $headers
     * @param string $body
     *
     * @throws Exception
     */
    public function __construct($headers, $body)
    {
        $this->setHeaders($headers);
        $this->body         = $body;
        $this->responseType = is_array($body) ? self::RESPONSE_TYPE_JSON : self::RESPONSE_TYPE_TEXT;
    }

    /**
     * @param string $headers
     * @param string $body
     *
     * @return self
     * @throws Exception
     */
    public static function parse($headers, $body)
    {
        return new self(self::parseHeaders($headers), self::parseBody($body));
    }

    /**
     * @param array $headers
     *
     * @return Response
     * @throws Exception
     */
    public function setHeaders($headers)
    {
        if (!is_array($headers)) {
            throw new Exception();
        }

        $this->headers = $headers;

        return $this;
    }

    /**
     * @param $header
     *
     * @return mixed
     * @throws Exception
     */
    public function getHeader($header)
    {
        if (isset($this->headers[$header])) {
            return $this->headers[$header];
        }

        if (isset($this->headers[strtolower($header)])) {
            return $this->headers[strtolower($header)];
        }

        throw new Exception("Header not found");
    }

    /**
     * @return int
     * @throws Exception
     */
    public function getHttpCode()
    {
        $httpHeader = $this->getHeader('Status');
        $httpHeader = explode(" ", $httpHeader);

        return (int)$httpHeader[1];
    }

    /**
     * @param bool $raw
     *
     * @return string
     */
    public function getBody($raw = false)
    {
        if (!$raw) {
            return $this->body;
        }

        return ($this->responseType === self::RESPONSE_TYPE_JSON) ? json_encode($this->body) : $this->body;
    }

    /**
     * @param $headers
     *
     * @return array
     */
    private static function parseHeaders($headers)
    {
        // We convert the raw header string into an array
        $headers = explode("\n", $headers);
        $headers = array_map(function ($header) {
            $exploded = explode(":", $header, 2);

            return array_map(function ($value) {
                return trim($value);
            }, $exploded);
        }, $headers);

        // We remove empty lines in array
        $headers = array_filter($headers, function ($value) {
            return (is_array($value) ? $value[0] : $value) !== '';
        });

        // Lastly, we clean the array format to be a key => value array
        // The response code is special as there is no key. We handle it differently
        $statusHeader = [];
        foreach ($headers as $index => $header) {
            if (isset($header[1])) {
                break;
            }

            $statusHeader = [
                'Status' => $header[0],
            ];
            unset($headers[$index]);
        }
        $processedHeaders = $statusHeader;

        foreach ($headers as $header) {
            if (!isset($header[1])) {
                continue;
            }
            
            $processedHeaders[$header[0]] = $header[1];
        }

        return $processedHeaders;
    }

    /**
     * @param $body
     *
     * @return mixed
     */
    private static function parseBody($body)
    {
        return self::isJson($body) ? json_decode($body, true) : $body;
    }


    private static function isJson($string)
    {
        return json_decode($string, true) !== null;
    }
}
