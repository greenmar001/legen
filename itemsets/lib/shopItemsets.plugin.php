<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopItemsetsPlugin extends shopPlugin
{

    public function backendProduct($product)
    {
        $settings = shopItemsetsHelper::getSettings();
        if ($settings['enable']) {
            $psm = new shopItemsetsPluginModel();
            $set_settings = $psm->getSet($product['id'], true);
            $count = $psm->countItems($product['id']);
            return array('edit_section_li' => '<li class="itemsetsPlugin' . (!$count ? " grey" : "") . '">
            <a href="#/product/' . (empty($product['id']) ? 'new' : $product['id']) . '/edit/itemsetsPlugin" id ="itemsets-tab" data-count="' . $count . '" data-control-stocks = "' . ($set_settings['control_stocks'] && $settings['ask_control']) . '">
            ' . _wp('Product set') . '
            <span class="s-product-edit-tab-status"></span>
            </a>
            <span class="shop-tooltip"></span>
            <link rel="stylesheet" href="' . $this->getPluginStaticUrl() . 'css/itemsets.css?v=' . $this->info['version'] . '" />
                <script src="' . $this->getPluginStaticUrl() . 'js/itemsets.js?v=' . $this->info['version'] . '"></script>
            </li>');
        }
    }

    public function productSave($params)
    {
        $settings = shopItemsetsHelper::getSettings();
        if ($settings['enable']) {
            $product = $params['data'];
            shopItemsetsHelper::recountSkus($product);
        }
    }

    public function backendProducts()
    {
        $settings = shopItemsetsHelper::getSettings();
        if ($settings['enable']) {
            $sim = new shopItemsetsPluginModel();
            $js = $sidebar_top_li = '';
            // Получаем id товаров, у которых имеются комплекты и отмечаем эти товары в списке
            $product_ids = $sim->getProductIds();
            if ($product_ids) {
                $js .= '<style type="text/css">
                       #product-list.thumbs .itemsets-list { padding-left: 10px;  }
                       #product-list.thumbs .itemsets-list li { width: 100%; margin-bottom: 5px; }
                   </style>';
                $items = array();
                // Получаем комплектующие для каждого товара-комплекта
                foreach ($product_ids as $pi) {
                    $items[$pi] = $sim->getItems($pi);
                }
                $js .= '<script type="text/javascript">
                        (function($) { 
                            $(function() {
                                $("#product-list").bind("append_product_list", function(e, products) {
                                    var ids = ' . json_encode($items) . ';
                                    var thumbs = $(this).hasClass("thumbs");
                                    $.each(ids, function(i, skus) {
                                        if (thumbs) {
                                            var name = $("#product-list li[data-product-id = \'"+i+"\'] label");
                                            var block = name;
                                        } else {
                                            var name = $("#product-list tr[data-product-id = \'"+i+"\'] .drag-handle a > div");
                                            var block = name.closest("td");
                                        }
                                        if (name.length && !name.hasClass("has-set")) { 
                                            name.prepend("<i title=\"' . _wp('Product-set') . '\" class=\"icon16\" style=\"vertical-align: middle; background: url(' . $this->getPluginStaticUrl() . '/img/itemsets.png) no-repeat\"></i>").addClass("has-set");
                                            var html = "<div style=\"margin: 5px 0 0 20px; height: inherit;\" class=\"small\">"+
                                                        "<a href=\"javascript:void(0)\" style=\"color: #03c;\" class=\"inline-link f-itemset-toggle\" data-toggle=\"- ' . _wp('collapse product-set') . '\"><b><i>+ ' . _wp('expand product-set') . '</i></b></a>";
                                                        $.each(skus, function(sku_id, sku) {
                                                            html += "<ul style=\"display: none;\" class=\"itemsets-list\">";
                                                            $.each(sku, function(id, s) {
                                                                html += "<li><a style=\"color: #03c;\" href=\"?action=products#/product/"+s["item_id"]+"/\">"+s["name"]+(s["sku_name"] ? " ("+s["sku_name"]+") " : " ")+" - <span style=\"color: #000;\">"+s["quantity"]+"</span> "+(s["sku_stock_id"] !== "0" && typeof(s["stock"][s["sku_stock_id"]]) !== "undefined" ? s["stock"][s["sku_stock_id"]]["icon"]+" @"+s["stock"][s["sku_stock_id"]]["name"] : s["icon"])+"</a></li>";
                                                            });
                                                            html += "</ul>";
                                                        });
                                                        html += "</div>";
                                            block.append(html);    
                                        }
                                    });
                                });

                                $(document).off("click", ".f-itemset-toggle").on("click", ".f-itemset-toggle", function() {
                                    var btn = $(this);
                                    var set = btn.siblings("ul");
                                    var newToggle = btn.attr("data-toggle");
                                    var oldToggle = btn.find("i").text();
                                    if (set.hasClass("open")) {
                                        set.removeClass("open").hide();
                                    } else {
                                        set.addClass("open").show();
                                    }
                                    btn.attr("data-toggle", oldToggle).find("i").text(newToggle);
                                });
                            });
                        })(jQuery);
                   </script>';
                $sidebar_top_li  = '<li id="s-itemsets">
                                    <span class="count">' . count($product_ids) . '</span>
                                    <a href="#/itemsets/"><i title="' . _wp('Product-sets') . '" class="icon16" style="background: url(' . $this->getPluginStaticUrl() . '/img/itemsets.png) no-repeat"></i> '._wp('Product-sets').'</a>
                                    <script type="text/javascript">
                                        $.products.itemsetsAction = function(params) {
                                            var url = "?module=products";
                                            if (params) {
                                                url += "&"+params;
                                            } else {
                                                url += "&hash=itemsets";
                                            }
                                            $.products.load(url, function() {
                                                $(".s-product-nav a").each(function() {
                                                    $(this).on("click", function() {
                                                        $.products.itemsetsAction($(this).attr("href").replace("#/products/", ""));
                                                        return false;
                                                    });
                                                });
                                            });
                                            return false;
                                        };    
                                    </script>
                                    </li>';
            } 
            return array('toolbar_section' => $js, 'sidebar_top_li' => $sidebar_top_li);
        }
    }

    public function orderActionPay($params)
    {
        $settings = shopItemsetsHelper::getSettings();
        if ($settings['enable']) {
            $app_settings_model = new waAppSettingsModel();
            $update_on_create = $app_settings_model->get('shop', 'update_stock_count_on_create_order');

            if (!$update_on_create && $params['before_state_id'] == 'new') {
                $sim = new shopItemsetsPluginModel();
                $sim->reduceProductsFromStocks($params['order_id'], 'Order %s was paid');
            }
        }
    }

    public function orderActionShip($params)
    {
        $settings = shopItemsetsHelper::getSettings();
        if ($settings['enable']) {
            $app_settings_model = new waAppSettingsModel();
            $update_on_create = $app_settings_model->get('shop', 'update_stock_count_on_create_order');

            if (!$update_on_create && $params['before_state_id'] == 'new') {
                $sim = new shopItemsetsPluginModel();
                $sim->reduceProductsFromStocks($params['order_id'], 'Order %s was shipped');
            }
        }
    }

    public function orderActionRestore($params)
    {
        $settings = shopItemsetsHelper::getSettings();
        if ($settings['enable']) {
            if ($params['before_state_id'] != 'refunded') {
                $app_settings_model = new waAppSettingsModel();
                $sim = new shopItemsetsPluginModel();

                $update_on_create = $app_settings_model->get('shop', 'update_stock_count_on_create_order');
                if ($update_on_create) {
                    $sim->reduceProductsFromStocks($params['order_id'], 'Order %s was restored');
                } else if (!$update_on_create && $params['before_state_id'] != 'new') {
                    $sim->reduceProductsFromStocks($params['order_id'], 'Order %s was restored');
                }
            }
        }
    }

    public function orderActionProcess($params)
    {
        $settings = shopItemsetsHelper::getSettings();
        if ($settings['enable']) {
            $app_settings_model = new waAppSettingsModel();
            if (!$app_settings_model->get('shop', 'update_stock_count_on_create_order')) {
                $sim = new shopItemsetsPluginModel();
                $sim->reduceProductsFromStocks($params['order_id'], 'Order %s was processed');
            }
        }
    }

    public function orderActionRefund($params)
    {
        $settings = shopItemsetsHelper::getSettings();
        if ($settings['enable']) {
            $sim = new shopItemsetsPluginModel();
            $sim->returnProductsToStocks($params['order_id'], 'Order %s was refunded');
        }
    }

    public function orderActionDelete($params)
    {
        $settings = shopItemsetsHelper::getSettings();
        if ($settings['enable']) {
            if ($params['before_state_id'] != 'refunded') {
                $app_settings_model = new waAppSettingsModel();
                $sim = new shopItemsetsPluginModel();
                $update_on_create = $app_settings_model->get('shop', 'update_stock_count_on_create_order');
                if ($update_on_create) {
                    $sim->returnProductsToStocks($params['order_id'], 'Order %s was deleted');
                } else if (!$update_on_create && $params['before_state_id'] != 'new') {
                    $sim->returnProductsToStocks($params['order_id'], 'Order %s was deleted');
                }
            }
        }
    }

    public function orderActionComplete($params)
    {
        $settings = shopItemsetsHelper::getSettings();
        if ($settings['enable']) {
            $app_settings_model = new waAppSettingsModel();
            $update_on_create = $app_settings_model->get('shop', 'update_stock_count_on_create_order');

            if (!$update_on_create && $params['before_state_id'] == 'new') {
                $sim = new shopItemsetsPluginModel();
                $sim->reduceProductsFromStocks($params['order_id'], 'Order %s was completed');
            }
        }
    }

    public function orderActionCreate($params)
    {
        $settings = shopItemsetsHelper::getSettings();
        if ($settings['enable']) {
            $app_settings_model = new waAppSettingsModel();
            if ($app_settings_model->get('shop', 'update_stock_count_on_create_order')) {
                $sim = new shopItemsetsPluginModel();
                $sim->reduceProductsFromStocks($params['order_id'], 'Order %s was placed');
            }
        }
    }
    
//    public function orderActionEdit($params)
//    {
//        $settings = shopItemsetsHelper::getSettings();
//        if ($settings['enable']) {
//            $app_settings_model = new waAppSettingsModel();
//            if ($app_settings_model->get('shop', 'update_stock_count_on_create_order')) {
//                $sim = new shopItemsetsPluginModel();
//                // Получаем список комплектов, входящих в заказ
//                $items = $sim->getItemsByOrderId($params['order_id']);
//               
//                $order_params_model = new shopItemsetsOrderParamsPluginModel();
//                $reduced = $order_params_model->getOne($params['order_id'], 'reduced');
//                if (!empty($items['set_items'])) {
//                    if ($reduced) {
//                        $sim->returnProductsToStocks($params['order_id'], 'Order %s was placed');
//                    }
//                    $sim->reduceProductsFromStocks($params['order_id'], 'Order %s was placed');
//                } else {
//                    if ($reduced) {
//                        $sim->returnProductsToStocks($params['order_id'], 'Order %s was placed');
//                    }
//                }
//            }
//        }
//    }

    public function backendOrderEdit($order)
    {
        $settings = shopItemsetsHelper::getSettings();
        if ($settings['enable']) {
            if (!empty($order['items'])) {
                $sim = new shopItemsetsPluginModel();
                $order_product_ids = array();
                // Собираем ID товаров, которые в заказе
                foreach ($order['items'] as $oi) {
                    $order_product_ids[] = $oi['id'];
                }
                // Получаем id товаров, у которых имеются комплекты и отмечаем эти товары в списке
                $product_ids = $sim->getProductIds($order_product_ids);

                if ($product_ids) {
                    return '<script type="text/javascript">
                            (function($) { 
                                $(function() {
                                    var ids = ' . json_encode($product_ids) . ';
                                    for (var i in ids) {
                                        var item = $("#order-items tr.s-order-item[data-product-id= \'"+ids[i]+"\'] strong.large");
                                        if (item.length) {
                                            item.prepend("<i title=\"' . _wp('Product-set') . '\" class=\"icon16\" style=\"background: url(' . $this->getPluginStaticUrl() . '/img/itemsets.png) no-repeat\"></i>");
                                        }
                                    }
                                });
                            })(jQuery);
                        </script>';
                }
            }
        }
    }

    public function backendOrder($order)
    {
        $settings = shopItemsetsHelper::getSettings();
        if ($settings['enable']) {
            if (!empty($order['items'])) {
                $sim = new shopItemsetsPluginModel();
                $order_product_ids = array();
                // Собираем ID товаров, которые в заказе
                foreach ($order['items'] as $order_item_id => $oi) {
                    $order_product_ids[$oi['product_id']][$oi['sku_id']] = $order_item_id;
                }
                // Получаем id товаров, у которых имеются комплекты и отмечаем эти товары в списке
                $product_ids = $sim->getProductIds(array_keys($order_product_ids));
                if ($product_ids) {
                    $order_item_ids = array();
                    // Для того чтобы отметить товары-комплекты, необходимо знать ID товаров в заказе
                    foreach ($product_ids as $pi) {
                        foreach ($order_product_ids[$pi] as $sku_id => $opi) {
                            $order_item_ids[$opi] = $sim->getItems($pi, $sku_id);
                        }
                    }
                    return array(
                        'info_section' => '<script type="text/javascript">
                    (function($) { 
                        $(function() {
                            var ids = ' . json_encode($order_item_ids) . ';
                            $.each(ids, function(i, skus) {
                                var item = $("#s-order-items tr[data-id = \'"+i+"\'] a");  
                                if (item.length && !item.hasClass("has-set")) {
                                    item.prepend("<i title=\"' . _wp('Product-set') . '\" class=\"icon16\" style=\"background: url(' . $this->getPluginStaticUrl() . '/img/itemsets.png) no-repeat\"></i>").addClass("has-set");
                                    var html = "<div style=\"margin: 5px 0 0 20px;\" class=\"small\">"+
                                                    "<a href=\"javascript:void(0)\" class=\"inline-link f-itemset-toggle\" data-toggle=\"- ' . _wp('collapse product-set') . '\"><b><i>+ ' . _wp('expand product-set') . '</i></b></a>";
                                                    $.each(skus, function(sku_id, sku) {    
                                                        html += "<ul style=\"display: none;\">";
                                                        $.each(sku, function(id, s) {
                                                            html += "<li><a href=\"?action=products#/product/"+s["item_id"]+"/\">"+s["name"]+(s["sku_name"] ? " ("+s["sku_name"]+") " : " ")+" - <span style=\"color: #000;\">"+s["quantity"]+"</span> "+(s["sku_stock_id"] !== "0" && typeof(s["stock"][s["sku_stock_id"]]) !== "undefined" ? s["stock"][s["sku_stock_id"]]["icon"]+" @"+s["stock"][s["sku_stock_id"]]["name"] : s["icon"])+"</a></li>";
                                                        });
                                                        html += "</ul>";
                                                    });
                                                html += "</div>";
                                    item.closest("td").append(html);
                                }
                            });
                            $(".f-itemset-toggle").click(function() {
                                var btn = $(this);
                                var set = btn.next();
                                var newToggle = btn.attr("data-toggle");
                                var oldToggle = btn.find("i").text();
                                if (set.hasClass("open")) {
                                    set.removeClass("open").hide();
                                } else {
                                    set.addClass("open").show();
                                }
                                btn.attr("data-toggle", oldToggle).find("i").text(newToggle);
                            });
                        });
                    })(jQuery);
                        </script>',
                    );
                }
            }
        }
    }

    public function productDelete($params)
    {
        $settings = shopItemsetsHelper::getSettings();
        if ($settings['enable']) {
            if (!empty($params['ids'])) {
                $sim = new shopItemsetsPluginModel();
                // Список комплектов, в которых присутствовал товар
                $product_ids = $sim->getProductIds($params['ids'], 'item_id');
                // Удаляем комплекты и их настройки
                $sim->delete($params['ids']);
                // Выполняем перерасчет остатков для товаров-комплектов на случай, если удаляемые товары присутствовали в комплектах
                if ($product_ids) {
                    foreach ($product_ids as $pi) {
                        shopItemsetsHelper::recountSkus($pi);
                    }
                }
            }
        }
    }

    public function frontendCart()
    {
        $settings = shopItemsetsHelper::getSettings();
        if ($settings['enable']) {

            // Если покупателям можно совершать заказы при отрицательном остатке, то прекращаем проверку
            if (wa('shop')->getConfig()->getGeneralSettings('ignore_stock_count')) {
                return;
            }

            $cart_model = new shopCartItemsModel();
            $cart = new shopCart();
            $code = $cart->getCode();
            // Получем все товары, находящиеся в корзине
            $items = $cart_model->where('code= ?', $code)->order('parent_id')->fetchAll('id');

            // Проблемные товары-комплекты и обычные товары
            $errors = shopItemsetsHelper::cartCheck($items);

            $url = wa()->getRouteUrl('shop/frontend/itemsetsCartRefresh');
            return "
                <script type='text/javascript'>
                    (function($) { 
                        $(function() {
                            $.itemsetsFrontend.initCart({
                                url: '" . $url . "',
                                allowCheckout: '" . !empty($settings['allow_checkout']) . "',
                                locale: '" . wa()->getLocale() . "'
                                 " . ( $errors['error_ids'] ? ", errorIds:" . json_encode($errors['error_ids']) : '') . "
                                 " . ( $errors['error_item_ids'] ? ", errorItemIds:" . json_encode($errors['error_item_ids']) : '') . "
                            });
                        });
                    })(jQuery);
                </script>";
        }
    }

    public function frontendProduct($product)
    {
        $settings = shopItemsetsHelper::getSettings();
        if ($settings['enable']) {
            $output = array(
                'menu' => '', 'cart' => '', 'block_aux' => '', 'block' => ''
            );
            $sis = new shopItemsetsPluginModel();
            $set_settings = $sis->getSet($product->id, true);
            if ($set_settings && $set_settings['show_items']) {
                foreach (array('menu', 'cart', 'block_aux', 'block') as $hook) {
                    // Выводим комплектующие в местах, указанных пользователем в настройках, учитывая шаблон для вывода
                    if (in_array($hook, $settings['output_places'])) {
                        $output[$hook] .= self::showItems($product, 0, $settings['output_file'] ? $settings['output_file'] : '', true, false);
                    }
                }
            }
            return $output;
        }
    }

    public function frontendHead()
    {
        $settings = shopItemsetsHelper::getSettings();
        if ($settings['enable']) {
            return "<link rel='stylesheet' href='" . shopItemsetsPluginHelper::getCssUrl() . "'>
                <style type='text/css'>
                    i.itemsets-pl.loader { background: url(" . shopItemsetsPluginHelper::getCdn() . wa()->getAppStaticUrl('shop') . "plugins/itemsets/img/loader.gif) no-repeat; width: 43px; height: 11px; display: inline-block; }
                </style>
                <script type='text/javascript' src='" . shopItemsetsPluginHelper::getCdn() . wa()->getAppStaticUrl('shop') . "plugins/itemsets/js/itemsetsFrontend.js'></script>
                <script type='text/javascript' src='" . shopItemsetsPluginHelper::getFrontendLocaleJSUrl() . "'></script>
                <script type='text/javascript'>
                    (function($) { 
                        $(function() {
                            $.itemsetsFrontend.init();
                        });
                    })(jQuery);
                </script>";
        }
    }

    /**
     * Output HTML of product-set items 
     * 
     * @param object|array|int $product
     * @param int $sku_id
     * @param string $template
     * @param bool $html_display
     * @param bool $use_as_helper
     * @return string - HTML code
     */
    public static function showItems($product, $sku_id = 0, $template = '', $html_display = true, $use_as_helper = true)
    {
        $settings = shopItemsetsHelper::getSettings();
        if ($settings['enable'] && $product) {
            if (gettype($product) == 'object') {
                $product = $product->getData();
                $product_id = $product['id'];
            } elseif (gettype($product) == 'array') {
                $product_id = (int) $product['id'];
            } else {
                $product_id = (int) $product;
            }
            if ($product_id) {
                // Если функция вызвана, как хелпер, то проверяем доступен ли комплект для вывода
                $psm = new shopItemsetsPluginModel();
                $set_settings = $psm->getSet($product_id, true);
                if ($use_as_helper) {
                    if (!$set_settings || ($set_settings && !$set_settings['show_items'])) {
                        return '';
                    }
                }
                $html = shopItemsetsHelper::getSetItemsHTML($product_id, $sku_id, $template);
                if ($html) {
                    $html = "<div class='itemsets-block-" . $product_id . "'>" . ($html_display ? $settings['html_before'] . $html . '<div style="clear: both;">' . $settings['html_after'] . '</div>' : $html) . "</div>";
                    return $html;
                }
                return '';
            }
        }
    }

    /**
     * Check if set exist and has items
     * 
     * @param object|array|int $product
     * @param int $sku_id
     * @return false
     */
    public static function hasItems($product, $sku_id = 0)
    {
        $settings = shopItemsetsHelper::getSettings();
        if ($settings['enable'] && $product) {
            if (gettype($product) == 'object') {
                $product = $product->getData();
                $product_id = $product['id'];
            } elseif (gettype($product) == 'array') {
                $product_id = (int) $product['id'];
            } else {
                $product_id = (int) $product;
            }
            if ($product_id) {
                $psm = new shopItemsetsPluginModel();
                return $psm->issetSet($product_id, $sku_id);
            }
        }
        return false;
    }

    public function productsCollection($params)
    {
        $collection = $params['collection'];
        $hash = $collection->getHash();
        if ($hash[0] !== 'itemsets') {
            return null;
        }
        $collection->addJoin('shop_itemsets');
        $collection->addTitle(_wp('Product-sets'));
        return true;
    }
    
    /**
     * Check if product is a set item
     * 
     * @param object|array|int $product
     * @param int $sku_id
     * @return false
     */
    public static function isSetItem($product, $sku_id = 0)
    {
        $settings = shopItemsetsHelper::getSettings();
        if ($settings['enable'] && $product) {
            if (gettype($product) == 'object') {
                $product = $product->getData();
                $product_id = $product['id'];
            } elseif (gettype($product) == 'array') {
                $product_id = (int) $product['id'];
            } else {
                $product_id = (int) $product;
            }
            if ($product_id) {
                $products = array();
                $psm = new shopItemsetsPluginModel();
                if ($ids = $psm->getProductIds((array) $product_id, 'item_id')) {
                    // Получаем комплекты, в которых присутствует товар
                    $pm = new shopProductModel();
                    $products = $pm->getById($ids);
                }
                
                return $products;
            }
        }
        return false;
    }

}
