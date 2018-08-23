<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magento.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magento.com for more information.
 *
 * @category    Mage
 * @package     Mage_Catalog
 * @copyright  Copyright (c) 2006-2015 X.commerce, Inc. (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Catalog product related items block
 *
 * @category   Mage
 * @package    Mage_Catalog
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Catalog_Block_Product_List_Related extends Mage_Catalog_Block_Product_Abstract
{
    /**
     * Default MAP renderer type
     *
     * @var string
     */
    protected $_mapRenderer = 'msrp_noform';

    protected $_itemCollection;
	
	protected function _construct()
	{
		$product_id = Mage::registry('current_product')->getId();
		$key = Mage::app()->getStore()->getId().'-relate-'.$product_id;
		$this->addData(array(
			'cache_lifetime' => 360000000, 
			'cache_tags' => array("mg_product_view_page_" .$key),
			'cache_key' => $key,
		));
		
		 parent::_construct();
	}
	
    protected function _prepareData()
    {
        $product = Mage::registry('product');
        /* @var $product Mage_Catalog_Model_Product */
        
        /*
        $this->_itemCollection = $product->getRelatedProductCollection()
            ->addAttributeToSelect('required_options')
            ->setPositionOrder()
            ->addStoreFilter()
        ;
        */
        
        $categorys = $product->getCategoryCollection();
        $level = 0;
        foreach($categorys as $category){
            if($category->getLevel()>$level){
                $level = $category->getLevel();
                $current_category = $category;
            }
        }

        $this->_itemCollection = $current_category->getProductCollection();
        $date = date('Y-m-d',time()-3600*24*30);
        $this->_itemCollection->getSelect()
            ->joinLeft(array('oi'=>'sales_flat_order_item'),
                "oi.product_id=e.entity_id and oi.created_at>'".$date."'",
                'SUM(oi.qty_ordered) as hot')
            ->where('oi.created_at>"'.$date.'"')
            ->group('e.entity_id')
            ->order('hot DESC');
        
        if (Mage::helper('catalog')->isModuleEnabled('Mage_Checkout')) {
            Mage::getResourceSingleton('checkout/cart')->addExcludeProductFilter($this->_itemCollection,
                Mage::getSingleton('checkout/session')->getQuoteId()
            );
            $this->_addProductAttributesAndPrices($this->_itemCollection);
        }
        
//        Mage::getSingleton('catalog/product_status')->addSaleableFilterToCollection($this->_itemCollection);
        Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($this->_itemCollection);

        $this->_itemCollection->setPageSize(12)->load();
        
        foreach ($this->_itemCollection as $product) {
            $product->setDoNotUseCategoryId(true);
        }

        return $this;
    }

    protected function _beforeToHtml()
    {
        $this->_prepareData();
        return parent::_beforeToHtml();
    }

    public function getItems()
    {
        return $this->_itemCollection;
    }

    /**
     * Get tags array for saving cache
     *
     * @return array
     */
    public function getCacheTags()
    {
        return array_merge(parent::getCacheTags(), $this->getItemsTags($this->getItems()));
    }
}
