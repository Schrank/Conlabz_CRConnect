<?php

class Conlabz_CrConnect_Model_Subscriber extends Mage_Core_Model_Abstract {

    /*
     *  Subscribe cusotmer
     */
    public function subscribe($customer = false, $groupId = 0) {

        return Mage::getModel("crconnect/api")->subscribe($customer, $groupId);
        
    }
    
    /*
     * Send activation email for customer
     */
    public function formsSendActivationMail($customer = false, $groupId = 0) {

        return Mage::getModel("crconnect/api")->formsSendActivationMail($customer, $groupId);
        
    }
    
    /*
     *  Subscribe cusotmer
     */
    public function unsubscribe($email = false, $groupId = 0) {

        return Mage::getModel("crconnect/api")->unsubscribe($email, $groupId);
        
    }
    
    public function updateCustomer($customer, $groupId = 0){
        
        return Mage::getModel("crconnect/api")->update($customer, $groupId);
        
    }
    
}
