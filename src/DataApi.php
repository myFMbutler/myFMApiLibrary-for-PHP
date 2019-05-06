<?php

namespace Lesterius\FileMakerApi;

use Lesterius\FileMakerApi\Exception\Exception;

/**
 * Class DataApi
 * @package Lesterius\DataApi
 */
final class DataApi implements DataApiInterface
{
    const FILEMAKER_NO_RECORDS = 401;

    const SCRIPT_PREREQUEST  = 'prerequest';
    const SCRIPT_PRESORT     = 'presort';
    const SCRIPT_POSTREQUEST = 'postrequest';

    protected $ClientRequest  = null;
    protected $apiDatabase    = null;
    protected $apiToken       = null;
    protected $convertToAssoc = true;

    /**
     * DataApi constructor
     *
     * @param      $apiUrl
     * @param      $apiDatabase
     * @param bool $sslVerify
     * @param      $apiUser
     * @param      $apiPassword
     *
     * @throws Exception
     */
    public function __construct($apiUrl, $apiDatabase, $apiUser = null, $apiPassword = null, $sslVerify = true)
    {
        $this->apiDatabase   = $apiDatabase;
        $this->ClientRequest = new CurlClient($apiUrl, $sslVerify);

        if (!empty($apiUser)) {
            $this->login($apiUser, $apiPassword);
        }
    }

    /**
     * Login to FileMaker API
     *
     * @param $apiUsername
     * @param $apiPassword
     *
     * @return $this
     * @throws Exception
     */
    public function login($apiUsername, $apiPassword)
    {
        $response = $this->ClientRequest->request(
            'POST',
            "/v1/databases/$this->apiDatabase/sessions",
            [
                'headers' => [
                    'Authorization' => 'Basic '.base64_encode("$apiUsername:$apiPassword"),
                ],
                'json'    => [],
            ]
        );

        $this->apiToken = $response->getHeader('X-FM-Data-Access-Token');

        return $this;
    }

    /**
     * @param $oAuthRequestId
     * @param $oAuthIdentifier
     *
     * @return $this
     * @throws Exception
     */
    public function loginOauth($oAuthRequestId, $oAuthIdentifier)
    {
        $response = $this->ClientRequest->request(
            'POST',
            "/v1/databases/$this->apiDatabase/sessions",
            [
                'header' => [
                    'X-FM-Data-Login-Type'       => 'oauth',
                    'X-FM-Data-OAuth-Request-Id' => $oAuthRequestId,
                    'X-FM-Data-OAuth-Identifier' => $oAuthIdentifier,
                ],
                'json'   => [],
            ]
        );

        $this->apiToken = $response->getHeader('X-FM-Data-Access-Token');

        return $this;
    }

    /**
     * @param       $layout
     * @param array $data
     * @param array $scripts
     * @param array $portalData
     *
     * @return mixed
     * @throws Exception
     */
    public function createRecord($layout, array $data, array $scripts = [], array $portalData = [])
    {
        $jsonOptions = [
            'fieldData' => json_encode(array_map('\strval', $data))
        ];

        if (!empty($portalData)) {
            $jsonOptions['portalData'] = json_encode($portalData);
        }

        $response = $this->ClientRequest->request(
            'POST',
            "/v1/databases/$this->apiDatabase/layouts/$layout/records",
            [
                'headers' => $this->getDefaultHeaders(),
                'json'    => array_merge(
                    $jsonOptions,
                    $this->prepareScriptOptions($scripts)
                ),
            ]
        );

        return $response->getBody()['response']['recordId'];
    }

    /**
     * @param       $layout
     * @param       $recordId
     * @param array $data
     * @param null  $lastModificationId
     * @param array $portalData
     * @param array $scripts
     *
     * @return mixed
     * @throws Exception
     */
    public function editRecord($layout, $recordId, array $data, $lastModificationId = null, array $portalData = [], array $scripts = [])
    {
        $jsonOptions = [
            'fieldData' => json_encode(array_map('\strval', $data)),
        ];

        if (!is_null($lastModificationId)) {
            $jsonOptions['modId'] = $lastModificationId;
        }

        $response = $this->ClientRequest->request(
            'PATCH',
            "/v1/databases/$this->apiDatabase/layouts/$layout/records/$recordId",
            [
                'headers' => $this->getDefaultHeaders(),
                'json'    => array_merge(
                    $jsonOptions,
                    $this->prepareScriptOptions($scripts)
                ),
            ]
        );

        return $response->getBody()['response']['modId'];
    }

    /**
     * Get record detail
     *
     * @param       $layout
     * @param       $recordId
     * @param array $portalOptions
     * @param array $scripts
     *
     * @return mixed
     * @throws Exception
     */
    public function getRecord(
        $layout,
        $recordId,
        array $portalOptions = [],
        array $scripts = []
    ) {
        $queryParams = [];
        if (!empty($portalOptions)) {
            $queryParams['portal'] = $portalOptions['name'];

            if (isset($portalOptions['limit'])) {
                $queryParams['_limit.'.$queryParams['portal']] = $portalOptions['limit'];
            }
            if (isset($portalOptions['offset'])) {
                $queryParams['_offset.'.$queryParams['portal']] = $portalOptions['offset'];
            }
        }

        $response = $this->ClientRequest->request(
            'GET',
            "/v1/databases/$this->apiDatabase/layouts/$layout/records/$recordId",
            [
                'headers'      => $this->getDefaultHeaders(),
                'query_params' => array_merge(
                    $queryParams,
                    $this->prepareScriptOptions($scripts)
                ),
            ]
        );

        return $response->getBody()['response']['data'][0];
    }

    /**
     *  Get list of records
     *
     * @param       $layout
     * @param null  $sort
     * @param null  $offset
     * @param null  $limit
     * @param array $portals
     * @param array $scripts
     *
     * @return mixed
     * @throws Exception
     */
    public function getRecords(
        $layout,
        $sort = null,
        $offset = null,
        $limit = null,
        array $portals = [],
        array $scripts = []
    ) {
        $jsonOptions = [];

        if (!is_null($offset)) {
            $jsonOptions['_offset'] = intval($offset);
        }

        if (!is_null($limit)) {
            $jsonOptions['_limit'] = intval($limit);
        }

        if (!is_null($sort)) {
            $jsonOptions['_sort'] = (is_array($sort) ? json_encode($sort) : $sort);
        }

        $response = $this->ClientRequest->request(
            'GET',
            "/v1/databases/$this->apiDatabase/layouts/$layout/records",
            [
                'headers'      => $this->getDefaultHeaders(),
                'query_params' => array_merge(
                    $jsonOptions,
                    $this->prepareScriptOptions($scripts),
                    $this->preparePortalsOptions($portals)
                ),
            ]
        );

        return $response->getBody()['response']['data'];
    }

    /**
     *  Upload files into container
     *
     * @param $layout
     * @param $recordId
     * @param $containerFieldName
     * @param $containerFieldRepetition
     * @param $filepath
     * @param null $filename
     *
     * @return true
     *
     * @throws Exception
     */
    public function uploadToContainer($layout, $recordId, $containerFieldName, $containerFieldRepetition, $filepath, $filename = null)
    {
        if (empty($filename)) {
            $filename = pathinfo($filepath, PATHINFO_FILENAME).'.'.pathinfo($filepath, PATHINFO_EXTENSION);
        }
        
        $this->ClientRequest->request(
            'POST',
            "/v1/databases/$this->apiDatabase/layouts/$layout/records/$recordId/containers/$containerFieldName/$containerFieldRepetition",
            [
                'headers' => array_merge(
                    $this->getDefaultHeaders(),
                    ['Content-Type' => 'multipart/form-data']
                ),
                'file'    => [
                    'name' => $filename,
                    'path' => $filepath
                ]
            ]
        );

        return true;
    }

    /**
     * Find records
     *
     * @param       $layout
     * @param       $query
     * @param null  $sort
     * @param null  $offset
     * @param null  $limit
     * @param array $portals
     * @param array $scripts
     * @param null  $responseLayout
     *
     * @return mixed
     * @throws Exception
     */
    public function findRecords(
        $layout,
        $query,
        $sort = null,
        $offset = null,
        $limit = null,
        array $portals = [],
        array $scripts = [],
        $responseLayout = null
    ) {
        if (!is_array($query)) {
            $preparedQuery = [$query];
        } else {
            $preparedQuery = [];
            foreach ($query as $queryItem) {
                if (!isset($queryItem['fields'])) {
                    break;
                }

                $item = [];
                foreach ($queryItem['fields'] as $field) {
                    $item[$field['fieldname']] = $field['fieldvalue'];
                }

                if (isset($queryItem['options']['omit']) && $queryItem['options']['omit'] == true) {
                    $preparedQuery[] = array_merge($item, ['omit' => "true"]);
                } else {
                    $preparedQuery[] = $item;
                }
            }
        }

        $jsonOptions = [
            'query' => json_encode($preparedQuery),
        ];

        if (!is_null($offset)) {
            $jsonOptions['offset'] = intval($offset);
        }

        if (!is_null($limit)) {
            $jsonOptions['limit'] = intval($limit);
        }

        if (!is_null($sort)) {
            $jsonOptions['sort'] = (is_array($sort) ? json_encode($sort) : $sort);
        }

        try {
            $response = $this->ClientRequest->request(
                'POST',
                "/v1/databases/$this->apiDatabase/layouts/$layout/_find",
                [
                    'headers' => $this->getDefaultHeaders(),
                    'json'    => array_merge(
                        $jsonOptions,
                        $this->prepareScriptOptions($scripts),
                        $this->preparePortalsOptions($portals)
                    ),
                ]
            );
        } catch (Exception $e) {
            if ($e->getCode() == self::FILEMAKER_NO_RECORDS) {
                return [];
            }

            throw $e;
        }

        return $response->getBody()['response']['data'];
    }

    /**
     * Define one or multiple global fields
     *
     * @param       $layout
     * @param array $globalFields
     *
     * @return mixed
     * @throws Exception
     */
    public function setGlobalFields($layout, array $globalFields)
    {
        $response = $this->ClientRequest->request(
            'PATCH',
            "/v1/databases/$this->apiDatabase/globals",
            [
                'headers' => $this->getDefaultHeaders(),
                'json'    => [
                    'globalFields' => json_encode($globalFields),
                ],
            ]
        );

        return $response->getBody();
    }

    /**
     * Delete record by id
     *
     * @param       $layout
     * @param       $recordId
     * @param array $scripts
     *
     * @throws Exception
     */
    public function deleteRecord($layout, $recordId, $scripts = [])
    {
        $this->ClientRequest->request(
            'DELETE',
            "/v1/databases/$this->apiDatabase/layouts/$layout/records/$recordId",
            [
                'headers' => $this->getDefaultHeaders(),
                'json'    => $this->prepareScriptOptions($scripts),
            ]
        );
    }

    /**
     *  Close the connection with FileMaker Server API
     * @throws Exception
     */
    public function logout()
    {
        $this->ClientRequest->request(
            'DELETE',
            "/v1/databases/$this->apiDatabase/sessions/$this->apiToken",
            []
        );

        $this->resetObject();

        return $this;
    }

    /**
     *  Get API token returned after a succesful login
     *
     * @return null|string
     */
    public function getApiToken()
    {
        return $this->apiToken;
    }

    /**
     *  Set API token in request headers
     */
    private function getDefaultHeaders()
    {
        return ['Authorization' => "Bearer $this->apiToken"];
    }

    /**
     * Reset object properties
     */
    private function resetObject()
    {
        foreach ($this as $key => $value) {
            $this->$key = null;
        }
    }

    /**
     * @param array $scripts
     *
     * @return array
     */
    private function prepareScriptOptions(array $scripts)
    {
        $preparedScript = [];
        foreach ($scripts as $script) {
            if (!in_array($script['type'], [self::SCRIPT_POSTREQUEST, self::SCRIPT_PREREQUEST, self::SCRIPT_PRESORT])) {
                continue;
            }

            $scriptSuffix = !($script['type'] === self::SCRIPT_POSTREQUEST) ? '.'.$script['type'] : '';

            $preparedScript['script'.$scriptSuffix]          = $script['name'];
            $preparedScript['script'.$scriptSuffix.'.param'] = $script['param'];
        }

        return $preparedScript;
    }

    /**
     * @param array $portals
     *
     * @return array
     */
    private function preparePortalsOptions(array $portals)
    {
        if (empty($portals)) {
            return [];
        }

        $portalList = [];

        foreach ($portals as $portal) {
            $portalList[] = $portal['name'];

            if (isset($portal['offset'])) {
                $options['offset.'.$portal['name']] = intval($portal['offset']);
            }

            if (isset($portal['limit'])) {
                $options['limit.'.$portal['name']] = intval($portal['limit']);
            }
        }

        $options['portal'] = json_encode($portalList);

        return $options;
    }
}
