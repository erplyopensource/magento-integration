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
class Eepohs_Erply_Model_Address extends Eepohs_Erply_Model_Erply
{

    private $attrName;

    private $attrType;

    private $erpTypeID;

    public function _construct()
    {
        //		$this->attrName = 'magentoAddressId';
        //		$this->attrType = 'int';
        //		$this->erpTypeID = 3;// registered address
    }

    public function saveCustomerAddress($customerId, $typeId, $data)
    {

        /** @var Eepohs_Erply_Helper_Data $helper */
        $helper = Mage::helper('Erply');

        $params = array(
            'ownerID' => $customerId,
            'typeID' => $typeId,
            'street' => $data["street"],
            'city' => $data["city"],
            'postalCode' => $data["postcode"],
            'state' => $data["region"],
            'country' => $data["country_id"]

        );

        if ($addressId = $this->getExistingAddress($customerId, $typeId)) {
            $params["addressID"] = $addressId;
        }
        $helper->log("Magento - Sending address data to Erply: " . print_r($params, true));

        $response = $this->makeRequest('saveAddress', $params);
        $helper->log("Saving customer address:" . var_export($params, true));
        if (isset($response["records"]) && count($response["records"]) > 0) {
            return $response["records"][0]["addressID"];
        } else {
            $helper->log("Coun't save address for customer:" . print_r($response, true));

            return false;
        }
    }

    protected function getExistingAddress($customerId, $typeId)
    {

        /** @var Eepohs_Erply_Helper_Data $helper */
        $helper = Mage::helper('Erply');

        $params = array(
            'ownerID' => $customerId,
            'typeID' => $typeId
        );
        $response = $this->makeRequest('getAddresses', $params);

        if (isset($response["records"]) && count($response["records"]) > 0) {
            return $response["records"][0]["addressID"];
        } else {
            $helper->log("Erply - Address wasn't found:" . print_r($response, true));

            return false;
        }
    }
}