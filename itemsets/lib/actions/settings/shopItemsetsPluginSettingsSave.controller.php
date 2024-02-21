<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopItemsetsPluginSettingsSaveController extends waJsonController
{

    public function execute()
    {
        // Сохранение файла CSS
        $css = waRequest::post('css', '');
        $paths = shopItemsetsPluginHelper::getCssPaths();
        file_put_contents($paths['changed'], $css);

        // Сохранение файла JS локализации
        $js = waRequest::post('js', null);
        if ($js !== null) {
            $js_paths = shopItemsetsPluginHelper::getFrontendLocaleJSPaths();
            file_put_contents($js_paths['changed'], $js);
        }
        // Сохранение файла шаблона
        $template = waRequest::post('template', null);
        if ($template !== null && is_array($template)) {
            foreach ($template as $template_name => $t) {
                $template_paths = shopItemsetsPluginHelper::getTemplatePaths($template_name);
                file_put_contents($template_paths['changed'], $t);
            }
        }
        $sm = new shopItemsetsSettingsPluginModel();
        $settings = waRequest::post('settings', array());
        // Удаляем старые настройки
        $sm->deleteByField('field', 'settings');
        $settings['enable'] = isset($settings['enable']) ? 1 : 0;
        $sm->save('settings', $settings);
    }

}
