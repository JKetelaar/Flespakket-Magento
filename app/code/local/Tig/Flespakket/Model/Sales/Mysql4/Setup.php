<?php
class Tig_Flespakket_Model_Sales_Mysql4_Setup extends Mage_Sales_Model_Mysql4_Setup
{
    public function getDefaultEntities()
    {
        $ret = parent::getDefaultEntities();
        $ret['order']['attributes']['flespakket_consignment_ids'] = array(
            'type' => 'text',
            'grid' => true,
        );
        return $ret;
    }
}
