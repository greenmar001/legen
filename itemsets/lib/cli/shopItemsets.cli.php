<?php

/**
 * Created by PhpStorm.
 * User: snark | itfrogs.ru
 * Date: 01.08.14
 * Time: 18:17
 */


/**
 * Class shopItemsetsCli
 */
class shopItemsetsCli extends waCliController
{
    /**
     * @throws Exception
     * @throws waException
     */
    public function execute()
    {
        $arg = waRequest::param(0, null);

        if($arg == 'recountStocks') {
            $this->recountStocks();
        }
    }

    private function recountStocks() {
        $sim = new shopItemsetsPluginModel();
        $product_ids = $sim->getProductIds();
        if ($product_ids) {
            foreach ($product_ids as $pi) {
                shopItemsetsHelper::recountSkus($pi);
                if (waSystemConfig::isDebug()) {
                    waLog::dump($pi, 'itemsets_recount_handler.log');
                }

            }
        }
    }

}



