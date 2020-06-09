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
    const LASTVERSION        = 'vLatest';

    protected $ClientRequest  = null;
    protected $apiDatabase    = null;
    protected $apiToken       = null;
    protected $convertToAssoc = true;

    private   $apiUsername          = null;
    private   $apiPassword          = null;
    private   $oAuthRequestId       = null;
    private   $oAuthIdentifier      = null;
    private   $version              = null;

    /**
     * DataApi constructor
     *
     * @param      $apiUrl
     * @param      $apiDatabase
     * @param null $version
     * @param      $apiUsername
     * @param      $apiPassword
     *
     * @param bool $sslVerify
     * @param null $oAuthRequestId
     * @param null $oAuthIdentifier
     * @throws Exception
     */
    public function __construct($apiUrl, $apiDatabase, $version = null, $apiUsername = null, $apiPassword = null, $sslVerify = true, $oAuthRequestId = null, $oAuthIdentifier = null)
    {
        $this->apiDatabase      = $apiDatabase;
        $this->ClientRequest    = new CurlClient($apiUrl, $sslVerify);
        $this->apiUsername      = $apiUsername;
        $this->apiPassword      = $apiPassword;
        $this->oAuthRequestId   = $oAuthRequestId;
        $this->oAuthIdentifier  = $oAuthIdentifier;

        if ((empty($apiUsername) && empty($apiPassword)) || (empty($oAuthRequestId) && empty($oAuthIdentifier))) {
            new \Exception("Data Api needs valid credentials [username;password] or [authRequestId;authIdentifier]");
        }

        if (is_null($version)) {
            $this->version = self::LASTVERSION;
        } else {
            $this->version = $version;
        }

        // Basic default Authentication
        $this->login();
    }

    // -- Start auth Part --

    /**
     * Login to FileMaker API
     *
     *
     *
     * @return $this
     * @throws Exception
     */
    public function login()
    {
        $headers = $this->getHeaderAuth();

        // Send curl request
        $response = $this->ClientRequest->request(
            'POST',
            "/$this->version/databases/$this->apiDatabase/sessions",
            [
                'headers' => $headers,
                'json'    => []
            ]
        );

        $this->apiToken = $response->getHeader('X-FM-Data-Access-Token');

        return $this;
    }

    /**
     *  Close the connection with FileMaker Server API
     *
     * @throws Exception
     */
    public function logout()
    {
        // Send curl request
        $this->ClientRequest->request(
            'DELETE',
            "/$this->version/databases/$this->apiDatabase/sessions/$this->apiToken",
            []
        );

        $this->apiToken = null;

        return $this;
    }

    /**
     * Validate Session
     *
     * @return bool
     * @throws Exception
     */
    public function validateSession()
    {
        // Send curl request
        $response = $this->ClientRequest->request(
            'GET',
            "/$this->version/validateSession",
            [
                'headers' => $this->getDefaultHeaders()
            ]
        );

        return (isset($response->getBody()['messages'][0]['code']) && $response->getBody()['messages'][0]['code'] == "0");
    }

    // -- End auth Part --

    // -- Start records Part --

    /**
     * Create a new record
     *
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
        // Prepare options
        $jsonOptions = $this->encodeFieldData($data);

        if (!empty($portalData)) {
            $jsonOptions['portalData'] = $this->encodePortalData($portalData);
        }

        // Send curl request
        $response = $this->ClientRequest->request(
            'POST',
            "/$this->version/databases/$this->apiDatabase/layouts/$layout/records",
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
     * Duplicate an existing record
     *
     * @param $layout
     * @param $recordId
     * @param array $scripts
     * @return mixed
     * @throws Exception
     */
    public function duplicateRecord($layout, $recordId, array $scripts = [])
    {
        // Send curl request
        $response = $this->ClientRequest->request(
            'POST',
            "/$this->version/databases/$this->apiDatabase/layouts/$layout/records/$recordId",
            [
                'headers' => $this->getDefaultHeaders(),
                'json'    => array_merge(
                    $this->prepareScriptOptions($scripts)
                ),
            ]
        );

        return $response->getBody()['response']['recordId'];
    }

    /**
     * Edit an existing record by ID
     *
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
        // Prepare options
        $jsonOptions = $this->encodeFieldData($data);

        if (!empty($lastModificationId)) {
            $jsonOptions['modId'] = $lastModificationId;
        }

        if (!empty($portalData)) {
            $jsonOptions['portalData'] = $this->encodePortalData($portalData);
        }

        // Send curl request
        $response = $this->ClientRequest->request(
            'PATCH',
            "/$this->version/databases/$this->apiDatabase/layouts/$layout/records/$recordId",
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
     * Delete record by ID
     *
     * @param       $layout
     * @param       $recordId
     * @param array $scripts
     *
     * @throws Exception
     */
    public function deleteRecord($layout, $recordId, array $scripts = [])
    {
        // Send curl request
        $this->ClientRequest->request(
            'DELETE',
            "/$this->version/databases/$this->apiDatabase/layouts/$layout/records/$recordId",
            [
                'headers' => $this->getDefaultHeaders(),
                'json'    => $this->prepareScriptOptions($scripts),
            ]
        );
    }

    /**
     * Get record detail by ID
     *
     * @param       $layout
     * @param       $recordId
     * @param array $portals
     * @param array $scripts
     *
     * @param null $responseLayout
     * @return mixed
     * @throws Exception
     */
    public function getRecord($layout, $recordId, array $portals = [], array $scripts = [], $responseLayout = null)
    {
        // Prepare options
        $jsonOptions = [];

        // optional parameters
        if (!empty($responseLayout)) {
            $jsonOptions['layout.response'] = $responseLayout;
        }

        // Send curl request
        $response = $this->ClientRequest->request(
            'GET',
            "/$this->version/databases/$this->apiDatabase/layouts/$layout/records/$recordId",
            [
                'headers'      => $this->getDefaultHeaders(),
                'query_params' => array_merge(
                    $jsonOptions,
                    $this->prepareScriptOptions($scripts),
                    $this->preparePortalsOptions($portals)
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
     * @param null  $responseLayout
     *
     * @return mixed
     * @throws Exception
     */
    public function getRecords($layout, $sort = null, $offset = null, $limit = null, array $portals = [], array $scripts = [], $responseLayout = null)
    {
        // Prepare options
        $jsonOptions = [];

        // Search options
        $this->prepareJsonOption($jsonOptions, $offset, $limit, $sort, $responseLayout, true);

        // Send curl request
        $response = $this->ClientRequest->request(
            'GET',
            "/$this->version/databases/$this->apiDatabase/layouts/$layout/records",
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
    public function findRecords($layout, $query, $sort = null, $offset = null, $limit = null, array $portals = [], array $scripts = [], $responseLayout = null)
    {
        // Prepare query
        if (!is_array($query)) {
            $preparedQuery = [$query];
        } else {
            $preparedQuery = $this->prepareQueryOptions($query);
        }

        // Prepare options
        $jsonOptions = [
            'query' => json_encode($preparedQuery),
        ];

        // Search options
        $this->prepareJsonOption($jsonOptions, $offset, $limit, $sort, $responseLayout);

        // Send curl request
        try {
            $response = $this->ClientRequest->request(
                'POST',
                "/$this->version/databases/$this->apiDatabase/layouts/$layout/_find",
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

    // -- End records Part --

    // -- Start scripts Part --

    /**
     * Execute script alone
     *
     * @param $layout
     * @param $scriptName
     * @param null $scriptParam
     * @return mixed
     * @throws Exception
     */
    public function executeScript($layout, $scriptName, $scriptParam = null)
    {
        // Prepare options
        $jsonOptions = [];

        // optional parameters
        if (!empty($scriptParam)) {
            $jsonOptions['script.param'] = $scriptParam;
        }

        // Send curl request
        $response = $this->ClientRequest->request(
            'GET',
            "/$this->version/databases/$this->apiDatabase/layouts/$layout/script/$scriptName",
            [
                'headers'      => $this->getDefaultHeaders(),
                'query_params' => array_merge(
                    $jsonOptions
                ),
            ]
        );

        $result = (isset($response->getBody()['response']['scriptResult'])?$response->getBody()['response']['scriptResult']:$response->getBody()['response']['scriptError']);
        return $result;
    }

    // -- End scripts Part --

    // -- Start container Part --

    /**
     * Upload files into container field with or without specific repetition
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
    public function uploadToContainer($layout, $recordId, $containerFieldName, $containerFieldRepetition = null, $filepath, $filename = null)
    {
        // Prepare options
        $containerFieldRepetitionFormat = "";

        if (empty($filename)) {
            $filename = pathinfo($filepath, PATHINFO_FILENAME).'.'.pathinfo($filepath, PATHINFO_EXTENSION);
        }

        if (!empty($containerFieldRepetition)) {
            $containerFieldRepetitionFormat = '/'.intval($containerFieldRepetition);
        }

        // Send curl request
        $this->ClientRequest->request(
            'POST',
            "/$this->version/databases/$this->apiDatabase/layouts/$layout/records/$recordId/containers/$containerFieldName".$containerFieldRepetitionFormat,
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

    // -- End container Part --

    // -- Start globals Part --

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
        // Send curl request
        $response = $this->ClientRequest->request(
            'PATCH',
            "/$this->version/databases/$this->apiDatabase/globals",
            [
                'headers' => $this->getDefaultHeaders(),
                'json'    => [
                    'globalFields' => json_encode($globalFields),
                ],
            ]
        );

        return $response->getBody()['response'];
    }

    // -- End globals Part --

    // -- Start metadata Part --

    /**
     * @return mixed
     * @throws Exception
     */
    public function getProductInfo()
    {
        // Send curl request
        $response = $this->ClientRequest->request(
            'GET',
            "/$this->version/productInfo",
            [
                'headers' => $this->getDefaultHeaders(),
                'json'    => []
            ]
        );

        return $response->getBody()['response'];
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function getDatabaseNames()
    {
        // Send curl request
        $response = $this->ClientRequest->request(
            'GET',
            "/$this->version/databases",
            [
                'headers' => $this->getHeaderAuth(),
                'json'    => []
            ]
        );

        return $response->getBody()['response'];
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function getLayoutNames()
    {
        // Send curl request
        $response = $this->ClientRequest->request(
            'GET',
            "/$this->version/databases/$this->apiDatabase/layouts",
            [
                'headers' => $this->getDefaultHeaders(),
                'json'    => []
            ]
        );

        return $response->getBody()['response'];
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function getScriptNames()
    {
        // Send curl request
        $response = $this->ClientRequest->request(
            'GET',
            "/$this->version/databases/$this->apiDatabase/scripts",
            [
                'headers' => $this->getDefaultHeaders(),
                'json'    => []
            ]
        );

        return $response->getBody()['response'];
    }

    /**
     * @param $layout
     * @param null $recordId
     *
     * @throws Exception
     * @return mixed
     */
    public function getLayoutMetadata($layout, $recordId = null)
    {
        // Prepare options
        $jsonOptions = [];

        $metadataFormat = '/metadata';

        if (!empty($recordId)) {
            $jsonOptions['recordId'] = $recordId;
            $metadataFormat = '';
        }

        // Send curl request
        $response = $this->ClientRequest->request(
            'GET',
            "/$this->version/databases/$this->apiDatabase/layouts/$layout".$metadataFormat,
            [
                'headers' => $this->getDefaultHeaders(),
                'json'    => array_merge(
                    $jsonOptions
                ),
            ]
        );

        return $response->getBody()['response'];
    }

    // -- End metadata Part --


    // -- Class accessors --

    /**
     *  Get API token returned after a successful login
     *
     * @return null|string
     */
    public function getApiToken()
    {
        return $this->apiToken;
    }

    /**
     * @return null
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param null $version
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }

    /**
     * @return null
     */
    public function getApiUsername()
    {
        return $this->apiUsername;
    }

    /**
     * @param null $apiUsername
     */
    public function setApiUsername($apiUsername)
    {
        $this->apiUsername = $apiUsername;
    }

    /**
     * @return null
     */
    public function getApiPassword()
    {
        return $this->apiPassword;
    }

    /**
     * @param null $apiPassword
     */
    public function setApiPassword($apiPassword)
    {
        $this->apiPassword = $apiPassword;
    }

    /**
     * @return null
     */
    public function getOAuthRequestId()
    {
        return $this->oAuthRequestId;
    }

    /**
     * @param null $oAuthRequestId
     */
    public function setOAuthRequestId($oAuthRequestId)
    {
        $this->oAuthRequestId = $oAuthRequestId;
    }

    /**
     * @return null
     */
    public function getOAuthIdentifier()
    {
        return $this->oAuthIdentifier;
    }

    /**
     * @param null $oAuthIdentifier
     */
    public function setOAuthIdentifier($oAuthIdentifier)
    {
        $this->oAuthIdentifier = $oAuthIdentifier;
    }

    /**
     *  Set API token in request headers
     *
     * @return array
     */
    private function getDefaultHeaders()
    {
        return ['Authorization' => "Bearer $this->apiToken"];
    }

    /**
     * Get header authorization for basic Auth
     *
     * @return array
     */
    private function getHeaderBasicAuth()
    {
        return ['Authorization' => 'Basic '.base64_encode("$this->apiUsername:$this->apiPassword")];
    }

    /**
     * Get header authorization for OAuth
     *
     * @return array
     */
    private function getHeaderOAuth()
    {
        return [
            'X-FM-Data-Login-Type'       => 'oauth',
            'X-FM-Data-OAuth-Request-Id' => $this->oAuthRequestId,
            'X-FM-Data-OAuth-Identifier' => $this->oAuthIdentifier,
        ];
    }

    /**
     * Get Header switch to parameters
     *
     * @return array
     */
    private function getHeaderAuth()
    {
        $headers = [];
        if (!empty($this->apiUsername)) {
            $headers = $this->getHeaderBasicAuth();
        }

        if (!empty($this->oAuthRequestId)) {
            $headers = $this->getHeaderOAuth();
        }

        return $headers;
    }

    // -- Options worker functions --

    /**
     * Prepare options fields for query
     *
     * @param array $query
     * @return array
     */
    private function prepareQueryOptions(array $query)
    {
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

        return $preparedQuery;
    }

    /**
     * Prepare options for script
     *
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
     * Prepare options for portals
     *
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

    /**
     * Json encode array with array_map options, for fieldData param
     *
     * @param array $data
     * @return array
     */
    private function encodeFieldData(array $data)
    {
        return [
            'fieldData' => json_encode(array_map('\strval', $data)),
        ];
    }

    /**
     * Normal json encode array
     *
     * @param array $portal
     * @return string
     */
    private function encodePortalData(array $portal)
    {
        return json_encode($portal);
    }

    /**
     * Prepare recurrent options for requests
     *
     * @param array $jsonOptions
     * @param null $offset
     * @param null $limit
     * @param null $sort
     * @param null $responseLayout
     * @param bool $withUnderscore
     */
    private function prepareJsonOption(array &$jsonOptions = [], $offset = null, $limit = null, $sort = null, $responseLayout = null, $withUnderscore = false)
    {
        $additionalCharacter = ($withUnderscore ? '_' : '');

        if (!empty($offset)) {
            $jsonOptions[$additionalCharacter.'offset'] = intval($offset);
        }

        if (!empty($limit)) {
            $jsonOptions[$additionalCharacter.'limit'] = intval($limit);
        }

        if (!empty($sort)) {
            $jsonOptions[$additionalCharacter.'sort'] = (is_array($sort) ? json_encode($sort) : $sort);
        }

        if (!empty($responseLayout)) {
            $jsonOptions['layout.response'] = $responseLayout;
        }
    }
}
