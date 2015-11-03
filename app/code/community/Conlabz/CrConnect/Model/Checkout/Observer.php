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
 * @copyright  Copyright (c) 2012 Conlabz GmbH (http://www.conlabz.de)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Conlabz_CrConnect_Model_Checkout_Observer
{
    public function success_action($observer)
    {   
        $event = $observer->getEvent();
        
        $email = false;
        $apiKey = trim(Mage::getStoreConfig('crroot/crconnect/api_key'));
        $listID = trim(Mage::getStoreConfig('crroot/crconnect/list_id'));
        
        $syncOrders = trim(Mage::getStoreConfig('crroot/crconnect/sync_orders'));
        $syncOrderStatus = trim(Mage::getStoreConfig('crroot/crconnect/sync_order_status'));
        
        if($syncOrders)
        	$lastOrderId = Mage::getSingleton('checkout/session')->getLastOrderId();
        else{
        	$lastOrderId = false;
        	Mage::log("CleverReach_CrConnect: order sycing deactivated");
        }
        if ($lastOrderId){
	            $order = Mage::getModel('sales/order')->load($lastOrderId);
	            $customer = Mage::getModel('customer/customer')->load($order->getCustomerId());
	            
	            $subscribed = $customer->getIsSubscribed();
	            
		        if($subscribed === NULL)
		        {
		            $subscribed = Mage::getModel('newsletter/subscriber')->loadByCustomer($customer)->isSubscribed();
		        }
	            
		        if($subscribed){
		        	$add = array("newsletter" => "1");
		        }else{
		        	$add = array("newsletter" => "0");
		        }
		    
               	$email = $order->getCustomerEmail();
               	
               	if($email){
             		if($customer->getEmail()){
               			$crReceiver = Mage::helper('crconnect')->prepareUserdata($customer, $add, false);
             		}else{
	             		$shippingAddress = $order->getBillingAddress()->getData();
	             		if($shippingAddress){
					        $crReceiver = array (
							  'email' => $email,
							  //'registered' => strtotime($shippingAddress["created_at"]),
							  //'activated' => strtotime($shippingAddress["updated_at"]),
							  'source' => 'MAGENTO',
							  'attributes' => array(0 => array("key" => "firstname", "value" => @$shippingAddress["firstname"]),
											        1 => array("key" => "lastname", "value" => @$shippingAddress["lastname"]),
											        2 => array("key" => "street", "value" => @$shippingAddress["street"]),
											        3 => array("key" => "zip", "value" => @$shippingAddress["postcode"]),
											        4 => array("key" => "city", "value" => @$shippingAddress["city"]),
											        5 => array("key" => "country", "value" => @$shippingAddress["country_id"]),
											        6 => array("key" => "salutation", "value" => @$shippingAddress["prefix"]),
											        7 => array("key" => "title", "value" => @$shippingAddress["suffix"]),
											        8 => array("key" => "company", "value" => @$shippingAddress["company"]))
					        );
					        
							$cookie = Mage::getSingleton('core/cookie');
					        if ($cookie->get('crmailing')){
		    			    	$crReceiver['orders'][0]['mailings_id'] = $cookie->get('crmailing');
		        			}
					        
							if($subscribed){
								$crReceiver["attributes"][] = array("key" => 'newsletter', "value" => "1");
							}
				        }
             		}               	
               	}
               	
        }
        
        if($apiKey && $listID && $email && $lastOrderId && $syncOrders)
        {
        	
            try {
                $client = new SoapClient(Mage::helper('crconnect')->getWsdl(), array("trace" => true));
            } catch(Exception $e) {
                Mage::log("CleverReach_CrConnect: Error connecting to CleverReach server: ".$e->getMessage());
            }

            /* ########################### */
	        if($crReceiver)
	            {
	                try {
	                	$tmp = $crReceiver;
	                	$addTxt="keeping status";
						//if new users should be activated by default. do it
	                	if($syncOrderStatus){
	                		$tmp["deactivated"] = 0;
							$addTxt = "forced active"; 
						}
						
						// Get keys for different user groups
	            		if (Mage::getStoreConfig('crroot/crconnect/showgroup') == '1'){
	            			$groupKeys = Mage::helper('crconnect')->getKeys();
	            			if ($groupId = $customer->getGroupId()){
	            				if (isset($groupKeys[$groupId])){
	            					$return = $client->receiverAdd($apiKey, $groupKeys[$groupId], $tmp);
								}
	            			}
	            		}else{
							$return = $client->receiverAdd($apiKey, $listID, $tmp);
						}
						
						if($return->status=="SUCCESS"){				
							Mage::log("CleverReach_CrConnect: subscribed ($addTxt) - ".$crReceiver["email"]);
						}else{		
							if($return->statuscode=="50"){ //seems to exists allready, try update
								$return = $client->receiverUpdate($apiKey, $listID, $tmp);
								if(!$return->status=="SUCCESS"){				
									Mage::log("CleverReach_CrConnect: order insert error - ".$return->message);
								}else{
									Mage::log("CleverReach_CrConnect: resubscribed ($addTxt) - ".$crReceiver["email"]);
								}
							}else{
								Mage::log("CleverReach_CrConnect: error - ".$return->message);
							}
						}
	                } catch(Exception $e) {
	                    Mage::log("CleverReach_CrConnect: Error in SOAP call: ".$e->getMessage());
	                }
	            }
            
            /* ########################### */
            
        	$items = $order->getAllItems();
        	if($items)foreach ($items as $item){
        		
        		$tmpItem = array();
        		$tmpItem["order_id"] = $lastOrderId;
        		$tmpItem["product"] = $item->getName();
        		$tmpItem["product_id"] = $item->getProductId();
        		$tmpItem["price"] = round($item->getPrice(),2);
        		$tmpItem["quantity"] = (integer)$item->getQtyOrdered();
        		$tmpItem["purchase_date"] = time();
        		$tmpItem["currency"] = $order->getData('order_currency_code');
        		$tmpItem["source"] = "MAGENTO Order";
		        
		        $cookie = Mage::getSingleton('core/cookie');
		        if ($cookie->get('crmailing')){
		        	$tmpItem['mailings_id'] = $cookie->get('crmailing');
		        }
        		
		        $tmp = $client->receiverAddOrder($apiKey, $listID, $email, $tmpItem);
	            if($tmp->status!="SUCCESS"){						
					Mage::log("CleverReach_CrConnect: Error - ".$tmp->message);
	            }else{
	            	Mage::log("CleverReach_CrConnect: submitted: ".$tmpItem["order_id"]." - ".$tmpItem["product"]);
	            }
        	}   
        }   	
    }
}
