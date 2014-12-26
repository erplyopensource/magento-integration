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
class Eepohs_Erply_Model_Customer extends Eepohs_Erply_Model_Erply
{

    public function addNewCustomer($customerId)
    {
        $customer = Mage::getModel('customer/customer')->load($customerId);

        return $this->sendCustomer($customer);
    }

    public function sendCustomer($customer)
    {

        /** @var Eepohs_Erply_Helper_Data $helper */
        $helper = Mage::helper('Erply');

        $customerID = $this->getCustomerExists($customer['email']);

        if ($customer instanceof Mage_Customer_Model_Customer) {
            $params = array(
                'firstName' => $customer->getFirstname(),
                'lastName' => $customer->getLastname(),
                'email' => $customer->getEmail(),
                'birthday' => $customer->getData('dob')
            );
        } else {
            $params = $customer;
        }

        if ($customerID) {
            $params["customerID"] = $customerID;
            $helper->log("Erply - Updating existing customer");
        } else {
            $helper->log("Erply - Creating new customer");
        }
        $response = $this->makeRequest('saveCustomer', $params);

        if ($response["status"]["responseStatus"] == "ok") {

            $helper->log("Erply - Customer saved!");

            return $response["records"][0]["customerID"];
        } else {
            $helper->log("Erply - Couldn't save customer data");
            $helper->log(print_r($response, true));

            return false;
        }
    }

    public function getCustomerExists($email)
    {
        if (!empty($email)) {
            /** @var Eepohs_Erply_Helper_Data $helper */
            $helper = Mage::helper('Erply');

            $params = array(
                'searchName' => $email
            );
            $response = $this->makeRequest('getCustomers', $params);

            if (count($response["records"]) > 0
                && $response["records"][0]["customerID"] > 0
            ) {
                $helper->log("Erply - Found existing customer with ID:" . $response["records"][0]["customerID"]);

                return $response["records"][0]["customerID"];
            }
            $helper->log("Erply - Couldn't find existing customer");

            return false;
        }

        return false;
    }
}
