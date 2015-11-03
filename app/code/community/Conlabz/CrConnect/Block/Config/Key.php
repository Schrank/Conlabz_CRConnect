<?php
class Conlabz_CrConnect_Block_Config_Key extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    
    public function __construct()
    {
        parent::__construct();
    }

    /*
     * Generate Feed URL for copy/paste
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
    
        $element = parent::_getElementHtml($element);
        $element .= '<button style="" onkeyup="Crconnect.confirmEnable()" onclick="Crconnect.confirmMainKey(true)" id="confirm-key-button" class="scalable disabled" type="button" disabled>
                        <span>'.Mage::helper("crconnect")->__("Confirm key").'</span>
                    </button>';
        
        return '<div style="width:430px">'.$element."</div>";
        
    }
	
}