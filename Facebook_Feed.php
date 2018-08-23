<?php
include('app/Mage.php');
Mage::app();

$argv = $_SERVER['argv'];

$ob = Mage::getModel('Facebook_AdsToolbox/observer');
$obins = new $ob;
$use_cache = false;
if( $argv['1'] == 0 ){
    $store_id = null;
}else {
    $store_id = $argv['1'];
}
$currency = $argv['2'];
$obins->internalGenerateFacebookProductFeed(false, $use_cache,$store_id,$currency);

echo 'success';
