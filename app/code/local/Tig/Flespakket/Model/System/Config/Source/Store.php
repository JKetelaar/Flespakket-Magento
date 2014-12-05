<?php
class Tig_Flespakket_Model_System_Config_Source_Store
{
    protected $_options;

    public function toOptionArray()
    {
        if (!$this->_options)
        {
            $this->_options = Mage::getResourceModel('core/store_collection')
                ->load()->toOptionArray();

            array_unshift($this->_options, array(
                'value' => '0',
                'label' => 'Default',
            ));
        }
        return $this->_options;
    }
}
