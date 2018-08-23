<?php
/**
 * Created by PhpStorm.
 * User: pc
 * Date: 2017/10/6
 * Time: 20:12
 */

include('app/Mage.php');
Mage::app();

$adapter = Mage::getSingleton('core/resource')->getConnection('core_write');

$date = date('Y-m-d',time()-3600*24*5);

$result = $adapter->query("SELECT product_id,SUM(qty_ordered) AS hot FROM sales_flat_order_item WHERE created_at>'".$date."' GROUP BY product_id");
$rows = $result->fetchAll();

$i = 0;
foreach($rows as $row){
    $i++;
    $top_sales_result = $adapter->query("select * from product_top_sales_month where item_id=".$i);
    $top_sales = $top_sales_result->fetch();
    if($top_sales){
        $where = 'item_id='.$i;
        $set = array(
            'product_id'=> $row['product_id'],
            'qty_ordered'=> $row['hot']
        );
        $adapter->update("product_top_sales_month",$set,$where);
    }else{
        $row = array(
            'item_id' => $i,
            'product_id'=>$row['product_id'],
            'qty_ordered'=>$row['hot']
        );
        $adapter->insert("product_top_sales_month",$row);
    }
}

if($i>0) {
    $where = 'item_id>'.$i;
    $adapter->delete('product_top_sales_month',$where);
}


