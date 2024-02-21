<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopItemsetsPluginBackendHandlerController extends waJsonController
{

    public function execute()
    {
        $action = waRequest::post('action', '');

        switch ($action) {
            // Перерасчет остатков
            case "recountStocks":
                $sim = new shopItemsetsPluginModel();
                $product_ids = $sim->getProductIds();
                if ($product_ids) {
                    foreach ($product_ids as $pi) {
                        shopItemsetsHelper::recountSkus($pi);
                    }
                }
                break;
            // Перерасчет остатков для товара
            case "forceRecountStocks":
                $sim = new shopItemsetsPluginModel();
                shopItemsetsHelper::recountSkus(waRequest::post("id", 0), true);
                break;
            // Сброс стилей к оригинальному варианту
            case "resetCss":
                $paths = shopItemsetsPluginHelper::getCssPaths();
                waFiles::delete($paths['changed']);
                $this->response = file_get_contents($paths['original']);
                break;
            // Сброс файла локали JS к оригинальному варианту
            case "resetJs":
                $paths = shopItemsetsPluginHelper::getFrontendLocaleJSPaths();
                waFiles::delete($paths['changed']);
                $this->response = file_get_contents($paths['original']);
                break;
            // Сброс файла шаблона
            case "resetTemplate":
                $template = waRequest::post('template', '', waRequest::TYPE_STRING_TRIM);
                if ($template) {
                    $paths = shopItemsetsPluginHelper::getTemplatePaths($template);
                    waFiles::delete($paths['changed']);
                    $this->response = file_get_contents($paths['original']);
                } else {
                    $this->errors = 1;
                }
                break;
        }
    }

}
