<?php
include('app/Mage.php');
Mage::app();

$adapter = Mage::getSingleton('core/resource')->getConnection('core_write');

$crontab_result = $adapter->query("SELECT * FROM crontab");

$crontab = $crontab_result->fetch();

$rate = Mage::getStoreConfig('bcshipping/general/rate');

if($crontab){
    $first_id = $crontab['node'];
    $colleciton_result = $adapter->query("SELECT * FROM catalog_product_entity WHERE entity_id>".$first_id." LIMIT 100");
    $collection = $colleciton_result->fetchAll();
    foreach ($collection as $item) {
        $product_id = $item['entity_id'];
        $cost = 0;
        $shipping_cost = 0;
        $weight = 0;
        $old_price = 0;
        $price_value_id = 0;
        //$product = Mage::getModel('catalog/product')->load($product_id);
        //155采购价，156运费，80重量,75价格
        $attribute_result = $adapter->query("SELECT * FROM `catalog_product_entity_decimal` WHERE entity_id=".$product_id." AND store_id=0 AND attribute_id IN (155,156,80,75)");
        $attributes = $attribute_result->fetchAll();
        foreach ($attributes as $attribute){
            switch ($attribute['attribute_id']){
                case 155:
                    $cost = $attribute['value'];
                    break;
                case 156:
                    $shipping_cost = $attribute['value'];
                    break;
                case 80:
                    $weight = $attribute['value'];
                    break;
                case 75:
                    $old_price = $attribute['value'];
                    $price_value_id = $attribute['value_id'];
                    break;
            }
        }

        if($weight>0&&$cost>0&&$shipping_cost>0){
            /*
            $bcshipping = Mage::helper('bcshipping')->getShippingCoseByCountry($weight,'NL');
            $price = $cost+$shipping_cost+($weight*$bcshipping->getPrice())+$bcshipping->getAdditionalPrice();
            $price = number_format($price*2/$rate,'2');

            if($price<6){
                $price = $price-1;
            }

            if($price>=6&&$price<=20){
                $price = $price-3;
            }

            if($price>20){
                $price = $price-5;
            }
            */

            $price = Mage::helper('bcshipping')->getBasePrice($cost,$shipping_cost,$weight);

            if($price>0 && $price!=$old_price && $price_value_id>0){
                $set = array('value'=>$price);
                $where = 'value_id='.$price_value_id;
                $adapter->update("catalog_product_entity_decimal",$set,$where);
            }
        }

        $last_id = $product_id;
    }

    if(isset($last_id)) {
        $set = array('node' => $last_id);
        $where = 'crontab_id=1';
        $adapter->update('crontab', $set, $where);
    }
}


