<?php

namespace Lesterius\FileMakerApi;

require_once __DIR__.'/../vendor/autoload.php';

/**
 * Class FileMakerApiTest
 * @package Lesterius\DataApi
 */
class DataApiSimpleTest
{
    protected $dataApi;

    /**
     * DataApiSimpleTest constructor.
     *
     * @param      $url
     * @param      $database
     * @param bool $sslVerify
     *
     * @throws \Exception
     */
    public function __construct($url, $database, $sslVerify = true)
    {
        $this->dataApi = new DataApi($url, $database, $sslVerify);
    }

    /**
     * @param $username
     * @param $password
     *
     * @return null|string
     */
    public function loginTest($username, $password)
    {
        try {
            $this->dataApi->login($username, $password);
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return $this->dataApi->getApiToken();
    }

    /**
     * @param $oAuthRequestId
     * @param $oAuthIdentifier
     *
     * @return null|string
     */
    public function loginOauthTest($oAuthRequestId, $oAuthIdentifier)
    {
        try {
            $this->dataApi->loginOauth($oAuthRequestId, $oAuthIdentifier);
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return $this->dataApi->getApiToken();
    }

    /**
     * @param $layout
     * @param $data
     *
     * @return string
     */
    public function createRecordTest($layout, array $data)
    {
        $record_id = null;

        try {
            $record_id = $this->dataApi->createRecord($layout, $data);
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return $record_id;
    }

    /**
     * @param       $layout
     * @param       $recordId
     * @param array $data
     * @param null  $lastModificationId
     *
     * @return mixed|string
     */
    public function editRecordTest($layout, $recordId, array $data, $lastModificationId = null)
    {
        try {
            $record_id = $this->dataApi->editRecord($layout, $recordId, $data, $lastModificationId);
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return $record_id;
    }

    /**
     * @param      $layout
     * @param      $recordId
     * @param null $offset
     * @param null $range
     * @param null $portal
     *
     * @return mixed|string
     */
    public function getRecordTest($layout, $recordId, $offset = null, $range = null, $portal = null)
    {
        try {
            $record_id = $this->dataApi->getRecord($layout, $recordId, $offset, $range, $portal);
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return $record_id;
    }

    /**
     * @param      $layout
     * @param null $sort
     * @param null $offset
     * @param null $range
     * @param null $portal
     *
     * @return mixed|string
     */
    public function getRecordsTest($layout, $sort = null, $offset = null, $range = null, $portal = null)
    {
        try {
            $result = $this->dataApi->getRecords($layout, $sort, $offset, $range, $portal);
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return $result;
    }

    /**
     * @param      $layout
     * @param      $query
     *
     * @param null $sort
     * @param null $offset
     * @param null $range
     * @param null $portal
     *
     * @return string
     */
    public function findRecordsTest($layout, $query, $sort = null, $offset = null, $range = null, $portal = null)
    {
        try {
            $result = $this->dataApi->findRecords($layout, $query, $sort, $offset, $range, $portal);
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return $result;
    }

    /**
     * @param       $layout
     * @param array $globalFields
     *
     * @return mixed|string
     */
    public function setGlobalFieldsTest($layout, array $globalFields)
    {
        try {
            $result = $this->dataApi->setGlobalFields($layout, $globalFields);
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return $result;
    }

    /**
     * @param $layout
     * @param $record_id
     *
     * @return string
     */
    public function deleteRecordTest($layout, $record_id)
    {
        try {
            $this->dataApi->deleteRecord($layout, $record_id);
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return $record_id;
    }

    /**
     * @return string
     */
    public function logoutTest()
    {
        try {
            $this->dataApi->logout();
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return true;
    }
}
