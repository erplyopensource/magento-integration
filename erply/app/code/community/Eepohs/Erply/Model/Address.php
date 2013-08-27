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
//        parent::_construct();
    }

    protected function getExistingAddress($customerId, $typeId, $storeId) {

        $params = array(
            'ownerID'   =>  $customerId,
            'typeID'    =>  $typeId
        );
        $reponse = $this->sendRequest('getAddresses', $params);
        $reponse = json_decode($reponse, true);
        if(isset($reponse["records"]) && count($reponse["records"]) > 0) {
            return $reponse["records"][0]["addressID"];
        } else {
            Mage::helper('Erply')->log("Erply - Address wasn't found:".print_r($reponse, true));
            return false;
        }
    }

    public function saveCustomerAddress($customerId, $typeId, $data, $storeId) {

        $this->verifyUser($storeId);

        $params = array(
            'ownerID'   =>  $customerId,
            'typeID'    =>  $typeId,
            'street'    =>  $data["street"],
            'city'      =>  $data["city"],
            'postalCode'  =>  $data["postcode"],
            'state'     =>  $data["region"],
            'country'   =>  $data["country_id"]

        );

        if($addressId = $this->getExistingAddress($customerId, $typeId, $storeId)) {
            $params["addressID"] = $addressId;
        }
        Mage::helper('Erply')->log("Magento - Sending address data to Erply: ".print_r($params, true));

        $reponse = $this->sendRequest('saveAddress', $params);
        $reponse = json_decode($reponse, true);
        Mage::helper('Erply')->log("Saving customer address:". var_export($params, true));
        if(isset($reponse["records"]) && count($reponse["records"]) > 0) {
            return $reponse["records"][0]["addressID"];
        } else {
            Mage::helper('Erply')->log("Coun't save address for customer:".print_r($reponse,true));
            return false;
        }
    }
}