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
class Eepohs_Erply_Block_Products extends Mage_Adminhtml_Block_Template
{
    protected function _toHtml()
    {

        $erply = Mage::getModel('Erply/Erply');

        $params = array("pageNo" => 1, "recordsOnPage" => 10);
        $result = $erply->makeRequest('getProducts', $params);

        $output = $result;
        $return = print_r($output, true);
        $out = print_r((Mage::getStoreConfig('eepohs_erply/product/attribute_set', 1)), true);

        return $out . "<br/>" . $erply->getUrl() . "<pre>$return</pre>";
    }
}