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
class Eepohs_Erply_Model_Import extends Eepohs_Erply_Model_Erply
{

    private $type = array(
        'product_update' => 'getProducts',
        'inventory_update' => 'getProductStock',
        'product_import' => 'getProducts',
        'category_import' => 'getProductGroups',
        'category_update' => 'getProductGroups',
        'image_import' => 'getProducts',
        'price_update' => 'getPriceLists');

    public function getTotalRecords($storeId, $importType, $params = array())
    {
        /** @var Eepohs_Erply_Helper_Data $helper */
        $helper = Mage::helper('Erply');

        $parameters = array_merge(array(
            'recordsOnPage' => 1,
            'pageNo' => 1,
            'displayedInWebshop' => 1,
            'active' => 1,
        ), $params);
        if ($importType == 'price_update') {
            $parameters["pricelistID"] = Mage::getStoreConfig('eepohs_erply/product/pricelist', $storeId);
        } elseif ($importType == 'inventory_update') {
            $parameters["warehouseID"] = Mage::getStoreConfig('eepohs_erply/product/warehouse', $storeId);
        }
        $results = $this->makeRequest($this->type[$importType], $parameters);
        $helper->log($this->type[$importType]);
        $helper->log($parameters);
        $helper->log($results["status"]);
        if ($importType == 'price_update') {
            $return = count($results["records"][0]["pricelistRules"]);
        } else {
            $return = $results["status"]["recordsTotal"];
        }

        return $return;
    }
}
