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

class Conlabz_CrConnect_UnsubscribeController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        // don't do anything if we didn't get the email parameter
        if(isset($_GET['email']))
        {
            $email = $_GET['email'];
           	$apiKey = trim(Mage::getStoreConfig('newsletter/crconnect/api_key'));
		    $listID = trim(Mage::getStoreConfig('newsletter/crconnect/list_id'));
            
            // Check that the email address actually is unsubscribed in
            // CleverReach
            if($apiKey && $listID)
            {
				try {
	                $client = new SoapClient(Mage::helper('crconnect')->getWsdl(), array("trace" => true));
	            } catch(Exception $e) {
	                Mage::log("CleverReach_CrConnect: Error connecting to CleverReach server: ".$e->getMessage());
                    $session->addException($e, $this->__('There was a problem with the subscription'));
                    $this->_redirectReferer();
	            }

	            //get data from cleverreach
	            Mage::log("CleverReach_CrConnect: Error - ".$tmp->message);
	            $tmp = $client->receiverGetByEmail($apiKey, $email);
	            if($tmp->status!="SUCCESS"){
					Mage::log("CleverReach_CrConnect: Error - ".$tmp->message);
	                $session->addException($e, $this->__('There was a problem with the unsubscription'));
                    $this->_redirectReferer();
	            }else{
	            	Mage::log("CleverReach_CrConnect: Error - ".$tmp->message);
	            }
	            
                // If we are unsubscribed in cleverreach, mark us as
                // unsubscribed in Magento.
                if($tmp->data->deactivated)
                {
                    try
                    {
                        Mage::log("CleverReach_CrConnect: Unsubscribing $email");
                        $collection = Mage::getModel('newsletter/subscriber')
                                ->loadByEmail($email)
                                ->unsubscribe();

                        Mage::getSingleton('customer/session')->addSuccess($this->__('You were successfully unsubscribed'));
                    }
                    catch (Exception $e)
                    {
                        Mage::log("CleverReach_CrConnect: ".$e->getMessage());
                        Mage::getSingleton('customer/session')->addError($this->__('There was an error while saving your subscription details'));
                    }
                }
                else
                {
                    Mage::log("CleverReach_CrConnect: Not unsubscribing $email, not unsubscribed in Campaign Monitor");
                }
            }
        }
        
        $this->_redirect('customer/account/');
    }
}