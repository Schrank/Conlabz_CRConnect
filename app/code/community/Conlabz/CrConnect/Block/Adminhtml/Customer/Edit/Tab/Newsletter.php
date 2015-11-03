<?php
class Conlabz_CrConnect_Block_Adminhtml_Customer_Edit_Tab_Newsletter extends Mage_Adminhtml_Block_Customer_Edit_Tab_Newsletter
{

    public function initForm()
    {

        $form = new Varien_Data_Form();
        $form->setHtmlIdPrefix('_newsletter');
        $customer = Mage::registry('current_customer');
        $subscriber = Mage::getModel('newsletter/subscriber')->loadByCustomer($customer);
        Mage::register('subscriber', $subscriber);

        $this->setForm($form);
        return $this;

        
        /*
         * Next code wa have for displaying newsletter groups for subscribe.
         * But In backend we have problems with assigning customers to Store based on previois subscriprion (magento part feature)
         * So this code deaktivated at the moment
         */
        if ($customer->getWebsiteId() == 0) {
            $this->setForm($form);
            return $this;
        }

        $fieldset = $form->addFieldset('base_fieldset', array('legend'=>Mage::helper('customer')->__('Newsletter Information')));

        $isMultiply = Mage::helper("crconnect")->getGroupsIds($customer->getGroupId());
        if (!Mage::helper('crconnect')->isShowDefaultGroup() || !$isMultiply){
        
            $fieldset->addField('subscription', 'checkbox',
                 array(
                        'label' => Mage::helper('customer')->__('Subscribed to Newsletter?'),
                        'name'  => 'subscription'
                 )
            );

            if ($customer->isReadonly()) {
                $form->getElement('subscription')->setReadonly(true, true);
            }
            Mage::helper('crconnect')->setCurrentStoreId($subscriber->getStoreId());
            $form->getElement('subscription')->setIsChecked($subscriber->isSubscribed());

        }
        
        if (Mage::helper("crconnect")->isDefaultGroupUser($customer->getGroupId())){
            
            $groupName = Mage::getModel('customer/group')->load($customer->getGroupId())->getCode();
            $fieldset->addField('gsubscription', 'checkbox',
                array(
                   'label' => Mage::helper('customer')->__('Subscribed to %s Newsletter?', $groupName),
                   'name'  => 'gsubscription'
                )
            );
            
            $ifCustomSubscribed = Mage::getModel('crconnect/api')->isSubscribed(
                        $customer->getEmail(), 
                        $customer->getGroupId()
                    );
            
            $form->getElement('gsubscription')->setIsChecked($ifCustomSubscribed);

        }
        
        if($changedDate = $this->getStatusChangedDate()) {
             $fieldset->addField('change_status_date', 'label',
                 array(
                        'label' => $subscriber->isSubscribed() ? Mage::helper('customer')->__('Last Date Subscribed') : Mage::helper('customer')->__('Last Date Unsubscribed'),
                        'value' => $changedDate,
                        'bold'  => true
                 )
            );
        }

        $this->setForm($form);
        return $this;
    }

}
