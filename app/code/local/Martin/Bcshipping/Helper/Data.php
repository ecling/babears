<?php
class Martin_Bcshipping_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function calculate($purchase_price,$shipping_cost,$weight,$country){
        $bcshipping = $this->getShippingCoseByCountry($weight,$country);
        $product_price = $purchase_price+$shipping_cost+($weight*$bcshipping->getPrice())+$bcshipping->getAdditionalPrice();
        $rate = Mage::getStoreConfig('bcshipping/general/rate');
        $product_price = number_format($product_price*2/$rate,'2');
        return $product_price;
    }

    public function calculateByProduct($product,$country){
        $purchase_price = $product->getPurchasePrice();
        $shipping_cost = $product->getShippingCost();
        $weight = $product->getWeight();
        return $this->calculate($purchase_price,$shipping_cost,$weight,$country);
    }

    public function getShippingCoseByCountry($weight,$country){
        $bcshipping = Mage::getModel('bcshipping/price')->loadByCountry($weight,$country);
        if(!$bcshipping->getId()){
            $bcshipping = Mage::getModel('bcshipping/price')->loadByCountry($weight,'NL');
        }
        return $bcshipping;
    }

    public function getBasePrice($purchase_price,$shipping_cost,$weight,$country='NL'){
        $country = $rate = Mage::getStoreConfig('bcshipping/general/country');
        $price = $this->calculate($purchase_price,$shipping_cost,$weight,$country);
        if($price<15){
            $price = $price-0.99;
        }

        if($price>=15&&$price<29){
            $price = $price-1.99;
        }

        if($price>=29&&$price<39){
            $price = $price-2.99;
        }

        if($price>=39&&$price<49){
            $price = $price-3.99;
        }

        if($price>=49){
            $price = $price-4.99;
        }
        return $price;
    }
    public function getBasePriceByProuct($product,$country='NL'){
        $purchase_price = $product->getPurchasePrice();
        $shipping_cost = $product->getShippingCost();
        $weight = $product->getWeight();
        $country = $rate = Mage::getStoreConfig('bcshipping/general/country');
        return $this->getBasePrice($purchase_price,$shipping_cost,$weight,$country);
    }
}