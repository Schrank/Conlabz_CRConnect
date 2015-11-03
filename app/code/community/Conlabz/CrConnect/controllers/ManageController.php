<?php

include "Mage/Newsletter/controllers/ManageController.php";

class Conlabz_CrConnect_ManageController extends Mage_Newsletter_ManageController {

    public function saveAction() {
    
        if (!$this->_validateFormKey()) {
            return $this->_redirect('customer/account/');
        }
        try {

            $email = Mage::getSingleton('customer/session')->getCustomer()->getEmail();    
            $subscriber = Mage::getModel("newsletter/subscriber")->loadByEmail($email);
            $subscriber->setEmail(Mage::getSingleton('customer/session')->getCustomer()->getEmail());

            if ((boolean) $this->getRequest()->getParam('is_subscribed', false)) {
                if (!$subscriber->isSubscribed()) {
                    
                    $status = Mage::getModel("newsletter/subscriber")->subscribe($email);
                    if (Mage::helper("crconnect")->isDoubleOptInEnabled()) {
                        Mage::getSingleton('customer/session')->addSuccess($this->__('Confirmation request has been sent.'));
                    } else {
                        Mage::getSingleton('customer/session')->addSuccess($this->__('Thank you for your subscription.'));
                    }
                    
                }
            } else {
                if ($subscriber->isSubscribed()) {
                    $status = Mage::getModel("crconnect/subscriber")->unsubscribe($email);
                    Mage::getSingleton('customer/session')->addSuccess($this->__('The subscription has been removed.'));
                }
            }

            $groupId = Mage::getSingleton('customer/session')->getCustomerGroupId();
            if ($groupId > 1) {
                if ((boolean) $this->getRequest()->getParam('is_gsubscribed', false)) {

                    if (!$subscriber->isSubscribed($groupId)) {

                        $status = Mage::getModel("newsletter/subscriber")->subscribe(Mage::getSingleton('customer/session')->getCustomer()->getEmail(), $groupId);
                        if (Mage::helper("crconnect")->isDoubleOptInEnabled()) {
                            Mage::getSingleton('customer/session')->addSuccess($this->__('Confirmation request has been sent.'));
                        } else {
                            Mage::getSingleton('customer/session')->addSuccess($this->__('Thank you for your subscription.'));
                        }
                    }
                } else {
                    if ($subscriber->isSubscribed($groupId)) {
                        $status = Mage::getModel("crconnect/subscriber")->unsubscribe($email, $groupId);
                        Mage::getSingleton('customer/session')->addSuccess($this->__('The subscription has been removed.'));
                    }
                }
            }
        } catch (Exception $e) {
            Mage::getSingleton('customer/session')->addError($e->getMessage());
            Mage::getSingleton('customer/session')->addError($this->__('An error occurred while saving your subscription.'));
        }
        $this->_redirect('customer/account/');
    }

}
