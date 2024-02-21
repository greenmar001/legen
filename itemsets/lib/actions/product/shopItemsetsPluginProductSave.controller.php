<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopItemsetsPluginProductSaveController extends waJsonController
{

    public function execute()
    {
        // Массив с товарами, входящими в комплект
        $items = waRequest::post('items', array());
        $save_data = array();
        $sort = array();

        // Товар, для которого формируем комплект
        $product_id = waRequest::post('itemsets-product', 0, waRequest::TYPE_INT);
        if (!$product_id) {
            $this->errors['messages'][] = _wp('Product ID not defined');
            return;
        }

        // Настройки комплекта
        $settings = waRequest::post('settings', array());

        // Product sku ids
        $psm = new shopProductSkusModel();
        $product_sku_ids = $psm->select("id")->where("product_id = i:id", array("id" => $product_id))->fetchAll(null, true);

        $sim = new shopItemsetsPluginModel();
        if ($items) {
            foreach ($items as $product_sku_id => $item) {
                $product_sku_id = (int) $product_sku_id;

                // Если такого артикула у товара не существует, то пропускаем его обработку
                if ($product_sku_id && !in_array($product_sku_id, $product_sku_ids)) {
                    continue;
                }

                $query = array();
                $count = 0;
                foreach ($item as $i_id => &$i) {
                    // Ключ должен состоять из ID товара и ID артикула
                    if (strpos($i_id, '-') === false) {
                        unset($i);
                        continue;
                    }

                    $parts = explode("-", $i_id);
                    $item_product_id = (int) $parts[0];
                    $item_sku_id = (int) $parts[1];

                    $i['discount'] = (float) $i['discount'];
                    $i['quantity'] = (int) $i['quantity'];
                    $i['stock_id'] = (int) $i['stock_id'];

                    if (!$i['discount'] || $i['discount'] < 0) {
                        $i['discount'] = 0;
                    }
                    if (!$i['quantity'] || $i['quantity'] <= 0) {
                        $i['quantity'] = 0;
                    }

                    $query[] = "(id = '" . $item_sku_id . "' AND product_id = '" . $item_product_id . "')";

                    if ($i['currency'] == '%') {
                        if ($i['discount'] > 100) {
                            $i['discount'] = 100;
                        }
                    }

                    if ($i['stock_id'] <= 0) {
                        $i['stock_id'] = 0;
                    }
                    $sort[$i_id] = $count;
                    $count++;
                }
                if ($query) {
                    // Если товар сам находится в каком-либо из комплектов, то не даем ему возможности создать комплект
                    if ($sim->getProductIds((array) $product_id, 'item_id')) {
                        $this->errors['messages'][] = _wp("The product is already in the set. It's forbidden to have set inside the other set.");
                        return;
                    }
                    // Запрещаем добавлять в комплект товары-комплекты
                    $forbid_ids = $sim->getProductIds();
                    // Проверяем, существуют ли переданные товары
                    $result = $psm->query("SELECT id, product_id FROM {$psm->getTableName()} WHERE " . implode(" OR ", $query) . " AND product_id NOT IN('" . implode("','", $forbid_ids) . "')")->fetchAll();
                    if ($result) {
                        foreach ($result as $r) {
                            $key = $r['product_id'] . "-" . $r['id'];
                            if (!isset($item[$key])) {
                                continue;
                            }
                            $key2 = $r['product_id'] . "-" . $r['id'] . "-" . $product_sku_id;
                            // Формируем массив на сохранение
                            $save_data[$key2] = $item[$key];
                            $save_data[$key2]['item_id'] = $r['product_id'];
                            $save_data[$key2]['item_sku_id'] = $r['id'];
                            $save_data[$key2]['product_sku_id'] = $product_sku_id;
                            $save_data[$key2]['sort_id'] = $sort[$key];
                        }
                    }
                }
            }
        }

        $this->response = $sim->save($product_id, $save_data, $settings);
    }

}
