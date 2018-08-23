<?php

class Martin_Flytcloud_Helper_Shipment extends Mage_Core_Helper_Abstract
{

    public function getShipmentByOrderId($orderId)
    {
        $shipmentCollection=Mage::getModel('sales/order_shipment')->getCollection()
                ->addFieldToFilter('order_id',$orderId);
        return $shipmentCollection->fetchItem();
    }
    
    public function saveShipment($shipment)
    {
        $shipment->getOrder()->setIsInProcess(true);
        $transactionSave = Mage::getModel('core/resource_transaction')
            ->addObject($shipment)
            ->addObject($shipment->getOrder())
            ->save();

        return $this;
    }
    protected function _getItemQtys($order)
    {
        $qtys=array();
        foreach($order->getAllVisibleItems() as $item)
        {
            $options=$item->getProductOptions();
            $qtys[$item->getId()]=$options['info_buyRequest']['qty'];
        }
       return $qtys;
    }
    public function initShippment($shipmentId,$orderId,$tracks)
    {
        $shipment = false;
        if ($shipmentId) {
            $shipment = Mage::getModel('sales/order_shipment')->load($shipmentId);
        } elseif ($orderId) {
            $order      = Mage::getModel('sales/order')->load($orderId);

            /**
             * Check order existing
             */
            if (!$order->getId()) {
                throw new Exception($this->__('The order no longer exists.'));
            }
            /**
             * Check shipment is available to create separate from invoice
             */
            if ($order->getForcedDoShipmentWithInvoice()) {
                throw new Exception($this->__('Cannot do shipment for the order separately from invoice.'));
            }
            /**
             * Check shipment create availability
             */
            if (!$order->canShip()) {
                throw new Exception($this->__('Cannot do shipment for the order.'));
            }
            $savedQtys = $this->_getItemQtys($order);
            $shipment = Mage::getModel('sales/service_order', $order)->prepareShipment($savedQtys);

            if ($tracks) {
                foreach ($tracks as $data) {
                    if (empty($data['number'])) {
                        Mage::throwException($this->__('Tracking number cannot be empty.'));
                    }
                    $track = Mage::getModel('sales/order_shipment_track')
                        ->addData($data);
                    $shipment->addTrack($track);
                }
            }
        }
        return $shipment;  
    }
}
