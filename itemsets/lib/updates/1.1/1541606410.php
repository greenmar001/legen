<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

// Чистка мусора
$files = array(
    'css' => wa()->getAppPath("plugins/itemsets/css/itemsetsFrontend.css", "shop"),
    'js' => wa()->getAppPath("plugins/itemsets/js/itemsetsFrontendLocale.js", "shop"),
);

try {
    foreach ($files as $type => $file) {
        if (file_exists($file)) {
            waFiles::move($file, $type == 'css' ? wa()->getDataPath('plugins/itemsets/css/itemsetsFrontend.css', true, 'shop', true) : wa()->getDataPath('plugins/itemsets/js/itemsetsFrontendLocale.js', true, 'shop', true));
        }
    }
} catch (Exception $e) {

}