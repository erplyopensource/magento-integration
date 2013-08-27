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
    private $storeID;
    private $url;
    private $code;
    private $username;
    private $password;
    private $session;
    public $userId;

    protected function _construct()
    {
        parent::_construct();
    }

    public function sendRequest($request, $parameters = array())
    {
        if (!$this->code || !$this->username || !$this->password)
            return false;

        if ($request != 'verifyUser' && !$this->session) {
            return false;
        }
        $parameters['sessionKey'] = $this->session;
        $parameters['request'] = $request;
        $parameters['version'] = '1.0';
        $parameters['clientCode'] = $this->code;

        $url = $this->getUrl();

        $http = new Varien_Http_Adapter_Curl();
        $http->setConfig(array('timeout' => 100));
//        $http->write(Zend_Http_Client::POST, $parameters);
        $http->write(Zend_Http_Client::POST, $url, CURL_HTTP_VERSION_1_0, array(), $parameters);
        $responseBody = Zend_Http_Response::extractBody($http->read());
        $http->close();



//        $http = new Varien_Http_Adapter_Curl();
//        $config = array('timeout' => 30);
//
//        $http->setConfig($config);
//        $http->write(Zend_Http_Client::POST, $url, '1.0', array(), http_build_query($parameters));
//        $content = Zend_Http_Response::extractBody($http->read());
//        print_r(Zend_Http_Response::extractHeaders($http->read()));
        //return $content;
//        $ch = curl_init();
//        curl_setopt($ch, CURLOPT_URL, $url);
//        curl_setopt($ch, CURLOPT_HEADER, 1);
//        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
//        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
//        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
//        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
//        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//        curl_setopt($ch, CURLOPT_POST, true);
//        curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
//
//        if (curl_exec($ch) === false)
//            return false;
//
//        $content = curl_multi_getcontent($ch);
//        curl_close($ch);

//        list($header1, $header2, $body) = explode("\r\n\r\n", $content, 3);

        return $responseBody;
    }

    public function setCode($code)
    {
        $this->code = $code;
    }

    public function setUsername($username)
    {
        $this->username = $username;
    }

    public function setPassword($password)
    {
        $this->password = $password;
    }

    public function getUrl()
    {
        if ($this->code)
            return "https://$this->code.erply.com/api/";

        return false;
    }

    public function verifyUser($storeID)
    {
        if($this->session) {
            return true;
        }
        $this->storeID = $storeID;
        $this->getConfig();
        if (!$this->username || !$this->password)
            return false;

        $result = $this->sendRequest(
            'verifyUser',
            array(
                'username' => $this->username
            , 'password' => $this->password
            )
        );
        $response = json_decode($result, true);
        if ($response["status"]["responseStatus"] == "ok" && $sessionKey = $response['records'][0]['sessionKey']) {
            $this->session = $sessionKey;
            $this->userId = $response['records'][0]['employeeID'];
            return true;
        }

        return false;
    }

    public function getConfig() {
        $this->username = Mage::getStoreConfig('eepohs_erply/account/username', $this->storeID);
        $this->password = Mage::getStoreConfig('eepohs_erply/account/password', $this->storeID);
        $this->code = Mage::getStoreConfig('eepohs_erply/account/code', $this->storeID);
    }
}
