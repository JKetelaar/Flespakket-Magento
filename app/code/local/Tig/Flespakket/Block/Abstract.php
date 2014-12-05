<?php
class Tig_Flespakket_Block_Abstract extends Mage_Core_Block_Template
{
    protected function getOrderId()
    {
        return (int) $this->getRequest()->getParam('order_id');
    }

    protected function getTimestamp()
    {
        return (int) $this->getRequest()->getParam('timestamp');
    }
}
