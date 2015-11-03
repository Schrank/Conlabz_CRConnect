<?php
/**
 * Conlabz GmbH
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com and you will be sent a copy immediately.
 *
 * @category   CleverReach
 * @package    Conlabz_CrConnect
 * @copyright  Copyright (c) 2012 Conlabz GmbH (http://www.cleverreach.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

include "Mage/Newsletter/controllers/SubscriberController.php";

// Class that 'hooks' newsletter subscriptions from the frontend sign-up box.
// This is necessary because the Mage_Newsletter_Model_Subscriber class
// doesn't extend Mage_Core_Model_Abstract and so can't be observed directly.
// Instead we redirect all requests for newsletter/subscriber to this
// controller, which extends Mage_Newsletter_SubscriberController and
// overrides the newAction method.
class Conlabz_CrConnect_HookController extends Mage_Newsletter_SubscriberController {
    
    public function confirmAction()
    {
        $id    = (int) $this->getRequest()->getParam('id');
        $code  = (string) $this->getRequest()->getParam('code');
    	
    	if ($id && $code) {
            $subscriber = Mage::getModel('newsletter/subscriber')->load($id);
            $session = Mage::getSingleton('core/session');
        	
        	if($subscriber->getId() && $subscriber->getCode()) {
                if($subscriber->confirm($code)) {

                   	Mage::log("Cleverreach_CrConnect: newsletter signup for ".$subscriber->getEmail().", confirmation");
                	
                   	$apiKey = trim(Mage::getStoreConfig('crroot/crconnect/api_key'));
		            $listID = trim(Mage::getStoreConfig('crroot/crconnect/list_id'));
					$confirm = trim(Mage::getStoreConfig('newsletter/subscription/confirm'));  
					

					
					$customer = Mage::getModel('customer/customer')->setWebsiteId(Mage::app()->getStore()->getWebsiteId())->loadByEmail($subscriber->getEmail());

					if ($customer->getId()){
					
						if (Mage::getStoreConfig('crroot/crconnect/showgroup') == '1'){
	            			$groupKeys = Mage::helper('crconnect')->getKeys();
	            			if ($groupId = $customer->getGroupId()){
	            				if (isset($groupKeys[$groupId])){
	            					$listID = $groupKeys[$groupId];
	            				}
	            			}
	            		}
					
					}
                	
	                try {
		                $client = new SoapClient(Mage::helper('crconnect')->getWsdl(), 
						                         array("trace" => true));
		            } catch(Exception $e) {
		                Mage::log("CleverReach_CrConnect: Error connecting to server: ".$e->getMessage());
	                    $session->addException($e, $this->__('There was a problem with the subscription'));
	                    $this->_redirectReferer();
		            }
                	
                   try {
                        $result = $client->receiverAdd($apiKey, $listID, array(
                                "email" => $subscriber->getEmail(),
                                "source" => "MAGENTO (frontend)",
                        		"attributes" => array("key" => "newsletter", "value" => "1"),
                        )
                        );
                      
                      	if($result->status!="SUCCESS" && $result->statuscode == "50"){
                      	
                      		$result = $client->receiverUpdate($apiKey, $listID, array(
                                "email" => $subscriber->getEmail(),
                                "source" => "MAGENTO (frontend)",
                        		"attributes" => array("key" => "newsletter", "value" => "1"),
                        		"deactivated"=>0
                       	 	));
				      	
                      	}	
					  
                    } catch (Exception $e) {
                        Mage::log("CleverReach_CrConnect: Error in SOAP call: ".$e->getMessage());
                        $session->addException($e, $this->__('Subscription was invalid'));
                        $this->_redirectReferer();
                    }
		            
                    parent::confirmAction();
                } else {
                    $session->addError($this->__('Invalid subscription confirmation code'));
                }
            } else {
                $session->addError($this->__('Invalid subscription ID'));
            }
        }

        $this->_redirectUrl(Mage::getBaseUrl());
    }
		
	
	public function newAction() {
	
		if ($this->getRequest()->isPost() && $this->getRequest()->getPost('email')) {
            $session            = Mage::getSingleton('core/session');
            $customerSession    = Mage::getSingleton('customer/session');
            $email              = (string) $this->getRequest()->getPost('email');
		
            $apiKey = trim(Mage::getStoreConfig('crroot/crconnect/api_key'));
            $listID = trim(Mage::getStoreConfig('crroot/crconnect/list_id'));
			$confirm = trim(Mage::getStoreConfig('newsletter/subscription/confirm'));        

            if($apiKey && $listID && !$confirm) {
            	Mage::log("Cleverreach_CrConnect: newsletter signup for $email, no confirmation");
            	
	            try {
	                $client = new SoapClient(Mage::helper('crconnect')->getWsdl(), array("trace" => true));
	            } catch(Exception $e) {
	                Mage::log("CleverReach_CrConnect: Error connecting to server: ".$e->getMessage());
                    $session->addException($e, $this->__('There was a problem with the subscription'));
                    $this->_redirectReferer();
	            }


                $customerHelper = Mage::helper('customer');
				{
                    // otherwise if nobody's logged in, ignore the custom
                    // attributes and just set the name to '(Guest)'
                    try {
/*                     	echo "<pre>"; */
/*                     	$result = $client->receiverGetByEmail($apiKey, $listID, 'alex.nuzil@conlabz.de', 1); */
/*                     	var_dump($result); */
/*                     	exit; */
                    	
                    	if (Mage::getStoreConfig('crroot/crconnect/showgroup') == '1'){
	            			$groupKeys = Mage::helper('crconnect')->getKeys();
	            			if ($groupId = $customerSession->getCustomerGroupId()){
	            				if (isset($groupKeys[$groupId])){
	            					$return = $listID = $groupKeys[$groupId];
								}
	            			}
	            		}
	            		
                        $result = $client->receiverAdd($apiKey, $listID, array(
                                	"email" => $email,
                                	"source" => "MAGENTO (frontend)",
                                	"attributes" => array('0'=>array('key'=>'store', 'value'=>Mage::app()->getStore()->getCode(), 'variable'=>'{STORE}'),"newsletter"=>"1"),
                        		)
                        );
                    } catch (Exception $e) {
                        Mage::log("CleverReach_CrConnect: Error in SOAP call: ".$e->getMessage());
                        $session->addException($e, $this->__('There was a problem with the subscription'));
                        $this->_redirectReferer();
                    }
                }
            } else if($apiKey && $listID && $confirm){
               Mage::log("Cleverreach_CrConnect: skiping $email, waiting for confirmation");
            }else{
            	Mage::log("Cleverreach_CrConnect: error: API key and/or ListID missing");
            }
        }

        parent::newAction();
    }
}