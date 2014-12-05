<?php

class Tig_Flespakket_Block_Widget_Package extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {
    public function _getValue(Varien_Object $row) {
        if (Mage::helper('flespakket')->getConfig('all_in_one') == 1) {
            $order = Mage::getModel('sales/order')->load($row->getId());
            $items = $order->getAllItems();
            $item_amount = 0;
            foreach ($items as $item) {
                $item_amount += $item->getData('qty_ordered');
                $product = Mage::getModel('catalog/product')->load($item->getProductId());
                if ($product->getTypeId() === 'bundle') {
                    $item_amount--;
                }
            }
            $html = array();
            array_push($html, "<div>");
            array_push($html, "<select id=\"flespakket_package_" . $row->getId() . "\" name=\"flespakket_package\">");
            array_push($html, "<option value=\"" . $item_amount . "\">Automatisch</option>");
            array_push($html, "<option value=\"other\">anders</option>");
            array_push($html, "</select>");
            array_push($html, "<div>");
            return implode("\n", $html);
        } else {
            return <<<HTML
                <div>
                    <select id="flespakket_package_{$row->getId()}" name="flespakket_package">
                        <option value="">Default</option>
                        <option value="bottle_1">1 fles</option>
                        <option value="bottle_2">2 flessen</option>
                        <option value="bottle_3">3 flessen</option>
                        <option value="bottle_6">6 flessen</option>
                        <option value="bottle_12">12 flessen</option>
                        <option value="bottle_18">18 flessen</option>
                        <option value="other">anders</option>
                    </select>
                </div>
HTML;
        }
    }
}
