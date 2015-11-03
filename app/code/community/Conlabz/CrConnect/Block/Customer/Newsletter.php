<?php
class Conlabz_CrConnect_Block_Customer_Newsletter extends Mage_Customer_Block_Newsletter 
{

    private $_api;
    
    public function __construct()
    {
        $this->setCustomerGroupId(Mage::getSingleton('customer/session')->getCustomerGroupId());
        $this->_api = Mage::getModel('crconnect/api');
    
        parent::__construct();
    }
    
    public function getCustomerGroupName(){
        
        $groupName = Mage::getModel('customer/group')->load($this->getCustomerGroupId())->getCode();

    }
    /*
     * Check if Customer Group not default
     * 
     * @return bool
     */
    public function isDefaultGroupUser(){

        return Mage::helper("crconnect")->isDefaultGroupUser($this->getCustomerGroupId());
        
    }
    
    public function isDefaultSubscribed(){
       
        return $this->_api->isSubscribed(Mage::getSingleton('customer/session')->getCustomer()->getEmail());
        
    }
    
    public function isCustomSubscribed(){
    
        return $this->_api->isSubscribed(
                                            Mage::getSingleton('customer/session')->getCustomer()->getEmail(), 
                                            Mage::getSingleton('customer/session')->getCustomerGroupId()
                                        );
        
    }
    

}
