<?php
class Tig_Flespakket_Block_Batch extends Tig_Flespakket_Block_Abstract
{
    private $_AddLabelColumn;

    public function __construct()
    {
        // we need this widget to re-use address modifications
        $this->_AddLabelColumn = new Tig_Flespakket_Block_Widget_AddLabelColumn();
    }

    protected function getReturnUrl()
    {
        return Mage::helper('flespakket')->getBaseUrl() . 'flespakket/index/batchreturn/timestamp/' . $this->getTimestamp();
    }

    protected function getFlespakketPostUrl()
    {
        $returnUrlEncoded = urlencode($this->getReturnUrl());
        return Mage::helper('flespakket')->getConfig('flespakket_url') . '/plugin/verzending-batch/?return_url=' . $returnUrlEncoded;
    }

    /*
     * input:  array("ToAddress['foo']" => "bar")
     * output: array('ToAddress' => array('foo' => 'bar'))
     */
    private function _convertFormParams($consignmentData)
    {
        $res = array();
        foreach($consignmentData as $key => $value)
        {
            $matches = array();
            preg_match('/([^\[]*)(.*)/', $key, $matches);
            if(empty($matches[2]))
            {
                $res[$key] = $value;
            }
            else
            {
                $subkey = str_replace(array('[',']'), '', $matches[2]);
                $res[$matches[1]][$subkey] = $value;
            }
        }
        return $res;
    }

    protected function getFormParams()
    {
        if (!$orderIds      = $this->getRequest()->getParam('order_ids')) { throw new Exception("'order_ids' not set"); }
        $orderPackages = $this->getRequest()->getParam('order_packages');

        $orderIds      = (strpos($orderIds,      '|') !== false) ? explode('|', $orderIds)      : array($orderIds);
        $orderPackages = (strpos($orderPackages, '|') !== false) ? explode('|', $orderPackages) : array($orderPackages);
        $formParams    = array();

        foreach($orderIds as $key => $orderId)
        {
            if ($order = Mage::getModel('sales/order')->load($orderId))
            {
                $address = $order->getShippingAddress();
                $address->setEmail($order->getCustomerEmail());

                $consignmentData = $this->_AddLabelColumn->_getConsignmentData($address);
                $consignmentData['package'] = $orderPackages[$key];
                $consignment     = $this->_convertFormParams($consignmentData);

                $formParams[$orderId] = serialize($consignment);
            }
        }
        return $formParams;
    }

    public function _getAddressComponents($address)
    {
        // redirect because Magento looks here for some reason
        return $this->_AddLabelColumn->_getAddressComponents($address);
    }
}
