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
class Eepohs_Erply_Model_Inventory extends Mage_Core_Model_Abstract
{

    public function _construct()
    {
        parent::_construct();
    }

    public function test() {
        echo "Kalanaine!";
    }

    public function updatePrices()
    {

    }

    public function updateInventory($products, $storeId)
    {
        Mage::helper('Erply')->log("Running Erply own updateInventory");
        foreach ($products as $_product) {

            if ($_product["code"]) {
                $sku = $_product["code"];
            } elseif ($_product["code2"]) {
                $sku = $_product["code2"];
            } else {
                $sku = $_product["code3"];
            }

            $product = Mage::getModel('catalog/product')
                ->loadByAttribute('sku', $sku);

            if (!$product) {
                $product = Mage::getModel('catalog/product')->load($_product["productID"]);
                if (!$product->getName()) {
                    return false;
                } else {
                    Mage::helper('Erply')->log("Editing old product: " . $_product["productID"]);
                }
            }
            /**
             * Update stock
             */
            $qty = $this->getProductQuantity($_product);
            $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product->getId());
            if (!$stockItem->getId()) {
                $stockItem->setData('product_id', $product->getId());
                $stockItem->setData('stock_id', 1);
            }

            if ($stockItem->getQty() != $qty) {
                $stockItem->setData('qty', $qty);
                $stockItem->setData('is_in_stock', $qty ? 1 : 0);
                $stockItem->save();
            }
            /**
             * Update price
             */
//            $product->setPrice($_product["price"]);
            $product->save();
        }
    }

    private function getProductQuantity($product)
    {
        $quantity = 0;

        foreach ($product['warehouses'] as $warehouse) {
            $quantity += $warehouse['free'];
        }

        return $quantity;
    }
}
