<?php
/***************************************************************************
 Extension Name	: New Products
 Extension URL	: http://www.magebees.com/magento-new-products-extension.html
 Copyright		: Copyright (c) 2015 MageBees, http://www.magebees.com
 Support Email	: support@magebees.com 
 ***************************************************************************/
class CapacityWebSolutions_Newproducts_Block_Newproducts extends Mage_Catalog_Block_Product_New //Mage_Catalog_Block_Product_Abstract 
{
	/**
     * Name of request parameter for page number value
     */
    const PAGE_VAR_NAME                     = 'p';
    protected $_productCollection;
	
	public function __construct() {
		parent::_construct();
		$this->addColumnCountLayoutDepend('empty', 6)
            ->addColumnCountLayoutDepend('one_column', 5)
            ->addColumnCountLayoutDepend('two_columns_left', 4)
            ->addColumnCountLayoutDepend('two_columns_right', 4)
            ->addColumnCountLayoutDepend('three_columns', 3);
		
		$this->setStoreId(Mage::app()->getStore()->getId());
		
		//General Settings
		$this->setEnabled((bool)Mage::getStoreConfig("newproducts/general/enabled"));
		$this->setDisplayHeading((bool)Mage::getStoreConfig("newproducts/general/display_heading"));
		$this->setHeading(Mage::getStoreConfig("newproducts/general/heading"));
		$this->setChooseProducts(Mage::getStoreConfig("newproducts/general/choose_products"));
		$this->setChooseType((bool)Mage::getStoreConfig("newproducts/general/choose_type"));
		$this->setDays((int)Mage::getStoreConfig("newproducts/general/new_days"));
		$this->setDisplayBy(Mage::getStoreConfig("newproducts/general/display_by"));
		$this->setCategories(Mage::getStoreConfig("newproducts/general/categories"));
		$this->setSortBy(Mage::getStoreConfig("newproducts/general/sort_by"));
		$this->setSortOrder(Mage::getStoreConfig("newproducts/general/sort_order"));
		
		$this->setProductsPrice((bool)Mage::getStoreConfig("newproducts/general/products_price"));
		$this->setReview((bool)Mage::getStoreConfig("newproducts/general/review"));
		$this->setShortDesc((bool)Mage::getStoreConfig("newproducts/general/short_desc"));
		$this->setDescLimit((int)Mage::getStoreConfig("newproducts/general/desc_limit"));
		$this->setAddToCart((bool)Mage::getStoreConfig("newproducts/general/add_to_cart"));
		$this->setAddToWishlist((bool)Mage::getStoreConfig("newproducts/general/add_to_wishlist"));
		$this->setAddToCompare((bool)Mage::getStoreConfig("newproducts/general/add_to_compare"));
		$this->setOutOfStock((bool)Mage::getStoreConfig("newproducts/general/out_of_stock"));
		$this->setIsResponsive((bool)Mage::getStoreConfig('newproducts/general/isresponsive'));
		
		//Template Settings
		$this->setCustomTemplate(Mage::getStoreConfig("newproducts/template/select_template"));
		$this->setProductsCount((int)Mage::getStoreConfig("newproducts/template/number_of_items"));
		$this->setShowPager((bool)Mage::getStoreConfig("newproducts/template/show_pager"));
		$this->setProductsPerPage((int)Mage::getStoreConfig("newproducts/template/products_per_page"));
		$this->setHeight((int)Mage::getStoreConfig("newproducts/template/thumbnail_height"));
		$this->setWidth((int)Mage::getStoreConfig("newproducts/template/thumbnail_width"));
	}

	public function setWidgetOptions(){
		//General Settings
		$this->setDisplayHeading((bool)$this->getWdDisplayHeading());
		$this->setHeading($this->getWdHeading());
		$this->setChooseProducts($this->getWdChooseProducts());
		$this->setChooseType($this->getWdChooseType());
		$this->setDays((int)$this->getWdNewDays());
		$this->setDisplayBy((int)$this->getWdDisplayBy());
		$this->setCategories($this->getWdCategories());
		$this->setSortBy($this->getWdSortBy());
		$this->setSortOrder($this->getWdSortOrder());
		$this->setProductsPrice((bool)$this->getWdProductsPrice());
		$this->setReview((bool)$this->getWdReview());
		$this->setShortDesc((bool)$this->getWdShortDesc());
		$this->setDescLimit((int)$this->getWdDescLimit());
		$this->setAddToCart((bool)$this->getWdAddToCart());
		$this->setAddToWishlist((bool)$this->getWdAddToWishlist());
		$this->setAddToCompare((bool)$this->getWdAddToCompare());
		$this->setOutOfStock((bool)$this->getWdOutOfStock());
		
		//Template Settings
		$this->setProductsCount((int)$this->getWdNumberOfItems());
		$this->setShowPager((bool)$this->getWdShowPager());
		$this->setProductsPerPage((int)$this->getWdProductsPerPage());
		$this->setHeight((int)$this->getWdThumbnailHeight());
		$this->setWidth((int)$this->getWdThumbnailWidth());
	}
	
	protected function _getProductCollection()  {
		switch ($this->getChooseProducts()) {
            case 1: //Auto
				if($this->getChooseType()){
					//$collection = parent::_getProductCollection();
					$collection = $this->_getAutoProductCollection();
				}else{
					$collection = $this->_getProductsCollectionByCreatedDate();
				}
                break;
			case 2: //Manually
				$collection = $this->_getManuallyAddedProductsCollection();
                break;
			case 3: //Both
				if($this->getChooseType()){
					$collection1 = $this->_getAutoProductCollection();
				}else{
					$collection1 = $this->_getProductsCollectionByCreatedDate();
				}
				$collection2 = $this->_getManuallyAddedProductsCollection();
						
				$merged_ids = array_unique(array_merge($collection1->getAllIds(), $collection2->getAllIds()));
			
				$collection = Mage::getResourceModel('catalog/product_collection')
					//->addFieldToFilter('entity_id', array('in' => $merged_ids))
					->addAttributeToSelect('*');
				break;
            default:
				$collection = $this->_getAutoProductCollection();
                break;
        }
		
		$storeId    = Mage::app()->getStore()->getId();
		
		$collection ->addMinimalPrice()
					->addFinalPrice()
					->setStore($storeId)
					->addStoreFilter($storeId)
					//->setPageSize($this->getProductsCount())
					//->setCurPage(1)
				;
					
		//Display out of stock products
		if(!$this->getOutOfStock()){
			Mage::getSingleton('cataloginventory/stock')
				->addInStockFilterToCollection($collection);
		}
			
		//Display By Category
		if($this->getDisplayBy()==2)
		{
			$categorytable = Mage::getSingleton('core/resource')->getTableName('catalog_category_product');
			$collection->getSelect()
						->joinLeft(array('at_category_id' => $categorytable),'e.entity_id = at_category_id.product_id','at_category_id.category_id')
						->group('e.entity_id')
						->where("at_category_id.category_id IN (".$this->getCategories().")")
					;
		}
					
		//Set Sort Order
		if($this->getSortOrder()=='rand'){
			$collection->getSelect()->order('rand()');
		}else{
			$collection->addAttributeToSort($this->getSortBy(), $this->getSortOrder());
		}

		return $collection;
    }

	/**
     * Prepare collection with new products
     *
     * @return Mage_Core_Block_Abstract
     */
    protected function _beforeToHtml(){
		if($this->getType()=="newproducts/widget")
		{
			$this->setWidgetOptions();
		}
        $this->getPagerHtml();
        $this->getProductCollection()->load();
        return parent::_beforeToHtml();
        //$this->setProductCollection($this->_getProductCollection());
	}
    
    public function getProductCollection(){
        if(is_null($this->_productCollection)){
            $this->_productCollection = $this->_getProductCollection();
        }
        return $this->_productCollection;
    }
	
	//Get new products collection as per new from and new to date
	protected function _getAutoProductCollection(){
		$todayStartOfDayDate  = Mage::app()->getLocale()->date()
            ->setTime('00:00:00')
            ->toString(Varien_Date::DATETIME_INTERNAL_FORMAT);

        $todayEndOfDayDate  = Mage::app()->getLocale()->date()
            ->setTime('23:59:59')
            ->toString(Varien_Date::DATETIME_INTERNAL_FORMAT);

        /** @var $collection Mage_Catalog_Model_Resource_Product_Collection */
        $collection = Mage::getResourceModel('catalog/product_collection');
        $collection->setVisibility(Mage::getSingleton('catalog/product_visibility')->getVisibleInCatalogIds());


        $collection = $this->_addProductAttributesAndPrices($collection)
            ->addStoreFilter()
            ->addAttributeToFilter('news_from_date', array('or'=> array(
                0 => array('date' => true, 'to' => $todayEndOfDayDate),
                1 => array('is' => new Zend_Db_Expr('null')))
            ), 'left')
            ->addAttributeToFilter('news_to_date', array('or'=> array(
                0 => array('date' => true, 'from' => $todayStartOfDayDate),
                1 => array('is' => new Zend_Db_Expr('null')))
            ), 'left')
            ->addAttributeToFilter(
                array(
                    array('attribute' => 'news_from_date', 'is'=>new Zend_Db_Expr('not null')),
                    array('attribute' => 'news_to_date', 'is'=>new Zend_Db_Expr('not null'))
                    )
              )
            ->addAttributeToSort('news_from_date', 'desc')
		;

        return $collection;	
	}
	
	protected function _getManuallyAddedProductsCollection(){
		/** @var $collection Mage_Catalog_Model_Resource_Product_Collection */
        $collection = Mage::getResourceModel('catalog/product_collection');
        $collection->setVisibility(Mage::getSingleton('catalog/product_visibility')->getVisibleInCatalogIds());

        $collection = $this->_addProductAttributesAndPrices($collection)
			->addAttributeToFilter('sku', array('in' => $this->getSkus()));
		return $collection;
    }
	
	protected function _getProductsCollectionByCreatedDate()
	{
		$days = $this->getDays();
		//$to = date('Y-m-d', $time);
		//$time = Mage::getModel('core/date')->timestamp(time());
				
		$to = Mage::getSingleton('core/date')->gmtDate();
		$time = strtotime(Mage::getSingleton('core/date')->gmtDate());
		$lastTime = $time - (60*60*24*$days);
		$from = date('Y-m-d H:i:s', $lastTime);
				
		$collection = Mage::getResourceModel('catalog/product_collection');
        $collection->setVisibility(Mage::getSingleton('catalog/product_visibility')->getVisibleInCatalogIds());
		$collection = $this->_addProductAttributesAndPrices($collection);
		$collection->addAttributeToSelect('*')
				->addAttributeToSort('created_at', 'desc')
				->addAttributeToFilter('created_at', array('from' => $from, 'to' => $to));
		return $collection; 
	}
	
	public function getSkusArr($element){
		return $element['sku'];
	}
	
	public function getSkus(){
		$store_id =  $this->getStoreId();
		$newproductsCollection = Mage::getModel('newproducts/newproducts')->getCollection()->addFieldToFilter('store_id', array(array('finset' => $store_id)));
		$product_skus=array_map(array($this,"getSkusArr"), $newproductsCollection->getData());
		return $product_skus;
	}
	
	public function limit_word($text, $limit) {
		if (str_word_count($text, 0) > $limit) {
		  $words = str_word_count($text, 2);
		  $pos = array_keys($words);
		  $text = substr($text, 0, $pos[$limit]) . '...';
		}
		return $text;
    }
	
	public function _toHtml(){	
		if (!$this->getEnabled()) {
            return '';
        }
		if(!$this->getTemplate()){
			if($this->getCustomTemplate()==2){
				$this->setTemplate('newproducts/newproducts-list.phtml');
			}else{
				$this->setTemplate('newproducts/newproducts-grid.phtml');
			}
		}
        return parent::_toHtml();
    }
	
	/**
     * Render pagination HTML
     *
     * @return string
     */
    public function getPagerHtml()
    {
        if ($this->getShowPager()) {
            if (!$this->_pager) {
                $this->_pager = $this->getLayout()
                    ->createBlock('newproducts/widget_html_pager', 'widget.new.product.list.pager');
				
                $this->_pager->setUseContainer(true)
                    ->setShowAmounts(true)
                    ->setShowPerPage(false)
                    ->setPageVarName(self::PAGE_VAR_NAME)
                    ->setLimit(24)
                    //->setLimit($this->getProductsPerPage())
                    //->setTotalLimit(100)
                    ->setCollection($this->getProductCollection());
            }
            if ($this->_pager instanceof Mage_Core_Block_Abstract) {
                //return $this->_pager->toHtml();
                $this->setChild('pager', $this->_pager);
            }
        }
        return $this;
    }

}
