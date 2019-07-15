<?php

namespace Lesterius\FileMakerApi;

/**
 * Interface DataApiInterface
 * @package Lesterius\FileMakerApi
 */
interface DataApiInterface
{
    /**
     *
     * @return mixed
     */
    public function login();

    /**
     * @return mixed
     */
    public function logout();

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
     * @param $layout
     * @param $recordId
     * @param array $scripts
     *
     * @return mixed
     */
    public function duplicateRecord($layout, $recordId, array $scripts = []);

    /**
     * @param       $layout
     * @param       $recordId
     * @param array $scripts
     *
     * @return mixed
     */
    public function deleteRecord($layout, $recordId, array $scripts = []);

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
     * @param array $portals
     * @param array $scripts
     * @param null $responseLayout
     *
     * @return mixed
     */
    public function getRecord($layout, $recordId, array $portals = [], array $scripts = [], $responseLayout = null);

    /**
     * @param       $layout
     * @param null  $sort
     * @param null  $offset
     * @param null  $limit
     * @param array $portals
     * @param array $scripts
     * @param null $responseLayout
     *
     * @return mixed
     */
    public function getRecords($layout, $sort = null, $offset = null, $limit = null, array $portals = [], array $scripts = [], $responseLayout = null);

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
     * @param $layout
     * @param $scriptName
     * @param null $scriptParam
     *
     * @return mixed
     */
    public function executeScript($layout, $scriptName, $scriptParam = null);

    /**
     * @param $layout
     * @param $recordId
     * @param $containerFieldName
     * @param null $containerFieldRepetition
     * @param $filepath
     * @param null $filename
     *
     * @return mixed
     */
    public function uploadToContainer($layout, $recordId, $containerFieldName, $containerFieldRepetition = null, $filepath, $filename = null);

    /**
     * @param       $layout
     * @param array $globalFields
     *
     * @return mixed
     */
    public function setGlobalFields($layout, array $globalFields);

    /**
     * @return mixed
     */
    public function getProductInfo();

    /**
     * @return mixed
     */
    public function getDatabaseNames();

    /**
     * @return mixed
     */
    public function getLayoutNames();

    /**
     * @return mixed
     */
    public function getScriptNames();

    /**
     * @param $layout
     * @param null $recordId
     *
     * @return mixed
     */
    public function getLayoutMetadata($layout, $recordId = null);

    /**
     * @return mixed
     */
    public function getApiToken();
}
