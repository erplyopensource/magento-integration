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
class Eepohs_Erply_Model_Product extends Eepohs_Erply_Model_Erply
{
    public function findProduct($sku)
    {

        $params = array(
            'searchName' => $sku
        );
        $product = $this->makeRequest('getProducts', $params);

        if ($product["status"]["responseStatus"] == "ok" && count($product["records"]) > 0) {
            foreach ($product["records"] as $_product) {
                if ($_product["code2"]) {
                    $code = $_product["code2"];
                } elseif ($_product["code"]) {
                    $code = $_product["code"];
                } else {
                    $code = $_product["code3"];
                }
                if ($code == $sku) {
                    return $_product;
                }
            }
        }
    }

    public function importProducts($products, $storeId, $store)
    {
        /** @var Eepohs_Erply_Helper_Data $helper */
        $helper = Mage::helper('Erply');

        Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
        if (!empty($products)) {
            foreach ($products as $_product) {

                $update = false;

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
                    $product = Mage::getModel('catalog/product')
                        ->load($_product["productID"]);
                    if (!$product->getName()) {
                        $product = new Mage_Catalog_Model_Product();
                        $product->setId($_product["productID"]);
                        $helper->log("Creating new product: " . $_product["productID"]);
                    } else {
                        $helper->log("Editing old product: " . $_product["productID"]);
                        $update = true;
                    }
                } else {
                    $update = true;
                }
                if ($_product["displayedInWebshop"] == 0) {
                    if ($update) {
                        try {
                            $product->delete();
                            $helper->log("Delete existing product which should be in webshop id: " . $_product["productID"] . " - sku: " . $sku);
                        } catch (Exception $e) {
                            $helper->log("Failed to delete product with message: " . $e->getMessage());
                        }
                    }
                    continue;
                }

                $product->setStoreId($storeId);

                // product does not exist so we will be creating a new one.
                $product->setIsMassupdate(true);
                $product->setExcludeUrlRewrite(true);

                $product->setTypeId('simple');
                $product->setWeight(1.0000);
                $product->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH);
                $product->setStatus(1);
                $product->setSku($sku);
                $product->setTaxClassId(0);

                // set the rest of the product information here that can be set on either new/update
                if (!$update) {
                    $product->setAttributeSetId((int)Mage::getStoreConfig('eepohs_erply/product/attribute_set', $storeId)); // the product attribute set to use
                }
                $product->setName($_product["name"]);
                $category = Mage::getModel('catalog/category')
                    ->load($_product["groupID"]);
                if ($category->getName()) {
                    $product->setCategoryIds(array($_product["groupID"])); // array of categories it will relate to
                }
                if (Mage::app()->isSingleStoreMode()) {
                    $product->setWebsiteIds(array(Mage::app()
                        ->getStore(true)
                        ->getWebsiteId()));
                } else {
                    $product->setWebsiteIds(array($store->getWebsiteId()));
                }

                $product->setBatchPrices(array());
                $product->setStockPriorities(array());
                //$product->setPrice($_product["price"]);

                // set the product images as such
                // $image is a full path to the image. I found it to only work when I put all the images I wanted to import into the {magento_path}/media/catalog/products - I just created my own folder called import and it read from those images on import.
                //        $image = '/path/to/magento/media/catalog/products/import/image.jpg';
                //
                //        $product->setMediaGallery (array('images'=>array (), 'values'=>array ()));
                //        $product->addImageToMediaGallery ($image, array ('image'), false, false);
                //        $product->addImageToMediaGallery ($image, array ('small_image'), false, false);
                //        $product->addImageToMediaGallery ($image, array ('thumbnail'), false, false);

                // setting custom attributes. for example for a custom attribute called special_attribute
                // special_attribute will be used on all examples below for the various attribute types
                //$product->setSpecialAttribute('value here');

                // setting a Yes/No Attribute
                //        $product->setSpecialField(1);

                // setting a Selection Attribute
                //$product->setSpecialAttribute($idOfAttributeOption); //specify the ID of the attribute option, eg you creteated an option called Blue in special_attribute it was assigned an ID of some number. Use that number.

                // setting a Mutli-Selection Attribute
                //$data['special_attribute'] = '101 , 102 , 103'; // coma separated string of option IDs. As ID , ID (mind the spaces before and after coma, it worked for me like that)
                //        $product->setData($data);
                if (isset($_product["attributes"])) {
                    $erplyAttributes = $_product["attributes"];
                    $mapping = unserialize(Mage::getStoreConfig('eepohs_erply/product/attributes', $storeId));
                    if (!empty($erplyAttributes) && !empty($mapping)) {
                        $mappings = array();
                        foreach ($mapping as $map) {
                            $mappings[$map["erply_attribute"]] = $map["magento_attribute"];
                        }
                        foreach ($erplyAttributes as $attribute) {
                            if (in_array($attribute["attributeName"], array_keys($mappings))) {
                                if ($attribute["attributeValue"]) {
                                    $product->setData($mappings[$attribute["attributeName"]], $attribute["attributeValue"]);
                                }
                            }
                        }
                    }
                }
                $product->save();
                $helper->log("Added: " . $product->getSku());
            }
        }
    }
}
