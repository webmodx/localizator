<?php

class localizator
{
    /** @var modX $modx */
    public $modx;


    /**
     * @param modX $modx
     * @param array $config
     */
    function __construct(modX &$modx, array $config = array())
    {
        $this->modx =& $modx;

        $corePath = $this->modx->getOption('localizator_core_path', $config,
            $this->modx->getOption('core_path') . 'components/localizator/'
        );
        $assetsUrl = $this->modx->getOption('localizator_assets_url', $config,
            $this->modx->getOption('assets_url') . 'components/localizator/'
        );
        $connectorUrl = $assetsUrl . 'connector.php';

        $this->config = array_merge(array(
            'assetsUrl' => $assetsUrl,
            'cssUrl' => $assetsUrl . 'css/',
            'jsUrl' => $assetsUrl . 'js/',
            'imagesUrl' => $assetsUrl . 'images/',
            'connectorUrl' => $connectorUrl,

            'corePath' => $corePath,
            'modelPath' => $corePath . 'model/',
            'chunksPath' => $corePath . 'elements/chunks/',
            'templatesPath' => $corePath . 'elements/templates/',
            'chunkSuffix' => '.chunk.tpl',
            'snippetsPath' => $corePath . 'elements/snippets/',
            'processorsPath' => $corePath . 'processors/',
        ), $config);

        $this->modx->addPackage('localizator', $this->config['modelPath']);
        $this->modx->lexicon->load('localizator:default');
    }

	// prepare text for curl request
	function translator_prepare($text, $limit = 2000) {
	    if ($limit > 0) {
	        $ret = array();
	        $limiten = mb_strlen($text, "UTF-8");
	        for ($i = 0; $i < $limiten; $i += $limit) {
	            $ret[] = mb_substr($text, $i, $limit, "UTF-8");
	        }
	        return $ret;
	    }
	    return preg_split("//u", $text, -1, PREG_SPLIT_NO_EMPTY);
	}

	// https://tech.yandex.ru/translate/doc/dg/concepts/About-docpage/
	function translator_Yandex($text, $from, $to) {
		if(!$text) return;
		$output = '';
		$data = array(
			'key' => $this->modx->getOption('localizator_key_yandex'),
		    'lang' => $from . '-' . $to,
		    'format' => 'html',
		);

		$text = $this->translator_prepare($text);
		foreach($text as $part) {
			$data['text'] = $part;
			$ch = curl_init('https://translate.yandex.net/api/v1.5/tr.json/translate');
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data,'','&'));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$response = curl_exec($ch);
			$response = json_decode($response, true);
			if($response['code'] == 200) {
				$output .= implode('', $response['text']);
			} else {
				$this->modx->log(1, 'localizator: yandex error - ' . $response['code'] .', see https://tech.yandex.ru/translate/doc/dg/reference/translate-docpage/');
			}
		}

		return $output;
	}

	function createForm(&$tabs, &$record, &$allfields, &$categories, $scriptProperties) {

        $input_prefix = $this->modx->getOption('input_prefix', $scriptProperties, '');
        $input_prefix = !empty($input_prefix) ? $input_prefix . '_' : '';
        $rte = isset($scriptProperties['which_editor']) ? $scriptProperties['which_editor'] : $this->modx->getOption('which_editor', '', $this->modx->_userConfig);
        

        foreach ($tabs as $tabid => $tab) {
            $tvs = array();
            $fields = $this->modx->getOption('fields', $tab, array());
            $fields = is_array($fields) ? $fields : $this->modx->fromJson($fields);
            if (is_array($fields) && count($fields) > 0) {

                foreach ($fields as &$field) {

                    $fieldname = $this->modx->getOption('field', $field, '');
                    $useDefaultIfEmpty = $this->modx->getOption('useDefaultIfEmpty', $field, 0);

                    /*generate unique tvid, must be numeric*/
                    /*todo: find a better solution*/
                    $field['tv_id'] = 'localizator_'.$fieldname;
                    $params = array();
                    $tv = false;


                    if (isset($field['inputTV']) && $tv = $this->modx->getObject('modTemplateVar', array('name' => $field['inputTV']))) {
                        $params = $tv->get('input_properties');
                        $params['inputTVid'] = $tv->get('id');
                    }

                    if (!empty($field['inputTVtype'])) {
                        $tv = $this->modx->newObject('modTemplateVar');
                        $tv->set('type', $field['inputTVtype']);
                    }

                    if (!$tv) {
                        $tv = $this->modx->newObject('modTemplateVar');
                        $tv->set('type', 'text');
                    }

                    $o_type = $tv->get('type');
                    
                    if ($tv->get('type') == 'richtext') {
                        $tv->set('type', 'migx' . str_replace(' ','_',strtolower($rte)));
                    }

                    //we change the phptype, that way we can use any id, not only integers (issues on windows-systems with big integers!)
                    $tv->_fieldMeta['id']['phptype'] = 'string';

                    if (!empty($field['inputOptionValues'])) {
                        $tv->set('elements', $field['inputOptionValues']);
                    }
                    if (!empty($field['default'])) {
                        $tv->set('default_text', $tv->processBindings($field['default']));
                    }
                    if (isset($field['display'])) {
                        $tv->set('display', $field['display']);
                    }
                    if (!empty($field['configs'])) {
                        $cfg = $this->modx->fromJson($field['configs']);
                        if (is_array($cfg)) {
                            $params = array_merge($params, $cfg);
                        } else {
                            $params['configs'] = $field['configs'];
                        }
                    }

                    /*insert actual value from requested record, convert arrays to ||-delimeted string */
                    $fieldvalue = '';
                    if (isset($record[$fieldname])) {
                        $fieldvalue = $record[$fieldname];
                        if (is_array($fieldvalue)) {
                            $fieldvalue = is_array($fieldvalue[0]) ? $this->modx->toJson($fieldvalue) : implode('||', $fieldvalue);
                        }
                    }

                    $tv->set('value', $fieldvalue);

                    if (!empty($field['caption'])) {
                        $field['caption'] = htmlentities($field['caption'], ENT_QUOTES, $this->modx->getOption('modx_charset'));
                        $tv->set('caption', $field['caption']);
                    }



                    $desc = '';
                    if (!empty($field['description'])) {
                        $desc = $field['description'];
                        $field['description'] = htmlentities($field['description'], ENT_QUOTES, $this->modx->getOption('modx_charset'));
                        $tv->set('description', $field['description']);
                    }

                    
                    $allfield = array();
                    $allfield['field'] = $fieldname;
                    $allfield['tv_id'] = $field['tv_id'];
                    $allfield['array_tv_id'] = $field['tv_id'] . '[]';
                    $allfields[] = $allfield;

                    $field['array_tv_id'] = $field['tv_id'] . '[]';
                    $mediasource = $this->getFieldSource($field, $tv);
                    
                    $tv->setSource($mediasource);
                    $tv->set('id', $field['tv_id']);

                    /*
                    $default = $tv->processBindings($tv->get('default_text'), $resourceId);
                    if (strpos($tv->get('default_text'), '@INHERIT') > -1 && (strcmp($default, $tv->get('value')) == 0 || $tv->get('value') == null)) {
                    $tv->set('inherited', true);
                    }
                    */

                    $isnew = $this->modx->getOption('isnew', $scriptProperties, 0);
                    $isduplicate = $this->modx->getOption('isduplicate', $scriptProperties, 0);


                    if (!empty($useDefaultIfEmpty)) {
                        //old behaviour minus use now default values for checkboxes, if new record
                        if ($tv->get('value') == null) {
                            $v = $tv->get('default_text');
                            if ($tv->get('type') == 'checkbox' && $tv->get('value') == '') {
                                if (!empty($isnew) && empty($isduplicate)) {
                                    $v = $tv->get('default_text');
                                } else {
                                    $v = '';
                                }
                            }
                            $tv->set('value', $v);
                        }
                    } else {
                        //set default value, only on new records
                        if (!empty($isnew) && empty($isduplicate)) {
                            $v = $tv->get('default_text');
                            $tv->set('value', $v);
                        }
                    }


                    $this->modx->smarty->assign('tv', $tv);

                    if (!isset($params['allowBlank']))
                        $params['allowBlank'] = 1;

                    $value = $tv->get('value');
                    if ($value === null) {
                        $value = $tv->get('default_text');
                    }

                    $this->modx->smarty->assign('params', $params);
                    /* find the correct renderer for the TV, if not one, render a textbox */
                    $inputRenderPaths = $tv->getRenderDirectories('OnTVInputRenderList', 'input');

                    if ($o_type == 'richtext') {
                        $fallback = true;
                        foreach ($inputRenderPaths as $path) {
                            $renderFile = $path . $tv->get('type') . '.class.php';
                            if (file_exists($renderFile)) {
                                $fallback = false;
                                break;
                            }
                        }
                        if ($fallback) {
                            $tv->set('type', 'textarea');
                        }
                    }

                    $inputForm = $tv->getRender($params, $value, $inputRenderPaths, 'input', null, $tv->get('type'));
                    $tv->set('formElement', $inputForm);
                    $tvs[] = $tv;
                }
            }

            $cat = array();
            $cat['category'] = $this->modx->getOption('caption', $tab, 'undefined');
            $cat['print_before_tabs'] = isset($tab['print_before_tabs']) && !empty($tab['print_before_tabs']) ? true : false;
            $cat['id'] = $tabid;
            $cat['tvs'] = $tvs;
            $categories[] = $cat;

        }

    }



    function getFieldSource($field, &$tv) {
        //source from config

        $sourcefrom = isset($field['sourceFrom']) && !empty($field['sourceFrom']) ? $field['sourceFrom'] : 'config';

        if ($sourcefrom == 'config' && isset($field['sources'])) {
            if (is_array($field['sources'])) {
                foreach ($field['sources'] as $context => $sourceid) {
                    $sources[$context] = $sourceid;
                }
            } else {
                $fsources = $this->modx->fromJson($field['sources']);
                if (is_array($fsources)) {
                    foreach ($fsources as $source) {
                        if (isset($source['context']) && isset($source['sourceid'])) {
                            $sources[$source['context']] = $source['sourceid'];
                        }
                    }
                }
            }

        }
        
        if (isset($sources[$this->working_context]) && !empty($sources[$this->working_context])) {
            //try using field-specific mediasource from config
            if ($mediasource = $this->modx->getObject('sources.modMediaSource', $sources[$this->working_context])) {
                return $mediasource;
            }
        }
        
        $mediasource = $tv->getSource($this->working_context,false);
        
        //try to get the context-default-media-source
        if (!$mediasource){
            $defaultSourceId = null;
            if ($contextSetting = $this->modx->getObject('modContextSetting',array('key'=>'default_media_source','context_key'=>$this->working_context))){
                $defaultSourceId = $contextSetting->get('value');
            }
            $mediasource = modMediaSource::getDefaultSource($this->modx,$defaultSourceId);
        }

        return $mediasource;
    }

}