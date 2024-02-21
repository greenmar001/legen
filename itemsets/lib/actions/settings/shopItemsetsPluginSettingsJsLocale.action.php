<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopItemsetsPluginSettingsJsLocaleAction extends waViewAction
{

    public function execute()
    {
        $js_path = shopItemsetsPluginHelper::getFrontendLocaleJSPath();
        $script = file_get_contents($js_path);

        $this->view->assign('script', $script);
        $this->view->assign('isChanged', shopItemsetsPluginHelper::isFrontendLocaleJSChanged());
    }

}
