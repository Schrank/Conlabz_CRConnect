<?php
class Conlabz_CrConnect_Block_GroupsApis extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    protected $magentoOptions;

    public function __construct()
    {
        $this->addColumn('magento', array(
            'label' => Mage::helper('adminhtml')->__('Magento user groups'),
            'size'  => 28,
        ));
        $this->addColumn('crconnect', array(
            'label' => Mage::helper('adminhtml')->__('CrConnect group API key'),
            'size'  => 28
        ));
        $this->_addAfter = false;
        $this->_addButtonLabel = Mage::helper('adminhtml')->__('Add API keys for group');
        
        parent::__construct();
        $this->setTemplate('crconnect/system/config/form/field/array_groups.phtml');
        
        // customer options
        $this->magentoOptions = array();
		$allGroups = Mage::getModel('customer/group')->getCollection()->toOptionHash();
        foreach($allGroups as $key=>$allGroup){
        	$this->magentoOptions[$key] = $allGroup;
        }
		
    }

    protected function _renderCellTemplate($columnName)
    {
        if (empty($this->_columns[$columnName])) {
            throw new Exception('Wrong column name specified.');
        }
        $column     = $this->_columns[$columnName];
        $inputName  = $this->getElement()->getName() . '[#{_id}][' . $columnName . ']';

        if($columnName == 'magento')
        {
            $rendered = '<select name="'.$inputName.'">';
            foreach($this->magentoOptions as $att => $name)
            {
                $rendered .= '<option value="'.$att.'">'.$name.'</option>';
            }
            $rendered .= '</select>';
        }
        else
        {
            return '<input type="text" name="' . $inputName . '" value="#{' . $columnName . '}" ' . ($column['size'] ? 'size="' . $column['size'] . '"' : '') . '/>';
        }
        
        return $rendered;
    }
}
