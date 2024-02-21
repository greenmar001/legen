<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopProductItemsetsPluginAction extends waViewAction
{

    public function execute()
    {
        waLocale::loadByDomain(array('shop', 'itemsets'));

        $this->setTemplate('ProductItemsetsPlugin');
        $model = new shopItemsetsPluginModel();
        $product_id = waRequest::get('id', 0, waRequest::TYPE_INT);

        // Если товар сам находится в каком-либо из комплектов, то не даем ему возможности создать комплект
        if ($ids = $model->getProductIds((array) $product_id, 'item_id')) {
            // Получаем комплекты, в которых присутствует товар
            $pm = new shopProductModel();
            $products = $pm->getById($ids);
            $this->view->assign('already_in_set', 1);
            $this->view->assign('products', $products);
        } else {
            $set = $model->getSet($product_id);
            // Product sku ids
            $psm = new shopProductSkusModel();
            $skus = $psm->select("id, name, sku")->order("sort ASC")->where("product_id = i:id", array("id" => $product_id))->fetchAll('id');

            $currency = waCurrency::getInfo(wa()->getConfig()->getCurrency(false));
            $locale = waLocale::getInfo(wa()->getLocale());
            $this->view->assign('currency', array(
                'code' => $currency['code'],
                'sign' => $currency['sign'],
                'sign_html' => !empty($currency['sign_html']) ? $currency['sign_html'] : $currency['sign'],
                'sign_position' => isset($currency['sign_position']) ? $currency['sign_position'] : 1,
                'sign_delim' => isset($currency['sign_delim']) ? $currency['sign_delim'] : ' ',
                'decimal_point' => $locale['decimal_point'],
                'frac_digits' => $locale['frac_digits'],
                'thousands_sep' => $locale['thousands_sep'],
            ));

            $this->view->assign('currencies', $this->getConfig()->getCurrencies());
            $this->view->assign('set', $set);
            $this->view->assign('product_id', $product_id);
            $this->view->assign('skus', $skus);
            $this->view->assign('stocks', wao(new shopStockModel())->getAll());
            $this->view->assign('plugin_url', wa()->getPlugin('itemsets')->getPluginStaticUrl());
        }
        $this->view->assign('domain_url', 'shop_itemsets');
    }

}
