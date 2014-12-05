<?php

$installer = $this;
$installer->startSetup();

$installer = $this;
$installer->getConnection()->addColumn($installer->getTable('sales/order'), 'flespakket_consignment_ids', 'text');

$installer->endSetup();
