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
class Eepohs_Erply_Block_System_Backend_Config_Attributes extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    protected $magentoAttributes;

    public function __construct()
    {
        $this->addColumn('erply_attribute', array(
            'label' => Mage::helper('Erply')->__('Erply Attribute'),
            'size'  => 28,
            'type'  => 'text'
        ));
        $this->addColumn('magento_attribute', array(
            'label' => Mage::helper('Erply')->__('Magento Attribute'),
            'size'  => 28,
            'type'  => 'select'
        ));
        $this->_addAfter = false;
        $this->_addButtonLabel = Mage::helper('Erply')->__('Add new mapping');

        parent::__construct();
        $this->setTemplate('erply/system/config/field/attributes.phtml');
    }
    /**
     * Add a column to array-grid
     *
     * @param string $name
     * @param array $params
     */
    public function addColumn($name, $params)
    {
        $this->_columns[$name] = array(
            'label'     => empty($params['label']) ? 'Column' : $params['label'],
            'size'      => empty($params['size'])  ? false    : $params['size'],
            'style'     => empty($params['style'])  ? null    : $params['style'],
            'class'     => empty($params['class'])  ? null    : $params['class'],
            'type'     => empty($params['type'])  ? null    : $params['type'],
            'renderer'  => false,
        );
        if ((!empty($params['renderer'])) && ($params['renderer'] instanceof Mage_Core_Block_Abstract)) {
            $this->_columns[$name]['renderer'] = $params['renderer'];
        }
    }
    /**
     * Render array cell for prototypeJS template
     *
     * @param string $columnName
     * @return string
     */
    protected function _renderCellTemplate($columnName)
    {
        if (empty($this->_columns[$columnName])) {
            throw new Exception('Wrong column name specified.');
        }
        $column     = $this->_columns[$columnName];
        $inputName  = $this->getElement()->getName() . '[#{_id}][' . $columnName . ']';

        if ($column['renderer']) {
            return $column['renderer']->setInputName($inputName)->setColumnName($columnName)->setColumn($column)
                ->toHtml();
        }
        if(isset($column['type']) && $column['type'] == 'select' && $columnName == 'magento_attribute')
        {
            $rendered = '<select name="'.$inputName.'">';
            $attributeSet = Mage::getStoreConfig('eepohs_erply/product/attribute_set');
            $attributes = Mage::getModel('catalog/product_attribute_api')->items($attributeSet);
            foreach($attributes as $attribute) {
                $rendered .= '<option value="'.$attribute["code"].'">'.$attribute["code"].'</option>';
            }
            $rendered .= '</select>';
            return $rendered;
        }

        return '<input type="text" name="' . $inputName . '" value="#{' . $columnName . '}" ' .
            ($column['size'] ? 'size="' . $column['size'] . '"' : '') . ' class="' .
            (isset($column['class']) ? $column['class'] : 'input-text') . '"'.
            (isset($column['style']) ? ' style="'.$column['style'] . '"' : '') . '/>';
    }
}
?>