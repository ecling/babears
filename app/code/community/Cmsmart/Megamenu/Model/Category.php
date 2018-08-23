<?php
/*
* Name Extension: Megamenu
*/

class Cmsmart_Megamenu_Model_Category extends Varien_Object
{
    static public function toOptionArray()
    {
		$category = Mage::getModel('catalog/category'); 
		$tree = $category->getTreeModel(); 
		$tree->load();
		$ids = $tree->getCollection()->getAllIds(); 
		$arr = array();
		$arr['-1']='----Please Select----';
		if ($ids)
		{
			foreach ($ids as $id)
			{ 
				$cat = Mage::getModel('catalog/category'); 
				$cat->load($id);
				$arr[$id] = $cat->getName();
			} 
		}
		return $arr;
;
    }
}
