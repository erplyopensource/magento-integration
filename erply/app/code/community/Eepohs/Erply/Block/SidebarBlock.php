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
class Eepohs_Erply_Block_SidebarBlock extends Mage_Adminhtml_Block_Widget_Tabs
{

    public function __construct()
    {
        parent::__construct();
        $this->setTitle(Mage::helper('Erply')->__('ERPLY'));
    }

    protected function _beforeToHtml()
    {
//        $this->addTab('erply_products', array(
//            'label' => Mage::helper('Erply')->__('Products')
//            , 'title' => Mage::helper('Erply')->__('Products')
//            , 'content' => $this
//		->getLayout()
//		->createBlock('Erply/Products')
//		->toHtml()
//            , 'active' => TRUE
//        ));
//
//        $this->addTab('erply_orders', array(
//            'label' => Mage::helper('Erply')->__('Orders')
//            , 'title' => Mage::helper('Erply')->__('Orders')
//            , 'content' => 'This is where orders will show up later on'
//            , 'active' => TRUE
//        ));
        $this->addTab('erply_import', array(
            'label' => Mage::helper('Erply')->__('Import')
            , 'title' => Mage::helper('Erply')->__('Import')
            , 'content' => $this
                ->getLayout()
                ->createBlock('Erply/Import','
                eepohs_erply_import',
                array('template' => 'erply/import.phtml'))
                ->toHtml(),
            'active' => TRUE
        ));

        parent::_beforeToHtml();
    }

}