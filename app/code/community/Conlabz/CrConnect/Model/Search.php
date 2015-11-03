<?php

class Conlabz_CrConnect_Model_Search {
    /*
     * Get Search Filters values
     */

    public function getFilter() {

        $filter = array();

        // Generate Categories Filter

        $filter[0] = array();
        $categoryFilter = array();
        $categoryFilter['name'] = Mage::helper("crconnect")->__("Category");
        $categoryFilter['required'] = false;
        $categoryFilter['query_key'] = "category";
        $categoryFilter['type'] = "dropdown";

        $rootcatId = Mage::app()->getStore()->getRootCategoryId();
        $categories = Mage::getModel('catalog/category')->getCategories($rootcatId);


        $categoriesTree = $this->getCategories($categories);
        $categoryFilter['values'] = array_merge(array(array("text" => "", "value" => "")), $categoriesTree);

        $filter[0] = $categoryFilter;

        $filter[1] = array();
        $productFilter = array();
        $productFilter['name'] = Mage::helper("crconnect")->__("Product");
        $productFilter['required'] = false;
        $productFilter['query_key'] = "product";
        $productFilter['type'] = "input";

        $filter[1] = $productFilter;

        return $filter;
    }

    /*
     * Get categories tree recurcively function 
     *
     * @return array of categories of one level
     */

    public function getCategories($categories) {

        foreach ($categories as $category) {
            $cat = Mage::getModel('catalog/category')->load($category->getId());
            $prefix = "";
            for ($i = 0; $i < $category->getLevel() - 2; $i++) {
                $prefix .= "&nbsp;";
            }
            $array[] = array("text" => $prefix . $category->getName(), "value" => $category->getId()); //In this line we get an a link for the product and product count of that category
            if ($category->hasChildren()) {
                $children = Mage::getModel('catalog/category')->getCategories($category->getId()); // $children get a list of all subcategories
                $array = array_merge($array, $this->getCategories($children)); //recursive call the get_categories function again.
            }
        }
        return $array;
    }

    /*
     * Get Search result
     *
     * @param int category - category ID
     * @param string product - part of name or description
     *
     */

    public function getSearch($category = false, $product = "", $store = false) {

        $products = Mage::getModel('catalog/product')->getCollection()
                ->addAttributeToSelect("name")
                ->addAttributeToSelect("description")
                ->addAttributeToSelect("image")
                ->addAttributeToSelect("price");

        if ($store) {
            $products->setStoreId($store);
        }

        if ($category) {
            $category = Mage::getModel("catalog/category")->load($category);
            $products->addCategoryFilter($category);
        }
        if ($product) {
            $products->addFieldToFilter("name", array("like" => "%{$product}%"));
        }

        $search = array();
        $search['settings']['type'] = "product";
        $search['settings']['link_editable'] = true;
        $search['settings']['link_text_editable'] = true;
        $search['settings']['image_size_editable'] = true;

        $items = array();
        if ($category || $product) {
            foreach ($products as $product) {

                $item = array();
                $item['title'] = $product->getName();
                $item['description'] = $product->getDescription();
                $item['image'] = $product->getImageUrl();
                $item['url'] = $product->getProductUrl();
                $item['price'] = Mage::helper('core')->formatPrice($product->getPrice());

                $items[] = $item;
            }
        }

        $search['items'] = $items;
        return $search;
    }

}
