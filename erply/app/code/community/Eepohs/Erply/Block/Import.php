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
class Eepohs_Erply_Block_Import extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('erply/import.phtml');
        $this->setTitle('Erply Import Scheduling');
        $this->_removeButton('save');
    }
    public function getSaveButtonHtml()
    {
        return $this->getChildHtml('save_button');
    }

    public function getSaveUrl()
    {
        return $this->getUrl('*/*/scheduleImport', array('_current'=>true));
    }
    public function fromHtml()
    {
        $form = new Varien_Data_Form(array(
            'id'        => 'import_form',
            'action'    => $this->getUrl('*/*/scheduleProductImport'),
            'method'    => 'post'
        ));
        $fieldset = $form->addFieldset('import_fieldset', array('legend' => 'Product Import'));
        $fieldset->addField('import_type', 'select', array(
            'name'      => 'import_type',
            'title'     => Mage::helper('Erply')->__('Import Type'),
            'label'     => Mage::helper('Erply')->__('Import Type'),
            'required'  => true,
            'values'    => array(
                'product_import'    => Mage::helper('Erply')->__('Product Import'),
                'category_import'   => Mage::helper('Erply')->__('Category Import'),
                'inventory_update'  => Mage::helper('Erply')->__('Inventory Update'),
                'price_update'  => Mage::helper('Erply')->__('Price Update'),
                'image_import'      => Mage::helper('Erply')->__('Image Import'),
            )
        ));
        $fieldset->addField('store_id', 'select', array(
            'name'      => 'store_id',
            'title'     => Mage::helper('Erply')->__('Store'),
            'label'     => Mage::helper('Erply')->__('Store'),
            'required'  => true,
            'values'    => Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm(false, true)
        ));
//        $fieldset->addField('schedule_date', 'date', array(
//            'name'      => 'schedule_date',
//            'title'     => Mage::helper('Erply')->__('Schedule Date'),
//            'label'     => Mage::helper('Erply')->__('Schedule Date'),
//            'required'  => true,
//            'format'    => '%Y-%m-%d',
//            'default'   => "NOW()",
//            'image'     => $this->getSkinUrl('images/grid-cal.gif'),
//        ));
//        $fieldset->addField('schedule_time', 'time', array(
//            'name'      => 'schedule_time',
//            'title'     => Mage::helper('Erply')->__('Schedule Time'),
//            'label'     => Mage::helper('Erply')->__('Schedule Time'),
//            'required'  => true,
//        ));
        $fieldset->addField('schedule_import', 'submit', array(
            'label'     => Mage::helper('Erply')->__('Schedule Import'),
            'required'  => true,
            'value'  => 'Schedule Import',
            'after_element_html' => '',
            'tabindex' => 1
        ));
	    return $form->toHtml();
    }
//
//
//    public function getButtons($fromTo)
//    {
//        $buttons = array();
//        switch($fromTo){
//            case 'e2m':
//                $buttons = array(
//                    'sync_products'		=> array(
//                        'label'		=> Mage::helper('adminhtml')->__('Inventory (Products, Categories etc.)'),
//                        'buttons'	=> array(
//                            array(
//                                'name'      => 'export_products',
//                                'action'    => Mage::helper('adminhtml')->__('Export')
//                            )
//                        , array(
//                                'name'      => 'import_products',
//                                'action'    => Mage::helper('adminhtml')->__('Import')
//                            )
//                            //							, array(
////								'name'      => 'sync_products',
////								'action'    => Mage::helper('adminhtml')->__('Synchronize')
////							)
//                        )
//                    )
//                , 'sync_stock_qty'=> array(
//                        'label'		=> Mage::helper('adminhtml')->__('Stock quantities'),
//                        'buttons'	=> array(
//                            array(
//                                'name'      => 'import_stock',
//                                'action'    => Mage::helper('adminhtml')->__('Import')
//                            )
//                        )
//                    )
//                , 'export_customers'=> array(
//                        'label'		=> Mage::helper('adminhtml')->__('Customers'),
//                        'buttons'	=> array(
//                            array(
//                                'name'      => 'export_customers',
//                                'action'    => Mage::helper('adminhtml')->__('Export')
//                            )
//                        )
//                    )
//                , 'export_sales'=> array(
//                        'label'		=> Mage::helper('adminhtml')->__('Sales Documents'),
//                        'buttons'	=> array(
//                            array(
//                                'name'      => 'export_sales',
//                                'action'    => Mage::helper('adminhtml')->__('Export')
//                            )
//                        )
//                    )
//                );
//                break;
//            default:
//                break;
//        }
//        return $buttons;
//    }
}