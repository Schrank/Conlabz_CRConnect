<?php

class Conlabz_CrConnect_Model_Api extends Mage_Core_Model_Abstract {

    const SUCCESS_STATUS = "SUCCESS";
    
    const ERROR_CODE_DUPLICATED = 50;
    const ERROR_CODE_INVALID = 40;
    
    public function __construct() {
        
        $this->init();
    
    }

    public function init($storeId = false){
        
        $this->_helper = Mage::helper('crconnect');
        
        if ($storeId){
            $this->_helper->log($this->_helper->__("SET Helper Store: ". $storeId));
            $this->_helper->setCurrentStoreId($storeId);
        }
        
        $this->_apiKey = $this->_helper->getApiKey();
        $this->_listID = $this->_helper->getDefaultListId();
        $this->_client = $this->getSoapClient();
        $this->_groupsListIds = $this->_helper->getGroupsIds();
        
    }
    /*
     * Get Connection to CrConnect
     */

    public function getSoapClient() {

        try {
            $client = new SoapClient($this->_helper->getWsdl(), array("trace" => true, "exception" => 0));
            return $client;
        } catch (Exception $e) {
            $this->_helper->log(Mage::helper("crconnect")->__("Connection to Cleverreach Server failed"));
            $this->_helper->log($e->getMessage());
            return false;
        }
        return false;
    }
    
    
    public function subscribe($customer = false, $groupId = 0) {

        if ($this->isConnected()){
            
            if (!$customer) {
                $customer = Mage::getSingleton('customer/session')->getCustomer();
            }
            $crReceiver = $this->_helper->prepareUserdata($customer, array('newsletter' => 1));
            $addResult = $this->receiverAdd($crReceiver, $groupId);
            if ($addResult->status == self::SUCCESS_STATUS) {
                
                $this->_helper->log($this->_helper->__("CALL: receiverAdd - SUCCESS"));
                $this->_helper->log($crReceiver);
                $this->_helper->log("receiverAdd: GroupId: ".$groupId);

                return true;
            } else {
                
                $this->_helper->log($this->_helper->__("CALL: receiverAdd - FAIL, then call receiverSetActive:".$customer->getEmail()));
                $this->_helper->log("receiverSetActive: GroupId: ".$groupId);

                $addResult = $this->receiverSetActive($customer->getEmail(), $groupId);
            }
            return true;
            
        }
        return false;
    }
    
    public function update($customer = false, $groupId = 0){
        
        if ($this->isConnected()){
            
            if (!$customer) {
                $customer = Mage::getSingleton('customer/session')->getCustomer();
            }
            $crReceiver = $this->_helper->prepareUserdata($customer);
            $updateResult = $this->receiverUpdate($crReceiver, $customer->getGroupId());
            if ($updateResult->status == self::SUCCESS_STATUS) {
                
                $this->_helper->log($this->_helper->__("CALL: receiverUpdate - SUCCESS"));
                $this->_helper->log($crReceiver);
                $this->_helper->log("receiverUpdate: GroupId: ".$customer->getGroupId());

                return true;
            } else {
                
                $this->_helper->log($this->_helper->__("CALL: receiverUpdate - FAIL"));
            
            }
            return true;
            
        }
        return false;
        
    }
    
    public function unsubscribe($email = false, $groupId = 0) {

        if ($this->isConnected() && $email){
            
            $result = $this->receiverSetInactive($email, $groupId);
            if ($result->status == self::SUCCESS_STATUS) {
                $this->_helper->log($this->_helper->__("CALL: receiverSetInactive - SUCCESS, Email:".$email." | GroupId:".$groupId));
                return true;
            }
            
        }
        return false;
    }

    /*
     * Check if connection was successfull 
     */

    public function isConnected() {

        if ($this->_client !== false && $this->_client !== NULL) {
            return true;
        }
        return false;
    }

    /*
     * If Account have more then 1 group
     */

    public function isMultyGroups() {

        if (is_array($this->_groupsListIds) && sizeof($this->_groupsListIds) > 0) {
            return true;
        }
        return false;
    }
    
    public function getGroupKey($groupId){
        
        if ($groupId == 0) {
            $listId = $this->_helper->getDefaultListId();
        } else {
            $listId = $this->_helper->getGroupsIds($groupId, true);
        }
        return $listId;
    }

    /*
     * Subscriber simple user to special group
     */
    public function receiverAdd($customerData, $groupId = 0) {

        $listId = $this->getGroupKey($groupId);
        
        $this->_helper->log("CALL: receiverAdd");
        $this->_helper->log($customerData);
        
        return $this->_client->receiverAdd($this->_apiKey, $listId, $customerData);
    
    }
    
    /*
     * Update simple user
     */
    public function receiverUpdate($customerData, $groupId = 0) {

        $listId = $this->getGroupKey($groupId);
        
        $this->_helper->log("CALL: receiverAdd");
        $this->_helper->log($customerData);
        
        return $this->_client->receiverAdd($this->_apiKey, $listId, $customerData);
    
    }
    
    /*
     * Deactivates a given receiver/email
     */
    public function receiverSetInactive($email, $groupId = 0) {

        $listId = $this->getGroupKey($groupId);
        $this->_helper->log("CALL: receiverSetInactive - Email".$email." | GroupId:". $groupId);
        return $this->_client->receiverSetInactive($this->_apiKey, $listId, $email);
    
    }
    
    public function receiverSetActive($email, $groupId = 0) {

        $listId = $this->getGroupKey($groupId);
        $this->_helper->log("CALL: receiverSetActive - Email".$email." | GroupId:". $groupId);
        return $this->_client->receiverSetActive($this->_apiKey, $listId, $email);

    }


    /*
     * Return request result
     * 
     * @param string result
     * @param error message if exists, or false
     * 
     * @return array - result
     */

    private function returnResult($result, $fail = false) {

        $return = array();
        $return['error'] = $fail;
        $return['data'] = $result;
        return $return;
    }

    /*
     * Get user account details
     */

    public function clientGetDetails() {

        $result = $this->_client->clientGetDetails($this->_apiKey);
        if ($result->status == self::SUCCESS_STATUS) {
            return $this->returnResult($result->data);
        } else {

            $this->_helper->log($this->_helper->__("CALL: clientGetDetails - failed"));
            $this->_helper->log($result->message);
            
            return $this->returnResult($result->data, $this->_helper->__("Your CleverReach API key seems to be invalid. Please check them and try again! <br /> Have no CleverReach account? Contact <a href='mailto:info@conlabz.de'>Conlabz GmbH</a> for additional information."));
        }
    }

    /*
     * Get Group Information
     */

    public function groupGetStats($groupId = false) {

        if (!$groupId) {
            $groupId = $this->_helper->getDefaultListId();
        }

        $result = $this->_client->groupGetStats($this->_apiKey, $groupId);
        if ($result->status == self::SUCCESS_STATUS) {
            return $this->returnResult($result->data);
        } else {

            $this->_helper->log($this->_helper->__("CALL: groupGetStats - failed. List ID: ".$groupId));
            $this->_helper->log($this->_helper->__($result->message));
            
            if ($groupId){
                return $this->returnResult($result->data, $this->_helper->__("Your list ID (%s) seem to be wrong. Please select other group!", $groupId));
            }
            return $this->returnResult($result->data, $this->_helper->__("Please set your CleverReach user group in Extension settings section."));
        }
    }

    /*
     * Get Group Information
     */

    public function groupGetDetails($listId = false) {

        if (!$listId) {
            $listId = $this->_helper->getDefaultListId();
        }

        $result = $this->_client->groupGetDetails($this->_apiKey, $listId);
        if ($result->status == self::SUCCESS_STATUS) {

            return $this->returnResult($result->data);
        } else {

            $this->_helper->log($this->_helper->__("CALL: groupGetDetails - failed. List ID: ".$listId));
            $this->_helper->log($this->_helper->__($result->message));
            
            if ($listId){
                return $this->returnResult($result->data, $this->_helper->__("Your list ID (%s) seem to be wrong. Please select other group!", $listId));
            }
            return $this->returnResult($result->data, $this->_helper->__("Please set your CleverReach user group in Extension settings section."));
            
        }
    }

    /*
     * Add batch of users to CleverReach
     * 
     * @param array - butch of users
     * @param int - Magento Groups ID
     * 
     * @return int amount of synced users
     */

    public function receiverAddBatch($batch, $groupId = 0) {

        if ($groupId == 0) {
            $listId = $this->_helper->getDefaultListId();
        } else {
            $listId = $this->_helper->getGroupsIds($groupId);
        }
        
        $this->_helper->log("CrConnect: receiverAddBatch.");
        $this->_helper->log($batch);
        
        $result = $this->_client->receiverAddBatch($this->_apiKey, $listId, $batch);
        if ($result->status == self::SUCCESS_STATUS) {
            
            $this->_helper->log("CrConnect: receiverAddBatch - SUCCESS.");
            $this->_helper->log("CrConnect: receiverAddBatch - key:".$this->_apiKey." | listId:".$listId);
            
            return count($batch);
        } else {

            $this->_helper->log("CrConnect: receiverAddBatch - FAIL.");
            $this->_helper->log("CrConnect: receiverAddBatch - key:".$this->_apiKey." | listId:".$listId);
            $this->_helper->log("CrConnect: receiverAddBatch - message:".$result->message);

            return false;
        }
    }

    public function receiverAddOrder($email, $orderInfo){
        
        $listId = $this->_helper->getDefaultListId();
        $result = $this->_client->receiverAddOrder($this->_apiKey, $listId, $email, $orderInfo);
        Mage::helper("crconnect")->log("CALL receiverAddOrder: ".$email);    
        Mage::helper("crconnect")->log($orderInfo);    
        Mage::helper("crconnect")->log($result);    
        if ($result->status == self::SUCCESS_STATUS) {
            return true;
        } else {
            return false;
        }
        
    }
    /*
     * Get and check if user subscribed to group
     *
     * @param string - user email
     * @param int - group Id
     *
     * @return bool true|false
     */

    public function isSubscribed($email, $groupId = 0) {

        if ($groupId == 0) {
            $listId = $this->_helper->getDefaultListId();
        } else {
            $listId = $this->_helper->getGroupsIds($groupId);
        }

        $result = $this->_client->groupGetDetails($this->_apiKey, $listId);
        if ($result->status == self::SUCCESS_STATUS) {

            $result = $this->_client->receiverGetByEmail($this->_apiKey, $listId, $email);
            if ($result->status == self::SUCCESS_STATUS && $result->data->active) {
                return true;
            }
        }
        return false;
    }
    
    /*
     * get groups for API key
     */
    public function getGroupsForKey($apiKey){
        
        $result = $this->_client->groupGetList($apiKey);
        if ($result->status == self::SUCCESS_STATUS) {
            return $result->data;   
        }else{
            return false;
        }
        
    }
    
    /*
     * get groups for API key
     */
    public function getFormsForGroup($apiKey, $groupId){
        
        $result = $this->_client->formsGetList($apiKey, $groupId);
        if ($result->status == self::SUCCESS_STATUS) {
            return $result->data;   
        }else{
            return false;
        }
        
    }
    
    public function formsSendActivationMail($customer, $groupId = 0){
        
        if ($this->isConnected()){
            
            // if not customer transfered, get current one from session
            if (!$customer) {
                $customer = Mage::getSingleton('customer/session')->getCustomer();
            }
            $crReceiver = $this->_helper->prepareUserdata($customer, array('newsletter' => 1), true);

            // Subscriber customer first
            $addResult = $this->receiverAdd($crReceiver, $groupId);
            
            $doidata = array(
                "user_ip" => $_SERVER['REMOTE_ADDR'],
                "user_agent" => $_SERVER['HTTP_USER_AGENT'],
                "referer" => Mage::getUrl("/"),
                "postdata" => "",
                "info" => "",
            );
            
            if ($addResult->status == self::SUCCESS_STATUS) {
                
                // Send activation email for customer
                $formId = $this->_helper->getFormsIds($groupId, true);
                $result = $this->_client->formsSendActivationMail($this->_apiKey, $formId, $customer->getEmail(), $doidata);

                if ($result->status == self::SUCCESS_STATUS) {
                    return true;
                }

            }else{
                
                $this->_helper->log("during formsSendActivationMail :: receiverAdd :: ERROR");
                $this->_helper->log($addResult);

                if ($addResult->statuscode == self::ERROR_CODE_DUPLICATED){
                    
                    if ($addResult->data->deactivated == 1){
        
                            
                        // Send activation email for customer
                        $formId = $this->_helper->getFormsIds($groupId, true);
                        $result = $this->_client->formsSendActivationMail($this->_apiKey, $formId, $customer->getEmail(), $doidata);
                        if ($result->status == self::SUCCESS_STATUS) {
                            return true;
                        }else{
                            
                            $this->_helper->log("during formsSendActivationMail :: formsSendActivationMail :: ERROR");
                            $this->_helper->log($result->message);
                            
                            if ($result->statuscode == self::ERROR_CODE_INVALID){
                                Mage::getSingleton("core/session")->addError($this->_helper->__("This Email blocked or wrong"));
                            }
                        }    
                        
                    }else{
                        
                        Mage::getSingleton("core/session")->addError($this->_helper->__("This Email already in our database"));
                        
                    }
                    
                }
                
            }
            return false;
            
        }
                
    }
    

    /*
     * Sync data between Magento and Cleverreach
     */

    public function synchronize() {

        //Check if we connected to Cr account
        if (!$this->isConnected()) {
            return false;
        }

        $this->_helper->log("RUN SYNCHRONIZATION");
        
        $isTrackingEnabled = $this->_helper->isTrackingEnabled();
        $subscribers = $this->_helper->getActiveMageSubscribers();

        $syncedUsers = 0;
        $batch = array();
        $i = 0;

        foreach ($subscribers as $subscriber) {

            $userGroup = 0;

            // If we should separate customers to different groups, then get customer Groups iD if exists
            if ($this->_helper->isSeparationEnabled()) {
                if ($subscriber["subscriber_email"]) {
                    // get customer by subscriber E-mail
                    $systemCustomer = Mage::getModel("customer/customer")->setWebsiteId($subscriber['website_id'])->loadByEmail($subscriber["subscriber_email"]);
                    if ($systemCustomer->getId()) {
                        $userGroup = $systemCustomer->getGroupId();
                    }
                }
            }
            
            
            if (isset($subscriber['customer_id']) && $subscriber['customer_id']){
                $tmp = $this->_helper->prepareUserdata(Mage::getModel("customer/customer")->load($subscriber['customer_id']));
            }else{
            
                $tmp["email"] = $subscriber["subscriber_email"];
                $tmp["source"] = "MAGENTO";

                // Prepare customer attributes
                $tmp["attributes"] = array(
                    0 => array("key" => "firstname", "value" => @$subscriber["customer_firstname"]),
                    1 => array("key" => "lastname", "value" => @$subscriber["customer_lastname"]),
                    2 => array("key" => "newsletter", "value" => "1")
                );
            
            }     
            
            // Separate users by Batch, 25 users in one
            if ($tmp["email"]) {
                $batch[$subscriber["store_id"]][$userGroup][floor($i++ / 25)][] = $tmp; //max 25 per batch
            }
        }

        try {

            // send subscribers batch to CleverReach	
            if ($batch) {

                foreach ($batch as $storeId => $groupBatch) {

                    $this->init($storeId);
                    
                    // send for each group	
                    foreach ($groupBatch as $groupId => $batchStore) {

                        foreach ($batchStore as $part) {

                            $this->_helper->log("SYNCHRONIZATION - receiverAddBatch");

                            $result = $this->receiverAddBatch($part, $groupId);
                            if ($result !== false) {
                                $this->_helper->log("FINISH SYNCHRONIZATION WITH EMPTY USERS");
                                $syncedUsers += $result;
                            } else {
                                return false;
                            }
                        }
                    }
                }
            }

            $this->_helper->log("FINISH SYNCHRONIZATION WITH :".$syncedUsers);
            return $syncedUsers;
            
        } catch (Exception $e) {
            $this->_helper->log("SYNCHRONIZATION Exception: ".$e->getMessage());
        }

        $this->_helper->log("FINISH SYNCHRONIZATION");
        
        return $synced_users;
    }
    
    public function setupDefaultClereReachList(){
        
        //Check if we connected to Cr account
        if (!$this->isConnected()) {
            return false;
        }
        
        $return = false;
        
        try {

            $return = $this->setupGroupFields($this->_helper->getDefaultListId());
            $groups = $this->_helper->getGroupsIds();
            foreach ($groups as $groupId){
                $return = $this->setupGroupFields($groupId);
            }
            
            
        } catch(Exception $e) {
        
            return false;
            
        }
        return $return;
    }
    public function setupGroupFields($listId){
     
        $return = false;
        
        $fields = array("firstname" => "firstname",
                        "lastname" => "lastname",
                        "street" => "street",
                        "zip" => "zip",
                        "city" => "city",
                        "country" => "country",
                        "salutation" => "salutation",
                        "title" => "title",
                        "company" => "company",
                        "newsletter" => "newsletter",
                        "group_id" => "group_id",
                        "group_name" => "group_name",
                        "gender" => "gender",
                        "store" => "store");
        
        $return = $this->_client->groupGetDetails($this->_apiKey, $listId);
        if($return->status == "SUCCESS"){

            foreach($return->data->attributes as $a){
                if(in_array($a->key, $fields)){
                    unset($fields[$a->key]);
                }
            }
            foreach($fields as $field){
                $return = $this->_client->groupAttributeAdd($this->_apiKey, $listId, $field, "text", "");
            }
        }
        return $return;
            
    }
}
