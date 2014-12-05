<?php
class Tig_Flespakket_Block_Return extends Tig_Flespakket_Block_Abstract
{
    protected function getConsignmentId()
    {
        return (int) $this->getRequest()->getParam('consignment_id');
    }

    private function _getOrder()
    {
        if(!$orderId = (int) $this->getRequest()->getParam('order_id')) { throw new Exception("'order_id' not set");         }
        if(!$ret     = Mage::getModel('sales/order')->load($orderId))   { throw new Exception("Order '$orderId' not found"); }

        return $ret;
    }

    protected function isRetour()
    {
        $consignmentRecords = Mage::helper('flespakket')->getConsignmentRecords($this->_getOrder());
        $consignmentId = $this->getConsignmentId();

        if(!isset($consignmentRecords[$consignmentId]))
        {
            throw new Exception("Consigment Id '{$consignmentId}' not found");
        }
        return $consignmentRecords[$consignmentId]['isRetour'];
    }
}
