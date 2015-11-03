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

class Conlabz_CrConnect_SubscriberController extends Mage_Newsletter_SubscriberController {

    const XML_PATH_LOGGED_CONFIRM_EMAIL_TEMPLATE = 'newsletter/subscription/confirm_logged_email_template';

    public function newAction() {

        if ($this->getRequest()->isPost() && $this->getRequest()->getPost('email')) {

            $session = Mage::getSingleton('core/session');
            $customerSession = Mage::getSingleton('customer/session');
            $email = (string) $this->getRequest()->getPost('email');

            try {

                $status = Mage::getModel("newsletter/subscriber")->subscribe($email);
                
                if ($status){
                    if (Mage::helper("crconnect")->isDoubleOptInEnabled()) {
                        Mage::getSingleton('core/session')->addSuccess($this->__('Confirmation request has been sent.'));
                    } else {
                        Mage::getSingleton('core/session')->addSuccess($this->__('Thank you for your subscription.'));
                    }
                }
                
                $this->_redirectReferer();
            } catch (Exception $e) {
                $session->addException($e, $this->__('There was a problem with the subscription'));
                $this->_redirectReferer();
            }
        }
    }

}
