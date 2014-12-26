<?php

/**
 * NB! This is a BETA release of Erply Connector.
 *
 * Use with caution and at your own risk.
 *
 * The author does not take any responsibility for any loss or damage to business
 * or customers or anything at all. These terms may change without further notice.
 *
 * License terms are subject to change. License is all-restrictive until
 * said otherwise.
 *
 * @author Eepohs Ltd
 */
class Eepohs_Erply_Model_Erply extends Mage_Core_Model_Abstract
{

    const STATUS_OK = 'ok';

    const API_VERSION = '1.0';

    private $storeId;

    private $code;

    private $username;

    private $password;

    public function setStoreId($storeId)
    {
        $this->storeId = $storeId;

        return $this;
    }

    protected function _construct()
    {
        parent::_construct();
    }

    /**
     * @return mixed
     */
    protected function getPassword()
    {
        return $this->password;
    }

    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @return mixed
     */
    protected function getUsername()
    {
        return $this->username;
    }

    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @param $request
     * @param array $params
     * @return mixed
     */
    public function makeRequest($request, $params = array())
    {

        /** @var Eepohs_Erply_Helper_Data $helper */
        $helper = Mage::helper('Erply');

        if (!$this->validateRequiredProperties()) {
            $this->getConfig();
        }

        if ($request != 'verifyUser' && !$this->isSessionActive()) {
            $this->createSession();
        }

        $helper->log('Erply - Request started');

        $params['sessionKey'] = $this->getSessionKey();
        $params['request'] = $request;
        $params['version'] = self::API_VERSION;
        $params['clientCode'] = $this->getCode();
        $helper->log(print_r($params, 1));

        $http = new Varien_Http_Adapter_Curl();
        $http->setConfig(array('timeout' => 100));

        $http->write(Zend_Http_Client::POST, $this->getUrl(),
            CURL_HTTP_VERSION_1_0, array(), $params);

        $response = Zend_Http_Response::extractBody($http->read());
        $http->close();
        if ($http->getErrno()) {
            $helper->log($http->getError());
        }
        $helper->log('Erply - Request completed');

        return json_decode($response, 1);
    }

    /**
     * @return bool
     */
    public function isSessionActive()
    {
        /** @var Eepohs_Erply_Helper_Data $helper */
        $helper = Mage::helper('Erply');

        $session = $helper->getErplySession();
        if (empty($session))
            return false;
        if ($session['sessionLength'] < time()) {
            $helper->unsetErplySession();

            return false;
        }

        return true;
    }

    /**
     * @return bool|array
     */

    public function createSession()
    {
        if ($this->isSessionActive())
            return true;

        /** @var Eepohs_Erply_Helper_Data $helper */
        $helper = Mage::helper('Erply');

        $response = $this->makeRequest(
            'verifyUser',
            array(
                'username' => $this->username,
                'password' => $this->password
            )
        );

        if ($response['status']['responseStatus'] == self::STATUS_OK
            && !empty($response['records'][0]['sessionKey'])
        ) {
            $record = $response['records'][0];
            $helper->log('Erply - Session start');
            $helper->log($record);

            $helper->setErplySession(
                array(
                    'sessionKey' => $record['sessionKey'],
                    'sessionLength' => time() + $record['sessionLength'] - 30,
                    'UserId' => $record['userID'],
                    'EmployeeId' => $record['employeeID'],
                    'EmployeeName' => $record['employeeName']
                )
            );
            $helper->log('Erply - Session was created successfully');

            return $helper->getErplySession();
        }

        return false;
    }

    /**
     * @return bool
     */
    protected function validateRequiredProperties()
    {
        return $this->code && $this->username && $this->password;
    }

    public function getConfig()
    {
        $this->storeId = Mage::app()->getStore()->getStoreId();;

        $this->username = Mage::getStoreConfig('eepohs_erply/account/username', $this->storeId);
        $this->password = Mage::getStoreConfig('eepohs_erply/account/password', $this->storeId);
        $this->code = Mage::getStoreConfig('eepohs_erply/account/code', $this->storeId);
    }

    /**
     * @return null|string
     */
    public function getSessionKey()
    {
        /** @var Eepohs_Erply_Helper_Data $helper */
        $helper = Mage::helper('Erply');

        if (!$this->isSessionActive())
            return null;
        $session = $helper->getErplySession();

        return $session['sessionKey'];
    }

    /**
     * @return mixed
     */
    protected function getCode()
    {
        return $this->code;
    }

    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    public function getUrl()
    {
        if ($this->code)
            return "https://$this->code.erply.com/api/";

        return null;
    }
}
