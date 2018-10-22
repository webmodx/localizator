<?php
//ini_set("display_errors",1);
//error_reporting(E_ALL);
/**
 * Loads the TV panel for MIGX.
 *
 * Note: This page is not to be accessed directly.
 *
 * @package migx
 * @subpackage processors
 */

class localizatorFormProcessor extends modProcessor {

    public function process() {

        require_once MODX_CORE_PATH.'model/modx/modmanagercontroller.class.php';
        require_once MODX_CORE_PATH . 'components/localizator/model/localizator/localizatorformcontroller.class.php';
        $controller = new LocalizatorFormController($this->modx);
        $this->modx->controller = &$controller;

        $this->modx->getService('smarty', 'smarty.modSmarty');
        $localizator = $this->modx->getService('localizator');
        $scriptProperties = $this->getProperties();

        $localizator->working_context = 'web';

        if ($this->modx->resource = $this->modx->getObject('modResource', $scriptProperties['resource_id'])) {
            $localizator->working_context = $this->modx->resource->get('context_key');
        }

        $controller->loadTemplatesPath();

        $controller->setPlaceholder('_config', $this->modx->config);
        $this->modx->lexicon->load('core:resource');
        $this->modx->lexicon->load('core:default');

        /*actual record */
        if ($loc = $this->modx->getObject('localizatorContent', $scriptProperties['loc_id'])){
            $scriptProperties['isnew'] = 0;
        }
        else{
            $loc = $this->modx->newObject('localizatorContent');
            $loc->set('resource_id', $scriptProperties['resource_id']);
            $scriptProperties['isnew'] = 1;
        }
        $record = $loc->toArray();

        $allfields = array();
        $formtabs = [
            'document' => [
                'caption' => $this->modx->lexicon('document'),
                'fields' => [
                    [
                        'field' => 'id',
                        'inputTVtype' => 'hidden',
                    ],
                    [
                        'field' => 'key',
                        'caption' => $this->modx->lexicon('localizator_language'),
                        'inputTVtype' => 'listbox',
                        'inputOptionValues' => '@SELECT `name`,`key` FROM `[[+PREFIX]]localizator_languages` WHERE `active` = 1',
                    ],
                    [
                        'field' => 'pagetitle',
                        'caption' => $this->modx->lexicon('resource_pagetitle'),
                    ],
                    [
                        'field' => 'longtitle',
                        'caption' => $this->modx->lexicon('resource_longtitle'),
                    ],
                    [
                        'field' => 'menutitle',
                        'caption' => $this->modx->lexicon('resource_menutitle'),
                    ],
                    [
                        'field' => 'description',
                        'inputTVtype' => 'textarea',
                        'caption' => $this->modx->lexicon('resource_description'),
                    ],
                    [
                        'field' => 'introtext',
                        'inputTVtype' => 'textarea',
                        'caption' => $this->modx->lexicon('introtext'),
                    ],
                    [
                        'field' => 'seotitle',
                        'caption' => $this->modx->lexicon('localizator_seotitle'),
                    ],
                    [
                        'field' => 'keywords',
                        'caption' => $this->modx->lexicon('localizator_keywords'),
                    ],
                    [
                        'field' => 'content',
                        'caption' => $this->modx->lexicon('resource_content'),
                        'inputTVtype' => 'richtext',
                    ],
                ],
            ],
        ];
        
        foreach ($loc->getTemplateVars(true) as $tv){
            if (!isset($formtabs[$tv['category_id']])){
                $formtabs[$tv['category_id']]= [
                    'caption' => $tv['category_name'] ? $tv['category_name'] : $this->modx->lexicon('no_category'),
                    'fields' => [],
                ];
            }
            $formtabs[$tv['category_id']]['fields'][] = [
                'field' => $tv['name'],
                'caption' => $tv['caption'],
                'description' => $tv['description'],
                'inputTV' => $tv['name'],
            ];
        }

        $categories = array();
        $result = $localizator->createForm($formtabs, $record, $allfields, $categories, $scriptProperties);

        if (isset($result['error'])){
            $controller->setPlaceholder('error', $result['error']);
        }
        
        $controller->setPlaceholder('formcaption', '');
        $controller->setPlaceholder('fields', $this->modx->toJSON($allfields));
        $controller->setPlaceholder('categories', $categories);
        $controller->setPlaceholder('resource_id', $loc->get('resource_id'));
        $controller->setPlaceholder('formAction', $scriptProperties['isnew'] ? 'create' : 'update');
        $controller->setPlaceholder('properties', $scriptProperties);
        //Todo: check for MIGX and MIGXdb, if tv_id is needed.
        $controller->setPlaceholder('win_id', $scriptProperties['win_id']);

        if (!empty($_REQUEST['showCheckbox'])) {
            $controller->setPlaceholder('showCheckbox', 1);
        }
        else{
            $controller->setPlaceholder('showCheckbox', 0);
        }


        return $controller->process($scriptProperties);

    }
}
return 'localizatorFormProcessor';
