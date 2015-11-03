<?php
class Conlabz_CrConnect_Block_Config_GroupsApis extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    protected $magentoOptions;

    public function __construct()
    {
        
        /*
         * Prepare settings columns
         */
        $this->addColumn('magento', array(
            'label' => Mage::helper('adminhtml')->__('Magento user groups'),
            'size'  => 28,
        ));
        $this->addColumn('crconnect', array(
            'label' => Mage::helper('adminhtml')->__('CleverReach Group'),
            'size'  => 28
        ));
        $this->addColumn('formid', array(
            'label' => Mage::helper('adminhtml')->__('CleverReach Form'),
            'size'  => 28
        ));
        
        $this->_addAfter = false;
        $this->_addButtonLabel = Mage::helper('adminhtml')->__('Add new group');
        
        parent::__construct();
        $this->setTemplate('crconnect/system/config/form/field/cr_array_groups.phtml');
        
        // customer options
        // Get Magento Groups List
        $this->magentoOptions = array();
        $allGroups = Mage::getModel('customer/group')->getCollection()->toOptionHash();
        foreach($allGroups as $key=>$allGroup){
            $this->magentoOptions[$key] = $allGroup;
        }
		
    }
    /*
     * Render Template
     */
    protected function _renderCellTemplate($columnName)
    {
        
        if (empty($this->_columns[$columnName])) {
            throw new Exception('Wrong column name specified.');
        }
        
        $column     = $this->_columns[$columnName];
        $inputName  = $this->getElement()->getName() . '[#{_id}][' . $columnName . ']';
        $inputId  = '#{_id}_' . $columnName . '';

        $api = Mage::getModel("crconnect/api");
        $groups = array();
        if ($api->isConnected()){
            $groups = $api->getGroupsForKey(Mage::helper('crconnect')->getApiKey());
            if (!$groups){
                $groups = array();
            }
        }
        
        switch($columnName){
            
            case 'magento':
                $rendered = '<select style="width: 200px" name="'.$inputName.'" id="'.$inputId.'">';
                foreach($this->magentoOptions as $att => $name)
                {
                    $rendered .= '<option value="'.$att.'">'.$name.'</option>';
                }
                $rendered .= '</select>';
                break;
            case 'crconnect':
                $rendered = '<select style="width: 200px" class="crconnect-groups-select" onchange="Crconnect.changeSubGroup(this)" id="'.$inputId.'" name="'.$inputName.'"><option value="">'.Mage::helper('crconnect')->__('Please select subscribers group').'</option>';
                foreach($groups as $group)
                {
                    $rendered .= '<option value="'.$group->id.'">'.$group->name.'</option>';
                }
                $rendered .= '</select>';
                break;
            case 'formid':
                $emptyForms = Mage::getModel("crconnect/system_config_source_emptyForms")->toOptionArray();
                $rendered = '<select style="width: 200px" name="'.$inputName.'" id="'.$inputId.'" class="crconnect-forms-select">';
                foreach($emptyForms as $emptyForm)
                {
                    $rendered .= '<option value="'.$emptyForm['value'].'">'.$emptyForm['label'].'</option>';
                }
                $rendered .= '</select>';
                break;
            default:
//                $rendered = '<input type="text" name="' . $inputName . '" value="#{' . $columnName . '}" ' . ($column['size'] ? 'size="' . $column['size'] . '"' : '') . '/>';
                break;
            
            
        }
        
        return $rendered;
    }
}
