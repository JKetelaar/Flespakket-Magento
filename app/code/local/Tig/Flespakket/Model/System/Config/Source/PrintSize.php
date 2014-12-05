<?php
class Tig_Flespakket_Model_System_Config_Source_PrintSize extends Varien_Object
{
    public function toOptionArray()
    {
        $helper = Mage::helper('flespakket');
        $array = array(
             array('value' => '0', 'label' => $helper->__('')),
             array('value' => '4', 'label' => $helper->__('A4')),
             array('value' => '6', 'label' => $helper->__('A6')),
        );
        return $array;
    }
}