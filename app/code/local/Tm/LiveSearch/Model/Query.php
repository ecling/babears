<?php

class Tm_LiveSearch_Model_Query extends Mage_CatalogSearch_Model_Query
{
    protected function _construct()
    {
        $this->_init('livesearch/query');
    }

    /**
     * Retrieve collection of suggest queries
     *
     * @return Mage_CatalogSearch_Model_Resource_Query_Collection
     */
    public function getSuggestCollection()
    {
        $visibilityModel = Mage::getSingleton('catalog/product_visibility');

        $searchTerm = Mage::helper('catalogsearch')->getQueryText();
        $collection = Mage::getResourceModel('catalog/product_collection')
            ->addAttributeToSelect(array('name', 'price', 'thumbnail', 'visibility', 'description', 'short_description', 'sku'))
            //->addAttributeToFilter('name', array('like' => '%' . $searchTerm . '%'))
            ->addAttributeToFilter(
                array(
                    array('attribute' => 'name', 'like' => '%' . $searchTerm . '%'),
					array('attribute' => 'sku', 'like' => '%' . $searchTerm . '%'),
                    array('attribute' => 'description', 'like' => '%' . $searchTerm . '%'),
                )
            )
            ->addAttributeToFilter('visibility', array('in' => $visibilityModel->getVisibleInSiteIds()));

        //print $collection->getSelect();

        return $collection;
    }
}