<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

return array(
    'name' => /*_wp*/('Product-sets'),
    'description' => /*_wp*/('Creation of product-sets like a unique unit'),
    'img' => 'img/itemsets.png',
    'vendor' => '969712',
    'version' => '991.1.2',
    'frontend' => true,
    'shop_settings' => true,
    'handlers' => array(
        'product_save' => 'productSave',
        'product_delete' => 'productDelete',
        'backend_product' => 'backendProduct',
        'backend_products' => 'backendProducts',
        'backend_order' => 'backendOrder',
        'backend_order_edit' => 'backendOrderEdit',
        'order_action.create' => 'orderActionCreate',
//        'order_action.edit' => 'orderActionEdit',
        'order_action.complete' => 'orderActionComplete',
        'order_action.delete' => 'orderActionDelete',
        'order_action.process' => 'orderActionProcess',
        'order_action.pay' => 'orderActionPay',
        'order_action.restore' => 'orderActionRestore',
        'order_action.ship' => 'orderActionShip',
        'order_action.refund' => 'orderActionRefund',
        'frontend_cart' => 'frontendCart',
        'frontend_product' => 'frontendProduct',
        'frontend_head' => 'frontendHead',
        'products_collection' => 'productsCollection',
    ),
);
