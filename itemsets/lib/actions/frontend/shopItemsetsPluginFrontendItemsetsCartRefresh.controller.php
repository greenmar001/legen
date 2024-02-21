<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopItemsetsPluginFrontendItemsetsCartRefreshController extends waJsonController
{

    public function execute()
    {
        if (wa()->getSetting('ignore_stock_count')) {
            return;
        }
        if (waRequest::method() == 'post') {
            $action = waRequest::post('action', '');
            $cart_id = waRequest::post('cart_id', 0, waRequest::TYPE_INT);
            $quantity = waRequest::post('quantity', 0, waRequest::TYPE_INT);

            // Если было передано значение ID товара в корзине, то продолжаем обработку
            if ($cart_id) {
                $cart_model = new shopCartItemsModel();
                $cart = new shopCart();
                $code = $cart->getCode();
                // Получем все товары, находящиеся в корзине
                $items = $cart_model->where('code= ?', $code)->order('parent_id')->fetchAll('id');

                // Если товар был удален, то исключаем его из списка 
                if ($action == 'delete' && isset($items[$cart_id])) {
                    unset($items[$cart_id]);
                } elseif ($quantity > 0) {
                    $items[$cart_id]['quantity'] = $quantity;
                }

                // Проблемные товары-комплекты и обычные товары
                $errors = shopItemsetsHelper::cartCheck($items);
                if (!$errors['error_ids']) {
                    unset($errors['error_ids']);
                }
                if (!$errors['error_item_ids']) {
                    unset($errors['error_item_ids']);
                }
                $this->response = $errors;
            }
        } else {
            $this->errors = 1;
        }
    }

}
