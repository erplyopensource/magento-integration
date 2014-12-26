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
class Eepohs_Erply_Model_Category_Import extends Eepohs_Erply_Model_Erply
{

    private $availableSortBy;

    private $defaultSortBy;

    public function _construct()
    {
        $this->availableSortBy = join(',', array_keys(Mage::getSingleton('catalog/config')
            ->getAttributeUsedForSortByArray()));
        $this->defaultSortBy = Mage::getStoreConfig('catalog/frontend/default_sort_by');
        parent::_construct();
    }

    public function addCategories($categories, $parent, $store = 0)
    {

        foreach ($categories as $_category) {
            $id = intval($_category["productGroupID"]);
            $pathIds = array();
            $data = array(
                'category_id' => $id,
                'id' => $id,
                'name' => $_category['name'],
                'is_active' => !empty($_category['showInWebshop']) ? 1 : 0,
                'include_in_menu' => 1,
                'position' => $_category["positionNo"],
                'available_sort_by' => $this->availableSortBy,
                'default_sort_by' => $this->defaultSortBy
            );
            $category = Mage::getModel('catalog/category')
                ->load($id);

            if (!$category->getName()) {

                $category = new Mage_Catalog_Model_Category();
                $category->setId($id);
                $category->setName($_category["name"]);
                $category->setPosition($_category["positionNo"]);
                $category->setIsActive($_category["showInWebshop"]);
                $category->setAttributeSetId($category->getDefaultAttributeSetId());
                $parentCategory = Mage::getModel('catalog/category')->load($parent);
                $childs = $parentCategory->getAllChildren(true);
                $lastCategory = end($childs);
                //                $category->setPath($parentCategory->getPath());
                //                Mage::helper('Erply')->log("Parent path: ".$parentCategory->getPath());
                //
                //                $pathIds = $parentCategory->getPathIds();
                //                if(!in_array($parent, $pathIds)) {
                //                    $pathIds[] = $parent;
                //                }
                //                $category->addData(array(
                //                    'path'=>implode('/', $pathIds)
                //                ));
                //                Mage::helper('Erply')->log("Parent path ids: ");
                //                Mage::helper('Erply')->log($pathIds);
                $category->addData(array(
                    'available_sort_by' => $this->availableSortBy,
                    'default_sort_by' => $this->defaultSortBy,
                    'include_in_menu' => $_category["showInWebshop"]
                ));
                //$category->setParentId($parent);

                try {
                    $validate = $category->validate();
                    if ($validate !== true) {
                        foreach ($validate as $code => $error) {
                            if ($error === true) {
                                Mage::throwException(Mage::helper('catalog')
                                    ->__('Attribute "%s" is required.', $code));
                            } else {
                                Mage::throwException($error);
                            }
                        }
                    }

                    $category->save();
                    Mage::helper('Erply')->log("Category saved:" . $id);
                    $category->move($parent, $lastCategory);
                } catch (Mage_Core_Exception $e) {
                    //                    $this->_fault('data_invalid', $e->getMessage());
                    Mage::throwException($e->getMessage());
                } catch (Exception $e) {
                    //                    $this->_fault('data_invalid', $e->getMessage());
                    Mage::throwException($e->getMessage());
                }
                //                Mage::getModel('catalog/category_api')->create($parent, $data, $store);
            } else {
                Mage::helper('Erply')->log("Updating category: " . $id);
                Mage::getModel('catalog/category_api')->update($id, $data, $store);
            }
            // product does not exist so we will be creating a new one.
            //$category = new Mage_Catalog_Model_Category();
            //            $category->setName($_category["name"]);
            //            $category->setPosition($_category["positionNo"]);
            //            $category->setIsActive($_category["showInWebshop"]);
            //            $parentCategory = Mage::getModel('catalog/category')->load($parent);
            //            $category->setPath($parentCategory->getPath());
            //            $category->save();
            if (is_array($_category["subGroups"]) && count($_category["subGroups"]) > 0) {
                $this->addCategories($_category["subGroups"], $id);
            }
        }
    }
}
