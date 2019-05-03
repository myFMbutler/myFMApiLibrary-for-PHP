<?php

namespace Lesterius\FileMakerApi;

/**
 * Interface DataApiInterface
 * @package Lesterius\FileMakerApi
 */
interface DataApiInterface
{
    /**
     * @param $apiUsername
     * @param $apiPassword
     *
     * @return mixed
     */
    public function login($apiUsername, $apiPassword);

    /**
     * @param $oAuthRequestId
     * @param $oAuthIdentifier
     *
     * @return mixed
     */
    public function loginOauth($oAuthRequestId, $oAuthIdentifier);

    /**
     * @param       $layout
     * @param array $data
     * @param array $scripts
     * @param array $portalData
     *
     * @return mixed
     */
    public function createRecord($layout, array $data, array $scripts = [], array $portalData = []);

    /**
     * @param       $layout
     * @param       $recordId
     * @param array $data
     * @param null  $lastModificationId
     * @param array $portalData
     * @param array $scripts
     *
     * @return mixed
     */
    public function editRecord($layout, $recordId, array $data, $lastModificationId = null, array $portalData = [], array $scripts = []);

    /**
     * @param       $layout
     * @param       $recordId
     * @param array $portalOptions
     * @param array $scripts
     *
     * @return mixed
     */
    public function getRecord($layout, $recordId, array $portalOptions = [], array $scripts = []);

    /**
     * @param       $layout
     * @param null  $sort
     * @param null  $offset
     * @param null  $limit
     * @param array $portals
     * @param array $scripts
     *
     * @return mixed
     */
    public function getRecords($layout, $sort = null, $offset = null, $limit = null, array $portals = [], array $scripts = []);

    /**
     * @param $layout
     * @param $recordId
     * @param $containerFieldName
     * @param $containerFieldRepetition
     * @param $filepath
     * @param $filename
     *
     * @return mixed
     */
    public function uploadToContainer($layout, $recordId, $containerFieldName, $containerFieldRepetition, $filepath, $filename);

    /**
     * @param        $layout
     * @param        $query
     * @param null   $sort
     * @param null   $offset
     * @param null   $limit
     * @param array  $portals
     * @param array  $scripts
     * @param string $responseLayout
     *
     * @return mixed
     */
    public function findRecords($layout, $query, $sort = null, $offset = null, $limit = null, array $portals = [], array $scripts = [], $responseLayout = null);

    /**
     * @param       $layout
     * @param array $globalFields
     *
     * @return mixed
     */
    public function setGlobalFields($layout, array $globalFields);

    /**
     * @param       $layout
     * @param       $recordId
     * @param array $scripts
     *
     * @return mixed
     */
    public function deleteRecord($layout, $recordId, $scripts = []);

    /**
    * @return mixed
    */
    public function logout();

    /**
     * @return mixed
     */
    public function getApiToken();
}
