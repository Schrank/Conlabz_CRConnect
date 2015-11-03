<?php

class Conlabz_CrConnect_Block_Adminhtml_Newsletter_Subscriber extends Mage_Adminhtml_Block_Newsletter_Subscriber
{

    protected $_successStatus = "SUCCESS";
	
    public function __construct(){
    
        $this->setTemplate('crconnect/newsletter/subscriber/list.phtml');
    
    }
	
    public function getSubscribersListData(){

	$errorList = array();
	$listInformation = array();

        $api = Mage::getModel("crconnect/api");
        
        if ($api->isConnected()){
            $clientDetails = $api->clientGetDetails(); 
            if ($clientDetails['error']){
                $errorList[] = $clientDetails['error'];
            }else{
                $listInformation['client'] = $clientDetails['data'];
            }	

            $groupStats = $api->groupGetStats(); 
            if ($groupStats['error']){
                $errorList[] = $groupStats['error'];
            }else{
                $listInformation['list'] = $groupStats['data'];
            }	
            
            $groupDetails = $api->groupGetDetails(); 
            if ($groupDetails['error']){
                $errorList[] = $groupDetails['error'];
            }else{
                $listInformation['list']->name = $groupDetails['data']->name;
                $listInformation['list']->last_mailing = $groupDetails['data']->last_mailing;
            }	
            
            $listInformation['groups'] = array();
            
            // If groups isset, get information for all groups in system
            if ($api->isMultyGroups()){

                $groups = Mage::helper("crconnect")->getGroupsIds();
                $counter = 0;   
                foreach ($groups as $group){
                    
                    $groupStats = $api->groupGetStats($group); 
                    if ($groupStats['error']){
                        $errorList[] = $groupStats['error'];
                    }else{
                        $listInformation['groups'][$counter] = $groupStats['data'];
                    }
                    
                    $groupDetails = $api->groupGetDetails($group); 
                    if ($groupDetails['error']){
                        $errorList[] = $groupDetails['error'];
                    }else{
                        $listInformation['groups'][$counter]->name = $groupDetails['data']->name;
                    }	
                    
                    $counter++;        
                }
                
            }
            
            
        }else{
          
            $errorList[] = Mage::helper("crconnect")->__("Can not connect to CleverReach account. Please check your Cleverreach settings.");
	
        }
        
        //Get amount of active and not active subscribers in system
        $activeSubscribers = count(Mage::getResourceModel('newsletter/subscriber_collection')->addFieldToFilter("subscriber_status", '1'));
        $inactiveSubscribers = count(Mage::getResourceModel('newsletter/subscriber_collection')->addFieldToFilter("subscriber_status", '0'));
        
        $listInformation['actvie_subscribers'] = $activeSubscribers;	
	$listInformation['inactive_subscribers'] = $inactiveSubscribers;	
        
        return array('error'=>$errorList, 'info'=>$listInformation);

    }
}
