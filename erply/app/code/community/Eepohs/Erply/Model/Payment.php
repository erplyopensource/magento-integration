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
/**
 * Created by Rauno Väli
 * Date: 27.03.12
 * Time: 10:25
 */
class Eepohs_Erply_Model_Payment extends Mage_Core_Model_Abstract
{

    private $_storeId;
    protected static $_defaultInvoiceState = 'PENDING';
    protected static $_invoiceStatesAry = array(
        'PENDING' => 'PENDING',
        'PROCESSING' => 'READY'
    );

    public function _construct()
    {
        parent::_construct();
    }

    public function preparePayment($data, $storeId)
    {

        $this->_storeId = $storeId;

        $order = Mage::getModel('sales/order')->loadByIncrementId($data["invoiceNo"]);

        if(!$order->getIncrementId()) return false;

        $payment = $order->getPayment();

        if(!$payment) return false;

        $billing = $order->getBillingAddress();

        if(!$billing) return false;

        Mage::helper('Erply')->log("Starting to prepare Payment");

        $this->_data = array();
        $erpAttributes = array();

        $this->_data["customerID"] = $data["customerID"];
        $this->_data["documentID"] = $data["documentID"];
        $this->_data["type"] = "CARD";
        $this->_data["cardType"] = "ONLINE";
        $this->_data["sum"] = $order->getGrandTotal();
        $this->_data["currencyCode"] = "USD";
        $this->_data["cardHolder"] = $billing->getFirstname()." ".$billing->getLastname();

        return $this->_data;
    }

    protected function getVatRates()
    {
        $erply = Mage::getModel('Erply/Erply');
        $erply->verifyUser($this->_storeId);
        $vatRates = $erply->sendRequest('getVatRates');
        $vatRates = json_decode($vatRates, true);
        if ( $vatRates["status"]["responseStatus"] == "ok" ) {
            return $vatRates["records"];
        } else {
            return false;
        }
    }

    /*
     * Function converts input associative array data to plain array with one input array value as key
     * and array of values as value
     * @param $array|array - array to convert
     * @param $key|string - the name of $array value to become a key
     * @param $value|array - an array of name values of $array to become a value
     * @return $newarray|array
     */

    protected function toKeyValueArray($array, $key, $valueArr)
    {
        $newarray = array();
        foreach ( $array as $item ) {
            foreach ( $valueArr as $value ) {
                if ( count($valueArr) == 1 ) {
                    $newarray[$item[$key]] = $item[$value];
                } else {
                    $newarray[$item[$key]][$value] = $item[$value];
                }
            }
        }
        return $newarray;
    }

}
