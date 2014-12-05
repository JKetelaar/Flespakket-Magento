<?php
class Tig_Flespakket_Block_Widget_Column extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    private function _getLabelLink($consignmentId)
    {
        return Mage::helper('flespakket')->getBaseUrl() . 'flespakket/index/getlabel/' . $consignmentId;
    }

    private function _getTrackTraceLink($record, $order)
    {
        if ('' == $record['trackTrace']) return '';

        $status = htmlspecialchars($record['status'] != '' ? $record['status'] : 'Track&Trace');

        $postcode = empty($record['postcode']) ? $order->getShippingAddress()->getPostcode() : $record['postcode'];
        $url = Mage::helper('flespakket')->getTrackTraceLink($record['trackTrace'], $postcode, $order->getShippingAddress()->getCountryId());

        return '<a href="' . $url . '" target="_blank">' . $status . '</a>';
    }

    private function _getIcon($isRetour)    { return $isRetour ? 'retour'     : 'pdf'; }
    private function _getAltText($isRetour) { return $isRetour ? 'Retour PDF' : 'PDF'; }

    public function _getValue(Varien_Object $row)
    {
        // get the full order, $row has limited data since Magento 1.6
        $order = Mage::getModel('sales/order')->load($row->getId());

        $pdfLabels = array();
        foreach (Mage::helper('flespakket')->getProcessedConsignmentRecords($order) as $record)
        {
            $skinUrl = $this->getSkinUrl('images/flespakket/' . $this->_getIcon($record['isRetour']) . '.png');
            $pdfLabels[] = <<<HTML
                <div style="width:160px;">
                    <a
                        href="{$this->_getLabelLink($record['consignmentId'])}"
                        onclick="return false;"
                        style="text-decoration:none;"
                        class="flespakket-consignment-label"
                        data-consignment-id="{$record['consignmentId']}"
                        >
                        <img
                            src="{$skinUrl}"
                            style="width:18px;height:20px;vertical-align:bottom"
                            alt="{$this->_getAltText($record['isRetour'])}"
                            />
                    </a>
                    {$this->_getTrackTraceLink($record, $order)}
                </div>
HTML;
        }
        return implode(' ', $pdfLabels);
    }
}
