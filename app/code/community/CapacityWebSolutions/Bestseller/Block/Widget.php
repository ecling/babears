<?php
class CapacityWebSolutions_Bestseller_Block_Widget extends CapacityWebSolutions_Bestseller_Block_Bestseller // Mage_Core_Block_Template
 implements Mage_Widget_Block_Interface
{
    public function addData(array $arr){
        $this->_data = array_merge($this->_data, $arr);
    }

    public function setData($key, $value = null){
        $this->_data[$key] = $value;
    }
 
    public function _toHtml(){
		if($this->getData('template')){
			$this->setTemplate($this->getData('template'));
		}
		return parent::_toHtml();
	}
}
