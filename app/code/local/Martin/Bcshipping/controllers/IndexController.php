<?php
class Martin_Bcshipping_IndexController extends Mage_Core_Controller_Front_Action
{
    public function indexAction(){
        //$test = Mage::getModel('bcshipping/price')->loadByCountry(1000,'UA');

        $product = Mage::getModel('catalog/product')->load(192);
        $price = Mage::helper('bcshipping')->calculate($product,'US');
        var_dump($price);
    }
}