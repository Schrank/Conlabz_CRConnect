<?php
class Conlabz_Crconnect_Adminhtml_CrconnectController extends Mage_Adminhtml_Controller_Action {

    /*
     * Accounts list action
     */
    public function synchronizeAction() {

        
        $syncedUsers = Mage::getModel("crconnect/api")->synchronize();
        $result = array();
	    
        if ($syncedUsers !== false){
            $result['error'] = false;
            $result['message'] = Mage::helper('crconnect')->__("Synchronization successfull. %s users were transmitted.", $syncedUsers);
            Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__("Synchronization successfull. %s users were transmitted.", $syncedUsers));
        }else{
            $result['error'] = true;
            $result['message'] = Mage::helper('crconnect')->__("Error occured while synchronization process. Please try again later.");
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__("Error occured while synchronization process. Please try again later."));
        }
	    
        $this->getResponse()->setBody(json_encode($result));
	    
    }
    
    protected function _isAllowed(){
    
        return true;
    
    }
    
}