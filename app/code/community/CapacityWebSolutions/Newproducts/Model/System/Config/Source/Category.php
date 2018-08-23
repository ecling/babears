<?php
/***************************************************************************
 Extension Name	: New Products
 Extension URL	: http://www.magebees.com/magento-new-products-extension.html
 Copyright		: Copyright (c) 2015 MageBees, http://www.magebees.com
 Support Email	: support@magebees.com 
 ***************************************************************************/
class CapacityWebSolutions_Newproducts_Model_System_Config_Source_Category
{
    public function buildCategoriesMultiselectValues(Varien_Data_Tree_Node $node, $values, $level = 0)
    {
        $nonEscapableNbspChar = html_entity_decode('&#160;', ENT_NOQUOTES, 'UTF-8');

        $level++;
        if ($level > 2) {
            $values[$node->getId()]['value'] = $node->getId();
            $values[$node->getId()]['label'] = str_repeat($nonEscapableNbspChar, ($level - 3) * 5).$node->getName();
        }

        foreach ($node->getChildren() as $child) {
            $values = $this->buildCategoriesMultiselectValues($child, $values, $level);
        }

        return $values;
    }

    public function toOptionArray()
    {
        $tree = Mage::getResourceSingleton('catalog/category_tree')->load();

        $parentId = 1;

        $root = $tree->getNodeById($parentId);

        if($root && $root->getId() == 1) {
            $root->setName(Mage::helper('catalog')->__('Root'));
        }
				
		$storeCode = Mage::app()->getRequest()->getParam('store',0);
		$store_id = Mage::getModel('core/store')->load($storeCode, 'code')->getId();
		
		$rootCategoryId = Mage::app()->getStore($store_id)->getRootCategoryId();

		$category_model = Mage::getModel('catalog/category'); //get category model
		$_category = $category_model->load($rootCategoryId );
		$all_child_categories = $category_model->getResource()->getAllChildren($_category);
		
		$collection = Mage::getModel('catalog/category')->getCollection()
		->addAttributeToSelect('name')
		->addAttributeToSelect('is_active');
		
		if($storeCode){
			$collection->addFieldToFilter('entity_id', array(array('in'=> $all_child_categories)));
		}
            
		$tree->addCollectionData($collection, true);

        $values['---'] = array(
            'value' => '',
            'label' => '',
        );
        return $this->buildCategoriesMultiselectValues($root, $values);
    }
}
