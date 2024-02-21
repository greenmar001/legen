<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */
return array(
    'shop_itemsets_settings' => array(
        'field' => array('varchar', 30, 'null' => 0),
        'ext' => array('varchar', 20, 'null' => 0),
        'value' => array('varchar', 50, 'null' => 0, 'default' => ''),
        'text' => array('text'),
        ':keys' => array(
            'PRIMARY' => array('field', 'ext', 'value'),
            'field' => 'field'
        ),
        ':options' => array('engine' => 'MyISAM')
    ),
    'shop_itemsets_order_params' => array(
        'order_id' => array('int', 11, 'null' => 0),
        'name' => array('varchar', 64, 'null' => 0),
        'value' => array('varchar', 255, 'null' => 0),
        ':keys' => array(
            'PRIMARY' => array('order_id', 'name'),
        ),
        ':options' => array('engine' => 'MyISAM')
    ),
    'shop_itemsets' => array(
        'product_id' => array('int', 11, 'null' => 0),
        'product_sku_id' => array('int', 11, 'null' => 0),
        'item_id' => array('int', 11, 'null' => 0),
        'item_sku_id' => array('int', 11, 'null' => 0),
        'quantity' => array('int', 11, 'default' => 1),
        'discount' => array('decimal', '12,2', 'default' => 0.00),
        'currency' => array('varchar', 3),
        'sort_id' => array('int', 5, 'default' => 0),
        'stock_id' => array('int', 11, 'default' => 0),
        ':keys' => array(
            'PRIMARY' => array('product_id', 'product_sku_id', 'item_id', 'item_sku_id'),
        ),
        ':options' => array('engine' => 'MyISAM')
    ),
);
