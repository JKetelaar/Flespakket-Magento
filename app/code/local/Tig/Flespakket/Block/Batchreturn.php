<?php
class Tig_Flespakket_Block_Batchreturn extends Tig_Flespakket_Block_Abstract
{
    protected function countConsignmentIds()
    {
        $batchCount = 0;
        $post = $this->getRequest()->getPost();

        foreach($post as $order_id => $serialized_data)
        {
            if(!is_numeric($order_id)) continue;

            $batchCount++;
        }
        return $batchCount;
    }
}
