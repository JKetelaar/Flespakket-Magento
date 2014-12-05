<?php
class Tig_Flespakket_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function getConfig($value, $group = 'general', $decrypt = false, $storeId = null)
    {
        if(strpos($group, 'flespakket_') !== 0)
        {
            $group = 'flespakket_' . $group;
        }
        if(empty($storeId)) // backend works as 1 store, pass the $order->getStoreId()
        {
            $storeId = Mage::app()->getStore()->getId(); // in case of frontend calls
        }
        $config = Mage::getStoreConfig('flespakket/' . $group . '/' . $value, $storeId);

        if($decrypt)
        {
            $config = Mage::helper('core')->decrypt($config);
        }

        if(Mage::app()->getStore()->isCurrentlySecure())
        {
            $config = str_replace('http://', 'https://', $config);
        }

        return trim($config);
    }

    public function getBaseUrl()
    {
        $url = Mage::getBaseUrl();

        if(Mage::app()->getStore()->isCurrentlySecure())
        {
            $url = str_replace('http://', 'https://', $url);
        }
        return $url;
    }

    private function _encodeConsignmentRecordValue($str)
    {
        return str_replace(array('%', ',', '-'), array('%25', '%2C', '%2D'), $str);
    }

    public function getConsignmentRecordString($consignmentRecords)
    {
        $ret = array();
        foreach($consignmentRecords as $rec)
        {
            foreach($rec as $i => $value)
            {
                $rec[$i] = $this->_encodeConsignmentRecordValue($value);
            }
            $ret[] = implode('-', $rec);
        }
        return implode(',', $ret);
    }

    public function getConsignmentRecords($str)
    {
        if(is_object($str))
        {
            // get the full order, $row has limited data since Magento 1.6
            $order = Mage::getModel('sales/order')->load($str->getId());
            $str = $order->getData('flespakket_consignment_ids');
        }
        $ret = array();
        foreach('' == $str ? array() : explode(',', $str) as $recStr)
        {
            $rec = array();
            foreach(explode('-', $recStr) as $value)
            {
                $rec[] = urldecode($value);
            }

            $consignmentId = $rec[0];

            // If consignmentId starts with 't', then the consignment still has not been
            // processed by Flespakket, and so the consignment ID is not yet known
            //
            if ('t' != $consignmentId[0])
            {
                $consignmentId = (int) $consignmentId; // real consignment ID
                if (!$consignmentId) continue; // Corrupt data
            }

            $ret[$consignmentId] = array(
                'consignmentId'     => $consignmentId,
                'isRetour'          => (bool) (isset($rec[1]) ? $rec[1] : 0),
                'trackTrace'        => (isset($rec[2]) && '' != $rec[2]) ? $rec[2] : null,
                'postcode'          => (isset($rec[3]) && '' != $rec[3]) ? $rec[3] : null,
                'status'            => (isset($rec[4]) && '' != $rec[4]) ? $rec[4] : null,
                'statusIsFinal'     => (bool) (isset($rec[5]) ? $rec[5] : 0),
                'statusLastChecked' => (isset($rec[6]) && '' != $rec[6]) ? (int) $rec[6] : null, // timestamp
            );
        }
        return $ret;
    }

    public function getProcessedConsignmentRecords($str)
    {
        foreach($ret = $this->getConsignmentRecords($str) as $consignmentId => $record)
        {
            if('t' == $consignmentId[0])
            {
                unset($ret[$consignmentId]);
            }
        }
        return $ret;
    }

    public function getConsignmentIds($str)
    {
        return array_keys($this->getProcessedConsignmentRecords($str));
    }

    public function getTrackTraceLink($tracktrace, $postcode, $country = 'NL')
    {
        if($country == 'NL')
        {
            $url = $this->getConfig('tracktrace_url_nl') . http_build_query(array(
                'lang' => 'nl',
                'B'    => $tracktrace,
                'P'    => $postcode,
            ));
        }
        else
        {
            $url = $this->getConfig('tracktrace_url_eu') . implode('/', array(
                $tracktrace,
                strtoupper($country),
                $postcode,
            ));
        }
        return $url;
    }

    public function getRetourLink($consignment_id, $tracktrace, $order)
    {
        $retour_hash = hash('sha1', $consignment_id . 'M4tini@Tig' . $this->getConfig('api_key', 'general', true));

        $url = $this->getConfig('flespakket_url') . '/retourzending-etiket/' . $consignment_id . '/' . $retour_hash;

        return $url;
    }

    public function sendTransactionalEmail($order, $consignmentRecord)
    {
        $api_key = $this->getConfig('api_key', 'general', true);
        $storeId = $order->getStoreId();

        $active = $this->getConfig('tracktrace', 'general', false, $storeId);
        $templateId = (int) $this->getConfig('tracktrace_template', 'general', false, $storeId);

        if($active == 1 && !empty($api_key) && $templateId != 0
        && isset($consignmentRecord['consignmentId']) && !empty($consignmentRecord['consignmentId']))
        {
            $sender = Mage::helper('flespakket')->getConfig('tracktrace_identity', 'general', false, $storeId);

            $recepientEmail = $order->getShippingAddress()->getEmail();
            if(empty($recepientEmail))
            {
                $recepientEmail = $order->getBillingAddress()->getEmail();
            }
            $recepientName  = $order->getShippingAddress()->getName();

            $tracktrace = $this->getTrackTraceLink($consignmentRecord['trackTrace'], $consignmentRecord['postcode'], $order->getShippingAddress()->getCountryId());
            $retourlink = $this->getRetourLink($consignmentRecord['consignmentId'], $consignmentRecord['trackTrace'], $order);

            $vars = array(
                'tracktrace_url'  => $tracktrace,
                'retourlabel_url' => $retourlink,
                'order'           => $order,
            );
            $storeId = Mage::app()->getStore()->getId();

            Mage::getModel('core/email_template')->sendTransactional($templateId, $sender, $recepientEmail, $recepientName, $vars, $storeId);

            $bcc = $this->getConfig('tracktrace_bcc', 'general', false, $storeId);
            $bccEmail = $this->getConfig('tracktrace_bcc_email', 'general', false, $storeId);
            if($bcc == 1 && !empty($bccEmail))
            {
                Mage::getModel('core/email_template')->sendTransactional($templateId, $sender, $bccEmail, '', $vars, $storeId);
            }

            return true;
        }
        return false;
    }
}
