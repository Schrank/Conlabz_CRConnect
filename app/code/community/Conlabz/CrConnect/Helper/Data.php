<?php

class Conlabz_CrConnect_Helper_Data extends Mage_Core_Helper_Abstract {

    const XML_WSDL_PATH = "http://api.cleverreach.com/soap/interface_v5.1.php?wsdl";
    const XML_API_KEY_CONFIG_PATH = "crroot/crconnect/api_key";
    const XML_LIST_ID_CONFIG_PATH = "crroot/crconnect/list_id";
    const XML_FORM_ID_CONFIG_PATH = "crroot/crconnect/form_id";
    const XML_SYNC_ORDERS_CONFIG_PATH = "crroot/crconnect/sync_orders";
    const XML_SYNC_ORDERS_EMAILS_CONFIG_PATH = "crroot/crconnect/sync_orders_emails";
    const XML_SYNC_ORDERS_M2E_PATH = "crroot/crconnect/m2e_sync";
    
    const XML_GROUP_SEPARATION_CONFIG_PATH = "crroot/crconnect/auto_separate";
    const XML_GROUP_KEYS = "crroot/crconnect/groups_keys";
    const XML_PATH_LOGGED_CONFIRM_EMAIL_TEMPLATE = 'crroot/crconnect/confirm_newsletter_logged';
    const XML_IS_SHOW_CUSTOMER_GROUP = 'crroot/crconnect/showgroup';

    const XML_FEED_PASSWORD = 'crroot/csconnect_search/password';

    const DEFAULT_GROUP_ID = 1;
    
    private $_currentStoreId = false;
    private $_currentWebsiteId = false;

    public function setCurrentStoreId($storeId = false){
        $this->_currentStoreId = $storeId;
    }
    public function setCurrentWebsiteId($storeId = false){
        $this->_currentWebsiteId = $storeId;
    }

    /*
     *  Get Wsdl path
     *
     *  @return string - path
     */

    public function getWsdl() {
        return self::XML_WSDL_PATH;
    }

    /*
     * Get Feed password (same should be inserted on CleverReach account)
     */

    public function getCleverReachFeedPassword() {
        return $this->getConfigForStore(self::XML_FEED_PASSWORD);
    }
    
    /*
     * Is exclude M2E orders from sync
     */
    public function isM2eExclude(){
        return $this->getConfigForStore(self::XML_SYNC_ORDERS_M2E_PATH);
    }
    /*
     * Check if Douple Opt In enabled
     */

    public function isDoubleOptInEnabled() {
        return $this->getConfigForStore(self::XML_PATH_LOGGED_CONFIRM_EMAIL_TEMPLATE);
    }

    /*
     *  Get Api Key
     *
     *  @return string - api key
     */

    public function getApiKey() {
        return $this->getConfigForStore(self::XML_API_KEY_CONFIG_PATH);
    }
    
    /*
     *  Set Api Key
     *
     *  @return string - api key
     */
    public function setApiKey($key) {
        return Mage::getConfig()->saveConfig(self::XML_API_KEY_CONFIG_PATH, $key);
    }

    /*
     *  Get Default List Id
     *
     *  @return string - list Id
     */

    public function getDefaultListId() {
        
        return $this->getConfigForStore(self::XML_LIST_ID_CONFIG_PATH);

    }
    
    /*
     *  Get Default Form Id
     *
     *  @return string - list Id
     */

    public function getDefaultFormId() {
        
        return $this->getConfigForStore(self::XML_FORM_ID_CONFIG_PATH);

    }

    /*
     *  Check if orders tracking enabled
     *
     *  @return bool - 0 or 1
     */

    public function isTrackingEnabled() {
        return $this->getConfigForStore(self::XML_SYNC_ORDERS_CONFIG_PATH);
    }

    /*
     *  Check if automated separation enabled
     *
     *  @return bool - 0 or 1
     */

    public function isSeparationEnabled() {
        return $this->getConfigForStore(self::XML_GROUP_SEPARATION_CONFIG_PATH);
    }

    /*
     * Log Clever Reach Connect activity
     * 
     * @param - string message
     */

    public function log($message) {

        Mage::log($message, null, "crconnect.log", true);
    }
    
    /*
     * Get if sync order emails enabled
     */
    public function isForceSyncEnabled(){
        return Mage::getStoreConfig(self::XML_SYNC_ORDERS_EMAILS_CONFIG_PATH);
    }

    /*
     * Get groups keys json value from system settings
     * 
     * @return json
     */
    public function getGroupsSystemValue(){
        
        return $this->getConfigForStore(self::XML_GROUP_KEYS);
        
    }
    
    public function getConfigForStore($path){
        
        $newsletterConfig = Mage::getStoreConfig($path);
        
        if ($store = $this->_currentStoreId){
            return Mage::getStoreConfig($path, $store);
        }
        if ($website = $this->_currentWebsiteId){
            $newsletterConfig = (array)Mage::getConfig()->getNode('websites/'.$website.'/'.$path);
            if (isset($newsletterConfig[0])){
                return $newsletterConfig[0];
            }
        }
//        if ($website = Mage::getSingleton("adminhtml/session")->getCrCustomerWebsite($website)){
//            $newsletterConfig = (array)Mage::getConfig()->getNode('websites/'.$website.'/'.$path);
//            if (isset($newsletterConfig[0])){
//                return $newsletterConfig[0];
//            }
//        }
        
        if ($store = Mage::app()->getRequest()->getParam('store')){
            $newsletterConfig = Mage::getStoreConfig($path, $store);
        }else{
            if ($website = Mage::app()->getRequest()->getParam('website')){
                $newsletterConfig = (array)Mage::getConfig()->getNode('websites/'.$website.'/'.$path);
                if (isset($newsletterConfig[0])){
                    $newsletterConfig = $newsletterConfig[0];
                }
            }
        }
        return $newsletterConfig;
        
    }
    /*
     * get non default groups ids
     * 
     * @param int group ID - if isset get key for special group
     * @param bool - if true - return default list if in case if user groups Id key not found
     * 
     * @return array | int = array of keys, OR groups ID
     */

    public function getGroupsIds($groupId = false, $defaultOnFail = false) {

        $newsletterConfig = unserialize($this->getConfigForStore(self::XML_GROUP_KEYS));
        $keysArray = array();

        // Generate array of groupId=>key
        if (is_array($newsletterConfig)) {
            foreach ($newsletterConfig as $config) {
                $keysArray[$config['magento']] = $config['crconnect'];
            }
        }

        if ($groupId !== false) {
            if (isset($keysArray[$groupId])) {
                return $keysArray[$groupId];
            } else {
                if ($defaultOnFail) {
                    return $this->getDefaultListId();
                }
                return false;
            }
        }
        return $keysArray;
    }
    
    /*
     * get non default forms ids
     * 
     * @param int group ID - if isset get key for special group
     * @param bool - if true - return default list if in case if user groups Id key not found
     * 
     * @return array | int = array of keys, OR groups ID
     */

    public function getFormsIds($groupId = false, $defaultOnFail = false) {

        $newsletterConfig = unserialize($this->getConfigForStore(self::XML_GROUP_KEYS));
        $keysArray = array();

        // Generate array of groupId=>key
        if (is_array($newsletterConfig)) {
            foreach ($newsletterConfig as $config) {
                $keysArray[$config['magento']] = $config['formid'];
            }
        }

        if ($groupId !== false) {
            if (isset($keysArray[$groupId])) {
                return $keysArray[$groupId];
            } else {
                if ($defaultOnFail) {
                    return $this->getDefaultFormId();
                }
                return false;
            }
        }

        return $keysArray;
    }
    
    /*
     * get non default forms ids
     * 
     * @return array | int = array of keys, OR groups ID
     */

    public function getFormsIdsByKeys() {

        $newsletterConfig = unserialize($this->getConfigForStore(self::XML_GROUP_KEYS));
        
        $keysArray = array();

        // Generate array of groupId=>key
        if (is_array($newsletterConfig)) {
            foreach ($newsletterConfig as $key=>$config) {
                if (isset($config['formid'])){
                    $keysArray[$key] = $config['formid'];
                }
            }
        }

        return $keysArray;
    }

    /*
     * get active subscribers
     */

    public function getActiveMageSubscribers() {

        return Mage::getResourceModel('newsletter/subscriber_collection')
                        ->showStoreInfo()
                        ->showCustomerInfo()
                        ->useOnlySubscribed()
                        ->getData();
    }

    public function prepareUserdata($customer, $custom_fields = false, $deactivate = false) {

        $name = $customer->getFirstname() . " " . $customer->getLastname();
        $newEmail = $customer->getEmail();
        $subscribed = $customer->getIsSubscribed();
        $shippingAddress = false;
        if ($shippingAddress = $customer->getDefaultBillingAddress()){
            $shippingAddress = $shippingAddress->getData();
        }

        if ($group = Mage::getModel('customer/group')->load($customer->getGroupId())) {
            $group = $group->getData();
        }


        if ($shippingAddress) {
            $crReceiver = array(
                'email' => $newEmail,
                'source' => 'MAGENTO');
            $crReceiver["attributes"] = array(
                0 => array("key" => "firstname", "value" => @$shippingAddress["firstname"]),
                1 => array("key" => "lastname", "value" => @$shippingAddress["lastname"]),
                2 => array("key" => "street", "value" => @$shippingAddress["street"]),
                3 => array("key" => "zip", "value" => @$shippingAddress["postcode"]),
                4 => array("key" => "city", "value" => @$shippingAddress["city"]),
                5 => array("key" => "country", "value" => @$shippingAddress["country_id"]),
                6 => array("key" => "salutation", "value" => @$shippingAddress["prefix"]),
                7 => array("key" => "title", "value" => @$shippingAddress["suffix"]),
                8 => array("key" => "company", "value" => @$shippingAddress["company"]));
        } else {
            $crReceiver = array(
                'email' => $newEmail,
                'source' => "MAGENTO"
            );
            $crReceiver["attributes"] = array(0 => array("key" => 'firstname', "value" => @$customer->getFirstname()),
                1 => array("key" => 'lastname', "value" => @$customer->getLastname()),
                2 => array("key" => 'salutation', "value" => @$customer->getPrefix()),
                3 => array("key" => 'title', "value" => @$customer->getSuffix()));
        }

        if($deactivate){
            $crReceiver['deactivated'] = 1;
        }

        array_push($crReceiver["attributes"], array("key" => 'group_id', "value" => @$group["customer_group_id"]));
        array_push($crReceiver["attributes"], array("key" => 'group_name', "value" => @$group["customer_group_code"]));
        array_push($crReceiver["attributes"], array("key" => 'gender', "value" => @$customer->getGender()));
        array_push($crReceiver["attributes"], array("key" => 'store', "value" => @Mage::getModel('customer/customer')->load($customer->getId())->getData("created_in")));

        if ($custom_fields) {
            foreach ($custom_fields as $key => $val) {
                array_push($crReceiver["attributes"], array("key" => $key, "value" => $val));
            }
        }

        return $crReceiver;
    }

    /*
     * Show or not customer group as separate group
     */
    public function isShowDefaultGroup(){

        return $this->getConfigForStore(self::XML_IS_SHOW_CUSTOMER_GROUP);
        
    }
    
    /*
     * If more then 1 user group key added, return true
     */
    public function isMultiGroupsSettings(){
        
        $groupIds = $this->getGroupsIds();
        if (is_array($groupIds) && sizeof($groupIds) > 1){
            return true;
        }
        return false;
        
    }
    public function isDefaultGroupUser($groupId){
        
        if ($groupId != self::DEFAULT_GROUP_ID){
            return true;
        }
        return false;
        
    }
    public function getM2eShippingMethods(){
        
        return array("m2eproshipping_m2eproshipping");

    }
}
