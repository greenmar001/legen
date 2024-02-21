<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopItemsetsPluginModel extends waModel
{

    protected $table = 'shop_itemsets';

    /**
     * Get product set items
     * 
     * @param int $product_id
     * @param int $sku_id
     * @return array
     */
    public function getItems($product_id, $sku_id = 0)
    {
        $result = array();
        $shop_product_model = new shopProductModel();
        $shop_product_skus_model = new shopProductSkusModel();
        $stocks_model = new shopProductStocksModel();
        $stock_model = new shopStockModel();

        $stocks = $stock_model->getByField('public', 1, true);
        if (!empty($stocks)) {
            foreach ($stocks as $stock) {
                $sql = "SELECT i.*, sp.name, sps.name as sku_name, sps.count as real_sku_count, sps.primary_price as sku_price, sps.available, 
                       sps.image_id as sku_image_id, sps.compare_price as sku_compare_price, st.stock_id, st.count as stock_count,
                       i.stock_id as sku_stock_id, stm.name as stock_name, sps.purchase_price, sp.currency as product_currency
                FROM {$this->table} i
                LEFT JOIN {$shop_product_skus_model->getTableName()} sps ON sps.id = i.item_sku_id AND sps.product_id = i.item_id
                LEFT JOIN {$shop_product_model->getTableName()} sp ON sp.id =i.item_id
                LEFT JOIN {$stocks_model->getTableName()} st ON (st.sku_id = i.item_sku_id AND st.product_id = i.item_id AND st.stock_id = {$stock['id']})
                LEFT JOIN {$stock_model->getTableName()} stm ON stm.id = st.stock_id ";
                if ($sku_id) {
                    $itemsets_settings = new shopItemsetsSettingsPluginModel();
                    $sql .= "LEFT JOIN {$itemsets_settings->getTableName()} ism ON ism.field = i.product_id AND ism.ext = 'split_set' ";
                }
                $sql .= "WHERE i.product_id = '" . (int) $product_id . "' AND sps.id IS NOT NULL ";
                if ($sku_id) {
                    $sql .= "AND IF(ism.value <> 1, i.product_sku_id = '" . (int) $sku_id . "', 1) ";
                }
                $sql .= "ORDER BY i.sort_id ASC";

                $query = $this->query($sql)->fetchAll();

                foreach ($query as $q) {
                    if (!isset($result[$q['product_sku_id']][$q['item_sku_id']])) {
                        $result[$q['product_sku_id']][$q['item_sku_id']] = $q;
                        $result[$q['product_sku_id']][$q['item_sku_id']]['name'] = htmlspecialchars($q['name']);
                        $result[$q['product_sku_id']][$q['item_sku_id']]['icon'] = shopHelper::getStockCountIcon($q['real_sku_count'], null, true);
                        unset($result[$q['product_sku_id']][$q['item_sku_id']]['stock_id'], $result[$q['product_sku_id']][$q['item_sku_id']]['stock_count'], $result[$q['product_sku_id']][$q['item_sku_id']]['stock_name']);
                    }

                    if ($q['stock_id'] !== null) {
                        $result[$q['product_sku_id']][$q['item_sku_id']]['stock'][$q['stock_id']] = array(
                            "count" => $q['stock_count'],
                            "icon" => shopHelper::getStockCountIcon($q['stock_count'], $q['stock_id'], true),
                            "name" => $q['stock_name']
                        );
                        $result[$q['product_sku_id']][$q['item_sku_id']]['stock_sort'][$q['stock_id']] = $q['stock_count'];
                    }
                }
            }
        }
        else {
            $sql = "SELECT i.*, sp.name, sps.name as sku_name, sps.count as real_sku_count, sps.primary_price as sku_price, sps.available, 
                       sps.image_id as sku_image_id, sps.compare_price as sku_compare_price, st.stock_id, st.count as stock_count,
                       i.stock_id as sku_stock_id, stm.name as stock_name, sps.purchase_price, sp.currency as product_currency
                FROM {$this->table} i
                LEFT JOIN {$shop_product_skus_model->getTableName()} sps ON sps.id = i.item_sku_id AND sps.product_id = i.item_id
                LEFT JOIN {$shop_product_model->getTableName()} sp ON sp.id =i.item_id
                LEFT JOIN {$stocks_model->getTableName()} st ON st.sku_id = sps.id
                LEFT JOIN {$stock_model->getTableName()} stm ON stm.id = st.stock_id ";
            if ($sku_id) {
                $itemsets_settings = new shopItemsetsSettingsPluginModel();
                $sql .= "LEFT JOIN {$itemsets_settings->getTableName()} ism ON ism.field = i.product_id AND ism.ext = 'split_set' ";
            }
            $sql .= "WHERE i.product_id = '" . (int) $product_id . "' AND sps.id IS NOT NULL ";
            if ($sku_id) {
                $sql .= "AND IF(ism.value <> 1, i.product_sku_id = '" . (int) $sku_id . "', 1) ";
            }
            $sql .= "ORDER BY i.sort_id ASC";

            $query = $this->query($sql)->fetchAll();
            foreach ($query as $q) {
                if (!isset($result[$q['product_sku_id']][$q['item_sku_id']])) {
                    $result[$q['product_sku_id']][$q['item_sku_id']] = $q;
                    $result[$q['product_sku_id']][$q['item_sku_id']]['name'] = htmlspecialchars($q['name']);
                    $result[$q['product_sku_id']][$q['item_sku_id']]['icon'] = shopHelper::getStockCountIcon($q['real_sku_count'], null, true);
                    unset($result[$q['product_sku_id']][$q['item_sku_id']]['stock_id'], $result[$q['product_sku_id']][$q['item_sku_id']]['stock_count'], $result[$q['product_sku_id']][$q['item_sku_id']]['stock_name']);
                }

                if ($q['stock_id'] !== null) {
                    $result[$q['product_sku_id']][$q['item_sku_id']]['stock'][$q['stock_id']] = array(
                        "count" => $q['stock_count'],
                        "icon" => shopHelper::getStockCountIcon($q['stock_count'], $q['stock_id'], true),
                        "name" => $q['stock_name']
                    );
                    $result[$q['product_sku_id']][$q['item_sku_id']]['stock_sort'][$q['stock_id']] = $q['stock_count'];
                }
            }
        }

        return $result;
    }

    /**
     * Get product-set items and common items
     * 
     * @param int $order_id
     * @return array
     */
    public function getItemsByOrderId($order_id)
    {
        $return = array('set_items' => array(), 'common_items' => array(), 'product_items' => array());
        $order_items_model = new shopOrderItemsModel();
        $items = $order_items_model->getItems($order_id);
        if ($items) {
            $order_product_ids = array();
            // Собираем ID товаров, которые в заказе
            foreach ($items as $i) {
                $order_product_ids[$i['product_id']][$i['sku_id']] = $i;
                $return['common_items'][$i['id']] = $i;
                if (!isset($return['product_items'][$i['product_id']])) {
                    $return['product_items'][$i['product_id']]['quantity'] = $i['quantity'];
                } else {
                    $return['product_items'][$i['product_id']]['quantity'] += $i['quantity'];
                }
            }
            // Получаем id товаров, у которых имеются комплекты
            $product_ids = $this->getProductIds(array_keys($order_product_ids));

            if ($product_ids) {
                $count = 0;
                foreach ($product_ids as $pi) {
                    foreach ($order_product_ids[$pi] as $sku_id => $opi) {
                        $sku_items = $this->getItems($pi, $sku_id);
                        $return['set_items'][$count]['items'] = reset($sku_items);
                        $return['set_items'][$count]['quantity'] = $opi['quantity'];
                        $count++;
                    }
                    unset($return['common_items'][$opi['id']]);
                }
            }
        }
        return $return;
    }

    /**
     * Count set items for product
     * 
     * @param int $product_id
     * @return int
     */
    public function countItems($product_id)
    {
        $shop_product_skus_model = new shopProductSkusModel();
        $sql = "SELECT COUNT(*) 
                FROM {$this->table} i
                LEFT JOIN {$shop_product_skus_model->getTableName()} sps ON sps.id = i.item_sku_id AND sps.product_id = i.item_id
                WHERE i.product_id = '" . (int) $product_id . "' AND sps.id IS NOT NULL";
        return $this->query($sql)->fetchField();
    }

    /**
     * Get product ids, which have sets, or if we have $ids - check them for sets
     * 
     * @param array[int]|null $ids - product ids need to be checked for sets
     * @param string $search_by - field in where clause
     * @return array
     */
    public function getProductIds($ids = null, $search_by = 'product_id')
    {
        $product_ids = array();
        if (!$ids) {
            $product_ids = $this->select("DISTINCT(product_id)")->fetchAll(null, true);
        } elseif (is_array($ids)) {
            $product_ids = $this->select("DISTINCT(product_id)")->where($search_by . " IN ('" . implode("','", $this->escape($ids, 'int')) . "')")->fetchAll(null, true);
        }
        return $product_ids ? $product_ids : array();
    }

    /**
     * Get product-set items by sku ID
     * 
     * @param int|array $sku_id
     * @param int $product_id
     * @param int $product_sku_id
     * @return array
     */
    public function getItemsBySkuId($sku_id, $product_id = 0, $product_sku_id = 0)
    {
        $product_ids = '';
        $product_sku_ids = '';
        if (is_array($sku_id)) {
            $ids = " AND i.item_sku_id IN ('" . implode("','", $this->escape($sku_id, 'int')) . "')";
        } else {
            $ids = " AND i.item_sku_id = '" . (int) $sku_id . "'";
        }
        if ($product_id) {
            if (is_array($product_id)) {
                $product_ids .= " AND i.product_id IN ('" . implode("','", $this->escape($product_id, 'int')) . "')";
            } else {
                $product_ids .= " AND i.product_id = '" . (int) $product_id . "'";
            }
        }
        if ($product_sku_id) {
            if (is_array($product_sku_id)) {
                $product_sku_ids .= " AND i.product_sku_id IN ('" . implode("','", $this->escape($product_sku_id, 'int')) . "')";
            } else {
                $product_sku_ids .= " AND i.product_sku_id = '" . (int) $product_sku_id . "'";
            }
        }
        $shop_product_skus_model = new shopProductSkusModel();
        $sql = "SELECT i.*,  sps.count
                FROM {$this->table} i
                LEFT JOIN {$shop_product_skus_model->getTableName()} sps ON sps.id = i.item_sku_id AND sps.product_id = i.item_id
                WHERE 1 $ids $product_ids $product_sku_ids AND sps.id IS NOT NULL";

        return $this->query($sql)->fetchAll('item_sku_id', 2);
    }

    /**
     * Get all sets
     * 
     * @param array $filter
     * @return array
     */
    public function getSets($filter = array())
    {
        $sets = array();
        $ids = "";

        if (!empty($filter['ids'])) {
            if (is_array($filter['ids'])) {
                $ids .= " AND field IN ('" . implode("','", $this->escape($filter['ids'], 'int')) . "')";
            } else {
                $ids .= " AND field = '" . (int) $filter['ids'] . "'";
            }
        }

        $sis = new shopItemsetsSettingsPluginModel();
        $sql = "SELECT * FROM {$sis->getTableName()} WHERE 1 $ids";
        $query = $this->query($sql);

        foreach ($query as $q) {
            $sets[$q['field']][$q['ext']] = $q['value'];
        }

        if ($sets) {
            foreach ($sets as $id => &$s) {
                $s['items'] = $this->getItems($id);
                if (!$s['items']) {
                    unset($s);
                }
            }
        }

        return $sets ? $sets : array();
    }

    /**
     * Get set data
     * 
     * @param int $product_id
     * @param bool $only_settings
     * @return array
     */
    public function getSet($product_id, $only_settings = false)
    {
        $sis = new shopItemsetsSettingsPluginModel();
        $set = $sis->get($product_id);

        // Если выбрано "Показывать только настройки", то возвращаем данные
        if ($only_settings) {
            $settings = shopItemsetsHelper::getSettings();
            if (!isset($set['show_items']) || $set['show_items'] == '-1') {
                $set['show_items'] = $settings['show_items'];
            }
            if (!isset($set['control_stocks']) || $set['control_stocks'] == '-1') {
                $set['control_stocks'] = $settings['control_stocks'];
            }
            if (!isset($set['change_price']) || $set['change_price'] == '-1') {
                $set['change_price'] = $settings['change_price'];
            }
            return $set;
        }
        $set['items'] = $this->getItems($product_id);
        return $set['items'] ? $set : array();
    }

    /**
     * Save set data
     * 
     * @param int $product_id
     * @param array $save_data
     * @param array $settings
     * @return bool
     */
    public function save($product_id, $save_data, $settings)
    {
        $sis = new shopItemsetsSettingsPluginModel();
        // Удаляем комплекты для товара
        $this->deleteByField(array("product_id" => $product_id));
        // Удаляем настройки 
        $sis->deleteByField(array("field" => $product_id));

        if ($save_data) {
            $query = array();
            foreach ($save_data as $s) {
                $query[] = array(
                    "product_id" => $product_id,
                    "product_sku_id" => $s['product_sku_id'],
                    "item_id" => $s['item_id'],
                    "item_sku_id" => $s['item_sku_id'],
                    "quantity" => $s['quantity'],
                    "discount" => $s['discount'],
                    "currency" => $s['currency'],
                    "sort_id" => $s['sort_id'],
                    "stock_id" => $s['stock_id'],
                );
            }
            $this->multipleInsert($query);
        }
        if ($settings && $save_data) {
            $sis->save($product_id, $settings);
        }
        return true;
    }

    /**
     * Delete 
     * 
     * @param array[int]|int $ids
     * @return boolean
     */
    public function delete($ids)
    {
        if (!empty($ids)) {
            if (is_array($ids)) {
                $product_ids = "IN (" . implode(",", $this->escape($ids, 'int')) . ")";
            } else {
                $product_ids = "='" . (int) $ids . "'";
            }
            $sis = new shopItemsetsSettingsPluginModel();
            $sql = "DELETE i, sis FROM {$this->table} i
                    LEFT JOIN {$sis->getTableName()} sis ON i.product_id = sis.field
                    WHERE i.product_id $product_ids";
            return $this->exec($sql);
        }
        return true;
    }

    /**
     * Reduce product-set items from stocks
     * 
     * @param int $order_id
     * @param string $log_message
     */
    public function reduceProductsFromStocks($order_id, $log_message)
    {
        $order_params_model = new shopItemsetsOrderParamsPluginModel();

        // Если товары уже списывались, то прекращаем обработку
        $reduced = $order_params_model->getOne($order_id, 'reduced');
        if ($reduced) {
            return;
        }

        // Получаем список комплектов, входящих в заказ
        $items = $this->getItemsByOrderId($order_id);
        $product_ids = array();
        // Обрабатываем товары-комплекты
        if ($items['set_items']) {
            $item_sku_ids = array();
            foreach ($items['set_items'] as $item) {
                foreach ($item['items'] as $i_id => $i) {
                    $sku_stock = array();
                    $item_sku_ids[$i['item_sku_id']] = $i['item_sku_id'];
                    $product_ids[$i['product_id']] = $i['product_id'];
                    $reduce = $item['quantity'] * $i['quantity'];
                    // Если имеется склад с бесконечным кол-м остатков, то ничего не списываем
                    if ($i['real_sku_count'] === null && !$i['sku_stock_id']) {
                        continue;
                    }
                    // Если имеются склады, то списываем с них остатки
                    if (isset($i['stock'])) {
                        $stocks = $i['stock'];
                        // Если указан определенный склад для товара
                        if (isset($stocks[$i['sku_stock_id']])) {
                            // Использовать только один склад или все поочередно
                            if ($i['sku_stock_id']) {
                                $stocks = array($i['sku_stock_id'] => $stocks[$i['sku_stock_id']]);
                            } else {
                                $stocks = array($i['sku_stock_id'] => $stocks[$i['sku_stock_id']]) + $stocks;
                            }
                        } elseif ($i['real_sku_count'] === null) {
                            continue;
                        }
                        $count_stocks = count($stocks);
                        $j = 0;
                        foreach ($stocks as $stock_id => $stock) {
                            // Если был выбран конкретный склад, с него уже списали остатки и имеется неограниченный склад, то больше не списываем ничего
                            /* if (isset($stocks[$i['sku_stock_id']]) && $j > 0 && $i['real_sku_count'] === null) {
                              continue;
                              }
                              // Если остатков хватает, чтобы списать количество или если последний шаг, то списываем оставшееся количество остатков
                              else */ if ($reduce < $stock['count'] || $count_stocks == 1) {
                                $sku_stock[$i_id][$stock_id] = 0 - $reduce;
                                break;
                            }
                            // Если количества на складе не хватает, чтобы уменьшить, то списываем остатки с этого склада и переходим к другому
                            else {
                                $sku_stock[$i_id][$stock_id] = 0 - $stock['count'];
                                $reduce -= $stock['count'];
                            }
                            $count_stocks--;
                            $j++;
                        }
                    } else {
                        $sku_stock[$i_id][0] = 0 - $reduce;
                    }

                    // Перераспределяем остатки по складам
                    $this->updateStockCount($sku_stock, $order_id, $log_message);
                }
            }

            // Пересчитываем остатки для комплектов, которые имеют в своем составе измененные выше товары
            $product_set_ids = array_diff($this->getProductIds($item_sku_ids, 'item_sku_id'), $product_ids);
            if ($product_set_ids) {
                foreach ($product_set_ids as $pi) {
                    shopItemsetsHelper::recountSkus($pi);
                }
            }
        }
        // Обрабатываем обычные товары
        if ($items['common_items']) {
            $common_sku_ids = array();
            foreach ($items['common_items'] as $i) {
                $common_sku_ids[] = $i['sku_id'];
                $imagine[$i['sku_id']] = array(
                    'action' => 'reduce',
                    'quantity' => $i['quantity']
                );
            }
            // Получаем товары-комплекты, в которых присутствуют обычные товары
            $common_set_ids = array_diff($this->getProductIds($common_sku_ids, 'item_sku_id'), $product_ids); // $this->getProductIds($common_sku_ids, 'item_sku_id');
            if ($common_set_ids) {
                foreach ($common_set_ids as $ci) {
                    // Моделируем ситуацию списания количества у товара и пересчитываем остатки для комплектов при измененных значениях
                    if (isset($product_ids[$ci]) && isset($items['product_items'][$ci])) {
                        // Если в одной корзине находится товар, присутствующий в одном из комплектов также, находящихся в корзине, то
                        // увеличиваем моделируемое количество остатков на кол-во заказанного комплекта, чтобы при списании не убралось лишнего
                        $new_imagine = $imagine;
                        foreach ($new_imagine as $im_id => $im) {
                            $new_imagine[$im_id]['correct'] = $items['product_items'][$ci]['quantity'];
                        }
                        shopItemsetsHelper::recountSkus($ci, false, $new_imagine);
                    } else {
                        shopItemsetsHelper::recountSkus($ci, false, $imagine);
                    }
                }
            }
        }
        // Ставим флаг, что товары были списаны
        $order_params_model->setOne($order_id, 'reduced', 1);
    }

    /**
     * Return product-set items to stocks
     * 
     * @param int $order_id
     * @param string $log_message
     */
    public function returnProductsToStocks($order_id, $log_message)
    {
        $order_params_model = new shopItemsetsOrderParamsPluginModel();

        $reduced = $order_params_model->getOne($order_id, 'reduced');
        if (!$reduced && $reduced !== null) {
            return;
        }
        // Получаем список комплектов, входящих в заказ
        $items = $this->getItemsByOrderId($order_id);
        $product_ids = array();
        // Обрабатываем товары-комплекты
        if ($items['set_items']) {
            $item_sku_ids = array();
            foreach ($items['set_items'] as $item) {
                foreach ($item['items'] as $i_id => $i) {
                    $sku_stock = array();
                    $item_sku_ids[$i['item_sku_id']] = $i['item_sku_id'];
                    $product_ids[$i['product_id']] = $i['product_id'];
                    $return = $item['quantity'] * $i['quantity'];
                    if ($i['real_sku_count'] === null && !$i['sku_stock_id']) {
                        continue;
                    }
                    // Если имеются склады, то списываем с них остатки
                    if (isset($i['stock'])) {
                        $stocks = $i['stock'];
                        // Если указан определенный склад для товара
                        if (isset($stocks[$i['sku_stock_id']])) {
                            // Использовать только один склад или все поочередно
                            if ($i['sku_stock_id']) {
                                $stocks = array($i['sku_stock_id'] => $stocks[$i['sku_stock_id']]);
                            } else {
                                $stocks = array($i['sku_stock_id'] => $stocks[$i['sku_stock_id']]) + $stocks;
                            }
                        } elseif ($i['real_sku_count'] === null) {
                            continue;
                        }
                        $count_stocks = count($stocks);
                        $j = 0;
                        foreach ($stocks as $stock_id => $stock) {
                            $sku_stock[$i_id][$stock_id] = ceil($return / ($count_stocks - $j));
                            $return -= $sku_stock[$i_id][$stock_id];
                            $j++;
                        }
                    } else {
                        $sku_stock[$i_id][0] = $return;
                    }

                    // Перераспределяем остатки по складам
                    $this->updateStockCount($sku_stock, $order_id, $log_message);
                }
            }

            // Пересчитываем остатки для комплектов, которые имеют в своем составе измененные выше товары
            $product_set_ids = array_diff($this->getProductIds($item_sku_ids, 'item_sku_id'), $product_ids);
            if ($product_set_ids) {
                foreach ($product_set_ids as $pi) {
                    shopItemsetsHelper::recountSkus($pi);
                }
            }
        }
        // Обрабатываем обычные товары
        if ($items['common_items']) {
            $common_sku_ids = array();
            foreach ($items['common_items'] as $i) {
                $common_sku_ids[] = $i['sku_id'];
                $imagine[$i['sku_id']] = array(
                    'action' => 'return',
                    'quantity' => $i['quantity']
                );
            }
            // Получаем товары-комплекты, в которых присутствуют обычные товары
            $common_set_ids = array_diff($this->getProductIds($common_sku_ids, 'item_sku_id'), $product_ids); // $this->getProductIds($common_sku_ids, 'item_sku_id');
            if ($common_set_ids) {
                foreach ($common_set_ids as $ci) {
                    // Моделируем ситуацию списания количества у товара и пересчитываем остатки для комплектов при измененных значениях
                    if (isset($product_ids[$ci]) && isset($items['product_items'][$ci])) {
                        // Если в одной корзине находится товар, присутствующий в одном из комплектов также, находящихся в корзине, то
                        // уменьшаем моделируемое количество остатков на кол-во заказанного комплекта, чтобы при списании не добавилось лишнего
                        $new_imagine = $imagine;
                        foreach ($new_imagine as $im_id => $im) {
                            $new_imagine[$im_id]['correct'] = $items['product_items'][$ci]['quantity'];
                        }
                        shopItemsetsHelper::recountSkus($ci, false, $new_imagine);
                    } else {
                        shopItemsetsHelper::recountSkus($ci, false, $imagine);
                    }
                }
            }
        }
        // Ставим флаг, что товары были списаны
        $order_params_model->setOne($order_id, 'reduced', 0);
    }

    /**
     * Update log stock count
     * 
     * @param array $data
     * @param int $order_id
     * @param string $log_message
     */
    public function updateStockCount($data, $order_id, $log_message)
    {
        if (!$data) {
            return;
        }
        $product_model = new shopProductModel();
        $product_skus_model = new shopProductSkusModel();
        $product_stocks_model = new shopProductStocksModel();
        $stocks_log_model = new shopProductStocksLogModel();

        $sku_ids = array_map('intval', array_keys($data));
        if (!$sku_ids) {
            return;
        }
        $skus = $product_skus_model->select('id,product_id')->where("id IN(" . implode(',', $sku_ids) . ")")->fetchAll('id');
        $sku_ids = array_keys($skus);
        if (!$sku_ids) {
            return;
        }
        $product_ids = array();

        foreach ($data as $sku_id => $sku_stock) {
            $sku_id = (int) $sku_id;
            if (!isset($skus[$sku_id]['product_id'])) {
                continue;
            }
            $product_id = $skus[$sku_id]['product_id'];
            foreach ($sku_stock as $stock_id => $count) {
                $stock_id = (int) $stock_id;
                shopProductStocksLogModel::setContext(shopProductStocksLogModel::TYPE_ORDER, $log_message, array('order_id' => $order_id));
                if ($stock_id) {
                    $item = $product_stocks_model->getByField(array(
                        'sku_id' => $sku_id,
                        'stock_id' => $stock_id
                    ));
                    if (!$item) {
                        continue;
                    }
                    $product_stocks_model->set(array(
                        'sku_id' => $sku_id,
                        'product_id' => $product_id,
                        'stock_id' => $stock_id,
                        'count' => $item['count'] + $count,
                    ));
                } else {
                    $old_count = $product_skus_model->select('count')->where('id=i:sku_id', array('sku_id' => $sku_id))->fetchField();
                    if ($old_count !== null) {
                        $log_data = array(
                            'product_id' => $product_id,
                            'sku_id' => $sku_id,
                            'before_count' => $old_count,
                            'after_count' => $old_count + $count,
                            'diff_count' => $count,
                        );
                        $stocks_log_model->insert($log_data);
                    }
                    $this->exec("UPDATE {$product_skus_model->getTableName()} SET count = count + ({$count}) WHERE id = $sku_id");
                }
                shopProductStocksLogModel::clearContext();
                if (isset($skus[$sku_id]['product_id'])) {
                    $product_ids[] = $product_id;
                }
            }
        }

        if (!$product_ids) {
            return;
        }

        // correct sku counters
        $sql = "UPDATE {$product_skus_model->getTableName()} sk JOIN (
                SELECT sk.id, SUM(st.count) AS count FROM {$product_skus_model->getTableName()} sk
                JOIN {$product_stocks_model->getTableName()} st ON sk.id = st.sku_id
                WHERE sk.id IN(" . implode(',', $sku_ids) . ")
                GROUP BY sk.id
                ORDER BY sk.id
            ) r ON sk.id = r.id
            SET sk.count = r.count
            WHERE sk.count IS NOT NULL";
        $this->exec($sql);

        // correct product counters
        $sql = "UPDATE {$product_model->getTableName()} p JOIN (
                SELECT p.id, SUM(sk.count) AS count FROM {$product_model->getTableName()} p
                JOIN {$product_skus_model->getTableName()} sk ON p.id = sk.product_id
                WHERE p.id IN(" . implode(',', array_unique($product_ids)) . ") AND sk.available = 1
                GROUP BY p.id
                ORDER BY p.id
            ) r ON p.id = r.id
            SET p.count = r.count
            WHERE p.count IS NOT NULL";
        $this->exec($sql);
    }

    /**
     * Check if set exists for product
     * 
     * @param int $product_id
     * @param int $sku_id
     * @return bool
     */
    public function issetSet($product_id, $sku_id = 0)
    {
        $sku = "";
        if ($sku_id) {
            $sku .= " AND product_sku_id = '" . (int) $sku_id . "'";
        }
        return !!$this->select("product_id")->where("product_id = '" . (int) $product_id . "'" . $sku)->fetchField();
    }

}
