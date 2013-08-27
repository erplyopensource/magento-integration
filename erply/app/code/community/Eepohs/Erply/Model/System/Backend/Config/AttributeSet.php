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
class Eepohs_Erply_Model_System_Backend_Config_AttributeSet {
    public function toOptionArray() {
        $attributeSets = Mage::getModel('catalog/product_attribute_set_api')->items();
        $return = array();
        foreach($attributeSets as $set) {
            $return[] = array('value' => $set["set_id"], 'label' =>$set["name"]);
        }
        return $return;
    }
}