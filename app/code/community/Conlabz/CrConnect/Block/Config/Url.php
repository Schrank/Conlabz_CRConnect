<?php
class Conlabz_CrConnect_Block_Config_Url extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /*
     * Generate Feed URL for copy/paste
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $url = Mage::getUrl('crconnect/search/index');
        $url .= "<font color='red'>store/1/</font>";
        return "<b>".$url."</b>";
    }
	
}