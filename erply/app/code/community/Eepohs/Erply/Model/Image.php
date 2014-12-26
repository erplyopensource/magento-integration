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
class Eepohs_Erply_Model_Image extends Mage_Core_Model_Abstract
{

    public function _construct()
    {
        parent::_construct();
    }

    public function updateImages($products, $storeId)
    {
        Mage::helper('Erply')->log("Running Erply Image Import");
        foreach ($products as $_product) {

            if ($_product["code2"]) {
                $sku = $_product["code2"];
            } elseif ($_product["code"]) {
                $sku = $_product["code"];
            } else {
                $sku = $_product["code3"];
            }

            $product = Mage::getModel('catalog/product')
                ->loadByAttribute('sku', $sku);

            if (!$product) {
                $product = Mage::getModel('catalog/product')
                    ->load($_product["productID"]);
                if (!$product->getName()) {
                    continue;
                } else {
                    Mage::helper('Erply')
                        ->log("Editing old product: " . $_product["productID"]);
                }
            }
            if (!empty($_product["images"])) {
                $pos = 1;
                foreach ($_product["images"] as $image) {
                    $url = $image["largeURL"];
                    $image_type = substr(strrchr($url, "."), 1);
                    //                    $filename = md5($url.$sku).".".$image_type;

                    //                    if(!is_dir(Mage::getBaseDir('media').DS.'erply_import')) {
                    //                        mkdir(Mage::getBaseDir('media').DS.'erply_import');
                    //                    }
                    //$filepath = Mage::getBaseDir('media').DS.'erply_import'.DS.$filename;
                    //file_put_contents($filepath, file_get_contents(trim($url)));
                    //                    $mediaAttribute = array('thumbnail', 'small_image', 'image');
                    //
                    $mimeType = getimagesize($url);
                    $imageData = array(
                        'file' => array(
                            'name' => $image['name'],
                            'content' => base64_encode(file_get_contents($url)),
                            'mime' => $mimeType['mime']
                        ),
                        'label' => $image['name'],
                        'position' => $pos,
                        'exclude' => 0
                    );
                    if ($pos == 1) {
                        $imageData['types'] = array('image', 'small_image', 'thumbnail');
                    }

                    //                    $product->addImageToMediaGallery($filepath, $mediaAttribute, false, false);
                    Mage::getModel('catalog/product_attribute_media_api')
                        ->create($_product["productID"], $imageData);
                    $pos++;
                }
            }

            $product->save();
        }
    }
}
