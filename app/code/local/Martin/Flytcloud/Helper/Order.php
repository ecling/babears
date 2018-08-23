<?php

class Martin_Flytcloud_Helper_Order extends Mage_Core_Helper_Abstract
{
    const FIELD_NOT_ASSIGNMENT='field-not-assignment';
    const FIELD_ASSIGNMENT='field-assignment';

    protected $_order;


    protected function _setOrder($order)
    {
        $this->_order=$order;
        return $this;
    }
    public function _getOrder()
    {
        return $this->_order;
    }
    protected function _getAddressObj()
    {
        $order=$this->_getOrder();
        if($order){
            $address=$order->getShippingAddress();
            if($address && $address instanceof Varien_Object){
                return  $address;
            }else{
                throw new Exception('there is no shipping address');
            }
        }else{
            throw new Exception('there should be a order first');
        }
    }


    protected function _getCustomerId()
    {
        return Mage::getStoreConfig('flytcloudbasic/account/customerId');
    }
    protected function _assignNum()
    {
        return 1;
    }
    protected function _getSign()
    {
        $customerId=$this->_getCustomerId();
        $secret=Mage::getStoreConfig('flytcloudbasic/account/password');
        $num=$this->_assignNum();
        return "$customerId##$secret##$num";
        
    }

    protected function _getCountry()
    {
        $countryId=$this->_getAddressObj()->getCountryId();
        return $countryId;
        //return Mage::helper('flytcloud')->getCountryById($countryId);
    }

    protected function _getPostType()
    {
        $postType=Mage::helper('flytcloud/shippingtype')->getShippingTypeByCountry($this->_getAddressObj()->getCountryId());
        return $postType->getShippingTypeCode();
    }
    
    protected function _getReceiverName()
    {
        $address=$this->_getAddressObj();
        return Mage::helper('flytcloud')->generateName($address);
    }
    protected function _getEmail()
    {
        $address=$this->_getAddressObj();
        return $address->getData('email');
    }
    protected function _getAddress1()
    {
          $streetData=$this->_getAddressObj()->getData('street');
          if(is_array($streetData) ){
              if(count($streetData)>1){
                  return implode(" ", $street);
              }else{
                  return array_pop($street);
              }
          }
          if(is_string($streetData)){
              return preg_replace('/\s+/',' ',$streetData);
          }
          throw new Exception("unexcept street value");
//        $address=$this->_getAddressObj();
//        $city=$address->getData('city');
//        $region=$address->getData('region');
//        $postcode=$address->getData('postcode');
//        $country=$this->_getCountry();
//        return "$city $region $postcode $country";
    }
    protected function _getState(){
        $address=$this->_getAddressObj();
        $region=$address->getData('region');
        if($region){
            return $region;
        }else{
            return $this->_getCity();
        }
        
    }
    protected function _getCity (){
        $address=$this->_getAddressObj();
        $city=$address->getData('city');
        return $city;
    }
    protected function _getZip()
    {
        $address=$this->_getAddressObj();
        $postcode=$address->getData('postcode');
        return $postcode;
    }
    protected function _getPhone()
    {
        $address=$this->_getAddressObj();
        return $address->getData('telephone');
    }
    
    protected function _getPackType(){
        return 'Package';
    }

    protected function _getRemark ()
    {
        $order=$this->_getOrder();
        $quoteId=$order->getQuoteId();
        $quote=Mage::getModel('sales/quote')->load($quoteId);
        $items=$quote->getAllVisibleItems();

        $remarks=array();
        $items=$order->getAllVisibleItems();
        
        foreach($items as $item)
        { 
                $remarkItem=$this->_initRemarkItemByOrderItem($item);
                $remarks[]=$remarkItem;
        }
        $remark=  implode(",", $remarks);
        return strlen($remark)<100?$remark:substr($remark,0,94).'.....';
    }
    
    public function getBuyOptions($item)
    {
        $productOptions=$item->getProductOptions();
        if(isset($productOptions['options']))
        {
            $options = $productOptions['options'];
            return $options;
        }
        return null;
    }
    protected function _initRemarkItemByOrderItem($item)
    {
        $remark=$item->getData('sku');

        $options = $this->getBuyOptions($item);
        foreach($options as $option )
        {
            $remark.="-".$option['value'];
        }


        $remark=rtrim($remark,'-');
        $qty = (int)$item->getQtyOrdered() ;
        $qty=$qty>1?"*$qty":'';
        $remark.=$qty;
        return $remark;
        
              
//            $colorLable="Color";
//            $sizeLabel='Size';
//            $sku=$item->getSku();
//            $name=$item->getName();
//            $productId=$item->getProductId();
//
//            $color=$this->getAttrTextFromOrderItem($item,$colorLable);
//            $size=$this->getAttrTextFromOrderItem($item,$sizeLabel);
//
//
//            //$qty = $item->getQtyOrdered() - max($item->getQtyShipped(), $item->getQtyInvoiced()) - $item->getQtyCanceled();
//            $qty = (int)$item->getQtyOrdered() ;
//            $qty=$qty>1?"*$qty":'';
//
//            $color=$color?"-$color":'';
//            $size=$size?"-$size":''; 
//            return $sku.$color.$size.$qty; ;
    }
    
    public function  getAttrTextFromOrderItem($item,$attrLabel)
    {
        $productOptions=$item->getProductOptions();
        if(isset($productOptions['options']))
        {
            $options = $productOptions['options'];
            foreach($options as $option){
                if(isset($option['label'])  && $attrLabel==$option['label']){
                    if(isset($option['value']))
                    {
                        return  $option['value'] ;
                    }else{
                        throw new Exception("coundn't get attribute text from order item");
                    }
                }
            }
        }else{
            //throw new Exception("coundn't get product options from order item");
        }
    }

    protected function _getCustomerOrderNo ()
    {
        $order=$this->_getOrder();
        return $order->getData('increment_id');
    }
    protected function _getPkgEnName ()
    {
        return 'Dresses/ Shirts/ Bikinis/ Pants/ Shorts';
    }

    protected function _getPkgCnName()
    {
        return '连衣裙/衬衫/比基尼/长裤/短裤';
    }
    protected function _getNum ()
    {
        return 1;
    }
    
    protected function _getCurrencyType ()
    {
        $shippingType=$this->_getPostType();
        if('EU-PACKET'===$shippingType || 'EU-PACKET-R'===$shippingType){
            return "GBP";
        }
        return "USD";
    }
    protected function _getUnitPrice ()
    {
        return 11;
    }
    protected function _getRealPrice ()
    {
        return 0;
    }
    protected function _map($fields)
{   
    foreach($fields as $field=>$flag){
        if(is_array($flag)){
            $val= $this->_map($flag);
        }elseif(self::FIELD_NOT_ASSIGNMENT===$flag){
            $val= '';
        }elseif(self::FIELD_ASSIGNMENT===$flag){
           $val= $this->_getAssignmentValue($field);
        }else{
            throw new Exception('unknown assignment flag in map');
        }
        $fields[$field]=$val;
    } 
    return $fields;
}
protected function _getProducingArea()
{
    return 'CN';//debug
}
protected function _getUsps3DaysIsReg()
{
    return 0;//debug
}
protected function _getAssignmentValue($field)
{
    $method="_get".$field;
    if(method_exists($this, $method)){
        return $this->$method();
    }else{ 
        throw new Martin_Flytcloud_Model_Exception_Assignment("do assigment failed:can't assing value to field $field,$method is not exists");
    }
}
protected function _getSignFlag()
{
    return 'scb.logistics.bellecat';
}

    protected $_map=array(
            'OrderUp'=>array(
                'CustomerId'=>self::FIELD_ASSIGNMENT,
                'Sign'=>self::FIELD_ASSIGNMENT,
                'SignFlag'=>self::FIELD_ASSIGNMENT,
                'OrderList'=>array(
                    'OrderInfo'=>array(
                        'Country'=>self::FIELD_ASSIGNMENT,
                        'PostType'=>self::FIELD_ASSIGNMENT,
                        'ReceiverName'=>self::FIELD_ASSIGNMENT,
                        'Email'=>self::FIELD_ASSIGNMENT,
                        'ReceiveAddr'=>self::FIELD_NOT_ASSIGNMENT,
                        'Address1'=>self::FIELD_ASSIGNMENT,
                        'Address2'=>self::FIELD_NOT_ASSIGNMENT,
                        'State'=>self::FIELD_ASSIGNMENT,
                        'City'=>self::FIELD_ASSIGNMENT,
                        'Zip'=>self::FIELD_ASSIGNMENT,
                        'Phone'=>self::FIELD_ASSIGNMENT,
                        'Remark'=>self::FIELD_ASSIGNMENT,
                        'PackType'=>self::FIELD_ASSIGNMENT,
                        'PackInfo1'=>self::FIELD_NOT_ASSIGNMENT,
                        'PackInfo1'=>self::FIELD_NOT_ASSIGNMENT,
                        'PackInfo3'=>self::FIELD_NOT_ASSIGNMENT,
                        'Usps3DaysIsReg'=>self::FIELD_ASSIGNMENT,  //?
                        'CustomerOrderNo'=>self::FIELD_ASSIGNMENT,

                        'PackageList'=>array(
                                 'PackageInfo'=>array(
                                 'PkgEnName'=>self::FIELD_ASSIGNMENT,
                                 'PkgCnName'=>self::FIELD_ASSIGNMENT,
                                 'Num'=>self::FIELD_ASSIGNMENT,
                                 'CurrencyType'=>self::FIELD_ASSIGNMENT,
                                 'UnitPrice'=>self::FIELD_ASSIGNMENT,
                                 'RealPrice'=>self::FIELD_ASSIGNMENT,
                                 'ProducingArea'=>self::FIELD_ASSIGNMENT,
                                 'HaikwanCode'=>self::FIELD_NOT_ASSIGNMENT,
                                 ),
                         ),
                    )
                )
            )
    );
    
    
    public function generateApiRequestXml(Mage_Sales_Model_Order $order){
        $mapFields=$this->_map;
        if(empty($mapFields)){
            throw new Exception("map for assignmet is empty");
        }
        $this->_setOrder($order);
        $xmlArray=$this->_map($mapFields);
        return $this->_arraytoxml($xmlArray); // 调用生成XML方法
    }
    public function _arraytoxml($arr, $root = false, $header = true) {
	if (! function_exists('is_hash')) {
		function is_hash($var) {
			return ( is_array($var) && array_keys($var) !== range(0, count($var)-1) );
		}
	}
	if (! function_exists('normalize_array2xml')) {
		function normalize_array2xml($arr, $level = 0) {
			if (is_object($arr)) $arr = get_object_vars($arr);

			for ($i = 0, $tabs = ''; $i < $level; $i++) $tabs .= "\t";
			$output = array();

			foreach($arr as $k => $v) {
				if (is_null($v)) {
					continue;
				}
				elseif (is_bool($v)) {
					$value = ($v === true ? 'TRUE' : 'FALSE');
					$output[] = $tabs . sprintf('<%1$s>%2$s</%1$s>', $k, $value);
				}
				elseif ($v === '') {
					$output[] = $tabs . sprintf('<%1$s/>', $k);
				}
				elseif (is_scalar($v)) {
					$value = $v;
					$output[] = $tabs . sprintf('<%1$s>%2$s</%1$s>', $k, htmlspecialchars($value));
				}
				elseif (is_hash($v)) {
					$value = normalize_array2xml($v, $level + 1);
					$output[] = $tabs . sprintf('<%1$s>%2$s</%1$s>', $k, "\n{$value}\n{$tabs}");
				}
				elseif (is_array($v)) {
					foreach($v as $w) {
						$output[] = normalize_array2xml(array($k => $w), $level);
					}
				}
			}
			return implode("\n", $output);
		}
	}

	if ($root) {
		$arr = array((string) $root => $arr);
	}
	$xml = normalize_array2xml($arr);
	if ($header) {
		$xml = '<?xml version="1.0" encoding="utf-8"?>' . "\n" . $xml;
	}

	return $xml;
    }
}
