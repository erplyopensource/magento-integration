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
class Eepohs_Erply_Model_Product_Import extends Eepohs_Erply_Model_Erply
{
    public function getTotalRecords($storeId) {
        $this->verifyUser($storeId);
        $parameters = array('recordsOnPage' => 1, 'pageNo' => 1);
        $results = json_decode($this->sendRequest('getProducts', $parameters), true);
        return $results["status"]["recordsTotal"];
    }

    public function importProducts() {

        $queue = Mage::getModel('Erply/Queue')->loadActive('erply_product_import');
        $params = array();
        if($queue) {
            $runEvery = Mage::getStoreConfig('eepohs_erply/queue/run_every', $queue->getStoreId());
            $loops = $queue->getLoopsPerRun();
            $pageSize = $queue->getRecordsPerRun();
            $recordsLeft = $queue->getTotalRecords() - $pageSize * $queue->getLastPageNo();
            if($queue->getChangedSince()) {
                $params = array('changedSince' => $queue->getChangedSince());
            }
            if( $loops * $pageSize > $recordsLeft ) {
                $loops = ceil( $recordsLeft / $pageSize );
                $queue->setStatus(0);
            } else {
                $thisRunTime = strtotime($queue->getScheduledAt());
                $newRunTime = strtotime('+'.$runEvery.'minute', $thisRunTime);
                $scheduleDateTime = date('Y-m-d H:i:s', $newRunTime);
                Mage::getModel('Erply/Cron')->addCronJob('erply_product_import', $scheduleDateTime);
                $queue->setScheduledAt($scheduleDateTime);
            }
            $loops--;
            $firstPage = $queue->getLastPageNo()+1;

            $queue->setLastPageNo($firstPage+$loops);
            $queue->setUpdatedAt(date('Y-m-d H:i:s', time()));

            $queue->save();
            $this->verifyUser($queue->getStoreId());
            $store = Mage::getModel('core/store')->load($queue->getStoreId());
            for($i = $firstPage; $i <= ($firstPage + $loops);$i++) {

                $parameters = array_merge(array('recordsOnPage' => $pageSize, 'pageNo'=>$i), $params);
                Mage::helper('Erply')->log("Erply request: ");
                Mage::helper('Erply')->log($parameters);
                $result = $this->sendRequest('getProducts', $parameters);
                $return = "";
                Mage::helper('Erply')->log("Erply product import:");
                Mage::helper('Erply')->log($result);
                $output = json_decode($result, true);
                $start = time();
                foreach($output["records"] as $_product) {

                    if($_product["code2"]) {
                        $sku = $_product["code2"];
                    } elseif($_product["code"]) {
                        $sku = $_product["code"];
                    } else {
                        $sku = $_product["code3"];
                    }
                    $product = Mage::getModel('catalog/product')
                        ->loadByAttribute('sku',$sku);

                    if(!$product){
                        $product = Mage::getModel('catalog/product')->load($_product["productID"]);
                        if(!$product->getName()) {
                            $product = new Mage_Catalog_Model_Product();
                            $product->setId($_product["productID"]);
                            Mage::helper('Erply')->log("Creating new product: ".$_product["productID"]);
                        } else {
                            Mage::helper('Erply')->log("Editing old product: ".$_product["productID"]);
                        }
                    }
                    // product does not exist so we will be creating a new one.
                    $product->setIsMassupdate(true);
                    $product->setExcludeUrlRewrite(true);


                    $product->setTypeId('simple');
                    $product->setWeight(1.0000);
                    $product->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH);
                    $product->setStatus(1);
                    $product->setSku($sku);
                    $product->setTaxClassId(0);
                    //                    if (Mage::app()->isSingleStoreMode()) {
                    //                        $product->setWebsiteIds(array(Mage::app()->getStore(true)->getWebsiteId()));
                    //                    }
                    //                    else {
                    //                        $product->setWebsiteIds(array(Mage::getModel('core/store')->load($queue->getStoreId())->getWebsiteId()));
                    //                    }

                    //                    $product->setStoreIDs(array($queue->getStoreId()));  // your store ids
                    //                    $product->setStockData(array(
                    //                        'is_in_stock' => 1,
                    //                        'qty' => 99999,
                    //                        'manage_stock' => 0,
                    //                    ));


                    // set the rest of the product information here that can be set on either new/update
                    $product->setAttributeSetId(4); // the product attribute set to use
                    $product->setName($_product["name"]);
                    $product->setCategoryIds(array($_product["groupID"])); // array of categories it will relate to
                    if (Mage::app()->isSingleStoreMode()) {
                        $product->setWebsiteIds(array(Mage::app()->getStore($queue->getStoreId())->getWebsiteId()));
                    }
                    else {
                        $product->setWebsiteIds(array($store->getWebsiteId()));
                    }
                    $product->setDescription($_product["longdesc"]);
                    $product->setShortDescription($_product["description"]);
                    $product->setPrice($_product["price"]);

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
                    $product->save();
                    Mage::helper('Erply')->log("Added: ".$product->getSku());
                }
                unset($output);
            }
        }
    }
}
