<?php
class Conlabz_CrConnect_Model_System_Config_Source_EmptyList
{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => '', 'label'=>Mage::helper('crconnect')->__('No groups to select')),
        );
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
//    public function toArray()
//    {
//        return array(
//            0 => Mage::helper('adminhtml')->__('No'),
//            1 => Mage::helper('adminhtml')->__('Yes'),
//        );
//    }

}
