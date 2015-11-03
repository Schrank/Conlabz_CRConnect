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
 * @copyright  Copyright (c) 2012 Conlabz GmbH (http://conlabz.de)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class SubscriberCustomField
{
    function SubscriberCustomField($k, $v)
    {
        $this->Key = $k;
        $this->Value = $v;
    }
}

class Conlabz_CrConnect_Model_Customer_Observer
{
	public function session_init($observer)
    {   
    	
    	$mailingId = Mage::getSingleton('core/app')->getRequest()->getParam('crmailing');
		$cookie = Mage::getSingleton('core/cookie');
		if ($mailingId){
			$cookie->set('crmailing', $mailingId ,time()+3600*24*14,'/');
		}
    	$customerId = Mage::getSingleton('core/app')->getRequest()->getParam('crcustomer');
		$cookie = Mage::getSingleton('core/cookie');
		if ($customerId){
			$cookie->set('crcustomer', $customerId ,time()+3600*24*14,'/');
		}
    
    }
	
	public function check_subscription_status($observer)
    {

   		$event = $observer->getEvent();
        $customer = $event->getCustomer();

        $apiKey = trim(Mage::getStoreConfig('crroot/crconnect/api_key'));
        $listID = trim(Mage::getStoreConfig('crroot/crconnect/list_id'));
       
        $name = $customer->getFirstname() . " " . $customer->getLastname();
        $newEmail = $customer->getEmail();
        $subscribed = $customer->getIsSubscribed();
        
        $groupId = Mage::getSingleton('customer/session')->getCustomerGroupId();
		
        $shippingAddress = false;
        if($tmp = $customer->getDefaultBillingAddress())
        	$shippingAddress = $tmp->getData();
		
		
        try {
            $client = new SoapClient(Mage::helper('crconnect')->getWsdl(), array("trace" => true));
        } catch(Exception $e) {
          	Mage::log("CleverReach_CrConnect: Error connecting to CleverReach server: ".$e->getMessage());
        }
            
		$keys = Mage::helper('crconnect')->getKeys();

        $isCustomSubscribed = false;
		if (isset($keys[$groupId])){
			$isCustomSubscribed = Mage::helper('crconnect')->getSubscriber($newEmail, $groupId);
        }
        
        if ($isCustomSubscribed){
        	if (isset($_POST['is_gsubscribed']) && $_POST['is_gsubscribed'] == 1){
        	
        	}else{
        		try {

        			$return = $client->receiverSetInactive($apiKey, $keys[$groupId], $newEmail);

        	    } catch(Exception $e) {
                    Mage::log("CleverReach_CrConnect: Error in SOAP call: ".$e->getMessage());
                    return;
                }
        	}
        }else{
        
        	if (isset($_POST['is_gsubscribed']) && $_POST['is_gsubscribed'] == 1){
        	
        		$crReceiver = Mage::helper('crconnect')->prepareUserdata($customer, array('newsletter'=>1), true);
                $return = $client->receiverAdd($apiKey, $keys[$groupId], $crReceiver);
                if ($return->status == "ERROR"){
	                if($return->statuscode=="50"){ //try update
						$crReceiver["deactivated"] = 0;
						$return = $client->receiverUpdate($apiKey, $keys[$groupId], $crReceiver);
						Mage::log("CleverReach_CrConnect:". $crReceiver["attributes"][1]["key"]);
						if(!$return->status=="SUCCESS"){
							Mage::log("CleverReach_CrConnect: resubscribe error - ".$return->message);
						}
					}
                }
        
        	}
        }

		$oldEmail = Mage::getModel('customer/customer')->load($customer->getId())->getEmail();

        // if subscribed is NULL (i.e. because the form didn't set it one way
        // or the other), get the existing value from the database

        if($subscribed === NULL)
        {
            $subscribed = Mage::getModel('newsletter/subscriber')->loadByCustomer($customer)->isSubscribed();
        }
        
        if($apiKey and $listID)
        {
            if($subscribed)
            {
            	$crReceiver = Mage::helper('crconnect')->prepareUserdata($customer, array('newsletter'=>1), true);
                Mage::log("CleverReach_CrConnect: Subscribing new email address (ob): $newEmail");
                try {
					
					// Get keys for different user groups
	            	if (Mage::getStoreConfig('crroot/crconnect/showgroup') == '1'){
	            		$groupKeys = Mage::helper('crconnect')->getKeys();
	            		if ($groupId = $customer->getGroupId()){
	            			if (isset($groupKeys[$groupId])){
	            				$return = $client->receiverSetInactive($apiKey, $listID, $crReceiver["email"]);
                				$listID = $groupKeys[$groupId];
	            			}
	            		}
	            	}
					$return = $client->receiverAdd($apiKey, $listID, $crReceiver);
					if($return->status=="SUCCESS"){				
						Mage::log("CleverReach_CrConnect: subscribed - ".$crReceiver["email"]);
					}else{
						if($return->statuscode=="50"){ //try update
							$crReceiver["deactivated"] = 0;
							$return = $client->receiverUpdate($apiKey, $listID, $crReceiver);
							Mage::log("CleverReach_CrConnect:". $crReceiver["attributes"][1]["key"]);
							if(!$return->status=="SUCCESS"){
								Mage::log("CleverReach_CrConnect: resubscribe error - ".$return->message);
							}
						}else{
							Mage::log("CleverReach_CrConnect: error - ".$return->message);
						}
					}
                } catch(Exception $e) {
                    Mage::log("CleverReach_CrConnect: Error in SOAP call: ".$e->getMessage());
                }
            }
            elseif($oldEmail)
            {

                Mage::log("CleverReach_CrConnect: Unsubscribing: $oldEmail");
                $crReceiver = Mage::helper('crconnect')->prepareUserdata($customer, array('newsletter'=>0), false);
                try {
                    $return = $client->receiverSetInactive($apiKey, $listID, $crReceiver["email"]);
                	if($return->status=="SUCCESS"){				
						Mage::log("CleverReach_CrConnect: unsubscribed - ".$crReceiver["email"]);
						
						if($return->status == "SUCCESS"){
							Mage::log("CleverReach_CrConnect: updating newsletterflag");
							$client->receiverUpdate($apiKey, $listID, $crReceiver);
						}
					}else{											//call failed
						Mage::log("CleverReach_CrConnect: error - ".$return->message);					
					}
                } catch(Exception $e) {
                    Mage::log("CleverReach_CrConnect: Error in SOAP call: ".$e->getMessage());
                }
            }
        }
    }

    public function customer_deleted($observer)
    {
    
        $event = $observer->getEvent();
        $customer = $event->getCustomer();

        $apiKey = trim(Mage::getStoreConfig('newsletter/crconnect/api_key'));
        $listID = trim(Mage::getStoreConfig('newsletter/crconnect/list_id'));
       
        $email = $customer->getEmail();

		$keys = Mage::helper('crconnect')->getKeys();

        $isCustomSubscribed = false;
		if (isset($keys[$customer->getGroupId()])){
			$isCustomSubscribed = Mage::helper('crconnect')->getSubscriber($email, $customer->getGroupId());
        }
        
        if($apiKey and $listID)
        {
            try {
                $client = new SoapClient(Mage::helper('crconnect')->getWsdl(), array("trace" => true));
            } catch(Exception $e) {
                Mage::log("CleverReach_CrConnect: Error connecting to server: ".$e->getMessage());
            }
        	
            Mage::log("CleverReach_CrConnect: Customer deleted, unsubscribing: $email");
            try {
                $return = $client->receiverDelete($apiKey, $listID, $email);
                if($return->status=="SUCCESS"){				
					Mage::log("CleverReach_CrConnect: deleted - ".$email);
				}else{											//call failed
					Mage::log("CleverReach_CrConnect: error - ".$return["message"]);
				}
				
				if ($isCustomSubscribed){
					$return = $client->receiverDelete($apiKey, $keys[$groupId], $email);
                	if($return->status=="SUCCESS"){				
						Mage::log("CleverReach_CrConnect: deleted - ".$email);
					}else{											//call failed
						Mage::log("CleverReach_CrConnect: error - ".$return["message"]);
					}	
				}
				
            } catch(Exception $e) {
                Mage::log("CleverReach_CrConnect: Error in SOAP call: ".$e->getMessage());
            }
        }
    }
    
    
    public function getIsSubscribed($observer){
    	 Mage::log("CleverReach_CrConnect: stat");
    	 //Mage_Customer_Block_Newsletter
    }
    
    
}
?>