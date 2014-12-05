<?php
class Tig_Flespakket_Block_Widget_AddLabelColumn extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
     * Split address such as 'Rechtboomssloot 97-I' into street, house number and toevoeging
     *
     * @return array
     */
    public function _getAddressComponents($address)
    {
        $ret = array();
        $ret['house_number']    = '';
        $ret['number_addition'] = '';

        $address = str_replace(array('?', '*', '[', ']', ',', '!'), ' ', $address);
        $address = preg_replace('/\s\s+/', ' ', $address);

        preg_match('/^([0-9]*)(.*?)([0-9]+)(.*)/', $address, $matches);

        if (!empty($matches[2]))
        {
            $ret['street']          = trim($matches[1] . $matches[2]);
            $ret['house_number']    = trim($matches[3]);
            $ret['number_addition'] = trim($matches[4]);
        }
        else // no street part
        {
            $ret['street'] = $address;
        }
        return $ret;
    }

    public function _getConsignmentDataHtml($consignmentData)
    {
        return htmlspecialchars(http_build_query($consignmentData));
    }

    public function _getConsignmentData($address, $backupEmail = '')
    {
        $streetArray  = $address->getStreet();
        $streetString = implode(' ', $streetArray);
        $emailString  = $address->getEmail();
        if(empty($emailString))
        {
            $emailString = $backupEmail;
        }

        $consignmentData = array();
        $consignmentData['ToAddress[name]']         = $address->getName();
        $consignmentData['ToAddress[business]']     = $address->getCompany();
        $consignmentData['ToAddress[town]']         = $address->getCity();
        $consignmentData['ToAddress[email]']        = $emailString;
        $consignmentData['ToAddress[phone_number]'] = $address->getTelephone();
        $consignmentData['ToAddress[country_code]'] = $address->getCountry();
        if ('NL' == $address->getCountry())
        {
            $consignmentData['ToAddress[postcode]'] = $address->getPostcode();
            foreach ($this->_getAddressComponents($streetString) as $component => $value)
            {
                $consignmentData["ToAddress[$component]"] = $value;
            }
        }
        else
        {
            $consignmentData['ToAddress[eps_postcode]'] = $address->getPostcode();
            $consignmentData['ToAddress[street]']       = $streetString;
        }
        $consignmentData['plugin_id']               = $address->getOrder()->getRealOrderId();
        return $consignmentData;
    }

    public function _getValue(Varien_Object $row)
    {
        if ($address = $row->getShippingAddress())
        {
            $backupEmail     = $row->getBillingAddress()->getEmail();
            $consignmentData = $this->_getConsignmentData($address, $backupEmail);
        }
        if(empty($consignmentData))
        {
            return '';
        }
        return <<<HTML
            <div>
                <a
                    href="#"
                    onclick="return false;"
                    class="flespakket-consignment-new"
                    data-id="{$row->getId()}"
                    data-consignment="{$this->_getConsignmentDataHtml($consignmentData)}"
                    ><img src="{$this->getSkinUrl('images/flespakket/pdf_add.png')}" style="width:18px height:20px" alt="PDF aanmaken" /></a>
                <a
                    href="#"
                    onclick="return false;"
                    class="flespakket-consignment-retour-new"
                    data-id="{$row->getId()}"
                    ><img src="{$this->getSkinUrl('images/flespakket/retour_add.png')}" style="width:18px height:20px" alt="Retour PDF aanmaken" /></a>
            </div>
HTML;
    }
}
