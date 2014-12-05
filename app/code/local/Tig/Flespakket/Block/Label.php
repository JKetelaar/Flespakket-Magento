<?php
class Tig_Flespakket_Block_Label extends Tig_Flespakket_Block_Abstract
{
    protected function getConsignmentIds()
    {
        if($consignmentId = (int) $this->getRequest()->getParam('consignment_id'))
        {
            return $consignmentId;
        }
        $ret = array();
        foreach(explode('|', $this->getRequest()->getParam('order_ids')) as $orderId)
        {
            if(!$order = Mage::getModel('sales/order')->load((int) $orderId))
            {
                throw new Exception("Order '$orderId' not found");
            }
            $ret = array_merge($ret, Mage::helper('flespakket')->getConsignmentIds($order));
        }
        if (!count($ret)) return;
        return implode('|', $ret);
    }

    protected function getLabelUrl()
    {
        return Mage::helper('flespakket')->getConfig('flespakket_url') . '/plugin/genereer-pdf/consignments/' . $this->getConsignmentIds();
    }
}
