<?php

class Conlabz_CrConnect_Model_Newsletter_Subscriber extends Mage_Newsletter_Model_Subscriber {

    /*
     * Check If customer is subscribed
     */
    public function isSubscribed($groupId = 0) {

        if (!$this->getEmail()){
            $this->setEmail(Mage::getSingleton('customer/session')->getCustomer()->getEmail());
        }
        return Mage::getModel("crconnect/api")->isSubscribed($this->getEmail(), $groupId);
    }

    /**
     * Subscribes by email
     *
     * @param string $email
     * @throws Exception
     * @return int
     */
    public function subscribe($email, $groupId = 0) {

        if (Mage::helper("customer")->isLoggedIn()) {
            $customerSession = Mage::getSingleton('customer/session');
            $customer = $customerSession->getCustomer();
        } else {
            $customer = Mage::getModel("customer/customer")->setWebsiteId(Mage::app()->getWebsite()->getId())->loadByEmail($email);
            $customer->setEmail($email);
        }
        
        if (!$this->isSubscribed($groupId)) {
            if (Mage::helper("crconnect")->isDoubleOptInEnabled()) {
                return Mage::getModel("crconnect/subscriber")->formsSendActivationMail($customer, $groupId);
            } else {
                return Mage::getModel("crconnect/subscriber")->subscribe($customer, $groupId);
            }
        } else {
            return false;
        }
    }
    public function sendConfirmationSuccessEmail()
    {
        if (!Mage::helper("crconnect")->isDoubleOptInEnabled()) {
            parent::sendConfirmationSuccessEmail();
        }
    }

}
