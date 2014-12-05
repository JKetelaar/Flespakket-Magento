<?php
class Tig_Flespakket_IndexController extends Mage_Core_Controller_Front_Action
{
    public function testAction()
    {
        die('Hello world');
    }

    /**
     * Send consignment request to Flespakket
     *
     * Store the 'timestamp' parameter - the unique identifier for this request, for this order
     */
    public function postAction()
    {
        if (!$orderId   = (int) $this->getRequest()->getParam('order_id'))  { throw new Exception("'order_id' not set");  }
        if (!$timestamp = (int) $this->getRequest()->getParam('timestamp')) { throw new Exception("'timestamp' not set"); }
        if (!$order = Mage::getModel('sales/order')->load($orderId)) { throw new Exception("Order '$orderId' not found"); }

        $consignmentRecords   = Mage::helper('flespakket')->getProcessedConsignmentRecords($order); // Remove any unprocessed records
        $consignmentRecords[] = array(
            'consignmentId' => "t$timestamp"                                          , // Timestamp is identified by prefix 't'
            'isRetour'      => $this->getRequest()->getParam('is_retour') ? '1' : '0' ,
        );
        $order->setData('flespakket_consignment_ids', Mage::helper('flespakket')->getConsignmentRecordString($consignmentRecords))
              ->save()
              ;
        $this->loadLayout();
        $this->renderLayout();
    }

    public function labelAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    /**
     * Record processed consignment ID, and update 'timestamp' for this order to be the consignment ID
     */
    public function returnAction()
    {
        if (!$orderId       = (int) $this->getRequest()->getParam('order_id'))       { throw new Exception("'order_id' not set");         }
        if (!$timestamp     = (int) $this->getRequest()->getParam('timestamp'))      { throw new Exception("'timestamp' not set");        }
        if (!$consignmentId = (int) $this->getRequest()->getParam('consignment_id')) { throw new Exception("'consignment_id' not set");   }
        if (!$trackTrace    = $this->getRequest()->getParam('tracktrace'))           { throw new Exception("'tracktrace' not set");       }
        if (!$order         = Mage::getModel('sales/order')->load($orderId))         { throw new Exception("Order '$orderId' not found"); }

        $postcode = (string) $this->getRequest()->getParam('postcode'); // not required for foreign shipments

        $consignmentRecords = Mage::helper('flespakket')->getConsignmentRecords($order);
        $newRecord = array(
            'consignmentId' => $consignmentId,
            'isRetour'      => null,
            'trackTrace'    => $trackTrace,
            'postcode'      => $postcode,
        );
        Mage::helper('flespakket')->sendTransactionalEmail($order, $newRecord);

        if (false !== array_search($consignmentId, array_keys($consignmentRecords), true))
        {
            // This consignmentId is already known
        }
        else if (false !== array_search($key = "t$timestamp", array_keys($consignmentRecords), true)) // Timestamp is identified by prefix 't'
        {
            // Successful processing of consignment request
            $newRecord['isRetour']    = $consignmentRecords[$key]['isRetour'];
            $consignmentRecords[$key] = $newRecord; // Overwrite timestamp with consignment ID
        }
        else
        {
            // No record of this consignment request, but save the consignment ID anyway
            $consignmentRecords[] = $newRecord;
        }
        $order->setData('flespakket_consignment_ids', $s = Mage::helper('flespakket')->getConsignmentRecordString($consignmentRecords))
              ->save()
              ;

        $this->loadLayout();
        $this->renderLayout();
    }

    /**
     * Send multiple consignment requests to Flespakket
     */
    public function batchAction()
    {
        if (!$timestamp = (int) $this->getRequest()->getParam('timestamp')) { throw new Exception("'timestamp' not set"); }

        $this->loadLayout();
        $this->renderLayout();
    }

    public function batchreturnAction()
    {
        if (!$timestamp = (int) $this->getRequest()->getParam('timestamp')) { throw new Exception("'timestamp' not set"); }

        $post = $this->getRequest()->getPost();

        foreach($post as $order_id => $serialized_data)
        {
            if(!is_numeric($order_id)) continue;

            if (!$order = Mage::getModel('sales/order')->load($order_id)) { throw new Exception("Order '$order_id' not found"); }

            $data = unserialize($serialized_data);

            $consignmentRecords = Mage::helper('flespakket')->getConsignmentRecords($order);
            $newRecord = array(
                'consignmentId' => $data['consignment_id'],
                'isRetour'      => null,
                'trackTrace'    => $data['tracktrace'],
                'postcode'      => $data['postcode'],
            );
            Mage::helper('flespakket')->sendTransactionalEmail($order, $newRecord);

            if (false !== array_search($data['consignment_id'], array_keys($consignmentRecords), true))
            {
                // This consignmentId is already known
            }
            else if (false !== array_search($key = "t$timestamp", array_keys($consignmentRecords), true)) // Timestamp is identified by prefix 't'
            {
                // Successful processing of consignment request
                $consignmentRecords[$key] = $newRecord; // Overwrite timestamp with consignment ID
            }
            else
            {
                // No record of this consignment request, but save the consignment ID anyway
                $consignmentRecords[] = $newRecord;
            }
            $order->setData('flespakket_consignment_ids', $s = Mage::helper('flespakket')->getConsignmentRecordString($consignmentRecords))
                  ->save()
                  ;
        }
        $this->loadLayout();
        $this->renderLayout();
    }

    public function pakjegemakAction()
    {
        echo $this->getLayout()->createBlock('flespakket/pakjegemak')->toHTML();
        exit;
    }
}
