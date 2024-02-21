<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopItemsetsPluginSettingsAction extends waViewAction
{

    public function execute()
    {
        // Общие настройки
        $settings = shopItemsetsHelper::getSettings();

        // Файл стилей CSS
        $path = shopItemsetsPluginHelper::getCssPath();
        $css = file_get_contents($path);

        // Файлы шаблонов
        $templates = shopItemsetsHelper::getTemplates();
        $template_changed = array();
        foreach ($templates as $t) {
            $template_changed[$t] = shopItemsetsPluginHelper::isTemplateChanged($t);
        }
        $this->view->assign('template_changed', $template_changed);

        $this->view->assign('settings', $settings);
        $this->view->assign('css', $css);
        $this->view->assign('css_changed', shopItemsetsPluginHelper::isCssChanged());
        $this->view->assign('js_changed', shopItemsetsPluginHelper::isFrontendLocaleJSChanged());
        $this->view->assign('lang', substr(wa()->getLocale(), 0, 2));
        $this->view->assign('csrf', waRequest::cookie('_csrf', ''));

        $this->view->assign('plugin_url', wa()->getPlugin('itemsets')->getPluginStaticUrl());
    }

}
