<?php

class Martin_Flytcloud_Model_Observer
{
    public function saveTracks($observer)
    {
        try{
            $order = $observer->getEvent()->getOrder();
            $tracks= $observer->getEvent()->getTracks();
            
            $shippmentId=null;
            $orderId=null;
            if($order)
            {
                $orderId=$order->getId();
            }
            $shipmentHelper=Mage::helper('flytcloud/shipment');
            $shippment=$shipmentHelper->getShipmentByOrderId($orderId);
            if($shippment)
            {
                $shippmentId=$shippment->getId();
            }
            $shipment=$shipmentHelper->initShippment($shippmentId, $orderId, $tracks);
            if(!$shipment->getId())$shipment->register();
            
            
            $shipmentHelper->saveShipment($shipment);
            $comment='';json_encode($tracks);
            foreach($tracks as $track)
            {
                $comment.=isset($track['number'])?$track['number']."\r\n":null;
            }
            Mage::log($comment,null,'flytcloud_upload.log');
            $shipment->sendEmail(true, $comment);
        } catch (Exception $ex) {
            Mage::log($ex,null,'flytcloud_upload.log');
           //var_dump($ex);exit;
        }

    }
}

        
