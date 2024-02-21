<?php

class shopItemsetsPluginBackendAutocompleteController extends waController
{

    protected $limit = 10;

    public function execute()
    {
        $data = array();
        $q = waRequest::get('term', '', waRequest::TYPE_STRING_TRIM);
        if ($q) {
            $settings = shopItemsetsHelper::getSettings();
            $data = $this->productsAutocomplete($q, $settings['search_limit']);
        }
        echo json_encode($data);
    }

    private function getForbidIds()
    {
        $sim = new shopItemsetsPluginModel();
        $forbid_ids = $sim->getProductIds();
        $forbid_ids[] = waRequest::get('forbid_id', 0, waRequest::TYPE_INT);
        return $forbid_ids;
    }

    public function productsAutocomplete($q, $limit = null)
    {
        $limit = $limit !== null ? $limit : $this->limit;

        $forbid_ids = $this->getForbidIds();

        $product_model = new shopProductModel();
        $product_skus_model = new shopProductSkusModel();
        $q = $product_model->escape($q, 'like');
        $fields = 'id, name AS value, price, count, sku_id';

        $products = $product_model->select($fields)
                ->where("name LIKE '$q%' AND id NOT IN('" . implode("','", $forbid_ids) . "')")
                ->limit($limit)
                ->fetchAll('id');
        $count = count($products);

        if ($count < $limit) {
            $product_ids = array_keys($product_skus_model->select('id, product_id')
                            ->where("sku LIKE '$q%' AND product_id NOT IN('" . implode("','", $forbid_ids) . "')")
                            ->limit($limit)
                            ->fetchAll('product_id'));
            if ($product_ids) {
                $data = $product_model->select($fields)
                        ->where('id IN (' . implode(',', $product_ids) . ')')
                        ->limit($limit - $count)
                        ->fetchAll('id');

                // not array_merge, because it makes first reset numeric keys and then make merge
                $new_products = $products + $data;
                $products = array();
                if ($new_products) {
                    foreach ($new_products as $np) {
                        $products[$np['id']] = $np;
                    }
                }
            }
        }

        // try find with LIKE %query%
        if (!$products) {
            $products = $product_model->select($fields)
                    ->where("name LIKE '%$q%' AND id NOT IN('" . implode("','", $forbid_ids) . "')")
                    ->limit($limit)
                    ->fetchAll('id');
        }
        $currency = wa()->getConfig()->getCurrency();
        $p_ids = array_keys($products);
        foreach ($products as &$p) {
            $p['price_str'] = wa_currency($p['price'], $currency);
        }
        unset($p);

        if (waRequest::get('with_skus')) {
            $product_skus = $product_skus_model->getByField('product_id', $p_ids, true);
            $product_and_skus = array();
            foreach ($product_skus as $ps) {
                $product_and_skus[] = array(
                    'value' => $products[$ps['product_id']]['value'],
                    'price_str' => wa_currency($ps['primary_price'], $currency),
                    'price' => $ps['primary_price'],
                    'count' => $ps['count'],
                    'sku_id' => $ps['id'],
                    'id' => $ps['product_id']
                );
            }
            $products = $product_and_skus;
        }

        $sku_ids = array();
        foreach ($products as $p) {
            $sku_ids[] = $p['sku_id'];
        }
        $skus = $product_skus_model->getByField('id', $sku_ids, 'id');
        $sku_names = array();
        foreach ($skus as $sku_id => $sku) {
            $name = '';
            if ($sku['name']) {
                $name = $sku['name'];
                if ($sku['sku']) {
                    $name .= ' (' . $sku['sku'] . ')';
                }
            } else {
                $name = $sku['sku'];
            }
            $sku_names[$sku_id]['name'] = $name;
            $sku_names[$sku_id]['count'] = $sku['count'];
        }
        foreach ($products as &$p) {
            $p['value'] = htmlspecialchars($p['value']);
            $p['icon'] = ' ' . shopHelper::getStockCountIcon($sku_names[$p['sku_id']]['count'], null, true);
            if ($sku_names[$p['sku_id']]['name']) {
                $p['sku_name'] = ' <span class="hint">' . $sku_names[$p['sku_id']]['name'] . '</span>';
            } else {
                $p['sku_name'] = "";
            }
            $p['label'] = $p['value'] . $p['icon'] . $p['sku_name'];
        }
        unset($p);

        return array_values($products);
    }

}
