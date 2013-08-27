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

class Eepohs_Erply_Model_Customer extends Mage_Core_Model_Abstract
{
    public function getCustomerExists($email, $storeId) {
        if($email) {
//            $this->verifyUser($storeId);
            $c = Mage::getModel('Erply/Erply');
            $c->verifyUser($storeId);
            $params = array(
                'searchName'  =>  $email
            );
            $response = $c->sendRequest('getCustomers', $params);
            $response = json_decode($response, true);
            if(count($response["records"]) > 0 && $response["records"][0]["customerID"] > 0) {
                Mage::helper('Erply')->log("Erply - Found existing customer with ID:".$response["records"][0]["customerID"]);
                return $response["records"][0]["customerID"];
            }
            Mage::helper('Erply')->log("Erply - Couldn't find existing customer");
            return false;

        }
    }

    public function sendCustomer($customer, $storeId) {
        $c = Mage::getModel('Erply/Erply');
        $c->verifyUser($storeId);
        $params = array();
        $customerID = $this->getCustomerExists($customer->getEmail(),$storeId);
        if($customer instanceof Mage_Customer_Model_Customer) {
        $params = array(
            'firstName' => $customer->getFirstname(),
            'lastName'  =>  $customer->getLastname(),
            'email'     =>  $customer->getEmail()
        );
        } else {
            $params = $customer;
        }
        if($customer->getData('dob')) {
            $params["birthday"] = $customer->getData('dob');
        }
        if($customerID) {
            $params["customerID"] = $customerID;
            Mage::helper('Erply')->log("Erply - Updating existing customer");
        } else {
            Mage::helper('Erply')->log("Erply - Creating new customer");
        }
        $customerData = $c->sendRequest('saveCustomer', $params);
        $customerData = json_decode($customerData, true);
        if($customerData["status"]["responseStatus"] == "ok") {

            Mage::helper('Erply')->log("Erply - Customer saved!");
            return $customerData["records"][0]["customerID"];
        } else {
            Mage::helper('Erply')->log("Erply - Couldn't save customer data".print_r($customerData,true));
            return false;
        }
    }

    public function addNewCustomer($customerId, $storeId) {
        $customer = Mage::getModel('customer/customer')->load($customerId);
        return $this->sendCustomer($customer, $storeId);
    }
}
