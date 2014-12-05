<?php
class Tig_Flespakket_Block_Post extends Tig_Flespakket_Block_Abstract
{
    protected function getReturnUrl()
    {
        return Mage::helper('flespakket')->getBaseUrl() . 'flespakket/index/return/order_id/' . $this->getOrderId() . '/timestamp/' . $this->getTimestamp();
    }

    protected function getFlespakketPostUrl()
    {
        $returnUrlEncoded = urlencode($this->getReturnUrl());

        $pluginAction = $this->getRequest()->getParam('is_retour') ? 'verzending-aanmaken-retour' : 'verzending-aanmaken';

        return Mage::helper('flespakket')->getConfig('flespakket_url') . '/plugin/' . $pluginAction . '/' . $this->getOrderId() . '?return_url=' . $returnUrlEncoded;
    }

    /**
     * Recursive
     */
    private function _getFormParams($requestData)
    {
        $ret = array();
        foreach ($requestData as $param => $value)
        {
            if (!is_array($value))
            {
                $ret[$param] = $value;
                continue;
            }
            foreach ($this->_getFormParams($value) as $param2 => $value2)
            {
                $ret[preg_replace('/^([^\[]*)(.*)/', $param . '[$1]$2', $param2)] = $value2;
            }
        }
        return $ret;
    }

    protected function getFormParams()
    {
        return $this->_getFormParams(array_diff_key($this->getRequest()->getParams(), array_fill_keys(array('order_id', 'timestamp', 'is_retour'), 1)));
    }
}
