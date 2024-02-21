<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopItemsetsHelper
{

    // Настройки плагина
    private static $settings = array();

    /**
     * Get plugin settings
     *
     * @return array
     */
    public static function getSettings()
    {
        if (!self::$settings) {
            $sm = new shopItemsetsSettingsPluginModel();
            $settings = $sm->get('settings');
            // Дефолтные настройки плагина
            if ($settings) {
                self::$settings = $settings;
                self::$settings['output_places'] = isset($settings['output_places']) ? (array) $settings['output_places'] : array();
            } else {
                $config = include shopItemsetsPluginHelper::path('lib/config/config.php', true);
                self::$settings = $config['settings'];
            }
        }
        return self::$settings;
    }

    /**
     * Get plugin templates
     *
     * @return array
     */
    public static function getTemplates()
    {
        static $templates = array();

        if (!$templates) {
            $settings = include shopItemsetsPluginHelper::path('lib/config/config.php', true);
            $templates = $settings['files'];
        }
        return $templates;
    }

    /**
     * Get real sku count
     *
     * @param int $sku_item_count
     * @param array $imagine
     * @param int $sku_id
     * @return int
     */
    private static function getCount($sku_item_count, $imagine = array(), $sku_id)
    {
        if (isset($sku_item_count) && $sku_item_count !== null) {
            // Моделируем ситуацию увеличения или уменьшения товара для расчета остатков. 
            // Необходимо при изменении количества основного товара, потому что само списание начинается после вызова хука
            if (isset($imagine[$sku_id])) {
                // Если необходимо уменьшить общее количество товара
                if ($imagine[$sku_id]['action'] == 'reduce') {
                    $sku_item_count -= $imagine[$sku_id]['quantity'];
                    if (isset($imagine[$sku_id]['correct'])) {
                        $sku_item_count += $imagine[$sku_id]['correct'];
                    }
                }
                // Если необходимо увеличить общее количество товара
                elseif ($imagine[$sku_id]['action'] == 'return') {
                    $sku_item_count += $imagine[$sku_id]['quantity'];
                    if (isset($imagine[$sku_id]['correct'])) {
                        $sku_item_count -= $imagine[$sku_id]['correct'];
                    }
                }
            }
        } else {
            $sku_item_count = 99999999999;
        }
        return $sku_item_count;
    }

    /**
     * Get info about skus count and number of use
     *
     * @param array $items
     * @param array $imagine
     * @return array
     */
    public static function skus_info($items, $imagine = array())
    {
         $skus = array();
        foreach ($items as $sku) {
            foreach ($sku as $sku_id => $i) {
                $use_stock = 0;
                $i['real_sku_count'] = self::getCount($i['real_sku_count'], $imagine, $sku_id);
                // Если необходимо использовать только один склад, то берем количество остатков только оттуда
                if ($i['sku_stock_id'] && isset($i['stock'][$i['sku_stock_id']])) {
                    $i['stock'][$i['sku_stock_id']]['count'] = self::getCount($i['stock'][$i['sku_stock_id']]['count'], $imagine, $sku_id);
                    $use_stock = 1;
                }

                else {
                    foreach ($i['stock'] as $ikey => $istock) {
                        $i['stock'][$ikey]['count'] = self::getCount($istock['count'], $imagine, $sku_id);
                        $use_stock = 1;
                    }
                }

                if ($i['available']) {
                    if ($i['real_sku_count'] > 0) {
                        if (!isset($skus[$sku_id]['count'])) {
                            $skus[$sku_id]['count'] = $i['real_sku_count'];
                        } else {
                            $skus[$sku_id]['count'] = min($skus[$sku_id]['count'], $i['real_sku_count']);
                        }
                        // Количество появлений у комплектов
                        if (!isset($skus[$sku_id]['used'])) {
                            $skus[$sku_id]['used'] = 1;
                        } else {
                            $skus[$sku_id]['used'] += 1;
                        }
                    } else {
                        $skus[$sku_id]['count'] = 0;
                    }

                    if ($use_stock) {
                        if (!isset($skus[$sku_id]['stocks'][$i['sku_stock_id']]['count'])) {
                            foreach ($i['stock'] as $ikey2 => $istock2) {
                                $skus[$sku_id]['stocks'][$ikey2]['count'] = $istock2['count'];
                                $skus[$sku_id]['stocks_sort'][$ikey2] = $istock2['count'];
                            }
                            //$skus[$sku_id]['stocks'][$i['sku_stock_id']]['count'] = $i['stock'][$i['sku_stock_id']]['count'];
                            //$skus[$sku_id]['stocks_sort'][$i['sku_stock_id']] = $i['stock'][$i['sku_stock_id']]['count'];
                        } else {
                            $skus[$sku_id]['stocks'][$i['sku_stock_id']]['count'] = min($skus[$sku_id]['stocks'][$i['sku_stock_id']]['count'], $i['stock'][$i['sku_stock_id']]['count']);
                            $skus[$sku_id]['stocks_sort'][$i['sku_stock_id']] = min($skus[$sku_id]['stocks'][$i['sku_stock_id']]['count'], $i['stock'][$i['sku_stock_id']]['count']);
                        }

                        if (!isset($skus[$sku_id]['stocks'][$i['sku_stock_id']]['used'])) {
                            foreach ($i['stock'] as $ikey2 => $istock2) {
                                $skus[$sku_id]['stocks'][$ikey2]['used'] = 1;
                            }
                            //$skus[$sku_id]['stocks'][$i['sku_stock_id']]['used'] = 1;
                        } else {
                            $skus[$sku_id]['stocks'][$i['sku_stock_id']]['used'] += 1;
                        }
                    }
                } else {
                    $skus[$sku_id]['count'] = 0;
                    if ($use_stock) {
                        $i['stock'][$i['sku_stock_id']]['count'] = 0;
                    }
                }
            }
        }
        return $skus;
    }

    private static function isSkuAvailable($sku_items)
    {
        if ($sku_items) {
            foreach ($sku_items as $si) {
                if (!$si['available']) {
                    return 0;
                } else {
                    continue;
                }
            }
        }
        return 1;
    }

    /**
     * Count the number of stocks for product skus
     *
     * @param array|object $product
     * @param bool $force
     * @param array $imagine
     */
    public static function recountSkus($product, $force = false, $imagine = array())
    {
        $sim = new shopItemsetsPluginModel();
        $sku_model = new shopProductSkusModel();
        $product_instance = new shopProduct($product);
        $stock_model = new shopStockModel();

        $price_changed = false;

        if (!is_array($product)) {
            $skus = $sku_model->getData($product_instance);
            $product_id = $product;
        } else {
            $skus = $product['skus'];
            $product_id = $product['id'];
        }

        $set = $sim->getSet($product_id);
        // Если у товара имеется комплект
        // Если установлен контроль за остатками, то обновляем их с учетом товаров, входящих в комплект
        if ($set) {
            if ($set['control_stocks'] == '-1') {
                $settings = shopItemsetsHelper::getSettings();
                $set['control_stocks'] = $settings['control_stocks'];
            }
            if ($set['change_price'] == '-1') {
                $settings = shopItemsetsHelper::getSettings();
                $set['change_price'] = $settings['change_price'];
            }
            // Валюта
            $currency = wa()->getConfig()->getCurrency(true);
            if (!empty($set['control_stocks']) || $force) {
                // Количество остатков у артикулов
                $set_count = array();
                // Информация о всех используемых комплектующих 
                $skus_info = $skus_info_copy = self::skus_info($set['items'], $imagine);
                // Массив артикулов, для которых не нужно проводить обработку
                $stop_sku = array();
                // Массив цен со скидкой
                $discount_price = array();
                // Массив цен артикулов и закупочных цен
                $total_price = array();

                $null = 99999999999;
                /**
                 * Сделать равномерное списание со складов, когда установлен автоматический режим
                 */
                // 1) Распределяем количество комплектующих равномерно по артикулам и вычисляем количество остатков
                foreach ($skus as $sku) {
                    if ((isset($set['items'][$sku['id']]) && !$set['split_set']) || (!empty($set['items']) && $set['split_set'] && empty($set_count))) {
                        if (!isset($discount_price[$sku['id']])) {
                            $discount_price[$sku['id']] = 0;
                        }
                        if (!isset($total_price[$sku['id']])) {
                            $total_price[$sku['id']]['total'] = $total_price[$sku['id']]['purchase'] = 0;
                        }
                        $min = $null;
                        if ($set['split_set']) {
                            $set_items = reset($set['items']);
                        } else {
                            $set_items = $set['items'][$sku['id']];
                        }
                        foreach ($set_items as $sku_id => $s) {
                            // Вычисляем цену со скидкой
                            if ($set['change_price']) {
                                if ($s['currency'] == '%') {
                                    $discount_price[$sku['id']] += $s['sku_price'] * $s['quantity'] * $s['discount'] / 100;
                                } else {
                                    $discount_price[$sku['id']] += shop_currency($s['discount'], $s['currency'], $currency, false);
                                }
                                $total_price[$sku['id']]['total'] += $s['sku_price'] * $s['quantity'];
                                $total_price[$sku['id']]['purchase'] += shop_currency($s['purchase_price'] * $s['quantity'], $s['product_currency'], $currency, false);
                            }
                            // Если используем конкретный склад
                            if (isset($skus_info[$sku_id]['stocks'][$s['sku_stock_id']])) {
                                if ($skus_info[$sku_id]['stocks'][$s['sku_stock_id']]['count'] !== $null) {
                                    if (!isset($skus_info[$sku_id]['stocks'][$s['sku_stock_id']]['i'])) {
                                        $skus_info[$sku_id]['stocks'][$s['sku_stock_id']]['i'] = 0;
                                    }
                                    // Если остатков уже нету, прекращаем обработку
                                    if ($skus_info_copy[$sku_id]['stocks'][$s['sku_stock_id']]['count'] <= 0) {
                                        $min = 0;
                                        $stop_sku[$sku['id']] = 1;
                                        continue;
                                    }
                                    // Предельное количество остатков на единицу комплектующего в артикуле.
                                    // Самое маленькое значение и будет максимальным количеством остатков для артикула
                                    $limit = ceil($skus_info_copy[$sku_id]['stocks'][$s['sku_stock_id']]['count'] / ($skus_info[$sku_id]['stocks'][$s['sku_stock_id']]['used'] - $skus_info[$sku_id]['stocks'][$s['sku_stock_id']]['i']));

                                    $skus_info_copy[$sku_id]['stocks'][$s['sku_stock_id']]['count'] -= $limit;
                                    $skus_info_copy[$sku_id]['count'] -= $limit;
                                    $skus_info_copy[$sku_id]['stocks_sort'][$s['sku_stock_id']] -= $limit;

                                    $min = min($min, floor($limit / $s['quantity']));

                                    $skus_info[$sku_id]['stocks'][$s['sku_stock_id']]['i'] ++;
                                    $skus_info[$sku_id]['stocks_sort'][$s['sku_stock_id']] -= $limit;
                                } else {
                                    continue;
                                }
                            } else {
                                if ($skus_info[$sku_id]['count'] !== $null) {
                                    if (!isset($skus_info[$sku_id]['i'])) {
                                        $skus_info[$sku_id]['i'] = 0;
                                    }
                                    // Если остатков уже нету, прекращаем обработку
                                    if ($skus_info_copy[$sku_id]['count'] <= 0) {
                                        $min = 0;
                                        $stop_sku[$sku['id']] = 1;
                                        continue;
                                    }
                                    // Предельное количество остатков на единицу комплектующего в артикуле.
                                    // Самое маленькое значение и будет максимальным количеством остатков для артикула
                                    $limit = ceil($skus_info_copy[$sku_id]['count'] / ($skus_info[$sku_id]['used'] - $skus_info[$sku_id]['i']));
                                    $skus_info_copy[$sku_id]['count'] -= $limit;

                                    // Если был выбран Автоматический расчет остатков для комплектующего, и в другом артикуле был указан конкретный склад для
                                    // списания, то необходимо равномерно уменьшить общее значение остатков для складов.
                                    if (isset($skus_info_copy[$sku_id]['stocks'])) {
                                        // Начинаем с наименьшего
                                        asort($skus_info[$sku_id]['stocks_sort']);
                                        $skus_stocks_count = count($skus_info[$sku_id]['stocks_sort']);
                                        $to_reduce = $limit;
                                        foreach ($skus_info[$sku_id]['stocks_sort'] as $st_id => $st) {
                                            $reduce = ceil($to_reduce / $skus_stocks_count);
                                            $skus_stocks_count--;
                                            if (isset($skus_info_copy[$sku_id]['stocks'][$st_id])) {
                                                if ($st > 0) {
                                                    if ($st < $reduce) {
                                                        $to_reduce -= $st;
                                                        $skus_info[$sku_id]['stocks'][$st_id]['count'] = 0;
                                                        $skus_info_copy[$sku_id]['stocks'][$st_id]['count'] = 0;
                                                        $skus_info[$sku_id]['stocks_sort'][$st_id] = 0;
                                                        $skus_info_copy[$sku_id]['stocks_sort'][$st_id] = 0;
                                                    } else {
                                                        $to_reduce -= $reduce;
                                                        $skus_info[$sku_id]['stocks'][$st_id]['count'] -= $reduce;
                                                        $skus_info_copy[$sku_id]['stocks'][$st_id]['count'] -= $reduce;
                                                        $skus_info[$sku_id]['stocks_sort'][$st_id] -= $reduce;
                                                        $skus_info_copy[$sku_id]['stocks_sort'][$st_id] -= $reduce;
                                                    }
                                                } else {
                                                    continue;
                                                }
                                            } else {
                                                if ($st > 0) {
                                                    if ($st < $reduce) {
                                                        $to_reduce -= $st;
                                                    } else {
                                                        $to_reduce -= $reduce;
                                                    }
                                                } else {
                                                    continue;
                                                }
                                            }
                                        }
                                    }
                                    $min = min($min, floor($limit / $s['quantity']));
                                    $skus_info[$sku_id]['i'] ++;
                                } else {
                                    continue;
                                }
                            }
                        }
                        // Если значение не изменилось, то имеется товар с бесконечным количеством остатков на складе
                        if ($min == $null) {
                            $max = null;
                        }
                        // Если у комплектующего не хватило остатов, то артикул будет нулевой
                        elseif ($min == 0) {
                            $max = 0;
                        } else {
                            $max = $min;
                            // Списываем общее количество остатков у комплектующего для дальнейшей обработки
                            foreach ($set_items as $sku_id => $s) {
                                if (isset($skus_info[$sku_id]['stocks'][$s['sku_stock_id']])) {
                                    if ($skus_info[$sku_id]['stocks'][$s['sku_stock_id']]['count'] !== $null) {
                                        $skus_info[$sku_id]['stocks'][$s['sku_stock_id']]['count'] -= $max * $s['quantity'];
                                    }
                                }
                                else {
                                    foreach ($skus_info[$sku_id]['stocks'] as $ikey => $istock) {
                                        if ($istock['count'] !== $null) {
                                            //$skus_info[$sku_id]['stocks'][$ikey]['count'] = 1;
                                            $skus_info[$sku_id]['stocks'][$ikey]['count'] -= $max * $s['quantity'];
                                            //$skus_info[$sku_id]['stocks'][$ikey]['count'] += $max * $s['quantity'];
                                            //$skus_info[$sku_id]['stocks'][$ikey]['count'] = 5;
                                        }
                                    }
                                }

                                if ($skus_info[$sku_id]['count'] !== $null) {
                                    $skus_info[$sku_id]['count'] -= $max * $s['quantity'];
                                }
                            }
                        }
                        // Устанавливаем первичное количество остатков для артикула
                        $set_count[$sku['id']] = $max;
                    } else {
                        $total_price[$sku['id']]['total'] = $sku['price'];
                        $total_price[$sku['id']]['purchase'] = isset($sku['purchase_price']) ? $sku['purchase_price'] : 0;
                        $discount_price[$sku['id']] = 0;
                    }
                }
                // 2) Сортируем комплекты по возрастанию, чтобы начать обработку артикулов с самого маленького
                asort($set_count);
                // Флаг сброса бесконечного цикла
                $break = false;
                // 3) Распределяем оставшиеся остатки комплектующих 
                // Если будут идеи как оптимизировать распределение - пишите: gapon2401@gmail.com
                while (!$break) {
                    // Еще один флаг остановки
                    $stop = true;
                    // Проверка на одинаковое количество остатков у всех артикулов
                    $set_equal = array();
                    $set_count_copy = $set_count;
                    $prev_key = 0;
                    // Перебираем артикулы, начиная с того, у которого меньше всего остатков
                    foreach ($set_count as $product_sku_id => $count) {
                        // Если артикул существует и не входит в массив запрещенных
                        if (((isset($set['items'][$product_sku_id]) && !$set['split_set']) || $set['split_set']) && !isset($stop_sku[$product_sku_id])) {
                            $min = $null;
                            if ($set['split_set']) {
                                $set_items2 = reset($set['items']);
                            } else {
                                $set_items2 = $set['items'][$product_sku_id];
                            }
                            // Перебираем комплектующие артикула
                            foreach ($set_items2 as $sku_id => $s) {
                                if (isset($skus_info[$sku_id]['stocks'][$s['sku_stock_id']])) {
                                    // Если у комплектующего не имеется уже остатков, то артикул прекращается обрабатываться
                                    if ($skus_info[$sku_id]['stocks'][$s['sku_stock_id']]['count'] == $min) {
                                        continue;
                                    } elseif ($skus_info[$sku_id]['stocks'][$s['sku_stock_id']]['count'] <= 0) {
                                        $min = 0;
                                        $stop_sku[$product_sku_id] = 1;
                                        break;
                                    } else {
                                        // Максимальное количество остатков для комплектующего 
                                        $limit2 = floor($skus_info[$sku_id]['stocks'][$s['sku_stock_id']]['count'] / $s['quantity']);
                                        // Если остатков не хватает, то прекращаем обработку для артикула
                                        if ($limit2 == 0) {
                                            $min = 0;
                                            $stop_sku[$product_sku_id] = 1;
                                            break;
                                        }
                                        // Высчитываем количество остатков для артикула
                                        $min = min($min, $limit2);
                                    }
                                } else {
                                    $test_sku_count = floatval($skus_info[$sku_id]['count']);
                                    if ($test_sku_count === floatval($min)) {
                                        continue;
                                    } elseif ($test_sku_count <= 0) {
                                        $min = 0;
                                        $stop_sku[$product_sku_id] = 1;
                                        break;
                                    } else {
                                        // Максимальное количество остатков для комплектующего
                                        $limit2 = floor($skus_info[$sku_id]['count'] / $s['quantity']);
                                        // Если остатков не хватает, то прекращаем обработку для артикула
                                        if ($limit2 == 0) {
                                            $min = 0;
                                            $stop_sku[$product_sku_id] = 1;
                                            break;
                                        }
                                        // Высчитываем количество остатков для артикула
                                        $min = min($min, $limit2);
                                    }
                                }
                            }
                            if ($min !== 0 && $min !== $null) {
                                // Получаем значение следующего артикула
                                $next = next($set_count_copy);
                                // Возвращаем указатель
                                prev($set_count_copy);

                                // Смысл вот в чем: Перебирая остатки комплектующих от наименьшего к наибольшему, мы стремимся равномерно распределеть их 
                                // по артикулам. Мы вычисляем, сколько нужно остатков, чтобы добраться наименьшему артикулу к последующему. Таким образом 
                                // стремимся подтянуть остатки всех артикулов к наибольшему, чтобы распределение было равномерным. Когда достигаем идеала и все
                                // остатки распределены одинаково, запускаем очередной перебор, при котором остатки будут добавляться к артикулам максимально.
                                // Если не достигли конца
                                if ($next !== false) {
                                    // Если до последующего артикула не хватает доступных комплектующих, то максимально добавляем остатки к артикулу
                                    if ($next - $count >= $min) {
                                        $max = $min;
                                        $stop = false;
                                    }
                                    // Если мы уже приблизились к последующему артикулу
                                    elseif ($next - $count <= 0) {
                                        $set_equal[] = 1;
                                        // Если у всех артикулов одинаковое количество остатков, то прибавляем к текущему его максимум.
                                        if (count($set_equal) == count($set_count)) {
                                            $max = $min;
                                            $stop = false;
                                        } else {
                                            next($set_count_copy);
                                            continue;
                                        }
                                    }
                                    // Если комплектующих больше, чем нужно, то добавляем к артикулу ровно столько, чтобы сровняться с ним
                                    elseif ($next - $count < $min) {
                                        $max = $next - $count;
                                        $stop = false;
                                    }
                                } else {
                                    if ($count - $set_count[$prev_key] <= 0) {
                                        $set_equal[] = 1;
                                        // Если у всех артикулов одинаковое количество остатков, то прибавляем к текущему его максимум.
                                        if (count($set_equal) == count($set_count)) {
                                            $max = $min;
                                            $stop = false;
                                        }
                                    }
                                    // Если обрабатывается последний артикул и все остальные добавлены в запрет, то прибавляем к нему его максимум
                                    elseif (!isset($stop_sku[$product_sku_id]) && count($stop_sku) == count($set_count) - 1) {
                                        $max = $min;
                                    } else {
                                        next($set_count_copy);
                                        continue;
                                    }
                                }
                                if (!isset($stop_sku[$product_sku_id])) {
                                    foreach ($set['items'][$product_sku_id] as $sku_id => $s) {
                                        if (isset($skus_info[$sku_id]['stocks'][$s['sku_stock_id']])) {
                                            $skus_info[$sku_id]['stocks'][$s['sku_stock_id']]['count'] -= $max * $s['quantity'];
                                        }
                                        else {
                                            foreach ($skus_info[$sku_id]['stocks'] as $ikey2 => $istock2) {
                                                $skus_info[$sku_id]['stocks'][$ikey2]['count'] -= $max * $s['quantity'];
                                            }
                                        }
                                        $skus_info[$sku_id]['count'] -= $max * $s['quantity'];
                                    }
                                }
                                $set_count[$product_sku_id] += $max;
                            }
                        }
                        $prev_key = $product_sku_id;
                        next($set_count_copy);
                    }
                    asort($set_count);
                    if ($stop) {
                        $break = true;
                    }
                }
                // Если в обработке один комплект на товар
                if ($set['split_set']) {
                    $product_count = $limit = reset($set_count);
                    $skus_count = count($skus);
                    $i = 0;
                    if ($set['change_price']) {
                        $discount_price = self::getPriceWithDiscount($set, key($set['items']));
                    }
                }

                $active_sku_id = $sku_available = 0;

                // Получаем информацию о складах
                $stocks = $stock_model->select('id')->order('sort ASC')->fetchAll(null, true);
                foreach ($skus as &$ps) {
                    $spread_stocks = 0;
                    if ((!empty($set[$ps['id']]) && in_array($set[$ps['id']], $stocks)) || !empty($set['spread_stock'])) {
                        $ps['stock'] = isset($ps['stock']) ? $ps['stock'] : array();
                        $spread_stocks = !empty($set[$ps['id']]) ? $set[$ps['id']] : $set['spread_stock'];
                    }
                    // Выполняем распределение остатков комплектов для каждого артикула
                    if (array_key_exists($ps['id'], $set_count) && !$set['split_set']) {
                        $sku_available = self::isSkuAvailable(!empty($set['items'][$ps['id']]) ? $set['items'][$ps['id']] : array());
                        $ps['count'] = $set_count[$ps['id']];
                        //////////////
                        if (!empty($spread_stocks) || (!empty($ps['stock']) && $stocks)) {
                            $stocks_count = count($stocks);
                            $skus_count2 = $ps['count'];
                            $limit2 = $skus_count2;
                            $j = 0;
                            /*
                            foreach ($stocks as $s) {
                                if ($skus_count2 === null) {
                                    $ps['count'] = null;
                                    foreach ($stocks as $st) {
                                        $ps['stock'][$st] = "";
                                    }
                                } elseif ($skus_count2 == 0) {
                                    $ps['stock'][$s] = 0;
                                } elseif ($skus_count2 <= $stocks_count) {
                                    if ($limit2) {
                                        $ps['stock'][$s] = $spread_stocks == $s ? 1 : 0;
                                        if ($spread_stocks !== $s) {
                                            continue;
                                        }
                                        $limit2--;
                                    } else {
                                        $ps['stock'][$s] = 0;
                                    }
                                } elseif ($skus_count2 > $stocks_count) {
                                    if (!empty($spread_stocks)) {
                                        $ps['stock'][$s] = $spread_stocks == $s ? $limit2 : 0;
                                        if ($spread_stocks !== $s) {
                                            continue;
                                        }
                                    } else {
                                        $ps['stock'][$s] = ceil($limit2 / ($stocks_count - $j));
                                    }
                                    $limit2 -= $ps['stock'][$s];
                                    $j++;
                                } else {
                                    continue;
                                }
                            }

                            */

                        }
                        else {
                            if ($ps['count'] === null) {
                                $ps['stock'][0] = "";
                            } else {
                                $ps['stock'][0] = $ps['count'];
                            }
                        }

                        //$ps['stock'][$s] = 3;

                        if ($set['change_price']) {
                            $discount = $total_price[$ps['id']]['total'] - $discount_price[$ps['id']];
                            $discount = $discount < 0 ? $total_price[$ps['id']]['total'] : $discount;
                            if ($set['round_price'] == 'ceil') {
                                $discount = ceil($discount);
                                $total_price[$ps['id']]['total'] = ceil($total_price[$ps['id']]['total']);
                            } elseif ($set['round_price'] == 'floor') {
                                $discount = floor($discount);
                                $total_price[$ps['id']]['total'] = floor($total_price[$ps['id']]['total']);
                            } else {
                                $discount = sprintf('%0.2f', $discount);
                                $total_price[$ps['id']]['total'] = sprintf('%0.2f', $total_price[$ps['id']]['total']);
                            }
                            $ps['primary_price'] = $discount;
                            $ps['price'] = shop_currency($discount, $currency, $product_instance->currency, false);
                            // Если цена у комплекта меньше, чем сумма всех комплектующих, то записываем данные в "зачеркнутую цену"
                            if ($ps['primary_price'] < $total_price[$ps['id']]['total']) {
                                $ps['compare_price'] = shop_currency($total_price[$ps['id']]['total'], $currency, $product_instance->currency, false);
                            } else {
                                $ps['compare_price'] = 0;
                            }
                            $ps['purchase_price'] = shop_currency($total_price[$ps['id']]['purchase'], $currency, $product_instance->currency, false);
                            $price_changed = true;
                        }
                    } elseif ($set['split_set']) {
                        $sku_available = self::isSkuAvailable(!empty($set['items']) ? reset($set['items']) : array());
                        if ($product_count === null) {
                            $ps['count'] = null;
                            if (!empty($spread_stocks) || (!empty($ps['stock']) && $stocks)) {
                                foreach ($stocks as $st) {
                                    $ps['stock'][$st] = "";
                                }
                            } else {
                                $ps['stock'][0] = "";
                            }
                        } elseif ($product_count == 0) {
                            $ps['count'] = 0;
                            if (!empty($spread_stocks) || (!empty($ps['stock']) && $stocks)) {
                                foreach ($stocks as $st) {
                                    $ps['stock'][$st] = 0;
                                }
                            } else {
                                $ps['stock'][0] = 0;
                            }
                        } elseif ($product_count <= $skus_count) {
                            if ($limit) {
                                $ps['count'] = 1;
                                if (!empty($spread_stocks) || (!empty($ps['stock']) && $stocks)) {
                                    foreach ($stocks as $k => $st) {
                                        // Если указан способ распределения остатков (конкретный склад)
                                        if (!empty($spread_stocks)) {
                                            $ps['stock'][$st] = $spread_stocks == $st ? 1 : 0;
                                        } else {
                                            $ps['stock'][$st] = $k == 0 ? 1 : 0;
                                        }
                                    }
                                } else {
                                    $ps['stock'][0] = 1;
                                }
                                $limit--;
                            } else {
                                $ps['count'] = 0;
                                if (!empty($spread_stocks) || (!empty($ps['stock']) && $stocks)) {
                                    foreach ($stocks as $k => $st) {
                                        $ps['stock'][$st] = 0;
                                    }
                                } else {
                                    $ps['stock'][0] = 0;
                                }
                            }
                        } elseif ($product_count > $skus_count) {
                            $ps['count'] = ceil($limit / ($skus_count - $i));
                            if (!empty($spread_stocks) || (!empty($ps['stock']) && $stocks)) {
                                $stocks_count = count($stocks);
                                $skus_count2 = $ps['count'];
                                $limit2 = $skus_count2;
                                $j = 0;
                                foreach ($stocks as $s) {
                                    if ($skus_count2 == 0) {
                                        $ps['stock'][$s] = 0;
                                    } elseif ($skus_count2 <= $stocks_count) {
                                        if ($limit2) {
                                            if (!empty($spread_stocks)) {
                                                $ps['stock'][$s] = $spread_stocks == $s ? $limit2 : 0;
                                                if ($spread_stocks !== $s) {
                                                    continue;
                                                }
                                            } else {
                                                $ps['stock'][$s] = 1;
                                            }
                                            $limit2--;
                                        } else {
                                            $ps['stock'][$s] = 0;
                                        }
                                    } elseif ($skus_count2 > $stocks_count) {
                                        if (!empty($spread_stocks)) {
                                            $ps['stock'][$s] = $spread_stocks == $s ? $limit2 : 0;
                                            if ($spread_stocks !== $s) {
                                                continue;
                                            }
                                        } else {
                                            $ps['stock'][$s] = ceil($limit2 / ($stocks_count - $j));
                                        }
                                        $limit2 -= $ps['stock'][$s];
                                        $j++;
                                    } else {
                                        continue;
                                    }
                                }
                            } else {
                                $ps['stock'][0] = $ps['count'];
                            }
                            $limit -= $ps['count'];
                            $i++;
                        }
                        if ($set['change_price']) {
                            $ps['primary_price'] = $discount_price['discount'];
                            $ps['price'] = shop_currency($discount_price['discount'], wa()->getConfig()->getCurrency(true), $product_instance->currency, false);
                            // Если цена у комплекта меньше, чем сумма всех комплектующих, то записываем данные в "зачеркнутую цену"
                            if ($ps['primary_price'] < $discount_price['total']) {
                                $ps['compare_price'] = shop_currency($discount_price['total'], wa()->getConfig()->getCurrency(true), $product_instance->currency, false);
                            }
                            $purchase_price = reset($total_price);
                            $ps['purchase_price'] = shop_currency($purchase_price['purchase'], wa()->getConfig()->getCurrency(true), $product_instance->currency, false);
                            $price_changed = true;
                        }
                    }

                    /* Устанавливаем в качестве активноо артикула тот, который есть в наличии  */
                    if (!$active_sku_id && $sku_available) {
                        $active_sku_id = $ps['id'];
                    }
                }

                $stock_mins = [];
                foreach ($stocks as $s) {
                    foreach ($set['items'] as $sku_key => $item_stocks) {
                        $stock_mins[$sku_key][$s] = 99999999;

                        $stock_min = 99999999;
                        foreach ($item_stocks as $item_stock) {
                            $stock_min = min($stock_min, $item_stock['stock'][$s]['count']);
                        }
                        $stock_mins[$sku_key][$s] = $stock_min;

                    }
                }

                foreach ($skus as $skey => &$sku) {
                    if (isset($stock_mins[$skey])) {
                        $sku['stock'] = $stock_mins[$skey];
                    }
                }

                // Обновляем артикулы
                $sku_model->setData($product_instance, $skus);

            }
            $product_model = new shopProductModel();
            $update_data = array("sku_id" => $active_sku_id);
            // Устанавливаем в изображение для активного артикула
            $image_id = $sku_model->select("image_id")->where("id = '".(int) $active_sku_id."'")->fetchField();
            if ($image_id) {
                $image_model = new shopProductImagesModel();
                $image_data = $image_model->getById($image_id);
                $update_data['image_id'] = $image_id;
                $update_data['image_filename'] = $image_data['filename'];
                $update_data['ext'] = $image_data['ext'];
            }
            $product_model->updateById($product_id, $update_data);

            // Если этап контроля остатков был пропущен, то проверяем требуется ли изменить цену товара
            if ($set['change_price'] && !$price_changed) {
                if ($set['split_set']) {
                    $discount_price = self::getPriceWithDiscount($set, key($set['items']));
                }

                foreach ($skus as &$ps) {
                    if (!$set['split_set']) {
                        $discount_price = self::getPriceWithDiscount($set, $ps['id']);
                    }
                    if ($discount_price['discount']) {
                        $ps['primary_price'] = $discount_price['discount'];
                        $ps['price'] = shop_currency($discount_price['discount'], $currency, $product_instance->currency, false);
                        // Если цена у комплекта меньше, чем сумма всех комплектующих, то записываем данные в "зачеркнутую цену"
                        if ($ps['primary_price'] < $discount_price['total']) {
                            $ps['compare_price'] = shop_currency($discount_price['total'], wa()->getConfig()->getCurrency(true), $product_instance->currency, false);
                        } else {
                            $ps['compare_price'] = 0;
                        }
                    }
                    $ps['purchase_price'] = shop_currency($discount_price['purchase'], wa()->getConfig()->getCurrency(true), $product_instance->currency, false);
                }
                // Обновляем артикулы
                $sku_model->setData($product_instance, $skus);
            }
        } else {
            // Проверяем, находится ли данный товар в каком-либо из комплектов
            //$product_ids = $sim->getProductIds((array) $product['id'], 'item_id');
            $product_ids = $sim->getProductIds((array) $product_id, 'item_id');
            // Если находится, то пересчитываем остатки для всех комплектов, у которых указана такая опция
            if ($product_ids) {
                foreach ($product_ids as $pi) {
                    self::recountSkus($pi);
                }
            }
        }
    }

    /**
     * Get price with discount
     *
     * @param array $set
     * @param int $sku_id
     * @return float
     */
    private static function getPriceWithDiscount($set, $sku_id)
    {
        $total_price = $discount_price = $discount = $purchase_price = 0;

        $currency = wa()->getConfig()->getCurrency(true);

        if (isset($set['items'][$sku_id])) {
            foreach ($set['items'][$sku_id] as $si) {
                if ($si['currency'] == '%') {
                    $discount_price = $discount_price + $si['sku_price'] * $si['quantity'] * $si['discount'] / 100;
                } else {
                    $discount_price = $discount_price + shop_currency($si['discount'], $si['currency'], $currency, false);
                }
                $total_price = $total_price + $si['sku_price'] * $si['quantity'];
                $purchase_price = $purchase_price + shop_currency($si['purchase_price'] * $si['quantity'], $si['product_currency'], $currency, false);
            }
            $discount = $total_price - $discount_price;
            $discount = $discount < 0 ? $total_price : $discount;
            if ($set['round_price'] == 'ceil') {
                $discount = ceil($discount);
                $total_price = ceil($total_price);
            } elseif ($set['round_price'] == 'floor') {
                $discount = floor($discount);
                $total_price = floor($total_price);
            } else {
                $discount = sprintf('%0.2f', $discount);
                $total_price = sprintf('%0.2f', $total_price);
            }
        }
        return array("total" => $total_price, "discount" => $discount, "purchase" => $purchase_price);
    }

    /**
     * Check cart for product-sets and products, which has not enough stock count
     *
     * @param array $items
     * @return array - array('error_ids' => array(), 'error_item_ids' => array())
     */
    public static function cartCheck($items)
    {
        // "Проблемные" комплекты
        $error_ids = array();
        // "Проблемные" товары корзины
        $error_item_ids = array();
        if ($items) {
            $sim = new shopItemsetsPluginModel();
            $item_ids = array();
            $cart_items = array();
            $cart_product_items = array();

            foreach ($items as $i_id => $i) {
                if ($i['type'] == 'product') {
                    $item_ids[$i['sku_id']] = $i['product_id'];
                    $cart_items[$i['sku_id']] = $i;
                    $cart_items[$i['sku_id']]['cart_id'] = $i_id;
                    $cart_product_items[$i['product_id']][$i['sku_id']] = $i;
                    $cart_product_items[$i['product_id']][$i['sku_id']]['cart_id'] = $i_id;
                }
            }
            // Получаем ID товаров-комплектов
            $set_ids = $sim->getProductIds();
            // Определяем, имеются ли в корзине товары-комплекты
            $sets_in_cart = array_intersect(array_values($item_ids), $set_ids);

            if ($sets_in_cart) {
                // Получаем информацию о товарах-комплектах
                $sets = $sim->getSets(array("ids" => $sets_in_cart));
                // Если в корзине имеются несколько товаров-комплектов, то проверяем, имеются ли у них схожие составляющие товары
                // и достаточно ли их количества
                $set_cart_items = array();
                foreach ($sets as $set) {
                    foreach ($set['items'] as $s_sku_id => $s_sku) {
                        if ((isset($cart_items[$s_sku_id]) && !$set['split_set']) || $set['split_set']) {
                            foreach ($s_sku as $s_item_id => $s_item) {
                                if (!isset($set_cart_items[$s_item_id])) {
                                    $set_cart_items[$s_item_id] = $s_item;
                                    $set_cart_items[$s_item_id]['quantity'] = 0;
                                }
                                // Имеется ли выборка только по одному складу
                                if ($s_item['sku_stock_id'] && isset($s_item['stock'][$s_item['sku_stock_id']])) {
                                    if (!isset($set_cart_items[$s_item_id]['stock'][$s_item['sku_stock_id']]['quantity'])) {
                                        $set_cart_items[$s_item_id]['stock'][$s_item['sku_stock_id']]['quantity'] = 0;
                                    }
                                }
                                if ((isset($cart_product_items[$s_item['product_id']][$s_sku_id]) && !$set['split_set']) || ($set['split_set'] && !empty($cart_product_items[$s_item['product_id']]))) {
                                    if ($set['split_set']) {
                                        $cart_product_items_var = reset($cart_product_items[$s_item['product_id']]);
                                    } else {
                                        $cart_product_items_var = $cart_product_items[$s_item['product_id']][$s_sku_id];
                                    }
                                    $set_cart_items[$s_item_id]['quantity'] += $cart_product_items_var['quantity'] * $s_item['quantity'] * ($set['split_set'] ? count($cart_product_items[$s_item['product_id']]) : 1);
                                    if ($set_cart_items[$s_item_id]['sku_stock_id'] && isset($set_cart_items[$s_item_id]['stock'][$set_cart_items[$s_item_id]['sku_stock_id']])) {
                                        $set_cart_items[$s_item_id]['stock'][$s_item['sku_stock_id']]['quantity'] += $cart_product_items_var['quantity'] * $s_item['quantity'] * ($set['split_set'] ? count($cart_product_items[$s_item['product_id']]) : 1);
                                    }
                                    if ($set['split_set']) {
                                        foreach ($cart_product_items[$s_item['product_id']] as $cpi2) {
                                            $set_cart_items[$s_item_id]['cart_id'][] = $cpi2['cart_id'];
                                        }
                                    } else {
                                        $set_cart_items[$s_item_id]['cart_id'][] = $cart_product_items_var['cart_id'];
                                    }
                                }
                            }
                        }
                    }
                }

                foreach ($set_cart_items as $item_id => $sci) {
                    if ($sci['real_sku_count'] !== null && $sci['real_sku_count'] < $sci['quantity']) {
                        $error_ids[$item_id] = $sci;
                    }
                    if ($sci['sku_stock_id'] && isset($sci['stock'][$sci['sku_stock_id']]) && $sci['stock'][$sci['sku_stock_id']]['count'] < $sci['stock'][$sci['sku_stock_id']]['quantity']) {
                        $error_ids[$item_id] = $sci;
                    }
                }
                // Если у товаров комплектов достаточно количества на складах, то выполняем дальнейшую проверку
                if (!$error_ids) {
                    // Проверяем, имеются ли товары в корзине, которые входят в состав товаров-комплектов
                    $set_items = $sim->getItemsBySkuId(array_keys($item_ids), $sets_in_cart, array_keys($cart_items));
                    if ($set_items) {
                        foreach ($set_items as $item_sku_id => $item) {
                            $quantity = $cart_items[$item_sku_id]['quantity'];
                            foreach ($item as $si) {
                                // Считаем общее количество товара с учетом комплектов и их артикулов, содержащих данный товар
                                $quantity += $cart_product_items[$si['product_id']][$si['product_sku_id']]['quantity'] * $si['quantity'] * ($sets[$si['product_id']]['split_set'] ? count($cart_product_items[$si['product_id']]) : 1);
                            }
                            // Если товара недостаточно для покупки, то записываем его в "проблемные товары"
                            if ($si['count'] !== null && $quantity > $si['count']) {
                                $error_item_ids[$item_sku_id] = $si;
                                $error_item_ids[$item_sku_id]['cart_id'][] = $cart_items[$item_sku_id]['cart_id'];
                                $error_item_ids[$item_sku_id]['limit'] = $quantity - $si['count'];
                            }
                        }
                    }
                }
            }
        }
        return array("error_ids" => $error_ids, "error_item_ids" => $error_item_ids);
    }

    /**
     * Get HTML code of the set items
     *
     * @param int $product_id
     * @param int $product_sku_id
     * @param string $template - theme template to output the items
     * @return string - HTML of set items
     */
    public static function getSetItemsHTML($product_id, $product_sku_id = 0, $template = "")
    {
        static $loaded = 0;
        $sim = new shopItemsetsPluginModel();
        // Получаем комплект
        $set = $sim->getSet($product_id);

        $html = "";
        if ($set) {
            $product_ids = array();
            // Собираем ID товаров, входящих в комплект для того, чтобы впоследствии получить о них информацию
            foreach ($set['items'] as $skus) {
                foreach ($skus as $s) {
                    $product_ids[] = $s['item_id'];
                }
            }
            $product_ids[] = $product_id;
            $pc = new shopProductsCollection('id/' . implode(",", $product_ids));
            $products = $pc->getProducts("*", 0, 500, true);
            $cur_currency = wa()->getConfig()->getCurrency(true);

            // Выводим комплектующие через файлы текущей темы
            $view = wa()->getView();
            $theme = new waTheme(waRequest::getTheme());
            $theme_path = $theme->getPath();
            // Список возможных шаблонов, через которые можно отобразить комплектующие.
            $files = array(
                'list-thumbs.html',
                'list-thumbs-mini.html',
                'products.slider.html',
                'products.list.html',
            );
            // Системные шаблоны
            $system_files = self::getTemplates();
            if ($template) {
                if (strpos($template, ",") !== false) {
                    $parts = explode(",", $template);
                    $files = array_merge($parts, $files);
                } else {
                    array_unshift($files, $template);
                }
            }
            $settings = self::getSettings();

            // Определяем шаблон для вывода комплектующих
            foreach ($files as $k => $f) {
                if (in_array(trim($f), $system_files)) {
                    if ($path = shopItemsetsPluginHelper::getTemplatePath($f)) {
                        $template_path = $path;
                        $view->assign('current_locale', wa()->getLocale());
                        $view->assign('ruble', $settings['ruble']);
                        break;
                    }
                } else if (file_exists($theme_path . '/' . $f)) {
                    $template_path = $theme_path . '/' . $f;
                    $view->assign('current_locale', wa()->getLocale());
                    $view->assign('ruble', $settings['ruble']);
                    $view->assign('is_category', 0);
                    $view->setThemeTemplate($theme, $f);
                    break;
                }
            }
            if (empty($template_path)) {
                $template_path = shopItemsetsPluginHelper::getTemplatePath(reset($system_files));
                $view->assign('current_locale', wa()->getLocale());
                $view->assign('ruble', $settings['ruble']);
            }

            // Для вывода комплектующих используем данные артикула, а не данные, принадлежащие товару
            foreach ($set['items'] as $sku_id => $skus) {
                $set_items = array();
                foreach ($skus as $k => $i) {
                    if (isset($products[$i['item_id']])) {
                        $set_items[$k] = $products[$i['item_id']];
                        $set_items[$k]['sku_id'] = $i['item_sku_id'];
                        $set_items[$k]['name'] .= $i['sku_name'] ? " (" . $i['sku_name'] . ")" : '';
                        if ($i['sku_stock_id'] && isset($i['stock'][$i['sku_stock_id']])) {
                            $set_items[$k]['count'] = $i['stock'][$i['sku_stock_id']]['count'];
                        }
                        $set_items[$k]['quantity'] = $i['quantity'];
                        $set_items[$k]['price'] = $i['sku_price'];
                        $set_items[$k]['compare_price'] = (float) shop_currency($i['sku_compare_price'], $set_items[$k]['currency'], null, false);
                        // Если товару сделали скидку, то изменяем цену
                        if ($i['discount']) {
                            if ($i['currency'] == '%') {
                                $discount_price = $i['sku_price'] * $i['discount'] / 100;
                            } else {
                                $discount_price = shop_currency($i['discount'], $i['currency'], $cur_currency, false);
                            }
                            $price_with_discount = $i['sku_price'] - $discount_price;
                            if ($price_with_discount >= 0 && $price_with_discount < $i['sku_price']) {
                                $set_items[$k]['compare_price'] = $i['sku_price'];
                                $set_items[$k]['price'] = $price_with_discount;
                            }
                        }
                        if (!empty($i['sku_image_id'])) {
                            $set_items[$k]['image_id'] = $i['sku_image_id'];
                        }
                    }
                }
                if ($set_items) {
                    $product_sku_id = ((!$product_sku_id && isset($products[$product_id])) || ($product_sku_id && !isset($set['items'][$product_sku_id]))) ? $products[$product_id]['sku_id'] : $product_sku_id;

                    $html .= "<div class='" . ($set['split_set'] ? "itemsets-split-set" : "itemsets-skus-block") . " itemsets-sku-" . $sku_id . "'";
                    if ($product_sku_id == $sku_id) {
                        $html .= " style='display: block;'";
                    }
                    $html .= ">";

                    $view->assign('sku_id', $sku_id);

                    // Настройка для шаблона "Удобная покупка"
                    if (strpos($template_path, 'products.slider.html') !== false) {
                        $view->assign('sliderId', uniqid());
                        $view->assign('s_products', $set_items);
                        $comfortbuy = 1;
                    } else {
                        $view->assign('products', $set_items);
                        $comfortbuy = 0;
                    }

                    if ($comfortbuy) {
                        $html .= '<div class="products-slider">';
                    }
                    $html .= $view->fetch($template_path);
                    if ($comfortbuy) {
                        $html .= '</div>';
                    }
                    $html .= "</div>";
                    if ($set['split_set']) {
                        break;
                    }
                }
            }
            if ($html) {
                $product = new shopProduct($product_id);
                if ($product && $product['sku_type'] == shopProductModel::SKU_TYPE_SELECTABLE) {
                    $features_selectable = $product->features_selectable;
                    $product_features_model = new shopProductFeaturesModel();
                    $sku_features = $product_features_model->getSkuFeatures($product->id);

                    $sku_selectable = array();
                    foreach ($sku_features as $sku_id => $sf) {
                        if (!isset($product->skus[$sku_id])) {
                            continue;
                        }
                        $sku_f = "";
                        foreach ($features_selectable as $f_id => $f) {
                            if (isset($sf[$f_id])) {
                                $sku_f .= $f_id . ":" . $sf[$f_id] . ";";
                            }
                        }
                        $sku_selectable[$sku_f] = array(
                            'id' => $sku_id,
                        );
                    }
                }

                if (!empty($sku_selectable)) {
                    $html .= "<script type='text/javascript'>
                            (function($) {
                                $(function() {
                                    $.itemsetsFrontend.features[" . $product_id . "] = " . json_encode($sku_selectable) . "
                                });
                            })(jQuery);
                        </script>";
                }
                if (!$loaded) {
                    $html .= "<script type='text/javascript'>(function($) { $(function() { $.itemsetsFrontend.initProduct(); }); })(jQuery);</script>";
                    $loaded = 1;
                }
            }
        }
        return $html;
    }

}
