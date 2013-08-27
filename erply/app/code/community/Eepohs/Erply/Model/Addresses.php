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

class Eepohs_Erply_Model_Addresses extends Eepohs_Erply_Model_Erply
{
	private $attrName;
	private $attrType;
	private $erpTypeID;

    public function _construct()
    {
		$this->attrName = 'magentoAddressId';
		$this->attrType = 'int';
		$this->erpTypeID = 3;// registered address
        parent::_construct();
    }

	public function syncAll($magCustomerId, $erpCustomerId){
		$magAddresses = $this->magCustomerAddressList($magCustomerId);
		if($magAddresses){
			foreach($magAddresses as $magAddress){
				$this->syncAddress($erpCustomerId, $magAddress);
			}
		}
	}

	public function syncAddress($erpCustomerId, $magAddress){
		$erpAddressId = null;
		$inputParams = null;
		$erpAddress = null;
		// if address is synchronised
		$erpAddress = $this->erpAddressExists($erpCustomerId, $magAddress);

		if($erpAddress){
			$erpAddressId = $erpAddress['addressID'];
			// erply getAddresses has 'added' and 'lastModified' response parameters
			$modifiedTime = null;
			if(!empty($erpAddress['lastModified'])){
				$modifiedTime = $erpAddress['lastModified'];
			}else{
				$modifiedTime = $erpAddress['added'];
			}
			if($modifiedTime && $modifiedTime < strtotime($magAddress['updated_at'])){
				//map address data to erply parameters
				$inputParams = $this->m2eMapAddress($erpCustomerId, $magAddress, $erpAddress);
			}
		}else{
			$inputParams = $this->m2eMapAddress($erpCustomerId, $magAddress);
		}
		if($inputParams){
			$erpAddressId = $this->erply->saveAddress($inputParams);
			if($erpAddressId){
				return $erpAddressId;
			}
		}
		return false;
	}

	private function m2eMapAddress($erpCustomerId, $magAddress, $erpAddress = false){
		$erpData = array();
		$erpAttributes = array();
		// if address was already synchronized then update data
		if($erpAddress)
		{
			$erpData = $erpAddress;
			if(isset($erpData['attributes'])){
				$erpAttributes = $erpData['attributes'];
				unset($erpData['attributes']);
			}
		}
		else
		{
			$erpData['ownerID'] = $erpCustomerId;
			$erpData['typeID'] = Mage::getStoreConfig('erply/sync_config/erply_billing_address_type_id');
			// create magento id attribute for new erply address
			if(isset($magAddress['customer_address_id']))
			{
				$magAddressAttribute = array(
					'attributeName' => $this->attrName,
					'attributeType' => $this->attrType,
					'attributeValue' => $magAddress['customer_address_id']
				);
				$erpAttributes = $this->erpSetAttribute($magAddressAttribute, $erpAttributes);
			}
		}

		$erpAttributes = $this->erpConvertAttributes($erpAttributes);

		if(isset($magAddress['street'])){
			$erpData['street'] = $magAddress['street'];
		}
		if(isset($magAddress['city'])){
			$erpData['city'] = $magAddress['city'];
		}
		if(isset($magAddress['postcode'])){
			$erpData['postalCode'] = $magAddress['postcode'];
		}
		if(isset($magAddress['region'])){
			$erpData['state'] = $magAddress['region'];
		}
		if(isset($magAddress['country_id'])){
			$erpData['country'] = $magAddress['country_id'];
		}

		$erpData = array_merge($erpData, $erpAttributes);
		return $erpData;
	}

	public function erpAddressExists($erpCustomerId, $magAddress){
		if(isset($magAddress['customer_address_id'])){
			$inputParams = array(
				'ownerID' => $erpCustomerId,
				'searchAttributeName' => $this->attrName,
				'searchAttributeValue' => $magAddress['customer_address_id']
			);
		}else{
			$inputParams = array(
				'ownerID' => $erpCustomerId
			);
		}

		$magAddressMd5 = md5(
			str_replace(' ', '', $magAddress['street']) .
			str_replace(' ', '', $magAddress['city']) .
			str_replace(' ', '', $magAddress['postcode']) .
			str_replace(' ', '', $magAddress['country_id'])
		);

		$erpAddresses = $this->erply->getAddresses($inputParams);
		foreach($erpAddresses as $erpAddress){
			if(isset($magAddress['customer_address_id'])){
				if(isset($erpAddress['attributes'])){
					$magAddressId = $this->erpGetAttribute($erpAddress['attributes'], $this->attrName);
					if($magAddressId && $magAddressId == $magAddress['customer_address_id']){
						return $erpAddress;
					}
				}
			}

			// if erply address not found then check it md5() with magento
			$erpAddressMd5 = md5(
				str_replace(' ', '', $erpAddress['street']) .
				str_replace(' ', '', $erpAddress['city']) .
				str_replace(' ', '', $erpAddress['postalCode']) .
				str_replace(' ', '', $erpAddress['country'])
			);
			if($erpAddressMd5 == $magAddressMd5){
				return $erpAddress;
			}
		}
		return false;
	}

	public function magCustomerAddressList($customerId)
	{
		try
		{
//			$filters = array(
//				array(
//					'customerId' => array('eq' => $customerId)
//				)
//			);
//			$addresses = $this->mapi->call($this->sessid, 'customer_address.list', $filters);
			$addresses = Mage::getModel('customer/address_api')->items($customerId);
			return $addresses;
		}
		catch(Exception $e)
		{
			return false;
		}
	}

	/**
	 * Get Magento Customer Address
	 *
	 * @param int $addressId
	 * @return array
	 */
	public function magCustomerAddressInfo($addressId)
	{
		try
		{
//			$filters = array(
//				array(
//					'addressId' => array('eq' => $addressId)
//				)
//			);
//			$address = $this->mapi->call($this->sessid, 'customer_address.info', $filters);
			$address = Mage::getModel('customer/address_api')->info($addressId);
			return $address;
		}
		catch(Exception $e)
		{
			return false;
		}
	}
}