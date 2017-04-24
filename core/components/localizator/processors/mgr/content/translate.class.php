<?php

class localizatorContentTranslateProcessor extends modProcessor {

    public function process() {
		$this->localizator = $this->modx->getService('localizator');

		if (!$resource_id = $this->getProperty('resource_id')) {
            return $this->failure('Не указан id ресурса');
        }

		if (!$default_language = $this->modx->getOption('localizator_default_language')) {
			return $this->failure('Не указана опция localizator_default_language, невозможно определить исходный языка для перевода');
		}

		$default_content = $this->modx->getObject('localizatorContent', array('key' => $default_language, 'resource_id' => $resource_id));
		if(!$default_content) {
			return $this->failure('Для автоматического перевода необходимо добавить хотя бы одну запись в таблицу');
		}

		$translate_translated = $this->modx->getOption('localizator_translate_translated', null, false);
		$translate_translated_fields = $this->modx->getOption('localizator_translate_translated_fields', null, false);
		$translate_fields = explode(',', $this->modx->getOption('localizator_translate_fields', null, 'pagetitle,longtitle,menutitle,seotitle,keywords,introtext,description,content'));


        $time = time();
        $time_limit = @ini_get('max_execution_time') - 20;
        if ($time_limit <= 5) {
            $time_limit = 5;
        }
        $start = $this->getProperty('start', 0);

        $c = $this->modx->newQuery('localizatorLanguage');
        if ($start == 0) {
            //$this->cleanTables();
        } else {
            $c->limit(1000000, $start);
        }
		$c->where(array(
			'key:!=' => $default_language
		));

		$languages = $this->modx->getIterator('localizatorLanguage', $c);
		foreach ($languages as $language) {
			//$this->modx->log(1, 'Перевод на ' . $language->key . ' - ' . $resource_id);

			$content = $this->modx->getObject('localizatorContent', array('key' => $language->key, 'resource_id' => $resource_id));
			if($content && $translate_translated) {
				foreach($translate_fields as $field) {
					$current = $content->get($field);
					$val = $default_content->get($field);
					if(empty($val)) continue;
					if(empty($current) || !empty($current) && $translate_translated_fields) {
						$content->set($field, $this->localizator->translator_Yandex($val, $default_language, ($language->cultureKey ?: $language->key)));
					}
				}
				$content->save();
			} else if(!$content) {
				$content = $this->modx->newObject('localizatorContent');
				$content->fromArray(array(
					'key' => $language->key,
					'resource_id' => $resource_id,
					'active' => 1,
				));
				foreach($translate_fields as $field) {
					$val = $default_content->get($field);
					if(!empty($val)) {
						$content->set($field, $this->localizator->translator_Yandex($val, $default_language, ($language->cultureKey ?: $language->key)));
					}
				}
				$content->save();
			}

			$start++;
			if ((time() - $time) >= $time_limit) {
                return $this->cleanup($start);
            }
		}

		return $this->cleanup($start);
    }


    public function cleanup($processed = 0)
    {
		$default_language = $this->modx->getOption('localizator_default_language');
		$c = $this->modx->newQuery('localizatorLanguage');
		$c->where(array('key:!=' => $default_language));
		$total = $this->modx->getCount('localizatorLanguage', $c);

        return $this->success('', array(
            'total' => $total,
            'processed' => $processed,
        ));
    }

}

return 'localizatorContentTranslateProcessor';