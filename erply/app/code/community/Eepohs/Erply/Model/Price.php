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
class Eepohs_Erply_Model_Price extends Eepohs_Erply_Model_Erply
{

    public function _construct()
    {
        parent::_construct();
    }

    public function updatePrices($rules, $storeId)
    {
        /** @var Eepohs_Erply_Helper_Data $helper */
        $helper = Mage::helper('Erply');

        $helper->log("Running price updates");
        if (!empty($rules)) {
            foreach ($rules as $rule) {
                if ($rule["type"] == 'PRODUCT') {
                    $productId = $rule["id"];
                    $price = $rule["price"];

                    $product = Mage::getModel('catalog/product')
                        ->setStoreId($storeId)
                        ->load($productId);
                    if ($product) {
                        $product->setPrice($price);
                        if ($product->validate()) {
                            $product->save();
                        }
                    }
                }
            }
        }
    }
}
