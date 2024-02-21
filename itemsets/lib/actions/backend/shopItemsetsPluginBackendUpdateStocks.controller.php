<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopItemsetsPluginBackendUpdateStocksController extends waJsonController
{

    public function execute()
    {
        $sku_id = waRequest::post("sku_id", 0, waRequest::TYPE_INT);

        $result = array();
        $shop_product_skus_model = new shopProductSkusModel();
        $stocks_model = new shopProductStocksModel();
        $stock_model = new shopStockModel();
        $sql = "SELECT sps.id, sps.count, st.stock_id, stm.name as stock_name, st.count as stock_count
                FROM {$shop_product_skus_model->getTableName()} sps
                LEFT JOIN {$stocks_model->getTableName()} st ON st.sku_id = sps.id
                LEFT JOIN {$stock_model->getTableName()} stm ON stm.id = st.stock_id
                WHERE sps.id = '" . $sku_id . "'";
        $query = $shop_product_skus_model->query($sql);
        foreach ($query as $q) {
            if ($q['stock_id'] !== null) {
                $result[$q['stock_id']] = array(
                    "icon" => shopHelper::getStockCountIcon($q['stock_count'], $q['stock_id'], true),
                    "name" => $q['stock_name']
                );
            }
        }
        $this->response = $result ? $result : null;
    }

}
