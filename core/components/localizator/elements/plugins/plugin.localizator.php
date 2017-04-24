<?php
switch($modx->event->name) {
	case 'OnDocFormPrerender':
		$localizator = $modx->getService('localizator');
		$modx->controller->addLexiconTopic('localizator:default');
		$modx->controller->addCss($localizator->config['cssUrl'] . 'mgr/main.css');
        $modx->controller->addCss($localizator->config['cssUrl'] . 'mgr/bootstrap.buttons.css');
		$modx->controller->addJavascript('/assets/components/localizator/js/mgr/localizator.js');
		$modx->controller->addJavascript('/assets/components/localizator/js/mgr/misc/utils.js');
		$modx->controller->addJavascript('/assets/components/localizator/js/mgr/misc/combo.js');
		$modx->controller->addJavascript('/assets/components/localizator/js/mgr/widgets/content.grid.js?v=' . time());
		$modx->controller->addHtml('
		<script type="text/javascript">
        		localizator.config = ' . json_encode($localizator->config) . ';
			localizator.config.connector_url = "' . $localizator->config['connectorUrl'] . '";
			Ext.ComponentMgr.onAvailable("modx-resource-tabs", function() {
				this.on("beforerender", function() {
					this.add({
						title: _("localizator_tab"),
						id: "localizator-resource-tab",
						items: [{
			                xtype: "localizator-grid-content",
							cls: "main-wrapper",
							resource_id: ' . $id . ',
			            }]
					});
				});
			});
        </script>
        ');
		break;

    case 'OnHandleRequest':
		if($modx->context->key == 'mgr' || !$modx->getOption('friendly_urls')) return;
        	$request = &$_REQUEST['q'];
		$host = $find = $_SERVER['HTTP_HOST'];
		if($request) {
			if(strpos($request, '/') !== false) {
				// "site.com/en/blog/article" to "site.com/en/"
				$tmp = explode('/', $request);
				$find = $host . '/' . $tmp[0] . '/';
			} else {
				$find = $host . '/' . $request;
			}
		}
		$q = $modx->newQuery('localizatorLanguage');
		$q->where(array(
		    array('http_host' => $find),
		    array('OR:http_host:=' => $host)
		));
		$q->sortby("FIELD(http_host, '{$find}', '{$host}')");
		$language = $modx->getObject('localizatorLanguage', $q);
		if($language) {
		    $modx->localizator_key = $language->key;
		    $modx->setOption('localizator_key', $modx->localizator_key);
		    $modx->setOption('cache_resource_key', 'resource/' . $modx->localizator_key);

		    $modx->cultureKey = $cultureKey = ($language->cultureKey ?: $language->key);
			$modx->setOption('cultureKey', $cultureKey);

            $modx->setPlaceholders(array(
                'cultureKey' => $cultureKey,
                'site_url' => $_SERVER['REQUEST_SCHEME'] . '://' . $language->http_host,
            ), '+');

            $modx->lexicon->load($cultureKey . ':localizator:site');
        }
		break;

    case 'OnPageNotFound':
        $localizator_key = $modx->localizator_key;
        $request = &$_REQUEST['q'];
		if($request == $localizator_key) {
		    $modx->sendRedirect($request . '/', array('responseCode' => 'HTTP/1.1 301 Moved Permanently'));
        } else if (preg_match('/^('.$localizator_key.')\//i', $request)) {
            $request = preg_replace('/^'.$localizator_key.'\//', '', $request);
        }
        $resource_id = (!$request) ? $modx->getOption('site_start', null, 1) : $modx->findResource($request);
        if($resource_id) {
            $modx->sendForward($resource_id);
        }
        break;

    case 'OnLoadWebDocument':
        $q = $modx->newQuery('localizatorContent');
        $q->leftJoin('localizatorLanguage','localizatorLanguage', 'localizatorLanguage.key = localizatorContent.key');
        $q->where(array(
            'localizatorContent.resource_id' => $modx->resource->id,
        ));
        $q->where(array(
            'localizatorLanguage.key' => $modx->localizator_key,
            'OR:localizatorLanguage.cultureKey:=' => $modx->localizator_key,
        ));
        $content = $modx->getObject('localizatorContent', $q);
        if($content) {
            $placeholders = array();
            $fields = explode(',', $modx->getOption('localizator_translate_fields'));
            foreach($fields as $field) {
                $value = $content->get($field);
                if($field == 'content') {
                    $placeholders['localizator_content'] = $value;
                    $modx->resource->set('localizator_content', $value);
                } else {
                    $placeholders[$field] = $value;
                    $modx->resource->set($field, $value);
                }
            }
            $modx->setPlaceholders($placeholders, '*');
        }
        //$modx->resource->cacheable = false;
        break;
}