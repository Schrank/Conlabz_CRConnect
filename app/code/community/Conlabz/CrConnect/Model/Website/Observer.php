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
 * @package    Conlanz_CrConnect
 * @copyright  Copyright (c) 2012 Conlabz GmbH (http://conlabz.de)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


class Conlabz_CrConnect_Model_Website_Observer
{
	public function trackingCodeCheck(){
		$mailingId = Mage::getSingleton('core/app')->getRequest()->getParam('crmailing');
		$cookie = Mage::getSingleton('core/cookie');
		if ($mailingId){
			$cookie->set('crmailing', $mailingId ,time()+3600*24*14,'/');
		}
	}
}
