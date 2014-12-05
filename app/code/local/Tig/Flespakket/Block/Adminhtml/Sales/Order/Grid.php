<?php
class Tig_Flespakket_Block_Adminhtml_Sales_Order_Grid extends Mage_Adminhtml_Block_Sales_Order_Grid
{
    public function getColumnsOrder()
    {
        return array_merge(parent::getColumnsOrder(), array(
            'flespakket_package' => 'status',
            'flespakket_labels'  => 'flespakket_package',
            'flespakket'         => 'flespakket_labels',
        ));
    }

    /**
     * Get latest status from Flespakket of all the consignments on this page
     *
     * Every time this function is called, it will check if an update of the order statuses is required.
     * Depending on the last update with a timeout, since PostNL updates our status 3 times a day anyway.
     *
     * NOTE - Increasing this timeout is pointless, please save our bandwidth and use the Track&Trace link to get the actual status. Thanks!
     */
    protected function updateFlespakketStatus()
    {
        if(0 == Mage::helper('flespakket')->getConfig('status_updates'))
        {
            return false;
        }
        $lastUpdated = (int) Mage::getStoreConfig('flespakket/status_last_updated_on');

        $consignmentIds  = array();
        $consignmentData = array();
        foreach($this->getCollection() as $order)
        {
            $consignmentData[$order->getId()] = array(
                'consignmentRecords' => $consignmentRecords = Mage::helper('flespakket')->getConsignmentRecords($order),
                'order'              => $order,
            );
            foreach($consignmentRecords as $consignmentId => $rec)
            {
                if ('t' == $consignmentId[0])                  continue; // Skip unprocessed consignments
                if ($rec['statusIsFinal'])                     continue;
                if (time() - $rec['statusLastChecked'] < 3600) continue;
                $consignmentIds[$consignmentId] = $order->getId();
            }
        }

        if(count($consignmentIds))
        {
            $statusFile = file($url = Mage::helper('flespakket')->getConfig('flespakket_url') . '/status/tnt/' . implode('|', array_keys($consignmentIds)));
            if(false === $statusFile)
            {
                Mage::log('Error getting consignment status from Flespakket');
                return;
            }
            foreach($statusFile as $row)
            {
                $row = explode('|', $row . '|');
                if (count($row) != 4) return;

                $consignmentId      = (int) $row[0];
                $orderId            = $consignmentIds[$consignmentId];
                $consignmentRecords = $consignmentData[$orderId]['consignmentRecords'];
                $order              = $consignmentData[$orderId]['order'];
                $status             = trim($row[2]);
                $statusIsFinal      = (int) $row[1];

                $consignmentRecords[$consignmentId]['status']            = $status;
                $consignmentRecords[$consignmentId]['statusIsFinal']     = $statusIsFinal;
                $consignmentRecords[$consignmentId]['statusLastChecked'] = time();

                $order->setData('flespakket_consignment_ids', Mage::helper('flespakket')->getConsignmentRecordString($consignmentRecords))
                      ->save()
                      ;
            }
        }
        return $this;
    }

    protected function _prepareMassaction()
    {
        parent::_prepareMassaction();

        $this->getMassactionBlock()
             ->addItem('process_with_flespakket', array(
                 'label' => Mage::helper('flespakket')->__('Process With Flespakket'),
                 // No 'url' key - to stop the page submitting a form back to the webshop
             ))
             ->addItem('print_flespakket_labels', array(
                 'label' => Mage::helper('flespakket')->__('Print Flespakket Labels'),
                 // No 'url' key - to stop the page submitting a form back to the webshop
             ));

        return $this;
    }

    protected function _afterLoadCollection()
    {
        $this->updateFlespakketStatus();
        return parent::_afterLoadCollection();
    }

    protected function _prepareColumns()
    {
        $this->prepareFlespakketColumns();
        return parent::_prepareColumns();
    }

    protected function prepareFlespakketColumns()
    {
        $this->addColumn('flespakket_package', array(
            'filter'   => false,
            'header'   => Mage::helper('flespakket')->__('Verpakking'),
            'index'    => 'flespakket_package',
            'renderer' => 'flespakket/widget_package',
            'sortable' => false,
            'type'     => 'text',
            'width'    => '100px',
        ));
        $this->addColumn('flespakket_labels', array(
            'filter'   => false,
            'header'   => Mage::helper('flespakket')->__('Flespakket Labels'),
            'index'    => 'flespakket_consignment_ids',
            'renderer' => 'flespakket/widget_column',
            'sortable' => false,
            'type'     => 'text',
            'width'    => '160px',
        ));
        $this->addColumn('flespakket', array(
            'filter'   => false,
            'header'   => Mage::helper('flespakket')->__('Flespakket'),
            'index'    => 'flespakket',
            'renderer' => 'flespakket/widget_addLabelColumn',
            'sortable' => false,
            'type'     => 'text',
            'width'    => '50px',
        ));
        return $this;
    }
}
