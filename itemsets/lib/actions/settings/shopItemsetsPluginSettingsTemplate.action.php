<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopItemsetsPluginSettingsTemplateAction extends waViewAction
{

    public function execute()
    {
        $template = waRequest::get('template', '', waRequest::TYPE_STRING_TRIM);
        $template_path = shopItemsetsPluginHelper::getTemplatePath($template);
        if ($template_path) {
            $tmpl = file_get_contents($template_path);
            switch ($template) {
                default:
                    $template_id = "t1";
                    break;
            }
            $this->view->assign('template', $tmpl);
            $this->view->assign('template_name', $template);
            $this->view->assign('template_id', $template_id);
            $this->view->assign('isChanged', shopItemsetsPluginHelper::isTemplateChanged($template));
        }
    }

}
