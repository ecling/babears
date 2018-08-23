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
 * @package     Mage_Adminhtml
 * @copyright  Copyright (c) 2006-2015 X.commerce, Inc. (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Grid column widget for rendering action grid cells
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Producturl
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Text
{

    /**
     * Renders column
     *
     * @param Varien_Object $row
     * @return string
     */
    public function render(Varien_Object $row)
    {
        $actions = $this->getColumn()->getActions();
        $field = $this->getColumn()->getField();
        $product_id =  $row->getData($field);

        $adapter = Mage::getSingleton('core/resource')->getConnection('core_write');
        $ur_result = $adapter->query("SELECT * FROM `core_url_rewrite` WHERE product_id='".$product_id."' AND category_id IS NULL LIMIT 1");
        $url = $ur_result->fetch();
        $url = Mage::getStoreConfig('web/unsecure/base_url').'en/'.(isset($url['request_path'])?$url['request_path']:'');
        //$url = Mage::getStoreConfig('web/unsecure/base_url').'en/catalog/product/view/id/'.$row->getData($field);

        return '<a target="_blank" href="'.$url.'">'.$this->getColumn()->getHeader().'</a>';
    }
}
