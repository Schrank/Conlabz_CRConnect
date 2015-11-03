<?php
class Conlabz_CrConnect_SearchController extends Mage_Core_Controller_Front_Action {

    public function indexAction() {

        $systemPassword = Mage::helper("crconnect")->getCleverReachFeedPassword();

        $store = $this->getRequest()->getParam('store');
        $password = $this->getRequest()->getParam("password");

        if (($systemPassword || $password) && $password != $systemPassword) {
            die(Mage::helper("crconnect")->__("You have no permissions to view this page"));
        }

        $search = Mage::getModel('crconnect/search');
        $action = $this->getRequest()->getParam('get');
        switch ($action) {
            case 'filter':
                $returnData = $search->getFilter();
                break;
            case 'search':
                $category = $this->getRequest()->getParam('category', false);
                $product = $this->getRequest()->getParam('product', '');
                $returnData = $search->getSearch($category, $product, $store);
                break;
        }

        $this->getResponse()->setBody(json_encode($returnData));
    }

}
